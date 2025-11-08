<?php

declare(strict_types=1);

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\FeedsParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;

uses(Tests\TestCase::class);

describe('FeedsParser', function () {
    beforeEach(function () {
        $this->httpClient = Mockery::mock(HttpClient::class);
        $this->contentExtractor = Mockery::mock(ContentExtractor::class);
        $this->parser = new FeedsParser($this->httpClient, $this->contentExtractor);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('detects and parses rss 2.0 feed', function () {
        $rssFeed = '<?xml version="1.0"?>
<rss version="2.0">
    <channel>
        <title>Test Feed</title>
        <item>
            <title>Item 1</title>
            <link>https://example.com/1</link>
            <description>Description 1</description>
            <pubDate>Mon, 01 Jan 2024 00:00:00 GMT</pubDate>
        </item>
        <item>
            <title>Item 2</title>
            <link>https://example.com/2</link>
            <description>Description 2</description>
        </item>
    </channel>
</rss>';

        $this->httpClient->shouldReceive('get')
            ->with('https://example.com/feed.rss')
            ->andReturn($rssFeed);

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://example.com/feed.rss',
            type: 'rss'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(2)
            ->and($result->items[0]['title'])->toBe('Item 1')
            ->and($result->items[0]['url'])->toBe('https://example.com/1')
            ->and($result->items[0]['description'])->toBe('Description 1')
            ->and($result->items[1]['title'])->toBe('Item 2');
    });

    it('detects and parses atom feed', function () {
        $atomFeed = '<?xml version="1.0"?>
<feed xmlns="http://www.w3.org/2005/Atom">
    <title>Test Atom Feed</title>
    <entry>
        <title>Atom Item 1</title>
        <link href="https://example.com/atom/1"/>
        <summary>Atom description 1</summary>
        <published>2024-01-01T00:00:00Z</published>
    </entry>
    <entry>
        <title>Atom Item 2</title>
        <link href="https://example.com/atom/2"/>
        <summary>Atom description 2</summary>
    </entry>
</feed>';

        $this->httpClient->shouldReceive('get')
            ->with('https://example.com/feed.atom')
            ->andReturn($atomFeed);

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://example.com/feed.atom',
            type: 'atom'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(2)
            ->and($result->items[0]['title'])->toBe('Atom Item 1')
            ->and($result->items[0]['url'])->toBe('https://example.com/atom/1')
            ->and($result->items[0]['description'])->toBe('Atom description 1');
    });

    it('detects and parses json feed', function () {
        $jsonFeed = json_encode([
            'version' => 'https://jsonfeed.org/version/1',
            'title' => 'Test JSON Feed',
            'items' => [
                [
                    'id' => '1',
                    'title' => 'JSON Item 1',
                    'url' => 'https://example.com/json/1',
                    'content_text' => 'JSON content 1',
                    'date_published' => '2024-01-01T00:00:00Z',
                ],
                [
                    'id' => '2',
                    'title' => 'JSON Item 2',
                    'url' => 'https://example.com/json/2',
                    'content_html' => '<p>JSON content 2</p>',
                ],
            ],
        ]);

        $this->httpClient->shouldReceive('get')
            ->with('https://example.com/feed.json')
            ->andReturn($jsonFeed);

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://example.com/feed.json',
            type: 'json'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(2)
            ->and($result->items[0]['title'])->toBe('JSON Item 1')
            ->and($result->items[0]['url'])->toBe('https://example.com/json/1')
            ->and($result->items[0]['description'])->toBe('JSON content 1');
    });

    it('auto-detects feed type from content', function () {
        $rssFeed = '<?xml version="1.0"?>
<rss version="2.0">
    <channel>
        <item>
            <title>Auto-detected RSS</title>
            <link>https://example.com/1</link>
        </item>
    </channel>
</rss>';

        $this->httpClient->shouldReceive('get')
            ->andReturn($rssFeed);

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://example.com/feed',
            type: 'auto' // Auto-detect
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(1)
            ->and($result->metadata['detected_type'])->toBe('rss');
    });

    it('extracts images from feed content', function () {
        $rssFeed = '<?xml version="1.0"?>
<rss version="2.0">
    <channel>
        <item>
            <title>Item with image</title>
            <link>https://example.com/1</link>
            <description><![CDATA[<img src="https://example.com/image.jpg">]]></description>
        </item>
    </channel>
</rss>';

        $this->httpClient->shouldReceive('get')
            ->andReturn($rssFeed);

        $this->contentExtractor->shouldReceive('extractImages')
            ->with(Mockery::type('string'))
            ->andReturn(['https://example.com/image.jpg']);

        $request = new ParseRequestDTO(
            source: 'https://example.com/feed',
            type: 'rss'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0])->toHaveKey('images')
            ->and($result->items[0]['images'])->toBe(['https://example.com/image.jpg']);
    });

    it('handles invalid xml gracefully', function () {
        $this->httpClient->shouldReceive('get')
            ->andReturn('invalid xml content <><');

        $request = new ParseRequestDTO(
            source: 'https://example.com/feed',
            type: 'rss'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeFalse()
            ->and($result->error)->toContain('parse')
            ->and($result->items)->toBe([]);
    });

    it('handles invalid json gracefully', function () {
        $this->httpClient->shouldReceive('get')
            ->andReturn('invalid json {{{');

        $request = new ParseRequestDTO(
            source: 'https://example.com/feed.json',
            type: 'json'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeFalse()
            ->and($result->error)->not->toBeNull()
            ->and($result->items)->toBe([]);
    });

    it('respects limit option from request', function () {
        $rssFeed = '<?xml version="1.0"?>
<rss version="2.0">
    <channel>
        <item><title>Item 1</title><link>https://example.com/1</link></item>
        <item><title>Item 2</title><link>https://example.com/2</link></item>
        <item><title>Item 3</title><link>https://example.com/3</link></item>
        <item><title>Item 4</title><link>https://example.com/4</link></item>
    </channel>
</rss>';

        $this->httpClient->shouldReceive('get')
            ->andReturn($rssFeed);

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://example.com/feed',
            type: 'rss',
            limit: 2
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(2)
            ->and($result->total)->toBe(4);
    });

    it('parses rss with enclosure media', function () {
        $rssFeed = '<?xml version="1.0"?>
<rss version="2.0">
    <channel>
        <item>
            <title>Item with media</title>
            <link>https://example.com/1</link>
            <enclosure url="https://example.com/audio.mp3" type="audio/mpeg" length="12345"/>
        </item>
    </channel>
</rss>';

        $this->httpClient->shouldReceive('get')
            ->andReturn($rssFeed);

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://example.com/feed',
            type: 'rss'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0])->toHaveKey('enclosure')
            ->and($result->items[0]['enclosure'])->toBe('https://example.com/audio.mp3');
    });

    it('handles empty feed gracefully', function () {
        $emptyFeed = '<?xml version="1.0"?>
<rss version="2.0">
    <channel>
        <title>Empty Feed</title>
    </channel>
</rss>';

        $this->httpClient->shouldReceive('get')
            ->andReturn($emptyFeed);

        $request = new ParseRequestDTO(
            source: 'https://example.com/feed',
            type: 'rss'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toBe([])
            ->and($result->total)->toBe(0);
    });
});
