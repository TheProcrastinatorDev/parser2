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

class SinglePageParser extends AbstractParser
{
    protected string $name = 'single_page';
    protected array $supportedTypes = ['auto', 'css', 'xpath', 'regex'];

    protected function parseInternal(ParseRequestDTO $request): array
    {
        $response = $this->httpClient->get($request->source, [
            'timeout' => $request->options['timeout'] ?? 30,
        ]);

        if (!$response->successful()) {
            throw new Exception("Failed to fetch page: HTTP {$response->status()}");
        }

        $html = $response->body();
        $content = $this->extractContent($html, $request);
        $cleanedContent = $this->contentExtractor->cleanHtml($content);
        $fixedContent = $this->contentExtractor->fixRelativeUrls($cleanedContent, $request->source);
        $images = $this->contentExtractor->extractImages($html, $request->source);
        $meta = $this->contentExtractor->extractMetaTags($html);

        return [
            [
                'title' => $meta['og:title'] ?? $meta['title'] ?? $this->extractTitle($html),
                'url' => $request->source,
                'content' => $fixedContent,
                'author' => $meta['author'] ?? $meta['og:author'] ?? '',
                'published_at' => $meta['article:published_time'] ?? $meta['og:published_time'] ?? null,
                'tags' => [],
                'images' => $images,
                'metadata' => $meta,
            ],
        ];
    }

    private function extractContent(string $html, ParseRequestDTO $request): string
    {
        return match ($request->type) {
            'css' => $this->extractByCss($html, $request->options['selector'] ?? 'body'),
            'xpath' => $this->extractByXPath($html, $request->options['xpath'] ?? '//body'),
            'regex' => $this->extractByRegex($html, $request->options['pattern'] ?? '/<body[^>]*>(.*?)<\/body>/is'),
            default => $this->extractAuto($html),
        };
    }

    private function extractByCss(string $html, string $selector): string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

        // Simple CSS selector to XPath conversion for common cases
        $xpath = new DOMXPath($dom);
        
        // Convert class selector
        if (str_starts_with($selector, '.')) {
            $class = substr($selector, 1);
            $nodes = $xpath->query("//*[contains(@class, '{$class}')]");
        }
        // Convert ID selector
        elseif (str_starts_with($selector, '#')) {
            $id = substr($selector, 1);
            $nodes = $xpath->query("//*[@id='{$id}']");
        }
        // Tag selector
        else {
            $nodes = $xpath->query("//{$selector}");
        }

        if ($nodes->length === 0) {
            return '';
        }

        $content = '';
        foreach ($nodes as $node) {
            $content .= $dom->saveHTML($node);
        }

        return $content;
    }

    private function extractByXPath(string $html, string $xpathExpr): string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $nodes = $xpath->query($xpathExpr);
        if ($nodes->length === 0) {
            return '';
        }

        $content = '';
        foreach ($nodes as $node) {
            $content .= $dom->saveHTML($node);
        }

        return $content;
    }

    private function extractByRegex(string $html, string $pattern): string
    {
        if (preg_match($pattern, $html, $matches)) {
            return $matches[1] ?? $matches[0] ?? '';
        }

        return '';
    }

    private function extractAuto(string $html): string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        // Try common content selectors
        $selectors = [
            '//article',
            '//main',
            '//*[@class="content"]',
            '//*[@id="content"]',
            '//*[@class="article"]',
            '//*[@class="post"]',
            '//body',
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $content = '';
                foreach ($nodes as $node) {
                    $content .= $dom->saveHTML($node);
                }
                if (strlen(trim(strip_tags($content))) > 100) {
                    return $content;
                }
            }
        }

        // Fallback to body
        $body = $xpath->query('//body');
        return $body->length > 0 ? $dom->saveHTML($body->item(0)) : '';
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
}
