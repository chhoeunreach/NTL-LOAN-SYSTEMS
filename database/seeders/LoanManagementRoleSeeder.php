<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates the roles a fresh install needs. Only `Admin` is hard-coded in the
 * codebase (App\User::can() and AuthServiceProvider::register() bypass every
 * gate for it); the rest are operational presets that show up automatically in
 * the user form role dropdown (LoanUserController::rolesForSelect).
 */
class LoanManagementRoleSeeder extends Seeder
{
    public function run(): void
    {
        if (! class_exists(Role::class) || ! Schema::hasTable('roles')) {
            $this->command?->warn('roles table not available, skipping role seeding.');

            return;
        }

        foreach ($this->roles() as $name => $permissions) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);

            if ($permissions === '*') {
                $role->syncPermissions($this->allPermissions());
            } else {
                $role->syncPermissions($this->existingPermissions($permissions));
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info('Roles seeded: '.implode(', ', array_keys($this->roles())).'.');
    }

    /**
     * @return array<string, string[]|'*'>
     */
    protected function roles(): array
    {
        return [
            'Admin' => '*',
            'Manager' => [
                'loan_management.dashboard.view',
                'loan_management.customers.view', 'loan_management.customers.create',
                'loan_management.customers.edit', 'loan_management.customers.delete',
                'loan_management.guarantors.view', 'loan_management.blacklist.view',
                'loan_management.loans.view', 'loan_management.loans.create',
                'loan_management.loans.edit', 'loan_management.loans.approve',
                'loan_management.loans.reject',
                'loan_management.schedules.view', 'loan_management.monthly_payments.view',
                'loan_management.overdue.view', 'loan_management.payments.view',
                'loan_management.payments.create', 'loan_management.payment_history.view',
                'loan_management.collection_visits.view', 'loan_management.collection.view',
                'loan_management.collection.assign', 'loan_management.collection.recovery',
                'loan_management.gps.view', 'loan_management.chat.view',
                'loan_management.chat.reply', 'loan_management.chat.assign',
                'loan_management.chat.transfer', 'loan_management.chat.close',
                'loan_management.chat.admin', 'loan_management.aba.view',
                'loan_management.reports.view', 'loan_management.import.view',
                'loan_management.export.view', 'loan_management.settings.view',
                'loan_management.create_from_sell',
                'user.view', 'roles.view', 'view_export_buttons',
                'access_all_locations', 'access_default_selling_price',
                'loan_management.sell_list', 'loan_management.sell_view',
                'loan_management.sell_convert',
            ],
            'Loan Staff' => [
                'loan_management.dashboard.view',
                'loan_management.customers.view', 'loan_management.customers.create',
                'loan_management.customers.edit',
                'loan_management.guarantors.view',
                'loan_management.loans.view', 'loan_management.loans.create',
                'loan_management.loans.edit',
                'loan_management.schedules.view', 'loan_management.monthly_payments.view',
                'loan_management.overdue.view', 'loan_management.payments.view',
                'loan_management.payments.create', 'loan_management.payment_history.view',
                'loan_management.collection_visits.view',
                'loan_management.gps.view', 'loan_management.chat.view',
                'loan_management.chat.reply', 'loan_management.chat.admin',
                'loan_management.customer_gps.manage',
                'loan_management.aba.view', 'loan_management.reports.view',
                'loan_management.create_from_sell', 'view_export_buttons',
                'loan_management.sell_list',
            ],
            'Cashier' => [
                'loan_management.dashboard.view',
                'loan_management.customers.view',
                'loan_management.loans.view',
                'loan_management.schedules.view', 'loan_management.monthly_payments.view',
                'loan_management.payments.view', 'loan_management.payments.create',
                'loan_management.payment_history.view',
                'loan_management.aba.view', 'sell.view',
            ],
            'Collector' => [
                'loan_management.dashboard.view',
                'loan_management.customers.view',
                'loan_management.loans.view',
                'loan_management.schedules.view', 'loan_management.overdue.view',
                'loan_management.payments.view', 'loan_management.payments.create',
                'loan_management.collection.view', 'loan_management.collection.assign',
                'loan_management.collection_visits.view',
                'loan_management.collection.recovery', 'loan_management.collection.legal',
                'loan_management.collection.repossess', 'loan_management.collection.writeoff',
                'loan_management.gps.view',
                'loan_management.chat.view', 'loan_management.chat.reply',
            ],
        ];
    }

    protected function allPermissions()
    {
        if (! class_exists(Permission::class) || ! Schema::hasTable('permissions')) {
            return collect();
        }

        return Permission::where('guard_name', 'web')->get();
    }

    /**
     * Filters against registered permissions so a role never references a
     * permission that failed to seed.
     */
    protected function existingPermissions(array $names)
    {
        $available = $this->allPermissions();

        if ($available->isEmpty()) {
            return collect();
        }

        return $available->whereIn('name', $names)->values();
    }
}