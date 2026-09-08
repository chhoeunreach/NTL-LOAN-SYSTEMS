<?php

namespace Database\Seeders;

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LoanAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@example.com');
        $username = env('ADMIN_USERNAME', 'admin');
        $password = env('ADMIN_PASSWORD', 'password');
        $name = env('ADMIN_NAME', 'Loan Admin');
        $firstName = env('ADMIN_FIRST_NAME', 'Loan');
        $lastName = env('ADMIN_LAST_NAME', 'Admin');
        $businessId = (int) env('ADMIN_BUSINESS_ID', 1);

        $this->seedPermissions();
        $role = $this->seedAdminRole();
        $this->seedMainUser($role, $email, $username, $password, $name, $firstName, $lastName, $businessId);
        $this->seedLoanUser($email, $username, $password, $name, $firstName, $lastName, $businessId);

        if (isset($this->command)) {
            $this->command->info('-----------------------------------------');
            $this->command->info('Loan admin user seeded successfully!');
            $this->command->info("Username : {$username}");
            $this->command->info("Email    : {$email}");
            $this->command->info("Password : {$password}");
            $this->command->info('-----------------------------------------');
        }
    }

    private function seedPermissions(): void
    {
        if (! class_exists(Permission::class) || ! Schema::hasTable('permissions')) {
            return;
        }

        $permissions = (array) config('loanmanagement.permissions', []);

        foreach ($permissions as $permissionName) {
            try {
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);
            } catch (\Throwable $e) {
                // Skip duplicate or unavailable permissions without stopping the admin seed.
            }
        }
    }

    private function seedAdminRole(): ?Role
    {
        if (! class_exists(Role::class) || ! Schema::hasTable('roles')) {
            return null;
        }

        try {
            $role = Role::firstOrCreate([
                'name' => 'Admin',
                'guard_name' => 'web',
            ]);

            if (class_exists(Permission::class) && Schema::hasTable('permissions')) {
                $permissions = Permission::where('guard_name', 'web')->get();

                if ($permissions->isNotEmpty() && method_exists($role, 'syncPermissions')) {
                    $role->syncPermissions($permissions);
                }
            }

            return $role;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function seedMainUser(
        ?Role $role,
        string $email,
        string $username,
        string $password,
        string $name,
        string $firstName,
        string $lastName,
        int $businessId
    ): void {
        if (! Schema::hasTable('users')) {
            return;
        }

        $user = User::query()
            ->where('email', $email)
            ->orWhere('username', $username)
            ->first();

        $userData = [
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make($password),
            'business_id' => $businessId,
            'allow_login' => true,
            'status' => 'active',
        ];

        if ($user) {
            $user->update($userData);
        } else {
            $user = User::create($userData);
        }

        if ($role && method_exists($user, 'assignRole')) {
            try {
                $user->assignRole($role);
            } catch (\Throwable $e) {
                // Role assignment is optional for installs without Spatie traits on User.
            }
        }
    }

    private function seedLoanUser(
        string $email,
        string $username,
        string $password,
        string $name,
        string $firstName,
        string $lastName,
        int $businessId
    ): void {
        try {
            $connection = config('loanmanagement.db_connection', 'mysql_loan');

            if (! Schema::connection($connection)->hasTable('loan_users')) {
                return;
            }

            $existingUser = DB::connection($connection)
                ->table('loan_users')
                ->where('email', $email)
                ->orWhere('username', $username)
                ->first();

            $userData = [
                'name' => $name,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($password),
                'business_id' => $businessId,
                'allow_login' => 1,
                'status' => 'active',
                'updated_at' => now(),
            ];

            if ($existingUser) {
                DB::connection($connection)
                    ->table('loan_users')
                    ->where('id', $existingUser->id)
                    ->update($userData);

                return;
            }

            $userData['created_at'] = now();

            DB::connection($connection)
                ->table('loan_users')
                ->insert($userData);
        } catch (\Throwable $e) {
            // The secondary loan connection is optional in some installations.
        }
    }
}
