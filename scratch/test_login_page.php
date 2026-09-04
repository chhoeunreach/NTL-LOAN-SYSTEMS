<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/customer/login', 'GET');
$response = $app->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content length: " . strlen($response->getContent()) . "\n";
