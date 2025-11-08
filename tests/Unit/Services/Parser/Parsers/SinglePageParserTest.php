<?php

declare(strict_types=1);

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\SinglePageParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;

uses(Tests\TestCase::class);

describe('SinglePageParser', function () {
    beforeEach(function () {
        $this->httpClient = Mockery::mock(HttpClient::class);
        $this->contentExtractor = Mockery::mock(ContentExtractor::class);
        $this->parser = new SinglePageParser($this->httpClient, $this->contentExtractor);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('extracts content with css selector', function () {
        $html = '
            <html>
                <body>
                    <article class="content">
                        <h1>Article Title</h1>
                        <p>Article content here</p>
                    </article>
                </body>
            </html>
        ';

        $this->httpClient->shouldReceive('get')
            ->with('https://example.com/article')
            ->andReturn($html);

        $this->contentExtractor->shouldReceive('extractText')
            ->with(Mockery::type('string'))
            ->andReturn('Article Title Article content here');

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $this->contentExtractor->shouldReceive('fixRelativeUrls')
            ->with(Mockery::type('string'), 'https://example.com/article')
            ->andReturnArg(0);

        $request = new ParseRequestDTO(
            source: 'https://example.com/article',
            type: 'css',
            options: ['selector' => 'article.content']
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(1)
            ->and($result->items[0]['content'])->toContain('Article Title');
    });

    it('extracts content with xpath selector', function () {
        $html = '
            <html>
                <body>
                    <div id="main">
                        <p>XPath content</p>
                    </div>
                </body>
            </html>
        ';

        $this->httpClient->shouldReceive('get')
            ->andReturn($html);

        $this->contentExtractor->shouldReceive('extractText')
            ->andReturn('XPath content');

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $this->contentExtractor->shouldReceive('fixRelativeUrls')
            ->andReturnArg(0);

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'xpath',
            options: ['selector' => '//div[@id="main"]']
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['content'])->toContain('XPath content');
    });

    it('auto-detects content with intelligent extraction', function () {
        $html = '
            <html>
                <body>
                    <article>
                        <h1>Auto-detected Article</h1>
                        <p>This is the main content.</p>
                    </article>
                </body>
            </html>
        ';

        $this->httpClient->shouldReceive('get')
            ->andReturn($html);

        $this->contentExtractor->shouldReceive('extractText')
            ->andReturn('Auto-detected Article This is the main content.');

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $this->contentExtractor->shouldReceive('fixRelativeUrls')
            ->andReturnArg(0);

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'auto' // Auto-detect
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['content'])->not->toBeNull();
    });

    it('extracts images from page', function () {
        $html = '<html><body><img src="/image.jpg"></body></html>';

        $this->httpClient->shouldReceive('get')
            ->andReturn($html);

        $this->contentExtractor->shouldReceive('extractText')
            ->andReturn('');

        $this->contentExtractor->shouldReceive('extractImages')
            ->with(Mockery::type('string'))
            ->andReturn(['https://example.com/image.jpg']);

        $this->contentExtractor->shouldReceive('fixRelativeUrls')
            ->with(Mockery::type('string'), 'https://example.com')
            ->andReturn('<html><body><img src="https://example.com/image.jpg"></body></html>');

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'auto'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['images'])->toBe(['https://example.com/image.jpg']);
    });

    it('fixes relative urls to absolute', function () {
        $html = '<html><body><a href="/page">Link</a></body></html>';

        $this->httpClient->shouldReceive('get')
            ->andReturn($html);

        $this->contentExtractor->shouldReceive('extractText')
            ->andReturn('Link');

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $this->contentExtractor->shouldReceive('fixRelativeUrls')
            ->with(Mockery::type('string'), 'https://example.com')
            ->andReturn('<html><body><a href="https://example.com/page">Link</a></body></html>');

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'auto'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['html'])->toContain('https://example.com/page');
    });

    it('cleans html before extraction when option enabled', function () {
        $html = '<html><body><script>alert("xss")</script><p>Content</p></body></html>';

        $this->httpClient->shouldReceive('get')
            ->andReturn($html);

        $this->contentExtractor->shouldReceive('cleanHtml')
            ->with($html)
            ->andReturn('<html><body><p>Content</p></body></html>');

        $this->contentExtractor->shouldReceive('extractText')
            ->andReturn('Content');

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $this->contentExtractor->shouldReceive('fixRelativeUrls')
            ->andReturnArg(0);

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'auto',
            options: ['clean_html' => true]
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue();
    });

    it('handles extraction errors gracefully', function () {
        $this->httpClient->shouldReceive('get')
            ->andThrow(new Exception('Network error'));

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'auto'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeFalse()
            ->and($result->error)->toContain('Network error');
    });

    it('returns title from page', function () {
        $html = '<html><head><title>Page Title</title></head><body>Content</body></html>';

        $this->httpClient->shouldReceive('get')
            ->andReturn($html);

        $this->contentExtractor->shouldReceive('extractText')
            ->andReturn('Content');

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $this->contentExtractor->shouldReceive('fixRelativeUrls')
            ->andReturnArg(0);

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'auto'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['title'])->toBe('Page Title');
    });

    it('handles pages with no content', function () {
        $html = '<html><body></body></html>';

        $this->httpClient->shouldReceive('get')
            ->andReturn($html);

        $this->contentExtractor->shouldReceive('extractText')
            ->andReturn('');

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $this->contentExtractor->shouldReceive('fixRelativeUrls')
            ->andReturnArg(0);

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'auto'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(1);
    });

    it('extracts meta description', function () {
        $html = '<html><head><meta name="description" content="Meta description"></head></html>';

        $this->httpClient->shouldReceive('get')
            ->andReturn($html);

        $this->contentExtractor->shouldReceive('extractText')
            ->andReturn('');

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $this->contentExtractor->shouldReceive('fixRelativeUrls')
            ->andReturnArg(0);

        $request = new ParseRequestDTO(
            source: 'https://example.com',
            type: 'auto'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['description'])->toBe('Meta description');
    });
});
