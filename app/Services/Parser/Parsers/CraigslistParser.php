<?php

namespace App\Services\Parser\Parsers;

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\AbstractParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\RateLimiter;
use DOMDocument;
use DOMXPath;
use Exception;

class CraigslistParser extends AbstractParser
{
    protected string $name = 'craigslist';
    protected array $supportedTypes = ['search', 'listing'];

    protected function parseInternal(ParseRequestDTO $request): array
    {
        $url = $this->buildCraigslistUrl($request);
        $response = $this->httpClient->get($url, [
            'timeout' => $request->options['timeout'] ?? 30,
        ]);

        // Check for IP blocking
        if ($response->status() === 403 || $this->isBlocked($response->body())) {
            throw new Exception('IP address may be blocked by Craigslist. Please wait before retrying.');
        }

        if (!$response->successful()) {
            throw new Exception("Failed to fetch Craigslist page: HTTP {$response->status()}");
        }

        $html = $response->body();

        return match ($request->type) {
            'listing' => $this->parseListing($html, $url),
            default => $this->parseSearchResults($html, $url),
        };
    }

    private function buildCraigslistUrl(ParseRequestDTO $request): string
    {
        $url = $request->source;

        // Add pagination for search results
        if ($request->type === 'search' && isset($request->options['page']) && $request->options['page'] > 1) {
            $offset = ($request->options['page'] - 1) * 120; // 120 items per page
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . 's=' . $offset;
        }

        return $url;
    }

    private function isBlocked(string $html): bool
    {
        $blockedIndicators = [
            'Access Denied',
            'Your IP has been blocked',
            'blocked',
            'temporarily unavailable',
        ];

        foreach ($blockedIndicators as $indicator) {
            if (stripos($html, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    private function parseSearchResults(string $html, string $baseUrl): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $items = [];
        $resultNodes = $xpath->query('//ul[contains(@class, "rows")]//li[contains(@class, "result-row")]');

        foreach ($resultNodes as $resultNode) {
            $titleNode = $xpath->query('.//a[contains(@class, "result-title")]', $resultNode);
            $title = $titleNode->length > 0 ? trim($titleNode->item(0)->textContent) : '';

            $linkNode = $xpath->query('.//a[contains(@class, "result-title")]', $resultNode);
            $relativeUrl = $linkNode->length > 0 ? $linkNode->item(0)->getAttribute('href') : '';
            $url = $this->makeAbsoluteUrl($relativeUrl, $baseUrl);

            $priceNode = $xpath->query('.//span[contains(@class, "result-price")]', $resultNode);
            $price = $priceNode->length > 0 ? trim($priceNode->item(0)->textContent) : '';

            $hoodNode = $xpath->query('.//span[contains(@class, "result-hood")]', $resultNode);
            $location = $hoodNode->length > 0 ? trim($hoodNode->item(0)->textContent, ' ()') : '';

            if ($title && $url) {
                $items[] = [
                    'title' => $title,
                    'url' => $url,
                    'content' => '',
                    'author' => '',
                    'published_at' => null,
                    'tags' => [],
                    'images' => [],
                    'metadata' => [
                        'price' => $price,
                        'location' => $location,
                        'listing_type' => 'search_result',
                    ],
                ];
            }
        }

        return $items;
    }

    private function parseListing(string $html, string $url): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $titleNode = $xpath->query('//h2[contains(@class, "postingtitle")]');
        $title = $titleNode->length > 0 ? trim($titleNode->item(0)->textContent) : '';

        $priceNode = $xpath->query('//span[contains(@class, "price")]');
        $price = $priceNode->length > 0 ? trim($priceNode->item(0)->textContent) : '';

        $locationNode = $xpath->query('//div[contains(@class, "mapaddress")]');
        $location = $locationNode->length > 0 ? trim($locationNode->item(0)->textContent) : '';

        $bodyNode = $xpath->query('//section[@id="postingbody"]');
        $content = $bodyNode->length > 0 ? trim($bodyNode->item(0)->textContent) : '';

        $images = $this->extractImages($xpath);
        $coordinates = $this->extractCoordinates($xpath);

        return [
            [
                'title' => $title,
                'url' => $url,
                'content' => $content,
                'author' => '',
                'published_at' => null,
                'tags' => [],
                'images' => $images,
                'metadata' => [
                    'price' => $price,
                    'location' => $location,
                    'coordinates' => $coordinates,
                    'listing_type' => 'full_listing',
                    'image_count' => count($images),
                ],
            ],
        ];
    }

    private function extractImages(DOMXPath $xpath): array
    {
        $images = [];
        $thumbNodes = $xpath->query('//div[@id="thumbs"]//img');

        foreach ($thumbNodes as $thumbNode) {
            $src = $thumbNode->getAttribute('src');
            if ($src) {
                // Convert thumbnail URL to full image URL if needed
                $fullImageUrl = str_replace('_50x50c', '', $src);
                $images[] = $fullImageUrl;
            }
        }

        return array_unique($images);
    }

    private function extractCoordinates(DOMXPath $xpath): ?array
    {
        $mapNode = $xpath->query('//div[@id="map"]');
        if ($mapNode->length === 0) {
            return null;
        }

        $dataLat = $mapNode->item(0)->getAttribute('data-latitude');
        $dataLon = $mapNode->item(0)->getAttribute('data-longitude');

        if ($dataLat && $dataLon) {
            return [
                'latitude' => (float) $dataLat,
                'longitude' => (float) $dataLon,
            ];
        }

        return null;
    }

    private function makeAbsoluteUrl(string $relativeUrl, string $baseUrl): string
    {
        if (empty($relativeUrl)) {
            return $baseUrl;
        }

        // Already absolute
        if (preg_match('/^https?:\/\//', $relativeUrl)) {
            return $relativeUrl;
        }

        // Parse base URL
        $baseParts = parse_url($baseUrl);
        $scheme = $baseParts['scheme'] ?? 'https';
        $host = $baseParts['host'] ?? '';

        // Relative URL starting with /
        if (str_starts_with($relativeUrl, '/')) {
            return $scheme . '://' . $host . $relativeUrl;
        }

        // Relative to current path
        $basePath = dirname($baseParts['path'] ?? '/');
        return $scheme . '://' . $host . rtrim($basePath, '/') . '/' . ltrim($relativeUrl, '/');
    }
}
