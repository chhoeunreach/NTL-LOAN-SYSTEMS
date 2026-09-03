@php
    $businessSettings = \Modules\LoanManagement\Services\BusinessSettingsService::get();
    $businessLogoUrl = \Modules\LoanManagement\Services\BusinessSettingsService::publicLogoUrl();
    $loginBackgroundUrl = \Modules\LoanManagement\Services\BusinessSettingsService::loginBackgroundUrl();
    $businessName = $businessSettings['business_name'] ?: 'Loan Management';
    $systemName = $businessSettings['system_name'] ?: 'Loan Management';
    $systemSubtitle = $businessSettings['system_subtitle'] ?: 'Dedicated loan operation workspace';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - {{ $systemName }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css">
    <style>
        :root {
            --login-primary: {{ $businessSettings['theme_color'] ?? '#2563eb' }};
            @if($loginBackgroundUrl)
                --login-background: url('{{ $loginBackgroundUrl }}');
            @endif
        }
        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body { min-height: 100vh; margin: 0; font-family: Arial, Helvetica, sans-serif; color: #111827; background: #eef3f8; }
        .login-shell {
            min-height: 100vh; display: grid; grid-template-columns: minmax(0, 1fr) minmax(430px, .82fr);
            background: linear-gradient(135deg, #0f172a 0%, #18324f 48%, #e9eef5 48%, #f8fafc 100%);
            isolation: isolate;
        }
        .login-shell.has-photo { background-image: linear-gradient(90deg, rgba(7, 18, 33, .88), rgba(7, 18, 33, .54) 52%, rgba(248, 250, 252, .96) 52%), var(--login-background); background-size: cover; background-position: center; }
        .login-brand-panel { min-height: 100vh; display: flex; flex-direction: column; justify-content: space-between; padding: 42px 54px; color: #fff; }
        .brand-mark {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-weight: 800;
            letter-spacing: 0;
        }
        .brand-logo {
            width: 46px;
            height: 46px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: rgba(255, 255, 255, .16);
            border: 1px solid rgba(255, 255, 255, .28);
            color: #fff;
            font-size: 19px;
        }
        .brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .brand-copy { max-width: 650px; padding-bottom: 34px; }
        .brand-copy h1 {
            margin: 0;
            font-size: 48px;
            line-height: 1.08;
            font-weight: 900;
            letter-spacing: 0;
        }
        .brand-copy p {
            max-width: 520px;
            margin: 18px 0 0;
            color: rgba(255, 255, 255, .82);
            font-size: 16px;
            line-height: 1.7;
        }
        .brand-points { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 28px; }
        .brand-point { min-height: 92px; padding: 14px; border: 1px solid rgba(255,255,255,.20); border-radius: 8px; background: rgba(255,255,255,.10); backdrop-filter: blur(12px); }
        .brand-point strong { display: block; font-size: 18px; }
        .brand-point span { display: block; margin-top: 6px; color: rgba(255,255,255,.76); font-size: 12px; line-height: 1.45; }
        .login-panel { min-height: 100vh; display: grid; place-items: center; padding: 36px; background: rgba(248, 250, 252, .90); backdrop-filter: blur(18px); }
        .login-box {
            width: min(440px, 100%);
            padding: 32px;
            background: rgba(255,255,255,.98);
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .14);
        }
        .login-box-head { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .login-box-logo { width: 44px; height: 44px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; overflow: hidden; background: #eef4fb; color: var(--login-primary); border: 1px solid #dbe4ef; font-weight: 900; flex: 0 0 auto; }
        .login-box-logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .login-title {
            margin: 0;
            color: #0f172a;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0;
        }
        .login-subtitle {
            margin: 4px 0 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }
        .login-box label {
            margin-bottom: 7px;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .login-box .form-control {
            height: 46px;
            border-color: #dbe4ef;
            border-radius: 6px;
            box-shadow: none;
            color: #0f172a;
            font-size: 14px;
        }
        .login-password-row { position: relative; }
        .login-password-row .form-control { padding-right: 84px; }
        .login-password-toggle { position: absolute; right: 6px; top: 6px; height: 34px; min-width: 68px; border: 0; border-radius: 6px; background: #eef4fb; color: #334155; font-weight: 800; font-size: 12px; }
        .login-box .form-control:focus {
            border-color: var(--login-primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--login-primary) 16%, transparent);
        }
        .login-options { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 4px 0 16px; color: #64748b; font-size: 12px; }
        .login-options label { display: inline-flex; align-items: center; gap: 7px; margin: 0; color: #475569; font-size: 12px; text-transform: none; }
        .login-button {
            height: 46px;
            margin-top: 8px;
            border: 0;
            border-radius: 6px;
            background: var(--login-primary);
            font-weight: 800;
            box-shadow: 0 12px 28px color-mix(in srgb, var(--login-primary) 28%, transparent);
        }
        .login-button:hover,
        .login-button:focus {
            filter: brightness(.96);
            background: var(--login-primary);
        }
        .login-meta { margin-top: 18px; color: #94a3b8; font-size: 12px; text-align: center; }
        .login-links { margin-top: 16px; display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; }
        .login-links a { color: #475569; font-size: 12px; font-weight: 800; text-decoration: none; }
        .login-links a:hover { color: var(--login-primary); }
        @supports not (color: color-mix(in srgb, #000 10%, transparent)) {
            .login-box .form-control:focus { box-shadow: 0 0 0 3px rgba(37, 99, 235, .14); }
            .login-button { box-shadow: 0 12px 28px rgba(37, 99, 235, .24); }
        }
        @media (max-width: 900px) {
            .login-shell {
                grid-template-columns: 1fr;
            }
            .login-brand-panel {
                min-height: 280px;
                padding: 28px;
            }
            .brand-copy {
                padding: 28px 0 0;
            }
            .brand-copy h1 {
                font-size: 32px;
            }
            .brand-points { grid-template-columns: 1fr; }
            .login-panel {
                min-height: auto;
                padding: 24px 18px 34px;
            }
            .login-box {
                padding: 26px;
            }
        }
    </style>
</head>
<body>
    <main class="login-shell {{ $loginBackgroundUrl ? 'has-photo' : '' }}">
        <section class="login-brand-panel">
            <div class="brand-mark">
                <span class="brand-logo">
                    @if($businessLogoUrl)
                        <img src="{{ $businessLogoUrl }}" alt="{{ $businessName }}">
                    @else
                        <span>{{ strtoupper(mb_substr($businessName, 0, 1)) }}</span>
                    @endif
                </span>
                <span>{{ $businessName }}</span>
            </div>
            <div class="brand-copy">
                <h1>{{ $systemName }}</h1>
                <p>{{ $systemSubtitle }}</p>
                <div class="brand-points" aria-label="Workspace highlights">
                    <div class="brand-point">
                        <strong>Loans</strong>
                        <span>Approve, monitor, and collect installments from one workspace.</span>
                    </div>
                    <div class="brand-point">
                        <strong>Customers</strong>
                        <span>Keep customer profiles, documents, and chats organized.</span>
                    </div>
                    <div class="brand-point">
                        <strong>Reports</strong>
                        <span>Review payments, balances, and daily collection activity.</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="login-panel">
            <div class="login-box">
                <div class="login-box-head">
                    <span class="login-box-logo">
                        @if($businessLogoUrl)
                            <img src="{{ $businessLogoUrl }}" alt="{{ $businessName }}">
                        @else
                            <span>{{ strtoupper(mb_substr($businessName, 0, 1)) }}</span>
                        @endif
                    </span>
                    <div>
                        <h2 class="login-title">Admin Login</h2>
                        <p class="login-subtitle">Sign in to continue to your secure workspace.</p>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form method="post" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label for="email">Email or username</label>
                        <input id="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="Enter email or username">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="login-password-row">
                            <input id="password" name="password" type="password" class="form-control" required autocomplete="current-password" placeholder="Enter password">
                            <button type="button" class="login-password-toggle" id="loginPasswordToggle">Show</button>
                        </div>
                    </div>
                    <div class="login-options">
                        <label><input type="checkbox" name="remember" value="1"> Remember me</label>
                    </div>
                    <button class="btn btn-primary btn-block login-button" type="submit">Sign In</button>
                </form>

                <div class="login-meta">{{ $businessName }} secure workspace</div>
                <div class="login-links">
                    @if(Route::has('loan-management.public.customer-login'))
                        <a href="{{ route('loan-management.public.customer-login') }}">Customer Login</a>
                    @endif
                    @if(\Modules\LoanManagement\Services\BusinessSettingsService::isCmsEnabled())
                        <a href="{{ route('loan-management.public.home') }}">Website</a>
                    @endif
                </div>
            </div>
        </section>
    </main>
    <script>
        (function () {
            var password = document.getElementById('password');
            var toggle = document.getElementById('loginPasswordToggle');
            if (!password || !toggle) return;
            toggle.addEventListener('click', function () {
                var showing = password.type === 'text';
                password.type = showing ? 'password' : 'text';
                toggle.textContent = showing ? 'Show' : 'Hide';
            });
        })();
    </script>
</body>
</html>
