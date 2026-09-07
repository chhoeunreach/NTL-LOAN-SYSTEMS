@extends('loanmanagement::layouts.app')
@section('title', (session('user.language', config('app.locale')) === 'km') ? 'របាយការណ៍សង្ខេបកម្ចីប្រចាំឆ្នាំ' : 'Yearly Installment Summary')

@php
    $isKhmer = $isKhmer ?? session('user.language', config('app.locale')) === 'km';
    $bi = fn ($en, $km) => $isKhmer ? $km : $en;
    $money = fn ($value) => '$'.number_format((float) ($value ?? 0), 2);
    $number = fn ($value) => number_format((float) ($value ?? 0), 0);
    $visibleTableMetrics = [
        'loan_count', 'principal_total', 'interest_total', 'loan_total',
        'paid_customer_count', 'collection_payment_total', 'deposit_payment_total', 'payment_total',
        'closed_count', 'closed_principal_total', 'closed_interest_total', 'closed_loan_total', 'closed_paid_total', 'closed_balance_total',
        'bad_count', 'bad_principal_total', 'bad_interest_total', 'bad_loan_total', 'bad_paid_total', 'bad_balance_total',
    ];
    $hasMetricValue = fn ($value) => $value !== null && $value !== '' && (float) $value != 0.0;
    $rowHasVisibleData = fn ($row) => collect($visibleTableMetrics)->contains(fn ($field) => $hasMetricValue($row[$field] ?? 0));
    $displayRows = array_values(array_filter($payload['rows'] ?? [], $rowHasVisibleData));
    $rowMoney = fn ($value) => ! $hasMetricValue($value) ? '' : $money($value);
    $rowNumber = fn ($value) => ! $hasMetricValue($value) ? '' : $number($value);
    $yearOptions = range((int) now()->format('Y'), 2000);
    $dateFrom = $filters['date_from'] ?? ($filters['start_year'].'-01-01');
    $dateTo = $filters['date_to'] ?? ($filters['end_year'].'-12-31');
    $dateRangeDisplay = \Carbon\Carbon::parse($dateFrom)->format('m-d-Y').' - '.\Carbon\Carbon::parse($dateTo)->format('m-d-Y');
    $yearlyLoanDetailFilterPayload = [
        'start_year' => $filters['start_year'],
        'end_year' => $filters['end_year'],
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'location_id' => $filters['location_id'],
        'search' => $filters['search'],
    ];
@endphp

