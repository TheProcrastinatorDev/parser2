<?php

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\MultiUrlParser;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\RateLimiter;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['parser.multi_url' => [
        'enabled' => true,
        'rate_limit' => ['requests' => 50, 'window' => 60],
    ]]);
});

it('can extract URLs via XPath selectors', function () {
    $html = '<html><body>
        <div class="links">
            <a href="https://example.com/page1">Link 1</a>
            <a href="https://example.com/page2">Link 2</a>
        </div>
    </body></html>';
    
    Http::fake([
        'example.com/list' => Http::response($html, 200),
        'example.com/page1' => Http::response('<html><body><p>Content 1</p></body></html>', 200),
        'example.com/page2' => Http::response('<html><body><p>Content 2</p></body></html>', 200),
    ]);

    $parser = new MultiUrlParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/list',
        'type' => 'xpath',
        'options' => ['xpath' => '//div[@class="links"]//a/@href'],
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(2);
});

it('can extract URLs via CSS selectors', function () {
    $html = '<html><body>
        <div class="links">
            <a href="/page1">Link 1</a>
            <a href="/page2">Link 2</a>
        </div>
    </body></html>';
    
    Http::fake([
        'example.com/list' => Http::response($html, 200),
        'example.com/page1' => Http::response('<html><body><p>Content 1</p></body></html>', 200),
        'example.com/page2' => Http::response('<html><body><p>Content 2</p></body></html>', 200),
    ]);

    $parser = new MultiUrlParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/list',
        'type' => 'css',
        'options' => ['selector' => '.links a'],
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(2);
});

it('can extract URLs via regex patterns', function () {
    $html = '<html><body>
        <p>Visit https://example.com/page1 and https://example.com/page2</p>
    </body></html>';
    
    Http::fake([
        'example.com/list' => Http::response($html, 200),
        'example.com/page1' => Http::response('<html><body><p>Content 1</p></body></html>', 200),
        'example.com/page2' => Http::response('<html><body><p>Content 2</p></body></html>', 200),
    ]);

    $parser = new MultiUrlParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/list',
        'type' => 'regex',
        'options' => ['pattern' => '/https?:\/\/[^\s]+/'],
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(2);
});

it('can extract URLs from fixed list', function () {
    Http::fake([
        'example.com/page1' => Http::response('<html><body><p>Content 1</p></body></html>', 200),
        'example.com/page2' => Http::response('<html><body><p>Content 2</p></body></html>', 200),
    ]);

    $parser = new MultiUrlParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/list',
        'type' => 'list',
        'options' => [
            'urls' => [
                'https://example.com/page1',
                'https://example.com/page2',
            ],
        ],
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(2);
});

it('deduplicates extracted links', function () {
    $html = '<html><body>
        <a href="https://example.com/page1">Link 1</a>
        <a href="https://example.com/page1">Link 1 Duplicate</a>
    </body></html>';
    
    Http::fake([
        'example.com/list' => Http::response($html, 200),
        'example.com/page1' => Http::response('<html><body><p>Content</p></body></html>', 200),
    ]);

    $parser = new MultiUrlParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/list',
        'type' => 'css',
        'options' => ['selector' => 'a'],
    ]);

    $result = $parser->parse($request);

    expect($result->items)->toHaveCount(1); // Should deduplicate
});

it('fixes relative URLs to absolute', function () {
    $html = '<html><body><a href="/page">Link</a></body></html>';
    
    Http::fake([
        'example.com/list' => Http::response($html, 200),
        'example.com/page' => Http::response('<html><body><p>Content</p></body></html>', 200),
    ]);

    $parser = new MultiUrlParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/list',
        'type' => 'css',
        'options' => ['selector' => 'a'],
    ]);

    $result = $parser->parse($request);

    expect($result->items[0]['url'])->toContain('https://example.com');
});

it('handles individual fetch failures gracefully', function () {
    Http::fake([
        'example.com/page1' => Http::response('<html><body><p>Content 1</p></body></html>', 200),
        'example.com/page2' => Http::response('Error', 500),
    ]);

    $parser = new MultiUrlParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/list',
        'type' => 'list',
        'options' => [
            'urls' => [
                'https://example.com/page1',
                'https://example.com/page2',
            ],
        ],
    ]);

    $result = $parser->parse($request);

    // Should return successful items and skip failed ones
    expect($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(1);
});
