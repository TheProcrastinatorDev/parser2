<?php

declare(strict_types=1);

namespace App\Services\Parser\Parsers;

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\AbstractParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;
use DOMDocument;
use DOMXPath;

class MediumParser extends AbstractParser
{
    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly ContentExtractor $contentExtractor,
    ) {}

    /**
     * Parse Medium article.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function doParse(ParseRequestDTO $request): array
    {
        // Fetch article HTML
        $html = $this->httpClient->get($request->source);

        // Parse article from HTML
        $article = $this->extractArticle($html, $request->source);

        return [$article];
    }

    /**
     * Extract article data from Medium HTML.
     *
     * @return array<string, mixed>
     */
    private function extractArticle(string $html, string $url): array
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);

        // Extract article content
        $articleNode = $xpath->query('//article')->item(0);
        $articleHtml = $articleNode ? $dom->saveHTML($articleNode) : $html;

        $cleanedHtml = $this->contentExtractor->cleanHtml($articleHtml);

        $article = [
            'url' => $url,
            'title' => $this->extractTitle($xpath),
            'author' => $this->extractAuthor($xpath),
            'description' => $this->extractDescription($xpath),
            'content' => $this->contentExtractor->extractText($cleanedHtml),
            'html' => $cleanedHtml,
            'published_at' => $this->extractPublishedTime($xpath),
            'tags' => $this->extractTags($xpath),
            'reading_time' => $this->extractReadingTime($xpath),
            'images' => $this->contentExtractor->extractImages($articleHtml),
            'is_paywalled' => $this->detectPaywall($html),
            'claps' => $this->extractClaps($xpath),
            'canonical_url' => $this->extractCanonicalUrl($xpath),
            'publication' => $this->extractPublication($xpath),
        ];

        libxml_clear_errors();

        return $article;
    }

    /**
     * Extract article title.
     */
    private function extractTitle(DOMXPath $xpath): ?string
    {
        // Try <title> tag first
        $titleNodes = $xpath->query('//title');
        if ($titleNodes && $titleNodes->length > 0) {
            return trim($titleNodes->item(0)->nodeValue);
        }

        // Try h1
        $h1Nodes = $xpath->query('//article//h1');
        if ($h1Nodes && $h1Nodes->length > 0) {
            return trim($h1Nodes->item(0)->nodeValue);
        }

        // Try og:title
        $ogTitleNodes = $xpath->query('//meta[@property="og:title"]');
        if ($ogTitleNodes && $ogTitleNodes->length > 0) {
            return $ogTitleNodes->item(0)->getAttribute('content');
        }

        return null;
    }

    /**
     * Extract article author.
     */
    private function extractAuthor(DOMXPath $xpath): ?string
    {
        // Try meta author
        $authorNodes = $xpath->query('//meta[@name="author"]');
        if ($authorNodes && $authorNodes->length > 0) {
            return $authorNodes->item(0)->getAttribute('content');
        }

        // Try article:author
        $articleAuthorNodes = $xpath->query('//meta[@property="article:author"]');
        if ($articleAuthorNodes && $articleAuthorNodes->length > 0) {
            return $articleAuthorNodes->item(0)->getAttribute('content');
        }

        return null;
    }

    /**
     * Extract article description.
     */
    private function extractDescription(DOMXPath $xpath): ?string
    {
        // Try og:description
        $ogDescNodes = $xpath->query('//meta[@property="og:description"]');
        if ($ogDescNodes && $ogDescNodes->length > 0) {
            return $ogDescNodes->item(0)->getAttribute('content');
        }

        // Try meta description
        $metaDescNodes = $xpath->query('//meta[@name="description"]');
        if ($metaDescNodes && $metaDescNodes->length > 0) {
            return $metaDescNodes->item(0)->getAttribute('content');
        }

        return null;
    }

    /**
     * Extract published time.
     */
    private function extractPublishedTime(DOMXPath $xpath): ?string
    {
        $publishedNodes = $xpath->query('//meta[@property="article:published_time"]');

        if ($publishedNodes && $publishedNodes->length > 0) {
            return $publishedNodes->item(0)->getAttribute('content');
        }

        return null;
    }

    /**
     * Extract article tags.
     *
     * @return array<int, string>
     */
    private function extractTags(DOMXPath $xpath): array
    {
        $tagNodes = $xpath->query('//meta[@property="article:tag"]');

        if (! $tagNodes || $tagNodes->length === 0) {
            return [];
        }

        $tags = [];
        foreach ($tagNodes as $tagNode) {
            $tag = $tagNode->getAttribute('content');
            if (! empty($tag)) {
                $tags[] = $tag;
            }
        }

        return $tags;
    }

    /**
     * Extract reading time.
     */
    private function extractReadingTime(DOMXPath $xpath): ?string
    {
        // Medium uses twitter:data1 for reading time
        $readingTimeNodes = $xpath->query('//meta[@name="twitter:data1"]');

        if ($readingTimeNodes && $readingTimeNodes->length > 0) {
            return $readingTimeNodes->item(0)->getAttribute('content');
        }

        return null;
    }

    /**
     * Detect if article is behind paywall.
     */
    private function detectPaywall(string $html): bool
    {
        // Check for common paywall indicators
        return str_contains($html, 'meteredContent')
            || str_contains($html, 'paywall')
            || str_contains($html, 'member-only');
    }

    /**
     * Extract clap count.
     */
    private function extractClaps(DOMXPath $xpath): ?string
    {
        // Try to find clap/recommend button
        $clapNodes = $xpath->query('//button[@data-action="show-recommends-list"]//span');

        if ($clapNodes && $clapNodes->length > 0) {
            return trim($clapNodes->item(0)->nodeValue);
        }

        return null;
    }

    /**
     * Extract canonical URL.
     */
    private function extractCanonicalUrl(DOMXPath $xpath): ?string
    {
        $canonicalNodes = $xpath->query('//link[@rel="canonical"]');

        if ($canonicalNodes && $canonicalNodes->length > 0) {
            return $canonicalNodes->item(0)->getAttribute('href');
        }

        return null;
    }

    /**
     * Extract publication name.
     */
    private function extractPublication(DOMXPath $xpath): ?string
    {
        // Try og:site_name for publication
        $siteNameNodes = $xpath->query('//meta[@property="og:site_name"]');

        if ($siteNameNodes && $siteNameNodes->length > 0) {
            return $siteNameNodes->item(0)->getAttribute('content');
        }

        return null;
    }

    /**
     * Get parser name.
     */
    protected function getName(): string
    {
        return 'medium';
    }
}
