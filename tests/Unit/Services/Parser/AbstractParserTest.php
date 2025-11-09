<?php

declare(strict_types=1);

use App\DTOs\Parser\ParseRequestDTO;
use App\DTOs\Parser\ParseResultDTO;
use App\Services\Parser\AbstractParser;
use Illuminate\Support\Facades\Config;

// Concrete test implementation of AbstractParser
class TestParser extends AbstractParser
{
    public bool $shouldThrowException = false;

    public array $mockItems = [];

    protected function doParse(ParseRequestDTO $request): array
    {
        if ($this->shouldThrowException) {
            throw new \Exception('Test exception');
        }

        return $this->mockItems;
    }

    protected function getName(): string
    {
        return 'test';
    }

    public function getConfigForTest(string $key, mixed $default = null): mixed
    {
        return $this->getConfig($key, $default);
    }
}

uses(Tests\TestCase::class);

describe('AbstractParser', function () {
    beforeEach(function () {
        $this->parser = new TestParser;
    });

    it('loads configuration from config file', function () {
        $timeout = $this->parser->getConfigForTest('timeout');

        expect($timeout)->toBeGreaterThan(0);
    });

    it('loads parser-specific configuration with fallback to default', function () {
        // TestParser uses 'test' as parser name which doesn't exist in config
        // So it should fall back to the provided default
        $timeout = $this->parser->getConfigForTest('timeout', 30);

        expect($timeout)->toBeGreaterThan(0);
    });

    it('returns success result when parsing succeeds', function () {
        $items = [
            ['title' => 'Item 1', 'url' => 'https://example.com/1'],
            ['title' => 'Item 2', 'url' => 'https://example.com/2'],
        ];

        $this->parser->mockItems = $items;

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'test'
        );

        $result = $this->parser->parse($request);

        expect($result)->toBeInstanceOf(ParseResultDTO::class)
            ->and($result->success)->toBeTrue()
            ->and($result->items)->toBe($items)
            ->and($result->error)->toBeNull()
            ->and($result->metadata['parser'])->toBe('test');
    });

    it('handles errors gracefully and returns error result', function () {
        $this->parser->shouldThrowException = true;

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'test'
        );

        $result = $this->parser->parse($request);

        expect($result)->toBeInstanceOf(ParseResultDTO::class)
            ->and($result->success)->toBeFalse()
            ->and($result->items)->toBe([])
            ->and($result->error)->toContain('Test exception');
    });

    it('applies limit to parsed items', function () {
        $items = [
            ['title' => 'Item 1'],
            ['title' => 'Item 2'],
            ['title' => 'Item 3'],
            ['title' => 'Item 4'],
            ['title' => 'Item 5'],
        ];

        $this->parser->mockItems = $items;

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'test',
            limit: 3
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(3)
            ->and($result->total)->toBe(5);
    });

    it('applies offset to parsed items', function () {
        $items = [
            ['title' => 'Item 1'],
            ['title' => 'Item 2'],
            ['title' => 'Item 3'],
            ['title' => 'Item 4'],
            ['title' => 'Item 5'],
        ];

        $this->parser->mockItems = $items;

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'test',
            offset: 2
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(3)
            ->and($result->items[0]['title'])->toBe('Item 3');
    });

    it('calculates nextOffset for pagination', function () {
        $items = array_map(fn ($i) => ['title' => "Item $i"], range(1, 10));

        $this->parser->mockItems = $items;

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'test',
            limit: 3,
            offset: 0
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(3)
            ->and($result->total)->toBe(10)
            ->and($result->nextOffset)->toBe(3);
    });

    it('sets nextOffset to null when no more items', function () {
        $items = [
            ['title' => 'Item 1'],
            ['title' => 'Item 2'],
        ];

        $this->parser->mockItems = $items;

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'test',
            limit: 5,
            offset: 0
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->nextOffset)->toBeNull();
    });

    it('includes metadata about parser execution', function () {
        $this->parser->mockItems = [['title' => 'Item 1']];

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'test'
        );

        $result = $this->parser->parse($request);

        expect($result->metadata)->toHaveKey('parser')
            ->and($result->metadata['parser'])->toBe('test')
            ->and($result->metadata)->toHaveKey('source')
            ->and($result->metadata['source'])->toBe('https://example.com');
    });

    it('validates parse request DTO', function () {
        $request = new ParseRequestDTO(
            source: '',
            type: 'test'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeFalse()
            ->and($result->error)->toContain('source');
    });
});
