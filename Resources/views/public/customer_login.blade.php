@php
    $businessLogoUrl = \Modules\LoanManagement\Services\BusinessSettingsService::publicLogoUrl();
    $businessName = \Modules\LoanManagement\Services\BusinessSettingsService::businessName();
    $themeColor = $settings['theme_color'] ?? '#2563eb';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Login - {{ $businessName }}</title>
    <style>
        :root { --public-primary: {{ $themeColor }}; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; min-height: 100vh; background: #f4f7fb; color: #102033; display: grid; place-items: center; padding: 24px 16px; }
        .box { width: min(430px, 100%); background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 18px 54px rgba(15,23,42,.10); padding: 28px; }
        .brand { display: inline-flex; align-items: center; gap: 10px; color: #0f172a; font-weight: 800; text-decoration: none; margin-bottom: 28px; }
        .logo { width: 42px; height: 42px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; overflow: hidden; background: #edf3fb; color: var(--public-primary); }
        .logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        h1 { margin: 0; font-size: 28px; letter-spacing: 0; }
        .muted { margin: 8px 0 24px; color: #64748b; line-height: 1.6; }
        label { display: block; margin-bottom: 7px; color: #334155; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        input { width: 100%; height: 46px; border: 1px solid #dbe4ef; border-radius: 6px; padding: 0 12px; font: inherit; outline: none; }
        input:focus { border-color: var(--public-primary); box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
        .field { margin-bottom: 14px; }
        .errors { margin: 0 0 18px; padding: 12px 14px; background: #fff1f2; border: 1px solid #fecdd3; color: #be123c; border-radius: 6px; }
        .button { width: 100%; height: 46px; border: 0; border-radius: 6px; background: var(--public-primary); color: #fff; font-weight: 800; cursor: pointer; }
        .links { margin-top: 18px; display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .link { color: var(--public-primary); font-weight: 800; text-decoration: none; }
    </style>
</head>
<body>
    <main class="box">
        <a class="brand" href="{{ route('loan-management.public.home') }}">
            <span class="logo">@if($businessLogoUrl)<img src="{{ $businessLogoUrl }}" alt="{{ $businessName }}">@else{{ strtoupper(mb_substr($businessName, 0, 1)) }}@endif</span>
            <span>{{ $businessName }}</span>
        </a>
        <h1>Customer Login</h1>
        <p class="muted">Use your phone number and password to view your customer dashboard.</p>

        @if ($errors->any())
            <div class="errors">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('loan-management.public.customer-login.store') }}">
            @csrf
            <div class="field">
                <label for="login">Phone or Username</label>
                <input id="login" name="login" value="{{ old('login') }}" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
            </div>
            <button class="button" type="submit">Login</button>
        </form>
        <div class="links">
            <a class="link" href="{{ route('loan-management.public.register') }}">Create account</a>
            <a class="link" href="{{ route('loan-management.public.home') }}">Back home</a>
        </div>
    </main>
</body>
</html>
