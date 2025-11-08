<?php

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\SinglePageParser;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\RateLimiter;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['parser.single_page' => [
        'enabled' => true,
        'rate_limit' => ['requests' => 100, 'window' => 60],
    ]]);
});

it('can extract content using CSS selector', function () {
    $html = '<html><body><div class="content"><p>Article content</p></div></body></html>';
    Http::fake(['example.com/*' => Http::response($html, 200)]);

    $parser = new SinglePageParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/article',
        'type' => 'css',
        'options' => ['selector' => '.content'],
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(1)
        ->and($result->items[0]['content'])->toContain('Article content');
});

it('can extract content using XPath', function () {
    $html = '<html><body><article><h1>Title</h1><p>Content</p></article></body></html>';
    Http::fake(['example.com/*' => Http::response($html, 200)]);

    $parser = new SinglePageParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/article',
        'type' => 'xpath',
        'options' => ['xpath' => '//article'],
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(1);
});

it('can extract content using regex', function () {
    $html = '<html><body><div id="content">Article text here</div></body></html>';
    Http::fake(['example.com/*' => Http::response($html, 200)]);

    $parser = new SinglePageParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/article',
        'type' => 'regex',
        'options' => ['pattern' => '/<div id="content">(.*?)<\/div>/s'],
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(1)
        ->and($result->items[0]['content'])->toContain('Article text here');
});

it('can auto-detect content intelligently', function () {
    $html = '<html><body><article><h1>Title</h1><p>Main content here</p></article><aside>Sidebar</aside></body></html>';
    Http::fake(['example.com/*' => Http::response($html, 200)]);

    $parser = new SinglePageParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/article',
        'type' => 'auto',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(1);
});

it('fixes relative URLs to absolute', function () {
    $html = '<html><body><a href="/page">Link</a><img src="../image.jpg"></body></html>';
    Http::fake(['example.com/*' => Http::response($html, 200)]);

    $parser = new SinglePageParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/article',
        'type' => 'auto',
    ]);

    $result = $parser->parse($request);

    expect($result->items[0]['content'])->toContain('https://example.com')
        ->and($result->items[0]['images'])->not->toBeEmpty();
});

it('extracts images from content', function () {
    $html = '<html><body><img src="https://example.com/image1.jpg"><img src="/image2.png"></body></html>';
    Http::fake(['example.com/*' => Http::response($html, 200)]);

    $parser = new SinglePageParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/article',
        'type' => 'auto',
    ]);

    $result = $parser->parse($request);

    expect($result->items[0]['images'])->toHaveCount(2);
});
