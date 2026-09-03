@extends('loanmanagement::layouts.app')
@section('title', (session('user.language', config('app.locale')) === 'km') ? 'របាយការណ៍រំលស់' : 'Installment Reports')

@php
    $isKhmer = $isKhmer ?? session('user.language', config('app.locale')) === 'km';
    $bi = fn ($en, $km) => $isKhmer ? $km : $en;
    $money = fn ($value) => '$'.number_format((float) ($value ?? 0), 2);
    $number = fn ($value) => number_format((float) ($value ?? 0), 0);
@endphp

@section('loan_css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap.min.css">
<style>
    .ir-page { color: #172033; }
    .ir-header { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
    .ir-title { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: 0; }
    .ir-subtitle { margin: 3px 0 0; color: #64748b; font-size: 13px; }
    .ir-cards { display: grid; grid-template-columns: repeat(5, minmax(150px, 1fr)); gap: 10px; margin-bottom: 12px; }
    .ir-card { background: #fff; border: 1px solid #dfe7f1; border-radius: 8px; padding: 12px; box-shadow: 0 8px 18px rgba(15,23,42,.06); display: flex; gap: 10px; align-items: center; min-height: 76px; }
    .ir-card-icon { width: 38px; height: 38px; border-radius: 8px; display: grid; place-items: center; flex: 0 0 38px; background: #eff6ff; color: #2563eb; }
    .ir-card small { display: block; color: #64748b; font-size: 12px; font-weight: 700; margin-bottom: 5px; }
    .ir-card strong { font-size: 18px; font-weight: 800; }
    .ir-panel { background: #fff; border: 1px solid #dfe7f1; border-radius: 8px; box-shadow: 0 8px 18px rgba(15,23,42,.06); margin-bottom: 14px; overflow: hidden; }
    .ir-panel-head { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 12px 14px; border-bottom: 1px solid #edf2f7; }
    .ir-panel-title { margin: 0; font-size: 16px; font-weight: 800; }
    .ir-filter-body { padding: 14px; }
    .ir-filter-grid { display: grid; grid-template-columns: repeat(4, minmax(165px, 1fr)); gap: 12px; align-items: end; }
    .ir-filter-date { grid-column: span 2; }
    .ir-filter-grid label { font-size: 12px; color: #475569; font-weight: 700; margin-bottom: 5px; }
    .ir-filter-grid .form-control { height: 36px; border-color: #d7e0eb; border-radius: 6px; }
    .ir-filter-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .ir-table-wrap { overflow-x: auto; padding: 14px; }
    .ir-table { min-width: 1520px; margin: 0; width: 100% !important; }
    .ir-table thead th { background: #f8fafc; color: #334155; border-bottom: 1px solid #dfe7f1 !important; font-size: 12px; text-transform: uppercase; white-space: nowrap; }
    .ir-table tbody td { vertical-align: middle !important; white-space: nowrap; }
    .ir-status { border-radius: 999px; display: inline-block; padding: 4px 9px; background: #eef2ff; color: #4338ca; font-size: 12px; font-weight: 800; }
    .ir-dt-panel .dataTables_wrapper .row:first-child { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin: 0 0 16px; }
    .ir-dt-panel .dataTables_wrapper .row:first-child:before,
    .ir-dt-panel .dataTables_wrapper .row:first-child:after { display: none; }
    .ir-dt-panel .dataTables_length { text-align: left; white-space: nowrap; }
    .ir-dt-panel .dataTables_length label { font-weight: 500; color: #172033; }
    .ir-dt-panel .dataTables_length select { height: 36px; min-width: 84px; border: 1px solid #cfd8e5; box-shadow: none; margin: 0 6px; }
    .ir-dt-panel .dt-buttons { display: inline-flex; justify-content: center; gap: 8px; flex-wrap: wrap; float: none; }
    .ir-dt-panel .dt-buttons .btn,
    .ir-dt-panel .dt-button { border: 1px solid #b8c3d3 !important; border-radius: 8px !important; background: #fff !important; color: #94a3b8 !important; font-weight: 700; padding: 5px 12px !important; box-shadow: none !important; }
    .ir-dt-panel .dataTables_filter { text-align: right; }
    .ir-dt-panel .dataTables_filter label { width: 100%; margin: 0; }
    .ir-dt-panel .dataTables_filter input { height: 36px; width: 240px !important; border: 1px solid #cfd8e5; border-radius: 0; box-shadow: none; margin-left: 0; }
    .ir-dt-panel .dataTables_info { color: #64748b; padding: 12px 0; }
    .ir-dt-panel .dataTables_paginate { padding-top: 8px; }
    @media (max-width: 1200px) { .ir-cards { grid-template-columns: repeat(2, 1fr); } .ir-filter-grid { grid-template-columns: repeat(2, 1fr); } .ir-dt-panel .dataTables_wrapper .row:first-child { display: block; } .ir-dt-panel .dataTables_length, .ir-dt-panel .dataTables_filter, .ir-dt-panel .dt-buttons { text-align: left; margin-bottom: 8px; } }
    @media (max-width: 768px) { .ir-cards, .ir-filter-grid { grid-template-columns: 1fr; } .ir-header { flex-direction: column; align-items: flex-start; } }
</style>
@endsection

@section('content_body')
@php
    $dateFrom = $filters['date_from'] ?? '';
    $dateTo = $filters['date_to'] ?? '';
    $dateRangeDisplay = $dateFrom && $dateTo
        ? \Carbon\Carbon::parse($dateFrom)->format('m-d-Y').' - '.\Carbon\Carbon::parse($dateTo)->format('m-d-Y')
        : '';
@endphp
<section class="content-header ir-page">
    <div class="ir-header">
        <div>
            <h1 class="ir-title">{{ $bi('Installment Reports', 'របាយការណ៍រំលស់') }}</h1>
            <p class="ir-subtitle">{{ $bi('Search loans, payment status, schedules, and outstanding balances.', 'ស្វែងរកកម្ចី ស្ថានភាពបង់ប្រាក់ កាលវិភាគ និងសមតុល្យ។') }}</p>
        </div>
        <button type="button" class="btn btn-default btn-sm" onclick="window.print()"><i class="fa fa-print"></i> {{ $bi('Print', 'បោះពុម្ព') }}</button>
    </div>
</section>

<section class="content ir-page">
    <div class="ir-cards">
        <div class="ir-card"><span class="ir-card-icon"><i class="fa fa-file-text-o"></i></span><span><small>{{ $bi('Loans', 'កម្ចី') }}</small><strong>{{ $number($summary['count'] ?? 0) }}</strong></span></div>
        <div class="ir-card"><span class="ir-card-icon"><i class="fa fa-money"></i></span><span><small>{{ $bi('Principal', 'ប្រាក់ដើម') }}</small><strong>{{ $money($summary['principal'] ?? 0) }}</strong></span></div>
        <div class="ir-card"><span class="ir-card-icon"><i class="fa fa-check-circle"></i></span><span><small>{{ $bi('Paid', 'បានបង់') }}</small><strong>{{ $money($summary['paid'] ?? 0) }}</strong></span></div>
        <div class="ir-card"><span class="ir-card-icon"><i class="fa fa-balance-scale"></i></span><span><small>{{ $bi('Balance', 'សមតុល្យ') }}</small><strong>{{ $money($summary['balance'] ?? 0) }}</strong></span></div>
        <div class="ir-card"><span class="ir-card-icon"><i class="fa fa-warning"></i></span><span><small>{{ $bi('Overdue', 'ហួសកំណត់') }}</small><strong>{{ $number($summary['overdue'] ?? 0) }}</strong></span></div>
    </div>

    <div class="ir-panel">
        <div class="ir-panel-head">
            <h3 class="ir-panel-title"><i class="fa fa-filter"></i> {{ $bi('Filters', 'តម្រង') }}</h3>
            <button type="button" class="btn btn-default btn-sm" data-ir-toggle data-toggle="collapse" data-target="#installmentReportFilters" aria-expanded="false" aria-controls="installmentReportFilters">
                <span data-ir-label>{{ $bi('Expand', 'បើក') }}</span> <i class="fa fa-chevron-down"></i>
            </button>
        </div>
        <div class="ir-filter-body collapse" id="installmentReportFilters" data-ir-body>
            <form method="GET">
                <div class="ir-filter-grid">
                    <div class="ir-filter-date">
                        <label>{{ $bi('Date Range', 'ចន្លោះថ្ងៃ') }}</label>
                        <input type="text" name="date_range" id="installmentReportDateRange" value="{{ $dateRangeDisplay }}" class="form-control" placeholder="{{ $bi('Select date range', 'ជ្រើសរើសចន្លោះថ្ងៃ') }}" autocomplete="off">
                        <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                        <input type="hidden" name="date_to" value="{{ $dateTo }}">
                    </div>
                    <div>
                        <label>{{ $bi('Location', 'ទីតាំង') }}</label>
                        <select name="location_id" class="form-control">
                            <option value="">{{ $bi('All locations', 'គ្រប់ទីតាំង') }}</option>
                            @foreach($locations as $key => $name)
                                <option value="{{ $key }}" @selected(($filters['location_id'] ?? '') === $key)>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label>{{ $bi('Search', 'ស្វែងរក') }}</label><input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="{{ $bi('Loan, invoice, customer', 'កម្ចី វិក្កយបត្រ អតិថិជន') }}"></div>
                    <div>
                        <label>{{ $bi('Loan status', 'ស្ថានភាពកម្ចី') }}</label>
                        <select name="status" class="form-control">
                            <option value="">{{ $bi('All statuses', 'គ្រប់ស្ថានភាព') }}</option>
                            @foreach($statusOptions as $key => $label)
                                <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>{{ $bi('Payment status', 'ស្ថានភាពបង់ប្រាក់') }}</label>
                        <select name="payment_status" class="form-control">
                            <option value="">{{ $bi('All payment statuses', 'គ្រប់ស្ថានភាពបង់ប្រាក់') }}</option>
                            @foreach($paymentStatusOptions as $key => $label)
                                <option value="{{ $key }}" @selected(($filters['payment_status'] ?? '') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div><label>{{ $bi('Collector', 'អ្នកប្រមូល') }}</label><input type="text" name="collector" value="{{ $filters['collector'] ?? '' }}" class="form-control"></div>
                    <div class="ir-filter-actions">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> {{ $bi('Apply', 'អនុវត្ត') }}</button>
                        <a href="{{ url()->current() }}" class="btn btn-default">{{ $bi('Reset', 'កំណត់ឡើងវិញ') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="ir-panel ir-dt-panel">
        <div class="ir-panel-head">
            <h3 class="ir-panel-title"><i class="fa fa-table"></i> {{ $bi('Installment Data', 'ទិន្នន័យរំលស់') }}</h3>
            <span class="ir-status">{{ $number(method_exists($rows, 'count') ? $rows->count() : count($rows)) }} {{ $bi('records', 'ទិន្នន័យ') }}</span>
        </div>
        <div class="ir-table-wrap">
            <table class="table table-bordered table-hover ir-table" id="installmentReportsTable">
                <thead>
                    <tr>
                        <th>{{ $bi('Loan #', 'លេខកម្ចី') }}</th>
                        <th>{{ $bi('Date', 'ថ្ងៃ') }}</th>
                        <th>{{ $bi('Invoice', 'វិក្កយបត្រ') }}</th>
                        <th>{{ $bi('Customer', 'អតិថិជន') }}</th>
                        <th>{{ $bi('Phone', 'ទូរស័ព្ទ') }}</th>
                        <th>{{ $bi('Location', 'ទីតាំង') }}</th>
                        <th>{{ $bi('Status', 'ស្ថានភាព') }}</th>
                        <th>{{ $bi('Payment', 'បង់ប្រាក់') }}</th>
                        <th class="text-right">{{ $bi('Total', 'សរុប') }}</th>
                        <th class="text-right">{{ $bi('Principal', 'ប្រាក់ដើម') }}</th>
                        <th class="text-right">{{ $bi('Paid', 'បានបង់') }}</th>
                        <th class="text-right">{{ $bi('Balance', 'សមតុល្យ') }}</th>
                        <th class="text-right">{{ $bi('Term', 'រយៈពេល') }}</th>
                        <th class="text-right">{{ $bi('Schedules', 'កាលវិភាគ') }}</th>
                        <th>{{ $bi('Next due', 'បង់បន្ទាប់') }}</th>
                        <th>{{ $bi('Last payment', 'បង់ចុងក្រោយ') }}</th>
                        <th>{{ $bi('Collector', 'អ្នកប្រមូល') }}</th>
                        <th>{{ $bi('Risk', 'ហានិភ័យ') }}</th>
                        <th>{{ $bi('Note', 'កំណត់ចំណាំ') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td><strong>{{ $row->loan_number }}</strong></td>
                            <td>{{ $row->loan_date ? \Carbon\Carbon::parse($row->loan_date)->format('d M Y') : '-' }}</td>
                            <td>{{ $row->invoice_no ?: '-' }}</td>
                            <td>{{ $row->customer_name ?: '-' }}</td>
                            <td>{{ $row->customer_phone ?: '-' }}</td>
                            <td>{{ $row->location_name ?: '-' }}</td>
                            <td><span class="ir-status">{{ ucwords(str_replace('_', ' ', (string) $row->status)) }}</span></td>
                            <td>{{ $row->payment_status ? ucwords(str_replace('_', ' ', (string) $row->payment_status)) : '-' }}</td>
                            <td class="text-right">{{ $money($row->total_amount) }}</td>
                            <td class="text-right">{{ $money($row->principal_amount) }}</td>
                            <td class="text-right">{{ $money($row->paid_amount) }}</td>
                            <td class="text-right">{{ $money($row->balance_amount) }}</td>
                            <td class="text-right">{{ $number($row->term_count) }}</td>
                            <td class="text-right">{{ $number($row->paid_schedule_count) }} / {{ $number($row->schedule_count) }}</td>
                            <td>{{ $row->next_due_date ? \Carbon\Carbon::parse($row->next_due_date)->format('d M Y') : '-' }}</td>
                            <td>{{ $row->last_payment_at ? \Carbon\Carbon::parse($row->last_payment_at)->format('d M Y') : '-' }}</td>
                            <td>{{ $row->collector_name ?: '-' }}</td>
                            <td>{{ (int) $row->is_overdue === 1 ? $bi('Overdue', 'ហួសកំណត់') : $bi('Normal', 'ធម្មតា') }}</td>
                            <td>{{ $row->note ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
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
    jQuery(function ($) {
        var $filterForm = $('#installmentReportDateRange').closest('form');
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
            }), function (start, end) {
                $dateRange.val(start.format(displayDateFormat) + ' - ' + end.format(displayDateFormat));
                $filterForm.find('[name="date_from"]').val(start.format('YYYY-MM-DD'));
                $filterForm.find('[name="date_to"]').val(end.format('YYYY-MM-DD'));
            });

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
        } else {
            $dateRange.prop('readonly', false).on('change', function () {
                var parts = String($(this).val()).split(/\s+-\s+|\s+~\s+/);
                if (parts.length === 2) {
                    $filterForm.find('[name="date_from"]').val(parts[0]);
                    $filterForm.find('[name="date_to"]').val(parts[1]);
                }
            });
        }

        $('#installmentReportFilters')
            .on('shown.bs.collapse', function () {
                var button = $('[data-ir-toggle]');
                button.attr('aria-expanded', 'true');
                button.find('[data-ir-label]').text(@json($bi('Collapse', 'បិទ')));
                button.find('i').attr('class', 'fa fa-chevron-up');
            })
            .on('hidden.bs.collapse', function () {
                var button = $('[data-ir-toggle]');
                button.attr('aria-expanded', 'false');
                button.find('[data-ir-label]').text(@json($bi('Expand', 'បើក')));
                button.find('i').attr('class', 'fa fa-chevron-down');
            });

        if (!$.fn.DataTable || $.fn.DataTable.isDataTable('#installmentReportsTable')) {
            return;
        }

        var exportTitle = @json($bi('Installment Reports', 'របាយការណ៍រំលស់'));
        var tableButtons = [];
        if ($.fn.dataTable.Buttons) {
            tableButtons = [
                {
                    extend: 'copy',
                    text: '<i class="fa fa-copy" aria-hidden="true"></i> Copy',
                    className: 'btn btn-default btn-sm',
                    title: exportTitle,
                    exportOptions: {columns: ':visible'}
                },
                {
                    extend: 'csv',
                    text: '<i class="fa fa-file-csv" aria-hidden="true"></i> Export CSV',
                    className: 'btn btn-default btn-sm',
                    title: exportTitle,
                    exportOptions: {columns: ':visible'}
                },
                {
                    extend: 'excel',
                    text: '<i class="fa fa-file-excel-o" aria-hidden="true"></i> Export Excel',
                    className: 'btn btn-default btn-sm',
                    title: exportTitle,
                    exportOptions: {columns: ':visible'}
                },
                {
                    extend: 'print',
                    text: '<i class="fa fa-print" aria-hidden="true"></i> Print',
                    className: 'btn btn-default btn-sm',
                    title: exportTitle,
                    exportOptions: {columns: ':visible', stripHtml: true}
                },
                {
                    extend: 'colvis',
                    text: '<i class="fa fa-columns" aria-hidden="true"></i> Column visibility',
                    className: 'btn btn-default btn-sm'
                },
                {
                    extend: 'pdf',
                    text: '<i class="fa fa-file-pdf-o" aria-hidden="true"></i> Export PDF',
                    className: 'btn btn-default btn-sm',
                    title: exportTitle,
                    orientation: 'landscape',
                    pageSize: 'A4',
                    exportOptions: {columns: ':visible'}
                }
            ];
        }

        $('#installmentReportsTable').DataTable({
            dom: '<"row margin-bottom-20 text-center"<"col-sm-2"l><"col-sm-8"B><"col-sm-2"f> r>tip',
            buttons: tableButtons,
            pageLength: 25,
            lengthMenu: [[25, 50, 100, -1], [25, 50, 100, 'All']],
            order: [[1, 'desc']],
            autoWidth: false,
            scrollX: true,
            language: {
                search: '',
                searchPlaceholder: 'Search ...'
            },
            columnDefs: [
                {targets: [8, 9, 10, 11, 12, 13], className: 'text-right'}
            ]
        });
    });
</script>
@endsection
