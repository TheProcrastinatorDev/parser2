<?php

declare(strict_types=1);

use App\DTOs\Parser\ParseResultDTO;

it('creates a parse result DTO using the constructor', function (): void {
    $dto = new ParseResultDTO(
        success: true,
        items: [
            [
                'title' => 'Example Article',
                'url' => 'https://example.com/article',
            ],
        ],
        error: null,
        metadata: [
            'parser' => 'feeds',
            'type' => 'rss',
            'execution_time' => 1.23,
        ],
        total: 1,
        nextOffset: null,
    );

    expect($dto->success)
        ->toBeTrue()
        ->and($dto->items)
        ->toHaveCount(1)
        ->and($dto->error)
        ->toBeNull()
        ->and($dto->metadata)
        ->toBe([
            'parser' => 'feeds',
            'type' => 'rss',
            'execution_time' => 1.23,
        ])
        ->and($dto->total)
        ->toBe(1)
        ->and($dto->nextOffset)
        ->toBeNull();
});

it('serialises a parse result DTO to array', function (): void {
    $dto = new ParseResultDTO(
        success: true,
        items: [['id' => 1]],
        error: null,
        metadata: ['parser' => 'feeds'],
        total: 1,
        nextOffset: 10,
    );

    expect($dto->toArray())->toBe([
        'success' => true,
        'items' => [['id' => 1]],
        'error' => null,
        'metadata' => ['parser' => 'feeds'],
        'total' => 1,
        'next_offset' => 10,
    ]);
});

it('creates a successful parse result DTO via helper', function (): void {
    $dto = ParseResultDTO::success(
        items: [
            ['id' => 1],
            ['id' => 2],
        ],
        metadata: ['parser' => 'feeds'],
        total: 5,
        nextOffset: 2,
    );

    expect($dto->success)
        ->toBeTrue()
        ->and($dto->error)
        ->toBeNull()
        ->and($dto->total)
        ->toBe(5)
        ->and($dto->nextOffset)
        ->toBe(2);
});

it('creates a failure parse result DTO via helper', function (): void {
    $dto = ParseResultDTO::failure(
        error: 'Feed fetch failed',
        metadata: [
            'parser' => 'feeds',
            'status_code' => 500,
        ],
    );

    expect($dto->success)
        ->toBeFalse()
        ->and($dto->error)
        ->toBe('Feed fetch failed')
        ->and($dto->items)
        ->toBe([])
        ->and($dto->total)
        ->toBe(0)
        ->and($dto->metadata)
        ->toBe([
            'parser' => 'feeds',
            'status_code' => 500,
        ])
        ->and($dto->nextOffset)
        ->toBeNull();
});

it('prevents mutation of parse result DTO properties', function (): void {
    $dto = ParseResultDTO::success(items: []);

    expect(fn () => $dto->success = false)
        ->toThrow(Error::class, 'Cannot modify readonly property');
});
