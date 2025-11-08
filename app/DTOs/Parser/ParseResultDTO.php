<?php

namespace App\DTOs\Parser;

readonly class ParseResultDTO
{
    public function __construct(
        public bool $success,
        public array $items,
        public array $metadata,
        public ?string $error = null,
        public ?int $total = null,
        public ?int $nextOffset = null,
    ) {}
}
