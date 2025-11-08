<?php

declare(strict_types=1);

namespace App\Services\Parser;

use App\DTOs\Parser\ParseRequestDTO;
use App\DTOs\Parser\ParseResultDTO;
use Exception;
use Illuminate\Support\Facades\Config;

abstract class AbstractParser
{
    /**
     * Parse content from the given request.
     */
    public function parse(ParseRequestDTO $request): ParseResultDTO
    {
        try {
            // Validate request
            $this->validateRequest($request);

            // Execute parser-specific parsing logic
            $items = $this->doParse($request);

            // Get total count before pagination
            $total = count($items);

            // Apply pagination
            $items = $this->applyPagination($items, $request);

            // Calculate next offset
            $nextOffset = $this->calculateNextOffset($request, $total, count($items));

            // Build metadata
            $metadata = $this->buildMetadata($request);

            return new ParseResultDTO(
                success: true,
                items: $items,
                metadata: $metadata,
                total: $total,
                nextOffset: $nextOffset,
            );
        } catch (Exception $e) {
            return new ParseResultDTO(
                success: false,
                items: [],
                error: $e->getMessage(),
                metadata: ['parser' => $this->getName()],
            );
        }
    }

    /**
     * Validate the parse request.
     *
     * @throws Exception
     */
    protected function validateRequest(ParseRequestDTO $request): void
    {
        if (empty($request->source)) {
            throw new Exception('Parse request validation failed: source is required');
        }
    }

    /**
     * Apply pagination to items.
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    protected function applyPagination(array $items, ParseRequestDTO $request): array
    {
        $offset = $request->offset ?? 0;
        $limit = $request->limit;

        // Apply offset
        if ($offset > 0) {
            $items = array_slice($items, $offset);
        }

        // Apply limit
        if ($limit !== null && $limit > 0) {
            $items = array_slice($items, 0, $limit);
        }

        return $items;
    }

    /**
     * Calculate the next offset for pagination.
     */
    protected function calculateNextOffset(ParseRequestDTO $request, int $total, int $returned): ?int
    {
        // No pagination if no limit specified
        if ($request->limit === null) {
            return null;
        }

        $currentOffset = $request->offset ?? 0;
        $nextOffset = $currentOffset + $returned;

        // No more items
        if ($nextOffset >= $total) {
            return null;
        }

        return $nextOffset;
    }

    /**
     * Build metadata about the parsing execution.
     *
     * @return array<string, mixed>
     */
    protected function buildMetadata(ParseRequestDTO $request): array
    {
        return [
            'parser' => $this->getName(),
            'source' => $request->source,
            'type' => $request->type,
        ];
    }

    /**
     * Get configuration value for this parser.
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        $parserName = $this->getName();

        // Try parser-specific config first
        $parserConfig = Config::get("parser.parsers.{$parserName}.{$key}");

        if ($parserConfig !== null) {
            return $parserConfig;
        }

        // Fall back to defaults
        $defaultConfig = Config::get("parser.defaults.{$key}");

        if ($defaultConfig !== null) {
            return $defaultConfig;
        }

        return $default;
    }

    /**
     * Execute parser-specific parsing logic.
     *
     * @return array<int, array<string, mixed>>
     */
    abstract protected function doParse(ParseRequestDTO $request): array;

    /**
     * Get the parser name for configuration lookup.
     */
    abstract protected function getName(): string;
}
