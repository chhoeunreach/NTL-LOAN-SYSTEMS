<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\LoanManagement\Services\TelegramSettingsService;

class TelegramWebhookStatus extends Command
{
    protected $signature = 'loan-management:telegram-webhook-status
        {--register : Register (or re-register) the webhook before showing status}
        {--unregister : Delete the webhook instead of showing status}';

    protected $description = 'Show Telegram webhook registration status and optionally register or unregister it.';

    public function handle(): int
    {
        $token = TelegramSettingsService::botToken();

        if ($token === '') {
            $this->error('No bot token configured. Save a bot token in Settings -> Telegram Bot first.');

            return self::FAILURE;
        }

        if ($this->option('unregister')) {
            return $this->unregister($token);
        }

        if ($this->option('register')) {
            $this->register($token);
        }

        return $this->status($token);
    }

    protected function webhookUrl(): string
    {
        $configured = trim((string) config('loanmanagement.telegram.webhook_url'));

        return $configured !== '' ? $configured : url('/webhook/loan-telegram');
    }

    protected function register(string $token): void
    {
        $url = $this->webhookUrl();
        $this->info("Registering webhook: {$url}");

        try {
            $response = Http::timeout(15)->asForm()->post("https://api.telegram.org/bot{$token}/setWebhook", [
                'url' => $url,
                'secret_token' => TelegramSettingsService::webhookSecret(),
            ]);
        } catch (\Throwable $e) {
            $this->error('Could not reach Telegram: '.$e->getMessage());

            return;
        }

        if ($response->failed() || ! $response->json('ok')) {
            $this->error('setWebhook failed: '.$response->body());

            return;
        }

        TelegramSettingsService::markWebhookRegistered($url);
        $this->info('Webhook registered successfully.');
    }

    protected function unregister(string $token): int
    {
        try {
            $response = Http::timeout(15)->asForm()->post("https://api.telegram.org/bot{$token}/deleteWebhook");
        } catch (\Throwable $e) {
            $this->error('Could not reach Telegram: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($response->failed() || ! $response->json('ok')) {
            $this->error('deleteWebhook failed: '.$response->body());

            return self::FAILURE;
        }

        $this->info('Webhook deleted. You can now poll updates or switch to another webhook URL.');

        return self::SUCCESS;
    }

    protected function status(string $token): int
    {
        try {
            $response = Http::timeout(15)->get("https://api.telegram.org/bot{$token}/getWebhookInfo");
        } catch (\Throwable $e) {
            $this->error('Could not reach Telegram: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($response->failed() || ! $response->json('ok')) {
            $this->error('getWebhookInfo failed: '.$response->body());

            return self::FAILURE;
        }

        $info = (array) $response->json('result', []);
        $url = (string) ($info['url'] ?? '');
        $pending = (int) ($info['pending_update_count'] ?? 0);
        $lastError = (string) ($info['last_error_message'] ?? '');
        $lastErrorDate = isset($info['last_error_date']) ? date('Y-m-d H:i:s', (int) $info['last_error_date']) : '';

        $expected = $this->webhookUrl();

        $this->table(
            ['Field', 'Value'],
            [
                ['Current webhook URL', $url !== '' ? $url : '(none)'],
                ['Expected URL', $expected],
                ['Matches expected', $this->label($url !== '' && $url === $expected)],
                ['Pending updates', (string) $pending],
                ['Last error', $lastError !== '' ? $lastError : '-'],
                ['Last error at', $lastErrorDate !== '' ? $lastErrorDate : '-'],
            ]
        );

        if ($url === '') {
            $this->warn('No webhook is registered. Run with --register to set it, or set LOAN_CHAT_TELEGRAM_WEBHOOK_URL in .env.');
        } elseif ($url === $expected) {
            $this->info('Webhook is registered at the expected URL. Inbound Telegram messages will reach this app.');
        } else {
            $this->warn('Webhook points elsewhere. Re-register or change LOAN_CHAT_TELEGRAM_WEBHOOK_URL to match.');
        }

        return self::SUCCESS;
    }

    protected function label(bool $ok): string
    {
        return $ok ? 'yes' : 'no';
    }
}