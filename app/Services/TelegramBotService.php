<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the Telegram Bot API built on Laravel's HTTP client.
 *
 * Used by the Telegram customer-chat bridge and the loan "@start" linking flow,
 * so every call depends only on guzzlehttp/guzzle (already required).
 */
class TelegramBotService
{
    protected string $token;

    public function __construct(string $token)
    {
        $this->token = trim($token);
    }

    protected function apiBase(): string
    {
        return 'https://api.telegram.org/bot'.$this->token;
    }

    /**
     * Send a plain text message. Returns the API payload (contains message_id).
     */
    public function sendMessageToChat(string $chatId, string $message, array $extra = []): array
    {
        return $this->post('sendMessage', [
            'chat_id' => $chatId,
            'text' => $message,
        ] + $extra);
    }

    /**
     * Send an image from a local file path. Returns the API payload.
     */
    public function sendPhotoToChat(string $chatId, string $filePath, ?string $caption = null, array $extra = []): array
    {
        return $this->multipart('sendPhoto', [
            'chat_id' => $chatId,
            'caption' => $caption,
        ], $filePath, 'photo', $extra);
    }

    /**
     * Send a document (or audio) from a local file path. Returns the API payload.
     */
    public function sendDocumentToChat(string $chatId, string $filePath, ?string $caption = null, ?string $fileName = null, array $extra = []): array
    {
        return $this->multipart('sendDocument', [
            'chat_id' => $chatId,
            'caption' => $caption,
        ], $filePath, 'document', $extra, $fileName);
    }

    /**
     * Send a location. Returns the API payload.
     */
    public function sendLocationToChat(string $chatId, float $latitude, float $longitude, array $extra = []): array
    {
        return $this->post('sendLocation', [
            'chat_id' => $chatId,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ] + $extra);
    }

    /**
     * Edit the text of an already-sent message. Returns the API payload.
     */
    public function editMessageText(string $chatId, int $messageId, string $text): array
    {
        return $this->post('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
        ]);
    }

    /**
     * Delete an already-sent message. Returns the API payload.
     */
    public function deleteMessage(string $chatId, int $messageId): array
    {
        return $this->post('deleteMessage', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
        ]);
    }

    /**
     * Resolve a file id to its metadata. Returns the "result" object (includes file_path).
     */
    public function getFile(string $fileId): array
    {
        return $this->get('getFile', ['file_id' => $fileId]);
    }

    /**
     * Download a bot file (by its file_path) to a temp local file and return the local path.
     */
    public function downloadFile(string $remotePath): string
    {
        $response = Http::timeout(60)->get('https://api.telegram.org/file/bot'.$this->token.'/'.ltrim($remotePath, '/'));

        if ($response->failed()) {
            throw new \RuntimeException('Telegram file download failed: '.$response->body());
        }

        $dir = storage_path('app/temp/telegram-files');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $localName = 'tg-'.bin2hex(random_bytes(8)).'-'.basename($remotePath);
        $localPath = $dir.DIRECTORY_SEPARATOR.$localName;
        file_put_contents($localPath, $response->body());

        return $localPath;
    }

    protected function post(string $method, array $payload): array
    {
        $response = Http::timeout(30)->post($this->apiBase().'/'.$method, $payload);

        return $this->decode($response);
    }

    protected function get(string $method, array $query): array
    {
        $response = Http::timeout(30)->get($this->apiBase().'/'.$method, $query);

        return $this->decode($response);
    }

    protected function multipart(string $method, array $fields, string $filePath, string $fileField, array $extra = [], ?string $fileName = null): array
    {
        if (! is_file($filePath)) {
            throw new \RuntimeException('Telegram file is not readable at: '.$filePath);
        }

        $multipart = [];
        foreach (($fields + $extra) as $key => $value) {
            if ($value !== null && $value !== '') {
                $multipart[] = ['name' => $key, 'contents' => (string) $value];
            }
        }
        $multipart[] = [
            'name' => $fileField,
            'contents' => fopen($filePath, 'r'),
            'filename' => $fileName ?: basename($filePath),
        ];

        $response = Http::timeout(60)->asMultipart()->post($this->apiBase().'/'.$method, $multipart);

        return $this->decode($response);
    }

    protected function decode(Response $response): array
    {
        if ($response->failed()) {
            throw new \RuntimeException('Telegram API error: '.$response->body());
        }

        $json = $response->json();

        if (! is_array($json) || ! ($json['ok'] ?? false)) {
            throw new \RuntimeException('Telegram API error: '.($response->body() ?: 'empty response'));
        }

        return (array) ($json['result'] ?? []);
    }
}