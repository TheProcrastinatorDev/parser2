<?php

use App\DTOs\Parser\ParseResultDTO;

it('can be created with required fields', function () {
    $dto = new ParseResultDTO(
        success: true,
        items: [
            [
                'title' => 'Test Article',
                'url' => 'https://example.com/article',
            ],
        ],
        metadata: [
            'parser' => 'feeds',
            'type' => 'rss',
        ],
    );

    expect($dto->success)->toBeTrue()
        ->and($dto->items)->toHaveCount(1)
        ->and($dto->items[0]['title'])->toBe('Test Article')
        ->and($dto->error)->toBeNull()
        ->and($dto->metadata)->toBe(['parser' => 'feeds', 'type' => 'rss'])
        ->and($dto->total)->toBeNull()
        ->and($dto->nextOffset)->toBeNull();
});

it('can be created with error', function () {
    $dto = new ParseResultDTO(
        success: false,
        items: [],
        metadata: ['parser' => 'feeds'],
        error: 'Failed to fetch feed',
    );

    expect($dto->success)->toBeFalse()
        ->and($dto->items)->toBeEmpty()
        ->and($dto->error)->toBe('Failed to fetch feed');
});

it('can be created with pagination', function () {
    $dto = new ParseResultDTO(
        success: true,
        items: [
            ['title' => 'Item 1'],
            ['title' => 'Item 2'],
        ],
        metadata: ['parser' => 'feeds'],
        total: 50,
        nextOffset: 10,
    );

    expect($dto->total)->toBe(50)
        ->and($dto->nextOffset)->toBe(10);
});

it('has readonly properties', function () {
    $dto = new ParseResultDTO(
        success: true,
        items: [],
        metadata: [],
    );

    $reflection = new ReflectionClass($dto);
    $property = $reflection->getProperty('success');
    
    expect($property->isReadOnly())->toBeTrue();
});
