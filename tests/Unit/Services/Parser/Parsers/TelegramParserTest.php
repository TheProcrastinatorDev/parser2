<?php

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\Parsers\TelegramParser;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\RateLimiter;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['parser.telegram' => [
        'enabled' => true,
        'rate_limit' => ['requests' => 20, 'window' => 60],
    ]]);
});

it('can parse public channel messages', function () {
    $html = '<html><body>
        <div class="tgme_widget_message">
            <div class="tgme_widget_message_text">Test message</div>
            <div class="tgme_widget_message_date">2025-01-01 12:00</div>
        </div>
    </body></html>';
    
    Http::fake(['t.me/*' => Http::response($html, 200)]);

    $parser = new TelegramParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://t.me/s/testchannel',
        'type' => 'channel',
    ]);

    $result = $parser->parse($request);

    expect($result->success)->toBeTrue()
        ->and($result->items)->not->toBeEmpty();
});

it('can detect message types', function () {
    $html = '<html><body>
        <div class="tgme_widget_message">
            <div class="tgme_widget_message_text">Text message</div>
        </div>
        <div class="tgme_widget_message">
            <a class="tgme_widget_message_photo_wrap" href="https://cdn4.telegram-cdn.org/photo.jpg"></a>
        </div>
        <div class="tgme_widget_message">
            <a class="tgme_widget_message_video_wrap" href="https://cdn4.telegram-cdn.org/video.mp4"></a>
        </div>
    </body></html>';
    
    Http::fake(['t.me/*' => Http::response($html, 200)]);

    $parser = new TelegramParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://t.me/s/testchannel',
        'type' => 'channel',
    ]);

    $result = $parser->parse($request);

    expect($result->items)->toHaveCount(3)
        ->and($result->items[0]['metadata']['message_type'])->toBe('text')
        ->and($result->items[1]['metadata']['message_type'])->toBe('photo')
        ->and($result->items[2]['metadata']['message_type'])->toBe('video');
});

it('can normalize t.me URLs', function () {
    $html = '<html><body><div class="tgme_widget_message">Test</div></body></html>';
    Http::fake(['t.me/*' => Http::response($html, 200)]);

    $parser = new TelegramParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    // Test both formats
    $request1 = ParseRequestDTO::fromArray([
        'source' => 'https://t.me/channel',
        'type' => 'channel',
    ]);

    $result1 = $parser->parse($request1);
    expect($result1->success)->toBeTrue();

    $request2 = ParseRequestDTO::fromArray([
        'source' => 'https://t.me/s/channel',
        'type' => 'channel',
    ]);

    $result2 = $parser->parse($request2);
    expect($result2->success)->toBeTrue();
});

it('can extract media thumbnails', function () {
    $html = '<html><body>
        <div class="tgme_widget_message">
            <a class="tgme_widget_message_photo_wrap" href="https://cdn4.telegram-cdn.org/photo.jpg">
                <i class="tgme_widget_message_photo" style="background-image: url(\'https://cdn4.telegram-cdn.org/thumb.jpg\')"></i>
            </a>
        </div>
    </body></html>';
    
    Http::fake(['t.me/*' => Http::response($html, 200)]);

    $parser = new TelegramParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://t.me/s/testchannel',
        'type' => 'channel',
    ]);

    $result = $parser->parse($request);

    expect($result->items[0]['images'])->not->toBeEmpty();
});

it('can extract view counts', function () {
    $html = '<html><body>
        <div class="tgme_widget_message">
            <div class="tgme_widget_message_text">Test</div>
            <span class="tgme_widget_message_views">1.2K views</span>
        </div>
    </body></html>';
    
    Http::fake(['t.me/*' => Http::response($html, 200)]);

    $parser = new TelegramParser(
        app(HttpClient::class),
        app(ContentExtractor::class),
        app(RateLimiter::class)
    );

    $request = ParseRequestDTO::fromArray([
        'source' => 'https://t.me/s/testchannel',
        'type' => 'channel',
    ]);

    $result = $parser->parse($request);

    expect($result->items[0]['metadata']['views'])->not->toBeNull();
});
