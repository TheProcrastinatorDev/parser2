<?php

use App\Services\Parser\ParserManager;
use App\Services\Parser\AbstractParser;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\RateLimiter;

// Create test parsers
class TestParser1 extends AbstractParser
{
    protected string $name = 'test1';
    protected array $supportedTypes = ['type1'];

    protected function parseInternal(\App\DTOs\Parser\ParseRequestDTO $request): array
    {
        return [];
    }
}

class TestParser2 extends AbstractParser
{
    protected string $name = 'test2';
    protected array $supportedTypes = ['type2'];

    protected function parseInternal(\App\DTOs\Parser\ParseRequestDTO $request): array
    {
        return [];
    }
}

it('can register a parser', function () {
    $manager = new ParserManager();
    $parser = new TestParser1(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $manager->register('test1', $parser);

    expect($manager->get('test1'))->toBe($parser);
});

it('can get parser by name', function () {
    $manager = new ParserManager();
    $parser = new TestParser1(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $manager->register('test1', $parser);

    expect($manager->get('test1'))->toBeInstanceOf(TestParser1::class);
});

it('can list all registered parsers', function () {
    $manager = new ParserManager();
    $parser1 = new TestParser1(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );
    $parser2 = new TestParser2(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $manager->register('test1', $parser1);
    $manager->register('test2', $parser2);

    $parsers = $manager->list();

    expect($parsers)->toHaveCount(2)
        ->and($parsers)->toHaveKey('test1')
        ->and($parsers)->toHaveKey('test2');
});

it('throws exception when parser not found', function () {
    $manager = new ParserManager();

    expect(fn() => $manager->get('nonexistent'))
        ->toThrow(Exception::class, 'Parser not found: nonexistent');
});

it('throws exception on duplicate registration', function () {
    $manager = new ParserManager();
    $parser = new TestParser1(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $manager->register('test1', $parser);

    expect(fn() => $manager->register('test1', $parser))
        ->toThrow(Exception::class, 'Parser already registered: test1');
});
