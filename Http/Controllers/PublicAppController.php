<?php

namespace Modules\LoanManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\LoanManagement\Entities\LoanCustomer;
use Modules\LoanManagement\Services\BusinessSettingsService;
use Modules\LoanManagement\Services\LoanCustomerService;

class PublicAppController extends Controller
{
    use ApiResponseTrait;

    public function home()
    {
        if (! BusinessSettingsService::isCmsEnabled()) {
            return Route::has('login')
                ? redirect()->route('login')
                : redirect('/login');
        }

        return view('loanmanagement::public.home', [
            'settings' => BusinessSettingsService::get(),
            'products' => $this->catalogProducts(),
        ]);
    }

    public function register()
    {
        return view('loanmanagement::public.register', [
            'settings' => BusinessSettingsService::get(),
        ]);
    }

    public function storeRegistration(Request $request, LoanCustomerService $customers)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'khmer_name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:50',
            'telegram' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:1000',
            'password' => 'required|string|min:8|confirmed',
            'installment_items' => 'nullable|string|max:10000',
        ]);

        $phone = trim((string) $data['phone']);
        $exists = Schema::connection('mysql_loan')->hasTable('loan_customers')
            && DB::connection('mysql_loan')->table('loan_customers')->where('phone', $phone)->exists();
        if ($exists) {
            return back()
                ->withErrors(['phone' => 'This phone number is already registered. Please login or contact support.'])
                ->withInput($request->except('password', 'password_confirmation'));
        }

        $installmentNote = $this->installmentRequestNote((string) ($data['installment_items'] ?? ''));
        unset($data['installment_items']);

        $customerId = $customers->create(array_merge($data, [
            'phone' => $phone,
            'login_phone' => $phone,
            'username' => $phone,
            'can_login' => 1,
            'status' => 'active',
            'customer_type' => 'public_registration',
            'note' => $installmentNote,
        ]));

        $customer = LoanCustomer::query()->find($customerId);
        if ($customer) {
            Auth::guard('customer_loan')->login($customer);
            $request->session()->regenerate();
        }

        return redirect()
            ->route('loan-management.public.customer-dashboard')
            ->with('status', 'Registration complete. Welcome to your customer dashboard.');
    }

    public function customerLogin()
    {
        $demoCustomers = LoanCustomer::query()
            ->where('can_login', 1)
            ->where('status', 'active')
            ->select('id', 'name', 'phone', 'login_phone', 'username')
            ->take(3)
            ->get();

        return view('loanmanagement::public.customer_login', [
            'settings' => BusinessSettingsService::get(),
            'demoCustomers' => $demoCustomers,
        ]);
    }

    public function customerLoginStore(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        $login = trim((string) $credentials['login']);
        $customer = LoanCustomer::query()
            ->where(function ($q) use ($login) {
                $q->where('username', $login)
                    ->orWhere('phone', $login)
                    ->orWhere('login_phone', $login);
            })
            ->where('can_login', 1)
            ->where('status', 'active')
            ->first();

        if (! $customer || ! Auth::guard('customer_loan')->attempt(['id' => $customer->id, 'password' => $credentials['password']], $request->boolean('remember'))) {
            return back()->withErrors(['login' => 'These credentials do not match our records.'])->onlyInput('login');
        }

        $request->session()->regenerate();
        $customer->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('loan-management.public.customer-dashboard'));
    }

    public function customerLogout(Request $request)
    {
        Auth::guard('customer_loan')->logout();
        if (! Auth::guard('web')->check() && ! Auth::check()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('loan-management.public.home');
    }

    public function customerDashboard()
    {
        $customer = Auth::guard('customer_loan')->user();
        if (! $customer) {
            return redirect()->route('loan-management.public.customer-login');
        }

        $loans = DB::connection('mysql_loan')->table('loans')
            ->where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $payments = DB::connection('mysql_loan')->table('loan_payments')
            ->where('customer_id', $customer->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('loanmanagement::public.customer_dashboard', [
            'settings' => BusinessSettingsService::get(),
            'customer' => $customer,
            'loans' => $loans,
            'payments' => $payments,
        ]);
    }

    protected function catalogProducts(): array
    {
        if (Schema::hasTable('products')) {
            $query = DB::table('products as p');

            if (Schema::hasTable('variations')) {
                $query->leftJoin('variations as v', 'v.product_id', '=', 'p.id');
            }

            $selects = [
                'p.id as product_id',
                'p.name as name',
            ];

            $selects[] = Schema::hasColumn('products', 'sku') ? 'p.sku as sku' : DB::raw('NULL as sku');
            $selects[] = Schema::hasColumn('products', 'image') ? 'p.image as image' : DB::raw('NULL as image');
            $selects[] = Schema::hasTable('variations') ? 'v.id as variation_id' : DB::raw('NULL as variation_id');
            $selects[] = Schema::hasTable('variations') && Schema::hasColumn('variations', 'name') ? 'v.name as variation_name' : DB::raw('NULL as variation_name');
            $selects[] = Schema::hasTable('variations') && Schema::hasColumn('variations', 'sub_sku') ? 'v.sub_sku as sub_sku' : DB::raw('NULL as sub_sku');
            $selects[] = Schema::hasTable('variations') && Schema::hasColumn('variations', 'default_sell_price') ? 'v.default_sell_price as price' : DB::raw('0 as price');

            if (Schema::hasColumn('products', 'not_for_selling')) {
                $query->where(function ($q) {
                    $q->whereNull('p.not_for_selling')->orWhere('p.not_for_selling', 0);
                });
            }

            if (Schema::hasColumn('products', 'is_inactive')) {
                $query->where(function ($q) {
                    $q->whereNull('p.is_inactive')->orWhere('p.is_inactive', 0);
                });
            }

            return $query
                ->select($selects)
                ->orderByDesc('p.id')
                ->limit(12)
                ->get()
                ->map(fn ($row) => $this->formatCatalogProduct($row))
                ->values()
                ->all();
        }

        if (! Schema::connection('mysql_loan')->hasTable('loan_products')) {
            return [];
        }

        return DB::connection('mysql_loan')->table('loan_products')
            ->selectRaw('id as product_id, name, sku, NULL as image, main_variation_id as variation_id, NULL as variation_name, sku as sub_sku, selling_price as price')
            ->orderByDesc('id')
            ->limit(12)
            ->get()
            ->map(fn ($row) => $this->formatCatalogProduct($row))
            ->values()
            ->all();
    }

    protected function formatCatalogProduct(object $row): array
    {
        $name = trim((string) ($row->name ?? 'Product'));
        $variation = trim((string) ($row->variation_name ?? ''));
        if ($variation !== '' && strtolower($variation) !== 'dummy') {
            $name .= ' - '.$variation;
        }

        return [
            'id' => (int) ($row->variation_id ?: $row->product_id),
            'product_id' => (int) ($row->product_id ?? 0),
            'variation_id' => (int) ($row->variation_id ?? 0),
            'name' => $name,
            'sku' => trim((string) ($row->sub_sku ?? $row->sku ?? '')),
            'price' => round((float) ($row->price ?? 0), 2),
            'image_url' => $this->productImageUrl((string) ($row->image ?? '')),
        ];
    }

    protected function productImageUrl(string $image): ?string
    {
        $image = trim($image);
        if ($image === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }

        foreach (['uploads/img/'.$image, 'uploads/products/'.$image, $image] as $path) {
            $path = ltrim($path, '/');
            if (is_file(public_path($path))) {
                return asset($path);
            }
        }

        return null;
    }

    protected function installmentRequestNote(string $itemsJson): ?string
    {
        $items = json_decode($itemsJson, true);
        if (! is_array($items) || empty($items)) {
            return null;
        }

        $lines = ['Public installment request:'];
        $total = 0;
        foreach (array_slice($items, 0, 20) as $item) {
            if (! is_array($item)) {
                continue;
            }

            $name = trim((string) ($item['name'] ?? 'Product'));
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $price = round((float) ($item['price'] ?? 0), 2);
            $total += $qty * $price;
            $sku = trim((string) ($item['sku'] ?? ''));
            $lines[] = '- '.$name.($sku !== '' ? ' (SKU: '.$sku.')' : '').' x'.$qty.' = '.number_format($qty * $price, 2);
        }

        $lines[] = 'Estimated total: '.number_format($total, 2);

        return implode("\n", $lines);
    }

    public function appSettings()
    {
        return $this->ok('App settings loaded', [
            'app_name' => 'LoanManagement',
            'support_chat' => true,
            'support_gps' => true,
            'support_aba_payway' => true,
            'support_file_upload' => true,
            'chat_polling_seconds' => (int) config('loanmanagement.chat.polling_interval_seconds', config('loanmanagement.chat_polling_seconds', 5)),
            'customer_api_guard' => (string) config('loanmanagement.customer_api_guard', 'customer_loan_api'),
        ]);
    }

    public function appVersion()
    {
        return $this->ok('App version loaded', [
            'module' => 'LoanManagement',
            'version' => (string) config('loanmanagement.version', '1.0.0'),
            'min_flutter_version' => '1.0.0',
        ]);
    }
}
