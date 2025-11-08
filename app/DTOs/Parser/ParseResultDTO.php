<?php

declare(strict_types=1);

namespace App\DTOs\Parser;

readonly class ParseResultDTO
{
    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public bool $success,
        public array $items,
        public ?string $error,
        public array $metadata = [],
        public int $total = 0,
        public ?int $nextOffset = null,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $metadata
     */
    public static function success(
        array $items,
        array $metadata = [],
        ?int $total = null,
        ?int $nextOffset = null,
    ): self {
        return new self(
            success: true,
            items: $items,
            error: null,
            metadata: $metadata,
            total: $total ?? count($items),
            nextOffset: $nextOffset,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<int, array<string, mixed>>  $items
     */
    public static function failure(string $error, array $metadata = [], array $items = []): self
    {
        return new self(
            success: false,
            items: $items,
            error: $error,
            metadata: $metadata,
            total: count($items),
            nextOffset: null,
        );
    }

    /**
     * @return array{
     *     success: bool,
     *     items: array<int, array<string, mixed>>,
     *     error: ?string,
     *     metadata: array<string, mixed>,
     *     total: int,
     *     next_offset: ?int
     * }
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'items' => $this->items,
            'error' => $this->error,
            'metadata' => $this->metadata,
            'total' => $this->total,
            'next_offset' => $this->nextOffset,
        ];
    }
}
