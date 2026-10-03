<?php

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array', 'cache.default' => 'array', 'view.compiled' => sys_get_temp_dir()]);
view()->share('errors', new Illuminate\Support\ViewErrorBag);
auth()->setUser(new class(['id' => 92001, 'name' => 'Preview Staff']) extends Illuminate\Auth\GenericUser implements Illuminate\Contracts\Auth\Access\Authorizable {
    public function can($ability, $arguments = []): bool { return true; }
});
session()->put('user.language', 'en');
Illuminate\Support\Facades\Cache::put('loan_management.sidebar_badges', [], 600);
file_put_contents(__DIR__.'/cms-editor-fixture.html', (new Modules\LoanManagement\Http\Controllers\SettingsController)->cms()->render());
echo "Rendered CMS editor fixture.\n";
