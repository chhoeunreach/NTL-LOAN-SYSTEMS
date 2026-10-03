<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\LoanManagement\Services\BusinessSettingsService;

/**
 * Materialises the business settings file that BusinessSettingsService reads.
 *
 * BusinessSettingsService::get() falls back to its DEFAULTS map at runtime, so
 * the file is not strictly required — but writing it once during first setup
 * gives operators a concrete starting point to edit.
 *
 * Current values always win: the installer defaults are merged underneath them
 * so this seeder can never clobber CmsHomeSeeder output or an operator's edits.
 */
class LoanManagementSystemDataSeeder extends Seeder
{
    public function run(): void
    {
        $current = BusinessSettingsService::get();
        BusinessSettingsService::save(array_merge($this->defaults(), $current));

        $this->command?->info('Business settings file initialised.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaults(): array
    {
        return [
            'business_name' => 'KY Store',
            'system_name' => 'Loan Management',
            'system_subtitle' => 'Dedicated loan operation workspace',
            'theme_color' => '#6366f1',
            'home_headline' => 'Simple loan service for customers',
            'home_subtitle' => 'Register with KY Store and our team will contact you about your loan request.',
            'home_body' => 'Fast customer registration, clear payment schedules, Telegram updates, and easy support from our branch team.',
        ];
    }
}
