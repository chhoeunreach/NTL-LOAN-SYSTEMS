<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\LoanManagement\Services\BusinessSettingsService;

class StandaloneSessionData
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->hasSession()) {
            $user = $request->user();
            $businessSettings = BusinessSettingsService::get();

            session()->put('business.name', session('business.name', $businessSettings['business_name'] ?? config('app.name')));
            session()->put('business.currency_symbol_placement', session('business.currency_symbol_placement', $businessSettings['currency_symbol_placement'] ?? 'before'));
            session()->put('business.currency_precision', session('business.currency_precision', $businessSettings['currency_precision'] ?? 2));
            session()->put('business.quantity_precision', session('business.quantity_precision', $businessSettings['quantity_precision'] ?? 2));
            session()->put('business.date_format', session('business.date_format', $businessSettings['date_format'] ?? 'd-m-Y'));
            session()->put('business.time_format', session('business.time_format', $businessSettings['time_format'] ?? 24));
            session()->put('business.time_zone', session('business.time_zone', $businessSettings['time_zone'] ?? config('app.timezone')));
            session()->put('business.fy_start_month', session('business.fy_start_month', $businessSettings['fy_start_month'] ?? 1));
            session()->put('currency.code', session('currency.code', $businessSettings['currency_code'] ?? 'USD'));
            session()->put('currency.symbol', session('currency.symbol', $businessSettings['currency_symbol'] ?? '$'));
            session()->put('currency.thousand_separator', session('currency.thousand_separator', ','));
            session()->put('currency.decimal_separator', session('currency.decimal_separator', '.'));

            session()->put('user.id', session('user.id', $user->id ?? 1));
            session()->put('user.business_id', session('user.business_id', $user->business_id ?? 1));
            session()->put('user.language', session('user.language', config('app.locale', 'en')));
            session()->put('user.business_location_name', session('user.business_location_name', 'Head Office'));
        }

        return $next($request);
    }
}
