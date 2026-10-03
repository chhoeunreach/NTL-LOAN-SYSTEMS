<?php

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array', 'cache.default' => 'array', 'view.compiled' => sys_get_temp_dir()]);
$product = ['id' => 7, 'variation_id' => 0, 'name' => 'ASUS Vivobook 15', 'price' => 520,
    'brand' => 'ASUS', 'category' => 'Computers', 'sku' => 'ASUS-15'];
$settings = Modules\LoanManagement\Services\BusinessSettingsService::get();
$settings['home_cms'] = array_merge($settings['home_cms'], ['hero' => true, 'assessment' => true,
    'guide' => true, 'experience' => true, 'catalog' => true, 'privacy' => true, 'brands_source' => 'managed']);
$html = view('loanmanagement::public.home', [
    'settings' => $settings,
    'products' => [$product, ['id' => 9, 'variation_id' => 0, 'name' => 'Canon Printer', 'price' => 180, 'brand' => 'Canon', 'category' => 'Printers', 'sku' => 'CANON-1'],
        ['id' => 11, 'variation_id' => 0, 'name' => 'Lenovo Laptop', 'price' => 750, 'brand' => 'Lenovo', 'category' => 'Computers', 'sku' => 'LENOVO-1']],
    'categories' => ['Computers', 'Printers'], 'brands' => ['ASUS', 'Canon', 'Lenovo'],
])->render();
file_put_contents(__DIR__.'/installment-fixture.html', $html);
echo "Rendered installment browser fixture.\n";
