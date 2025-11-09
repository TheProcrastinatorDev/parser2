<?php

declare(strict_types=1);

namespace App\DTOs\Parser;

readonly class ParseResultDTO
{
    /**
     * Create a new ParseResultDTO instance.
     *
     * @param  bool  $success  Whether the parsing was successful
     * @param  array<int, array<string, mixed>>  $items  Parsed items
     * @param  string|null  $error  Error message if parsing failed
     * @param  array<string, mixed>  $metadata  Additional metadata about the parsing
     * @param  int|null  $total  Total number of items available
     * @param  int|null  $nextOffset  Offset for the next page (if paginated)
     */
    public function __construct(
        public bool $success,
        public array $items,
        public ?string $error = null,
        public array $metadata = [],
        public ?int $total = null,
        public ?int $nextOffset = null,
    ) {}

    /**
     * Convert DTO to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'items' => $this->items,
            'error' => $this->error,
            'metadata' => $this->metadata,
            'total' => $this->total,
            'nextOffset' => $this->nextOffset,
        ];
    }
}
