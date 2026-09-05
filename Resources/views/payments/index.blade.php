@php
    $lmIsKhmer = session('user.language', config('app.locale')) === 'km';
    $lmText = fn ($en, $km) => $lmIsKhmer ? $km : $en;
@endphp
@extends('loanmanagement::layouts.app')
@section('title', $lmText('Payments Ledger', 'បញ្ជីការទូទាត់ប្រាក់'))

@section('loan_css')
<style>
    *, *::before, *::after { box-sizing: border-box; }

    .lm-pay-index-wrap {
        font-family: 'Kantumruy Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: #f1f5f9;
        color: #1e293b;
        margin: -15px -15px 0 -15px;
        min-height: calc(100vh - 60px);
        display: flex;
        flex-direction: column;
    }

    /* Enterprise Dark Header Strip */
    .lm-pay-index-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: #fff;
        padding: 8px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        position: relative;
        z-index: 10;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    .lm-pay-index-header-left { display: flex; align-items: center; gap: 10px; }
    .lm-pay-index-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: rgba(16, 185, 129, 0.25);
        border: 1px solid rgba(52, 211, 153, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        color: #34d399;
    }
    .lm-pay-index-title { font-size: 15px; font-weight: 700; margin: 0; color: #f8fafc; }
    .lm-pay-index-sub { font-size: 11px; color: #94a3b8; margin: 1px 0 0; }

    .lm-pay-index-body {
        flex: 1;
        padding: 10px 14px;
        background: #f1f5f9;
    }

    /* KPI Summary Row */
    .lm-payment-summary-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 8px;
        margin-bottom: 10px;
    }
    @media (max-width: 1100px) {
        .lm-payment-summary-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 600px) {
        .lm-payment-summary-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .lm-payment-summary-card {
        display: flex;
        align-items: center;
        gap: 10px;
        min-height: 55px;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }
    .lm-payment-summary-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 32px;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        color: #fff;
        font-size: 14px;
    }
    .lm-payment-summary-copy { min-width: 0; flex: 1; }
    .lm-payment-summary-copy span {
        display: block;
        color: #64748b;
        font-size: 9.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.2px;
    }
    .lm-payment-summary-copy strong {
        display: block;
        margin: 1px 0 0;
        color: #0f172a;
        font-size: 14px;
        font-weight: 800;
        line-height: 1.15;
    }
    .lm-payment-summary-copy small {
        display: block;
        font-size: 9.5px;
        color: #94a3b8;
    }
    .lm-payment-summary-card.tone-green .lm-payment-summary-icon { background: #16a34a; }
    .lm-payment-summary-card.tone-cyan .lm-payment-summary-icon { background: #0891b2; }
    .lm-payment-summary-card.tone-blue .lm-payment-summary-icon { background: #2563eb; }
    .lm-payment-summary-card.tone-violet .lm-payment-summary-icon { background: #7c3aed; }
    .lm-payment-summary-card.tone-orange .lm-payment-summary-icon { background: #ea580c; }

    /* Compact Filter Panel */
    .lm-filter-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        margin-bottom: 10px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }
    .lm-filter-header {
        padding: 6px 12px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .lm-filter-title {
        font-size: 11px;
        font-weight: 700;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: flex;
        align-items: center;
        gap: 6px;
        border: 0;
        background: transparent;
        cursor: pointer;
        padding: 0;
    }
    .lm-filter-toggle-btn {
        min-height: 24px;
        padding: 2px 8px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        background: #fff;
        color: #475569;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        cursor: pointer;
    }
    .lm-filter-toggle-btn:hover { background: #f1f5f9; color: #0f172a; }
    .lm-filter-card.is-collapsed .lm-filter-body { display: none; }
    .lm-filter-body { padding: 8px 12px; }

    /* Form Controls */
    .lm-filter-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 6px 8px;
    }
    @media (max-width: 1200px) {
        .lm-filter-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 600px) {
        .lm-filter-grid { grid-template-columns: 1fr 1fr; }
    }
    .lm-filter-field { display: flex; flex-direction: column; gap: 2px; }
    .lm-filter-lbl { font-size: 9.5px; font-weight: 700; text-transform: uppercase; color: #64748b; }
    .lm-filter-input {
        height: 28px;
        padding: 3px 6px;
        font-size: 11px;
        border: 1px solid #cbd5e1;
        border-radius: 5px;
        outline: none;
        width: 100%;
    }
    .lm-filter-input:focus { border-color: #16a34a; box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.12); }

    .lm-filter-actions {
        display: flex;
        align-items: flex-end;
        gap: 6px;
    }
    .lm-btn-filter {
        height: 28px;
        padding: 0 10px;
        border-radius: 5px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border: 1px solid transparent;
        text-decoration: none;
    }
    .lm-btn-filter-apply { background: #16a34a; color: #fff; border-color: #15803d; }
    .lm-btn-filter-apply:hover { background: #15803d; color: #fff; }
    .lm-btn-filter-reset { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .lm-btn-filter-reset:hover { background: #e2e8f0; color: #0f172a; text-decoration: none; }

    /* Main Table Card */
    .lm-table-card {
        background: #fff;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
        overflow: hidden;
    }
    .lm-table-card-head {
        padding: 6px 12px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .lm-table-dense {
        width: 100%;
        margin-bottom: 0;
        border-collapse: collapse;
        font-size: 11px;
    }
    .lm-table-dense th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 9.5px;
        letter-spacing: 0.2px;
        padding: 6px 8px;
        border-bottom: 1px solid #cbd5e1;
        white-space: nowrap;
    }
    .lm-table-dense td {
        padding: 5px 8px;
        border-bottom: 1px solid #f1f5f9;
        color: #1e293b;
        vertical-align: middle;
    }
    .lm-table-dense tr:hover td { background-color: #f8fafc; }

    /* Badges & Buttons */
    .lm-badge {
        font-size: 9px;
        font-weight: 700;
        padding: 2px 6px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        text-transform: uppercase;
    }
    .lm-badge-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .lm-badge-warning { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .lm-badge-danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .lm-badge-info { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .lm-badge-gray { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }

    .lm-btn-tbl {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        padding: 2px 6px;
        font-size: 10px;
        font-weight: 600;
        border-radius: 4px;
        border: 1px solid transparent;
        cursor: pointer;
        text-decoration: none;
    }
    .lm-btn-tbl-info { background: #e0f2fe; color: #0284c7; border-color: #bae6fd; }
    .lm-btn-tbl-info:hover { background: #bae6fd; color: #0369a1; text-decoration: none; }
    .lm-btn-tbl-edit { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
    .lm-btn-tbl-edit:hover { background: #e2e8f0; color: #0f172a; text-decoration: none; }
    .lm-btn-tbl-del { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
    .lm-btn-tbl-del:hover { background: #fecaca; color: #991b1b; }

    /* Mobile Responsive Card List */
    .lm-payment-mobile-list { display: none; }
    @media (max-width: 767px) {
        .lm-table-dense { display: none; }
        .lm-payment-mobile-list { display: block; padding: 8px; }
        .lm-payment-mobile-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            padding: 8px;
            margin-bottom: 6px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
        }
        .lm-payment-mobile-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 4px;
            margin-bottom: 4px;
        }
        .lm-payment-mobile-actions {
            display: flex;
            gap: 4px;
            margin-top: 6px;
            padding-top: 4px;
            border-top: 1px dashed #e2e8f0;
        }
    }
</style>
@endsection

@section('content_body')
<div class="lm-pay-index-wrap">
    <!-- Enterprise Dark Header Strip -->
    <header class="lm-pay-index-header">
        <div class="lm-pay-index-header-left">
            <div class="lm-pay-index-icon">
                <i class="fa fa-money"></i>
            </div>
            <div>
                <h1 class="lm-pay-index-title">{{ $lmText('Payments & Collection Ledger', 'បញ្ជីការទូទាត់ និងប្រមូលប្រាក់') }}</h1>
                <p class="lm-pay-index-sub">{{ $lmText('Manage receipts, installment collections and pay-offs', 'គ្រប់គ្រងបង្កាន់ដៃ ការប្រមូលប្រាក់រំលស់ និងការទូទាត់ផ្តាច់') }}</p>
            </div>
        </div>
    </header>

    <!-- Main Workspace Body -->
    <div class="lm-pay-index-body">
        <!-- Top KPI Summary Cards -->
        <div class="lm-payment-summary-grid">
            <div class="lm-payment-summary-card tone-green">
                <div class="lm-payment-summary-icon"><i class="fa fa-money"></i></div>
                <div class="lm-payment-summary-copy">
                    <span>{{ $lmText('Filtered Total', 'ទឹកប្រាក់សរុប') }}</span>
                    <strong>$ {{ number_format($summary['amount'] ?? 0, 2) }}</strong>
                    <small>{{ $lmText('Matching filters', 'តាមលក្ខខណ្ឌចម្រោះ') }}</small>
                </div>
            </div>

            <div class="lm-payment-summary-card tone-cyan">
                <div class="lm-payment-summary-icon"><i class="fa fa-list"></i></div>
                <div class="lm-payment-summary-copy">
                    <span>{{ $lmText('Payments Count', 'ចំនួនបង្កាន់ដៃ') }}</span>
                    <strong>{{ number_format($summary['count'] ?? 0) }}</strong>
                    <small>{{ $lmText('Total receipts', 'បង្កាន់ដៃសរុប') }}</small>
                </div>
            </div>

            <div class="lm-payment-summary-card tone-blue">
                <div class="lm-payment-summary-icon"><i class="fa fa-bank"></i></div>
                <div class="lm-payment-summary-copy">
                    <span>{{ $lmText('Installment Payments', 'ការបង់រំលស់') }}</span>
                    <strong>$ {{ number_format($summary['loan_amount'] ?? 0, 2) }}</strong>
                    <small>{{ number_format($summary['loan_count'] ?? 0) }} {{ $lmText('records', 'ប្រតិបត្តិការ') }}</small>
                </div>
            </div>

            <div class="lm-payment-summary-card tone-violet">
                <div class="lm-payment-summary-icon"><i class="fa fa-calendar"></i></div>
                <div class="lm-payment-summary-copy">
                    <span>{{ $lmText('Monthly Scheduled', 'បង់តាមវគ្គ') }}</span>
                    <strong>$ {{ number_format($summary['monthly_amount'] ?? 0, 2) }}</strong>
                    <small>{{ number_format($summary['monthly_count'] ?? 0) }} {{ $lmText('records', 'ប្រតិបត្តិការ') }}</small>
                </div>
            </div>

            <div class="lm-payment-summary-card tone-orange">
                <div class="lm-payment-summary-icon"><i class="fa fa-check-circle"></i></div>
                <div class="lm-payment-summary-copy">
                    <span>{{ $lmText('Pay Off / Closed', 'បង់ផ្តាច់') }}</span>
                    <strong>$ {{ number_format($summary['payoff_amount'] ?? 0, 2) }}</strong>
                    <small>{{ number_format($summary['payoff_count'] ?? 0) }} {{ $lmText('records', 'ប្រតិបត្តិការ') }}</small>
                </div>
            </div>
        </div>

        <!-- Filter Panel -->
        <div class="lm-filter-card is-collapsed" id="loanPaymentFilterPanel">
            <div class="lm-filter-header">
                <button type="button" class="lm-filter-title" id="loanPaymentFilterTitle">
                    <i class="fa fa-filter text-primary"></i> {{ $lmText('Filters & Advanced Search', 'តម្រង និងស្វែងរកកម្រិតខ្ពស់') }}
                </button>
                <button type="button" class="lm-filter-toggle-btn" id="loanPaymentFilterToggle">
                    <span id="loanPaymentFilterToggleText">{{ $lmText('Expand', 'ពង្រីក') }}</span>
                    <i class="fa fa-chevron-down" id="loanPaymentFilterToggleIcon" aria-hidden="true"></i>
                </button>
            </div>
            <div class="lm-filter-body" id="loanPaymentFilterBody">
                <form method="GET" action="{{ route('loan-management.payments.index') }}" id="loanPaymentFilterForm">
                    <div class="lm-filter-grid">
                        <div class="lm-filter-field">
                            <label class="lm-filter-lbl">{{ $lmText('Search', 'ស្វែងរក') }}</label>
                            <input type="text" name="search" class="lm-filter-input" value="{{ $filters['search'] ?? '' }}" placeholder="{{ $lmText('Receipt, loan, customer...', 'បង្កាន់ដៃ, កិច្ចសន្យា...') }}">
                        </div>
                        <div class="lm-filter-field">
                            <label class="lm-filter-lbl">{{ $lmText('Installment #', 'លេខកិច្ចសន្យា') }}</label>
                            <input type="text" name="loan_number" class="lm-filter-input" value="{{ $filters['loan_number'] ?? '' }}" placeholder="LN-...">
                        </div>
                        <div class="lm-filter-field">
                            <label class="lm-filter-lbl">{{ $lmText('Customer Name/Phone', 'ឈ្មោះ/ទូរស័ព្ទ') }}</label>
                            <input type="text" name="customer" class="lm-filter-input" value="{{ $filters['customer'] ?? '' }}" placeholder="{{ $lmText('Name or phone', 'ឈ្មោះ ឬទូរស័ព្ទ') }}">
                        </div>
                        <div class="lm-filter-field">
                            <label class="lm-filter-lbl">{{ $lmText('Payment Method', 'វិធីទូទាត់') }}</label>
                            <select name="method" class="lm-filter-input">
                                <option value="">{{ $lmText('-- All Methods --', '-- គ្រប់វិធីទូទាត់ --') }}</option>
                                @foreach($methods as $key => $label)
                                    @php
                                        $displayFilterMethod = (string) $label;
                                        if (str_starts_with($displayFilterMethod, 'lang_v1.') || str_starts_with($displayFilterMethod, 'messages.')) {
                                            $rawFilterKey = str_replace(['lang_v1.', 'messages.'], '', $displayFilterMethod);
                                            $displayFilterMethod = $rawFilterKey === 'advance' ? $lmText('Advance Payment', 'ប្រាក់បង់មុន / បុរេប្រទាន (Advance)') : ucfirst(str_replace('_', ' ', $rawFilterKey));
                                        }
                                    @endphp
                                    <option value="{{ $label }}" {{ ($filters['method'] ?? '') == $label || ($filters['method'] ?? '') == $key ? 'selected' : '' }}>{{ $displayFilterMethod }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lm-filter-field">
                            <label class="lm-filter-lbl">{{ $lmText('Payment Type', 'ប្រភេទបង់') }}</label>
                            <select name="payment_type" class="lm-filter-input">
                                <option value="">{{ $lmText('-- All Types --', '-- គ្រប់ប្រភេទ --') }}</option>
                                <option value="loan" {{ ($filters['payment_type'] ?? '') === 'loan' ? 'selected' : '' }}>{{ $lmText('Installment', 'រំលស់') }}</option>
                                <option value="monthly" {{ ($filters['payment_type'] ?? '') === 'monthly' ? 'selected' : '' }}>{{ $lmText('Monthly', 'ប្រចាំខែ') }}</option>
                                <option value="payoff" {{ ($filters['payment_type'] ?? '') === 'payoff' ? 'selected' : '' }}>{{ $lmText('Pay Off', 'បង់ផ្តាច់') }}</option>
                            </select>
                        </div>
                        <div class="lm-filter-field">
                            <label class="lm-filter-lbl">{{ $lmText('Location / Branch', 'សាខា') }}</label>
                            <select name="location_id" class="lm-filter-input">
                                <option value="">{{ $lmText('-- All Branches --', '-- គ្រប់សាខា --') }}</option>
                                @foreach($locations as $id => $name)
                                    <option value="{{ $id }}" {{ (string)($filters['location_id'] ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lm-filter-field">
                            <label class="lm-filter-lbl">{{ $lmText('Status', 'ស្ថានភាព') }}</label>
                            <select name="status" class="lm-filter-input">
                                <option value="">{{ $lmText('-- All Status --', '-- គ្រប់ស្ថានភាព --') }}</option>
                                @foreach($statuses as $status => $label)
                                    <option value="{{ $status }}" {{ ($filters['status'] ?? '') == $status ? 'selected' : '' }}>{{ ucfirst($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="lm-filter-field">
                            <label class="lm-filter-lbl">{{ $lmText('Date From', 'ពីថ្ងៃ') }}</label>
                            <input type="date" name="date_from" class="lm-filter-input" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                        <div class="lm-filter-field">
                            <label class="lm-filter-lbl">{{ $lmText('Date To', 'ដល់ថ្ងៃ') }}</label>
                            <input type="date" name="date_to" class="lm-filter-input" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                        <div class="lm-filter-field lm-filter-actions" style="grid-column: span 3;">
                            <button type="submit" class="lm-btn-filter lm-btn-filter-apply" id="loanPaymentFilterApply">
                                <i class="fa fa-filter"></i> {{ $lmText('Apply Filter', 'អនុវត្ត') }}
                            </button>
                            <a href="{{ route('loan-management.payments.index') }}" class="lm-btn-filter lm-btn-filter-reset" id="loanPaymentFilterReset">
                                <i class="fa fa-refresh"></i> {{ $lmText('Reset', 'កំណត់ឡើងវិញ') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payments Table -->
        <div class="lm-table-card">
            <div class="lm-table-card-head">
                <h3 style="font-size: 11px; font-weight: 700; color: #334155; margin: 0; text-transform: uppercase;">
                    <i class="fa fa-list text-primary"></i> {{ $lmText('Payment Records Ledger', 'បញ្ជីប្រតិបត្តិការទូទាត់') }}
                </h3>
            </div>
            <div class="table-responsive">
                <table class="lm-table-dense">
                    <thead>
                        <tr>
                            <th>{{ $lmText('Receipt #', 'លេខបង្កាន់ដៃ') }}</th>
                            <th>{{ $lmText('Paid Date', 'កាលបរិច្ឆេទ') }}</th>
                            <th>{{ $lmText('Installment #', 'លេខកិច្ចសន្យា') }}</th>
                            <th>{{ $lmText('Customer', 'អតិថិជន') }}</th>
                            <th>{{ $lmText('Type', 'ប្រភេទ') }}</th>
                            <th>{{ $lmText('Method', 'វិធីទូទាត់') }}</th>
                            <th class="text-right">{{ $lmText('Amount Paid', 'ចំនួនទឹកប្រាក់') }}</th>
                            <th class="text-center">{{ $lmText('Status', 'ស្ថានភាព') }}</th>
                            <th>{{ $lmText('Reference', 'លេខយោង') }}</th>
                            <th>{{ $lmText('Received By', 'អ្នកទទួលប្រាក់') }}</th>
                            <th class="text-center" style="width: 120px;">{{ $lmText('Action', 'សកម្មភាព') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($payments as $payment)
                        @php
                            $paymentStatus = strtolower((string)($payment->status ?? 'confirmed'));
                            $isPaid = in_array($paymentStatus, ['paid', 'confirmed', 'completed']);
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('loan-management.payments.show', $payment->id) }}" style="font-weight: 700; color: #2563eb;">
                                    {{ $payment->receipt_number ?? ('#'.$payment->id) }}
                                </a>
                            </td>
                            <td>{{ ! empty($payment->paid_date) ? \Carbon\Carbon::parse($payment->paid_date)->format('d-m-Y') : '-' }}</td>
                            <td>
                                @if(Route::has('loan-management.loans.view') && ! empty($payment->loan_id))
                                    <a href="{{ route('loan-management.loans.view', $payment->loan_id) }}" target="_blank" style="font-weight: 600; color: #0f172a;">
                                        {{ $payment->loan_number ?? ('#'.$payment->loan_id) }}
                                    </a>
                                @else
                                    {{ $payment->loan_number ?? '-' }}
                                @endif
                            </td>
                            <td>
                                <strong>{{ $payment->customer_name ?? '-' }}</strong>
                                @if(!empty($payment->customer_phone))
                                    <small class="text-muted" style="display: block; font-size: 10px;">{{ $payment->customer_phone }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="lm-badge lm-badge-info">
                                    {{ \Modules\LoanManagement\Http\Controllers\LoanPaymentController::paymentTypeLabel($payment->payment_type ?? 'monthly') }}
                                </span>
                            </td>
                            <td>
                                <span class="lm-badge lm-badge-gray">
                                    {{ $payment->payment_method ?? '-' }}
                                </span>
                            </td>
                            <td class="text-right" style="font-weight: 700; color: #16a34a; font-size: 12px;">
                                $ {{ number_format((float) ($payment->amount ?? 0), 2) }}
                            </td>
                            <td class="text-center">
                                <span class="lm-badge {{ $isPaid ? 'lm-badge-success' : 'lm-badge-warning' }}">
                                    {{ ucfirst($payment->status ?? '-') }}
                                </span>
                            </td>
                            <td>{{ $payment->reference_number ?? '-' }}</td>
                            <td>{{ $payment->received_by ?? '-' }}</td>
                            <td class="text-center" style="white-space: nowrap;">
                                <a href="{{ route('loan-management.payments.show', $payment->id) }}" class="lm-btn-tbl lm-btn-tbl-info" title="{{ $lmText('View Receipt', 'មើល') }}">
                                    <i class="fa fa-eye"></i>
                                </a>
                                @if(\Modules\LoanManagement\Helpers\LoanMenuHelper::loanUserCan('loan_management.payment|loan_management.payments.create|loan_management.edit'))
                                    <a href="{{ route('loan-management.payments.edit', $payment->id) }}" class="lm-btn-tbl lm-btn-tbl-edit" title="{{ $lmText('Edit', 'កែប្រែ') }}">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    <form method="POST" action="{{ route('loan-management.payments.destroy', $payment->id) }}" style="display:inline;" onsubmit="return confirm('{{ $lmText('Delete this payment? This will update loan balance.', 'តើអ្នកពិតជាចង់លុបការទូទាត់នេះមែនទេ?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="lm-btn-tbl lm-btn-tbl-del" title="{{ $lmText('Delete', 'លុប') }}">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center text-muted" style="padding: 16px;">
                                <i class="fa fa-info-circle"></i> {{ $lmText('No payments found matching your criteria.', 'រកមិនឃើញទិន្នន័យការទូទាត់ទេ។') }}
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card List -->
            <div class="lm-payment-mobile-list">
                @forelse($payments as $payment)
                    @php
                        $paymentShowUrl = route('loan-management.payments.show', $payment->id);
                        $paymentStatus = $payment->status ?? '-';
                        $paymentIsPaid = in_array($paymentStatus, ['paid', 'confirmed', 'completed']);
                    @endphp
                    <div class="lm-payment-mobile-card">
                        <div class="lm-payment-mobile-card-top">
                            <div>
                                <a href="{{ $paymentShowUrl }}" style="font-weight: 700; color: #2563eb;">{{ $payment->receipt_number ?? ('#'.$payment->id) }}</a>
                                <small style="display:block; color: #64748b;">{{ ! empty($payment->paid_date) ? \Carbon\Carbon::parse($payment->paid_date)->format('d-m-Y') : '-' }}</small>
                            </div>
                            <strong style="color: #16a34a; font-size: 13px;">$ {{ number_format((float) ($payment->amount ?? 0), 2) }}</strong>
                        </div>
                        <div style="font-size: 11px; margin-bottom: 4px;">
                            <strong>{{ $payment->customer_name ?? '-' }}</strong> &bull; <span class="text-muted">{{ $payment->loan_number ?? '-' }}</span>
                        </div>
                        <div class="lm-payment-mobile-actions">
                            <a href="{{ $paymentShowUrl }}" class="lm-btn-tbl lm-btn-tbl-info"><i class="fa fa-eye"></i> {{ $lmText('View', 'មើល') }}</a>
                            @if(\Modules\LoanManagement\Helpers\LoanMenuHelper::loanUserCan('loan_management.payment|loan_management.payments.create|loan_management.edit'))
                                <a href="{{ route('loan-management.payments.edit', $payment->id) }}" class="lm-btn-tbl lm-btn-tbl-edit"><i class="fa fa-pencil"></i> {{ $lmText('Edit', 'កែប្រែ') }}</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted" style="padding: 12px;">{{ $lmText('No payments found.', 'រកមិនឃើញទិន្នន័យទេ។') }}</div>
                @endforelse
            </div>

            @if($payments->hasPages())
            <div style="padding: 8px 12px; border-top: 1px solid #e2e8f0; text-align: center;">
                {{ $payments->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('loan_js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var panel = document.getElementById('loanPaymentFilterPanel');
        var title = document.getElementById('loanPaymentFilterTitle');
        var toggle = document.getElementById('loanPaymentFilterToggle');
        var toggleText = document.getElementById('loanPaymentFilterToggleText');
        var toggleIcon = document.getElementById('loanPaymentFilterToggleIcon');
        var form = document.getElementById('loanPaymentFilterForm');
        var reset = document.getElementById('loanPaymentFilterReset');
        var storageKey = 'lm_payment_filters_collapsed_v1';

        if (!panel || !toggle || !toggleText || !toggleIcon) {
            return;
        }

        function setCollapsed(collapsed) {
            panel.classList.toggle('is-collapsed', collapsed);
            toggleText.textContent = collapsed ? '{{ $lmText("Expand", "ពង្រីក") }}' : '{{ $lmText("Collapse", "បង្រួម") }}';
            toggleIcon.classList.toggle('fa-chevron-down', collapsed);
            toggleIcon.classList.toggle('fa-chevron-up', !collapsed);
            try { window.localStorage.setItem(storageKey, collapsed ? '1' : '0'); } catch (e) {}
        }

        function togglePanel() {
            setCollapsed(!panel.classList.contains('is-collapsed'));
        }

        try {
            var savedState = window.localStorage.getItem(storageKey);
            setCollapsed(savedState === null ? true : savedState === '1');
        } catch (e) {
            setCollapsed(true);
        }

        toggle.addEventListener('click', togglePanel);
        if (title) {
            title.addEventListener('click', togglePanel);
        }
        if (form) {
            form.addEventListener('submit', function () { setCollapsed(true); });
        }
        if (reset) {
            reset.addEventListener('click', function () {
                try { window.localStorage.setItem(storageKey, '1'); } catch (e) {}
            });
        }
    });
</script>
@endsection
