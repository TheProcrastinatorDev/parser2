<?php

declare(strict_types=1);

namespace App\Services\Parser\Parsers;

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\AbstractParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;
use DOMDocument;
use DOMXPath;

class BingSearchParser extends AbstractParser
{
    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly ContentExtractor $contentExtractor,
    ) {}

    /**
     * Parse Bing search results.
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
        return $this->extractSearchResults($html);
    }

    /**
     * Build Bing search URL from request.
     */
    private function buildSearchUrl(ParseRequestDTO $request): string
    {
        $query = urlencode($request->source);

        // Determine count (limit)
        $count = $request->limit ?? 50;

        // Build base URL
        $url = "https://www.bing.com/search?q={$query}&count={$count}";

        // Add offset (pagination)
        if ($request->offset) {
            // Bing uses 'first' parameter, starting from 1
            $first = $request->offset + 1;
            $url .= "&first={$first}";
        }

        // Add filters if provided
        if (! empty($request->filters)) {
            foreach ($request->filters as $key => $value) {
                $filterValue = urlencode("{$key}:\"{$value}\"");
                $url .= "&filters={$filterValue}";
            }
        }

        return $url;
    }

    /**
     * Extract search results from Bing HTML.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractSearchResults(string $html): array
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        // Add UTF-8 meta tag to ensure proper encoding
        $html = '<?xml encoding="UTF-8">'.$html;
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);

        // Find all search result items
        $resultNodes = $xpath->query('//ol[@id="b_results"]/li[contains(@class, "b_algo")]');

        if (! $resultNodes || $resultNodes->length === 0) {
            libxml_clear_errors();

            return [];
        }

        $items = [];

        foreach ($resultNodes as $resultNode) {
            $item = [
                'title' => $this->extractTitle($resultNode, $xpath),
                'url' => $this->extractUrl($resultNode, $xpath),
                'description' => $this->extractDescription($resultNode, $xpath),
                'domain' => $this->extractDomain($resultNode, $xpath),
                'type' => $this->detectResultType($resultNode),
                'published_time' => $this->extractPublishedTime($resultNode, $xpath),
            ];

            $items[] = $item;
        }

        libxml_clear_errors();

        return $items;
    }

    /**
     * Extract result title.
     */
    private function extractTitle(\DOMElement $resultNode, DOMXPath $xpath): ?string
    {
        $titleNodes = $xpath->query('.//h2/a', $resultNode);

        if ($titleNodes && $titleNodes->length > 0) {
            return trim($titleNodes->item(0)->nodeValue);
        }

        return null;
    }

    /**
     * Extract result URL.
     */
    private function extractUrl(\DOMElement $resultNode, DOMXPath $xpath): ?string
    {
        $linkNodes = $xpath->query('.//h2/a[@href]', $resultNode);

        if ($linkNodes && $linkNodes->length > 0) {
            return $linkNodes->item(0)->getAttribute('href');
        }

        return null;
    }

    /**
     * Extract result description.
     */
    private function extractDescription(\DOMElement $resultNode, DOMXPath $xpath): ?string
    {
        $descNodes = $xpath->query('.//p', $resultNode);

        if ($descNodes && $descNodes->length > 0) {
            $descHtml = $descNodes->item(0)->ownerDocument->saveHTML($descNodes->item(0));

            return $this->contentExtractor->extractText($descHtml);
        }

        return null;
    }

    /**
     * Extract domain/cite information.
     */
    private function extractDomain(\DOMElement $resultNode, DOMXPath $xpath): ?string
    {
        $citeNodes = $xpath->query('.//div[contains(@class, "b_attribution")]//cite', $resultNode);

        if ($citeNodes && $citeNodes->length > 0) {
            return trim($citeNodes->item(0)->nodeValue);
        }

        return null;
    }

    /**
     * Detect result type (web, video, news, etc.).
     */
    private function detectResultType(\DOMElement $resultNode): string
    {
        $classList = $resultNode->getAttribute('class');

        if (str_contains($classList, 'b_vidans')) {
            return 'video';
        }

        if (str_contains($classList, 'b_news')) {
            return 'news';
        }

        if (str_contains($classList, 'b_imageans')) {
            return 'image';
        }

        return 'web';
    }

    /**
     * Extract published time (for news results).
     */
    private function extractPublishedTime(\DOMElement $resultNode, DOMXPath $xpath): ?string
    {
        $timeNodes = $xpath->query('.//span[contains(@class, "news_dt")]', $resultNode);

        if ($timeNodes && $timeNodes->length > 0) {
            return trim($timeNodes->item(0)->nodeValue);
        }

        return null;
    }

    /**
     * Get parser name.
     */
    protected function getName(): string
    {
        return 'bing';
    }
}
