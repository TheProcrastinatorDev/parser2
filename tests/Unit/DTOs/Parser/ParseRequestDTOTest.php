<?php

declare(strict_types=1);

use App\DTOs\Parser\ParseRequestDTO;

it('creates a parse request DTO from array data', function (): void {
    $dto = ParseRequestDTO::fromArray([
        'parser' => 'feeds',
        'source' => 'https://example.com/feed.xml',
        'type' => 'rss',
        'keywords' => ['laravel', 'php'],
        'options' => ['max_items' => 25],
        'limit' => 20,
        'offset' => 5,
        'filters' => [
            'date_from' => '2025-01-01',
            'date_to' => '2025-01-31',
        ],
    ]);

    expect($dto->parser)
        ->toBe('feeds')
        ->and($dto->source)
        ->toBe('https://example.com/feed.xml')
        ->and($dto->type)
        ->toBe('rss')
        ->and($dto->keywords)
        ->toBe(['laravel', 'php'])
        ->and($dto->options)
        ->toBe(['max_items' => 25])
        ->and($dto->limit)
        ->toBe(20)
        ->and($dto->offset)
        ->toBe(5)
        ->and($dto->filters)
        ->toBe([
            'date_from' => '2025-01-01',
            'date_to' => '2025-01-31',
        ]);
});

it('applies defaults to optional parse request fields', function (): void {
    $dto = ParseRequestDTO::fromArray([
        'parser' => 'feeds',
        'source' => 'https://example.com/feed.xml',
        'type' => 'rss',
    ]);

    expect($dto->keywords)
        ->toBe([])
        ->and($dto->options)
        ->toBe([])
        ->and($dto->limit)
        ->toBeNull()
        ->and($dto->offset)
        ->toBeNull()
        ->and($dto->filters)
        ->toBe([]);
});

it('serialises a parse request DTO to array', function (): void {
    $dto = ParseRequestDTO::fromArray([
        'parser' => 'feeds',
        'source' => 'https://example.com/feed.xml',
        'type' => 'rss',
        'keywords' => ['laravel'],
        'options' => ['max_items' => 50],
        'limit' => 10,
        'offset' => 0,
        'filters' => ['language' => 'en'],
    ]);

    expect($dto->toArray())->toBe([
        'parser' => 'feeds',
        'source' => 'https://example.com/feed.xml',
        'type' => 'rss',
        'keywords' => ['laravel'],
        'options' => ['max_items' => 50],
        'limit' => 10,
        'offset' => 0,
        'filters' => ['language' => 'en'],
    ]);
});

it('prevents mutation of parse request DTO properties', function (): void {
    $dto = ParseRequestDTO::fromArray([
        'parser' => 'feeds',
        'source' => 'https://example.com/feed.xml',
        'type' => 'rss',
    ]);

    expect(fn () => $dto->source = 'https://malicious.test')
        ->toThrow(Error::class, 'Cannot modify readonly property');
});
