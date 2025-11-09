<?php

declare(strict_types=1);

namespace App\Services\Parser\Parsers;

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\AbstractParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;
use DOMDocument;
use DOMXPath;

class TelegramParser extends AbstractParser
{
    public function __construct(
        private readonly HttpClient $httpClient,
        private readonly ContentExtractor $contentExtractor,
    ) {}

    /**
     * Parse Telegram channel messages.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function doParse(ParseRequestDTO $request): array
    {
        // Build Telegram URL
        $url = $this->buildTelegramUrl($request->source);

        // Fetch channel HTML
        $html = $this->httpClient->get($url);

        // Parse messages from HTML
        return $this->extractMessages($html);
    }

    /**
     * Build Telegram URL from source.
     */
    private function buildTelegramUrl(string $source): string
    {
        // If already a full URL, return as-is
        if (str_starts_with($source, 'http://') || str_starts_with($source, 'https://')) {
            return $source;
        }

        // Build URL from channel name
        return 'https://t.me/s/'.$source;
    }

    /**
     * Extract messages from Telegram HTML.
     *
     * @return array<int, array<string, mixed>>
     */
    private function extractMessages(string $html): array
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument;
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);

        // Find all message containers (exclude nested message-related divs)
        $messageNodes = $xpath->query('//div[contains(concat(" ", @class, " "), " tgme_widget_message ") and not(contains(@class, "tgme_widget_message_"))]');

        if (! $messageNodes || $messageNodes->length === 0) {
            libxml_clear_errors();

            return [];
        }

        $items = [];

        foreach ($messageNodes as $messageNode) {
            $messageHtml = $dom->saveHTML($messageNode);

            $item = [
                'url' => $this->extractMessageUrl($messageNode, $xpath),
                'message_id' => $this->extractMessageId($messageNode),
                'author' => $this->extractAuthor($messageNode, $xpath),
                'content' => $this->extractContent($messageNode, $xpath),
                'html' => $messageHtml,
                'created_at' => $this->extractTimestamp($messageNode, $xpath),
                'views' => $this->extractViews($messageNode, $xpath),
                'type' => $this->detectMessageType($messageNode, $xpath),
                'images' => $this->contentExtractor->extractImages($messageHtml),
                'video_url' => $this->extractVideoUrl($messageNode, $xpath),
                'forwarded' => $this->isForwarded($messageNode, $xpath),
                'forwarded_from' => $this->extractForwardedFrom($messageNode, $xpath),
                'is_reply' => $this->isReply($messageNode, $xpath),
                'reply_to_author' => $this->extractReplyAuthor($messageNode, $xpath),
            ];

            $items[] = $item;
        }

        libxml_clear_errors();

        return $items;
    }

    /**
     * Extract message ID from data-post attribute.
     */
    private function extractMessageId(\DOMElement $messageNode): ?string
    {
        $dataPost = $messageNode->getAttribute('data-post');
        if (empty($dataPost)) {
            return null;
        }

        // Extract message ID from "channel/123" format
        $parts = explode('/', $dataPost);

        return end($parts) ?: null;
    }

    /**
     * Extract message URL.
     */
    private function extractMessageUrl(\DOMElement $messageNode, DOMXPath $xpath): ?string
    {
        $dataPost = $messageNode->getAttribute('data-post');
        if (empty($dataPost)) {
            return null;
        }

        return 'https://t.me/'.$dataPost;
    }

    /**
     * Extract message author.
     */
    private function extractAuthor(\DOMElement $messageNode, DOMXPath $xpath): ?string
    {
        $authorNodes = $xpath->query('.//div[contains(@class, "tgme_widget_message_author")]', $messageNode);

        if ($authorNodes && $authorNodes->length > 0) {
            return trim($authorNodes->item(0)->nodeValue);
        }

        return null;
    }

    /**
     * Extract message content.
     */
    private function extractContent(\DOMElement $messageNode, DOMXPath $xpath): string
    {
        $textNodes = $xpath->query('.//div[contains(@class, "tgme_widget_message_text")]', $messageNode);

        if ($textNodes && $textNodes->length > 0) {
            $textHtml = '';
            foreach ($textNodes as $textNode) {
                $textHtml .= $textNode->ownerDocument->saveHTML($textNode);
            }

            return $this->contentExtractor->extractText($textHtml);
        }

        return '';
    }

    /**
     * Extract message timestamp.
     */
    private function extractTimestamp(\DOMElement $messageNode, DOMXPath $xpath): ?string
    {
        $timeNodes = $xpath->query('.//time[@datetime]', $messageNode);

        if ($timeNodes && $timeNodes->length > 0) {
            return $timeNodes->item(0)->getAttribute('datetime');
        }

        return null;
    }

    /**
     * Extract view count.
     */
    private function extractViews(\DOMElement $messageNode, DOMXPath $xpath): ?string
    {
        $viewNodes = $xpath->query('.//span[contains(@class, "tgme_widget_message_views")]', $messageNode);

        if ($viewNodes && $viewNodes->length > 0) {
            return trim($viewNodes->item(0)->nodeValue);
        }

        return null;
    }

    /**
     * Detect message type (text, photo, video, document).
     */
    private function detectMessageType(\DOMElement $messageNode, DOMXPath $xpath): string
    {
        // Check for video
        $videoNodes = $xpath->query('.//div[contains(@class, "tgme_widget_message_video")]', $messageNode);
        if ($videoNodes && $videoNodes->length > 0) {
            return 'video';
        }

        // Check for photo
        $photoNodes = $xpath->query('.//div[contains(@class, "tgme_widget_message_photo")]', $messageNode);
        if ($photoNodes && $photoNodes->length > 0) {
            return 'photo';
        }

        // Check for document
        $docNodes = $xpath->query('.//div[contains(@class, "tgme_widget_message_document")]', $messageNode);
        if ($docNodes && $docNodes->length > 0) {
            return 'document';
        }

        return 'text';
    }

    /**
     * Extract video URL.
     */
    private function extractVideoUrl(\DOMElement $messageNode, DOMXPath $xpath): ?string
    {
        $videoNodes = $xpath->query('.//video[@src]', $messageNode);

        if ($videoNodes && $videoNodes->length > 0) {
            return $videoNodes->item(0)->getAttribute('src');
        }

        return null;
    }

    /**
     * Check if message is forwarded.
     */
    private function isForwarded(\DOMElement $messageNode, DOMXPath $xpath): bool
    {
        $forwardedNodes = $xpath->query('.//div[contains(@class, "tgme_widget_message_forwarded_from")]', $messageNode);

        return $forwardedNodes && $forwardedNodes->length > 0;
    }

    /**
     * Extract forwarded from information.
     */
    private function extractForwardedFrom(\DOMElement $messageNode, DOMXPath $xpath): ?string
    {
        $forwardedNodes = $xpath->query('.//div[contains(@class, "tgme_widget_message_forwarded_from")]', $messageNode);

        if ($forwardedNodes && $forwardedNodes->length > 0) {
            return trim($forwardedNodes->item(0)->nodeValue);
        }

        return null;
    }

    /**
     * Check if message is a reply.
     */
    private function isReply(\DOMElement $messageNode, DOMXPath $xpath): bool
    {
        $replyNodes = $xpath->query('.//div[contains(@class, "tgme_widget_message_reply")]', $messageNode);

        return $replyNodes && $replyNodes->length > 0;
    }

    /**
     * Extract reply author.
     */
    private function extractReplyAuthor(\DOMElement $messageNode, DOMXPath $xpath): ?string
    {
        $replyAuthorNodes = $xpath->query('.//div[contains(@class, "tgme_widget_message_reply")]//div[contains(@class, "tgme_widget_message_author")]', $messageNode);

        if ($replyAuthorNodes && $replyAuthorNodes->length > 0) {
            return trim($replyAuthorNodes->item(0)->nodeValue);
        }

        return null;
    }

    /**
     * Get parser name.
     */
    protected function getName(): string
    {
        return 'telegram';
    }
}
