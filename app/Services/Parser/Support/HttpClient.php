<?php

declare(strict_types=1);

namespace App\Services\Parser\Support;

use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class HttpClient
{
    private int $userAgentIndex = 0;

    private array $retryableStatusCodes = [429, 500, 502, 503, 504];

    /**
     * Perform HTTP GET request with retry logic and user-agent rotation.
     *
     * @param  array<string, string>  $headers
     *
     * @throws Exception
     */
    public function get(string $url, array $headers = []): string
    {
        return $this->request('GET', $url, $headers);
    }

    /**
     * Perform HTTP request with retry logic.
     *
     * @param  array<string, string>  $headers
     *
     * @throws Exception
     */
    private function request(string $method, string $url, array $headers = [], int $attempt = 0): string
    {
        $maxRetries = Config::get('parser.http.retry.max_attempts', 3);
        $timeout = Config::get('parser.http.timeout', 30);

        // Merge headers with defaults and add rotated user-agent
        $allHeaders = array_merge(
            Config::get('parser.http.headers', []),
            [
                'User-Agent' => $this->getRotatedUserAgent(),
            ],
            $headers
        );

        try {
            $response = Http::withHeaders($allHeaders)
                ->timeout($timeout)
                ->$method($url);

            // Check if we should retry based on status code
            if (in_array($response->status(), $this->retryableStatusCodes)) {
                if ($attempt < $maxRetries) {
                    $this->sleep($attempt);

                    return $this->request($method, $url, $headers, $attempt + 1);
                }

                throw new Exception("HTTP request failed after {$maxRetries} retries with status: {$response->status()}");
            }

            // Return successful response body
            return $response->body();
        } catch (ConnectionException $e) {
            if ($attempt < $maxRetries) {
                $this->sleep($attempt);

                return $this->request($method, $url, $headers, $attempt + 1);
            }

            throw new Exception("HTTP connection failed after {$maxRetries} retries: {$e->getMessage()}");
        }
    }

    /**
     * Get next user agent from rotation.
     */
    private function getRotatedUserAgent(): string
    {
        $userAgents = Config::get('parser.http.user_agents', [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ]);

        $userAgent = $userAgents[$this->userAgentIndex % count($userAgents)];
        $this->userAgentIndex++;

        return $userAgent;
    }

    /**
     * Sleep with exponential backoff.
     */
    private function sleep(int $attempt): void
    {
        $baseDelay = Config::get('parser.http.retry.delay', 1000); // milliseconds
        $multiplier = Config::get('parser.http.retry.multiplier', 2);

        $delay = $baseDelay * pow($multiplier, $attempt);

        // Convert milliseconds to microseconds for usleep
        usleep((int) ($delay * 1000));
    }
}
