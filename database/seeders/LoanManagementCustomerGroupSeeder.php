<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds the customer groups the loan module reads in order to tag borrowers.
 *
 * The 'រំលស់' (Installment) group is created on demand by
 * LoanToPosPrefillService and referenced as a literal by
 * CreateLoanFromSellService, LoanFromSellController and DataController, so a
 * fresh install must already contain it.
 */
class LoanManagementCustomerGroupSeeder extends Seeder
{
    public function run(): void
    {
        $businessId = (int) env('ADMIN_BUSINESS_ID', 1);
        $seeded = 0;

        foreach ($this->connections() as $connection) {
            if (! Schema::connection($connection)->hasTable('customer_groups')) {
                $this->command?->warn("customer_groups table missing on connection [{$connection}].");

                continue;
            }

            $seeded += $this->seedConnection($connection, $businessId);
        }

        $this->command?->info("Customer groups seeded ({$seeded} row(s) written).");
    }

    /**
     * Groups are read from both the loan connection (App\CustomerGroup,
     * LoanCreateController) and the default connection (LoanDashboardService,
     * CreateLoanFromSellService, TransactionUtil).
     */
    protected function connections(): array
    {
        $connections = [(string) config('loanmanagement.db_connection', 'mysql_loan')];
        $default = (string) config('database.default');

        $loanDatabase = DB::connection($connections[0])->getDatabaseName();
        $defaultDatabase = DB::connection($default)->getDatabaseName();

        if ($loanDatabase !== $defaultDatabase && ! in_array($default, $connections, true)) {
            $connections[] = $default;
        }

        return $connections;
    }

    protected function seedConnection(string $connection, int $businessId): int
    {
        $columns = Schema::connection($connection)->getColumnListing('customer_groups');
        $hasDeletedAt = Schema::connection($connection)->hasColumn('customer_groups', 'deleted_at');
        $written = 0;

        foreach ($this->groups() as $group) {
            $payload = array_intersect_key([
                'business_id' => $businessId,
                'name' => $group['name'],
                'amount' => 0,
                'price_calculation_type' => $group['price_calculation_type'],
                'selling_price_group_id' => null,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ], array_flip($columns));

            if (! array_key_exists('name', $payload)) {
                continue;
            }

            $query = DB::connection($connection)->table('customer_groups')
                ->where('name', $group['name']);

            if (array_key_exists('business_id', $payload)) {
                $query->where('business_id', $businessId);
            }

            if ($hasDeletedAt) {
                $query->whereNull('deleted_at');
            }

            if ($query->exists()) {
                continue;
            }

            DB::connection($connection)->table('customer_groups')->insert($payload);
            $written++;
        }

        return $written;
    }

    protected function groups(): array
    {
        return [
            ['name' => 'រំលស់', 'price_calculation_type' => 'percentage'],
            ['name' => 'អ៊ីអន', 'price_calculation_type' => 'percentage'],
            ['name' => 'General', 'price_calculation_type' => 'percentage'],
        ];
    }
}