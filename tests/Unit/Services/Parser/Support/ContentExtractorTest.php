<?php

use App\Services\Parser\Support\ContentExtractor;

it('can extract images from HTML content', function () {
    $html = '<html><body><img src="https://example.com/image1.jpg"><img src="/image2.png"></body></html>';
    $extractor = new ContentExtractor();
    $baseUrl = 'https://example.com';

    $images = $extractor->extractImages($html, $baseUrl);

    expect($images)->toHaveCount(2)
        ->and($images[0])->toBe('https://example.com/image1.jpg')
        ->and($images[1])->toBe('https://example.com/image2.png');
});

it('can clean HTML by removing scripts and styles', function () {
    $html = '<html><head><script>alert("test");</script><style>body { color: red; }</style></head><body><p>Content</p></body></html>';
    $extractor = new ContentExtractor();

    $cleaned = $extractor->cleanHtml($html);

    expect($cleaned)->not->toContain('<script>')
        ->and($cleaned)->not->toContain('<style>')
        ->and($cleaned)->toContain('<p>Content</p>');
});

it('can fix relative URLs to absolute', function () {
    $html = '<a href="/page">Link</a><img src="../image.jpg">';
    $extractor = new ContentExtractor();
    $baseUrl = 'https://example.com/article';

    $fixed = $extractor->fixRelativeUrls($html, $baseUrl);

    expect($fixed)->toContain('href="https://example.com/page"')
        ->and($fixed)->toContain('src="https://example.com/image.jpg"');
});

it('can extract meta tags', function () {
    $html = '<html><head><meta property="og:image" content="https://example.com/og.jpg"><meta name="twitter:image" content="https://example.com/twitter.jpg"></head></html>';
    $extractor = new ContentExtractor();

    $meta = $extractor->extractMetaTags($html);

    expect($meta)->toHaveKey('og:image')
        ->and($meta['og:image'])->toBe('https://example.com/og.jpg')
        ->and($meta)->toHaveKey('twitter:image')
        ->and($meta['twitter:image'])->toBe('https://example.com/twitter.jpg');
});

it('handles UTF-8 encoding correctly', function () {
    $html = '<html><body><p>Тест 测试 🚀</p></body></html>';
    $extractor = new ContentExtractor();

    $cleaned = $extractor->cleanHtml($html);

    expect($cleaned)->toContain('Тест')
        ->and($cleaned)->toContain('测试')
        ->and($cleaned)->toContain('🚀');
});

it('handles empty HTML gracefully', function () {
    $extractor = new ContentExtractor();

    expect($extractor->cleanHtml(''))->toBe('')
        ->and($extractor->extractImages('', 'https://example.com'))->toBe([])
        ->and($extractor->extractMetaTags(''))->toBe([]);
});
