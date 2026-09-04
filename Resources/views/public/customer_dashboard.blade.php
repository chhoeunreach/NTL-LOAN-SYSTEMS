@php
    $businessLogoUrl = \Modules\LoanManagement\Services\BusinessSettingsService::publicLogoUrl();
    $businessName = \Modules\LoanManagement\Services\BusinessSettingsService::businessName();
    $themeColor = $settings['theme_color'] ?? '#2563eb';
    $money = function ($value) { return number_format((float) ($value ?? 0), 2); };
    $displayName = trim((string) ($customer->khmer_name ?? '')) ?: trim((string) ($customer->name ?? 'Customer'));
    $adminUser = Auth::guard('web')->user() ?? Auth::user();
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customer Dashboard - {{ $businessName }}</title>
    <style>
        :root { --public-primary: {{ $themeColor }}; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; background: #f4f7fb; color: #102033; }
        .topbar { background: #fff; border-bottom: 1px solid #e2e8f0; }
        .topbar-inner { width: min(1180px, calc(100% - 32px)); margin: 0 auto; min-height: 70px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
        .brand { display: inline-flex; align-items: center; gap: 10px; color: #0f172a; font-weight: 800; text-decoration: none; }
        .logo { width: 42px; height: 42px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; overflow: hidden; background: #edf3fb; color: var(--public-primary); }
        .logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .topbar-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .btn-topbar { display: inline-flex; align-items: center; gap: 8px; border: 1px solid #dbe4ef; background: #fff; color: #334155; border-radius: 6px; height: 40px; padding: 0 14px; font-weight: 800; font-size: 14px; text-decoration: none; transition: all .15s ease-in-out; }
        .btn-topbar:hover { background: #f1f5f9; color: #0f172a; border-color: #cbd5e1; }
        .btn-topbar svg { width: 16px; height: 16px; }
        .btn-admin-pill { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #e9d5ff; background: #faf5ff; color: #7e22ce; border-radius: 6px; height: 40px; padding: 0 14px; font-weight: 800; font-size: 14px; text-decoration: none; transition: all .15s ease-in-out; }
        .btn-admin-pill:hover { background: #f3e8ff; border-color: #d8b4fe; }
        .wrap { width: min(1180px, calc(100% - 32px)); margin: 26px auto 44px; }
        .hero { background: var(--public-primary); color: #fff; border-radius: 8px; padding: 26px; margin-bottom: 18px; }
        .hero h1 { margin: 0; font-size: 30px; letter-spacing: 0; }
        .hero p { margin: 8px 0 0; color: rgba(255,255,255,.84); }
        .grid { display: grid; grid-template-columns: 360px minmax(0, 1fr); gap: 18px; align-items: start; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 12px 32px rgba(15,23,42,.06); overflow: hidden; }
        .card h2 { margin: 0; padding: 16px 18px; font-size: 18px; border-bottom: 1px solid #edf2f7; letter-spacing: 0; }
        .card-body { padding: 18px; }
        .info { display: grid; gap: 12px; }
        .info-row { display: grid; gap: 4px; }
        .info-row span { color: #64748b; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .info-row strong { color: #0f172a; font-size: 14px; overflow-wrap: anywhere; }
        .profile-actions { padding: 14px 18px 18px; border-top: 1px solid #edf2f7; background: #fafbfc; }
        .btn-profile-logout { width: 100%; min-height: 42px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid #fee2e2; background: #fff; color: #dc2626; border-radius: 6px; font-weight: 800; font-size: 14px; cursor: pointer; transition: all .15s ease-in-out; }
        .btn-profile-logout:hover { background: #fef2f2; border-color: #fca5a5; }
        .btn-profile-logout svg { width: 16px; height: 16px; }
        .stack { display: grid; gap: 18px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 11px 12px; border-bottom: 1px solid #edf2f7; text-align: left; font-size: 13px; vertical-align: top; }
        th { color: #475569; font-size: 12px; text-transform: uppercase; background: #f8fafc; }
        .empty { margin: 0; color: #64748b; }
        .note-box { margin: 0; color: #334155; line-height: 1.6; white-space: pre-wrap; }
        .status { display: inline-flex; min-height: 24px; align-items: center; border-radius: 999px; padding: 0 9px; background: #e8f2ff; color: #1d4ed8; font-size: 12px; font-weight: 800; }
        @media (max-width: 900px) {
            .grid { grid-template-columns: 1fr; }
            .topbar-inner { align-items: flex-start; flex-direction: column; padding: 14px 0; gap: 12px; }
            .topbar-actions { width: 100%; justify-content: flex-start; flex-wrap: wrap; }
            table { min-width: 680px; }
            .table-scroll { overflow-x: auto; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="{{ route('loan-management.public.home') }}">
                <span class="logo">@if($businessLogoUrl)<img src="{{ $businessLogoUrl }}" alt="{{ $businessName }}">@else{{ strtoupper(mb_substr($businessName, 0, 1)) }}@endif</span>
                <span>{{ $businessName }}</span>
            </a>
            <div class="topbar-actions">
                <a href="{{ route('loan-management.public.home') }}" class="btn-topbar">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="2" y1="12" x2="22" y2="12"></line>
                        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                    </svg>
                    Website
                </a>
                <a href="{{ route('loan-management.public.customer-login') }}" class="btn-topbar" title="Switch or add another account">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    Switch Account
                </a>
                @if($adminUser)
                    <a href="{{ route('loan-management.dashboard') }}" class="btn-admin-pill" title="Go back to Admin Dashboard">
                        ⚡ Admin Panel
                    </a>
                @endif
            </div>
        </div>
    </header>

    <main class="wrap">
        <section class="hero">
            <h1>Welcome, {{ $displayName }}</h1>
            <p>Your customer information, loan records, and recent payments are shown below.</p>
        </section>

        @if(session('status'))
            <div class="card" style="margin-bottom:18px;"><div class="card-body">{{ session('status') }}</div></div>
        @endif

        <div class="grid">
            <section class="card">
                <h2>My Information</h2>
                <div class="card-body info">
                    <div class="info-row"><span>Customer Code</span><strong>{{ $customer->customer_code ?: '-' }}</strong></div>
                    <div class="info-row"><span>Name</span><strong>{{ $customer->name ?: '-' }}</strong></div>
                    <div class="info-row"><span>Khmer Name</span><strong>{{ $customer->khmer_name ?: '-' }}</strong></div>
                    <div class="info-row"><span>Phone</span><strong>{{ $customer->phone ?: '-' }}</strong></div>
                    <div class="info-row"><span>Alternative Phone</span><strong>{{ $customer->alternate_phone ?: '-' }}</strong></div>
                    <div class="info-row"><span>Email</span><strong>{{ $customer->email ?: '-' }}</strong></div>
                    <div class="info-row"><span>Telegram</span><strong>{{ $customer->telegram ?: ($customer->telegram_username ?: '-') }}</strong></div>
                    <div class="info-row"><span>Gender</span><strong>{{ $customer->gender ?: '-' }}</strong></div>
                    <div class="info-row"><span>Date of Birth</span><strong>{{ $customer->date_of_birth ?: '-' }}</strong></div>
                    <div class="info-row"><span>ID Card</span><strong>{{ $customer->id_card_number ?: '-' }}</strong></div>
                    <div class="info-row"><span>Passport</span><strong>{{ $customer->passport_number ?: '-' }}</strong></div>
                    <div class="info-row"><span>Address</span><strong>{{ $customer->address ?: '-' }}</strong></div>
                    <div class="info-row"><span>Province</span><strong>{{ $customer->province ?: '-' }}</strong></div>
                    <div class="info-row"><span>District</span><strong>{{ $customer->district ?: '-' }}</strong></div>
                    <div class="info-row"><span>Commune</span><strong>{{ $customer->commune ?: '-' }}</strong></div>
                    <div class="info-row"><span>Village</span><strong>{{ $customer->village ?: '-' }}</strong></div>
                    <div class="info-row"><span>Family Contact</span><strong>{{ $customer->family_contact_name ?: '-' }}</strong></div>
                    <div class="info-row"><span>Family Phone</span><strong>{{ $customer->family_contact_phone ?: '-' }}</strong></div>
                    <div class="info-row"><span>Spouse Name</span><strong>{{ $customer->spouse_name ?: '-' }}</strong></div>
                    <div class="info-row"><span>Spouse Phone</span><strong>{{ $customer->spouse_phone ?: '-' }}</strong></div>
                    <div class="info-row"><span>Workplace</span><strong>{{ $customer->workplace ?: '-' }}</strong></div>
                    <div class="info-row"><span>Monthly Income</span><strong>{{ $money($customer->monthly_income) }}</strong></div>
                    <div class="info-row"><span>Customer Type</span><strong>{{ $customer->customer_type ?: '-' }}</strong></div>
                    <div class="info-row"><span>GPS Tracking</span><strong>{{ $customer->allow_gps_tracking ? 'Enabled' : 'Disabled' }}</strong></div>
                    <div class="info-row"><span>Last Login</span><strong>{{ $customer->last_login_at ?: '-' }}</strong></div>
                    <div class="info-row"><span>Status</span><strong>{{ $customer->status ?: 'active' }}</strong></div>
                </div>
                <div class="profile-actions">
                    <form method="POST" action="{{ route('loan-management.public.customer-logout') }}" style="margin: 0; width: 100%;">
                        @csrf
                        <button class="btn-profile-logout" type="submit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            Log Out from Customer Account
                        </button>
                    </form>
                </div>
            </section>

            <div class="stack">
                @if(!empty($customer->note))
                    <section class="card">
                        <h2>Installment Request</h2>
                        <div class="card-body">
                            <p class="note-box">{{ $customer->note }}</p>
                        </div>
                    </section>
                @endif

                <section class="card">
                    <h2>My Loans</h2>
                    <div class="table-scroll">
                        @if($loans->count())
                            <table>
                                <thead><tr><th>Loan</th><th>Status</th><th>Total</th><th>Paid</th><th>Balance</th><th>Date</th></tr></thead>
                                <tbody>
                                    @foreach($loans as $loan)
                                        <tr>
                                            <td>{{ $loan->loan_number ?? ('#'.$loan->id) }}</td>
                                            <td><span class="status">{{ $loan->status ?? '-' }}</span></td>
                                            <td>{{ $money($loan->total_amount ?? $loan->loan_amount ?? 0) }}</td>
                                            <td>{{ $money($loan->paid_amount ?? $loan->total_paid ?? 0) }}</td>
                                            <td>{{ $money($loan->balance_amount ?? $loan->remaining_balance ?? 0) }}</td>
                                            <td>{{ $loan->created_at ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="card-body"><p class="empty">No loans found yet.</p></div>
                        @endif
                    </div>
                </section>

                <section class="card">
                    <h2>Recent Payments</h2>
                    <div class="table-scroll">
                        @if($payments->count())
                            <table>
                                <thead><tr><th>Date</th><th>Loan</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
                                <tbody>
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td>{{ $payment->paid_at ?? $payment->paid_date ?? $payment->created_at ?? '-' }}</td>
                                            <td>{{ $payment->loan_number_snapshot ?? ('#'.($payment->loan_id ?? '-')) }}</td>
                                            <td>{{ $money($payment->amount ?? $payment->total_paid_base ?? 0) }}</td>
                                            <td>{{ $payment->payment_method_snapshot ?? $payment->payment_method ?? '-' }}</td>
                                            <td><span class="status">{{ $payment->status ?? 'confirmed' }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="card-body"><p class="empty">No payments found yet.</p></div>
                        @endif
                    </div>
                </section>
            </div>
        </div>
    </main>
</body>
</html>
