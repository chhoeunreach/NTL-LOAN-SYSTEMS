<?php

namespace Modules\LoanManagement\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;

class BusinessSettingsService
{
    protected const DEFAULTS = [
        'business_name' => 'KY Store',
        'system_name' => 'Loan Management',
        'system_subtitle' => 'Dedicated loan operation workspace',
        'theme_color' => '#6366f1',
        'logo_path' => null,
        'login_background_path' => null,
        'home_headline' => 'Simple loan service for customers',
        'home_subtitle' => 'Register with NTL CO.LTD and our team will contact you about your loan request.',
        'home_body' => 'Fast customer registration, clear payment schedules, Telegram updates, and easy support from our branch team.',
        'invoice_message_template' => "❤️ **{Customer Name}** អរគុណសម្រាប់ការទូទាត់ និងការទុកចិត្តលើ **{Business Name}**។\n🧾 វិក្កយបត្ររបស់អ្នកសូមមើលខាងក្រោម។",
    ];

    public static function get(): array
    {
        $settings = self::read();

        return array_merge(self::DEFAULTS, array_intersect_key($settings, self::DEFAULTS));
    }

    public static function save(array $data): void
    {
        $current = self::get();
        $payload = [
            'business_name' => self::cleanText($data['business_name'] ?? $current['business_name'], 80),
            'system_name' => self::cleanText($data['system_name'] ?? $current['system_name'], 80),
            'system_subtitle' => self::cleanText($data['system_subtitle'] ?? $current['system_subtitle'], 120),
            'theme_color' => self::cleanColor($data['theme_color'] ?? $current['theme_color']),
            'logo_path' => $data['logo_path'] ?? $current['logo_path'],
            'login_background_path' => $data['login_background_path'] ?? $current['login_background_path'],
            'home_headline' => self::cleanText($data['home_headline'] ?? $current['home_headline'], 140),
            'home_subtitle' => self::cleanText($data['home_subtitle'] ?? $current['home_subtitle'], 220),
            'home_body' => self::cleanMultilineText($data['home_body'] ?? $current['home_body'], 1200, ''),
            'invoice_message_template' => self::cleanMultilineText($data['invoice_message_template'] ?? $current['invoice_message_template'], 2000, self::DEFAULTS['invoice_message_template']),
        ];

        $path = self::path();
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public static function businessName(): string
    {
        return self::get()['business_name'];
    }

    public static function systemName(): string
    {
        return self::get()['system_name'];
    }

    public static function systemSubtitle(): string
    {
        return self::get()['system_subtitle'];
    }

    public static function themeColor(): string
    {
        return self::get()['theme_color'];
    }

    public static function invoiceMessageTemplate(): string
    {
        return self::get()['invoice_message_template'];
    }

    public static function invoiceMessage(string $customerName = ''): string
    {
        $settings = self::get();
        $replacements = [
            '{Customer Name}' => trim($customerName) !== '' ? trim($customerName) : 'Customer',
            '{Business Name}' => $settings['business_name'] ?: 'KY Store',
        ];

        return strtr($settings['invoice_message_template'], $replacements);
    }

    public static function logoUrl(): ?string
    {
        $path = self::get()['logo_path'] ?? null;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        if (Route::has('loan-management.settings.business.logo')) {
            return route('loan-management.settings.business.logo');
        }

        return Storage::disk('public')->url($path);
    }

    public static function publicLogoUrl(): ?string
    {
        $path = self::get()['logo_path'] ?? null;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        if (Route::has('loan-management.settings.business.public-logo')) {
            return route('loan-management.settings.business.public-logo');
        }

        return Storage::disk('public')->url($path);
    }

    public static function loginBackgroundUrl(): ?string
    {
        $path = self::get()['login_background_path'] ?? null;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        if (Route::has('loan-management.settings.business.login-background')) {
            return route('loan-management.settings.business.login-background');
        }

        return Storage::disk('public')->url($path);
    }

    public static function deleteLogo(?string $path = null): void
    {
        $path = $path ?: (self::get()['logo_path'] ?? null);

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function deleteLoginBackground(?string $path = null): void
    {
        $path = $path ?: (self::get()['login_background_path'] ?? null);

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public static function cssVariables(): string
    {
        $color = self::themeColor();
        [$r, $g, $b] = self::hexToRgb($color);
        $dark = self::rgbToHex((int) round($r * 0.82), (int) round($g * 0.82), (int) round($b * 0.82));
        $light = self::mixWithWhite($r, $g, $b, 0.25);
        $tone50 = self::mixWithWhite($r, $g, $b, 0.92);
        $tone100 = self::mixWithWhite($r, $g, $b, 0.84);
        $tone200 = self::mixWithWhite($r, $g, $b, 0.70);

        return implode("\n", [
            "--lm-primary: {$color};",
            "--lm-primary-dark: {$dark};",
            "--lm-primary-light: {$light};",
            "--lm-primary-50: {$tone50};",
            "--lm-primary-100: {$tone100};",
            "--lm-primary-200: {$tone200};",
            "--lm-sidebar-active: {$color};",
            "--lm-primary-rgb: {$r}, {$g}, {$b};",
        ]);
    }

    public static function hasSavedSettings(): bool
    {
        return is_file(self::path());
    }

    protected static function read(): array
    {
        $path = self::path();

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    protected static function path(): string
    {
        return storage_path('app/loan-management/business_settings.json');
    }

    protected static function cleanText($value, int $length): string
    {
        $clean = trim((string) $value);

        return $clean !== '' ? mb_substr($clean, 0, $length) : '';
    }

    protected static function cleanMultilineText($value, int $length, string $default = ''): string
    {
        $clean = trim(str_replace(["\r\n", "\r"], "\n", (string) $value));

        return $clean !== '' ? mb_substr($clean, 0, $length) : $default;
    }

    protected static function cleanColor($value): string
    {
        $color = strtolower(trim((string) $value));

        return preg_match('/^#[0-9a-f]{6}$/', $color) ? $color : self::DEFAULTS['theme_color'];
    }

    protected static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    protected static function rgbToHex(int $r, int $g, int $b): string
    {
        return sprintf('#%02x%02x%02x', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
    }

    protected static function mixWithWhite(int $r, int $g, int $b, float $whiteRatio): string
    {
        return self::rgbToHex(
            (int) round(($r * (1 - $whiteRatio)) + (255 * $whiteRatio)),
            (int) round(($g * (1 - $whiteRatio)) + (255 * $whiteRatio)),
            (int) round(($b * (1 - $whiteRatio)) + (255 * $whiteRatio))
        );
    }
}
