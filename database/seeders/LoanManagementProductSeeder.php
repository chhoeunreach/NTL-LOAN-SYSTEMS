<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds a small starting catalog so the public product page, the catalog
 * dropdowns and the brand filter (LoanProductController) are not empty on a
 * fresh install. Rows are keyed by sku, so re-seeding is a no-op.
 */
class LoanManagementProductSeeder extends Seeder
{
    public function run(): void
    {
        $connection = (string) config('loanmanagement.db_connection', 'mysql_loan');

        if (! Schema::connection($connection)->hasTable('loan_products')) {
            $this->command?->warn("loan_products table missing on connection [{$connection}], skipping products.");

            return;
        }

        $columns = Schema::connection($connection)->getColumnListing('loan_products');
        $hasSku = Schema::connection($connection)->hasColumn('loan_products', 'sku');
        $written = 0;

        foreach ($this->products() as $product) {
            $sku = $product['sku'];

            if ($hasSku && DB::connection($connection)->table('loan_products')->where('sku', $sku)->exists()) {
                continue;
            }

            $meta = $product['meta'];
            unset($product['meta']);

            $payload = array_merge($product, [
                'cost_price' => 0,
                'qty_available' => 0,
                'meta_json' => json_encode($meta),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::connection($connection)->table('loan_products')
                ->insert(array_intersect_key($payload, array_flip($columns)));

            $written++;
        }

        $this->command?->info("Default loan products seeded ({$written} row(s) written).");
    }

    public static function products(): array
    {
        return [
            [
                'sku' => 'LOAN-PHONE-SAMSUNG-A05',
                'name' => 'Samsung Galaxy A05',
                'selling_price' => 175,
                'meta' => [
                    'brand' => 'Samsung',
                    'model' => 'Galaxy A05',
                    'category' => 'Mobile Phones',
                    'min_down_payment_percent' => 10,
                    'allowed_durations' => [3, 6, 12],
                    'description' => 'Entry level Android smartphone available on monthly installments.',
                ],
            ],
            [
                'sku' => 'LOAN-PHONE-IPHONE-13',
                'name' => 'Apple iPhone 13',
                'selling_price' => 780,
                'meta' => [
                    'brand' => 'Apple',
                    'model' => 'iPhone 13',
                    'category' => 'Mobile Phones',
                    'min_down_payment_percent' => 20,
                    'allowed_durations' => [6, 12, 24],
                    'description' => '128GB unlocked iPhone 13 available with a down payment.',
                ],
            ],
            [
                'sku' => 'LOAN-LAPTOP-ASUS-VIVOBOOK-15',
                'name' => 'ASUS Vivobook 15',
                'selling_price' => 520,
                'meta' => [
                    'brand' => 'ASUS',
                    'model' => 'Vivobook 15',
                    'category' => 'Computers',
                    'min_down_payment_percent' => 15,
                    'allowed_durations' => [6, 12, 24],
                    'description' => '15.6 inch everyday laptop for study and office work.',
                ],
            ],
            [
                'sku' => 'LOAN-TV-HIKVISION-43',
                'name' => 'HIKVISION 43" Smart TV',
                'selling_price' => 340,
                'meta' => [
                    'brand' => 'HIKVISION',
                    'model' => '43 inch Smart TV',
                    'category' => 'Televisions',
                    'min_down_payment_percent' => 10,
                    'allowed_durations' => [3, 6, 12],
                    'description' => '43 inch Smart TV with app casting and HDMI ports.',
                ],
            ],
            [
                'sku' => 'LOAN-PRINTER-EPSON-L3250',
                'name' => 'EPSON EcoTank L3250',
                'selling_price' => 165,
                'meta' => [
                    'brand' => 'EPSON',
                    'model' => 'EcoTank L3250',
                    'category' => 'Printers',
                    'min_down_payment_percent' => 10,
                    'allowed_durations' => [3, 6, 12],
                    'description' => 'Print, scan and copy WiFi printer with refillable ink tanks.',
                ],
            ],
            [
                'sku' => 'LOAN-HOME-WASHING-MACHINE',
                'name' => 'Inverter Washing Machine 8kg',
                'selling_price' => 285,
                'meta' => [
                    'brand' => 'Generic',
                    'model' => 'Inverter 8kg Front Load',
                    'category' => 'Home Appliances',
                    'min_down_payment_percent' => 15,
                    'allowed_durations' => [6, 12, 24],
                    'description' => 'Front load washing machine with inverter motor and warranty.',
                ],
            ],
        ];
    }
}