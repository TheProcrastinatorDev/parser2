<?php

namespace App\Services\Parser\Parsers;

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\AbstractParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\RateLimiter;
use Exception;
use SimpleXMLElement;

class FeedsParser extends AbstractParser
{
    protected string $name = 'feeds';
    protected array $supportedTypes = ['rss', 'atom', 'json', 'google_news', 'bing_news'];

    protected function parseInternal(ParseRequestDTO $request): array
    {
        $response = $this->httpClient->get($request->source, [
            'timeout' => $request->options['timeout'] ?? 30,
        ]);

        if (!$response->successful()) {
            throw new Exception("Failed to fetch feed: HTTP {$response->status()}");
        }

        $content = $response->body();
        $feedType = $this->detectFeedType($content, $request->type);

        $items = match ($feedType) {
            'json' => $this->parseJsonFeed($content),
            'atom' => $this->parseAtomFeed($content),
            default => $this->parseRssFeed($content),
        };

        // Apply max_items limit if specified
        $maxItems = $request->options['max_items'] ?? null;
        if ($maxItems !== null && count($items) > $maxItems) {
            $items = array_slice($items, 0, $maxItems);
        }

        return $items;
    }

    private function detectFeedType(string $content, string $requestedType): string
    {
        // If type is explicitly requested and valid, use it
        if (in_array($requestedType, ['rss', 'atom', 'json'])) {
            return $requestedType;
        }

        // Auto-detect from content
        if (str_starts_with(trim($content), '{')) {
            return 'json';
        }

        if (str_contains($content, '<feed') && str_contains($content, 'xmlns="http://www.w3.org/2005/Atom"')) {
            return 'atom';
        }

        return 'rss'; // Default to RSS
    }

    private function parseRssFeed(string $xml): array
    {
        libxml_use_internal_errors(true);
        $feed = @simplexml_load_string($xml);

        if ($feed === false) {
            throw new Exception('Invalid RSS XML: ' . implode(', ', array_map(fn($e) => $e->message, libxml_get_errors())));
        }

        $items = [];
        foreach ($feed->channel->item as $item) {
            $items[] = [
                'title' => (string) $item->title,
                'url' => (string) $item->link,
                'content' => (string) ($item->description ?? $item->content ?? ''),
                'author' => (string) ($item->author ?? ''),
                'published_at' => $this->parseDate((string) ($item->pubDate ?? '')),
                'tags' => [],
                'images' => $this->extractImagesFromContent((string) ($item->description ?? '')),
                'metadata' => [],
            ];
        }

        return $items;
    }

    private function parseAtomFeed(string $xml): array
    {
        libxml_use_internal_errors(true);
        $feed = @simplexml_load_string($xml);

        if ($feed === false) {
            throw new Exception('Invalid Atom XML: ' . implode(', ', array_map(fn($e) => $e->message, libxml_get_errors())));
        }

        $items = [];
        $feed->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');

        foreach ($feed->entry as $entry) {
            $link = (string) ($entry->link['href'] ?? $entry->link ?? '');
            
            $items[] = [
                'title' => (string) $entry->title,
                'url' => $link,
                'content' => (string) ($entry->content ?? $entry->summary ?? ''),
                'author' => (string) ($entry->author->name ?? ''),
                'published_at' => $this->parseDate((string) ($entry->published ?? $entry->updated ?? '')),
                'tags' => [],
                'images' => $this->extractImagesFromContent((string) ($entry->content ?? '')),
                'metadata' => [],
            ];
        }

        return $items;
    }

    private function parseJsonFeed(string $json): array
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON feed: ' . json_last_error_msg());
        }

        if (!isset($data['items']) || !is_array($data['items'])) {
            return [];
        }

        $items = [];
        foreach ($data['items'] as $item) {
            $items[] = [
                'title' => $item['title'] ?? '',
                'url' => $item['url'] ?? $item['external_url'] ?? '',
                'content' => $item['content_html'] ?? $item['content_text'] ?? $item['summary'] ?? '',
                'author' => $item['author']['name'] ?? '',
                'published_at' => $this->parseDate($item['date_published'] ?? ''),
                'tags' => $item['tags'] ?? [],
                'images' => $this->extractImagesFromContent($item['content_html'] ?? ''),
                'metadata' => [],
            ];
        }

        return $items;
    }

    private function parseDate(string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            $timestamp = strtotime($dateString);
            return $timestamp ? date('c', $timestamp) : null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function extractImagesFromContent(string $content): array
    {
        if (empty($content)) {
            return [];
        }

        return $this->contentExtractor->extractImages($content, '');
    }
}
