<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\LoginController;
use Database\Seeders\PortalDemoAccountSeeder;
use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Modules\LoanManagement\Http\Controllers\PublicAppController;
use Modules\LoanManagement\Services\BusinessSettingsService;
use Modules\LoanManagement\Services\PortalDemoService;
use PHPUnit\Framework\TestCase;

class PortalLoginTest extends TestCase
{
    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        config(['database.default' => 'sqlite', 'database.connections.sqlite' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.connections.mysql_loan' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'session.driver' => 'array', 'cache.default' => 'array', 'view.compiled' => sys_get_temp_dir()]);
        DB::purge('sqlite'); DB::purge('mysql_loan');
        $this->storage = sys_get_temp_dir().'/portal-login-'.bin2hex(random_bytes(5));
        $app->useStoragePath($this->storage);
        BusinessSettingsService::save(['customer_login_enabled' => true, 'demo_customer_login_enabled' => true]);
        view()->share('errors', new \Illuminate\Support\ViewErrorBag);
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('username'); $table->string('email');
            $table->string('password'); $table->string('status'); $table->boolean('allow_login'); $table->rememberToken();
            $table->timestamps();
        });
        Schema::connection('mysql_loan')->create('loan_customers', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('username'); $table->string('phone'); $table->string('login_phone');
            $table->string('password'); $table->string('status'); $table->boolean('can_login'); $table->rememberToken();
            $table->string('email')->nullable();
            $table->timestamp('last_login_at')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'Sample Staff', 'username' => 'staff', 'email' => 'staff@example.com', 'password' => Hash::make('secret-test'), 'status' => 'active', 'allow_login' => true]);
        DB::connection('mysql_loan')->table('loan_customers')->insert(['id' => 2, 'name' => 'Sample Customer', 'username' => 'customer', 'phone' => '012345678', 'login_phone' => '012345678', 'password' => Hash::make('secret-test'), 'status' => 'active', 'can_login' => true]);
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory($this->storage);
        DB::disconnect('sqlite'); DB::disconnect('mysql_loan');
        parent::tearDown();
    }

    public function testDemoRequiresDesignatedAccountAndVerifiedPassword(): void
    {
        config(['loanmanagement.demo_customer_identifier' => 'customer', 'loanmanagement.demo_customer_password' => 'wrong']);
        $this->assertNull(\Modules\LoanManagement\Services\PortalDemoService::credentials('customer'));
        config(['loanmanagement.demo_customer_password' => 'secret-test']);
        $this->assertSame('customer', \Modules\LoanManagement\Services\PortalDemoService::credentials('customer')['login']);
        BusinessSettingsService::save(['demo_customer_login_enabled' => false]);
        $this->assertNull(\Modules\LoanManagement\Services\PortalDemoService::credentials('customer'));
    }

    public function testCustomerLoginPageFollowsDemoVisibilitySetting(): void
    {
        config(['loanmanagement.demo_customer_identifier' => 'customer', 'loanmanagement.demo_customer_password' => 'secret-test']);

        $response = (new PublicAppController)->customerLogin();
        $this->assertSame('customer', $response->getData()['demoLogin']['login']);

        BusinessSettingsService::save(['demo_customer_login_enabled' => false]);

        $response = (new PublicAppController)->customerLogin();
        $this->assertNull($response->getData()['demoLogin']);
    }

    public function testDisabledCustomerPortalBlocksCustomerPages(): void
    {
        BusinessSettingsService::save(['customer_login_enabled' => false]);
        auth()->guard('customer_loan')->setUser(new GenericUser(['id' => 2, 'remember_token' => null]));

        $response = (new PublicAppController)->customerDashboard();

        $this->assertFalse(auth()->guard('customer_loan')->check());
        $this->assertStringContainsString('/', $response->getTargetUrl());
        $this->assertSame('Customer login portal is currently disabled by administrator.', session('status'));
    }

    public function testLoginPostRoutesHaveRateLimits(): void
    {
        foreach (['/login', '/customer/login'] as $path) {
            $route = app('router')->getRoutes()->match(Request::create($path, 'POST'));
            $this->assertContains('throttle:6,1', $route->gatherMiddleware());
        }
    }

    public function testModalLoginAuthenticatesAndReturnsDashboardRedirect(): void
    {
        $request = $this->request('/customer/login', ['login' => 'customer', 'password' => 'secret-test']);
        $request->headers->set('Accept', 'application/json');
        $response = (new PublicAppController)->customerLoginStore($request);
        $this->assertSame(route('loan-management.public.customer-dashboard'), $response->getData(true)['redirect']);
        $this->assertSame(2, auth()->guard('customer_loan')->id());
        $this->assertNotNull(DB::connection('mysql_loan')->table('loan_customers')->where('id', 2)->value('last_login_at'));
    }

    public function testModalLoginRejectsWrongPasswordAndDisabledPortal(): void
    {
        $request = $this->request('/customer/login', ['login' => 'customer', 'password' => 'incorrect']);
        $request->headers->set('Accept', 'application/json');
        try {
            (new PublicAppController)->customerLoginStore($request);
            $this->fail('Expected incorrect password to be rejected');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('login', $exception->errors());
        }
        $this->assertFalse(auth()->guard('customer_loan')->check());
        BusinessSettingsService::save(['customer_login_enabled' => false]);
        $this->assertSame(403, (new PublicAppController)->customerLoginStore($request)->getStatusCode());
    }

    public function testModalLoginAcceptsCustomerEmail(): void
    {
        DB::connection('mysql_loan')->table('loan_customers')->where('id', 2)->update(['email' => 'customer@example.com']);
        $request = $this->request('/customer/login', ['login' => 'customer@example.com', 'password' => 'secret-test']);
        $request->headers->set('Accept', 'application/json');
        $this->assertSame(200, (new PublicAppController)->customerLoginStore($request)->getStatusCode());
        $this->assertSame(2, auth()->guard('customer_loan')->id());
    }

    private function request(string $path, array $data): Request
    {
        $request = Request::create($path, 'POST', $data);
        $request->setLaravelSession(session()->driver());
        app()->instance('request', $request);
        app('url')->setRequest($request);
        return $request;
    }

    public function testStaffSignInUsesBackendAndRememberToken(): void
    {
        $request = $this->request('/login', ['email' => 'staff', 'password' => 'secret-test', 'remember' => '1']);
        $response = (new LoginController)->login($request);
        $this->assertSame(1, auth()->id());
        $this->assertStringContainsString('/loan-management/dashboard', $response->getTargetUrl());
        $this->assertNotEmpty(DB::table('users')->value('remember_token'));
    }

    public function testDisabledStaffCannotLoginAndFailedLoginPreservesCustomerSession(): void
    {
        auth()->guard('customer_loan')->setUser(new GenericUser(['id' => 2]));
        DB::table('users')->update(['allow_login' => false]);
        (new LoginController)->login($this->request('/login', ['email' => 'staff', 'password' => 'secret-test']));
        $this->assertFalse(auth()->check());
        $this->assertSame(2, auth()->guard('customer_loan')->id());
    }

    public function testCustomerSignInSwitchesPortalOnlyAfterSuccess(): void
    {
        auth()->setUser(new GenericUser(['id' => 1, 'remember_token' => null]));
        session()->put('url.intended', '/loan-management/users');
        $request = $this->request('/customer/login', ['login' => 'customer', 'password' => 'wrong']);
        (new PublicAppController)->customerLoginStore($request);
        $this->assertSame(1, auth()->id());
        $this->assertFalse(auth()->guard('customer_loan')->check());
        $response = (new PublicAppController)->customerLoginStore($this->request('/customer/login', ['login' => '012345678', 'password' => 'secret-test', 'remember' => '1']));
        $this->assertFalse(auth()->check());
        $this->assertSame(2, auth()->guard('customer_loan')->id());
        $this->assertStringContainsString('/customer/dashboard', $response->getTargetUrl());
        $this->assertNotEmpty(DB::connection('mysql_loan')->table('loan_customers')->value('last_login_at'));
    }

    public function testDisabledCustomerCannotSignIn(): void
    {
        DB::connection('mysql_loan')->table('loan_customers')->update(['can_login' => false]);
        (new PublicAppController)->customerLoginStore($this->request('/customer/login', ['login' => 'customer', 'password' => 'secret-test']));
        $this->assertFalse(auth()->guard('customer_loan')->check());
    }

    public function testBothTemplatesHaveRealActionsAndNoSimulatedLogin(): void
    {
        foreach (['admin' => 'auth.login', 'customer' => 'loanmanagement::public.customer_login'] as $portal => $view) {
            $html = view($view, ['settings' => BusinessSettingsService::get()])->render();
            $this->assertStringContainsString('name="_token"', $html);
            $this->assertStringContainsString('name="'.($portal === 'admin' ? 'email' : 'login').'"', $html);
            $this->assertStringContainsString('name="remember"', $html);
            $this->assertStringContainsString('id="togglePassword"', $html);
            $this->assertStringNotContainsString('handleLogin(', $html);
            $this->assertStringNotContainsString('href="#"', $html);
        }
    }

    public function testDemoSeederCreatesTheDesignatedAccountSoPanelAppears(): void
    {
        config(['loanmanagement.demo_customer_identifier' => '010111001', 'loanmanagement.demo_customer_password' => 'secret-test']);

        DB::connection('mysql_loan')->table('loan_customers')->delete();

        $this->assertNull(PortalDemoService::credentials('customer'), 'No demo account exists yet, so the panel must stay hidden.');

        (new PortalDemoAccountSeeder)->run();

        $credentials = PortalDemoService::credentials('customer');
        $this->assertNotNull($credentials, 'Seeder must create the account the demo panel depends on.');
        $this->assertSame('010111001', $credentials['login']);
        $this->assertSame('secret-test', $credentials['password']);

        $customer = DB::connection('mysql_loan')->table('loan_customers')->where('login_phone', '010111001')->first();
        $this->assertSame(1, (int) $customer->can_login);
        $this->assertSame('active', $customer->status);
        $this->assertTrue(Hash::check('secret-test', $customer->password), 'Password must be stored hashed, not double-hashed.');

        $html = view('loanmanagement::public.customer_login', [
            'settings' => BusinessSettingsService::get(),
            'demoLogin' => $credentials,
        ])->render();
        $this->assertStringContainsString('id="fillDemo"', $html);
        $this->assertStringContainsString('010111001', $html);
    }

    public function testDemoSeederIsIdempotentAndDoesNotDuplicateAccounts(): void
    {
        config(['loanmanagement.demo_customer_identifier' => 'customer', 'loanmanagement.demo_customer_password' => 'secret-test']);

        $seeder = new PortalDemoAccountSeeder;
        $seeder->run();
        $seeder->run();

        $this->assertSame(1, DB::connection('mysql_loan')->table('loan_customers')->where('login_phone', 'customer')->count());
        $this->assertSame('customer', PortalDemoService::credentials('customer')['login']);
    }
}
