<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\LoanManagement\Services\BusinessSettingsService;


class LoanManagementSystemDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBusinessSettings();

    }

    private function seedBusinessSettings(): void
    {
        if (BusinessSettingsService::hasSavedSettings()) {
            return;
        }

        BusinessSettingsService::save([
            'business_name' => 'KY Store',
            'system_name' => 'Loan Management',
            'system_subtitle' => 'Dedicated loan operation workspace',
            'theme_color' => '#6366f1',
            'home_headline' => 'Simple loan service for customers',
            'home_subtitle' => 'Register with KY Store and our team will contact you about your loan request.',
            'home_body' => 'Fast customer registration, clear payment schedules, Telegram updates, and easy support from our branch team.',
        ]);
    }


}