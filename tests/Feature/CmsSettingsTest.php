<?php

namespace Tests\Feature;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Modules\LoanManagement\Http\Controllers\SettingsController;
use Modules\LoanManagement\Services\BusinessSettingsService;
use Modules\LoanManagement\Services\CmsHomeService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CmsSettingsTest extends TestCase
{
    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        config(['cache.default' => 'array', 'session.driver' => 'array', 'view.compiled' => sys_get_temp_dir()]);
        $this->storage = sys_get_temp_dir().'/cms-settings-'.bin2hex(random_bytes(5));
        $app->useStoragePath($this->storage);
        view()->share('errors', new \Illuminate\Support\ViewErrorBag);
        auth()->setUser(new class(['id' => 92001, 'name' => 'Sample Staff']) extends GenericUser implements \Illuminate\Contracts\Auth\Access\Authorizable {
            public function can($ability, $arguments = []): bool { return true; }
        });
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory($this->storage);
        parent::tearDown();
    }

    public function testCmsSavePersistsSectionsAndPreservesOtherSettings(): void
    {
        BusinessSettingsService::save(['business_name' => 'Sample Business']);
        $cms = CmsHomeService::normalize(['catalog' => false, 'menu_about' => false, 'label_products' => 'Our Shop', 'contact_email' => 'support@example.com']);
        $request = Request::create('/loan-management/settings/cms', 'POST', ['home_headline' => 'Sample Headline', 'home_subtitle' => 'Sample Subtitle', 'home_body' => 'Sample About', 'home_cms' => $cms]);
        $request->setLaravelSession(session()->driver()); app()->instance('request', $request);
        $response = (new SettingsController)->updateCms($request);
        $saved = BusinessSettingsService::get();
        $this->assertFalse($saved['home_cms']['catalog']);
        $this->assertFalse($saved['home_cms']['menu_about']);
        $this->assertSame('Our Shop', $saved['home_cms']['label_products']);
        $this->assertSame('support@example.com', $saved['home_cms']['contact_email']);
        $this->assertSame('Sample Business', $saved['business_name']);
        $this->assertStringContainsString('/settings/cms', $response->getTargetUrl());
        BusinessSettingsService::save(['business_name' => 'Updated Business']);
        $this->assertFalse(BusinessSettingsService::get()['home_cms']['catalog']);
        BusinessSettingsService::save(['home_hero_path' => 'loan-management/cms/sample.jpg']);
        BusinessSettingsService::save(['home_hero_path' => null]);
        $this->assertNull(BusinessSettingsService::get()['home_hero_path']);
    }

    public function testViewOnlyStaffCannotModifyCms(): void
    {
        auth()->setUser(new class(['id' => 92002]) extends GenericUser implements \Illuminate\Contracts\Auth\Access\Authorizable {
            public function can($ability, $arguments = []): bool { return $ability === 'loan_management.view'; }
        });
        try {
            (new SettingsController)->updateCms(Request::create('/test', 'POST'));
            $this->fail('Expected permission denial');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function testHeroUploadCanBeReplacedAndRestored(): void
    {
        config(['filesystems.disks.public.root' => $this->storage.'/public']);
        $data = ['home_headline' => 'Sample Headline', 'home_subtitle' => 'Sample Subtitle', 'home_body' => '', 'home_cms' => CmsHomeService::normalize([])];
        $upload = \Illuminate\Http\UploadedFile::fake()->createWithContent('hero.jpg', file_get_contents(module_path('LoanManagement', 'Resources/assets/cms-home/hero.jpg')));
        $request = Request::create('/loan-management/settings/cms', 'POST', $data, [], ['home_hero' => $upload]);
        $request->setLaravelSession(session()->driver()); app()->instance('request', $request);
        (new SettingsController)->updateCms($request);
        $path = BusinessSettingsService::get()['home_hero_path'];
        $this->assertStringStartsWith('loan-management/cms/', $path);
        $this->assertTrue(\Illuminate\Support\Facades\Storage::disk('public')->exists($path));
        $request = Request::create('/loan-management/settings/cms', 'POST', $data + ['remove_home_hero' => '1']);
        $request->setLaravelSession(session()->driver()); app()->instance('request', $request);
        (new SettingsController)->updateCms($request);
        $this->assertNull(BusinessSettingsService::get()['home_hero_path']);
        $this->assertFalse(\Illuminate\Support\Facades\Storage::disk('public')->exists($path));
    }

    public function testSettingsViewContainsControlsAndImageUpload(): void
    {
        session()->put('user.language', 'en');
        \Illuminate\Support\Facades\Cache::put('loan_management.sidebar_badges', [], 600);
        $html = (new SettingsController)->cms()->render();
        foreach (CmsHomeService::fields() as $key => $field) {
            $this->assertStringContainsString('name="home_cms['.$key.']"', $html);
        }
        $this->assertStringContainsString('name="home_hero"', $html);
        $this->assertStringContainsString('multipart/form-data', $html);
    }
}
