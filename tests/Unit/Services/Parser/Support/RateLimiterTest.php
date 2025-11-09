<?php

declare(strict_types=1);

use App\Services\Parser\Support\RateLimiter;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

describe('RateLimiter', function () {
    beforeEach(function () {
        Cache::flush();
        $this->limiter = new RateLimiter;
    });

    it('allows requests within per-minute limit', function () {
        $allowed = $this->limiter->attempt('test_parser', 60, 1000);

        expect($allowed)->toBeTrue();
    });

    it('blocks requests exceeding per-minute limit', function () {
        // Set very low limit
        $limit = 2;

        // First two requests should succeed
        expect($this->limiter->attempt('test_parser', $limit, 1000))->toBeTrue();
        expect($this->limiter->attempt('test_parser', $limit, 1000))->toBeTrue();

        // Third request should be blocked
        expect($this->limiter->attempt('test_parser', $limit, 1000))->toBeFalse();
    });

    it('allows requests within per-hour limit', function () {
        $allowed = $this->limiter->attempt('test_parser', 60, 1000);

        expect($allowed)->toBeTrue();
    });

    it('blocks requests exceeding per-hour limit', function () {
        // Set very low hour limit
        $perMinute = 60;
        $perHour = 2;

        // First two requests should succeed
        expect($this->limiter->attempt('test_parser', $perMinute, $perHour))->toBeTrue();
        expect($this->limiter->attempt('test_parser', $perMinute, $perHour))->toBeTrue();

        // Third request should be blocked by hour limit
        expect($this->limiter->attempt('test_parser', $perMinute, $perHour))->toBeFalse();
    });

    it('maintains separate limits per parser', function () {
        $limit = 2;

        // Use up limit for parser1
        $this->limiter->attempt('parser1', $limit, 1000);
        $this->limiter->attempt('parser1', $limit, 1000);

        // parser1 should be blocked
        expect($this->limiter->attempt('parser1', $limit, 1000))->toBeFalse();

        // parser2 should still be allowed
        expect($this->limiter->attempt('parser2', $limit, 1000))->toBeTrue();
    });

    it('returns remaining attempts', function () {
        $limit = 5;

        $this->limiter->attempt('test_parser', $limit, 1000);
        $remaining = $this->limiter->remaining('test_parser', $limit, 1000);

        expect($remaining)->toBe(4);
    });

    it('returns correct remaining after multiple attempts', function () {
        $limit = 10;

        $this->limiter->attempt('test_parser', $limit, 1000);
        $this->limiter->attempt('test_parser', $limit, 1000);
        $this->limiter->attempt('test_parser', $limit, 1000);

        $remaining = $this->limiter->remaining('test_parser', $limit, 1000);

        expect($remaining)->toBe(7);
    });

    it('returns zero remaining when limit exceeded', function () {
        $limit = 2;

        $this->limiter->attempt('test_parser', $limit, 1000);
        $this->limiter->attempt('test_parser', $limit, 1000);

        $remaining = $this->limiter->remaining('test_parser', $limit, 1000);

        expect($remaining)->toBe(0);
    });

    it('resets minute limit after time window', function () {
        $limit = 2;

        // Use up the limit
        $this->limiter->attempt('test_parser', $limit, 1000);
        $this->limiter->attempt('test_parser', $limit, 1000);

        expect($this->limiter->attempt('test_parser', $limit, 1000))->toBeFalse();

        // Clear cache to simulate time passing
        Cache::forget('rate_limit:test_parser:minute');

        // Should now be allowed again
        expect($this->limiter->attempt('test_parser', $limit, 1000))->toBeTrue();
    });

    it('resets hour limit after time window', function () {
        $perMinute = 60;
        $perHour = 2;

        // Use up the hour limit
        $this->limiter->attempt('test_parser', $perMinute, $perHour);
        $this->limiter->attempt('test_parser', $perMinute, $perHour);

        expect($this->limiter->attempt('test_parser', $perMinute, $perHour))->toBeFalse();

        // Clear cache to simulate time passing
        Cache::forget('rate_limit:test_parser:hour');

        // Should now be allowed again
        expect($this->limiter->attempt('test_parser', $perMinute, $perHour))->toBeTrue();
    });

    it('returns time until reset for minute limit', function () {
        $limit = 2;

        $this->limiter->attempt('test_parser', $limit, 1000);
        $this->limiter->attempt('test_parser', $limit, 1000);

        $availableIn = $this->limiter->availableIn('test_parser');

        // Should return seconds until minute resets (approximately 60 seconds)
        expect($availableIn)->toBeGreaterThan(0)
            ->and($availableIn)->toBeLessThanOrEqual(60);
    });

    it('clears rate limit for specific parser', function () {
        $limit = 2;

        // Use up the limit
        $this->limiter->attempt('test_parser', $limit, 1000);
        $this->limiter->attempt('test_parser', $limit, 1000);

        expect($this->limiter->attempt('test_parser', $limit, 1000))->toBeFalse();

        // Clear the limit
        $this->limiter->clear('test_parser');

        // Should now be allowed
        expect($this->limiter->attempt('test_parser', $limit, 1000))->toBeTrue();
    });

    it('tracks hit count correctly', function () {
        $limit = 10;

        $this->limiter->attempt('test_parser', $limit, 1000);
        $this->limiter->attempt('test_parser', $limit, 1000);
        $this->limiter->attempt('test_parser', $limit, 1000);

        $hits = $this->limiter->hits('test_parser');

        expect($hits)->toBe(3);
    });
});
