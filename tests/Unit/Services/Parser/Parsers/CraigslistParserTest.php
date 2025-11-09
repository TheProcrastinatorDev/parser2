<?php

declare(strict_types=1);

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\CraigslistParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;

uses(Tests\TestCase::class);

describe('CraigslistParser', function () {
    beforeEach(function () {
        $this->httpClient = Mockery::mock(HttpClient::class);
        $this->contentExtractor = Mockery::mock(ContentExtractor::class);
        $this->parser = new CraigslistParser($this->httpClient, $this->contentExtractor);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('parses craigslist search results', function () {
        $html = '
            <html>
                <body>
                    <ul class="rows">
                        <li class="result-row">
                            <a href="https://sfbay.craigslist.org/sfc/apa/d/test-listing-1/123.html" class="result-title">Test Listing 1</a>
                            <span class="result-price">$2000</span>
                            <span class="result-hood">(San Francisco)</span>
                            <time datetime="2025-01-15 10:00">Jan 15</time>
                        </li>
                        <li class="result-row">
                            <a href="https://sfbay.craigslist.org/sfc/apa/d/test-listing-2/456.html" class="result-title">Test Listing 2</a>
                            <span class="result-price">$2500</span>
                            <span class="result-hood">(Oakland)</span>
                            <time datetime="2025-01-15 11:00">Jan 15</time>
                        </li>
                    </ul>
                </body>
            </html>
        ';

        $this->httpClient->shouldReceive('get')
            ->with(Mockery::pattern('/craigslist\.org\/search\//'))
            ->andReturn($html);

        $request = new ParseRequestDTO(
            source: 'https://sfbay.craigslist.org/search/apa',
            type: 'craigslist'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(2)
            ->and($result->items[0]['title'])->toBe('Test Listing 1')
            ->and($result->items[0]['price'])->toBe('$2000')
            ->and($result->items[1]['title'])->toBe('Test Listing 2');
    });

    it('extracts listing metadata correctly', function () {
        $html = '
            <li class="result-row">
                <a href="https://sfbay.craigslist.org/sfc/apa/d/listing/123.html" class="result-title">Test</a>
                <span class="result-price">$1500</span>
                <span class="result-hood">(Downtown)</span>
                <time datetime="2025-01-15 12:30">Jan 15</time>
                <span class="result-meta">
                    <span class="housing">2br - 1000ft²</span>
                </span>
            </li>
        ';

        $this->httpClient->shouldReceive('get')->andReturn('<ul class="rows">'.$html.'</ul>');

        $request = new ParseRequestDTO(
            source: 'https://sfbay.craigslist.org/search/apa',
            type: 'craigslist'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['location'])->toBe('(Downtown)')
            ->and($result->items[0]['posted_at'])->toBe('2025-01-15 12:30')
            ->and($result->items[0]['housing'])->toBe('2br - 1000ft²');
    });

    it('handles listings without price', function () {
        $html = '
            <ul class="rows">
                <li class="result-row">
                    <a href="https://sfbay.craigslist.org/sfc/jjj/d/job/123.html" class="result-title">Job Listing</a>
                    <span class="result-hood">(SF)</span>
                </li>
            </ul>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);

        $request = new ParseRequestDTO(
            source: 'https://sfbay.craigslist.org/search/jjj',
            type: 'craigslist'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['price'])->toBeNull();
    });

    it('extracts listing images', function () {
        $html = '
            <ul class="rows">
                <li class="result-row">
                    <a href="https://sfbay.craigslist.org/sfc/apa/d/listing/123.html" class="result-title">Test</a>
                    <a class="result-image gallery" data-ids="1:abc,2:def">
                        <img src="https://images.craigslist.org/thumb1.jpg" />
                    </a>
                </li>
            </ul>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);

        $request = new ParseRequestDTO(
            source: 'https://sfbay.craigslist.org/search/apa',
            type: 'craigslist'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['has_image'])->toBeTrue()
            ->and($result->items[0]['image_ids'])->toBe('1:abc,2:def');
    });

    it('handles pagination with offset', function () {
        $html = '<ul class="rows"></ul>';

        $this->httpClient->shouldReceive('get')
            ->with(Mockery::pattern('/s=120/'))
            ->andReturn($html);

        $request = new ParseRequestDTO(
            source: 'https://sfbay.craigslist.org/search/apa',
            type: 'craigslist',
            offset: 120
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue();
    });

    it('handles search with query parameter', function () {
        $html = '<ul class="rows"></ul>';

        $this->httpClient->shouldReceive('get')
            ->with(Mockery::pattern('/query=apartment/'))
            ->andReturn($html);

        $request = new ParseRequestDTO(
            source: 'https://sfbay.craigslist.org/search/apa',
            type: 'craigslist',
            keywords: ['apartment']
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue();
    });

    it('filters out duplicate listings', function () {
        $html = '
            <ul class="rows">
                <li class="result-row" data-pid="123">
                    <a href="/listing1.html" class="result-title">Listing 1</a>
                </li>
                <li class="result-row" data-pid="123">
                    <a href="/listing1.html" class="result-title">Listing 1 Duplicate</a>
                </li>
                <li class="result-row" data-pid="456">
                    <a href="/listing2.html" class="result-title">Listing 2</a>
                </li>
            </ul>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);

        $request = new ParseRequestDTO(
            source: 'https://sfbay.craigslist.org/search/sss',
            type: 'craigslist'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(2);
    });

    it('handles no search results', function () {
        $html = '
            <html>
                <body>
                    <div class="cl-search-result">
                        <div class="cl-no-results">No results found</div>
                    </div>
                </body>
            </html>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);

        $request = new ParseRequestDTO(
            source: 'https://sfbay.craigslist.org/search/sss',
            type: 'craigslist'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toBeArray()
            ->and($result->items)->toHaveCount(0);
    });

    it('handles http errors gracefully', function () {
        $this->httpClient->shouldReceive('get')
            ->andThrow(new Exception('Search failed'));

        $request = new ParseRequestDTO(
            source: 'https://sfbay.craigslist.org/search/sss',
            type: 'craigslist'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeFalse()
            ->and($result->error)->toContain('Search failed');
    });

    it('builds correct search url with parameters', function () {
        $html = '<ul class="rows"></ul>';

        $this->httpClient->shouldReceive('get')
            ->with('https://sfbay.craigslist.org/search/apa?query=apartment&s=0')
            ->andReturn($html);

        $request = new ParseRequestDTO(
            source: 'https://sfbay.craigslist.org/search/apa',
            type: 'craigslist',
            keywords: ['apartment'],
            offset: 0
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue();
    });
});
