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
        body {
            min-height: 100vh;
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #111827;
            background: #eef3f8;
        }
        .login-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(380px, .9fr);
            background:
                linear-gradient(135deg, rgba(7, 20, 38, .76), rgba(7, 20, 38, .28)),
                radial-gradient(circle at 18% 20%, rgba(255, 255, 255, .22), transparent 30%),
                linear-gradient(135deg, #11314f, #31506d 52%, #8aa3b8);
            background-size: cover;
            background-position: center;
        }
        .login-shell.has-photo {
            background-image:
                linear-gradient(135deg, rgba(8, 18, 33, .84), rgba(8, 18, 33, .30)),
                var(--login-background);
        }
        .login-brand-panel {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 48px;
            color: #fff;
        }
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
        .brand-copy {
            max-width: 580px;
            padding-bottom: 42px;
        }
        .brand-copy h1 {
            margin: 0;
            font-size: 44px;
            line-height: 1.08;
            font-weight: 800;
            letter-spacing: 0;
        }
        .brand-copy p {
            max-width: 520px;
            margin: 18px 0 0;
            color: rgba(255, 255, 255, .82);
            font-size: 16px;
            line-height: 1.7;
        }
        .login-panel {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px;
            background: rgba(248, 250, 252, .94);
            backdrop-filter: blur(18px);
        }
        .login-box {
            width: min(420px, 100%);
            padding: 34px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .14);
        }
        .login-title {
            margin: 0;
            color: #0f172a;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: 0;
        }
        .login-subtitle {
            margin: 8px 0 26px;
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
        .login-box .form-control:focus {
            border-color: var(--login-primary);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--login-primary) 16%, transparent);
        }
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
        .login-meta {
            margin-top: 20px;
            color: #94a3b8;
            font-size: 12px;
            text-align: center;
        }
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
            </div>
        </section>

        <section class="login-panel">
            <div class="login-box">
                <h2 class="login-title">Welcome back</h2>
                <p class="login-subtitle">Sign in to manage customers, loans, collections, and payments.</p>

                @if ($errors->any())
                    <div class="alert alert-danger">{{ $errors->first() }}</div>
                @endif

                <form method="post" action="{{ route('login') }}">
                    @csrf
                    <div class="form-group">
                        <label for="email">Email or username</label>
                        <input id="email" name="email" class="form-control" value="{{ old('email', 'admin@example.com') }}" required autofocus autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input id="password" name="password" type="password" class="form-control" required autocomplete="current-password">
                    </div>
                    <button class="btn btn-primary btn-block login-button" type="submit">Login</button>
                </form>

                <div class="login-meta">{{ $businessName }} secure workspace</div>
            </div>
        </section>
    </main>
</body>
</html>
