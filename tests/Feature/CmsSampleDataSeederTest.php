<?php

namespace Tests\Feature;

use Database\Seeders\CmsSampleDataSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\LoanManagement\Services\BusinessSettingsService;
use PHPUnit\Framework\TestCase;

class CmsSampleDataSeederTest extends TestCase
{
    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        config(['database.connections.mysql_loan' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'session.driver' => 'array']);
        DB::purge('mysql_loan');
        $this->storage = sys_get_temp_dir().'/cms-samples-'.bin2hex(random_bytes(5));
        $app->useStoragePath($this->storage);
        Schema::connection('mysql_loan')->create('loan_products', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('sku');
            $table->decimal('selling_price', 18, 2); $table->decimal('cost_price', 18, 2);
            $table->integer('qty_available'); $table->text('meta_json'); $table->timestamps(); $table->softDeletes();
        });
    }

    protected function tearDown(): void
    {
        app('files')->deleteDirectory($this->storage);
        DB::disconnect('mysql_loan');
        parent::tearDown();
    }

    public function testFullSamplesFillEmptyContentAndCanBeRepeatedWithoutOverwritingEdits(): void
    {
        BusinessSettingsService::save(['home_cms' => ['contact_phone' => '', 'contact_address' => 'Custom Address', 'hero' => false]]);
        $seeder = new CmsSampleDataSeeder;
        $seeder->run();
        $cms = BusinessSettingsService::get()['home_cms'];
        $this->assertNotEmpty($cms['contact_phone']);
        $this->assertSame('showroom@example.com', $cms['contact_email']);
        $this->assertSame('Custom Address', $cms['contact_address']);
        $this->assertFalse($cms['hero']);
        $this->assertSame('managed', $cms['brands_source']);
        $this->assertCount(8, $cms['brands_items']);
        $this->assertNotEmpty($cms['brands_items'][4]['website_url']);
        $this->assertSame(3, DB::connection('mysql_loan')->table('loan_products')->count());
        $asus = DB::connection('mysql_loan')->table('loan_products')->where('sku', 'CMS-SAMPLE-ASUS-15')->first();
        $this->assertTrue(json_decode($asus->meta_json, true)['is_sample']);
        $this->assertSame(0, $asus->qty_available);
        DB::connection('mysql_loan')->table('loan_products')->where('id', $asus->id)->update(['selling_price' => 600, 'qty_available' => 5]);
        BusinessSettingsService::save(['home_cms' => array_merge($cms, ['contact_phone' => 'Custom Phone'])]);
        $seeder->run();
        $this->assertSame(3, DB::connection('mysql_loan')->table('loan_products')->count());
        $this->assertSame(600.0, (float) DB::connection('mysql_loan')->table('loan_products')->where('id', $asus->id)->value('selling_price'));
        $this->assertSame(5, DB::connection('mysql_loan')->table('loan_products')->where('id', $asus->id)->value('qty_available'));
        $this->assertSame('Custom Phone', BusinessSettingsService::get()['home_cms']['contact_phone']);
    }
}
