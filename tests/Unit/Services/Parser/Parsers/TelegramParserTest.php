<?php

declare(strict_types=1);

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\TelegramParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;

uses(Tests\TestCase::class);

describe('TelegramParser', function () {
    beforeEach(function () {
        $this->httpClient = Mockery::mock(HttpClient::class);
        $this->contentExtractor = Mockery::mock(ContentExtractor::class);
        $this->parser = new TelegramParser($this->httpClient, $this->contentExtractor);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('parses telegram channel messages', function () {
        $html = '
            <html>
                <body>
                    <div class="tgme_widget_message_wrap">
                        <div class="tgme_widget_message" data-post="testchannel/100">
                            <div class="tgme_widget_message_author">Test Channel</div>
                            <div class="tgme_widget_message_text">Test message content</div>
                            <time datetime="2025-01-15T10:00:00+00:00">Jan 15 at 10:00</time>
                            <span class="tgme_widget_message_views">1.5K</span>
                        </div>
                    </div>
                </body>
            </html>
        ';

        $this->httpClient->shouldReceive('get')
            ->with('https://t.me/s/testchannel')
            ->andReturn($html);

        $this->contentExtractor->shouldReceive('extractText')
            ->with(Mockery::type('string'))
            ->andReturn('Test message content');

        $this->contentExtractor->shouldReceive('extractImages')
            ->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://t.me/s/testchannel',
            type: 'telegram'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(1)
            ->and($result->items[0]['content'])->toBe('Test message content')
            ->and($result->items[0]['author'])->toBe('Test Channel');
    });

    it('extracts message metadata correctly', function () {
        $html = '
            <div class="tgme_widget_message" data-post="channel/123">
                <div class="tgme_widget_message_author">Author Name</div>
                <div class="tgme_widget_message_text">Message text</div>
                <time datetime="2025-01-15T12:30:00+00:00">Jan 15</time>
                <span class="tgme_widget_message_views">2.3K</span>
            </div>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('extractText')->andReturn('Message text');
        $this->contentExtractor->shouldReceive('extractImages')->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://t.me/s/channel',
            type: 'telegram'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['message_id'])->toBe('123')
            ->and($result->items[0]['views'])->toBe('2.3K')
            ->and($result->items[0]['created_at'])->toBe('2025-01-15T12:30:00+00:00');
    });

    it('extracts images from telegram messages', function () {
        $html = '
            <div class="tgme_widget_message">
                <div class="tgme_widget_message_photo">
                    <a style="background-image:url(\'https://cdn.telegram.org/image.jpg\')"></a>
                </div>
                <div class="tgme_widget_message_text">Caption</div>
            </div>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('extractText')->andReturn('Caption');
        $this->contentExtractor->shouldReceive('extractImages')
            ->with(Mockery::type('string'))
            ->andReturn(['https://cdn.telegram.org/image.jpg']);

        $request = new ParseRequestDTO(
            source: 'https://t.me/s/channel',
            type: 'telegram'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['images'])->toBe(['https://cdn.telegram.org/image.jpg']);
    });

    it('detects video messages', function () {
        $html = '
            <div class="tgme_widget_message">
                <div class="tgme_widget_message_video">
                    <video src="https://cdn.telegram.org/video.mp4"></video>
                </div>
                <div class="tgme_widget_message_text">Video caption</div>
            </div>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('extractText')->andReturn('Video caption');
        $this->contentExtractor->shouldReceive('extractImages')->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://t.me/s/channel',
            type: 'telegram'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['type'])->toBe('video')
            ->and($result->items[0]['video_url'])->toContain('video.mp4');
    });

    it('handles forwarded messages', function () {
        $html = '
            <div class="tgme_widget_message">
                <div class="tgme_widget_message_forwarded_from">
                    Forwarded from <a href="https://t.me/original">Original Channel</a>
                </div>
                <div class="tgme_widget_message_text">Forwarded content</div>
            </div>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('extractText')->andReturn('Forwarded content');
        $this->contentExtractor->shouldReceive('extractImages')->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://t.me/s/channel',
            type: 'telegram'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['forwarded'])->toBeTrue()
            ->and($result->items[0]['forwarded_from'])->toContain('Original Channel');
    });

    it('handles reply messages', function () {
        $html = '
            <div class="tgme_widget_message">
                <div class="tgme_widget_message_reply">
                    <div class="tgme_widget_message_author">Replied Author</div>
                    <div class="tgme_widget_message_text">Original message</div>
                </div>
                <div class="tgme_widget_message_text">Reply content</div>
            </div>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('extractText')->andReturn('Reply content');
        $this->contentExtractor->shouldReceive('extractImages')->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://t.me/s/channel',
            type: 'telegram'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items[0]['is_reply'])->toBeTrue()
            ->and($result->items[0]['reply_to_author'])->toBe('Replied Author');
    });

    it('parses multiple messages from channel', function () {
        $html = '
            <div class="tgme_widget_message_wrap">
                <div class="tgme_widget_message">
                    <div class="tgme_widget_message_text">Message 1</div>
                </div>
            </div>
            <div class="tgme_widget_message_wrap">
                <div class="tgme_widget_message">
                    <div class="tgme_widget_message_text">Message 2</div>
                </div>
            </div>
        ';

        $this->httpClient->shouldReceive('get')->andReturn($html);
        $this->contentExtractor->shouldReceive('extractText')
            ->andReturn('Message 1', 'Message 2');
        $this->contentExtractor->shouldReceive('extractImages')->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'https://t.me/s/channel',
            type: 'telegram'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toHaveCount(2)
            ->and($result->items[0]['content'])->toBe('Message 1')
            ->and($result->items[1]['content'])->toBe('Message 2');
    });

    it('handles channels with no messages', function () {
        $html = '<html><body><div class="tgme_channel_info">Channel Info</div></body></html>';

        $this->httpClient->shouldReceive('get')->andReturn($html);

        $request = new ParseRequestDTO(
            source: 'https://t.me/s/emptychannel',
            type: 'telegram'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue()
            ->and($result->items)->toBeArray()
            ->and($result->items)->toHaveCount(0);
    });

    it('handles http errors gracefully', function () {
        $this->httpClient->shouldReceive('get')
            ->andThrow(new Exception('Channel not found'));

        $request = new ParseRequestDTO(
            source: 'https://t.me/s/nonexistent',
            type: 'telegram'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeFalse()
            ->and($result->error)->toContain('Channel not found');
    });

    it('builds correct telegram url from channel name', function () {
        $html = '<div class="tgme_widget_message"><div class="tgme_widget_message_text">Test</div></div>';

        $this->httpClient->shouldReceive('get')
            ->with('https://t.me/s/testchannel')
            ->andReturn($html);

        $this->contentExtractor->shouldReceive('extractText')->andReturn('Test');
        $this->contentExtractor->shouldReceive('extractImages')->andReturn([]);

        $request = new ParseRequestDTO(
            source: 'testchannel', // Just channel name
            type: 'telegram'
        );

        $result = $this->parser->parse($request);

        expect($result->success)->toBeTrue();
    });
});
