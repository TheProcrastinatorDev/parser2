<?php

declare(strict_types=1);

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\MediumParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;

uses(Tests\TestCase::class);

describe('MediumParser', function () {
    beforeEach(function () {
        $this->httpClient = Mockery::mock(HttpClient::class);
        $this->contentExtractor = Mockery::mock(ContentExtractor::class);
        $this->parser = new MediumParser($this->httpClient, $this->contentExtractor);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('parses medium article from url', function () {
        $html = '
            <html>
                <head>
                    <title>Test Article Title</title>
                    <meta property="og:description" content="Article description here" />
                    <meta property="article:published_time" content="2025-01-15T10:00:00.000Z" />
                    <meta name="author" content="John Doe" />
                </head>
                <body>
                    <article>
                        <h1>Test Article Title</h1>
                        <p>Article content paragraph 1.</p>
                        <p>Article content paragraph 2.</p>
                    </article>
                </body>
            </html>
        ';

        $this->httpClient->shouldReceive('get')
            ->with('https://medium.com/@author/test-article')
            ->andReturn($html);

        $this->contentExtractor->shouldReceive('cleanHtml')
            ->andReturn('<article><p>Article content paragraph 1.</p><p>Article content paragraph 2.</p></article>');

        $this->contentExtractor->shouldReceive('extractText')
            ->andReturn('Article content paragraph 1. Article content paragraph 2.');

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://medium.com/@author/test-article',
            type: 'medium'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(1)
            ->and($result->items[0]['title'])->toBe('Test Article Title')
            ->and($result->items[0]['author'])->toBe('John Doe')
            ->and($result->items[0]['description'])->toBe('Article description here');
    });

    it('extracts article metadata correctly', function () {
        $html = '
            <html>
                <head>
                    <meta property="article:published_time" content="2025-01-15T12:30:00.000Z" />
                    <meta property="article:tag" content="Technology" />
                    <meta property="article:tag" content="Programming" />
                    <meta name="twitter:data1" content="5 min read" />
                </head>
                <body><article><p>Content</p></article></body>
            </html>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('cleanHtml')->andReturn('<article><p>Content</p></article>');
        $this->contentExtractor->shouldReceive('extractText')->andReturn('Content');
        $this->contentExtractor->shouldReceive('extractImages')->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://medium.com/article',
            type: 'medium'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['published_at'])->toBe('2025-01-15T12:30:00.000Z')
            ->and($result->items[0]['tags'])->toContain('Technology')
            ->and($result->items[0]['tags'])->toContain('Programming')
            ->and($result->items[0]['reading_time'])->toBe('5 min read');
    });

    it('extracts images from medium article', function () {
        $html = '
            <html>
                <body>
                    <article>
                        <img src="https://miro.medium.com/image1.jpg" />
                        <p>Content with image</p>
                    </article>
                </body>
            </html>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('cleanHtml')->andReturn($html);
        $this->contentExtractor->shouldReceive('extractText')->andReturn('Content with image');
        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn(['https://miro.medium.com/image1.jpg']);

        $request = new ParseRequestDTO(
            source: 'https://medium.com/article',
            type: 'medium'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['images'])->toContain('https://miro.medium.com/image1.jpg');
    });

    it('handles paywall detection', function () {
        $html = '
            <html>
                <body>
                    <div class="meteredContent">
                        <article><p>Premium content</p></article>
                    </div>
                </body>
            </html>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('cleanHtml')->andReturn('<article><p>Premium content</p></article>');
        $this->contentExtractor->shouldReceive('extractText')->andReturn('Premium content');
        $this->contentExtractor->shouldReceive('extractImages')->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://medium.com/article',
            type: 'medium'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['is_paywalled'])->toBeTrue();
    });

    it('extracts clap count from article', function () {
        $html = '
            <html>
                <body>
                    <article>
                        <button data-action="show-recommends-list">
                            <span>1.2K</span>
                        </button>
                        <p>Content</p>
                    </article>
                </body>
            </html>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('cleanHtml')->andReturn($html);
        $this->contentExtractor->shouldReceive('extractText')->andReturn('Content');
        $this->contentExtractor->shouldReceive('extractImages')->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://medium.com/article',
            type: 'medium'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['claps'])->toBe('1.2K');
    });

    it('extracts canonical url from article', function () {
        $html = '
            <html>
                <head>
                    <link rel="canonical" href="https://medium.com/@author/canonical-article" />
                </head>
                <body><article><p>Content</p></article></body>
            </html>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('cleanHtml')->andReturn('<article><p>Content</p></article>');
        $this->contentExtractor->shouldReceive('extractText')->andReturn('Content');
        $this->contentExtractor->shouldReceive('extractImages')->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://medium.com/article',
            type: 'medium'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['canonical_url'])->toBe('https://medium.com/@author/canonical-article');
    });

    it('parses publication name from url', function () {
        $html = '
            <html>
                <head>
                    <meta property="og:site_name" content="Towards Data Science" />
                </head>
                <body><article><p>Content</p></article></body>
            </html>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('cleanHtml')->andReturn('<article><p>Content</p></article>');
        $this->contentExtractor->shouldReceive('extractText')->andReturn('Content');
        $this->contentExtractor->shouldReceive('extractImages')->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://medium.com/publication/article',
            type: 'medium'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['publication'])->toBe('Towards Data Science');
    });

    it('handles articles with no publication', function () {
        $html = '<html><body><article><p>Content</p></article></body></html>';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('cleanHtml')->andReturn('<article><p>Content</p></article>');
        $this->contentExtractor->shouldReceive('extractText')->andReturn('Content');
        $this->contentExtractor->shouldReceive('extractImages')->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://medium.com/@author/article',
            type: 'medium'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['publication'])->toBeNull();
    });

    it('handles http errors gracefully', function () {
        $this->httpClient->shouldReceive('get')
            ->andThrow(new Exception('Article not found'));

        $request = new ParseRequestDTO(
            source: 'https://medium.com/nonexistent',
            type: 'medium'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeFalse()
            ->and($result->error)->toContain('Article not found');
    });

    it('handles articles with no content', function () {
        $html = '<html><body><div>No article here</div></body></html>';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('cleanHtml')->andReturn($html);
        $this->contentExtractor->shouldReceive('extractText')->andReturn('');
        $this->contentExtractor->shouldReceive('extractImages')->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://medium.com/article',
            type: 'medium'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(1)
            ->and($result->items[0]['content'])->toBe('');
    });
});
