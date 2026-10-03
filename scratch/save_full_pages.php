<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\User::first();
\Illuminate\Support\Facades\Auth::login($user);

$httpKernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$req1 = Illuminate\Http\Request::create('/loan-management/admin-loan', 'GET');
$req1->setLaravelSession($app['session']->driver());
$res1 = $httpKernel->handle($req1);
file_put_contents(__DIR__ . '/full_admin_loan.html', $res1->getContent());

$req2 = Illuminate\Http\Request::create('/loan-management/reports/dashboard', 'GET');
$req2->setLaravelSession($app['session']->driver());
$res2 = $httpKernel->handle($req2);
file_put_contents(__DIR__ . '/full_dashboard_reports.html', $res2->getContent());

echo "Wrote full_admin_loan.html (" . strlen($res1->getContent()) . " bytes)\n";
echo "Wrote full_dashboard_reports.html (" . strlen($res2->getContent()) . " bytes)\n";
