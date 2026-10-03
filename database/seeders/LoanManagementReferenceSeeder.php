<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\LoanManagement\Services\LoanSyncFromPosService;

class LoanManagementReferenceSeeder extends Seeder
{
    /**
     * The loan connection, resolved once so every method stays in sync with config.
     *
     * @var string
     */
    protected $connection;

    public function __construct()
    {
        $this->connection = (string) config('loanmanagement.db_connection', 'mysql_loan');
    }

    public function run(): void
    {
        if (Schema::connection($this->connection)->hasTable('loan_business_locations')) {
            $this->seedLoanBusinessLocations();
        }

        if (Schema::connection($this->connection)->hasTable('loan_currencies')) {
            $this->seedLoanCurrencies();
        }

        if (Schema::connection($this->connection)->hasTable('loan_payment_methods')) {
            $this->ensurePaymentMethodColumns();
            $this->seedLoanPaymentMethods();
        }
    }

    /**
     * SettingsController orders payment methods by sort_order and stores a code,
     * and both columns were only ever added lazily when that page loaded.
     */
    protected function ensurePaymentMethodColumns(): void
    {
        if (! Schema::connection($this->connection)->hasColumn('loan_payment_methods', 'code')) {
            Schema::connection($this->connection)->table('loan_payment_methods', function (Blueprint $table) {
                $table->string('code', 60)->nullable()->after('id')->index();
            });
        }

        if (! Schema::connection($this->connection)->hasColumn('loan_payment_methods', 'sort_order')) {
            Schema::connection($this->connection)->table('loan_payment_methods', function (Blueprint $table) {
                $table->integer('sort_order')->default(0)->after('is_active');
            });
        }
    }

    private function seedLoanBusinessLocations(): void
    {
        if (Schema::hasTable('business_locations')) {
            app(LoanSyncFromPosService::class)->syncBusinessLocations();
        }

        $count = (int) DB::connection($this->connection)->table('loan_business_locations')->count();
        if ($count > 0) {
            $this->ensureLocationDefaults();

            return;
        }

        $now = now();
        DB::connection($this->connection)->table('loan_business_locations')->insert($this->loanLocationColumns([
            'main_business_id' => null,
            'main_location_id' => null,
            'name' => 'Main Location',
            'location_code' => 'MAIN',
            'loan_invoice_prefix' => 'KY-',
            'address' => 'Phnom Penh',
            'phone' => null,
            'status' => 'active',
            'telegram_notify_payment' => false,
            'telegram_notify_installment' => false,
            'synced_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]));
    }

    private function ensureLocationDefaults(): void
    {
        $rows = DB::connection($this->connection)->table('loan_business_locations')->get();

        foreach ($rows as $row) {
            $updates = [];
            if ($this->loanLocationHasColumn('location_code') && empty($row->location_code)) {
                $updates['location_code'] = 'LOC-'.str_pad((string) $row->id, 3, '0', STR_PAD_LEFT);
            }
            if ($this->loanLocationHasColumn('loan_invoice_prefix') && empty($row->loan_invoice_prefix)) {
                $updates['loan_invoice_prefix'] = 'KY-';
            }
            if ($this->loanLocationHasColumn('status') && empty($row->status)) {
                $updates['status'] = 'active';
            }
            if ($this->loanLocationHasColumn('updated_at')) {
                $updates['updated_at'] = now();
            }

            if (! empty($updates)) {
                DB::connection($this->connection)->table('loan_business_locations')->where('id', $row->id)->update($updates);
            }
        }
    }

    private function loanLocationColumns(array $payload): array
    {
        $columns = Schema::connection($this->connection)->getColumnListing('loan_business_locations');

        return array_intersect_key($payload, array_flip($columns));
    }

    private function loanLocationHasColumn(string $column): bool
    {
        return Schema::connection($this->connection)->hasColumn('loan_business_locations', $column);
    }

    private function seedLoanCurrencies(): void
    {
        $columns = [
            'name' => Schema::connection($this->connection)->hasColumn('loan_currencies', 'name'),
            'exchange_rate' => Schema::connection($this->connection)->hasColumn('loan_currencies', 'exchange_rate'),
            'is_default' => Schema::connection($this->connection)->hasColumn('loan_currencies', 'is_default'),
            'is_active' => Schema::connection($this->connection)->hasColumn('loan_currencies', 'is_active'),
            'updated_at' => Schema::connection($this->connection)->hasColumn('loan_currencies', 'updated_at'),
            'created_at' => Schema::connection($this->connection)->hasColumn('loan_currencies', 'created_at'),
        ];

        $now = now();
        $rows = [
            'USD' => ['name' => 'US Dollar', 'exchange_rate' => 1, 'is_default' => 1, 'is_active' => 1],
            'KHR' => ['name' => 'Cambodian Riel', 'exchange_rate' => 4100, 'is_default' => 0, 'is_active' => 1],
        ];

        foreach ($rows as $code => $data) {
            $updateData = [];
            foreach ($data as $key => $value) {
                if (! empty($columns[$key])) {
                    $updateData[$key] = $value;
                }
            }
            if ($columns['updated_at']) {
                $updateData['updated_at'] = $now;
            }
            if ($columns['created_at']) {
                $updateData['created_at'] = $now;
            }

            DB::connection($this->connection)->table('loan_currencies')->updateOrInsert(
                ['code' => $code],
                $updateData
            );
        }
    }

    private function seedLoanPaymentMethods(): void
    {
        $hasIsActive = Schema::connection($this->connection)->hasColumn('loan_payment_methods', 'is_active');
        $hasUpdatedAt = Schema::connection($this->connection)->hasColumn('loan_payment_methods', 'updated_at');
        $hasCreatedAt = Schema::connection($this->connection)->hasColumn('loan_payment_methods', 'created_at');
        $hasCode = Schema::connection($this->connection)->hasColumn('loan_payment_methods', 'code');
        $hasSortOrder = Schema::connection($this->connection)->hasColumn('loan_payment_methods', 'sort_order');
        $now = now();

        foreach ($this->paymentMethods() as $index => $name) {
            $updateData = [];
            if ($hasCode) {
                $updateData['code'] = strtolower(str_replace(' ', '_', $name));
            }
            if ($hasIsActive) {
                $updateData['is_active'] = 1;
            }
            if ($hasSortOrder) {
                $updateData['sort_order'] = $index + 1;
            }
            if ($hasUpdatedAt) {
                $updateData['updated_at'] = $now;
            }
            if ($hasCreatedAt) {
                $updateData['created_at'] = $now;
            }

            DB::connection($this->connection)->table('loan_payment_methods')->updateOrInsert(
                ['name' => $name],
                $updateData
            );
        }
    }

    protected function paymentMethods(): array
    {
        return ['Cash', 'ABA', 'ACLEDA', 'Wing', 'Bank Transfer', 'QR', 'Credit Adjustment'];
    }
}
