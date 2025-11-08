<?php

namespace App\Services\Parser\Support;

use Illuminate\Support\Facades\Http;
use Exception;

class HttpClient
{
    private array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36',
    ];

    private int $currentUserAgentIndex = 0;

    private array $retryableStatuses = [429, 500, 502, 503, 504];

    public function get(string $url, array $options = []): \Illuminate\Http\Client\Response
    {
        $maxRetries = $options['max_retries'] ?? 3;
        $timeout = $options['timeout'] ?? 30;
        $attempt = 0;

        while ($attempt <= $maxRetries) {
            try {
                $response = Http::timeout($timeout)
                    ->withHeaders([
                        'User-Agent' => $this->getNextUserAgent(),
                    ])
                    ->get($url);

                // If successful or non-retryable error, return immediately
                if ($response->successful() || !in_array($response->status(), $this->retryableStatuses)) {
                    return $response;
                }

                // If retryable error and we have retries left, wait and retry
                if ($attempt < $maxRetries) {
                    $waitTime = pow(2, $attempt); // Exponential backoff
                    usleep($waitTime * 1000000); // Convert to microseconds
                    $attempt++;
                    continue;
                }

                // Max retries exceeded
                throw new Exception("HTTP request failed after {$maxRetries} retries. Status: {$response->status()}");
            } catch (Exception $e) {
                if ($attempt >= $maxRetries) {
                    throw $e;
                }
                $waitTime = pow(2, $attempt);
                usleep($waitTime * 1000000);
                $attempt++;
            }
        }

        throw new Exception("HTTP request failed after {$maxRetries} retries");
    }

    private function getNextUserAgent(): string
    {
        $userAgent = $this->userAgents[$this->currentUserAgentIndex];
        $this->currentUserAgentIndex = ($this->currentUserAgentIndex + 1) % count($this->userAgents);
        return $userAgent;
    }
}
