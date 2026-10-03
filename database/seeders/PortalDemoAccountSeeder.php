<?php

namespace Database\Seeders;

use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\LoanManagement\Services\LoanCustomerService;

class PortalDemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDemoCustomer(LoanCustomerService::class);
        $this->seedDemoAdmin();
    }

    /**
     * The demo panel on the customer portal only renders when a designated
     * account exists with a verified password, can_login and active status.
     */
    protected function seedDemoCustomer(string $customerService): void
    {
        $connection = (string) config('loanmanagement.db_connection', 'mysql_loan');
        if (! Schema::connection($connection)->hasTable('loan_customers')) {
            $this->warn('loan_customers table not found, skipping demo customer.');

            return;
        }

        $identifier = trim((string) config('loanmanagement.demo_customer_identifier', '010111001'));
        $password = (string) config('loanmanagement.demo_customer_password', 'password');
        $name = (string) config('loanmanagement.demo_customer_name', 'Demo Customer');

        if ($identifier === '') {
            $this->warn('No demo customer identifier configured, skipping.');

            return;
        }

        $existing = DB::connection($connection)->table('loan_customers')
            ->where(fn ($query) => $query->where('login_phone', $identifier)
                ->orWhere('phone', $identifier)
                ->orWhere('username', $identifier))
            ->orderBy('id')
            ->first();

        $payload = [
            'name' => $name,
            'phone' => $identifier,
            'login_phone' => $identifier,
            'username' => $identifier,
            'can_login' => 1,
            'status' => 'active',
            'customer_type' => 'demo',
            'note' => 'Demo account for the Customer Portal demo login panel.',
            'password' => Hash::make($password),
            'updated_at' => now(),
        ];
        $payload = array_intersect_key($payload, array_flip(Schema::connection($connection)->getColumnListing('loan_customers')));

        if ($existing) {
            DB::connection($connection)->table('loan_customers')
                ->where('id', $existing->id)
                ->update($payload);
            $this->info("Demo customer updated (#{$existing->id}): {$identifier}");
        } else {
            // LoanCustomerService hashes the plain password and filters columns itself.
            $plain = $payload;
            $plain['password'] = $password;
            $id = app($customerService)->create($plain);
            $this->info("Demo customer created (#{$id}): {$identifier}");
        }
    }

    protected function seedDemoAdmin(): void
    {
        $identifier = trim((string) config('loanmanagement.demo_admin_identifier', 'admin@example.com'));
        $password = (string) config('loanmanagement.demo_admin_password', 'password');

        if ($identifier === '' || ! Schema::hasTable('users')) {
            $this->warn('No demo admin identifier or users table, skipping demo admin.');

            return;
        }

        $existing = User::query()
            ->where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if ($existing) {
            $existing->forceFill([
                'password' => Hash::make($password),
                'status' => 'active',
                'allow_login' => 1,
            ])->save();
            $this->info("Demo admin updated (#{$existing->id}): {$identifier}");

            return;
        }

        User::query()->create([
            'name' => 'Demo Admin',
            'username' => $identifier,
            'email' => $identifier,
            'password' => Hash::make($password),
            'status' => 'active',
            'allow_login' => 1,
        ]);
        $this->info("Demo admin created: {$identifier}");
    }

    protected function info(string $message): void
    {
        if (isset($this->command)) {
            $this->command->info($message);
        }
    }

    protected function warn(string $message): void
    {
        if (isset($this->command)) {
            $this->command->warn($message);
        }
    }
}