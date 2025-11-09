<?php

declare(strict_types=1);

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\RedditParser;
use App\Services\Parser\Support\HttpClient;

uses(Tests\TestCase::class);

describe('RedditParser', function () {
    beforeEach(function () {
        $this->httpClient = Mockery::mock(HttpClient::class);
        $this->parser = new RedditParser($this->httpClient);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('parses subreddit json endpoint', function () {
        $subredditJson = json_encode([
            'data' => [
                'children' => [
                    [
                        'data' => [
                            'title' => 'Test Post 1',
                            'url' => 'https://reddit.com/r/test/1',
                            'permalink' => '/r/test/comments/1/test_post',
                            'selftext' => 'Post content',
                            'score' => 100,
                            'upvote_ratio' => 0.95,
                            'gilded' => 2,
                            'created_utc' => 1704067200,
                            'is_self' => true,
                        ],
                    ],
                    [
                        'data' => [
                            'title' => 'Test Post 2',
                            'url' => 'https://example.com/image.jpg',
                            'permalink' => '/r/test/comments/2/test_post_2',
                            'score' => 50,
                            'created_utc' => 1704070800,
                            'is_self' => false,
                        ],
                    ],
                ],
            ],
        ]);

        $this->httpClient->shouldReceive('get')
            ->with('https://reddit.com/r/test.json')
            ->andReturn($subredditJson);

        $request = new ParseRequestDTO(
            source: 'https://reddit.com/r/test.json',
            type: 'subreddit'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(2)
            ->and($result->items[0]['title'])->toBe('Test Post 1')
            ->and($result->items[0]['url'])->toBe('https://reddit.com/r/test/1')
            ->and($result->items[0]['score'])->toBe(100)
            ->and($result->items[0]['upvote_ratio'])->toBe(0.95)
            ->and($result->items[0]['gilded'])->toBe(2)
            ->and($result->items[0]['type'])->toBe('text');
    });

    it('determines post type as text for self posts', function () {
        $postJson = json_encode([
            'data' => [
                'children' => [
                    [
                        'data' => [
                            'title' => 'Self Post',
                            'url' => 'https://reddit.com/r/test/1',
                            'permalink' => '/r/test/1',
                            'selftext' => 'Content here',
                            'is_self' => true,
                            'created_utc' => 1704067200,
                        ],
                    ],
                ],
            ],
        ]);

        $this->httpClient->shouldReceive('get')
            ->andReturn($postJson);

        $request = new ParseRequestDTO(
            source: 'https://reddit.com/r/test.json',
            type: 'subreddit'
        );

        $result = $this->parser->parse($request);

        expect($result->items[0]['type'])->toBe('text');
    });

    it('determines post type as image for image urls', function () {
        $postJson = json_encode([
            'data' => [
                'children' => [
                    [
                        'data' => [
                            'title' => 'Image Post',
                            'url' => 'https://i.redd.it/image.jpg',
                            'permalink' => '/r/test/1',
                            'is_self' => false,
                            'created_utc' => 1704067200,
                        ],
                    ],
                ],
            ],
        ]);

        $this->httpClient->shouldReceive('get')
            ->andReturn($postJson);

        $request = new ParseRequestDTO(
            source: 'https://reddit.com/r/test.json',
            type: 'subreddit'
        );

        $result = $this->parser->parse($request);

        expect($result->items[0]['type'])->toBe('image');
    });

    it('determines post type as video for video urls', function () {
        $postJson = json_encode([
            'data' => [
                'children' => [
                    [
                        'data' => [
                            'title' => 'Video Post',
                            'url' => 'https://v.redd.it/video123',
                            'permalink' => '/r/test/1',
                            'is_self' => false,
                            'created_utc' => 1704067200,
                        ],
                    ],
                ],
            ],
        ]);

        $this->httpClient->shouldReceive('get')
            ->andReturn($postJson);

        $request = new ParseRequestDTO(
            source: 'https://reddit.com/r/test.json',
            type: 'subreddit'
        );

        $result = $this->parser->parse($request);

        expect($result->items[0]['type'])->toBe('video');
    });

    it('determines post type as link for external links', function () {
        $postJson = json_encode([
            'data' => [
                'children' => [
                    [
                        'data' => [
                            'title' => 'Link Post',
                            'url' => 'https://example.com/article',
                            'permalink' => '/r/test/1',
                            'is_self' => false,
                            'created_utc' => 1704067200,
                        ],
                    ],
                ],
            ],
        ]);

        $this->httpClient->shouldReceive('get')
            ->andReturn($postJson);

        $request = new ParseRequestDTO(
            source: 'https://reddit.com/r/test.json',
            type: 'subreddit'
        );

        $result = $this->parser->parse($request);

        expect($result->items[0]['type'])->toBe('link');
    });

    it('handles nsfw flag', function () {
        $postJson = json_encode([
            'data' => [
                'children' => [
                    [
                        'data' => [
                            'title' => 'NSFW Post',
                            'url' => 'https://reddit.com/r/test/1',
                            'permalink' => '/r/test/1',
                            'over_18' => true,
                            'created_utc' => 1704067200,
                        ],
                    ],
                ],
            ],
        ]);

        $this->httpClient->shouldReceive('get')
            ->andReturn($postJson);

        $request = new ParseRequestDTO(
            source: 'https://reddit.com/r/test.json',
            type: 'subreddit'
        );

        $result = $this->parser->parse($request);

        expect($result->items[0]['nsfw'])->toBeTrue();
    });

    it('handles spoiler flag', function () {
        $postJson = json_encode([
            'data' => [
                'children' => [
                    [
                        'data' => [
                            'title' => 'Spoiler Post',
                            'url' => 'https://reddit.com/r/test/1',
                            'permalink' => '/r/test/1',
                            'spoiler' => true,
                            'created_utc' => 1704067200,
                        ],
                    ],
                ],
            ],
        ]);

        $this->httpClient->shouldReceive('get')
            ->andReturn($postJson);

        $request = new ParseRequestDTO(
            source: 'https://reddit.com/r/test.json',
            type: 'subreddit'
        );

        $result = $this->parser->parse($request);

        expect($result->items[0]['spoiler'])->toBeTrue();
    });

    it('handles pagination via after parameter', function () {
        $postJson = json_encode([
            'data' => [
                'after' => 't3_abc123',
                'children' => [
                    [
                        'data' => [
                            'title' => 'Post',
                            'url' => 'https://reddit.com/r/test/1',
                            'permalink' => '/r/test/1',
                            'created_utc' => 1704067200,
                        ],
                    ],
                ],
            ],
        ]);

        $this->httpClient->shouldReceive('get')
            ->andReturn($postJson);

        $request = new ParseRequestDTO(
            source: 'https://reddit.com/r/test.json',
            type: 'subreddit'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->metadata)->toHaveKey('after')
            ->and($result->metadata['after'])->toBe('t3_abc123');
    });

    it('handles invalid json gracefully', function () {
        $this->httpClient->shouldReceive('get')
            ->andReturn('invalid json {{{');

        $request = new ParseRequestDTO(
            source: 'https://reddit.com/r/test.json',
            type: 'subreddit'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeFalse()
            ->and($result->error)->not->toBeNull();
    });

    it('handles empty subreddit gracefully', function () {
        $emptyJson = json_encode([
            'data' => [
                'children' => [],
            ],
        ]);

        $this->httpClient->shouldReceive('get')
            ->andReturn($emptyJson);

        $request = new ParseRequestDTO(
            source: 'https://reddit.com/r/test.json',
            type: 'subreddit'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toBe([])
            ->and($result->total)->toBe(0);
    });

    it('extracts author information', function () {
        $postJson = json_encode([
            'data' => [
                'children' => [
                    [
                        'data' => [
                            'title' => 'Post',
                            'url' => 'https://reddit.com/r/test/1',
                            'permalink' => '/r/test/1',
                            'author' => 'test_user',
                            'created_utc' => 1704067200,
                        ],
                    ],
                ],
            ],
        ]);

        $this->httpClient->shouldReceive('get')
            ->andReturn($postJson);

        $request = new ParseRequestDTO(
            source: 'https://reddit.com/r/test.json',
            type: 'subreddit'
        );

        $result = $this->parser->parse($request);

        expect($result->items[0]['author'])->toBe('test_user');
    });
});
