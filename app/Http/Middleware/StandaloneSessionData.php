<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StandaloneSessionData
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->hasSession()) {
            $user = $request->user();

            session()->put('business.name', session('business.name', config('app.name')));
            session()->put('business.currency_symbol_placement', session('business.currency_symbol_placement', 'before'));
            session()->put('business.currency_precision', session('business.currency_precision', 2));
            session()->put('business.quantity_precision', session('business.quantity_precision', 2));
            session()->put('currency.code', session('currency.code', 'USD'));
            session()->put('currency.symbol', session('currency.symbol', '$'));
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
