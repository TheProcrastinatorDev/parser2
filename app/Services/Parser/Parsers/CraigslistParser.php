<?php

declare(strict_types=1);

namespace App\Services\Parser\Parsers;

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\AbstractParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;
use DOMDocument;
use DOMXPath;

class CraigslistParser extends AbstractParser
{
    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly ContentExtractor $contentExtractor,
    ) {}

    /**
     * Parse Craigslist search results.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function doParse(ParseRequestDTO $request): array
    {
        // Build search URL
        $url = $this->buildSearchUrl($request);

        // Fetch search results HTML
        $html = $this->httpClient->get($url);

        // Parse search results from HTML
        return $this->extractListings($html);
    }

    /**
     * Build Craigslist search URL from request.
     */
    private function buildSearchUrl(ParseRequestDTO $request): string
    {
        // Start with source URL
        $url = $request->source;

        $params = [];

        // Add search query if keywords provided
        if (! empty($request->keywords)) {
            $params['query'] = implode(' ', $request->keywords);
        }

        // Add offset for pagination
        $params['s'] = $request->offset ?? 0;

        // Build query string
        if (! empty($params)) {
            $queryString = http_build_query($params);
            $url .= (str_contains($url, '?') ? '&' : '?').$queryString;
        }

        return $url;
    }

    /**
     * Extract listings from Craigslist HTML.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractListings(string $html): array
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        // Add UTF-8 meta tag to ensure proper encoding
        $html = '<?xml encoding="UTF-8">'.$html;
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);

        // Find all listing rows
        $listingNodes = $xpath->query('//ul[contains(@class, "rows")]//li[contains(@class, "result-row")]');

        if (! $listingNodes || $listingNodes->length === 0) {
            libxml_clear_errors();

            return [];
        }

        $items = [];
        $seenPostIds = [];

        foreach ($listingNodes as $listingNode) {
            // Get post ID to filter duplicates
            $postId = $listingNode->getAttribute('data-pid');

            // Skip duplicates
            if (! empty($postId) && isset($seenPostIds[$postId])) {
                continue;
            }

            if (! empty($postId)) {
                $seenPostIds[$postId] = true;
            }

            $item = [
                'post_id' => $postId,
                'title' => $this->extractTitle($listingNode, $xpath),
                'url' => $this->extractUrl($listingNode, $xpath),
                'price' => $this->extractPrice($listingNode, $xpath),
                'location' => $this->extractLocation($listingNode, $xpath),
                'posted_at' => $this->extractPostedTime($listingNode, $xpath),
                'housing' => $this->extractHousingInfo($listingNode, $xpath),
                'has_image' => $this->hasImage($listingNode, $xpath),
                'image_ids' => $this->extractImageIds($listingNode, $xpath),
            ];

            $items[] = $item;
        }

        libxml_clear_errors();

        return $items;
    }

    /**
     * Extract listing title.
     */
    private function extractTitle(\DOMElement $listingNode, DOMXPath $xpath): ?string
    {
        $titleNodes = $xpath->query('.//a[contains(@class, "result-title")]', $listingNode);

        if ($titleNodes && $titleNodes->length > 0) {
            return trim($titleNodes->item(0)->nodeValue);
        }

        return null;
    }

    /**
     * Extract listing URL.
     */
    private function extractUrl(\DOMElement $listingNode, DOMXPath $xpath): ?string
    {
        $linkNodes = $xpath->query('.//a[contains(@class, "result-title")][@href]', $listingNode);

        if ($linkNodes && $linkNodes->length > 0) {
            $href = $linkNodes->item(0)->getAttribute('href');

            // Convert relative URLs to absolute
            if (str_starts_with($href, '/')) {
                return 'https://craigslist.org'.$href;
            }

            return $href;
        }

        return null;
    }

    /**
     * Extract listing price.
     */
    private function extractPrice(\DOMElement $listingNode, DOMXPath $xpath): ?string
    {
        $priceNodes = $xpath->query('.//span[contains(@class, "result-price")]', $listingNode);

        if ($priceNodes && $priceNodes->length > 0) {
            return trim($priceNodes->item(0)->nodeValue);
        }

        return null;
    }

    /**
     * Extract listing location.
     */
    private function extractLocation(\DOMElement $listingNode, DOMXPath $xpath): ?string
    {
        $locationNodes = $xpath->query('.//span[contains(@class, "result-hood")]', $listingNode);

        if ($locationNodes && $locationNodes->length > 0) {
            return trim($locationNodes->item(0)->nodeValue);
        }

        return null;
    }

    /**
     * Extract posted time.
     */
    private function extractPostedTime(\DOMElement $listingNode, DOMXPath $xpath): ?string
    {
        $timeNodes = $xpath->query('.//time[@datetime]', $listingNode);

        if ($timeNodes && $timeNodes->length > 0) {
            return $timeNodes->item(0)->getAttribute('datetime');
        }

        return null;
    }

    /**
     * Extract housing information (bedrooms, square footage).
     */
    private function extractHousingInfo(\DOMElement $listingNode, DOMXPath $xpath): ?string
    {
        $housingNodes = $xpath->query('.//span[contains(@class, "housing")]', $listingNode);

        if ($housingNodes && $housingNodes->length > 0) {
            return trim($housingNodes->item(0)->nodeValue);
        }

        return null;
    }

    /**
     * Check if listing has images.
     */
    private function hasImage(\DOMElement $listingNode, DOMXPath $xpath): bool
    {
        $imageNodes = $xpath->query('.//a[contains(@class, "result-image")]', $listingNode);

        return $imageNodes && $imageNodes->length > 0;
    }

    /**
     * Extract image IDs from data-ids attribute.
     */
    private function extractImageIds(\DOMElement $listingNode, DOMXPath $xpath): ?string
    {
        $imageNodes = $xpath->query('.//a[contains(@class, "result-image")][@data-ids]', $listingNode);

        if ($imageNodes && $imageNodes->length > 0) {
            return $imageNodes->item(0)->getAttribute('data-ids');
        }

        return null;
    }

    /**
     * Get parser name.
     */
    protected function getName(): string
    {
        return 'craigslist';
    }
}
