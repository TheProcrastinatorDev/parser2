<?php

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\FeedsParser;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\RateLimiter;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['parser.feeds' => [
        'enabled' => true,
        'rate_limit' => ['requests' => 60, 'window' => 60],
    ]]);
});

it('can detect RSS feed type', function () {
    $rssXml = '<?xml version="1.0"?><rss version="2.0"><channel><title>Test</title></channel></rss>';
    Http::fake(['example.com/*' => Http::response($rssXml, 200, ['Content-Type' => 'application/rss+xml'])]);

    $parser = new FeedsParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/feed.xml',
        'type' => 'rss',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue();
});

it('can detect Atom feed type', function () {
    $atomXml = '<?xml version="1.0"?><feed xmlns="http://www.w3.org/2005/Atom"><title>Test</title></feed>';
    Http::fake(['example.com/*' => Http::response($atomXml, 200, ['Content-Type' => 'application/atom+xml'])]);

    $parser = new FeedsParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/feed.atom',
        'type' => 'atom',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue();
});

it('can detect JSON feed type', function () {
    $jsonFeed = json_encode([
        'version' => 'https://jsonfeed.org/version/1',
        'title' => 'Test Feed',
        'items' => [],
    ]);
    Http::fake(['example.com/*' => Http::response($jsonFeed, 200, ['Content-Type' => 'application/json'])]);

    $parser = new FeedsParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/feed.json',
        'type' => 'json',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue();
});

it('can parse RSS 2.0 feed with items', function () {
    $rssXml = '<?xml version="1.0"?>
    <rss version="2.0">
        <channel>
            <title>Test Feed</title>
            <item>
                <title>Article 1</title>
                <link>https://example.com/article1</link>
                <description>Description 1</description>
                <pubDate>Mon, 01 Jan 2025 00:00:00 GMT</pubDate>
            </item>
            <item>
                <title>Article 2</title>
                <link>https://example.com/article2</link>
                <description>Description 2</description>
            </item>
        </channel>
    </rss>';
    
    Http::fake(['example.com/*' => Http::response($rssXml, 200)]);

    $parser = new FeedsParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/feed.xml',
        'type' => 'rss',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(2)
        ->and($result->items[0]['title'])->toBe('Article 1')
        ->and($result->items[0]['url'])->toBe('https://example.com/article1');
});

it('respects max_items limit', function () {
    $rssXml = '<?xml version="1.0"?>
    <rss version="2.0">
        <channel>
            <item><title>Item 1</title><link>https://example.com/1</link></item>
            <item><title>Item 2</title><link>https://example.com/2</link></item>
            <item><title>Item 3</title><link>https://example.com/3</link></item>
        </channel>
    </rss>';
    
    Http::fake(['example.com/*' => Http::response($rssXml, 200)]);

    $parser = new FeedsParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/feed.xml',
        'type' => 'rss',
        'options' => ['max_items' => 2],
    ]);

    $result = $parser->parse($request);

    expect($result->items)->toHaveCount(2);
});

it('handles invalid feed XML gracefully', function () {
    Http::fake(['example.com/*' => Http::response('Invalid XML', 200)]);

    $parser = new FeedsParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/feed.xml',
        'type' => 'rss',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeFalse()
        ->and($result->error)->not->toBeNull();
});
