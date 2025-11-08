<?php

use App\DTOs\Parser\ParseRequestDTO;
use App\DTOs\Parser\ParseResultDTO;
use App\Services\Parser\AbstractParser;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\RateLimiter;

// Create a concrete implementation for testing
class TestParser extends AbstractParser
{
    protected string $name = 'test';
    protected array $supportedTypes = ['test'];

    protected function parseInternal(ParseRequestDTO $request): array
    {
        return [
            [
                'title' => 'Test Item',
                'url' => $request->source,
                'content' => 'Test content',
            ],
        ];
    }
}

beforeEach(function () {
    // Mock config
    config(['parser.test' => [
        'enabled' => true,
        'rate_limit' => ['requests' => 10, 'window' => 60],
    ]]);
});

it('loads configuration from config file', function () {
    $parser = new TestParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $config = $parser->getConfig();
    
    expect($config)->toBeArray()
        ->and($config['enabled'])->toBeTrue();
});

it('validates ParseRequestDTO', function () {
    $parser = new TestParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com',
        'type' => 'test',
    ]);

    expect(fn() => $parser->parse($request))->not->toThrow();
});

it('processes items through pipeline', function () {
    $parser = new TestParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com',
        'type' => 'test',
    ]);

    $result = $parser->parse($request);

    expect($result)->toBeInstanceOf(ParseResultDTO::class)
        ->and($result->success)->toBeTrue()
        ->and($result->items)->toHaveCount(1)
        ->and($result->items[0]['title'])->toBe('Test Item');
});

it('supports pagination via offset and limit', function () {
    $parser = new TestParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com',
        'type' => 'test',
        'limit' => 1,
        'offset' => 0,
    ]);

    $result = $parser->parse($request);

    expect($result->items)->toHaveCount(1);
});

it('handles errors gracefully', function () {
    // Create a parser that throws an exception
    $failingParser = new class(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    ) extends AbstractParser {
        protected string $name = 'failing';
        protected array $supportedTypes = ['failing'];

        protected function parseInternal(ParseRequestDTO $request): array
        {
            throw new Exception('Parse failed');
        }
    };

    config(['parser.failing' => ['enabled' => true]]);

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://example.com',
        'type' => 'failing',
    ]);

    $result = $failingParser->parse($request);

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('Parse failed');
});
