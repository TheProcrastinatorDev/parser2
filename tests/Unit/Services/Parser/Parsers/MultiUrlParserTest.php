<?php

declare(strict_types=1);

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\MultiUrlParser;
use App\Services\Parser\Parsers\SinglePageParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;

uses(Tests\TestCase::class);

describe('MultiUrlParser', function () {
    beforeEach(function () {
        $this->httpClient = Mockery::mock(HttpClient::class);
        $this->contentExtractor = Mockery::mock(ContentExtractor::class);
        $this->singlePageParser = Mockery::mock(SinglePageParser::class);
        $this->parser = new MultiUrlParser($this->singlePageParser);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('parses multiple urls from array', function () {
        $urls = [
            'https://example.com/page1',
            'https://example.com/page2',
        ];

        $mockResult1 = new \App\DTOs\Parser\ParseResultDTO(
            success: true,
            items: [['title' => 'Page 1', 'content' => 'Content 1']],
        );

        $mockResult2 = new \App\DTOs\Parser\ParseResultDTO(
            success: true,
            items: [['title' => 'Page 2', 'content' => 'Content 2']],
        );

        $this->singlePageParser->shouldReceive('parse')
            ->with(Mockery::on(function ($request) use ($urls) {
                return $request->source === $urls[0];
            }))
            ->andReturn($mockResult1);

        $this->singlePageParser->shouldReceive('parse')
            ->with(Mockery::on(function ($request) use ($urls) {
                return $request->source === $urls[1];
            }))
            ->andReturn($mockResult2);

        $request = new ParseRequestDTO(
            source: json_encode($urls),
            type: 'multi'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(2)
            ->and($result->items[0]['title'])->toBe('Page 1')
            ->and($result->items[1]['title'])->toBe('Page 2');
    });

    it('includes source url in each result item', function () {
        $urls = ['https://example.com/page1'];

        $mockResult = new \App\DTOs\Parser\ParseResultDTO(
            success: true,
            items: [['title' => 'Test']],
        );

        $this->singlePageParser->shouldReceive('parse')
            ->andReturn($mockResult);

        $request = new ParseRequestDTO(
            source: json_encode($urls),
            type: 'multi'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['source_url'])->toBe('https://example.com/page1');
    });

    it('handles parsing errors for individual urls', function () {
        $urls = [
            'https://example.com/valid',
            'https://example.com/invalid',
        ];

        $mockResult1 = new \App\DTOs\Parser\ParseResultDTO(
            success: true,
            items: [['title' => 'Valid Page']],
        );

        $mockResult2 = new \App\DTOs\Parser\ParseResultDTO(
            success: false,
            items: [],
            error: 'Page not found',
        );

        $this->singlePageParser->shouldReceive('parse')
            ->andReturn($mockResult1, $mockResult2);

        $request = new ParseRequestDTO(
            source: json_encode($urls),
            type: 'multi'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(2)
            ->and($result->items[0]['parse_success'])->toBeTrue()
            ->and($result->items[1]['parse_success'])->toBeFalse()
            ->and($result->items[1]['parse_error'])->toBe('Page not found');
    });

    it('accepts urls as comma-separated string', function () {
        $urlString = 'https://example.com/page1,https://example.com/page2';

        $mockResult = new \App\DTOs\Parser\ParseResultDTO(
            success: true,
            items: [['title' => 'Test']],
        );

        $this->singlePageParser->shouldReceive('parse')
            ->twice()
            ->andReturn($mockResult);

        $request = new ParseRequestDTO(
            source: $urlString,
            type: 'multi'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(2);
    });

    it('accepts urls as newline-separated string', function () {
        $urlString = "https://example.com/page1\nhttps://example.com/page2";

        $mockResult = new \App\DTOs\Parser\ParseResultDTO(
            success: true,
            items: [['title' => 'Test']],
        );

        $this->singlePageParser->shouldReceive('parse')
            ->twice()
            ->andReturn($mockResult);

        $request = new ParseRequestDTO(
            source: $urlString,
            type: 'multi'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(2);
    });

    it('passes through options to single page parser', function () {
        $urls = ['https://example.com'];
        $options = ['selector' => '.content', 'clean_html' => true];

        $this->singlePageParser->shouldReceive('parse')
            ->with(Mockery::on(function ($request) use ($options) {
                return $request->options === $options;
            }))
            ->andReturn(new \App\DTOs\Parser\ParseResultDTO(
                success: true,
                items: [['title' => 'Test']],
            ));

        $request = new ParseRequestDTO(
            source: json_encode($urls),
            type: 'multi',
            options: $options
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue();
    });

    it('handles empty url list', function () {
        $request = new ParseRequestDTO(
            source: json_encode([]),
            type: 'multi'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toBeArray()
            ->and($result->items)->toHaveCount(0);
    });

    it('skips invalid urls', function () {
        $urls = [
            'https://example.com/valid',
            'not-a-url',
            'https://example.com/another-valid',
        ];

        $mockResult = new \App\DTOs\Parser\ParseResultDTO(
            success: true,
            items: [['title' => 'Test']],
        );

        $this->singlePageParser->shouldReceive('parse')
            ->twice() // Only called for 2 valid URLs
            ->andReturn($mockResult);

        $request = new ParseRequestDTO(
            source: json_encode($urls),
            type: 'multi'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(2);
    });

    it('includes metadata about total urls processed', function () {
        $urls = ['https://example.com/page1', 'https://example.com/page2'];

        $mockResult = new \App\DTOs\Parser\ParseResultDTO(
            success: true,
            items: [['title' => 'Test']],
        );

        $this->singlePageParser->shouldReceive('parse')
            ->andReturn($mockResult);

        $request = new ParseRequestDTO(
            source: json_encode($urls),
            type: 'multi'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->metadata)->toHaveKey('total_urls')
            ->and($result->metadata['total_urls'])->toBe(2);
    });

    it('handles json parse errors gracefully', function () {
        $request = new ParseRequestDTO(
            source: 'invalid json {',
            type: 'multi'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeFalse()
            ->and($result->error)->toContain('Invalid source format');
    });
});
