<?php

declare(strict_types=1);

use App\DTOs\Parser\ParseResultDTO;

describe('ParseResultDTO', function () {
    it('creates successful result with items', function () {
        $items = [
            ['title' => 'Item 1', 'url' => 'https://example.com/1'],
            ['title' => 'Item 2', 'url' => 'https://example.com/2'],
        ];

        $dto = new ParseResultDTO(
            success: true,
            items: $items,
            metadata: ['parser' => 'feeds', 'type' => 'rss'],
            total: 2,
        );

        expect($dto->success)->toBeTrue()
            ->and($dto->items)->toBe($items)
            ->and($dto->error)->toBeNull()
            ->and($dto->metadata)->toBe(['parser' => 'feeds', 'type' => 'rss'])
            ->and($dto->total)->toBe(2)
            ->and($dto->nextOffset)->toBeNull();
    });

    it('creates error result with error message', function () {
        $dto = new ParseResultDTO(
            success: false,
            items: [],
            error: 'Failed to fetch URL: Connection timeout',
            metadata: ['parser' => 'feeds'],
        );

        expect($dto->success)->toBeFalse()
            ->and($dto->items)->toBe([])
            ->and($dto->error)->toBe('Failed to fetch URL: Connection timeout')
            ->and($dto->metadata)->toBe(['parser' => 'feeds']);
    });

    it('handles pagination with nextOffset', function () {
        $dto = new ParseResultDTO(
            success: true,
            items: [['title' => 'Item 1']],
            metadata: ['parser' => 'reddit'],
            total: 100,
            nextOffset: 25,
        );

        expect($dto->nextOffset)->toBe(25)
            ->and($dto->total)->toBe(100);
    });

    it('creates result with null error when successful', function () {
        $dto = new ParseResultDTO(
            success: true,
            items: [['title' => 'Item 1']],
            metadata: [],
        );

        expect($dto->error)->toBeNull();
    });

    it('has readonly properties that cannot be modified', function () {
        $dto = new ParseResultDTO(
            success: true,
            items: [],
            metadata: [],
        );

        // Attempting to modify readonly property should throw error
        expect(fn () => $dto->success = false)
            ->toThrow(Error::class);
    });

    it('serializes to array correctly', function () {
        $items = [['title' => 'Item 1']];
        $metadata = ['parser' => 'feeds', 'type' => 'rss'];

        $dto = new ParseResultDTO(
            success: true,
            items: $items,
            metadata: $metadata,
            total: 1,
            nextOffset: null,
            error: null,
        );

        $array = $dto->toArray();

        expect($array)->toBe([
            'success' => true,
            'items' => $items,
            'error' => null,
            'metadata' => $metadata,
            'total' => 1,
            'nextOffset' => null,
        ]);
    });

    it('handles empty items array', function () {
        $dto = new ParseResultDTO(
            success: true,
            items: [],
            metadata: ['parser' => 'feeds'],
            total: 0,
        );

        expect($dto->items)->toBe([])
            ->and($dto->total)->toBe(0);
    });

    it('includes metadata about parser execution', function () {
        $metadata = [
            'parser' => 'feeds',
            'type' => 'rss',
            'execution_time' => 1.23,
            'cache_hit' => false,
        ];

        $dto = new ParseResultDTO(
            success: true,
            items: [],
            metadata: $metadata,
        );

        expect($dto->metadata)->toBe($metadata)
            ->and($dto->metadata['parser'])->toBe('feeds')
            ->and($dto->metadata['execution_time'])->toBe(1.23);
    });
});
