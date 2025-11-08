<?php

namespace App\Services\Parser\Support;

use DOMDocument;
use DOMXPath;

class ContentExtractor
{
    public function extractImages(string $html, string $baseUrl): array
    {
        if (empty($html)) {
            return [];
        }

        $images = [];
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $imgNodes = $xpath->query('//img[@src]');
        foreach ($imgNodes as $img) {
            $src = $img->getAttribute('src');
            $absoluteUrl = $this->makeAbsoluteUrl($src, $baseUrl);
            if ($absoluteUrl) {
                $images[] = $absoluteUrl;
            }
        }

        return $images;
    }

    public function cleanHtml(string $html): string
    {
        if (empty($html)) {
            return '';
        }

        // Remove script tags
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        
        // Remove style tags
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);
        
        // Remove comments
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        
        // Clean up whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        $html = trim($html);

        return $html;
    }

    public function fixRelativeUrls(string $html, string $baseUrl): string
    {
        if (empty($html)) {
            return '';
        }

        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        // Fix href attributes
        $links = $xpath->query('//a[@href]');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $absoluteUrl = $this->makeAbsoluteUrl($href, $baseUrl);
            if ($absoluteUrl) {
                $link->setAttribute('href', $absoluteUrl);
            }
        }

        // Fix src attributes
        $images = $xpath->query('//img[@src]');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            $absoluteUrl = $this->makeAbsoluteUrl($src, $baseUrl);
            if ($absoluteUrl) {
                $img->setAttribute('src', $absoluteUrl);
            }
        }

        return $dom->saveHTML();
    }

    public function extractMetaTags(string $html): array
    {
        if (empty($html)) {
            return [];
        }

        $meta = [];
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $metaNodes = $xpath->query('//meta[@property or @name]');
        foreach ($metaNodes as $metaNode) {
            $property = $metaNode->getAttribute('property');
            $name = $metaNode->getAttribute('name');
            $content = $metaNode->getAttribute('content');

            $key = $property ?: $name;
            if ($key && $content) {
                $meta[$key] = $content;
            }
        }

        return $meta;
    }

    private function makeAbsoluteUrl(string $url, string $baseUrl): ?string
    {
        if (empty($url)) {
            return null;
        }

        // Already absolute
        if (preg_match('/^https?:\/\//', $url)) {
            return $url;
        }

        $baseParts = parse_url($baseUrl);
        if (!$baseParts) {
            return null;
        }

        $scheme = $baseParts['scheme'] ?? 'https';
        $host = $baseParts['host'] ?? '';
        $basePath = $baseParts['path'] ?? '/';

        // Remove filename from base path if present
        if (pathinfo($basePath, PATHINFO_EXTENSION)) {
            $basePath = dirname($basePath);
        }
        $basePath = rtrim($basePath, '/') . '/';

        // Handle relative URLs
        if (str_starts_with($url, '/')) {
            return $scheme . '://' . $host . $url;
        }

        if (str_starts_with($url, '../')) {
            $path = $basePath;
            while (str_starts_with($url, '../')) {
                $path = dirname($path);
                $url = substr($url, 3);
            }
            return $scheme . '://' . $host . $path . '/' . $url;
        }

        return $scheme . '://' . $host . $basePath . $url;
    }
}
