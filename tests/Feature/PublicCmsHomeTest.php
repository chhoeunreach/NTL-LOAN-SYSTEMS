<?php

namespace Tests\Feature;

use Illuminate\Auth\GenericUser;
use Illuminate\Contracts\Console\Kernel;
use Modules\LoanManagement\Http\Controllers\PublicAppController;
use PHPUnit\Framework\TestCase;

class PublicCmsHomeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        config(['session.driver' => 'array', 'cache.default' => 'array', 'view.compiled' => sys_get_temp_dir()]);
        auth()->guard('web')->forgetUser();
        auth()->guard('customer_loan')->forgetUser();
    }

    public function renderHome(array $cms = []): string
    {
        return view('loanmanagement::public.home', [
            'settings' => ['home_cms' => $cms, 'home_headline' => 'Shop <safely>', 'home_subtitle' => 'Sample subtitle', 'home_body' => 'Sample about text'],
            'products' => [['id' => 1, 'name' => 'Sample <Laptop>', 'price' => 1200, 'category' => 'Computers', 'brand' => 'Sample Brand']],
            'categories' => ['Computers'], 'brands' => ['Sample Brand'],
        ])->render();
    }

    public function testTemplatePreservesLiveCatalogAndRealGuestLinks(): void
    {
        $html = $this->renderHome();
        foreach (['Products Catalog', 'Installment Guide', 'publicMenuToggle', 'catalogSearchInput', 'cartApply', 'Sample &lt;Laptop&gt;', 'Shop &lt;safely&gt;', 'Sample about text'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        $this->assertStringContainsString(route('loan-management.public.register'), $html);
        $this->assertStringContainsString(route('loan-management.public.customer-login'), $html);
        $this->assertStringNotContainsString('hero-card', $html);
        $this->assertStringNotContainsString('Apply Installment Installment', $html);
    }

    public function testCustomerDashboardAndRequestLinksRemainAvailable(): void
    {
        auth()->guard('customer_loan')->setUser(new GenericUser(['id' => 123, 'name' => 'Sample Customer', 'phone' => '123', 'username' => 'sample', 'customer_photo_url' => null]));
        $html = $this->renderHome();
        $this->assertStringContainsString('Go to My Dashboard', $html);
        $this->assertStringContainsString(route('loan-management.public.customer-loan-request', ['product_id' => 1]), $html);
        $this->assertStringContainsString('customerProfileToggle', $html);
    }

    public function testHeroAssetIsServedAsAnImage(): void
    {
        $response = (new PublicAppController)->homeImage();
        $this->assertStringStartsWith('image/', $response->getFile()->getMimeType());
        $this->assertGreaterThan(1000, $response->getFile()->getSize());
    }

    public function testHiddenSectionsRemoveMenuLinksAndCartActions(): void
    {
        $html = $this->renderHome(['hero' => false, 'guide' => false, 'about' => false, 'contact' => false, 'cart' => false]);
        foreach (['id="home"', 'href="#home"', 'href="#how"', 'href="#about"', 'href="#contact"', 'id="cart"', 'data-product='] as $text) {
            $this->assertStringNotContainsString($text, $html);
        }
        $this->assertStringContainsString('without-cart', $html);
        $this->assertStringContainsString('id="products"', $html);
    }

    public function testCmsContentAndMenuLabelsAreRendered(): void
    {
        $html = $this->renderHome(['label_products' => 'Our Shop', 'contact_phone' => '012 123 456', 'contact_email' => 'support@example.com', 'contact_address' => 'Sample Address', 'guide_title' => 'Sample Guide']);
        foreach (['Our Shop', 'Sample Guide', 'Sample Address', 'mailto:support@example.com', 'tel:012123456'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }
}
