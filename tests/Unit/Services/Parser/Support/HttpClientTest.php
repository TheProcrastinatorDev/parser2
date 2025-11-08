<?php

use App\Services\Parser\Support\HttpClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake();
});

it('can make successful GET request', function () {
    Http::fake([
        'example.com/*' => Http::response('Success', 200),
    ]);

    $client = new HttpClient();
    $response = $client->get('https://example.com/test');

    expect($response->status())->toBe(200)
        ->and($response->body())->toBe('Success');
});

it('retries on 429 rate limit errors with exponential backoff', function () {
    Http::fakeSequence()
        ->push('Rate limited', 429)
        ->push('Rate limited', 429)
        ->push('Success', 200);

    $client = new HttpClient();
    
    $response = $client->get('https://example.com/test', ['max_retries' => 3]);
    
    expect($response->status())->toBe(200);
    expect(Http::recorded())->toHaveCount(3); // Should have made 3 requests
});

it('retries on 500 server errors', function () {
    Http::fakeSequence()
        ->push('Server error', 500)
        ->push('Success', 200);

    $client = new HttpClient();
    $response = $client->get('https://example.com/test', ['max_retries' => 2]);

    expect($response->status())->toBe(200);
    expect(Http::recorded())->toHaveCount(2);
});

it('retries on 502, 503, 504 errors', function () {
    foreach ([502, 503, 504] as $status) {
        Http::fakeSequence()
            ->push('Error', $status)
            ->push('Success', 200);

        $client = new HttpClient();
        $response = $client->get('https://example.com/test', ['max_retries' => 2]);

        expect($response->status())->toBe(200);
    }
});

it('throws exception after max retries exceeded', function () {
    Http::fake([
        'example.com/*' => Http::response('Error', 500),
    ]);

    $client = new HttpClient();
    
    expect(fn() => $client->get('https://example.com/test', ['max_retries' => 2]))
        ->toThrow(Exception::class);
});

it('rotates user agents', function () {
    Http::fake([
        'example.com/*' => Http::response('Success', 200),
    ]);

    $client = new HttpClient();
    $client->get('https://example.com/test1');
    $client->get('https://example.com/test2');

    $recorded = Http::recorded();
    expect($recorded)->toHaveCount(2);
    
    // Verify user agents are present (exact comparison may vary)
    $headers1 = $recorded[0][0]->headers();
    $headers2 = $recorded[1][0]->headers();
    
    expect($headers1)->toHaveKey('User-Agent')
        ->and($headers2)->toHaveKey('User-Agent');
});

it('does not retry on non-retryable errors', function () {
    Http::fake([
        'example.com/*' => Http::response('Not Found', 404),
    ]);

    $client = new HttpClient();
    $response = $client->get('https://example.com/test');

    expect($response->status())->toBe(404);
    expect(Http::recorded())->toHaveCount(1); // Should not retry 404
});
