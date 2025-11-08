<?php

namespace App\Services\Parser\Parsers;

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\AbstractParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\RateLimiter;
use Exception;
use SimpleXMLElement;

class MediumParser extends AbstractParser
{
    protected string $name = 'medium';
    protected array $supportedTypes = ['user', 'publication', 'tag'];

    protected function parseInternal(ParseRequestDTO $request): array
    {
        $url = $this->buildMediumUrl($request->source, $request->type);
        $response = $this->httpClient->get($url, [
            'timeout' => $request->options['timeout'] ?? 30,
        ]);

        if (!$response->successful()) {
            throw new Exception("Failed to fetch Medium feed: HTTP {$response->status()}");
        }

        $content = $response->body();
        $feedType = $this->detectFeedType($content);

        return match ($feedType) {
            'atom' => $this->parseAtomFeed($content),
            default => $this->parseRssFeed($content),
        };
    }

    private function buildMediumUrl(string $source, string $type): string
    {
        // If source already ends with /feed, return as is
        if (str_ends_with($source, '/feed')) {
            return $source;
        }

        // Build URL based on type
        return match ($type) {
            'user' => rtrim($source, '/') . '/feed',
            'publication' => rtrim($source, '/') . '/feed',
            'tag' => "https://medium.com/feed/tag/{$source}",
            default => rtrim($source, '/') . '/feed',
        };
    }

    private function detectFeedType(string $content): string
    {
        if (str_contains($content, '<feed') && str_contains($content, 'xmlns="http://www.w3.org/2005/Atom"')) {
            return 'atom';
        }

        return 'rss';
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
            $content = (string) ($item->children('http://purl.org/rss/1.0/modules/content/')->encoded ?? $item->description ?? '');
            $readTime = $this->extractReadTime($content);

            $items[] = [
                'title' => (string) $item->title,
                'url' => (string) $item->link,
                'content' => strip_tags($content),
                'author' => (string) ($item->children('http://purl.org/dc/elements/1.1/')->creator ?? $this->extractAuthorFromUrl((string) $item->link)),
                'published_at' => $this->parseDate((string) ($item->pubDate ?? '')),
                'tags' => [],
                'images' => $this->extractImagesFromContent($content),
                'metadata' => [
                    'read_time' => $readTime,
                    'estimated_read_time' => $readTime ?: $this->estimateReadTime($content),
                ],
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
            $content = (string) ($entry->content ?? $entry->summary ?? '');
            $readTime = $this->extractReadTime($content);

            $items[] = [
                'title' => (string) $entry->title,
                'url' => $link,
                'content' => strip_tags($content),
                'author' => (string) ($entry->author->name ?? $this->extractAuthorFromUrl($link)),
                'published_at' => $this->parseDate((string) ($entry->published ?? $entry->updated ?? '')),
                'tags' => [],
                'images' => $this->extractImagesFromContent($content),
                'metadata' => [
                    'read_time' => $readTime,
                    'estimated_read_time' => $readTime ?: $this->estimateReadTime($content),
                ],
            ];
        }

        return $items;
    }

    private function extractReadTime(string $content): ?int
    {
        // Look for "X min read" pattern
        if (preg_match('/(\d+)\s*min\s*read/i', $content, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function estimateReadTime(string $content): int
    {
        // Estimate: ~200 words per minute
        $wordCount = str_word_count(strip_tags($content));
        return max(1, (int) ceil($wordCount / 200));
    }

    private function extractAuthorFromUrl(string $url): string
    {
        if (preg_match('#medium\.com/@([^/]+)#', $url, $matches)) {
            return '@' . $matches[1];
        }

        return '';
    }

    private function extractImagesFromContent(string $content): array
    {
        return $this->contentExtractor->extractImages($content, '');
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
}
