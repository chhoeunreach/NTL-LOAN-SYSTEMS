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
$request = Illuminate\Http\Request::create('/loan-management/admin-loan');
$request->setLaravelSession(session()->driver());
$route = new Illuminate\Routing\Route(['GET'], 'loan-management/admin-loan', ['as' => 'loan-management.admin-loan']);
$route->bind($request);
$request->setRouteResolver(fn () => $route);
$app->instance('request', $request);
$html = view('loanmanagement::admin_loan.index', [
    'filters' => ['start_year' => 2026, 'end_year' => 2026, 'location_id' => '', 'search' => ''],
    'payload' => ['adminRows' => [], 'adminMonthlyRows' => []],
    'locations' => [], 'isKhmer' => false,
])->render();
file_put_contents(__DIR__.'/admin-layout-fixture.html', $html);
echo "Rendered shared admin layout fixture.\n";
