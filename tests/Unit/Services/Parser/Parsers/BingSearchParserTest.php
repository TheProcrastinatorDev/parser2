<?php

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\BingSearchParser;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\RateLimiter;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['parser.bing_search' => [
        'enabled' => true,
        'rate_limit' => ['requests' => 10, 'window' => 60],
    ]]);
});

it('can parse web search results', function () {
    $html = '<html><body>
        <ol id="b_results">
            <li class="b_algo">
                <h2><a href="https://example.com/page1">Result Title 1</a></h2>
                <p>Result description 1</p>
            </li>
            <li class="b_algo">
                <h2><a href="https://example.com/page2">Result Title 2</a></h2>
                <p>Result description 2</p>
            </li>
        </ol>
    </body></html>';
    
    Http::fake(['bing.com/*' => Http::response($html, 200)]);

    $parser = new BingSearchParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://www.bing.com/search?q=test',
        'type' => 'web',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(2)
        ->and($result->items[0]['title'])->toBe('Result Title 1')
        ->and($result->items[0]['url'])->toBe('https://example.com/page1');
});

it('can parse news search results', function () {
    $html = '<html><body>
        <div class="news">
            <div class="newsitem">
                <a href="https://example.com/news1">News Title 1</a>
                <div class="snippet">News description</div>
            </div>
        </div>
    </body></html>';
    
    Http::fake(['bing.com/*' => Http::response($html, 200)]);

    $parser = new BingSearchParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://www.bing.com/news/search?q=test',
        'type' => 'news',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->not->toBeEmpty();
});

it('can parse image search results', function () {
    $html = '<html><body>
        <div class="dg_b">
            <div class="iusc">
                <img src="https://example.com/image1.jpg" data-src="https://example.com/image1.jpg">
                <div class="inflnk">Image Title 1</div>
            </div>
        </div>
    </body></html>';
    
    Http::fake(['bing.com/*' => Http::response($html, 200)]);

    $parser = new BingSearchParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://www.bing.com/images/search?q=test',
        'type' => 'images',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->not->toBeEmpty()
        ->and($result->items[0]['images'])->not->toBeEmpty();
});

it('supports pagination via first parameter', function () {
    $html = '<html><body><ol id="b_results"></ol></body></html>';
    Http::fake(['bing.com/*' => Http::response($html, 200)]);

    $parser = new BingSearchParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://www.bing.com/search?q=test',
        'type' => 'web',
        'options' => ['first' => 10],
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue();
});

it('handles no results found gracefully', function () {
    $html = '<html><body><div class="b_no">No results found</div></body></html>';
    Http::fake(['bing.com/*' => Http::response($html, 200)]);

    $parser = new BingSearchParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://www.bing.com/search?q=nonexistent12345',
        'type' => 'web',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->toBeEmpty();
});
