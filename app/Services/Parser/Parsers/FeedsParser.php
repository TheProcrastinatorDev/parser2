<?php

declare(strict_types=1);

namespace App\Services\Parser\Parsers;

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\AbstractParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;
use DOMDocument;
use Exception;

class FeedsParser extends AbstractParser
{
    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly ContentExtractor $contentExtractor,
    ) {}

    /**
     * Parse feed content.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    protected function doParse(ParseRequestDTO $request): array
    {
        // Fetch feed content
        $content = $this->httpClient->get($request->source);

        // Detect feed type
        $feedType = $this->detectFeedType($content, $request->type);

        // Parse based on detected type
        $items = match ($feedType) {
            'json' => $this->parseJsonFeed($content),
            'atom' => $this->parseAtomFeed($content),
            default => $this->parseRssFeed($content),
        };

        // Store detected type in metadata (will be added by AbstractParser)
        $this->detectedType = $feedType;

        return $items;
    }

    /**
     * Detect feed type from content.
     */
    private function detectFeedType(string $content, string $requestedType): string
    {
        // Use requested type if not auto-detect
        if ($requestedType !== 'auto' && in_array($requestedType, ['rss', 'atom', 'json'])) {
            return $requestedType;
        }

        // Try to detect JSON feed
        if ($this->isJsonFeed($content)) {
            return 'json';
        }

        // Try to detect Atom feed
        if (str_contains($content, '<feed') && str_contains($content, 'http://www.w3.org/2005/Atom')) {
            return 'atom';
        }

        // Default to RSS
        return 'rss';
    }

    /**
     * Check if content is JSON feed.
     */
    private function isJsonFeed(string $content): bool
    {
        $data = json_decode($content, true);

        return is_array($data) && isset($data['items']);
    }

    /**
     * Parse RSS 2.0 feed.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    private function parseRssFeed(string $content): array
    {
        libxml_use_internal_errors(true);

        $xml = new DOMDocument;
        if (! $xml->loadXML($content)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new Exception('Failed to parse RSS feed: Invalid XML');
        }

        $items = [];
        $itemNodes = $xml->getElementsByTagName('item');

        foreach ($itemNodes as $itemNode) {
            $item = [
                'title' => $this->getNodeValue($itemNode, 'title'),
                'url' => $this->getNodeValue($itemNode, 'link'),
                'description' => $this->getNodeValue($itemNode, 'description'),
                'published_at' => $this->getNodeValue($itemNode, 'pubDate'),
            ];

            // Extract enclosure (media)
            $enclosure = $itemNode->getElementsByTagName('enclosure')->item(0);
            if ($enclosure && $enclosure->hasAttribute('url')) {
                $item['enclosure'] = $enclosure->getAttribute('url');
            }

            // Extract images from description
            if ($item['description']) {
                $images = $this->contentExtractor->extractImages($item['description']);
                if (! empty($images)) {
                    $item['images'] = $images;
                }
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Parse Atom feed.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    private function parseAtomFeed(string $content): array
    {
        libxml_use_internal_errors(true);

        $xml = new DOMDocument;
        if (! $xml->loadXML($content)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new Exception('Failed to parse Atom feed: Invalid XML');
        }

        $items = [];
        $entryNodes = $xml->getElementsByTagName('entry');

        foreach ($entryNodes as $entryNode) {
            $item = [
                'title' => $this->getNodeValue($entryNode, 'title'),
                'description' => $this->getNodeValue($entryNode, 'summary')
                    ?: $this->getNodeValue($entryNode, 'content'),
                'published_at' => $this->getNodeValue($entryNode, 'published')
                    ?: $this->getNodeValue($entryNode, 'updated'),
            ];

            // Get link
            $linkNode = $entryNode->getElementsByTagName('link')->item(0);
            if ($linkNode && $linkNode->hasAttribute('href')) {
                $item['url'] = $linkNode->getAttribute('href');
            }

            // Extract images from description
            if ($item['description']) {
                $images = $this->contentExtractor->extractImages($item['description']);
                if (! empty($images)) {
                    $item['images'] = $images;
                }
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Parse JSON feed.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    private function parseJsonFeed(string $content): array
    {
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse JSON feed: '.json_last_error_msg());
        }

        if (! isset($data['items']) || ! is_array($data['items'])) {
            throw new Exception('Invalid JSON feed: missing items array');
        }

        $items = [];

        foreach ($data['items'] as $jsonItem) {
            $item = [
                'title' => $jsonItem['title'] ?? '',
                'url' => $jsonItem['url'] ?? ($jsonItem['id'] ?? ''),
                'description' => $jsonItem['content_text']
                    ?? strip_tags($jsonItem['content_html'] ?? '')
                    ?? $jsonItem['summary'] ?? '',
                'published_at' => $jsonItem['date_published'] ?? ($jsonItem['date_modified'] ?? null),
            ];

            // Extract images from HTML content
            if (isset($jsonItem['content_html'])) {
                $images = $this->contentExtractor->extractImages($jsonItem['content_html']);
                if (! empty($images)) {
                    $item['images'] = $images;
                }
            }

            // Add image from JSON feed's image field
            if (isset($jsonItem['image'])) {
                $item['images'] = array_merge($item['images'] ?? [], [$jsonItem['image']]);
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Get value from XML node.
     */
    private function getNodeValue($node, string $tagName): ?string
    {
        $elements = $node->getElementsByTagName($tagName);
        if ($elements->length > 0) {
            return trim($elements->item(0)->nodeValue);
        }

        return null;
    }

    /**
     * Build metadata with detected type.
     *
     * @return array<string, mixed>
     */
    protected function buildMetadata(ParseRequestDTO $request): array
    {
        $metadata = parent::buildMetadata($request);

        if (isset($this->detectedType)) {
            $metadata['detected_type'] = $this->detectedType;
        }

        return $metadata;
    }

    /**
     * Get parser name.
     */
    protected function getName(): string
    {
        return 'feeds';
    }

    /**
     * Detected feed type (stored for metadata).
     */
    private ?string $detectedType = null;
}
