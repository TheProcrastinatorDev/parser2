<?php

declare(strict_types=1);

namespace App\Services\Parser\Parsers;

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\AbstractParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;
use DOMDocument;
use DOMXPath;

class SinglePageParser extends AbstractParser
{
    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly ContentExtractor $contentExtractor,
    ) {}

    /**
     * Parse single page content.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function doParse(ParseRequestDTO $request): array
    {
        // Fetch page HTML
        $html = $this->httpClient->get($request->source);

        // Clean HTML if requested
        if ($request->options['clean_html'] ?? false) {
            $html = $this->contentExtractor->cleanHtml($html);
        }

        // Fix relative URLs
        $html = $this->contentExtractor->fixRelativeUrls($html, $request->source);

        // Extract content based on type
        $extractedHtml = match ($request->type) {
            'css' => $this->extractByCssSelector($html, $request->options['selector'] ?? ''),
            'xpath' => $this->extractByXPath($html, $request->options['selector'] ?? ''),
            default => $this->autoExtractContent($html),
        };

        // Build single item
        $item = [
            'url' => $request->source,
            'title' => $this->extractTitle($html),
            'description' => $this->extractMetaDescription($html),
            'html' => $extractedHtml,
            'content' => $this->contentExtractor->extractText($extractedHtml),
            'images' => $this->contentExtractor->extractImages($extractedHtml),
        ];

        return [$item];
    }

    /**
     * Extract content using CSS selector.
     */
    private function extractByCssSelector(string $html, string $selector): string
    {
        if (empty($selector)) {
            return $html;
        }

        libxml_use_internal_errors(true);

        $dom = new DOMDocument;
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);

        // Convert CSS selector to XPath (simplified conversion)
        $xpathQuery = $this->cssToXPath($selector);

        $nodes = $xpath->query($xpathQuery);

        if ($nodes && $nodes->length > 0) {
            $extracted = $dom->saveHTML($nodes->item(0));
            libxml_clear_errors();

            return $extracted ?: $html;
        }

        libxml_clear_errors();

        return $html;
    }

    /**
     * Extract content using XPath.
     */
    private function extractByXPath(string $html, string $xpathQuery): string
    {
        if (empty($xpathQuery)) {
            return $html;
        }

        libxml_use_internal_errors(true);

        $dom = new DOMDocument;
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query($xpathQuery);

        if ($nodes && $nodes->length > 0) {
            $extracted = $dom->saveHTML($nodes->item(0));
            libxml_clear_errors();

            return $extracted ?: $html;
        }

        libxml_clear_errors();

        return $html;
    }

    /**
     * Auto-detect and extract main content.
     */
    private function autoExtractContent(string $html): string
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument;
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);

        // Try common content selectors in order of priority
        $selectors = [
            '//article',
            '//main',
            '//*[contains(@class, "content")]',
            '//*[contains(@class, "article")]',
            '//*[contains(@id, "content")]',
            '//*[contains(@id, "article")]',
            '//body',
        ];

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                $extracted = $dom->saveHTML($nodes->item(0));
                libxml_clear_errors();

                return $extracted ?: $html;
            }
        }

        libxml_clear_errors();

        return $html;
    }

    /**
     * Extract page title.
     */
    private function extractTitle(string $html): ?string
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument;
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $titles = $dom->getElementsByTagName('title');
        if ($titles->length > 0) {
            $title = trim($titles->item(0)->nodeValue);
            libxml_clear_errors();

            return $title;
        }

        libxml_clear_errors();

        return null;
    }

    /**
     * Extract meta description.
     */
    private function extractMetaDescription(string $html): ?string
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument;
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);
        $metas = $xpath->query('//meta[@name="description"]');

        if ($metas && $metas->length > 0) {
            $description = $metas->item(0)->getAttribute('content');
            libxml_clear_errors();

            return trim($description);
        }

        libxml_clear_errors();

        return null;
    }

    /**
     * Convert simple CSS selector to XPath (simplified conversion).
     */
    private function cssToXPath(string $css): string
    {
        // Handle class selector
        if (str_starts_with($css, '.')) {
            $class = substr($css, 1);

            return "//*[contains(@class, '{$class}')]";
        }

        // Handle ID selector
        if (str_starts_with($css, '#')) {
            $id = substr($css, 1);

            return "//*[@id='{$id}']";
        }

        // Handle element.class selector
        if (str_contains($css, '.')) {
            [$element, $class] = explode('.', $css, 2);

            return "//{$element}[contains(@class, '{$class}')]";
        }

        // Handle element#id selector
        if (str_contains($css, '#')) {
            [$element, $id] = explode('#', $css, 2);

            return "//{$element}[@id='{$id}']";
        }

        // Default: element selector
        return "//{$css}";
    }

    /**
     * Get parser name.
     */
    protected function getName(): string
    {
        return 'single_page';
    }
}
