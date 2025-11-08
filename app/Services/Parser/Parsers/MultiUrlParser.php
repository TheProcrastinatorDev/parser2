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

class MultiUrlParser extends AbstractParser
{
    protected string $name = 'multi_url';
    protected array $supportedTypes = ['xpath', 'css', 'regex', 'list'];

    public function __construct(
        HttpClient $httpClient,
        ContentExtractor $contentExtractor,
        RateLimiter $rateLimiter,
    ) {
        parent::__construct($httpClient, $contentExtractor, $rateLimiter);
    }

    protected function parseInternal(ParseRequestDTO $request): array
    {
        $urls = $this->extractUrls($request);
        $urls = $this->deduplicateUrls($urls);
        $urls = $this->fixRelativeUrls($urls, $request->source);

        // Process each URL using SinglePageParser pattern
        $items = [];
        foreach ($urls as $url) {
            try {
                $pageResponse = $this->httpClient->get($url);
                if (!$pageResponse->successful()) {
                    continue;
                }

                $pageHtml = $pageResponse->body();
                $cleanedContent = $this->contentExtractor->cleanHtml($pageHtml);
                $fixedContent = $this->contentExtractor->fixRelativeUrls($cleanedContent, $url);
                $images = $this->contentExtractor->extractImages($pageHtml, $url);
                $meta = $this->contentExtractor->extractMetaTags($pageHtml);

                $items[] = [
                    'title' => $meta['og:title'] ?? $meta['title'] ?? $this->extractTitle($pageHtml),
                    'url' => $url,
                    'content' => $fixedContent,
                    'author' => $meta['author'] ?? $meta['og:author'] ?? '',
                    'published_at' => $meta['article:published_time'] ?? $meta['og:published_time'] ?? null,
                    'tags' => [],
                    'images' => $images,
                    'metadata' => $meta,
                ];
            } catch (Exception $e) {
                // Skip failed URLs, continue with others
                continue;
            }
        }

        return $items;
    }

    private function extractUrls(ParseRequestDTO $request): array
    {
        return match ($request->type) {
            'xpath' => $this->extractUrlsByXPath($request),
            'css' => $this->extractUrlsByCss($request),
            'regex' => $this->extractUrlsByRegex($request),
            'list' => $this->extractUrlsFromList($request),
            default => [],
        };
    }

    private function extractUrlsByXPath(ParseRequestDTO $request): array
    {
        $response = $this->httpClient->get($request->source);
        if (!$response->successful()) {
            throw new Exception("Failed to fetch page: HTTP {$response->status()}");
        }

        $html = $response->body();
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $xpathExpr = $request->options['xpath'] ?? '//a/@href';
        $nodes = $xpath->query($xpathExpr);

        $urls = [];
        foreach ($nodes as $node) {
            $url = $node->nodeValue ?? $node->textContent ?? '';
            if ($url) {
                $urls[] = trim($url);
            }
        }

        return $urls;
    }

    private function extractUrlsByCss(ParseRequestDTO $request): array
    {
        $response = $this->httpClient->get($request->source);
        if (!$response->successful()) {
            throw new Exception("Failed to fetch page: HTTP {$response->status()}");
        }

        $html = $response->body();
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $selector = $request->options['selector'] ?? 'a';
        $attribute = $request->options['attribute'] ?? 'href';

        // Convert CSS selector to XPath (simple cases)
        $xpathExpr = $this->cssToXPath($selector);
        $nodes = $xpath->query($xpathExpr);

        $urls = [];
        foreach ($nodes as $node) {
            $url = $node->getAttribute($attribute);
            if ($url) {
                $urls[] = trim($url);
            }
        }

        return $urls;
    }

    private function extractUrlsByRegex(ParseRequestDTO $request): array
    {
        $response = $this->httpClient->get($request->source);
        if (!$response->successful()) {
            throw new Exception("Failed to fetch page: HTTP {$response->status()}");
        }

        $html = $response->body();
        $pattern = $request->options['pattern'] ?? '/https?:\/\/[^\s<>"\']+/';

        if (preg_match_all($pattern, $html, $matches)) {
            return array_unique($matches[0]);
        }

        return [];
    }

    private function extractUrlsFromList(ParseRequestDTO $request): array
    {
        $urls = $request->options['urls'] ?? [];
        
        if (!is_array($urls)) {
            return [];
        }

        return array_filter($urls, fn($url) => filter_var($url, FILTER_VALIDATE_URL));
    }

    private function deduplicateUrls(array $urls): array
    {
        // Normalize URLs (remove trailing slashes, fragments, etc.)
        $normalized = [];
        foreach ($urls as $url) {
            $normalizedUrl = rtrim(parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH), '/');
            $normalized[$normalizedUrl] = $url;
        }

        return array_values($normalized);
    }

    private function fixRelativeUrls(array $urls, string $baseUrl): array
    {
        $baseParts = parse_url($baseUrl);
        $scheme = $baseParts['scheme'] ?? 'https';
        $host = $baseParts['host'] ?? '';

        return array_map(function ($url) use ($scheme, $host, $baseUrl) {
            // Already absolute
            if (preg_match('/^https?:\/\//', $url)) {
                return $url;
            }

            // Relative URL
            if (str_starts_with($url, '/')) {
                return $scheme . '://' . $host . $url;
            }

            // Relative to current path
            $basePath = dirname(parse_url($baseUrl, PHP_URL_PATH));
            return $scheme . '://' . $host . rtrim($basePath, '/') . '/' . ltrim($url, '/');
        }, $urls);
    }

    private function extractTitle(string $html): string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $titleNodes = $xpath->query('//title');
        if ($titleNodes->length > 0) {
            return trim($titleNodes->item(0)->textContent);
        }

        $h1Nodes = $xpath->query('//h1');
        if ($h1Nodes->length > 0) {
            return trim($h1Nodes->item(0)->textContent);
        }

        return '';
    }

    private function cssToXPath(string $selector): string
    {
        // Simple CSS to XPath conversion for common cases
        if (str_starts_with($selector, '.')) {
            $class = substr($selector, 1);
            return "//*[contains(@class, '{$class}')]";
        }

        if (str_starts_with($selector, '#')) {
            $id = substr($selector, 1);
            return "//*[@id='{$id}']";
        }

        return "//{$selector}";
    }
}
