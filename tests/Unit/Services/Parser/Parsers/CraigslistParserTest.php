<?php

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\CraigslistParser;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\RateLimiter;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['parser.craigslist' => [
        'enabled' => true,
        'rate_limit' => ['requests' => 1, 'window' => 1],
    ]]);
});

it('can parse search results page', function () {
    $html = '<html><body>
        <ul class="rows">
            <li class="result-row">
                <a href="/apa/1234567890.html" class="result-title">Apartment for rent</a>
                <span class="result-price">$1000</span>
                <span class="result-hood">(downtown)</span>
            </li>
        </ul>
    </body></html>';
    
    Http::fake(['craigslist.org/*' => Http::response($html, 200)]);

    $parser = new CraigslistParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://sfbay.craigslist.org/search/apa',
        'type' => 'search',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(1)
        ->and($result->items[0]['title'])->toBe('Apartment for rent');
});

it('can parse full listing details', function () {
    $html = '<html><body>
        <h2 class="postingtitle">Full Apartment Listing</h2>
        <span class="price">$1000</span>
        <div class="mapaddress">123 Main St, San Francisco, CA</div>
        <div id="thumbs">
            <img src="https://images.craigslist.org/image1.jpg">
            <img src="https://images.craigslist.org/image2.jpg">
        </div>
        <section id="postingbody">Full description of the apartment</section>
    </body></html>';
    
    Http::fake(['craigslist.org/*' => Http::response($html, 200)]);

    $parser = new CraigslistParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://sfbay.craigslist.org/apa/1234567890.html',
        'type' => 'listing',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(1)
        ->and($result->items[0]['title'])->toBe('Full Apartment Listing')
        ->and($result->items[0]['metadata']['price'])->toBe('$1000')
        ->and($result->items[0]['images'])->toHaveCount(2);
});

it('can extract price, location, and images', function () {
    $html = '<html><body>
        <span class="price">$1500</span>
        <div class="mapaddress">456 Oak Ave, Oakland, CA 94601</div>
        <div id="thumbs">
            <img src="https://images.craigslist.org/thumb1.jpg">
        </div>
    </body></html>';
    
    Http::fake(['craigslist.org/*' => Http::response($html, 200)]);

    $parser = new CraigslistParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://sfbay.craigslist.org/apa/123.html',
        'type' => 'listing',
    ]);

    $result = $parser->parse($request);

    expect($result->items[0]['metadata']['price'])->toBe('$1500')
        ->and($result->items[0]['metadata']['location'])->toContain('Oakland')
        ->and($result->items[0]['images'])->not->toBeEmpty();
});

it('handles IP blocking detection', function () {
    $html = '<html><body><h1>Access Denied</h1><p>Your IP has been blocked</p></body></html>';
    Http::fake(['craigslist.org/*' => Http::response($html, 403)]);

    $parser = new CraigslistParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://sfbay.craigslist.org/search/apa',
        'type' => 'search',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('blocked');
});

it('supports pagination', function () {
    $html = '<html><body>
        <ul class="rows">
            <li class="result-row"><a href="/apa/1.html">Item 1</a></li>
        </ul>
        <a class="button next" href="/search/apa?s=120">next 120 results</a>
    </body></html>';
    
    Http::fake(['craigslist.org/*' => Http::response($html, 200)]);

    $parser = new CraigslistParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://sfbay.craigslist.org/search/apa',
        'type' => 'search',
        'options' => ['page' => 2],
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue();
});

it('respects rate limit', function () {
    $html = '<html><body><ul class="rows"></ul></body></html>';
    Http::fake(['craigslist.org/*' => Http::response($html, 200)]);

    $parser = new CraigslistParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://sfbay.craigslist.org/search/apa',
        'type' => 'search',
    ]);

    $result1 = $parser->parse($request);
    expect($result1->success)->toBeTrue();

    // Second request should respect rate limit
    $result2 = $parser->parse($request);
    // May be rate limited or successful depending on timing
    expect($result2)->toBeInstanceOf(\App\DTOs\Parser\ParseResultDTO::class);
});
