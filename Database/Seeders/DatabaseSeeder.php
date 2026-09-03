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

        $role = Role::query()->firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->assignRole($role);
    }
}
