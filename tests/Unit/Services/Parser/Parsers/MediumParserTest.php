<?php

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\MediumParser;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\RateLimiter;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['parser.medium' => [
        'enabled' => true,
        'rate_limit' => ['requests' => 40, 'window' => 60],
    ]]);
});

it('can parse user RSS feed', function () {
    $rssXml = '<?xml version="1.0"?>
    <rss version="2.0">
        <channel>
            <title>@username - Medium</title>
            <item>
                <title>Article Title</title>
                <link>https://medium.com/@username/article</link>
                <description>Article description</description>
                <pubDate>Mon, 01 Jan 2025 00:00:00 GMT</pubDate>
            </item>
        </channel>
    </rss>';
    
    Http::fake(['medium.com/*' => Http::response($rssXml, 200)]);

    $parser = new MediumParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://medium.com/@username/feed',
        'type' => 'user',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(1)
        ->and($result->items[0]['title'])->toBe('Article Title');
});

it('can parse publication feed', function () {
    $rssXml = '<?xml version="1.0"?>
    <rss version="2.0">
        <channel>
            <title>Publication Name - Medium</title>
            <item>
                <title>Publication Article</title>
                <link>https://medium.com/publication/article</link>
            </item>
        </channel>
    </rss>';
    
    Http::fake(['medium.com/*' => Http::response($rssXml, 200)]);

    $parser = new MediumParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://medium.com/feed/publication',
        'type' => 'publication',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue();
});

it('can extract read time from content', function () {
    $rssXml = '<?xml version="1.0"?>
    <rss version="2.0">
        <channel>
            <item>
                <title>Test</title>
                <link>https://medium.com/@user/test</link>
                <content:encoded><![CDATA[<p>Article with read time: 5 min read</p>]]></content:encoded>
            </item>
        </channel>
    </rss>';
    
    Http::fake(['medium.com/*' => Http::response($rssXml, 200)]);

    $parser = new MediumParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://medium.com/@user/feed',
        'type' => 'user',
    ]);

    $result = $parser->parse($request);

    expect($result->items[0]['metadata']['read_time'])->not->toBeNull();
});

it('can extract author information', function () {
    $rssXml = '<?xml version="1.0"?>
    <rss version="2.0">
        <channel>
            <item>
                <title>Test</title>
                <link>https://medium.com/@author/article</link>
                <dc:creator>Author Name</dc:creator>
            </item>
        </channel>
    </rss>';
    
    Http::fake(['medium.com/*' => Http::response($rssXml, 200)]);

    $parser = new MediumParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://medium.com/@author/feed',
        'type' => 'user',
    ]);

    $result = $parser->parse($request);

    expect($result->items[0]['author'])->not->toBeEmpty();
});

it('handles both RSS 2.0 and Atom formats', function () {
    $atomXml = '<?xml version="1.0"?>
    <feed xmlns="http://www.w3.org/2005/Atom">
        <title>Test Feed</title>
        <entry>
            <title>Atom Article</title>
            <link href="https://medium.com/@user/article"/>
        </entry>
    </feed>';
    
    Http::fake(['medium.com/*' => Http::response($atomXml, 200, ['Content-Type' => 'application/atom+xml'])]);

    $parser = new MediumParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://medium.com/@user/feed',
        'type' => 'user',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue();
});
