<?php

declare(strict_types=1);

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\BingSearchParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;

uses(Tests\TestCase::class);

describe('BingSearchParser', function () {
    beforeEach(function () {
        $this->httpClient = Mockery::mock(HttpClient::class);
        $this->contentExtractor = Mockery::mock(ContentExtractor::class);
        $this->parser = new BingSearchParser($this->httpClient, $this->contentExtractor);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('parses bing search results', function () {
        $html = '
            <html>
                <body>
                    <ol id="b_results">
                        <li class="b_algo">
                            <h2><a href="https://example.com/page1">Test Result 1</a></h2>
                            <p>This is the description for result 1.</p>
                            <div class="b_attribution"><cite>example.com</cite></div>
                        </li>
                        <li class="b_algo">
                            <h2><a href="https://example.com/page2">Test Result 2</a></h2>
                            <p>This is the description for result 2.</p>
                            <div class="b_attribution"><cite>example.com</cite></div>
                        </li>
                    </ol>
                </body>
            </html>
        ';

        $this->httpClient->shouldReceive('get')
            ->with(Mockery::pattern('/bing\.com\/search\?q=test\+query/'))
            ->andReturn($html);

        $this->contentExtractor->shouldReceive('extractText')
            ->andReturn('This is the description for result 1.', 'This is the description for result 2.');

        $request = new ParseRequestDTO(
            source: 'test query',
            type: 'bing'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(2)
            ->and($result->items[0]['title'])->toBe('Test Result 1')
            ->and($result->items[0]['url'])->toBe('https://example.com/page1')
            ->and($result->items[1]['title'])->toBe('Test Result 2');
    });

    it('extracts result metadata correctly', function () {
        $html = '
            <li class="b_algo">
                <h2><a href="https://example.com">Result Title</a></h2>
                <p>Result description here.</p>
                <div class="b_attribution">
                    <cite>example.com › path › to › page</cite>
                </div>
            </li>
        ';

        $this->httpClient->shouldReceive('get')->andReturn('<ol id="b_results">'.$html.'</ol>');
        $this->contentExtractor->shouldReceive('extractText')->andReturn('Result description here.');

        $request = new ParseRequestDTO(
            source: 'test',
            type: 'bing'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['domain'])->toBe('example.com › path › to › page')
            ->and($result->items[0]['description'])->toBe('Result description here.');
    });

    it('handles pagination with offset', function () {
        $html = '<ol id="b_results"><li class="b_algo"><h2><a href="https://example.com">Result</a></h2></li></ol>';

        $this->httpClient->shouldReceive('get')
            ->with(Mockery::pattern('/&first=11/'))
            ->andReturn($html);

        $this->contentExtractor->shouldReceive('extractText')->andReturn('');

        $request = new ParseRequestDTO(
            source: 'test query',
            type: 'bing',
            offset: 10 // Bing uses first=11 for second page
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue();
    });

    it('handles search with filters', function () {
        $html = '<ol id="b_results"></ol>';

        $this->httpClient->shouldReceive('get')
            ->with(Mockery::pattern('/&filters=ex1%3A%22filtervalue%22/'))
            ->andReturn($html);

        $request = new ParseRequestDTO(
            source: 'test',
            type: 'bing',
            filters: ['ex1' => 'filtervalue']
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue();
    });

    it('extracts video search results', function () {
        $html = '
            <ol id="b_results">
                <li class="b_algo b_vidans">
                    <h2><a href="https://example.com/video">Video Result</a></h2>
                    <p>Video description.</p>
                </li>
            </ol>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('extractText')->andReturn('Video description.');

        $request = new ParseRequestDTO(
            source: 'test video',
            type: 'bing'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['type'])->toBe('video');
    });

    it('extracts news search results', function () {
        $html = '
            <ol id="b_results">
                <li class="b_algo b_news">
                    <h2><a href="https://news.example.com/article">News Article</a></h2>
                    <p>News description.</p>
                    <span class="news_dt">2 hours ago</span>
                </li>
            </ol>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('extractText')->andReturn('News description.');

        $request = new ParseRequestDTO(
            source: 'test news',
            type: 'bing'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['type'])->toBe('news')
            ->and($result->items[0]['published_time'])->toBe('2 hours ago');
    });

    it('handles no search results', function () {
        $html = '
            <html>
                <body>
                    <ol id="b_results">
                        <!-- No results -->
                    </ol>
                </body>
            </html>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);

        $request = new ParseRequestDTO(
            source: 'nonexistent query',
            type: 'bing'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toBeArray()
            ->and($result->items)->toHaveCount(0);
    });

    it('builds correct search url with query', function () {
        $html = '<ol id="b_results"></ol>';

        $this->httpClient->shouldReceive('get')
            ->with('https://www.bing.com/search?q=multiple+word+query&count=50')
            ->andReturn($html);

        $request = new ParseRequestDTO(
            source: 'multiple word query',
            type: 'bing'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue();
    });

    it('respects limit parameter', function () {
        $html = '<ol id="b_results"></ol>';

        $this->httpClient->shouldReceive('get')
            ->with(Mockery::pattern('/&count=25/'))
            ->andReturn($html);

        $request = new ParseRequestDTO(
            source: 'test',
            type: 'bing',
            limit: 25
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue();
    });

    it('handles http errors gracefully', function () {
        $this->httpClient->shouldReceive('get')
            ->andThrow(new Exception('Search failed'));

        $request = new ParseRequestDTO(
            source: 'test query',
            type: 'bing'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeFalse()
            ->and($result->error)->toContain('Search failed');
    });
});
