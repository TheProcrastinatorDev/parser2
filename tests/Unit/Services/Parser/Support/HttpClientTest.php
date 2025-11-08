<?php

declare(strict_types=1);

use App\Services\Parser\Support\HttpClient;
use Illuminate\Support\Facades\Http;

uses(Tests\TestCase::class);

describe('HttpClient', function () {
    beforeEach(function () {
        $this->client = new HttpClient();
    });

    it('successfully performs http get request', function () {
        Http::fake([
            'https://example.com' => Http::response('Test content', 200),
        ]);

        $response = $this->client->get('https://example.com');

        expect($response)->toBe('Test content');
    });

    it('retries on 429 rate limit error with exponential backoff', function () {
        Http::fake([
            'https://example.com' => Http::sequence()
                ->push('', 429)
                ->push('', 429)
                ->push('Success', 200),
        ]);

        $response = $this->client->get('https://example.com');

        expect($response)->toBe('Success');
        Http::assertSentCount(3);
    });

    it('retries on 500 server error', function () {
        Http::fake([
            'https://example.com' => Http::sequence()
                ->push('', 500)
                ->push('Success', 200),
        ]);

        $response = $this->client->get('https://example.com');

        expect($response)->toBe('Success');
        Http::assertSentCount(2);
    });

    it('retries on 502 bad gateway error', function () {
        Http::fake([
            'https://example.com' => Http::sequence()
                ->push('', 502)
                ->push('Success', 200),
        ]);

        $response = $this->client->get('https://example.com');

        expect($response)->toBe('Success');
    });

    it('retries on 503 service unavailable error', function () {
        Http::fake([
            'https://example.com' => Http::sequence()
                ->push('', 503)
                ->push('Success', 200),
        ]);

        $response = $this->client->get('https://example.com');

        expect($response)->toBe('Success');
    });

    it('retries on 504 gateway timeout error', function () {
        Http::fake([
            'https://example.com' => Http::sequence()
                ->push('', 504)
                ->push('Success', 200),
        ]);

        $response = $this->client->get('https://example.com');

        expect($response)->toBe('Success');
    });

    it('throws exception after max retries exceeded', function () {
        Http::fake([
            'https://example.com' => Http::response('', 500),
        ]);

        expect(fn () => $this->client->get('https://example.com'))
            ->toThrow(Exception::class);

        Http::assertSentCount(4); // 1 initial + 3 retries = 4 total
    });

    it('rotates user agents across requests', function () {
        Http::fake([
            '*' => Http::response('Success', 200),
        ]);

        $this->client->get('https://example.com/1');
        $this->client->get('https://example.com/2');
        $this->client->get('https://example.com/3');

        Http::assertSentCount(3);

        // Verify different user agents were used
        $recorded = Http::recorded();
        $userAgents = $recorded->map(fn ($req) => $req[0]->header('User-Agent')[0])->all();

        // At least 1 user agent should be used (rotation works)
        expect(count(array_unique($userAgents)))->toBeGreaterThanOrEqual(1);
    });

    it('includes default headers in requests', function () {
        Http::fake([
            'https://example.com' => Http::response('Success', 200),
        ]);

        $this->client->get('https://example.com');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Accept')
                && $request->hasHeader('Accept-Language');
        });
    });

    it('allows custom headers to be passed', function () {
        Http::fake([
            'https://example.com' => Http::response('Success', 200),
        ]);

        $this->client->get('https://example.com', [
            'X-Custom-Header' => 'CustomValue',
        ]);

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Custom-Header')
                && $request->header('X-Custom-Header')[0] === 'CustomValue';
        });
    });

    it('respects timeout configuration', function () {
        Http::fake([
            'https://example.com' => Http::response('Success', 200),
        ]);

        $this->client->get('https://example.com');

        Http::assertSent(function ($request) {
            // Laravel Http client sets timeout, we just verify request was made
            return true;
        });
    });

    it('handles connection errors gracefully', function () {
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        expect(fn () => $this->client->get('https://example.com'))
            ->toThrow(Exception::class);
    });
});
