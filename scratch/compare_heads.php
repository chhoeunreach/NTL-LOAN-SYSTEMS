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

$req2 = Illuminate\Http\Request::create('/loan-management/reports/dashboard', 'GET');
$req2->setLaravelSession($app['session']->driver());
$res2 = $httpKernel->handle($req2);

preg_match('/<head>(.*?)<\/head>/is', $res1->getContent(), $h1);
preg_match('/<head>(.*?)<\/head>/is', $res2->getContent(), $h2);

echo "HEAD 1 (Admin Loan) length: " . strlen($h1[1] ?? '') . "\n";
echo "HEAD 2 (Dashboard Reports) length: " . strlen($h2[1] ?? '') . "\n";

// Find all css links in head 1 vs head 2
preg_match_all('/<link[^>]+>/i', $h1[1] ?? '', $l1);
preg_match_all('/<link[^>]+>/i', $h2[1] ?? '', $l2);

echo "\nLinks in Head 1:\n";
foreach ($l1[0] as $l) echo "  $l\n";

echo "\nLinks in Head 2:\n";
foreach ($l2[0] as $l) echo "  $l\n";

// Find all <style> tags
preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $h1[1] ?? '', $s1);
preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $h2[1] ?? '', $s2);

echo "\nStyle tags in Head 1: " . count($s1[0]) . "\n";
echo "Style tags in Head 2: " . count($s2[0]) . "\n";
