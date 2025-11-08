<?php

declare(strict_types=1);

namespace App\DTOs\Parser;

readonly class ParseRequestDTO
{
    public function __construct(
        public string $parser,
        public string $source,
        public string $type,
        public array $keywords = [],
        public array $options = [],
        public ?int $limit = null,
        public ?int $offset = null,
        public array $filters = [],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            parser: $payload['parser'],
            source: $payload['source'],
            type: $payload['type'],
            keywords: $payload['keywords'] ?? [],
            options: $payload['options'] ?? [],
            limit: array_key_exists('limit', $payload) ? self::toNullableInt($payload['limit']) : null,
            offset: array_key_exists('offset', $payload) ? self::toNullableInt($payload['offset']) : null,
            filters: $payload['filters'] ?? [],
        );
    }

    /**
     * @return array{
     *     parser: string,
     *     source: string,
     *     type: string,
     *     keywords: array<int, mixed>,
     *     options: array<string, mixed>,
     *     limit: ?int,
     *     offset: ?int,
     *     filters: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'parser' => $this->parser,
            'source' => $this->source,
            'type' => $this->type,
            'keywords' => $this->keywords,
            'options' => $this->options,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'filters' => $this->filters,
        ];
    }

    /**
     * @param  mixed  $value
     */
    private static function toNullableInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return (int) $value;
    }
}
