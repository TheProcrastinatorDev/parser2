<?php

namespace App\Services\Parser\Parsers;

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\AbstractParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\RateLimiter;
use DOMDocument;
use DOMXPath;
use Exception;

class TelegramParser extends AbstractParser
{
    protected string $name = 'telegram';
    protected array $supportedTypes = ['channel'];

    protected function parseInternal(ParseRequestDTO $request): array
    {
        $url = $this->normalizeTelegramUrl($request->source);
        $response = $this->httpClient->get($url, [
            'timeout' => $request->options['timeout'] ?? 30,
        ]);

        if (!$response->successful()) {
            throw new Exception("Failed to fetch Telegram channel: HTTP {$response->status()}");
        }

        $html = $response->body();
        return $this->parseTelegramHtml($html, $url);
    }

    private function normalizeTelegramUrl(string $url): string
    {
        // Convert t.me/channel to t.me/s/channel format
        $url = rtrim($url, '/');
        if (preg_match('#https?://t\.me/([^/]+)$#', $url, $matches)) {
            return "https://t.me/s/{$matches[1]}";
        }
        return $url;
    }

    private function parseTelegramHtml(string $html, string $baseUrl): array
    {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);

        $items = [];
        $messageNodes = $xpath->query('//div[contains(@class, "tgme_widget_message")]');

        foreach ($messageNodes as $messageNode) {
            $textNode = $xpath->query('.//div[contains(@class, "tgme_widget_message_text")]', $messageNode);
            $text = $textNode->length > 0 ? trim($textNode->item(0)->textContent) : '';

            $dateNode = $xpath->query('.//time[@datetime]', $messageNode);
            $date = $dateNode->length > 0 ? $dateNode->item(0)->getAttribute('datetime') : null;

            $linkNode = $xpath->query('.//a[contains(@class, "tgme_widget_message_date")]', $messageNode);
            $messageUrl = $linkNode->length > 0 ? $linkNode->item(0)->getAttribute('href') : '';

            $messageType = $this->detectMessageType($xpath, $messageNode);
            $images = $this->extractImages($xpath, $messageNode, $baseUrl);
            $views = $this->extractViews($xpath, $messageNode);

            $items[] = [
                'title' => $this->extractTitle($text),
                'url' => $messageUrl ?: $baseUrl,
                'content' => $text,
                'author' => $this->extractChannelName($baseUrl),
                'published_at' => $date,
                'tags' => [],
                'images' => $images,
                'metadata' => [
                    'message_type' => $messageType,
                    'views' => $views,
                    'channel' => $this->extractChannelName($baseUrl),
                ],
            ];
        }

        return $items;
    }

    private function detectMessageType(DOMXPath $xpath, $messageNode): string
    {
        // Check for photo
        if ($xpath->query('.//a[contains(@class, "tgme_widget_message_photo_wrap")]', $messageNode)->length > 0) {
            return 'photo';
        }

        // Check for video
        if ($xpath->query('.//a[contains(@class, "tgme_widget_message_video_wrap")]', $messageNode)->length > 0) {
            return 'video';
        }

        // Check for link
        if ($xpath->query('.//a[contains(@class, "tgme_widget_message_link")]', $messageNode)->length > 0) {
            return 'link';
        }

        // Check for YouTube link
        $textNode = $xpath->query('.//div[contains(@class, "tgme_widget_message_text")]', $messageNode);
        if ($textNode->length > 0) {
            $text = $textNode->item(0)->textContent;
            if (preg_match('#(youtube\.com|youtu\.be)#i', $text)) {
                return 'youtube';
            }
        }

        return 'text';
    }

    private function extractImages(DOMXPath $xpath, $messageNode, string $baseUrl): array
    {
        $images = [];

        // Extract photo URLs
        $photoNodes = $xpath->query('.//a[contains(@class, "tgme_widget_message_photo_wrap")]', $messageNode);
        foreach ($photoNodes as $photoNode) {
            $href = $photoNode->getAttribute('href');
            if ($href) {
                $images[] = $href;
            }

            // Also check for background-image in style
            $styleNodes = $xpath->query('.//i[contains(@class, "tgme_widget_message_photo")]', $photoNode);
            foreach ($styleNodes as $styleNode) {
                $style = $styleNode->getAttribute('style');
                if (preg_match('/background-image:\s*url\([\'"]?([^\'"]+)[\'"]?\)/i', $style, $matches)) {
                    $images[] = $matches[1];
                }
            }
        }

        return array_unique($images);
    }

    private function extractViews(DOMXPath $xpath, $messageNode): ?int
    {
        $viewsNode = $xpath->query('.//span[contains(@class, "tgme_widget_message_views")]', $messageNode);
        if ($viewsNode->length === 0) {
            return null;
        }

        $viewsText = trim($viewsNode->item(0)->textContent);
        if (preg_match('/([\d.]+)\s*([KM]?)\s*views?/i', $viewsText, $matches)) {
            $number = (float) $matches[1];
            $multiplier = strtoupper($matches[2] ?? '');
            
            return match ($multiplier) {
                'K' => (int) ($number * 1000),
                'M' => (int) ($number * 1000000),
                default => (int) $number,
            };
        }

        return null;
    }

    private function extractTitle(string $text): string
    {
        // Use first line or first 100 characters as title
        $lines = explode("\n", $text);
        $firstLine = trim($lines[0] ?? '');
        
        if (strlen($firstLine) > 100) {
            return substr($firstLine, 0, 97) . '...';
        }

        return $firstLine ?: 'Telegram Message';
    }

    private function extractChannelName(string $url): string
    {
        if (preg_match('#t\.me/s?/([^/?#]+)#', $url, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
