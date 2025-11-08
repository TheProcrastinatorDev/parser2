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

class BingSearchParser extends AbstractParser
{
    protected string $name = 'bing_search';
    protected array $supportedTypes = ['web', 'news', 'images'];

    protected function parseInternal(ParseRequestDTO $request): array
    {
        $url = $this->buildSearchUrl($request);
        $response = $this->httpClient->get($url, [
            'timeout' => $request->options['timeout'] ?? 30,
        ]);

        if (!$response->successful()) {
            throw new Exception("Failed to fetch Bing search: HTTP {$response->status()}");
        }

        $html = $response->body();

        return match ($request->type) {
            'news' => $this->parseNewsResults($html),
            'images' => $this->parseImageResults($html),
            default => $this->parseWebResults($html),
        };
    }

    private function buildSearchUrl(ParseRequestDTO $request): string
    {
        $url = $request->source;

        // Add pagination if specified
        if (isset($request->options['first'])) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . 'first=' . urlencode($request->options['first']);
        }

        return $url;
    }

    private function parseWebResults(string $html): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $items = [];
        $resultNodes = $xpath->query('//ol[@id="b_results"]/li[contains(@class, "b_algo")]');

        foreach ($resultNodes as $resultNode) {
            $titleNode = $xpath->query('.//h2/a', $resultNode);
            $title = $titleNode->length > 0 ? trim($titleNode->item(0)->textContent) : '';

            $linkNode = $xpath->query('.//h2/a', $resultNode);
            $url = $linkNode->length > 0 ? $linkNode->item(0)->getAttribute('href') : '';

            $descNode = $xpath->query('.//p', $resultNode);
            $description = $descNode->length > 0 ? trim($descNode->item(0)->textContent) : '';

            if ($title && $url) {
                $items[] = [
                    'title' => $title,
                    'url' => $url,
                    'content' => $description,
                    'author' => '',
                    'published_at' => null,
                    'tags' => [],
                    'images' => [],
                    'metadata' => [
                        'search_type' => 'web',
                    ],
                ];
            }
        }

        return $items;
    }

    private function parseNewsResults(string $html): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $items = [];
        $newsNodes = $xpath->query('//div[contains(@class, "news")]//div[contains(@class, "newsitem")]');

        foreach ($newsNodes as $newsNode) {
            $titleNode = $xpath->query('.//a', $newsNode);
            $title = $titleNode->length > 0 ? trim($titleNode->item(0)->textContent) : '';

            $linkNode = $xpath->query('.//a', $newsNode);
            $url = $linkNode->length > 0 ? $linkNode->item(0)->getAttribute('href') : '';

            $snippetNode = $xpath->query('.//div[contains(@class, "snippet")]', $newsNode);
            $snippet = $snippetNode->length > 0 ? trim($snippetNode->item(0)->textContent) : '';

            if ($title && $url) {
                $items[] = [
                    'title' => $title,
                    'url' => $url,
                    'content' => $snippet,
                    'author' => '',
                    'published_at' => null,
                    'tags' => [],
                    'images' => [],
                    'metadata' => [
                        'search_type' => 'news',
                    ],
                ];
            }
        }

        return $items;
    }

    private function parseImageResults(string $html): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $items = [];
        $imageNodes = $xpath->query('//div[contains(@class, "dg_b")]//div[contains(@class, "iusc")]');

        foreach ($imageNodes as $imageNode) {
            $imgNode = $xpath->query('.//img', $imageNode);
            $imageUrl = '';
            $title = '';

            if ($imgNode->length > 0) {
                $img = $imgNode->item(0);
                $imageUrl = $img->getAttribute('data-src') ?: $img->getAttribute('src');
                
                // Try to get title from various sources
                $titleNode = $xpath->query('.//div[contains(@class, "inflnk")]', $imageNode);
                if ($titleNode->length > 0) {
                    $title = trim($titleNode->item(0)->textContent);
                } else {
                    $title = $img->getAttribute('alt') ?: $img->getAttribute('title') ?: 'Image';
                }
            }

            // Get source URL
            $linkNode = $xpath->query('.//a', $imageNode);
            $sourceUrl = $linkNode->length > 0 ? $linkNode->item(0)->getAttribute('href') : '';

            if ($imageUrl) {
                $items[] = [
                    'title' => $title,
                    'url' => $sourceUrl ?: $imageUrl,
                    'content' => '',
                    'author' => '',
                    'published_at' => null,
                    'tags' => [],
                    'images' => [$imageUrl],
                    'metadata' => [
                        'search_type' => 'images',
                    ],
                ];
            }
        }

        return $items;
    }
}
