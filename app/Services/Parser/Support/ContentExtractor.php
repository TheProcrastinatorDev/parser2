<?php

declare(strict_types=1);

namespace App\Services\Parser\Support;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Config;

class ContentExtractor
{
    /**
     * Extract images from HTML content.
     *
     * @return array<int, string>
     */
    public function extractImages(string $html): array
    {
        if (empty($html)) {
            return [];
        }

        $images = [];

        // Suppress warnings from malformed HTML
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);

        // Extract from meta tags
        $metaSelectors = Config::get('parser.extraction.image_selectors', [
            'meta[property="og:image"]',
            'meta[name="twitter:image"]',
            'img[src]',
        ]);

        // Extract og:image
        $ogImages = $xpath->query('//meta[@property="og:image"]');
        foreach ($ogImages as $meta) {
            if ($content = $meta->getAttribute('content')) {
                $images[] = $content;
            }
        }

        // Extract twitter:image
        $twitterImages = $xpath->query('//meta[@name="twitter:image"]');
        foreach ($twitterImages as $meta) {
            if ($content = $meta->getAttribute('content')) {
                $images[] = $content;
            }
        }

        // Extract img src
        $imgTags = $xpath->query('//img[@src]');
        foreach ($imgTags as $img) {
            if ($src = $img->getAttribute('src')) {
                $images[] = $src;
            }
        }

        libxml_clear_errors();

        return $images;
    }

    /**
     * Clean HTML by removing unwanted tags and attributes.
     */
    public function cleanHtml(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Suppress warnings from malformed HTML
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Remove unwanted tags
        $removeTags = Config::get('parser.extraction.remove_tags', [
            'script', 'style', 'iframe', 'noscript',
        ]);

        foreach ($removeTags as $tagName) {
            $tags = $dom->getElementsByTagName($tagName);
            $nodesToRemove = [];

            foreach ($tags as $tag) {
                $nodesToRemove[] = $tag;
            }

            foreach ($nodesToRemove as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        // Remove unwanted attributes
        $removeAttributes = Config::get('parser.extraction.remove_attributes', [
            'onclick', 'onload', 'onerror',
        ]);

        $xpath = new DOMXPath($dom);
        $allElements = $xpath->query('//*');

        foreach ($allElements as $element) {
            foreach ($removeAttributes as $attr) {
                if ($element->hasAttribute($attr)) {
                    $element->removeAttribute($attr);
                }
            }
        }

        $cleaned = $dom->saveHTML();

        libxml_clear_errors();

        return $cleaned ?: '';
    }

    /**
     * Normalize encoding to UTF-8.
     */
    public function normalizeEncoding(string $content): string
    {
        if (empty($content)) {
            return '';
        }

        // Check if already valid UTF-8
        if (mb_check_encoding($content, 'UTF-8')) {
            return $content;
        }

        // Try to detect encoding and convert
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        return $content;
    }

    /**
     * Fix relative URLs to absolute URLs.
     */
    public function fixRelativeUrls(string $html, string $baseUrl): string
    {
        if (empty($html)) {
            return '';
        }

        // Fix protocol-relative URLs (//example.com)
        $html = preg_replace_callback(
            '/(?:src|href)="\/\/([^"]+)"/i',
            function ($matches) use ($baseUrl) {
                $protocol = parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https';

                return str_replace('//'.$matches[1], $protocol.'://'.$matches[1], $matches[0]);
            },
            $html
        );

        // Fix root-relative URLs (/path)
        $html = preg_replace_callback(
            '/(?:src|href)="\/([^\/][^"]+)"/i',
            function ($matches) use ($baseUrl) {
                return str_replace('/'.$matches[1], rtrim($baseUrl, '/').'/'.$matches[1], $matches[0]);
            },
            $html
        );

        return $html;
    }

    /**
     * Extract text content from HTML.
     */
    public function extractText(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // First clean the HTML
        $html = $this->cleanHtml($html);

        // Strip all HTML tags
        $text = strip_tags($html);

        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }
}
