<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$conn = DB::connection('mysql_loan');
echo "loans count: " . $conn->table('loans')->count() . "\n";
$loan = $conn->table('loans')->first();
if ($loan) {
    print_r($loan);
}

$defConn = DB::connection();
echo "default conn database: " . $defConn->getDatabaseName() . "\n";
echo "default conn contacts count: " . ($defConn->getSchemaBuilder()->hasTable('contacts') ? $defConn->table('contacts')->count() : 'no contacts table') . "\n";
if ($defConn->getSchemaBuilder()->hasTable('users')) {
    echo "default conn users: " . $defConn->table('users')->count() . "\n";
    print_r($defConn->table('users')->select('id', 'username', 'email')->take(3)->get());
}
