<?php

declare(strict_types=1);

namespace App\DTOs\Parser;

readonly class ParseRequestDTO
{
    /**
     * Create a new ParseRequestDTO instance.
     *
     * @param string $source The URL or source to parse
     * @param string $type The type of parsing (rss, atom, json, html, etc.)
     * @param array<int, string> $keywords Keywords to search for (optional)
     * @param array<string, mixed> $options Additional parser-specific options
     * @param int|null $limit Maximum number of items to return
     * @param int|null $offset Offset for pagination
     * @param array<string, mixed> $filters Additional filters to apply
     */
    public function __construct(
        public string $source,
        public string $type,
        public array $keywords = [],
        public array $options = [],
        public ?int $limit = null,
        public ?int $offset = null,
        public array $filters = [],
    ) {}

    /**
     * Create instance from array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            source: $data['source'],
            type: $data['type'],
            keywords: $data['keywords'] ?? [],
            options: $data['options'] ?? [],
            limit: $data['limit'] ?? null,
            offset: $data['offset'] ?? null,
            filters: $data['filters'] ?? [],
        );
    }

    /**
     * Convert DTO to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'type' => $this->type,
            'keywords' => $this->keywords,
            'options' => $this->options,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'filters' => $this->filters,
        ];
    }
}
