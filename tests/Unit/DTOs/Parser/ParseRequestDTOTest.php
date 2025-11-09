<?php

declare(strict_types=1);

use App\DTOs\Parser\ParseRequestDTO;

describe('ParseRequestDTO', function () {
    it('creates instance from array with all fields', function () {
        $data = [
            'source' => 'https://example.com/feed.rss',
            'type' => 'rss',
            'keywords' => ['laravel', 'php'],
            'options' => ['user_agent' => 'Mozilla/5.0'],
            'limit' => 10,
            'offset' => 0,
            'filters' => ['category' => 'tech'],
        ];

        $dto = ParseRequestDTO::fromArray($data);

        expect($dto->source)->toBe('https://example.com/feed.rss')
            ->and($dto->type)->toBe('rss')
            ->and($dto->keywords)->toBe(['laravel', 'php'])
            ->and($dto->options)->toBe(['user_agent' => 'Mozilla/5.0'])
            ->and($dto->limit)->toBe(10)
            ->and($dto->offset)->toBe(0)
            ->and($dto->filters)->toBe(['category' => 'tech']);
    });

    it('creates instance from array with only required fields', function () {
        $data = [
            'source' => 'https://example.com/feed.rss',
            'type' => 'rss',
        ];

        $dto = ParseRequestDTO::fromArray($data);

        expect($dto->source)->toBe('https://example.com/feed.rss')
            ->and($dto->type)->toBe('rss')
            ->and($dto->keywords)->toBe([])
            ->and($dto->options)->toBe([])
            ->and($dto->limit)->toBeNull()
            ->and($dto->offset)->toBeNull()
            ->and($dto->filters)->toBe([]);
    });

    it('serializes to array correctly', function () {
        $dto = new ParseRequestDTO(
            source: 'https://example.com/feed.rss',
            type: 'rss',
            keywords: ['laravel', 'php'],
            options: ['user_agent' => 'Mozilla/5.0'],
            limit: 10,
            offset: 0,
            filters: ['category' => 'tech'],
        );

        $array = $dto->toArray();

        expect($array)->toBe([
            'source' => 'https://example.com/feed.rss',
            'type' => 'rss',
            'keywords' => ['laravel', 'php'],
            'options' => ['user_agent' => 'Mozilla/5.0'],
            'limit' => 10,
            'offset' => 0,
            'filters' => ['category' => 'tech'],
        ]);
    });

    it('has readonly properties that cannot be modified', function () {
        $dto = new ParseRequestDTO(
            source: 'https://example.com/feed.rss',
            type: 'rss',
        );

        // Attempting to modify readonly property should throw error
        expect(fn () => $dto->source = 'https://other.com')
            ->toThrow(Error::class);
    });

    it('handles pagination with limit and offset', function () {
        $dto = new ParseRequestDTO(
            source: 'https://example.com/api',
            type: 'json',
            limit: 50,
            offset: 100,
        );

        expect($dto->limit)->toBe(50)
            ->and($dto->offset)->toBe(100);
    });

    it('handles empty arrays for optional array fields', function () {
        $dto = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'html',
        );

        expect($dto->keywords)->toBe([])
            ->and($dto->options)->toBe([])
            ->and($dto->filters)->toBe([]);
    });
});
