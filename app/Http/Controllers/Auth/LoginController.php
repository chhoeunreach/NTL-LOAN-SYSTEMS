<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('loan-management.dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $field = filter_var($credentials['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Disallow concurrent logins: log out customer session if active
        if (Auth::guard('customer_loan')->check()) {
            Auth::guard('customer_loan')->logout();
        }

        if (Auth::attempt([$field => $credentials['email'], 'password' => $credentials['password']], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended('/loan-management/dashboard');
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        if (Auth::guard('customer_loan')->check()) {
            Auth::guard('customer_loan')->logout();
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $redirectTo = $request->query('redirect') ?? $request->input('redirect');
        if ($redirectTo && (str_starts_with($redirectTo, '/') || filter_var($redirectTo, FILTER_VALIDATE_URL))) {
            return redirect($redirectTo);
        }

        return redirect()->route('login');
    }
}
