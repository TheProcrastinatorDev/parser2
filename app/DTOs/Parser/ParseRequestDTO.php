<?php

namespace App\DTOs\Parser;

readonly class ParseRequestDTO
{
    public function __construct(
        public string $source,
        public string $type,
        public array $keywords = [],
        public array $options = [],
        public ?int $limit = null,
        public ?int $offset = null,
        public array $filters = [],
    ) {}

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
