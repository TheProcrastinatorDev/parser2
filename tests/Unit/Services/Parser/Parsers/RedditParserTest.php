<?php

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\RedditParser;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\RateLimiter;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['parser.reddit' => [
        'enabled' => true,
        'rate_limit' => ['requests' => 30, 'window' => 60],
    ]]);
});

it('can parse subreddit JSON endpoint', function () {
    $jsonData = [
        'data' => [
            'children' => [
                [
                    'data' => [
                        'title' => 'Test Post',
                        'url' => 'https://reddit.com/r/test/post1',
                        'selftext' => 'Post content',
                        'author' => 'testuser',
                        'score' => 100,
                        'created_utc' => 1609459200,
                        'subreddit' => 'test',
                    ],
                ],
            ],
            'after' => 'next_page_token',
        ],
    ];

    Http::fake(['reddit.com/*' => Http::response(json_encode($jsonData), 200)]);

    $parser = new RedditParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://www.reddit.com/r/test.json',
        'type' => 'subreddit',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(1)
        ->and($result->items[0]['title'])->toBe('Test Post')
        ->and($result->items[0]['url'])->toBe('https://reddit.com/r/test/post1');
});

it('can determine post type', function () {
    $jsonData = [
        'data' => [
            'children' => [
                [
                    'data' => [
                        'title' => 'Image Post',
                        'url' => 'https://i.redd.it/image.jpg',
                        'post_hint' => 'image',
                    ],
                ],
                [
                    'data' => [
                        'title' => 'Video Post',
                        'url' => 'https://v.redd.it/video',
                        'post_hint' => 'hosted:video',
                    ],
                ],
                [
                    'data' => [
                        'title' => 'Text Post',
                        'selftext' => 'Text content',
                        'post_hint' => 'self',
                    ],
                ],
            ],
        ],
    ];

    Http::fake(['reddit.com/*' => Http::response(json_encode($jsonData), 200)]);

    $parser = new RedditParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://www.reddit.com/r/test.json',
        'type' => 'subreddit',
    ]);

    $result = $parser->parse($request);

    expect($result->items[0]['metadata']['post_type'])->toBe('image')
        ->and($result->items[1]['metadata']['post_type'])->toBe('video')
        ->and($result->items[2]['metadata']['post_type'])->toBe('text');
});

it('handles pagination via after parameter', function () {
    $jsonData = [
        'data' => [
            'children' => [],
            'after' => 'next_token',
        ],
    ];

    Http::fake(['reddit.com/*' => Http::response(json_encode($jsonData), 200)]);

    $parser = new RedditParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://www.reddit.com/r/test.json',
        'type' => 'subreddit',
        'options' => ['after' => 'prev_token'],
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue();
});

it('handles NSFW and spoiler flags', function () {
    $jsonData = [
        'data' => [
            'children' => [
                [
                    'data' => [
                        'title' => 'NSFW Post',
                        'over_18' => true,
                        'spoiler' => false,
                    ],
                ],
                [
                    'data' => [
                        'title' => 'Spoiler Post',
                        'over_18' => false,
                        'spoiler' => true,
                    ],
                ],
            ],
        ],
    ];

    Http::fake(['reddit.com/*' => Http::response(json_encode($jsonData), 200)]);

    $parser = new RedditParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://www.reddit.com/r/test.json',
        'type' => 'subreddit',
    ]);

    $result = $parser->parse($request);

    expect($result->items[0]['metadata']['nsfw'])->toBeTrue()
        ->and($result->items[1]['metadata']['spoiler'])->toBeTrue();
});
