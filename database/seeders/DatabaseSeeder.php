<?php

namespace Database\Seeders;

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Loan Admin',
                'first_name' => 'Loan',
                'last_name' => 'Admin',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'business_id' => 1,
                'allow_login' => true,
                'status' => 'active',
            ]
        );

        if (class_exists(Role::class) && \Illuminate\Support\Facades\Schema::hasTable('roles')) {
            try {
                $role = Role::query()->firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
                if (method_exists($admin, 'assignRole')) {
                    $admin->assignRole($role);
                }
            } catch (\Throwable $e) {
                // Ignore role assignment failure
            }
        }

        $this->call(LoanManagementDatabaseSeeder::class);

        if ((bool) env('LOAN_SEED_DEMO_DATA', true)) {
            $this->call(LoanManagementDemoDataSeeder::class);
        }
    }
}
