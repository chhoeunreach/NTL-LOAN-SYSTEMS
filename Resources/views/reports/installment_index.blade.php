@extends('loanmanagement::layouts.app')
@section('title', (session('user.language', config('app.locale')) === 'km') ? 'របាយការណ៍រំលស់' : 'Installment Reports')

@php
    $isKhmer = $isKhmer ?? session('user.language', config('app.locale')) === 'km';
    $bi = fn ($en, $km) => $isKhmer ? $km : $en;
    $money = fn ($value) => '$'.number_format((float) ($value ?? 0), 2);
    $number = fn ($value) => number_format((float) ($value ?? 0), 0);

    $dateFrom = $filters['date_from'] ?? '';
    $dateTo = $filters['date_to'] ?? '';
    $dateRangeDisplay = $dateFrom && $dateTo
        ? \Carbon\Carbon::parse($dateFrom)->format('m-d-Y').' - '.\Carbon\Carbon::parse($dateTo)->format('m-d-Y')
        : '';
@endphp

@section('loan_css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap.min.css">
<style>
    /* =========================================================
       ULTIMATE POS STANDARD STYLE FOR INSTALLMENT REPORTS
       ========================================================= */
    .ir-page {
        font-family: 'Kantumruy Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    /* KPI Summary Cards */
    .ir-cards {
        display: grid;
        grid-template-columns: repeat(5, minmax(150px, 1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    .ir-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .ir-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
    }
    .ir-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 9px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
        background: #eff6ff;
        color: #2563eb;
    }
    .ir-card small {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .4px;
        margin-bottom: 2px;
    }
    .ir-card strong {
        display: block;
        font-size: 19px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.15;
    }

    /* Filters Component Styling */
    .lm-pos-filter-grid {
        display: grid;
        grid-template-columns: 2fr 1.5fr 1.5fr 1.2fr 1.2fr 1.2fr auto;
        gap: 12px 14px;
        align-items: end;
        padding: 6px 0;
    }
    @media (max-width: 1400px) {
        .lm-pos-filter-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 768px) {
        .lm-pos-filter-grid { grid-template-columns: 1fr; }
        .ir-cards { grid-template-columns: 1fr; }
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

    /* Ultimate POS DataTables Toolbar Layout */
    .lm-dt-top {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: wrap !important;
        gap: 12px !important;
        padding: 12px 16px !important;
        background: #ffffff !important;
        border-bottom: 1px solid #f1f5f9 !important;
    }
    .lm-dt-length label {
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        margin: 0 !important;
        font-weight: 500 !important;
        font-size: 13px !important;
        color: #475569 !important;
    }
    .lm-dt-length select {
        height: 34px !important;
        padding: 2px 28px 2px 10px !important;
        border-radius: 6px !important;
        border: 1px solid #cbd5e1 !important;
        font-size: 13px !important;
        color: #1e293b !important;
        background-color: #fff !important;
        outline: none !important;
    }
    .lm-dt-buttons {
        display: inline-flex !important;
        align-items: center !important;
        gap: 4px !important;
        flex-wrap: wrap !important;
    }
    .lm-dt-buttons .btn {
        border-radius: 6px !important;
        padding: 6px 12px !important;
        font-size: 12.5px !important;
        font-weight: 600 !important;
        border: 1px solid #cbd5e1 !important;
        background: #ffffff !important;
        color: #334155 !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
        transition: all 0.15s ease !important;
    }
    .lm-dt-buttons .btn:hover {
        background: #f8fafc !important;
        border-color: #94a3b8 !important;
        color: #0f172a !important;
    }
    .lm-dt-search {
        margin: 0 !important;
    }
    .lm-dt-search label {
        margin: 0 !important;
        display: block !important;
    }
    .lm-dt-search input {
        height: 34px !important;
        min-width: 220px !important;
        border-radius: 6px !important;
        border: 1px solid #cbd5e1 !important;
        padding: 6px 12px !important;
        font-size: 13px !important;
        outline: none !important;
        background: #ffffff !important;
        box-shadow: none !important;
        transition: border-color 0.15s ease !important;
    }
    .lm-dt-search input:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.15) !important;
    }

    /* Table Styling */
    .lm-table-dense {
        margin-bottom: 0 !important;
        border-collapse: collapse !important;
    }
    .lm-table-dense th {
        font-size: 12.5px !important;
        font-weight: 700 !important;
        text-transform: uppercase !important;
        letter-spacing: 0.3px !important;
        color: #475569 !important;
        background: #f8fafc !important;
        border-top: 1px solid #e2e8f0 !important;
        border-bottom: 1px solid #cbd5e1 !important;
        padding: 10px 12px !important;
        white-space: nowrap !important;
        vertical-align: middle !important;
    }
    .lm-table-dense td {
        font-size: 13px !important;
        color: #1e293b !important;
        padding: 10px 12px !important;
        vertical-align: middle !important;
        border-top: 1px solid #f1f5f9 !important;
        white-space: nowrap !important;
    }
    .lm-table-dense tbody tr:hover {
        background-color: #f8fafc !important;
    }

    /* Bottom Info & Pagination */
    .lm-dt-bottom {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        flex-wrap: wrap !important;
        gap: 12px !important;
        padding: 12px 16px !important;
        background: #ffffff !important;
        border-top: 1px solid #f1f5f9 !important;
    }
    .lm-dt-info {
        font-size: 13px !important;
        color: #64748b !important;
        padding: 0 !important;
    }
    .lm-dt-pagination .pagination {
        margin: 0 !important;
    }
    .lm-dt-pagination .pagination > li > a {
        border-radius: 4px !important;
        margin: 0 2px !important;
        border: 1px solid #e2e8f0 !important;
        color: #475569 !important;
    }
    .lm-dt-pagination .pagination > .active > a {
        background-color: #0284c7 !important;
        border-color: #0284c7 !important;
        color: #ffffff !important;
    }
    .ir-status {
        border-radius: 999px;
        display: inline-block;
        padding: 3px 8px;
        background: #eef2ff;
        color: #4338ca;
        font-size: 11px;
        font-weight: 700;
    }
</style>
@endsection

@section('content_body')
<div class="ir-page">

    {{-- Content Header --}}
    <section class="content-header" style="padding: 0 0 16px 0; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <h1 style="font-size: 22px; font-weight: 700; color: #1e293b; margin: 0;">
            {{ $bi('Installment Reports', 'របាយការណ៍រំលស់') }}
            <small style="font-size: 13px; color: #64748b; font-weight: 400; margin-left: 8px;">
                {{ $bi('Search loans, payment status, schedules, and outstanding balances', 'ស្វែងរកកម្ចី ស្ថានភាពបង់ប្រាក់ កាលវិភាគ និងសមតុល្យ') }}
            </small>
        </h1>
        <button type="button" class="btn btn-default btn-sm" onclick="window.print()" style="font-weight: 600; border-radius: 6px;">
            <i class="fa fa-print"></i> {{ $bi('Print', 'បោះពុម្ព') }}
        </button>
    </section>

    {{-- KPI Cards --}}
    <div class="ir-cards">
        <div class="ir-card">
            <span class="ir-card-icon"><i class="fa fa-file-text-o"></i></span>
            <div>
                <small>{{ $bi('Installments', 'កម្ចី') }}</small>
                <strong>{{ $number($summary['count'] ?? 0) }}</strong>
            </div>
        </div>
        <div class="ir-card">
            <span class="ir-card-icon" style="background:#ecfdf5; color:#16a34a;"><i class="fa fa-money"></i></span>
            <div>
                <small>{{ $bi('Principal', 'ប្រាក់ដើម') }}</small>
                <strong>{{ $money($summary['principal'] ?? 0) }}</strong>
            </div>
        </div>
        <div class="ir-card">
            <span class="ir-card-icon" style="background:#eff6ff; color:#2563eb;"><i class="fa fa-check-circle"></i></span>
            <div>
                <small>{{ $bi('Paid', 'បានបង់') }}</small>
                <strong>{{ $money($summary['paid'] ?? 0) }}</strong>
            </div>
        </div>
        <div class="ir-card">
            <span class="ir-card-icon" style="background:#fffbeb; color:#d97706;"><i class="fa fa-balance-scale"></i></span>
            <div>
                <small>{{ $bi('Balance', 'សមតុល្យ') }}</small>
                <strong>{{ $money($summary['balance'] ?? 0) }}</strong>
            </div>
        </div>
        <div class="ir-card">
            <span class="ir-card-icon" style="background:#fef2f2; color:#dc2626;"><i class="fa fa-warning"></i></span>
            <div>
                <small>{{ $bi('Overdue', 'ហួសកំណត់') }}</small>
                <strong>{{ $number($summary['overdue'] ?? 0) }}</strong>
            </div>
        </div>
    </div>

    {{-- Ultimate POS Standard Collapsible Filters Component --}}
    @component('components.filters', ['title' => __('report.filters'), 'closed' => true])
        <form method="GET" action="{{ route('loan-management.reports.index') }}" id="installmentReportFilterForm">
            <div class="lm-pos-filter-grid">
                {{-- Date Range --}}
                <div class="lm-pos-filter-field">
                    <label>{{ $bi('Date Range', 'ចន្លោះថ្ងៃ') }}</label>
                    <input type="text" name="date_range" id="installmentReportDateRange" value="{{ $dateRangeDisplay }}" class="form-control" placeholder="{{ $bi('Select date range', 'ជ្រើសរើសចន្លោះថ្ងៃ') }}" autocomplete="off">
                    <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                    <input type="hidden" name="date_to" value="{{ $dateTo }}">
                </div>

                {{-- Location --}}
                <div class="lm-pos-filter-field">
                    <label>{{ $bi('Location', 'ទីតាំង') }}</label>
                    <select name="location_id" class="form-control">
                        <option value="">{{ $bi('All locations', 'គ្រប់ទីតាំង') }}</option>
                        @foreach($locations as $key => $name)
                            <option value="{{ $key }}" @selected(($filters['location_id'] ?? '') === $key)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Search --}}
                <div class="lm-pos-filter-field">
                    <label>{{ $bi('Search', 'ស្វែងរក') }}</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ $bi('Installment, invoice, customer', 'កម្ចី វិក្កយបត្រ អតិថិជន') }}">
                </div>

                {{-- Status --}}
                <div class="lm-pos-filter-field">
                    <label>{{ $bi('Installment status', 'ស្ថានភាពកម្ចី') }}</label>
                    <select name="status" class="form-control">
                        <option value="">{{ $bi('All statuses', 'គ្រប់ស្ថានភាព') }}</option>
                        @foreach($statusOptions as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Payment Status --}}
                <div class="lm-pos-filter-field">
                    <label>{{ $bi('Payment status', 'ស្ថានភាពបង់ប្រាក់') }}</label>
                    <select name="payment_status" class="form-control">
                        <option value="">{{ $bi('All payment statuses', 'គ្រប់ស្ថានភាពបង់ប្រាក់') }}</option>
                        @foreach($paymentStatusOptions as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['payment_status'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Collector --}}
                <div class="lm-pos-filter-field">
                    <label>{{ $bi('Collector', 'អ្នកប្រមូល') }}</label>
                    <input type="text" name="collector" value="{{ $filters['collector'] ?? '' }}" class="form-control" placeholder="{{ $bi('Collector name', 'ឈ្មោះអ្នកប្រមូល') }}">
                </div>

                {{-- Actions --}}
                <div class="lm-pos-filter-actions">
                    <button type="submit" class="lm-btn-pos-filter">
                        <i class="fa fa-filter"></i> {{ $bi('Apply', 'អនុវត្ត') }}
                    </button>
                    <a href="{{ route('loan-management.reports.index') }}" class="lm-btn-pos-reset">
                        <i class="fa fa-refresh"></i> {{ $bi('Reset', 'សម្អាត') }}
                    </a>
                </div>
            </div>
        </form>
    @endcomponent

    {{-- Ultimate POS Standard Widget Component --}}
    @component('components.widget', ['class' => 'box-primary', 'title' => $bi('Installment Data', 'ទិន្នន័យរំលស់')])
        <div class="table-responsive">
            <table class="lm-table-dense table table-bordered table-striped table-hover" id="installmentReportsTable" style="width: 100%; margin-bottom: 0;">
                <thead>
                    <tr style="background: #f8fafc; color: #475569;">
                        <th>{{ $bi('Installment #', 'លេខកម្ចី') }}</th>
                        <th>{{ $bi('Date', 'ថ្ងៃ') }}</th>
                        <th>{{ $bi('Invoice', 'វិក្កយបត្រ') }}</th>
                        <th>{{ $bi('Customer', 'អតិថិជន') }}</th>
                        <th>{{ $bi('Phone', 'ទូរស័ព្ទ') }}</th>
                        <th>{{ $bi('Location', 'ទីតាំង') }}</th>
                        <th style="text-align: center;">{{ $bi('Status', 'ស្ថានភាព') }}</th>
                        <th style="text-align: center;">{{ $bi('Payment', 'បង់ប្រាក់') }}</th>
                        <th class="text-right">{{ $bi('Total', 'សរុប') }}</th>
                        <th class="text-right">{{ $bi('Principal', 'ប្រាក់ដើម') }}</th>
                        <th class="text-right">{{ $bi('Paid', 'បានបង់') }}</th>
                        <th class="text-right">{{ $bi('Balance', 'សមតុល្យ') }}</th>
                        <th class="text-right">{{ $bi('Term', 'រយៈពេល') }}</th>
                        <th class="text-right">{{ $bi('Schedules', 'កាលវិភាគ') }}</th>
                        <th>{{ $bi('Next due', 'បង់បន្ទាប់') }}</th>
                        <th>{{ $bi('Last payment', 'បង់ចុងក្រោយ') }}</th>
                        <th>{{ $bi('Collector', 'អ្នកប្រមូល') }}</th>
                        <th style="text-align: center;">{{ $bi('Risk', 'ហានិភ័យ') }}</th>
                        <th>{{ $bi('Note', 'កំណត់ចំណាំ') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td>
                                <a href="{{ route('loan-management.loans.view', $row->id) }}" style="font-weight: 700; color: #0284c7; text-decoration: none;">
                                    {{ $row->loan_number }}
                                </a>
                            </td>
                            <td>{{ $row->loan_date ? \Carbon\Carbon::parse($row->loan_date)->format('d M Y') : '-' }}</td>
                            <td>{{ $row->invoice_no ?: '-' }}</td>
                            <td><strong>{{ $row->customer_name ?: '-' }}</strong></td>
                            <td>{{ $row->customer_phone ?: '-' }}</td>
                            <td>{{ $row->location_name ?: '-' }}</td>
                            <td style="text-align: center;"><span class="ir-status">{{ ucwords(str_replace('_', ' ', (string) $row->status)) }}</span></td>
                            <td style="text-align: center;">{{ $row->payment_status ? ucwords(str_replace('_', ' ', (string) $row->payment_status)) : '-' }}</td>
                            <td class="text-right">{{ $money($row->total_amount) }}</td>
                            <td class="text-right">{{ $money($row->principal_amount) }}</td>
                            <td class="text-right">{{ $money($row->paid_amount) }}</td>
                            <td class="text-right"><strong style="color: #0f172a;">{{ $money($row->balance_amount) }}</strong></td>
                            <td class="text-right">{{ $number($row->term_count) }}</td>
                            <td class="text-right">{{ $number($row->paid_schedule_count) }} / {{ $number($row->schedule_count) }}</td>
                            <td>{{ $row->next_due_date ? \Carbon\Carbon::parse($row->next_due_date)->format('d M Y') : '-' }}</td>
                            <td>{{ $row->last_payment_at ? \Carbon\Carbon::parse($row->last_payment_at)->format('d M Y') : '-' }}</td>
                            <td>{{ $row->collector_name ?: '-' }}</td>
                            <td style="text-align: center;">
                                @if((int) $row->is_overdue === 1)
                                    <span class="label label-danger">{{ $bi('Overdue', 'ហួសកំណត់') }}</span>
                                @else
                                    <span class="label label-success">{{ $bi('Normal', 'ធម្មតា') }}</span>
                                @endif
                            </td>
                            <td>{{ $row->note ?: '-' }}</td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
        </div>
    @endcomponent

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
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
<script>
$(document).ready(function () {
    var $filterForm = $('#installmentReportFilterForm');
    $filterForm.on('change', 'select', function () {
        $filterForm.submit();
    });

    var displayDateFormat = window.moment_date_format || 'MM-DD-YYYY';
    var dateRangeSettings = window.dateRangeSettings ? $.extend(true, {}, window.dateRangeSettings) : {};
    var $dateRange = $('#installmentReportDateRange');

    if (window.moment && $.fn.daterangepicker && $dateRange.length) {
        var startDate = @json($dateFrom) ? moment(@json($dateFrom)) : moment().startOf('month');
        var endDate = @json($dateTo) ? moment(@json($dateTo)) : moment().endOf('month');
        var fyStart = (typeof financial_year !== 'undefined' && financial_year.start && moment(financial_year.start).isValid())
            ? moment(financial_year.start)
            : moment().startOf('year');
        var fyEnd = (typeof financial_year !== 'undefined' && financial_year.end && moment(financial_year.end).isValid())
            ? moment(financial_year.end)
            : moment().endOf('year');

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
        }));

        $dateRange
            .on('apply.daterangepicker', function (event, picker) {
                $(this).val(picker.startDate.format(displayDateFormat) + ' - ' + picker.endDate.format(displayDateFormat));
                $filterForm.find('[name="date_from"]').val(picker.startDate.format('YYYY-MM-DD'));
                $filterForm.find('[name="date_to"]').val(picker.endDate.format('YYYY-MM-DD'));
                $filterForm.submit();
            })
            .on('cancel.daterangepicker', function () {
                $(this).val('');
                $filterForm.find('[name="date_from"], [name="date_to"]').val('');
                $filterForm.submit();
            });
    }

    if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#installmentReportsTable')) {
        var exportTitle = @json($bi('Installment Reports', 'របាយការណ៍រំលស់'));
        var tableButtons = [];
        if ($.fn.dataTable.Buttons) {
            tableButtons = [
                {
                    extend: 'copy',
                    text: 'Copy',
                    className: 'btn btn-default btn-sm',
                    title: exportTitle,
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend: 'csv',
                    text: '<i class="fa fa-file-text-o"></i> Export CSV',
                    className: 'btn btn-default btn-sm',
                    title: exportTitle,
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend: 'excel',
                    text: '<i class="fa fa-file-excel-o"></i> Export Excel',
                    className: 'btn btn-default btn-sm',
                    title: exportTitle,
                    exportOptions: { columns: ':visible' }
                },
                {
                    extend: 'print',
                    text: '<i class="fa fa-print"></i> Print',
                    className: 'btn btn-default btn-sm',
                    title: exportTitle,
                    exportOptions: { columns: ':visible', stripHtml: true }
                },
                {
                    extend: 'colvis',
                    text: '<i class="fa fa-columns"></i> Column visibility',
                    className: 'btn btn-default btn-sm'
                },
                {
                    extend: 'pdf',
                    text: '<i class="fa fa-file-pdf-o"></i> Export PDF <i class="fa fa-caret-down" style="margin-left:2px;"></i>',
                    className: 'btn btn-default btn-sm',
                    title: exportTitle,
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: { columns: ':visible' }
                }
            ];
        }

        $('#installmentReportsTable').DataTable({
            dom: '<"lm-dt-top"<"lm-dt-length"l><"lm-dt-buttons"B><"lm-dt-search"f>>rt<"lm-dt-bottom"<"lm-dt-info"i><"lm-dt-pagination"p>>',
            buttons: tableButtons,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "{{ $bi('All', 'ទាំងអស់') }}"]],
            order: [[1, 'desc']],
            autoWidth: false,
            language: {
                search: '',
                searchPlaceholder: 'Search ...',
                lengthMenu: 'Show _MENU_ entries',
                emptyTable: '{{ $bi("No installment data found.", "មិនមានទិន្នន័យកម្ចីទេ។") }}',
                info: '{{ $bi("Showing _START_ to _END_ of _TOTAL_ entries", "បង្ហាញពី _START_ ដល់ _END_ នៃ _TOTAL_ ធាតុ") }}',
                infoEmpty: '{{ $bi("Showing 0 to 0 of 0 entries", "បង្ហាញ 0 នៃ 0 ធាតុ") }}',
                infoFiltered: '({{ $bi("filtered from _MAX_ total entries", "ចម្រាញ់ចេញពី _MAX_ ធាតុសរុប") }})',
                paginate: {
                    first: '{{ $bi("First", "ដំបូង") }}',
                    last: '{{ $bi("Last", "ចុងក្រោយ") }}',
                    next: '{{ $bi("Next", "បន្ទាប់") }}',
                    previous: '{{ $bi("Previous", "មុន") }}'
                }
            },
            columnDefs: [
                { targets: [8, 9, 10, 11, 12, 13], className: 'text-right' },
                { targets: [6, 7, 17], className: 'text-center' }
            ]
        });
    }
});
</script>
@endsection
