@extends('loanmanagement::layouts.app')

@php
    $isKhmer = $isKhmer ?? (session('user.language', config('app.locale')) === 'km');
    $text = fn ($en, $km) => $isKhmer ? $km : $en;
    $money = fn ($val) => '$' . number_format((float) ($val ?? 0), 2);
    $dateFrom = $filters['date_from'] ?? '';
    $dateTo = $filters['date_to'] ?? '';
    $dateRangeDisplay = $dateFrom && $dateTo
        ? \Carbon\Carbon::parse($dateFrom)->format('m-d-Y').' - '.\Carbon\Carbon::parse($dateTo)->format('m-d-Y')
        : '';
    $hasActiveFilters = $dateRangeDisplay !== '' || trim((string) ($filters['search'] ?? '')) !== '';
@endphp

@section('title', $text('Blacklist & Risk Registry', 'បញ្ជីខ្មៅអតិថិជន'))

@section('loan_css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap.min.css">
<style>
    :root {
        --bl-danger: #e11d48;
        --bl-danger-hover: #be123c;
        --bl-danger-bg: #fff1f2;
        --bl-danger-border: #fecdd3;
        --bl-slate-900: #0f172a;
        --bl-slate-800: #1e293b;
        --bl-slate-700: #334155;
        --bl-slate-500: #64748b;
        --bl-slate-200: #e2e8f0;
        --bl-slate-100: #f1f5f9;
        --bl-slate-50: #f8fafc;
    }

    .lm-blacklist-page {
        padding: 6px 0 24px;
        color: var(--bl-slate-800);
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    /* --- HERO HEADER --- */
    .lm-blacklist-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 22px 28px;
        background: #ffffff;
        border: 1px solid var(--bl-slate-200);
        border-left: 4px solid var(--bl-danger);
        border-radius: 16px;
        box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.06);
        margin-bottom: 20px;
    }
    .lm-blacklist-hero-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .lm-blacklist-hero-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        background: var(--bl-danger-bg);
        color: var(--bl-danger);
        display: inline-grid;
        place-items: center;
        font-size: 22px;
        flex-shrink: 0;
        box-shadow: 0 4px 12px rgba(225, 29, 72, 0.15);
    }
    .lm-blacklist-hero h1 {
        margin: 0;
        font-size: 22px;
        font-weight: 800;
        color: var(--bl-slate-900);
        letter-spacing: -0.02em;
    }
    .lm-blacklist-hero p {
        margin: 4px 0 0;
        font-size: 13px;
        color: var(--bl-slate-500);
    }
    .lm-blacklist-hero-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .lm-btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 16px;
        border-radius: 10px;
        background: #ffffff;
        border: 1px solid var(--bl-slate-200);
        color: var(--bl-slate-700);
        font-size: 13px;
        font-weight: 600;
        text-decoration: none !important;
        transition: all 0.2s ease;
    }
    .lm-btn-back:hover {
        background: var(--bl-slate-50);
        color: var(--bl-slate-900);
        border-color: #cbd5e1;
    }
    .lm-btn-flag {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 18px;
        border-radius: 10px;
        background: var(--bl-danger);
        border: 1px solid var(--bl-danger);
        color: #ffffff !important;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(225, 29, 72, 0.35);
        transition: all 0.2s ease;
    }
    .lm-btn-flag:hover {
        background: var(--bl-danger-hover);
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(225, 29, 72, 0.45);
    }

    /* --- KPI STATS CARDS --- */
    .lm-blacklist-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 20px;
    }
    .lm-stat-card {
        background: #ffffff;
        border: 1px solid var(--bl-slate-200);
        border-radius: 14px;
        padding: 16px 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
        transition: all 0.2s ease;
    }
    .lm-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    }
    .lm-stat-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: inline-grid;
        place-items: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .lm-stat-danger .lm-stat-card-icon { background: #fff1f2; color: #e11d48; }
    .lm-stat-amber .lm-stat-card-icon { background: #fffbeb; color: #d97706; }
    .lm-stat-indigo .lm-stat-card-icon { background: #eef2ff; color: #4f46e5; }
    .lm-stat-slate .lm-stat-card-icon { background: #f1f5f9; color: #64748b; }
    .lm-stat-card-content small {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: var(--bl-slate-500);
        margin-bottom: 2px;
    }
    .lm-stat-card-content strong {
        display: block;
        font-size: 22px;
        font-weight: 800;
        color: var(--bl-slate-900);
        line-height: 1.1;
    }

    .lm-blacklist-filter {
        background: #ffffff;
        border: 1px solid var(--bl-slate-200);
        border-radius: 14px;
        margin-bottom: 18px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.035);
        overflow: hidden;
    }
    .lm-blacklist-filter-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 13px 16px;
        border-bottom: 1px solid var(--bl-slate-100);
    }
    .lm-blacklist-filter-head h3 {
        margin: 0;
        font-size: 15px;
        font-weight: 800;
        color: var(--bl-slate-900);
    }
    .lm-blacklist-filter-body {
        padding: 16px;
    }
    .lm-blacklist-filter-grid {
        display: grid;
        grid-template-columns: minmax(220px, 1.5fr) minmax(220px, 1fr) auto;
        gap: 12px;
        align-items: end;
    }
    .lm-blacklist-filter-grid label {
        display: block;
        margin-bottom: 5px;
        color: var(--bl-slate-700);
        font-size: 12px;
        font-weight: 700;
    }
    .lm-blacklist-filter-grid .form-control {
        height: 38px;
        border-color: var(--bl-slate-200);
        border-radius: 8px;
        box-shadow: none;
    }
    .lm-blacklist-filter-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    /* --- TABLE CARD --- */
    .lm-blacklist-card {
        background: #ffffff;
        border: 1px solid var(--bl-slate-200);
        border-radius: 16px;
        box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05);
        padding: 20px;
        overflow: hidden;
    }

    /* DataTables Toolbar Flex */
    .lm-dt-top {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: nowrap !important;
        gap: 16px !important;
        margin-bottom: 16px !important;
    }
    .lm-dt-length { flex: 0 0 auto !important; }
    .lm-dt-length label {
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        margin: 0 !important;
        white-space: nowrap !important;
        font-size: 13px !important;
        color: var(--bl-slate-700) !important;
        font-weight: 500 !important;
    }
    .lm-dt-length select {
        height: 36px !important;
        padding: 4px 10px !important;
        border: 1px solid var(--bl-slate-200) !important;
        border-radius: 8px !important;
        background: #ffffff !important;
        color: var(--bl-slate-700) !important;
        font-weight: 600 !important;
        outline: none !important;
    }
    .lm-dt-buttons {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        flex-wrap: wrap !important;
        justify-content: center !important;
    }
    .lm-dt-buttons .btn {
        background: #ffffff !important;
        border: 1px solid var(--bl-slate-200) !important;
        border-radius: 8px !important;
        color: var(--bl-slate-700) !important;
        font-size: 12px !important;
        font-weight: 600 !important;
        padding: 6px 12px !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02) !important;
        transition: all 0.15s ease !important;
    }
    .lm-dt-buttons .btn:hover {
        background: var(--bl-slate-50) !important;
        border-color: #cbd5e1 !important;
        color: var(--bl-slate-900) !important;
    }
    .lm-dt-search { flex: 0 0 auto !important; margin-left: auto !important; }
    .lm-dt-search label { margin: 0 !important; display: block !important; }
    .lm-dt-search input {
        height: 38px !important;
        min-width: 240px !important;
        padding: 6px 14px !important;
        border: 1px solid var(--bl-slate-200) !important;
        border-radius: 999px !important;
        font-size: 13px !important;
        background: var(--bl-slate-50) !important;
        outline: none !important;
        transition: all 0.2s ease !important;
    }
    .lm-dt-search input:focus {
        background: #ffffff !important;
        border-color: var(--bl-danger) !important;
        box-shadow: 0 0 0 3px rgba(225, 29, 72, 0.15) !important;
    }

    /* Table Styles */
    #blacklist_table {
        border-collapse: separate !important;
        border-spacing: 0;
        width: 100% !important;
        border: none !important;
    }
    #blacklist_table thead th {
        background: var(--bl-slate-50) !important;
        color: var(--bl-slate-700) !important;
        font-size: 11.5px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.05em !important;
        padding: 13px 16px !important;
        border: none !important;
        border-top: 1px solid var(--bl-slate-200) !important;
        border-bottom: 2px solid #cbd5e1 !important;
        vertical-align: middle !important;
        white-space: nowrap !important;
    }
    #blacklist_table thead th:first-child { border-top-left-radius: 10px; }
    #blacklist_table thead th:last-child { border-top-right-radius: 10px; }
    #blacklist_table tbody td {
        padding: 13px 16px !important;
        vertical-align: middle !important;
        border-top: 1px solid var(--bl-slate-100) !important;
        border-bottom: none !important;
        border-left: none !important;
        border-right: none !important;
        font-size: 13px;
        color: var(--bl-slate-700);
    }
    #blacklist_table tbody tr:hover td {
        background: rgba(225, 29, 72, 0.02) !important;
    }

    /* Badges & Tags */
    .lm-badge-blacklist {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 999px;
        background: var(--bl-danger-bg);
        color: var(--bl-danger);
        border: 1px solid var(--bl-danger-border);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.02em;
    }
    .lm-reason-pill {
        display: inline-block;
        max-width: 220px;
        background: #fffbeb;
        color: #b45309;
        border: 1px solid #fde68a;
        padding: 4px 9px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.3;
        white-space: normal;
        word-break: break-word;
    }
    .lm-cust-link {
        font-weight: 700;
        color: var(--bl-slate-900);
        text-decoration: none !important;
    }
    .lm-cust-link:hover {
        color: var(--bl-danger);
    }
    .lm-phone-link {
        color: var(--bl-slate-500);
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        text-decoration: none !important;
    }
    .lm-phone-link:hover {
        color: var(--bl-slate-900);
    }

    /* Chips for quick reasons in Modal */
    .lm-quick-reason-chip {
        display: inline-block;
        padding: 5px 11px;
        margin: 0 4px 6px 0;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .lm-quick-reason-chip:hover {
        background: #fff1f2;
        color: #e11d48;
        border-color: #fecdd3;
    }

    @media (max-width: 992px) {
        .lm-blacklist-stats { grid-template-columns: repeat(2, 1fr); }
        .lm-blacklist-filter-grid { grid-template-columns: 1fr 1fr; }
        .lm-dt-top { flex-direction: column !important; align-items: stretch !important; }
        .lm-dt-search { margin-left: 0 !important; width: 100% !important; }
        .lm-dt-search input { width: 100% !important; }
    }
    @media (max-width: 640px) {
        .lm-blacklist-hero { flex-direction: column; align-items: flex-start; }
        .lm-blacklist-stats { grid-template-columns: 1fr; }
        .lm-blacklist-filter-grid { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content_body')
<div class="lm-blacklist-page">

    <!-- HERO HEADER -->
    <div class="lm-blacklist-hero">
        <div class="lm-blacklist-hero-left">
            <div class="lm-blacklist-hero-icon">
                <i class="fa fa-user-times"></i>
            </div>
            <div>
                <h1>{{ $text('Blacklist & Risk Registry', 'បញ្ជីខ្មៅ និងបញ្ជីហានិភ័យ') }}</h1>
                <p>{{ $text('Monitor, manage, and prevent high-risk or defaulting customers from obtaining new installment financing.', 'គ្រប់គ្រង និងទប់ស្កាត់អតិថិជនមានហានិភ័យខ្ពស់ ឬខូចបំណុលពីការទទួលកម្ចីថ្មី។') }}</p>
            </div>
        </div>
        <div class="lm-blacklist-hero-actions">
            <a href="{{ route('loan-management.loans') }}" class="lm-btn-back">
                <i class="fa fa-arrow-left"></i> {{ $text('All Installments', 'កម្ចីទាំងអស់') }}
            </a>
            <button type="button" class="lm-btn-flag" data-toggle="modal" data-target="#modalAddBlacklist">
                <i class="fa fa-plus-circle"></i> {{ $text('Flag Customer', 'ដាក់បញ្ចូលបញ្ជីខ្មៅ') }}
            </button>
        </div>
    </div>

    <!-- 4 KPI CARDS -->
    <div class="lm-blacklist-stats">
        <div class="lm-stat-card lm-stat-danger">
            <div class="lm-stat-card-icon"><i class="fa fa-user-times"></i></div>
            <div class="lm-stat-card-content">
                <small>{{ $text('Total Blacklisted', 'អតិថិជនបញ្ជីខ្មៅសរុប') }}</small>
                <strong>{{ number_format($summary['total_blacklisted'] ?? 0) }}</strong>
            </div>
        </div>
        <div class="lm-stat-card lm-stat-amber">
            <div class="lm-stat-card-icon"><i class="fa fa-exclamation-triangle"></i></div>
            <div class="lm-stat-card-content">
                <small>{{ $text('Debt at Risk', 'បំណុលមានហានិភ័យ') }}</small>
                <strong>{{ $money($summary['total_debt_at_risk'] ?? 0) }}</strong>
            </div>
        </div>
        <div class="lm-stat-card lm-stat-indigo">
            <div class="lm-stat-card-icon"><i class="fa fa-shield"></i></div>
            <div class="lm-stat-card-content">
                <small>{{ $text('Linked Installments', 'កម្ចីជាប់ពាក់ព័ន្ធ') }}</small>
                <strong>{{ number_format($summary['linked_loans_count'] ?? 0) }}</strong>
            </div>
        </div>
        <div class="lm-stat-card lm-stat-slate">
            <div class="lm-stat-card-icon"><i class="fa fa-calendar-times-o"></i></div>
            <div class="lm-stat-card-content">
                <small>{{ $text('Flagged This Month', 'បានដាក់ក្នុងខែនេះ') }}</small>
                <strong>{{ number_format($summary['flagged_this_month'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    <div class="lm-blacklist-filter">
        <div class="lm-blacklist-filter-head">
            <h3><i class="fa fa-filter"></i> {{ $text('Filters', 'តម្រង') }}</h3>
            <button type="button" class="btn btn-default btn-sm" data-bl-filter-toggle data-toggle="collapse" data-target="#blacklistFilters" aria-expanded="{{ $hasActiveFilters ? 'true' : 'false' }}" aria-controls="blacklistFilters">
                <span data-bl-filter-label>{{ $hasActiveFilters ? $text('Collapse', 'បិទ') : $text('Expand', 'បើក') }}</span> <i class="fa {{ $hasActiveFilters ? 'fa-chevron-up' : 'fa-chevron-down' }}"></i>
            </button>
        </div>
        <div class="lm-blacklist-filter-body collapse {{ $hasActiveFilters ? 'in' : '' }}" id="blacklistFilters">
            <form method="GET">
                <div class="lm-blacklist-filter-grid">
                    <div>
                        <label>{{ $text('Date Range', 'ចន្លោះថ្ងៃ') }}</label>
                        <input type="text" name="date_range" id="blacklistDateRange" value="{{ $dateRangeDisplay }}" class="form-control" placeholder="{{ $text('Select flagged date range', 'ជ្រើសរើសចន្លោះថ្ងៃបានដាក់បញ្ជី') }}" autocomplete="off">
                        <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                        <input type="hidden" name="date_to" value="{{ $dateTo }}">
                    </div>
                    <div>
                        <label>{{ $text('Search', 'ស្វែងរក') }}</label>
                        <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ $text('Customer, phone, code, ID, reason', 'អតិថិជន ទូរស័ព្ទ កូដ អត្តសញ្ញាណ មូលហេតុ') }}">
                    </div>
                    <div class="lm-blacklist-filter-actions">
                        <button type="submit" class="btn btn-danger"><i class="fa fa-search"></i> {{ $text('Apply', 'អនុវត្ត') }}</button>
                        <a href="{{ route('loan-management.blacklist.index') }}" class="btn btn-default">{{ $text('Reset', 'កំណត់ឡើងវិញ') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLE CARD -->
    <div class="lm-blacklist-card">
        <div class="table-responsive">
            <table class="table table-striped" id="blacklist_table" width="100%">
                <thead>
                    <tr>
                        <th style="width:70px;">{{ $text('Code', 'កូដ') }}</th>
                        <th>{{ $text('Customer', 'អតិថិជន') }}</th>
                        <th>{{ $text('Document / ID', 'អត្តសញ្ញាណប័ណ្ណ') }}</th>
                        <th>{{ $text('Blacklist Reason', 'មូលហេតុបញ្ជីខ្មៅ') }}</th>
                        <th>{{ $text('Flagged Date', 'កាលបរិច្ឆេទ') }}</th>
                        <th>{{ $text('Flagged By', 'អ្នករាយការណ៍') }}</th>
                        <th style="text-align:right;">{{ $text('Debt at Risk', 'បំណុលនៅសល់') }}</th>
                        <th style="text-align:center;">{{ $text('Status', 'ស្ថានភាព') }}</th>
                        <th style="text-align:center; width:90px;">{{ $text('Action', 'សកម្មភាព') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $c)
                    @php
                        $reason = trim((string) ($c->blacklist_reason ?? ''));
                        $reasonDisplay = $reason !== '' ? $reason : $text('Not specified', 'មិនបានបញ្ជាក់');
                        $flaggedDate = !empty($c->blacklist_date) ? \Carbon\Carbon::parse($c->blacklist_date)->format('d M Y') : '-';
                        $flaggedAgo = !empty($c->blacklist_date) ? \Carbon\Carbon::parse($c->blacklist_date)->diffForHumans() : '';
                        $staff = $staffNames[$c->blacklist_by] ?? ($c->blacklist_by ? 'Staff #'.$c->blacklist_by : '-');
                    @endphp
                    <tr>
                        <td>
                            <span style="font-family:ui-monospace, monospace; font-weight:700; color:var(--bl-danger);">
                                {{ $c->customer_code ?: ('#' . $c->id) }}
                            </span>
                        </td>
                        <td>
                            <div>
                                <a href="{{ route('loan-management.customers.show', $c->id) }}" class="lm-cust-link">
                                    {{ $c->name }}
                                </a>
                                @if(!empty($c->khmer_name))
                                    <small class="text-muted" style="display:block;">{{ $c->khmer_name }}</small>
                                @endif
                                @if(!empty($c->phone))
                                    <a href="tel:{{ $c->phone }}" class="lm-phone-link">
                                        <i class="fa fa-phone"></i> {{ $c->phone }}
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td>
                            @if(!empty($c->id_card_number))
                                <span style="font-family:ui-monospace, monospace; font-weight:600; color:#334155;">
                                    <i class="fa fa-id-card-o text-muted"></i> {{ $c->id_card_number }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <span class="lm-reason-pill">
                                <i class="fa fa-info-circle text-amber"></i> {{ $reasonDisplay }}
                            </span>
                        </td>
                        <td>
                            <div style="white-space:nowrap;">
                                <strong>{{ $flaggedDate }}</strong>
                                @if($flaggedAgo)
                                    <br><small class="text-muted">{{ $flaggedAgo }}</small>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span style="font-weight:600; color:#475569;">{{ $staff }}</span>
                        </td>
                        <td style="text-align:right;">
                            <strong style="color:var(--bl-danger); font-size:14px;">
                                {{ $money($c->total_debt ?? 0) }}
                            </strong>
                            <br>
                            <small class="text-muted">
                                {{ (int) ($c->total_loans ?? 0) }} {{ $text('Installments', 'កម្ចី') }}
                            </small>
                        </td>
                        <td style="text-align:center;">
                            <span class="lm-badge-blacklist">
                                <i class="fa fa-ban"></i> {{ $text('Blacklisted', 'បញ្ជីខ្មៅ') }}
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <div class="btn-group btn-group-xs">
                                <button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" aria-expanded="false" style="border-radius:6px; font-weight:600;">
                                    <i class="fa fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                    <li>
                                        <a href="{{ route('loan-management.customers.show', $c->id) }}">
                                            <i class="fa fa-user"></i> {{ $text('View Customer Profile', 'មើលព័ត៌មានអតិថិជន') }}
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0)" class="js-edit-reason"
                                           data-id="{{ $c->id }}"
                                           data-name="{{ $c->name }}"
                                           data-reason="{{ e($c->blacklist_reason) }}">
                                            <i class="fa fa-pencil"></i> {{ $text('Edit Reason', 'កែប្រែមូលហេតុ') }}
                                        </a>
                                    </li>
                                    <li class="divider"></li>
                                    <li>
                                        <a href="javascript:void(0)" class="text-success js-whitelist-customer"
                                           data-id="{{ $c->id }}"
                                           data-name="{{ $c->name }}">
                                            <i class="fa fa-check-circle"></i> {{ $text('Remove from Blacklist', 'ដកចេញពីបញ្ជីខ្មៅ') }}
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($customers->isEmpty())
                <div class="text-center text-muted" style="padding:40px 16px;">
                    <i class="fa fa-check-circle-o text-success" style="font-size:36px; display:block; margin-bottom:8px;"></i>
                    <strong>{{ $text('No blacklisted customers found.', 'មិនមានអតិថិជននៅក្នុងបញ្ជីខ្មៅទេ។') }}</strong>
                    <p class="text-muted" style="margin-top:4px; font-size:12px;">{{ $text('All customers have clean credit standing.', 'អតិថិជនទាំងអស់មានប្រវត្តិទូទាត់ល្អ។') }}</p>
                </div>
            @endif
        </div>
    </div>

</div>

<!-- MODAL: ADD CUSTOMER TO BLACKLIST -->
<div class="modal fade" id="modalAddBlacklist" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">
            <form action="" method="POST" id="formAddBlacklist">
                @csrf
                <input type="hidden" name="blacklist_status" value="1">
                <div class="modal-header" style="background:#fff1f2; border-bottom:1px solid #fecdd3; padding:16px 20px;">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" style="color:#e11d48; font-weight:800;">
                        <i class="fa fa-user-times"></i> {{ $text('Flag Customer to Blacklist', 'ដាក់បញ្ចូលអតិថិជនទៅក្នុងបញ្ជីខ្មៅ') }}
                    </h4>
                </div>
                <div class="modal-body" style="padding:20px;">
                    <div class="form-group">
                        <label for="select_blacklist_customer" style="font-weight:700; color:#1e293b;">
                            {{ $text('Select Customer', 'ជ្រើសរើសអតិថិជន') }} <span class="text-danger">*</span>
                        </label>
                        <select id="select_blacklist_customer" class="form-control select2" style="width:100%;" required>
                            <option value="">-- {{ $text('Search customer by name or phone...', 'ស្វែងរកតាមឈ្មោះ ឬទូរស័ព្ទ...') }} --</option>
                            @foreach($eligibleCustomers as $ec)
                                <option value="{{ $ec->id }}" data-action="{{ route('loan-management.customers.blacklist', $ec->id) }}">
                                    {{ $ec->name }} {{ $ec->phone ? '('.$ec->phone.')' : '' }} {{ $ec->customer_code ? '['.$ec->customer_code.']' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group" style="margin-top:16px;">
                        <label for="blacklist_reason_input" style="font-weight:700; color:#1e293b;">
                            {{ $text('Reason for Blacklisting', 'មូលហេតុនៃការដាក់បញ្ជីខ្មៅ') }} <span class="text-danger">*</span>
                        </label>
                        <textarea name="blacklist_reason" id="blacklist_reason_input" rows="3" class="form-control" placeholder="{{ $text('Describe default behavior, refusal to pay, fraudulent document, etc.', 'ពិពណ៌នាអំពីអាកប្បកិរិយាយឺតយ៉ាវ បដិសេធមិនបង់ប្រាក់ ក្លែងបន្លំឯកសារ...') }}" required style="border-radius:8px;"></textarea>

                        <div style="margin-top:8px;">
                            <small class="text-muted" style="display:block; margin-bottom:4px; font-weight:600;">
                                {{ $text('Quick Reason Suggestions:', 'ជម្រើសមូលហេតុរហ័ស៖') }}
                            </small>
                            <span class="lm-quick-reason-chip" data-text="{{ $text('Defaulted >90 days / Refused to settle loan', 'យឺតយ៉ាវលើសពី 90 ថ្ងៃ / បដិសេធមិនព្រមទូទាត់') }}">
                                {{ $text('Defaulted >90 days', 'យឺតយ៉ាវ >90 ថ្ងៃ') }}
                            </span>
                            <span class="lm-quick-reason-chip" data-text="{{ $text('Fraudulent identity / forged documents', 'ក្លែងបន្លំអត្តសញ្ញាណ / ឯកសារក្លែងក្លាយ') }}">
                                {{ $text('Identity Fraud', 'ក្លែងបន្លំអត្តសញ្ញាណ') }}
                            </span>
                            <span class="lm-quick-reason-chip" data-text="{{ $text('Absconded / Changed phone & unreachable', 'រត់គេចខ្លួន / ប្តូរលេខទូរស័ព្ទមិនអាចទាក់ទងបាន') }}">
                                {{ $text('Unreachable', 'មិនអាចទាក់ទងបាន') }}
                            </span>
                            <span class="lm-quick-reason-chip" data-text="{{ $text('Repossession dispute / Asset hidden', 'ជម្លោះរឹបអូសទ្រព្យ / លាក់បាំងទ្រព្យ') }}">
                                {{ $text('Asset Dispute', 'ជម្លោះទ្រព្យ') }}
                            </span>
                        </div>
                    </div>

                    <div class="alert alert-warning" style="margin-top:16px; margin-bottom:0; font-size:12px; border-radius:8px;">
                        <i class="fa fa-warning"></i>
                        {{ $text('Blacklisting a customer will restrict them from applying for new installment loans across all branches.', 'ការដាក់បញ្ចូលបញ្ជីខ្មៅនឹងរារាំងអតិថិជននេះពីការស្នើសុំកម្ចីរំលស់ថ្មីនៅគ្រប់សាខា។') }}
                    </div>
                </div>
                <div class="modal-footer" style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:12px 20px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ $text('Cancel', 'បោះបង់') }}</button>
                    <button type="submit" class="btn btn-danger" style="font-weight:700;">
                        <i class="fa fa-ban"></i> {{ $text('Confirm Blacklist', 'បញ្ជាក់ការដាក់បញ្ជីខ្មៅ') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: EDIT BLACKLIST REASON -->
<div class="modal fade" id="modalEditReason" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">
            <form action="" method="POST" id="formEditReason">
                @csrf
                <input type="hidden" name="blacklist_status" value="1">
                <div class="modal-header" style="background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:16px 20px;">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" style="color:#0f172a; font-weight:800;">
                        <i class="fa fa-pencil"></i> {{ $text('Update Blacklist Reason', 'កែប្រែមូលហេតុបញ្ជីខ្មៅ') }}
                    </h4>
                </div>
                <div class="modal-body" style="padding:20px;">
                    <p style="font-size:13px; color:#475569;">
                        {{ $text('Customer:', 'អតិថិជន៖') }} <strong id="edit_customer_name" style="color:#0f172a;"></strong>
                    </p>
                    <div class="form-group">
                        <label for="edit_reason_input" style="font-weight:700;">{{ $text('Reason', 'មូលហេតុ') }}</label>
                        <textarea name="blacklist_reason" id="edit_reason_input" rows="3" class="form-control" required style="border-radius:8px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:12px 20px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ $text('Cancel', 'បោះបង់') }}</button>
                    <button type="submit" class="btn btn-primary" style="font-weight:700;">
                        <i class="fa fa-save"></i> {{ $text('Save Changes', 'រក្សាទុក') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: UNBLOCK CUSTOMER (WHITELIST) -->
<div class="modal fade" id="modalWhitelist" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content" style="border-radius:14px; overflow:hidden;">
            <form action="" method="POST" id="formWhitelist">
                @csrf
                <input type="hidden" name="blacklist_status" value="0">
                <input type="hidden" name="blacklist_reason" value="">
                <div class="modal-header" style="background:#ecfdf5; border-bottom:1px solid #a7f3d0; padding:16px 20px;">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title" style="color:#059669; font-weight:800;">
                        <i class="fa fa-check-circle"></i> {{ $text('Remove from Blacklist', 'ដកចេញពីបញ្ជីខ្មៅ') }}
                    </h4>
                </div>
                <div class="modal-body" style="padding:20px; text-align:center;">
                    <p style="font-size:14px; color:#334155; margin-bottom:8px;">
                        {{ $text('Are you sure you want to unblock', 'តើអ្នកប្រាកដជាចង់ដក') }}
                        <strong id="whitelist_customer_name" style="color:#0f172a; display:block; margin:6px 0; font-size:15px;"></strong>
                        {{ $text('from the blacklist?', 'ចេញពីបញ្ជីខ្មៅមែនទេ?') }}
                    </p>
                    <small class="text-muted">{{ $text('Customer will be allowed to apply for loans again.', 'អតិថិជននឹងអាចស្នើសុំកម្ចីឡើងវិញបាន។') }}</small>
                </div>
                <div class="modal-footer" style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:12px 20px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ $text('Cancel', 'បោះបង់') }}</button>
                    <button type="submit" class="btn btn-success" style="font-weight:700;">
                        <i class="fa fa-check"></i> {{ $text('Confirm Unblock', 'បញ្ជាក់ដកចេញ') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('loan_js')
<script src="https://cdn.jsdelivr.net/npm/moment@2.30.1/min/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1/daterangepicker.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function(){
    if ($.fn.select2) {
        $('#select_blacklist_customer').select2({
            dropdownParent: $('#modalAddBlacklist')
        });
    }

    var $dateRange = $('#blacklistDateRange');
    var $filterForm = $dateRange.closest('form');
    var displayDateFormat = window.moment_date_format || 'MM-DD-YYYY';
    var dateRangeSettings = window.dateRangeSettings ? $.extend(true, {}, window.dateRangeSettings) : {};

    if (window.moment && $.fn.daterangepicker && $dateRange.length) {
        var startDate = @json($dateFrom) ? moment(@json($dateFrom)) : moment();
        var endDate = @json($dateTo) ? moment(@json($dateTo)) : moment();
        var fyStart = (typeof financial_year !== 'undefined' && financial_year.start && moment(financial_year.start).isValid()) ? moment(financial_year.start) : moment().startOf('year');
        var fyEnd = (typeof financial_year !== 'undefined' && financial_year.end && moment(financial_year.end).isValid()) ? moment(financial_year.end) : moment().endOf('year');

        $dateRange.daterangepicker($.extend(true, {}, dateRangeSettings, {
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
                'This Year': [moment().startOf('year'), moment().endOf('year')],
                'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
                'Current financial year': [fyStart.clone(), fyEnd.clone()],
                'Last financial year': [fyStart.clone().subtract(1, 'year'), fyEnd.clone().subtract(1, 'year')]
            },
            locale: $.extend(true, {}, dateRangeSettings.locale || {}, {
                format: displayDateFormat,
                separator: ' - ',
                applyLabel: @json($text('Apply', 'អនុវត្ត')),
                cancelLabel: @json($text('Clear', 'សម្អាត')),
                customRangeLabel: @json($text('Custom Range', 'ជ្រើសរើសផ្ទាល់')),
                toLabel: '~'
            })
        }));

        $dateRange
            .on('apply.daterangepicker', function (event, picker) {
                $(this).val(picker.startDate.format(displayDateFormat) + ' - ' + picker.endDate.format(displayDateFormat));
                $filterForm.find('[name="date_from"]').val(picker.startDate.format('YYYY-MM-DD'));
                $filterForm.find('[name="date_to"]').val(picker.endDate.format('YYYY-MM-DD'));
            })
            .on('cancel.daterangepicker', function () {
                $(this).val('');
                $filterForm.find('[name="date_from"], [name="date_to"]').val('');
            });
    }

    $('#blacklistFilters')
        .on('shown.bs.collapse', function () {
            var button = $('[data-bl-filter-toggle]');
            button.attr('aria-expanded', 'true');
            button.find('[data-bl-filter-label]').text(@json($text('Collapse', 'បិទ')));
            button.find('i').attr('class', 'fa fa-chevron-up');
        })
        .on('hidden.bs.collapse', function () {
            var button = $('[data-bl-filter-toggle]');
            button.attr('aria-expanded', 'false');
            button.find('[data-bl-filter-label]').text(@json($text('Expand', 'បើក')));
            button.find('i').attr('class', 'fa fa-chevron-down');
        });

    // Initialize DataTable only when the body has real rows matching the header.
    if ($('#blacklist_table tbody tr').length) {
        $('#blacklist_table').DataTable({
            pageLength: 25,
            dom: "<'lm-dt-top'<'lm-dt-length'l><'lm-dt-buttons'B><'lm-dt-search'f>>" +
                 "<'row'<'col-sm-12'tr>>" +
                 "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            buttons: [
                { extend: 'excel', text: '<i class="fa fa-file-excel-o"></i> Excel', className: 'btn btn-default btn-sm', exportOptions: { columns: [0,1,2,3,4,5,6] } },
                { extend: 'print', text: '<i class="fa fa-print"></i> Print', className: 'btn btn-default btn-sm', exportOptions: { columns: [0,1,2,3,4,5,6] } }
            ],
            language: {
                search: "_INPUT_",
                searchPlaceholder: @json($text('Search blacklist...', 'ស្វែងរកបញ្ជីខ្មៅ...')),
                lengthMenu: @json($text('Show _MENU_', 'បង្ហាញ _MENU_')),
                info: @json($text('Showing _START_ to _END_ of _TOTAL_ entries', 'បង្ហាញពី _START_ ដល់ _END_ នៃ _TOTAL_ ធាតុ')),
                paginate: {
                    next: @json($text('Next', 'បន្ទាប់')),
                    previous: @json($text('Prev', 'មុន'))
                }
            },
            order: [[4, 'desc']]
        });
    }

    // Quick chips in Add Modal
    $(document).on('click', '.lm-quick-reason-chip', function(){
        var txt = $(this).data('text');
        $('#blacklist_reason_input').val(txt);
    });

    // On select customer, update form action
    $('#select_blacklist_customer').on('change', function(){
        var action = $(this).find(':selected').data('action');
        $('#formAddBlacklist').attr('action', action || '');
    });

    // Edit reason modal trigger
    $(document).on('click', '.js-edit-reason', function(){
        var id = $(this).data('id');
        var name = $(this).data('name');
        var reason = $(this).data('reason');

        var actionUrl = "{{ url('loan-management/customers') }}/" + id + "/blacklist";
        $('#formEditReason').attr('action', actionUrl);
        $('#edit_customer_name').text(name);
        $('#edit_reason_input').val(reason);
        $('#modalEditReason').modal('show');
    });

    // Whitelist / Unblock modal trigger
    $(document).on('click', '.js-whitelist-customer', function(){
        var id = $(this).data('id');
        var name = $(this).data('name');

        var actionUrl = "{{ url('loan-management/customers') }}/" + id + "/blacklist";
        $('#formWhitelist').attr('action', actionUrl);
        $('#whitelist_customer_name').text(name);
        $('#modalWhitelist').modal('show');
    });
});
</script>
@endsection
