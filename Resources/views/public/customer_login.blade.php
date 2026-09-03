@php
    $businessLogoUrl = \Modules\LoanManagement\Services\BusinessSettingsService::publicLogoUrl();
    $businessName = \Modules\LoanManagement\Services\BusinessSettingsService::businessName();
    $themeColor = $settings['theme_color'] ?? '#2563eb';
    $themeDark = \Modules\LoanManagement\Services\BusinessSettingsService::themeColor();
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Login · {{ $businessName }}</title>
    <style>
        :root { --primary: {{ $themeColor }}; --ink: #0f172a; --muted: #64748b; --line: #e2e8f0; --soft: #f8fafc; }
        * { box-sizing: border-box; }
        body {
            margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh; color: var(--ink); display: grid; place-items: center; padding: 24px 16px;
            background: radial-gradient(1200px 600px at 15% -10%, rgba(37,99,235,.12), transparent 60%),
                        radial-gradient(1000px 500px at 110% 110%, {{ $themeColor }}1c, transparent 55%), var(--soft);
        }
        .shell { width: min(440px, 100%); }
        .card {
            background: #fff; border: 1px solid var(--line); border-radius: 18px; overflow: hidden;
            box-shadow: 0 24px 70px -18px rgba(15,23,42,.18);
        }
        .brand-row { display: flex; align-items: center; gap: 12px; padding: 22px 26px 0; }
        .brand { display: inline-flex; align-items: center; gap: 12px; color: var(--ink); font-weight: 800; text-decoration: none; }
        .logo {
            width: 44px; height: 44px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center;
            overflow: hidden; background: {{ $themeColor }}14; color: var(--primary); border: 1px solid {{ $themeColor }}2e; flex: 0 0 auto;
        }
        .logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .body { padding: 22px 26px 26px; }
        h1 { margin: 0; font-size: 22px; letter-spacing: -.3px; }
        .sub { margin: 8px 0 0; color: var(--muted); font-size: 14px; line-height: 1.6; }
        .banner {
            margin: 18px 0 0; padding: 12px 14px; border-radius: 10px; font-size: 14px; line-height: 1.5;
        }
        .banner.error { background: #fff1f2; border: 1px solid #fecdd3; color: #be123c; }
        .banner.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; }
        .banner.info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8; }
        form { margin-top: 20px; }
        .field { margin-bottom: 16px; }
        .field label { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; color: #334155; font-size: 13px; font-weight: 700; }
        .field label svg { opacity: .7; }
        .control { position: relative; }
        input[type="text"], input[type="tel"], input[type="password"] {
            width: 100%; height: 48px; border: 1px solid var(--line); border-radius: 12px; padding: 0 42px 0 14px;
            font: inherit; font-size: 15px; background: #fff; color: var(--ink); transition: border-color .15s, box-shadow .15s; outline: none;
        }
        input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px {{ $themeColor }}1f; }
        input::placeholder { color: #94a3b8; }
        .toggle-pw {
            position: absolute; right: 4px; top: 50%; transform: translateY(-50%); width: 34px; height: 34px;
            border: 0; background: transparent; border-radius: 8px; color: #94a3b8; cursor: pointer; display: none; align-items: center; justify-content: center;
        }
        .toggle-pw:hover { color: #475569; }
        .row { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 4px 0 20px; flex-wrap: wrap; }
        .remember { display: inline-flex; align-items: center; gap: 8px; color: #475569; font-size: 14px; cursor: pointer; user-select: none; }
        .remember input { width: 16px; height: 16px; accent-color: var(--primary); cursor: pointer; }
        .button {
            width: 100%; height: 50px; border: 0; border-radius: 12px; background: var(--primary); color: #fff;
            font: inherit; font-size: 15px; font-weight: 800; cursor: pointer; letter-spacing: .2px;
            box-shadow: 0 12px 24px -8px {{ $themeColor }}66; transition: transform .1s, box-shadow .15s, opacity .15s;
        }
        .button:hover:not(:disabled) { box-shadow: 0 16px 30px -8px {{ $themeColor }}80; transform: translateY(-1px); }
        .button:disabled { opacity: .7; cursor: not-allowed; }
        .spinner { display: none; width: 18px; height: 18px; border: 2px solid rgba(255,255,255,.4); border-top-color: #fff; border-radius: 50%; animation: spin .7s linear infinite; vertical-align: -3px; margin-right: 8px; }
        .button.loading .spinner { display: inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .divider { display: flex; align-items: center; gap: 12px; margin: 22px 0; color: #94a3b8; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; }
        .divider::before, .divider::after { content: ""; flex: 1; height: 1px; background: var(--line); }
        .links { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .link {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;
            height: 46px; border-radius: 12px; font-size: 14px; font-weight: 700; transition: background .15s, border-color .15s;
        }
        .link.primary { color: var(--primary); border: 1px solid {{ $themeColor }}45; background: {{ $themeColor }}0a; }
        .link.primary:hover { background: {{ $themeColor }}14; }
        .link.ghost { color: #475569; border: 1px solid var(--line); }
        .link.ghost:hover { background: var(--soft); }
        .foot { text-align: center; margin-top: 18px; font-size: 12px; color: #94a3b8; }
        @media (max-width: 360px) { .links { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="shell">
        <div class="card">
            <div class="brand-row">
                <a class="brand" href="{{ route('loan-management.public.home') }}">
                    <span class="logo">
                        @if($businessLogoUrl)<img src="{{ $businessLogoUrl }}" alt="{{ $businessName }}">@else{{ strtoupper(mb_substr($businessName, 0, 1)) }}@endif
                    </span>
                    <span>{{ $businessName }}</span>
                </a>
            </div>
            <div class="body">
                <h1>Welcome back</h1>
                <p class="sub">Sign in with your phone number and password to view your dashboard.</p>

                @if ($errors->any())
                    <div class="banner error">{{ $errors->first() }}</div>
                @elseif (session('status'))
                    <div class="banner success">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('loan-management.public.customer-login.store') }}" id="loginForm">
                    @csrf
                    <div class="field">
                        <label for="login">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                            Phone or Username
                        </label>
                        <div class="control">
                            <input id="login" name="login" type="tel" value="{{ old('login') }}" placeholder="Phone number or username" required autofocus autocomplete="username" inputmode="tel" maxlength="255">
                        </div>
                    </div>
                    <div class="field">
                        <label for="password">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Password
                        </label>
                        <div class="control">
                            <input id="password" name="password" type="password" required autocomplete="current-password" maxlength="255">
                            <button type="button" class="toggle-pw" id="togglePw" aria-label="Show password">
                                <svg id="eyeOutline" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg id="eyeOff" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="row">
                        <label class="remember" for="remember">
                            <input type="checkbox" id="remember" name="remember" value="1">
                            Keep me signed in
                        </label>
                    </div>
                    <button class="button" type="submit" id="submitBtn">
                        <span class="spinner"></span><span id="submitLabel">Sign in</span>
                    </button>
                </form>

                <div class="divider">New here?</div>
                <div class="links">
                    <a class="link primary" href="{{ route('loan-management.public.register') }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                        Create account
                    </a>
                    <a class="link ghost" href="{{ route('loan-management.public.home') }}">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 19-7-7 7-7"/><path d="M19 12H5"/></svg>
                        Back home
                    </a>
                </div>
            </div>
        </div>
        <p class="foot">&copy; {{ date('Y') }} {{ $businessName }}. All rights reserved.</p>
    </div>

    <script>
        (function () {
            var pw = document.getElementById('password');
            var btn = document.getElementById('togglePw');
            var eyeOn = document.getElementById('eyeOutline');
            var eyeOff = document.getElementById('eyeOff');
            btn.style.display = 'inline-flex';
            btn.addEventListener('click', function () {
                var showing = pw.type === 'text';
                pw.type = showing ? 'password' : 'text';
                eyeOn.style.display = showing ? '' : 'none';
                eyeOff.style.display = showing ? 'none' : '';
                pw.focus();
            });

            var form = document.getElementById('loginForm');
            var submit = document.getElementById('submitBtn');
            form.addEventListener('submit', function () {
                submit.classList.add('loading');
                submit.disabled = true;
            });
        })();
    </script>
</body>
</html>