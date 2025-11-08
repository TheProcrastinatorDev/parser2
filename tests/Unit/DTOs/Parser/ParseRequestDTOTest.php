<?php

use App\DTOs\Parser\ParseRequestDTO;

it('can be created from array', function () {
    $data = [
        'source' => 'https://example.com/feed.xml',
        'type' => 'rss',
        'keywords' => ['laravel', 'php'],
        'options' => ['timeout' => 30],
        'limit' => 10,
        'offset' => 0,
        'filters' => ['date_from' => '2025-01-01'],
    ];

    $dto = ParseRequestDTO::fromArray($data);

    expect($dto->source)->toBe('https://example.com/feed.xml')
        ->and($dto->type)->toBe('rss')
        ->and($dto->keywords)->toBe(['laravel', 'php'])
        ->and($dto->options)->toBe(['timeout' => 30])
        ->and($dto->limit)->toBe(10)
        ->and($dto->offset)->toBe(0)
        ->and($dto->filters)->toBe(['date_from' => '2025-01-01']);
});

it('can be created with minimal required fields', function () {
    $data = [
        'source' => 'https://example.com/feed.xml',
        'type' => 'rss',
    ];

    $dto = ParseRequestDTO::fromArray($data);

    expect($dto->source)->toBe('https://example.com/feed.xml')
        ->and($dto->type)->toBe('rss')
        ->and($dto->keywords)->toBe([])
        ->and($dto->options)->toBe([])
        ->and($dto->limit)->toBeNull()
        ->and($dto->offset)->toBeNull()
        ->and($dto->filters)->toBe([]);
});

it('can be serialized to array', function () {
    $data = [
        'source' => 'https://example.com/feed.xml',
        'type' => 'rss',
        'keywords' => ['laravel'],
        'options' => ['timeout' => 30],
        'limit' => 10,
        'offset' => 0,
        'filters' => ['date_from' => '2025-01-01'],
    ];

    $dto = ParseRequestDTO::fromArray($data);
    $array = $dto->toArray();

    expect($array)->toBe($data);
});

it('has readonly properties', function () {
    $dto = ParseRequestDTO::fromArray([
        'source' => 'https://example.com/feed.xml',
        'type' => 'rss',
    ]);

    // Verify it's readonly by checking reflection
    $reflection = new ReflectionClass($dto);
    $property = $reflection->getProperty('source');
    
    expect($property->isReadOnly())->toBeTrue();
});
