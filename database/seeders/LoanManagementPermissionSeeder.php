<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

class LoanManagementPermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (! class_exists(Permission::class) || ! Schema::hasTable('permissions')) {
            return;
        }

        $registered = 0;

        foreach ($this->permissions() as $permission) {
            try {
                if (Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web'])->wasRecentlyCreated) {
                    $registered++;
                }
            } catch (\Throwable $e) {
                // Ignore individual duplicate / permission error
            }
        }

        $this->command?->info("Loan management permissions ready ({$registered} newly registered).");
    }

    /**
     * Permissions the application checks at runtime but that are absent from
     * config('loanmanagement.permissions'): the POS-compat flags in App\User,
     * the export buttons rendered across the blade views, the module installer
     * gate, and the sell-integration bridge in DataController.
     */
    public function permissions(): array
    {
        return array_values(array_unique(array_merge(
            (array) config('loanmanagement.permissions', []),
            [
                'view_export_buttons',
                'access_all_locations',
                'access_default_selling_price',
                'manage_modules',
                'sell.view',
                'loan_management.sell_list',
                'loan_management.sell_view',
                'loan_management.sell_convert',
            ]
        )));
    }
}