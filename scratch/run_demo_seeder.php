<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$seeder = new Database\Seeders\LoanManagementDemoDataSeeder();
$seeder->run();

$customers = Illuminate\Support\Facades\DB::connection('mysql_loan')->table('loan_customers')->get();
echo "Customers count after seed: " . count($customers) . "\n";
foreach ($customers as $c) {
    echo "ID: {$c->id}, Code: {$c->customer_code}, Name: {$c->name}, Phone: {$c->phone}, Username: {$c->username}, CanLogin: {$c->can_login}\n";
}
