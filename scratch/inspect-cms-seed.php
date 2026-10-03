<?php
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$settings = Modules\LoanManagement\Services\BusinessSettingsService::get();
$result = ['business_name' => $settings['business_name'], 'contact' => array_intersect_key($settings['home_cms'], array_flip(['contact_phone', 'contact_email', 'contact_address', 'contact_hours'])),
    'sections' => array_intersect_key($settings['home_cms'], array_flip(['hero', 'brands', 'guide', 'catalog', 'contact']))];
try {
    $result['products_table'] = Illuminate\Support\Facades\Schema::connection('mysql_loan')->hasTable('loan_products');
    if ($result['products_table']) $result['product_count'] = Illuminate\Support\Facades\DB::connection('mysql_loan')->table('loan_products')->whereNull('deleted_at')->count();
} catch (Throwable $exception) {
    $result['database_available'] = false;
}
echo json_encode($result), PHP_EOL;
