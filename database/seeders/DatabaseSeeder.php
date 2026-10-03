<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Throwable;

/**
 * Single entry point for a complete first setup.
 *
 * Run against a migrated database:
 *   php artisan migrate --force
 *   php artisan db:seed --force
 *
 * Everything lives in LoanManagementDatabaseSeeder so that seeding from here,
 * `php artisan loan-management:install`, and the module installer all produce
 * the same result. Every seeder below is idempotent.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(LoanManagementDatabaseSeeder::class);
        $this->callSampleData();
    }

    /**
     * Catalog samples are optional: CmsSampleDataSeeder throws when loan_products
     * is absent, which would otherwise abort the rest of a partially migrated
     * install. The core setup above is already committed at this point.
     */
    protected function callSampleData(): void
    {
        try {
            $this->call(CmsSampleDataSeeder::class);
        } catch (Throwable $e) {
            $this->command?->warn('Sample catalog data skipped: '.$e->getMessage());
        }
    }
}