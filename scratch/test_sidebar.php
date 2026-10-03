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

preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $res1->getContent(), $styles1);

foreach ($styles1[1] as $i => $content) {
    echo "=== STYLE BLOCK $i ===\n";
    // Check for selectors like .active, a, button, box-shadow, padding
    preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $content, $rules);
    foreach ($rules[1] as $idx => $selector) {
        $sel = trim($selector);
        $body = trim($rules[2][$idx]);
        if (
            preg_match('/(^|,|\s)(\.active|\.lm-|aside|nav|sidebar|a\b)/i', $sel) ||
            preg_match('/(box-shadow|padding|margin|line-height)/i', $body) && preg_match('/(^|,|\s)(\*|html|body|a\b)/i', $sel)
        ) {
            echo "  Selector: $sel\n    Body: $body\n";
        }
    }
}
