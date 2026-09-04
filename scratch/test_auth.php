<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Modules\LoanManagement\Entities\LoanCustomer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

$login = '010111001';
$password = 'password';

$customer = LoanCustomer::query()
    ->where(function ($q) use ($login) {
        $q->where('username', $login)
            ->orWhere('phone', $login)
            ->orWhere('login_phone', $login);
    })
    ->where('can_login', 1)
    ->where('status', 'active')
    ->first();

echo "Customer found: " . ($customer ? $customer->name : 'No') . "\n";
if ($customer) {
    echo "Password check: " . (Hash::check($password, $customer->password) ? 'MATCH!' : 'FAIL') . "\n";
    $attempt = Auth::guard('customer_loan')->attempt(['id' => $customer->id, 'password' => $password]);
    echo "Guard attempt: " . ($attempt ? 'SUCCESS!' : 'FAILED') . "\n";
}
