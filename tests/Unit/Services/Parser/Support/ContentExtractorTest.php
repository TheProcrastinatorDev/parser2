<?php

declare(strict_types=1);

use App\Services\Parser\Support\ContentExtractor;

uses(Tests\TestCase::class);

describe('ContentExtractor', function () {
    beforeEach(function () {
        $this->extractor = new ContentExtractor;
    });

    it('extracts images from html content', function () {
        $html = '<html><body><img src="image1.jpg"><img src="image2.png"></body></html>';

        $images = $this->extractor->extractImages($html);

        expect($images)->toHaveCount(2)
            ->and($images[0])->toBe('image1.jpg')
            ->and($images[1])->toBe('image2.png');
    });

    it('extracts og:image meta tag', function () {
        $html = '<html><head><meta property="og:image" content="https://example.com/og-image.jpg"></head></html>';

        $images = $this->extractor->extractImages($html);

        expect($images)->toContain('https://example.com/og-image.jpg');
    });

    it('extracts twitter:image meta tag', function () {
        $html = '<html><head><meta name="twitter:image" content="https://example.com/twitter-image.jpg"></head></html>';

        $images = $this->extractor->extractImages($html);

        expect($images)->toContain('https://example.com/twitter-image.jpg');
    });

    it('extracts multiple image types from html', function () {
        $html = '
            <html>
                <head>
                    <meta property="og:image" content="https://example.com/og.jpg">
                    <meta name="twitter:image" content="https://example.com/twitter.jpg">
                </head>
                <body>
                    <img src="https://example.com/img1.jpg">
                    <img src="https://example.com/img2.png">
                </body>
            </html>
        ';

        $images = $this->extractor->extractImages($html);

        expect($images)->toHaveCount(4);
    });

    it('removes script tags from html', function () {
        $html = '<html><body><p>Content</p><script>alert("xss")</script></body></html>';

        $cleaned = $this->extractor->cleanHtml($html);

        expect($cleaned)->not->toContain('<script>')
            ->and($cleaned)->toContain('Content');
    });

    it('removes style tags from html', function () {
        $html = '<html><body><p>Content</p><style>body { color: red; }</style></body></html>';

        $cleaned = $this->extractor->cleanHtml($html);

        expect($cleaned)->not->toContain('<style>')
            ->and($cleaned)->toContain('Content');
    });

    it('removes iframe tags from html', function () {
        $html = '<html><body><p>Content</p><iframe src="https://ads.com"></iframe></body></html>';

        $cleaned = $this->extractor->cleanHtml($html);

        expect($cleaned)->not->toContain('<iframe>')
            ->and($cleaned)->toContain('Content');
    });

    it('removes noscript tags from html', function () {
        $html = '<html><body><p>Content</p><noscript>No JS</noscript></body></html>';

        $cleaned = $this->extractor->cleanHtml($html);

        expect($cleaned)->not->toContain('<noscript>')
            ->and($cleaned)->toContain('Content');
    });

    it('removes onclick attributes from html', function () {
        $html = '<button onclick="malicious()">Click</button>';

        $cleaned = $this->extractor->cleanHtml($html);

        expect($cleaned)->not->toContain('onclick')
            ->and($cleaned)->toContain('Click');
    });

    it('removes onload attributes from html', function () {
        $html = '<img src="test.jpg" onload="malicious()">';

        $cleaned = $this->extractor->cleanHtml($html);

        expect($cleaned)->not->toContain('onload');
    });

    it('removes onerror attributes from html', function () {
        $html = '<img src="test.jpg" onerror="malicious()">';

        $cleaned = $this->extractor->cleanHtml($html);

        expect($cleaned)->not->toContain('onerror');
    });

    it('handles utf-8 encoding correctly', function () {
        $content = 'Hello 世界 🌍';

        $normalized = $this->extractor->normalizeEncoding($content);

        expect($normalized)->toBe('Hello 世界 🌍')
            ->and(mb_check_encoding($normalized, 'UTF-8'))->toBeTrue();
    });

    it('converts iso-8859-1 to utf-8', function () {
        $content = mb_convert_encoding('Café', 'ISO-8859-1', 'UTF-8');

        $normalized = $this->extractor->normalizeEncoding($content);

        expect(mb_check_encoding($normalized, 'UTF-8'))->toBeTrue()
            ->and($normalized)->toContain('Café');
    });

    it('fixes relative urls to absolute', function () {
        $html = '<img src="/images/photo.jpg">';
        $baseUrl = 'https://example.com';

        $fixed = $this->extractor->fixRelativeUrls($html, $baseUrl);

        expect($fixed)->toContain('https://example.com/images/photo.jpg');
    });

    it('fixes protocol-relative urls', function () {
        $html = '<img src="//cdn.example.com/image.jpg">';
        $baseUrl = 'https://example.com';

        $fixed = $this->extractor->fixRelativeUrls($html, $baseUrl);

        expect($fixed)->toContain('https://cdn.example.com/image.jpg');
    });

    it('preserves absolute urls', function () {
        $html = '<img src="https://other.com/image.jpg">';
        $baseUrl = 'https://example.com';

        $fixed = $this->extractor->fixRelativeUrls($html, $baseUrl);

        expect($fixed)->toBe('<img src="https://other.com/image.jpg">');
    });

    it('handles multiple relative urls in same html', function () {
        $html = '<img src="/img1.jpg"><a href="/page">Link</a>';
        $baseUrl = 'https://example.com';

        $fixed = $this->extractor->fixRelativeUrls($html, $baseUrl);

        expect($fixed)->toContain('https://example.com/img1.jpg')
            ->and($fixed)->toContain('https://example.com/page');
    });

    it('extracts text content from html', function () {
        $html = '<html><body><h1>Title</h1><p>Paragraph text</p></body></html>';

        $text = $this->extractor->extractText($html);

        expect($text)->toContain('Title')
            ->and($text)->toContain('Paragraph text')
            ->and($text)->not->toContain('<h1>')
            ->and($text)->not->toContain('<p>');
    });

    it('handles empty html gracefully', function () {
        $html = '';

        $images = $this->extractor->extractImages($html);
        $cleaned = $this->extractor->cleanHtml($html);

        expect($images)->toBe([])
            ->and($cleaned)->toBe('');
    });
});