@section('loan_css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<style>
    /* =========================================================
       ULTIMATE POS STANDARD STYLE FOR YEARLY SUMMARY
       ========================================================= */
    .yls-wrap {
        font-family: 'Kantumruy Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        color: #1f2937;
    }

    /* KPI Summary Cards Grid */
    .yls-card-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(140px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    .yls-card {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #ffffff;
        padding: 12px 14px;
        display: flex;
        gap: 12px;
        align-items: center;
        min-height: 54px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, .03);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .yls-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, .06);
    }
    .yls-card-icon {
        width: 40px;
        height: 40px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 17px;
        flex: 0 0 40px;
    }
    .yls-card small {
        display: block;
        color: #64748b;
        font-weight: 700;
        margin-bottom: 2px;
        font-size: 11px;
        line-height: 1.2;
        text-transform: uppercase;
        letter-spacing: .3px;
    }
    .yls-card strong {
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.2;
        word-break: break-word;
    }
    .yls-tone-teal { color: #0f766e; background: #f0fdfa; border: 1px solid #ccfbf1; }
    .yls-tone-blue { color: #2563eb; background: #eff6ff; border: 1px solid #bfdbfe; }
    .yls-tone-green { color: #16a34a; background: #f0fdf4; border: 1px solid #bbf7d0; }
    .yls-tone-orange { color: #d97706; background: #fffbeb; border: 1px solid #fde68a; }
    .yls-tone-purple { color: #7c3aed; background: #f5f3ff; border: 1px solid #ddd6fe; }
    .yls-tone-red { color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; }

    /* Filters Component Styling */
    .lm-pos-filter-grid {
        display: grid;
        grid-template-columns: 2fr 1.5fr 1.5fr auto;
        gap: 12px 14px;
        align-items: end;
        padding: 6px 0;
    }
    @media (max-width: 1200px) {
        .yls-card-grid { grid-template-columns: repeat(3, minmax(140px, 1fr)); }
        .lm-pos-filter-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .yls-card-grid { grid-template-columns: 1fr; }
        .lm-pos-filter-grid { grid-template-columns: 1fr; }
    }
    .lm-pos-filter-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .lm-pos-filter-field label {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        line-height: 1.2;
    }
    .lm-pos-filter-field .form-control {
        height: 38px;
        padding: 6px 12px;
        font-size: 13px;
        color: #1e293b;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        outline: none;
        width: 100%;
        box-shadow: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .lm-pos-filter-field .form-control:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15);
    }
    .lm-pos-filter-field select.form-control {
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        padding-right: 28px;
        -webkit-appearance: none;
        appearance: none;
    }
    .lm-pos-filter-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 2px;
    }
    .lm-btn-pos-filter {
        height: 38px;
        padding: 0 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        background: #0284c7;
        color: #fff;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .lm-btn-pos-filter:hover { background: #0369a1; }
    .lm-btn-pos-reset {
        height: 38px;
        padding: 0 14px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }
    .lm-btn-pos-reset:hover { background: #e2e8f0; color: #1e293b; text-decoration: none; }

    /* Summary Table Styling */
    .yls-table {
        width: 100%;
        min-width: 1280px;
        margin-bottom: 0;
        table-layout: fixed;
        border-collapse: separate;
        border-spacing: 0;
    }
    .yls-table thead th {
        text-align: center;
        vertical-align: middle !important;
        white-space: normal;
        font-size: 11px;
        line-height: 1.2;
        border-color: #dbe3ef !important;
        padding: 6px 4px !important;
        font-weight: 700;
    }
    .yls-table tbody td,
    .yls-table tfoot th {
        vertical-align: middle !important;
        white-space: nowrap;
        font-size: 11px;
        padding: 6px 6px !important;
        border-color: #e6edf5 !important;
    }
    .yls-table th:first-child,
    .yls-table td:first-child { width: 42px; }
    .yls-table th:nth-child(2),
    .yls-table td:nth-child(2) { width: 68px; }
    .yls-table thead tr:first-child th {
        color: #fff;
        height: 32px;
        border-bottom: 0 !important;
        box-shadow: inset 0 -2px 0 rgba(255, 255, 255, .35);
    }
    .yls-table thead tr:nth-child(2) th { height: 30px; }
    .yls-group-label { display: inline-block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; vertical-align: middle; }
    .yls-group-loans { background: #0f766e; color: #fff; }
    .yls-group-payments { background: #15803d; color: #fff; }
    .yls-group-closed { background: #c2410c; color: #fff; }
    .yls-group-risk { background: #dc2626; color: #fff; }
    .yls-table thead tr:nth-child(2) th:nth-child(n+1):nth-child(-n+4),
    .yls-table tbody td:nth-child(n+3):nth-child(-n+6),
    .yls-table tfoot th:nth-child(n+2):nth-child(-n+5) { background: #f0fdfa; }
    .yls-table thead tr:nth-child(2) th:nth-child(n+5):nth-child(-n+8),
    .yls-table tbody td:nth-child(n+7):nth-child(-n+10),
    .yls-table tfoot th:nth-child(n+6):nth-child(-n+9) { background: #f0fdf4; }
    .yls-table thead tr:nth-child(2) th:nth-child(n+9):nth-child(-n+14),
    .yls-table tbody td:nth-child(n+11):nth-child(-n+16),
    .yls-table tfoot th:nth-child(n+10):nth-child(-n+15) { background: #fff7ed; }
    .yls-table thead tr:nth-child(2) th:nth-child(n+15):nth-child(-n+20),
    .yls-table tbody td:nth-child(n+17):nth-child(-n+22),
    .yls-table tfoot th:nth-child(n+16):nth-child(-n+21) { background: #fef2f2; }
    .yls-table tbody tr:hover td { filter: brightness(.97); }
    .yls-table tbody tr { cursor: pointer; }
    .yls-table tbody tr:hover td:first-child { box-shadow: inset 3px 0 0 #0284c7; }
    .yls-total-row th { background: #e2e8f0 !important; color: #0f172a; font-weight: 800; }
    .yls-generated { color: #94a3b8; font-size: 11px; padding: 8px 4px 0; text-align: right; }

    /* Modal Styling */
    .yls-loan-modal {
        position: fixed;
        inset: 0;
        z-index: 10050;
        display: none;
        background: rgba(15, 23, 42, .62);
        padding: 18px;
    }
    .yls-loan-modal.is-open { display: flex; }
    .yls-loan-modal-dialog {
        display: flex;
        flex-direction: column;
        width: 100%;
        height: 100%;
        border-radius: 10px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 24px 80px rgba(15, 23, 42, .35);
    }
    .yls-loan-modal-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        padding: 10px 14px;
        border-bottom: 1px solid #dbe4ef;
        background: #f8fafc;
    }
    .yls-loan-modal-title {
        color: #0f172a;
        font-size: 15px;
        font-weight: 800;
    }
    .yls-loan-modal-close {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: #fff;
        color: #334155;
        height: 30px;
        padding: 0 12px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
    }
    .yls-loan-modal-close:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .yls-loan-modal iframe {
        width: 100%;
        height: 100%;
        border: 0;
        background: #eef3f8;
    }
</style>
@endsection

@section('content_body')
<div class="yls-wrap">

    {{-- Content Header --}}
    <section class="content-header" style="padding: 0 0 16px 0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 700; color: #1e293b; margin: 0;">
                {{ $bi('Yearly Installment Summary', 'របាយការណ៍សង្ខេបកម្ចីប្រចាំឆ្នាំ') }}
                <small style="font-size: 13px; color: #64748b; font-weight: 400; margin-left: 8px;">
                    {{ $bi('Annual loan, schedule, collection, deposit, and overdue totals', 'សរុបកម្ចី កាលវិភាគ ការប្រមូលប្រាក់ ប្រាក់កក់ និងហួសកំណត់ប្រចាំឆ្នាំ') }}
                </small>
            </h1>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <a class="btn btn-success btn-sm" style="font-weight: 600; border-radius: 6px;"
               href="{{ route('loan-management.reports.yearly-loan-summary', array_merge(request()->query(), ['export' => 'csv'])) }}">
                <i class="fa fa-file-excel-o"></i> {{ $bi('Export Excel', 'នាំចេញ Excel') }}
            </a>
            <button type="button" class="btn btn-default btn-sm" onclick="window.print()" style="font-weight: 600; border-radius: 6px;">
                <i class="fa fa-print"></i> {{ $bi('Print', 'បោះពុម្ព') }}
            </button>
        </div>
    </section>

    {{-- KPI Cards --}}
    <div class="yls-card-grid">
        @foreach($payload['cards'] as $card)
            <div class="yls-card">
                <div class="yls-card-icon yls-tone-{{ $card['tone'] }}">
                    <i class="{{ $card['icon'] }}"></i>
                </div>
                <div>
                    <small>{{ $card['label'] }}</small>
                    <strong>{{ $card['value'] }}</strong>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Ultimate POS Collapsible Filters Component --}}
    @component('components.filters', ['title' => __('report.filters'), 'closed' => true])
        <form method="GET" action="{{ route('loan-management.reports.yearly-loan-summary') }}" id="ylsFilterForm">
            <div class="lm-pos-filter-grid">
                {{-- Date Range --}}
                <div class="lm-pos-filter-field">
                    <label>{{ $bi('Date Range', 'ចន្លោះថ្ងៃ') }}</label>
                    <input type="text" name="date_range" id="ylsDateRange" class="form-control" value="{{ $dateRangeDisplay }}" placeholder="{{ $bi('Select date range', 'ជ្រើសរើសចន្លោះថ្ងៃ') }}" autocomplete="off">
                    <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                    <input type="hidden" name="date_to" value="{{ $dateTo }}">
                </div>

                {{-- Location --}}
                <div class="lm-pos-filter-field">
                    <label>{{ $bi('Location', 'សាខា') }}</label>
                    <select name="location_id" class="form-control">
                        <option value="">{{ $bi('All Locations', 'សាខាទាំងអស់') }}</option>
                        @foreach($locations as $id => $name)
                            <option value="{{ $id }}" {{ (string) $filters['location_id'] === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Search --}}
                <div class="lm-pos-filter-field">
                    <label>{{ $bi('Search', 'ស្វែងរក') }}</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ $bi('Installment, customer, phone', 'កម្ចី អតិថិជន ទូរស័ព្ទ') }}">
                </div>

                {{-- Actions --}}
                <div class="lm-pos-filter-actions">
                    <button type="submit" class="lm-btn-pos-filter">
                        <i class="fa fa-filter"></i> {{ $bi('Filter', 'ចម្រោះ') }}
                    </button>
                    <a href="{{ route('loan-management.reports.yearly-loan-summary') }}" class="lm-btn-pos-reset">
                        <i class="fa fa-refresh"></i> {{ $bi('Reset', 'កំណត់ឡើងវិញ') }}
                    </a>
                </div>
            </div>
        </form>
    @endcomponent

    {{-- Ultimate POS Standard Widget Component --}}
    @component('components.widget', ['class' => 'box-primary', 'title' => $bi('Yearly Installment Summary Data', 'ទិន្នន័យសង្ខេបកម្ចីប្រចាំឆ្នាំ')])
        <div class="table-responsive">
            <table class="table table-bordered table-hover yls-table">
                <thead>
                    <tr>
                        <th rowspan="2">{{ $bi('No.', 'ល.រ') }}</th>
                        <th rowspan="2">{{ $bi('Year', 'ឆ្នាំ') }}</th>
                        <th colspan="4" class="yls-group-loans" title="{{ $bi('Registered Installment Customers', 'អតិថិជនចុះឈ្មោះរំលស់') }}"><i class="fa fa-users"></i> <span class="yls-group-label">{{ $bi('Registered', 'ចុះឈ្មោះ') }}</span></th>
                        <th colspan="4" class="yls-group-payments" title="{{ $bi('Installment Customers Paid Total', 'អតិថិជនរំលស់បានបង់ទូរទៅ') }}"><i class="fa fa-money"></i> <span class="yls-group-label">{{ $bi('Paid Total', 'បានបង់សរុប') }}</span></th>
                        <th colspan="6" class="yls-group-closed" title="{{ $bi('Paid Off Installment Customers', 'អតិថិជនរំលស់បានបង់ផ្ដាច់') }}"><i class="fa fa-check-circle-o"></i> <span class="yls-group-label">{{ $bi('Paid Off', 'បង់ផ្ដាច់') }}</span></th>
                        <th colspan="6" class="yls-group-risk" title="{{ $bi('Bad Installment Customers', 'អតិថិជនរំលស់ខូច') }}"><i class="fa fa-warning"></i> <span class="yls-group-label">{{ $bi('Bad / Risk', 'ខូច / ហានិភ័យ') }}</span></th>
                    </tr>
                    <tr>
                        <th>{{ $bi('Count', 'ចំនួន') }}</th>
                        <th>{{ $bi('Principal', 'ប្រាក់ដើម') }}</th>
                        <th>{{ $bi('Interest', 'ការប្រាក់') }}</th>
                        <th>{{ $bi('Total', 'សរុប') }}</th>
                        <th>{{ $bi('Count', 'ចំនួន') }}</th>
                        <th>{{ $bi('Collection', 'ប្រមូលប្រាក់') }}</th>
                        <th>{{ $bi('Deposit', 'ប្រាក់កក់') }}</th>
                        <th>{{ $bi('Total', 'សរុប') }}</th>
                        <th>{{ $bi('Count', 'ចំនួន') }}</th>
                        <th>{{ $bi('Principal', 'ប្រាក់ដើម') }}</th>
                        <th>{{ $bi('Interest', 'ការប្រាក់') }}</th>
                        <th>{{ $bi('Total', 'សរុប') }}</th>
                        <th>{{ $bi('Paid', 'បានបង់') }}</th>
                        <th>{{ $bi('Balance', 'សមតុល្យ') }}</th>
                        <th>{{ $bi('Count', 'ចំនួន') }}</th>
                        <th>{{ $bi('Principal', 'ប្រាក់ដើម') }}</th>
                        <th>{{ $bi('Interest', 'ការប្រាក់') }}</th>
                        <th>{{ $bi('Total', 'សរុប') }}</th>
                        <th>{{ $bi('Paid', 'បានបង់') }}</th>
                        <th>{{ $bi('Balance', 'សមតុល្យ') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($displayRows as $row)
                        <tr data-loan-detail-year="{{ $row['year'] }}" title="{{ $bi('Click to view loan details', 'ចុចដើម្បីមើលព័ត៌មានលម្អិតកម្ចី') }}">
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="text-center"><strong>{{ $row['year'] }}</strong></td>
                            <td class="text-right">{{ $rowNumber($row['loan_count']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['principal_total']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['interest_total']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['loan_total']) }}</td>
                            <td class="text-right">{{ $rowNumber($row['paid_customer_count']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['collection_payment_total']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['deposit_payment_total']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['payment_total']) }}</td>
                            <td class="text-right">{{ $rowNumber($row['closed_count']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['closed_principal_total']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['closed_interest_total']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['closed_loan_total']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['closed_paid_total']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['closed_balance_total']) }}</td>
                            <td class="text-right">{{ $rowNumber($row['bad_count']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['bad_principal_total']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['bad_interest_total']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['bad_loan_total']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['bad_paid_total']) }}</td>
                            <td class="text-right">{{ $rowMoney($row['bad_balance_total']) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="22" class="text-center text-muted" style="padding: 24px !important;">{{ $bi('No data found for this date range.', 'រកមិនឃើញទិន្នន័យសម្រាប់ចន្លោះថ្ងៃនេះទេ។') }}</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    @php($total = $payload['totals'])
                    <tr class="yls-total-row">
                        <th colspan="2" class="text-center">{{ $bi('Total', 'សរុប') }}</th>
                        <th class="text-right">{{ $number($total['loan_count']) }}</th>
                        <th class="text-right">{{ $money($total['principal_total']) }}</th>
                        <th class="text-right">{{ $money($total['interest_total']) }}</th>
                        <th class="text-right">{{ $money($total['loan_total']) }}</th>
                        <th class="text-right">{{ $number($total['paid_customer_count']) }}</th>
                        <th class="text-right">{{ $money($total['collection_payment_total']) }}</th>
                        <th class="text-right">{{ $money($total['deposit_payment_total']) }}</th>
                        <th class="text-right">{{ $money($total['payment_total']) }}</th>
                        <th class="text-right">{{ $number($total['closed_count']) }}</th>
                        <th class="text-right">{{ $money($total['closed_principal_total']) }}</th>
                        <th class="text-right">{{ $money($total['closed_interest_total']) }}</th>
                        <th class="text-right">{{ $money($total['closed_loan_total']) }}</th>
                        <th class="text-right">{{ $money($total['closed_paid_total']) }}</th>
                        <th class="text-right">{{ $money($total['closed_balance_total']) }}</th>
                        <th class="text-right">{{ $number($total['bad_count']) }}</th>
                        <th class="text-right">{{ $money($total['bad_principal_total']) }}</th>
                        <th class="text-right">{{ $money($total['bad_interest_total']) }}</th>
                        <th class="text-right">{{ $money($total['bad_loan_total']) }}</th>
                        <th class="text-right">{{ $money($total['bad_paid_total']) }}</th>
                        <th class="text-right">{{ $money($total['bad_balance_total']) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="yls-generated">
            <i class="fa fa-clock-o"></i> {{ $bi('Generated', 'បានបង្កើត') }} {{ now()->format('Y-m-d H:i') }}
        </div>
    @endcomponent

</div>

<div class="yls-loan-modal" id="ylsLoanModal" aria-hidden="true">
    <div class="yls-loan-modal-dialog">
        <div class="yls-loan-modal-head">
            <div class="yls-loan-modal-title" id="ylsLoanModalTitle">{{ $bi('Installment Details', 'ព័ត៌មានលម្អិតកម្ចី') }}</div>
            <button type="button" class="yls-loan-modal-close" id="ylsLoanModalClose">{{ $bi('Close', 'បិទ') }}</button>
        </div>
        <iframe id="ylsLoanModalFrame" title="{{ $bi('Installment Details', 'ព័ត៌មានលម្អិតកម្ចី') }}"></iframe>
    </div>
</div>
@endsection

@section('loan_js')
<script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/min/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1/daterangepicker.min.js"></script>
<script>
    (function ($) {
        var $form = $('#ylsFilterForm');
        $form.on('change', 'select', function () {
            $form.trigger('submit');
        });

        var displayDateFormat = window.moment_date_format || 'MM-DD-YYYY';
        var dateRangeSettings = window.dateRangeSettings ? $.extend(true, {}, window.dateRangeSettings) : {};

        if (window.moment && $.fn.daterangepicker) {
            var startDate = moment(@json($dateFrom));
            var endDate = moment(@json($dateTo));
            var fyStart = (typeof financial_year !== 'undefined' && financial_year.start && moment(financial_year.start).isValid())
                ? moment(financial_year.start)
                : moment().startOf('year');
            var fyEnd = (typeof financial_year !== 'undefined' && financial_year.end && moment(financial_year.end).isValid())
                ? moment(financial_year.end)
                : moment().endOf('year');

            $('#ylsDateRange').daterangepicker($.extend(true, {}, dateRangeSettings, {
                autoUpdateInput: false,
                showDropdowns: true,
                linkedCalendars: false,
                startDate: startDate,
                endDate: endDate,
                parentEl: 'body',
                opens: 'right',
                drops: 'auto',
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                    'This month last year': [moment().subtract(1, 'year').startOf('month'), moment().subtract(1, 'year').endOf('month')],
                    'This Year': [moment().startOf('year'), moment().endOf('year')],
                    'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                    'Current financial year': [fyStart.clone(), fyEnd.clone()],
                    'Last financial year': [fyStart.clone().subtract(1, 'year'), fyEnd.clone().subtract(1, 'year')]
                },
                locale: $.extend(true, {}, dateRangeSettings.locale || {}, {
                    format: displayDateFormat,
                    separator: ' - ',
                    applyLabel: @json($bi('Apply', 'អនុវត្ត')),
                    cancelLabel: @json($bi('Clear', 'សម្អាត')),
                    customRangeLabel: @json($bi('Custom Range', 'ជ្រើសរើសផ្ទាល់')),
                    toLabel: '~'
                })
            }), function (start, end) {
                $('#ylsDateRange').val(start.format(displayDateFormat) + ' - ' + end.format(displayDateFormat));
                $form.find('[name="date_from"]').val(start.format('YYYY-MM-DD'));
                $form.find('[name="date_to"]').val(end.format('YYYY-MM-DD'));
                $form.trigger('submit');
            });

            $('#ylsDateRange')
                .on('apply.daterangepicker', function (event, picker) {
                    $(this).val(picker.startDate.format(displayDateFormat) + ' - ' + picker.endDate.format(displayDateFormat));
                    $form.find('[name="date_from"]').val(picker.startDate.format('YYYY-MM-DD'));
                    $form.find('[name="date_to"]').val(picker.endDate.format('YYYY-MM-DD'));
                    $form.trigger('submit');
                })
                .on('cancel.daterangepicker', function () {
                    $(this).val('');
                    $form.find('[name="date_from"], [name="date_to"]').val('');
                    $form.trigger('submit');
                });
        } else {
            $('#ylsDateRange').prop('readonly', false).on('change', function () {
                var parts = String($(this).val()).split(/\s+-\s+|\s+~\s+/);
                if (parts.length === 2) {
                    $form.find('[name="date_from"]').val(parts[0]);
                    $form.find('[name="date_to"]').val(parts[1]);
                }
            });
        }

        var detailUrl = @json(route('loan-management.admin-loan.details'));
        var filters = @json($yearlyLoanDetailFilterPayload);
        var labels = {
            all: @json($bi('All Installments', 'កម្ចីទាំងអស់')),
            registered: @json($bi('Registered Installments', 'អតិថិជនចុះឈ្មោះរំលស់')),
            generalPaid: @json($bi('General Installments Paid', 'អតិថិជនរំលស់បានបង់ទូរទៅ')),
            paidOff: @json($bi('Paid Off', 'បង់ផ្ដាច់')),
            badDebt: @json($bi('Bad / Risk', 'ខូច / ហានិភ័យ'))
        };

        function groupForCell(index) {
            if (index >= 2 && index <= 5) return 'registered';
            if (index >= 6 && index <= 9) return 'generalPaid';
            if (index >= 10 && index <= 15) return 'paidOff';
            if (index >= 16 && index <= 21) return 'badDebt';
            return 'all';
        }

        function openLoanModal(year, group) {
            var params = new URLSearchParams();
            params.set('year', year);
            params.set('group', group);
            Object.keys(filters).forEach(function (key) {
                if (filters[key] !== null && filters[key] !== undefined && String(filters[key]) !== '') {
                    params.set(key, filters[key]);
                }
            });

            $('#ylsLoanModalTitle').text((labels[group] || labels.all) + ' - ' + year);
            $('#ylsLoanModalFrame').attr('src', detailUrl + '?' + params.toString());
            $('#ylsLoanModal').addClass('is-open').attr('aria-hidden', 'false');
            $('body').css('overflow', 'hidden');
        }

        function closeLoanModal() {
            $('#ylsLoanModal').removeClass('is-open').attr('aria-hidden', 'true');
            $('#ylsLoanModalFrame').attr('src', 'about:blank');
            $('body').css('overflow', '');
        }

        $('.yls-table tbody').on('click', 'td', function (event) {
            if ($(event.target).closest('a, button, input, select, textarea').length) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();

            var $row = $(this).closest('tr');
            var year = $row.data('loan-detail-year');
            if (!year) {
                return;
            }
            openLoanModal(year, groupForCell(this.cellIndex));
        });

        $('#ylsLoanModalClose').on('click', closeLoanModal);
        $('#ylsLoanModal').on('click', function (event) {
            if (event.target === this) {
                closeLoanModal();
            }
        });
        $(document).on('keydown', function (event) {
            if (event.key === 'Escape') {
                closeLoanModal();
            }
        });
    })(jQuery);
</script>
@endsection
