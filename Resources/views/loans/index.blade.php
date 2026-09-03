@extends('loanmanagement::layouts.app')
@section('title', 'Installment List')
@section('hide_breadcrumb', '1')

@section('loan_css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap.min.css">
<style>
    .lm-loan-list-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 16px;
        border: 1px solid #dfe7f1;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .06);
    }
    .lm-loan-list-hero h1 {
        margin: 0;
        color: #111827;
        font-size: 22px;
        font-weight: 900;
        letter-spacing: 0;
    }
    .lm-loan-list-hero p {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
    }
    .lm-loan-list-hero-actions {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .lm-loan-list-hero-actions .btn {
        border-radius: 7px;
        font-weight: 800;
    }
    .lm-loan-list-stats {
        grid-template-columns: repeat(4, minmax(190px, 1fr));
    }
    .lm-loan-list-stat {
        position: relative;
        min-height: 108px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 52px;
        gap: 14px;
        align-items: start;
        overflow: hidden;
        border: 1px solid rgba(148, 163, 184, .28);
        border-radius: 12px;
        padding: 16px;
        background:
            linear-gradient(135deg, rgba(255, 255, 255, .98), rgba(248, 250, 252, .92)),
            radial-gradient(circle at 100% 0%, var(--stat-glow, rgba(37, 99, 235, .14)), transparent 34%);
        box-shadow: 0 14px 30px rgba(15, 23, 42, .08);
        transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .lm-loan-list-stat:hover {
        transform: translateY(-2px);
        border-color: rgba(37, 99, 235, .28);
        box-shadow: 0 18px 38px rgba(15, 23, 42, .12);
    }
    .lm-loan-list-stat::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: var(--stat-color, var(--lm-primary, #2563eb));
    }
    .lm-loan-list-stat::after {
        content: "";
        position: absolute;
        right: -28px;
        top: -34px;
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background: var(--stat-glow, rgba(37, 99, 235, .12));
        pointer-events: none;
    }
    .lm-loan-list-stat-icon {
        position: relative;
        z-index: 1;
        width: 52px;
        height: 52px;
        display: grid;
        place-items: center;
        border-radius: 14px;
        background: var(--stat-bg, #eff6ff);
        color: var(--stat-color, #2563eb);
        font-size: 20px;
        border: 1px solid rgba(255, 255, 255, .72);
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .75), 0 10px 20px rgba(15, 23, 42, .08);
    }
    .lm-loan-list-stat-body {
        position: relative;
        z-index: 1;
        min-width: 0;
    }
    .lm-loan-list-stat span:not(.lm-loan-list-stat-icon) {
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: .02em;
        text-transform: uppercase;
    }
    .lm-loan-list-stat strong {
        margin-top: 7px;
        color: #0f172a;
        font-size: 26px;
        line-height: 1.05;
        letter-spacing: 0;
    }
    .lm-loan-list-stat small {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        max-width: 100%;
        margin-top: 10px;
        padding: 4px 8px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .78);
        color: #94a3b8;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, .16);
    }
    .lm-loan-list-stat small::before {
        content: "";
        width: 6px;
        height: 6px;
        flex: 0 0 6px;
        border-radius: 50%;
        background: var(--stat-color, #2563eb);
    }
    .lm-loan-list-stat.stat-total {
        --stat-color: #2563eb;
        --stat-bg: #eff6ff;
        --stat-glow: rgba(37, 99, 235, .16);
    }
    .lm-loan-list-stat.stat-showing {
        --stat-color: #0891b2;
        --stat-bg: #ecfeff;
        --stat-glow: rgba(8, 145, 178, .16);
    }
    .lm-loan-list-stat.stat-active {
        --stat-color: #16a34a;
        --stat-bg: #f0fdf4;
        --stat-glow: rgba(22, 163, 74, .16);
    }
    .lm-loan-list-stat.stat-balance {
        --stat-color: #ea580c;
        --stat-bg: #fff7ed;
        --stat-glow: rgba(234, 88, 12, .17);
    }
    .lm-loan-list-filter,
    .lm-loan-list-table-card {
        border-color: #dbe4ef;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
    }
    .lm-loan-list-filter-toggle-label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .lm-loan-list-filter-toggle-label::before {
        content: "\f0b0";
        font-family: FontAwesome;
        color: var(--lm-primary, #2563eb);
    }
    .lm-loan-list-filter-body {
        grid-template-columns: minmax(260px, 1.5fr) repeat(5, minmax(150px, 1fr)) minmax(120px, .7fr);
    }
    .lm-loan-list-field.date-range-field {
        min-width: 260px;
    }
    .lm-loan-list-field .form-control,
    .lm-loan-list-field .select2-container .select2-selection--single {
        border-radius: 6px;
    }
    .lm-loan-list-field-actions .btn {
        min-height: 38px;
        border-radius: 7px;
        box-shadow: 0 8px 18px rgba(37, 99, 235, .18);
    }
    .lm-loan-list-table-card .lm-dt-top {
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .lm-loan-list-table-card .dt-buttons {
        display: inline-flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 8px;
        float: none;
    }
    .lm-loan-list-table-card .dt-buttons .btn,
    .lm-loan-list-table-card .dt-button {
        border: 1px solid #b8c3d3 !important;
        border-radius: 8px !important;
        background: #fff !important;
        color: #64748b !important;
        font-weight: 800;
        padding: 6px 12px !important;
        box-shadow: none !important;
    }
    .lm-loan-list-table-card .dataTables_filter {
        text-align: right;
    }
    .lm-loan-list-table-card .dataTables_filter input {
        width: 240px !important;
        border-radius: 0;
    }
    .lm-loan-list-table-card #loan_list_table {
        border-collapse: separate !important;
        border-spacing: 0;
    }
    .lm-loan-list-table-card #loan_list_table thead th {
        background: #0f172a !important;
        color: #fff !important;
    }
    .lm-loan-list-table-card #loan_list_table tbody tr:nth-child(even) td {
        background: #fbfdff;
    }
    .lm-loan-list-table-card #loan_list_table tbody tr:hover td {
        background: #eef6ff !important;
    }
    @media (max-width: 1200px) {
        .lm-loan-list-stats {
            grid-template-columns: repeat(2, minmax(180px, 1fr));
        }
        .lm-loan-list-filter-body {
            grid-template-columns: repeat(2, minmax(180px, 1fr));
        }
    }
    @media (max-width: 768px) {
        .lm-loan-list-hero {
            align-items: flex-start;
            flex-direction: column;
        }
        .lm-loan-list-hero-actions {
            justify-content: flex-start;
            width: 100%;
        }
        .lm-loan-list-stats {
            grid-template-columns: 1fr;
        }
        .lm-loan-list-filter-body {
            grid-template-columns: 1fr;
        }
        .lm-loan-list-table-card .dataTables_filter {
            text-align: left;
        }
    }
</style>
@endsection

@section('content_body')
@php
    $isKhmer = session('user.language', config('app.locale')) === 'km';
    $text = fn ($en, $km) => $isKhmer ? $km : $en;
@endphp
<section class="content no-print">
    <div class="lm-mobile-section-tabs">
        <a href="{{ route('loan-management.loans') }}" class="active">
            <i class="fa fa-credit-card"></i> {{ $text('Loans', 'កម្ចី') }}
        </a>
        <a href="{{ route('loan-management.monthly-payments.index') }}">
            <i class="fa fa-money"></i> {{ $text('Collection', 'ការប្រមូលប្រាក់') }}
        </a>
    </div>

    <div class="lm-loan-list-shell">
        <div class="lm-loan-list-hero">
            <div>
                <h1>{{ $text('All Loans', 'កម្ចីទាំងអស់') }}</h1>
                <p>{{ $text('Monitor installment accounts, collection progress, and customer balances in one workspace.', 'តាមដានគណនីរំលស់ ការប្រមូលប្រាក់ និងសមតុល្យអតិថិជននៅកន្លែងតែមួយ។') }}</p>
            </div>
            <div class="lm-loan-list-hero-actions">
                <a href="{{ route('loan-management.loans.create') }}" class="btn btn-primary">
                    <i class="fa fa-plus-circle"></i> {{ $text('New Loan', 'កម្ចីថ្មី') }}
                </a>
                <button type="button" class="btn btn-default" onclick="window.print()">
                    <i class="fa fa-print"></i> {{ $text('Print', 'បោះពុម្ព') }}
                </button>
            </div>
        </div>
        <div class="lm-loan-list-stats">
            <div class="lm-loan-list-stat stat-total">
                <span class="lm-loan-list-stat-icon"><i class="fa fa-database"></i></span>
                <div class="lm-loan-list-stat-body"><span>{{ $text('Total Records', 'ចំនួនសរុប') }}</span><strong id="loanStatTotal">0</strong><small>{{ $text('matching current filters', 'ត្រូវតាមតម្រង') }}</small></div>
            </div>
            <div class="lm-loan-list-stat stat-showing">
                <span class="lm-loan-list-stat-icon"><i class="fa fa-list"></i></span>
                <div class="lm-loan-list-stat-body"><span>{{ $text('Showing', 'កំពុងបង្ហាញ') }}</span><strong id="loanStatShowing">0</strong><small>{{ $text('records on this page', 'ទិន្នន័យលើទំព័រនេះ') }}</small></div>
            </div>
            <div class="lm-loan-list-stat stat-active">
                <span class="lm-loan-list-stat-icon"><i class="fa fa-check-circle"></i></span>
                <div class="lm-loan-list-stat-body"><span>{{ $text('Active Page', 'សកម្មលើទំព័រ') }}</span><strong id="loanStatActive">0</strong><small>{{ $text('active or approved loans', 'កម្ចីសកម្ម ឬអនុម័ត') }}</small></div>
            </div>
            <div class="lm-loan-list-stat stat-balance">
                <span class="lm-loan-list-stat-icon"><i class="fa fa-balance-scale"></i></span>
                <div class="lm-loan-list-stat-body"><span>{{ $text('Balance Page', 'សមតុល្យលើទំព័រ') }}</span><strong id="loanStatBalance">$0.00</strong><small>{{ $text('outstanding on visible rows', 'សមតុល្យលើជួរដែលឃើញ') }}</small></div>
            </div>
        </div>

        <div class="lm-loan-list-filter collapsed" id="loanFilterPanel">
            <div class="lm-loan-list-filter-toggle">
                <span class="lm-loan-list-filter-toggle-label">{{ $text('Filters', 'តម្រង') }}</span>
                <span class="lm-loan-list-filter-toggle-actions">
                    <a href="javascript:void(0)" id="loanFilterReset" class="lm-loan-list-reset">{{ $text('Reset', 'កំណត់ឡើងវិញ') }}</a>
                    <button type="button" class="lm-loan-list-collapse-btn" id="loanFilterToggle" aria-expanded="false" aria-controls="loanFilterBody">
                        <span id="loanFilterToggleText">{{ $text('Expand', 'ពង្រីក') }}</span>
                        <i class="fa fa-chevron-down" id="loanFilterToggleIcon" aria-hidden="true"></i>
                    </button>
                </span>
            </div>
            <div class="lm-loan-list-filter-body" id="loanFilterBody">
            <div class="lm-loan-list-field date-range-field">
                {!! Form::label('sell_list_filter_date_range', $text('Date Range', 'ចន្លោះកាលបរិច្ឆេទ')) !!}
                {!! Form::text('sell_list_filter_date_range', null, ['placeholder' => $text('Select date range', 'ជ្រើសរើសចន្លោះកាលបរិច្ឆេទ'), 'class' => 'form-control', 'readonly']) !!}
                <input type="hidden" id="start_date">
                <input type="hidden" id="end_date">
            </div>
            <div class="lm-loan-list-field">
                <label for="status">{{ $text('Status', 'ស្ថានភាព') }}</label>
                <select id="status" class="form-control select2" style="width:100%">
                    <option value="">{{ $text('All Statuses', 'ស្ថានភាពទាំងអស់') }}</option>
                    <option value="draft">{{ $text('Draft', 'ព្រាង') }}</option>
                    <option value="pending">{{ $text('Pending', 'កំពុងរង់ចាំ') }}</option>
                    <option value="approved">{{ $text('Approved', 'បានអនុម័ត') }}</option>
                    <option value="active">{{ $text('Active', 'កំពុងដំណើរការ') }}</option>
                    <option value="completed">{{ $text('Completed', 'បានបញ្ចប់') }}</option>
                    <option value="rejected">{{ $text('Rejected', 'បានបដិសេធ') }}</option>
                    <option value="cancelled">{{ $text('Cancelled', 'បានបោះបង់') }}</option>
                    <option value="defaulted">{{ $text('Defaulted', 'ខូចបំណុល') }}</option>
                </select>
            </div>
            <div class="lm-loan-list-field">
                <label for="location_name">{{ $text('Location', 'សាខា') }}</label>
                {!! Form::select('location_name', $locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => $text('All Locations', 'សាខាទាំងអស់'), 'id' => 'location_name']) !!}
            </div>
            <div class="lm-loan-list-field">
                <label for="collector_name">{{ $text('Collector', 'អ្នកប្រមូលប្រាក់') }}</label>
                {!! Form::select('collector_name', $collectors, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => $text('All Collectors', 'អ្នកប្រមូលប្រាក់ទាំងអស់'), 'id' => 'collector_name']) !!}
            </div>
            <div class="lm-loan-list-field">
                <label for="customer">{{ $text('Customer', 'អតិថិជន') }}</label>
                <input id="customer" class="form-control" placeholder="{{ $text('Customer name', 'ឈ្មោះអតិថិជន') }}">
            </div>
            <div class="lm-loan-list-field lm-loan-list-field-actions">
                <button type="button" class="btn btn-primary btn-block" id="loanFilterApply">
                    <i class="fa fa-filter"></i> {{ $text('Apply', 'អនុវត្ត') }}
                </button>
            </div>
            </div>
        </div>

        <div class="lm-loan-list-table-card">
            <div class="lm-mobile-loan-list" id="loan_mobile_list">
                <div class="text-center text-muted" style="padding: 16px;">{{ $text('Loading loans...', 'កំពុងផ្ទុកកម្ចី...') }}</div>
            </div>
            <table class="table table-bordered table-striped" id="loan_list_table" width="100%">
                <thead>
                    <tr>
                        <th>{{ $text('Loan #', 'លេខកម្ចី') }}</th>
                        <th>{{ $text('Date', 'កាលបរិច្ឆេទ') }}</th>
                        <th>{{ $text('Source Invoice', 'វិក្កយបត្រយោង') }}</th>
                        <th>{{ $text('Customer', 'អតិថិជន') }}</th>
                        <th>{{ $text('Phone', 'ទូរស័ព្ទ') }}</th>
                        <th>{{ $text('Location', 'សាខា') }}</th>
                        <th>{{ $text('Collector', 'អ្នកប្រមូល') }}</th>
                        <th>{{ $text('Principal', 'ប្រាក់ដើម') }}</th>
                        <th>{{ $text('Paid', 'បានបង់') }}</th>
                        <th>{{ $text('Balance', 'សមតុល្យ') }}</th>
                        <th>{{ $text('Status', 'ស្ថានភាព') }}</th>
                        <th>{{ $text('Currency', 'រូបិយប័ណ្ណ') }}</th>
                        <th>{{ $text('Action', 'សកម្មភាព') }}</th>
                    </tr>
                </thead>
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
$(document).ready(function(){
    if ($.fn.select2) {
        $('.select2').select2();
    }
    var loanBaseUrl = "{{ url('loan-management/loans') }}";
    var loanDateFormat = typeof moment_date_format !== 'undefined' ? moment_date_format : 'YYYY-MM-DD';
    var loanListText = {
        processing: @json($text('Loading loans...', 'កំពុងផ្ទុកកម្ចី...')),
        search: @json($text('Search', 'ស្វែងរក')),
        lengthMenu: @json($text('Show _MENU_ loans', 'បង្ហាញ _MENU_ កម្ចី')),
        info: @json($text('Showing _START_ to _END_ of _TOTAL_ loans', 'បង្ហាញ _START_ ដល់ _END_ នៃ _TOTAL_ កម្ចី')),
        infoEmpty: @json($text('No loans to show', 'មិនមានកម្ចីសម្រាប់បង្ហាញ')),
        emptyTable: @json($text('No loans found for the selected filters.', 'រកមិនឃើញកម្ចីសម្រាប់តម្រងដែលបានជ្រើស។')),
        zeroRecords: @json($text('No matching loans found.', 'រកមិនឃើញកម្ចីដែលត្រូវគ្នា។')),
        paginateNext: @json($text('Next', 'បន្ទាប់')),
        paginatePrevious: @json($text('Previous', 'មុន')),
        statusUpdated: @json($text('Status updated', 'បានធ្វើបច្ចុប្បន្នភាពស្ថានភាព')),
        statusFailed: @json($text('Failed to update status', 'ធ្វើបច្ចុប្បន្នភាពស្ថានភាពមិនបានសម្រេច')),
        deleteConfirm: @json($text('Delete this loan?', 'លុបកម្ចីនេះឬ?')),
        deleteFailed: @json($text('Failed to delete loan.', 'លុបកម្ចីមិនបានសម្រេច។')),
        noLoans: @json($text('No loans found.', 'រកមិនឃើញកម្ចី។')),
        copied: @json($text('Copied loan information', 'បានចម្លងព័ត៌មានកម្ចី')),
        copyFailed: @json($text('Unable to copy loan information', 'មិនអាចចម្លងព័ត៌មានកម្ចីបានទេ')),
        customer: @json($text('Customer', 'អតិថិជន')),
        phone: @json($text('Phone', 'ទូរស័ព្ទ')),
        location: @json($text('Location', 'សាខា')),
        collector: @json($text('Collector', 'អ្នកប្រមូល')),
        principal: @json($text('Principal', 'ប្រាក់ដើម')),
        paid: @json($text('Paid', 'បានបង់')),
        balance: @json($text('Balance', 'សមតុល្យ')),
        view: @json($text('View', 'មើល')),
        pay: @json($text('Pay', 'បង់ប្រាក់')),
        telegram: @json($text('Telegram', 'តេឡេក្រាម')),
        connectTelegram: @json($text('Connect Telegram', 'ភ្ជាប់ Telegram'))
    };

    function plainText(value) {
        return $('<div>').html(value || '').text().trim() || '-';
    }

function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function formatLmExpiry(value) {
        if (!value) {
            return '';
        }
        var date = new Date(value);
        if (isNaN(date.getTime())) {
            return String(value);
        }
        var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + ' ' + pad(date.getHours()) + ':' + pad(date.getMinutes());
    }

    function copyLoanText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        var deferred = $.Deferred();
        var textarea = document.createElement('textarea');
        textarea.value = text || '';
        textarea.setAttribute('readonly', 'readonly');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();

        try {
            document.execCommand('copy');
            deferred.resolve();
        } catch (e) {
            deferred.reject(e);
        }

        document.body.removeChild(textarea);
        return deferred.promise();
    }

    function debounce(fn, wait) {
        var timer = null;
        return function() {
            var context = this;
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function() {
                fn.apply(context, args);
            }, wait);
        };
    }

    function mobileLoanCard(row) {
        var id = row.id || '';
        var customerId = row.customer_id || '';
        var telegramLinked = !!row.telegram_chat_id;
        var loanNumber = plainText(row.loan_number);
        var customer = plainText(row.customer_name_snapshot);
        var phone = plainText(row.customer_phone_snapshot);
        var date = plainText(row.loan_date);
        var statusText = plainText(row.status).toLowerCase();
        var statusClass = statusText.replace(/[^a-z0-9_-]+/g, '-');
        var location = plainText(row.location_name_snapshot);
        var collector = plainText(row.collector_name_snapshot);
        var principal = plainText(row.principal_amount);
        var paid = plainText(row.paid_amount);
        var balance = plainText(row.balance_amount);
        var viewUrl = loanBaseUrl + '/' + id + '/view';
        var quickPayUrl = loanBaseUrl + '/' + id + '/payment/quick-pay';
        var telegramUrl = customerId ? "{{ url('loan-management/customers') }}/" + customerId + "/telegram/link" : '';

        return ''
            + '<article class="lm-mobile-loan-card">'
            + '  <div class="lm-mobile-loan-card-header">'
            + '    <div><div class="lm-mobile-loan-card-title">' + escapeHtml(loanNumber) + '</div><div class="lm-mobile-loan-card-date">' + escapeHtml(date) + '</div></div>'
            + '    <span class="lm-mobile-loan-card-status status-' + escapeHtml(statusClass) + '">' + escapeHtml(statusText || 'status') + '</span>'
            + '  </div>'
            + '  <div class="lm-mobile-loan-card-body">'
            + '    <div class="lm-mobile-loan-card-row"><span class="label">' + escapeHtml(loanListText.customer) + '</span><span class="value">' + escapeHtml(customer) + '</span></div>'
            + '    <div class="lm-mobile-loan-card-row"><span class="label">' + escapeHtml(loanListText.phone) + '</span><span class="value">' + escapeHtml(phone) + '</span></div>'
            + '    <div class="lm-mobile-loan-card-row"><span class="label">' + escapeHtml(loanListText.location) + '</span><span class="value">' + escapeHtml(location) + '</span></div>'
            + '    <div class="lm-mobile-loan-card-row"><span class="label">' + escapeHtml(loanListText.collector) + '</span><span class="value">' + escapeHtml(collector) + '</span></div>'
            + '  </div>'
            + '  <div class="lm-mobile-loan-card-balance">'
            + '    <div class="lm-mobile-loan-card-balance-item"><small>' + escapeHtml(loanListText.principal) + '</small><strong>' + escapeHtml(principal) + '</strong></div>'
            + '    <div class="lm-mobile-loan-card-balance-item paid"><small>' + escapeHtml(loanListText.paid) + '</small><strong>' + escapeHtml(paid) + '</strong></div>'
            + '    <div class="lm-mobile-loan-card-balance-item due"><small>' + escapeHtml(loanListText.balance) + '</small><strong>' + escapeHtml(balance) + '</strong></div>'
            + '  </div>'
            + '  <div class="lm-mobile-loan-card-actions">'
            + '    <a href="' + viewUrl + '" class="btn btn-default btn-sm"><i class="fa fa-eye"></i> ' + escapeHtml(loanListText.view) + '</a>'
            + '    <a href="#" class="btn btn-success btn-sm btn-modal" data-href="' + quickPayUrl + '" data-container=".view_modal"><i class="fa fa-money"></i> ' + escapeHtml(loanListText.pay) + '</a>'
            + (telegramUrl ? (telegramLinked ? '    <button type="button" class="btn btn-default btn-sm" disabled><i class="fa fa-check-circle"></i> ' + escapeHtml(loanListText.telegram) + '</button>' : '    <a href="#" class="btn btn-info btn-sm js-loan-telegram-link" data-url="' + telegramUrl + '" data-customer="' + escapeHtml(customer) + '"><i class="fa fa-paper-plane"></i> ' + escapeHtml(loanListText.telegram) + '</a>') : '')
            + '  </div>'
            + '</article>';
    }

    function renderMobileLoanList(rows) {
        var $list = $('#loan_mobile_list');
        if (!$list.length) return;
        if (!rows || !rows.length) {
            $list.html('<div class="lm-mobile-loan-empty">' + escapeHtml(loanListText.noLoans) + '</div>');
            return;
        }

        $list.html(rows.map(mobileLoanCard).join(''));
    }

    function moneyToNumber(value) {
        return parseFloat(String(plainText(value)).replace(/[^0-9.-]+/g, '')) || 0;
    }

    function formatMoney(value) {
        return '$' + Number(value || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function updateLoanStats(api) {
        var info = api.page.info();
        var rows = api.rows({page: 'current'}).data().toArray();
        var active = 0;
        var balance = 0;

        rows.forEach(function(row) {
            var status = plainText(row.status).toLowerCase();
            if (status === 'active' || status === 'approved') {
                active += 1;
            }
            balance += moneyToNumber(row.balance_amount);
        });

        $('#loanStatTotal').text((info.recordsDisplay || 0).toLocaleString());
        $('#loanStatShowing').text(rows.length.toLocaleString());
        $('#loanStatActive').text(active.toLocaleString());
        $('#loanStatBalance').text(formatMoney(balance));
    }

    var loanTable = null;
    var exportTitle = @json($text('All Loans', 'កម្ចីទាំងអស់'));
    var tableButtons = [];
    if ($.fn.dataTable && $.fn.dataTable.Buttons) {
        tableButtons = [
            {
                extend: 'copy',
                text: '<i class="fa fa-copy" aria-hidden="true"></i> Copy',
                className: 'btn btn-default btn-sm',
                title: exportTitle,
                exportOptions: {columns: ':visible:not(:last-child)', stripHtml: true}
            },
            {
                extend: 'csv',
                text: '<i class="fa fa-file-text-o" aria-hidden="true"></i> Export CSV',
                className: 'btn btn-default btn-sm',
                title: exportTitle,
                exportOptions: {columns: ':visible:not(:last-child)', stripHtml: true}
            },
            {
                extend: 'excel',
                text: '<i class="fa fa-file-excel-o" aria-hidden="true"></i> Export Excel',
                className: 'btn btn-default btn-sm',
                title: exportTitle,
                exportOptions: {columns: ':visible:not(:last-child)', stripHtml: true}
            },
            {
                extend: 'print',
                text: '<i class="fa fa-print" aria-hidden="true"></i> Print',
                className: 'btn btn-default btn-sm',
                title: exportTitle,
                exportOptions: {columns: ':visible:not(:last-child)', stripHtml: true}
            },
            {
                extend: 'colvis',
                text: '<i class="fa fa-columns" aria-hidden="true"></i> Column visibility',
                className: 'btn btn-default btn-sm',
                columns: ':not(:last-child)'
            },
            {
                extend: 'pdf',
                text: '<i class="fa fa-file-pdf-o" aria-hidden="true"></i> Export PDF',
                className: 'btn btn-default btn-sm',
                title: exportTitle,
                orientation: 'landscape',
                pageSize: 'A4',
                exportOptions: {columns: ':visible:not(:last-child)', stripHtml: true}
            }
        ];
    }

    function reloadLoanTable() {
        if (loanTable && loanTable.ajax) {
            loanTable.ajax.reload();
        }
    }

    function setRange(s, e){
        $('#start_date').val(s.format('YYYY-MM-DD'));
        $('#end_date').val(e.format('YYYY-MM-DD'));
        $('#sell_list_filter_date_range').val(s.format(loanDateFormat) + ' ~ ' + e.format(loanDateFormat));
    }

    if (typeof moment !== 'undefined' && $.fn.daterangepicker) {
        var loanDrs = (typeof dateRangeSettings !== 'undefined') ? dateRangeSettings : {};
        var defaultStartDate = moment().startOf('month');
        var defaultEndDate = moment();
        var fyStart = (typeof financial_year !== 'undefined' && financial_year.start && moment(financial_year.start).isValid())
            ? moment(financial_year.start)
            : moment().startOf('year');
        var fyEnd = (typeof financial_year !== 'undefined' && financial_year.end && moment(financial_year.end).isValid())
            ? moment(financial_year.end)
            : moment().endOf('year');
        var loanDateRanges = {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [
                moment().subtract(1, 'month').startOf('month'),
                moment().subtract(1, 'month').endOf('month')
            ],
            'This month last year': [
                moment().subtract(1, 'year').startOf('month'),
                moment().subtract(1, 'year').endOf('month')
            ],
            'This Year': [moment().startOf('year'), moment().endOf('year')],
            'Last Year': [
                moment().subtract(1, 'year').startOf('year'),
                moment().subtract(1, 'year').endOf('year')
            ],
            'Current financial year': [fyStart.clone(), fyEnd.clone()],
            'Last financial year': [
                fyStart.clone().subtract(1, 'year'),
                fyEnd.clone().subtract(1, 'year')
            ]
        };

        $('#sell_list_filter_date_range').daterangepicker($.extend(true, {}, loanDrs, {
            autoUpdateInput: false,
            showDropdowns: true,
            linkedCalendars: false,
            startDate: defaultStartDate,
            endDate: defaultEndDate,
            ranges: loanDateRanges,
            locale: $.extend(true, {}, loanDrs.locale || {}, {
                format: loanDateFormat,
                separator: ' ~ ',
                applyLabel: @json($text('Apply', 'អនុវត្ត')),
                cancelLabel: @json($text('Clear', 'សម្អាត')),
                customRangeLabel: 'Custom Range',
                toLabel: '~'
            })
        }), function(s, e){
            setRange(s, e);
            reloadLoanTable();
        });

        $('#sell_list_filter_date_range').on('cancel.daterangepicker', function(){
            $(this).val('');
            $('#start_date').val('');
            $('#end_date').val('');
            reloadLoanTable();
        });
    } else {
        $('#sell_list_filter_date_range').prop('readonly', false).on('change', function(){
            var raw = String($(this).val() || '').trim();
            var parts = raw.split(/\s*(?:~|\s-\s)\s*/);
            if (parts.length >= 2) {
                $('#start_date').val(parts[0]);
                $('#end_date').val(parts[1]);
            } else {
                $('#start_date,#end_date').val('');
            }
            reloadLoanTable();
        });
    }

    if (!$.fn.DataTable) {
        $('#loan_mobile_list').html('<div class="lm-mobile-loan-empty text-danger">DataTable library is not loaded.</div>');
        return;
    }

    $.fn.dataTable.ext.errMode = 'none';
    $('#loan_list_table').on('error.dt', function(e, settings, techNote, message) {
        $('#loan_mobile_list').html('<div class="lm-mobile-loan-empty text-danger">' + escapeHtml(message || loanListText.emptyTable) + '</div>');
        if (window.toastr) {
            toastr.error(message || loanListText.emptyTable);
        }
    });

    loanTable = $('#loan_list_table').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        scrollX: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        dom: "<'row lm-dt-top'<'col-sm-2'l><'col-sm-8 text-center'B><'col-sm-2'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row lm-dt-bottom'<'col-sm-5'i><'col-sm-7'p>>",
        buttons: tableButtons,
        language: {
            processing: loanListText.processing,
            search: loanListText.search + ':',
            searchPlaceholder: @json($text('Search ...', 'ស្វែងរក ...')),
            lengthMenu: loanListText.lengthMenu,
            info: loanListText.info,
            infoEmpty: loanListText.infoEmpty,
            emptyTable: loanListText.emptyTable,
            zeroRecords: loanListText.zeroRecords,
            paginate: {
                next: loanListText.paginateNext,
                previous: loanListText.paginatePrevious
            }
        },
        order: [[1, 'desc']],
        ajax: {
            url: "{{ route('loan-management.loans.list-data') }}",
            data: function(d){
                d.start_date = $('#start_date').val();
                d.end_date = $('#end_date').val();
                d.status = $('#status').val();
                d.location_name = $('#location_name').val();
                d.collector_name = $('#collector_name').val();
                d.customer = $('#customer').val();
            },
            error: function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message) || xhr.responseText || loanListText.emptyTable;
                $('#loan_mobile_list').html('<div class="lm-mobile-loan-empty text-danger">' + escapeHtml(message) + '</div>');
            }
        },
        columns: [
            {data:'loan_number', name:'loan_number'},
            {data:'loan_date', name:'loan_date'},
            {data:'source_invoice_no', name:'source_invoice_no'},
            {data:'customer_name_snapshot', name:'customer_name_snapshot'},
            {data:'customer_phone_snapshot', name:'customer_phone_snapshot'},
            {data:'location_name_snapshot', name:'location_name_snapshot', searchable:false},
            {data:'collector_name_snapshot', name:'collector_name_snapshot'},
            {data:'principal_amount', name:'principal_amount'},
            {data:'paid_amount', name:'paid_amount'},
            {data:'balance_amount', name:'balance_amount'},
            {data:'status', name:'status'},
            {data:'currency', name:'currency'},
            {data:'action', name:'action', orderable:false, searchable:false}
        ],
        fnDrawCallback: function(){
            if (typeof __currency_convert_recursively === 'function') {
                __currency_convert_recursively($('#loan_list_table'));
            }
            var api = this.api();
            var rows = api.rows({page: 'current'}).data().toArray();
            renderMobileLoanList(rows);
            updateLoanStats(api);
        }
    });

    $(document).on('change', '#status,#location_name,#collector_name', function(){
        loanTable.ajax.reload();
    });

    $('#customer').on('input', debounce(function(){
        loanTable.ajax.reload();
    }, 300));

    $(document).on('click', '#loanFilterReset', function(){
        $('#sell_list_filter_date_range,#start_date,#end_date,#customer').val('');
        $('#status,#location_name,#collector_name').val('').trigger('change.select2');
        loanTable.search('');
        $('#loan_list_table_filter input[type="search"]').val('');
        loanTable.ajax.reload();
        setLoanFilterCollapsed(true);
    });

    var $loanFilter = $('#loanFilterPanel');
    var $loanFilterToggle = $('#loanFilterToggle');
    var $loanFilterToggleText = $('#loanFilterToggleText');
    var $loanFilterToggleIcon = $('#loanFilterToggleIcon');
    var loanFilterStateKey = 'lm_loan_filter_collapsed_v2';

    function setLoanFilterCollapsed(isCollapsed) {
        $loanFilter.toggleClass('collapsed', isCollapsed);
        $loanFilterToggle.attr('aria-expanded', isCollapsed ? 'false' : 'true');
        $loanFilterToggleText.text(isCollapsed ? @json($text('Expand', 'ពង្រីក')) : @json($text('Collapse', 'បង្រួម')));
        $loanFilterToggleIcon
            .toggleClass('fa-chevron-down', isCollapsed)
            .toggleClass('fa-chevron-up', ! isCollapsed);
        try { window.localStorage.setItem(loanFilterStateKey, isCollapsed ? '1' : '0'); } catch(err){}
    }

    if ($loanFilter.length) {
        var initialCollapsed = true;
        try {
            var savedLoanFilterState = window.localStorage.getItem(loanFilterStateKey);
            initialCollapsed = savedLoanFilterState === null ? true : savedLoanFilterState === '1';
        } catch(err){}
        setLoanFilterCollapsed(initialCollapsed);

        function toggleLoanFilterPanel() {
            setLoanFilterCollapsed(! $loanFilter.hasClass('collapsed'));
        }

        $loanFilterToggle.on('click', function(){
            toggleLoanFilterPanel();
        });

        $loanFilter.find('.lm-loan-list-filter-toggle-label').on('click', function(){
            toggleLoanFilterPanel();
        });

        $('#loanFilterApply').on('click', function(){
            loanTable.ajax.reload();
            setLoanFilterCollapsed(true);
        });
    }

    $(document).on('click', '.btn-delete-loan', function(){
        if(!confirm(loanListText.deleteConfirm)) return;
        $.ajax({
            url: $(this).data('url'),
            type: 'DELETE',
            data: {_token: $('meta[name=\"csrf-token\"]').attr('content')},
            success: function(){ loanTable.ajax.reload(); },
            error: function(){ alert(loanListText.deleteFailed); }
        });
    });


    $(document).on('click', '.btn-change-status', function(e){
        e.preventDefault();
        $.post($(this).data('url'), {
            _token: $('meta[name=\"csrf-token\"]').attr('content'),
            status: $(this).data('status')
        }, function(){ loanTable.ajax.reload(); }).fail(function(){ alert(loanListText.statusFailed + '.'); });
    });

    $(document).on('change', '.js-loan-status-select', function(){
        var $select = $(this);
        var oldStatus = $select.data('original-status') || '';
        var newStatus = $select.val();
        var url = $select.data('url');

        if (!url || !newStatus || newStatus === oldStatus) {
            return;
        }

        $select.prop('disabled', true);
        $.post(url, {
            _token: $('meta[name=\"csrf-token\"]').attr('content'),
            status: newStatus
        }, function(){
            if (window.toastr) {
                toastr.success(loanListText.statusUpdated);
            }
            loanTable.ajax.reload(null, false);
        }).fail(function(){
            $select.val(oldStatus);
            if (window.toastr) {
                toastr.error(loanListText.statusFailed);
            } else {
                alert(loanListText.statusFailed + '.');
            }
        }).always(function(){
            $select.prop('disabled', false);
        });
    });

    $(document).on('click', '.js-copy-loan-payment-info', function(e){
        e.preventDefault();

        var $button = $(this);
        var url = $button.data('url');
        if (!url) return;

        $button.prop('disabled', true);
        $.getJSON(url)
            .done(function(res) {
                $.when(copyLoanText(res && res.data ? (res.data.text || '') : ''))
                    .done(function() {
                        if (window.toastr) {
                            toastr.success(loanListText.copied);
                        }
                    })
                    .fail(function() {
                        alert(loanListText.copyFailed);
                    });
            })
            .fail(function() {
                alert(loanListText.copyFailed);
            })
            .always(function() {
                $button.prop('disabled', false);
            });
    });

    $(document).on('click', '.js-loan-telegram-link', function(e){
        e.preventDefault();

        var $button = $(this);
        var url = $button.data('url');
        var customer = $button.data('customer') || 'customer';
        if (!url) return;

        $button.prop('disabled', true).addClass('disabled');
        $.post(url, {_token: $('meta[name="csrf-token"]').attr('content')})
            .done(function(res) {
                var link = res && res.link ? res.link : '';
                var expires = res && res.expires_at ? res.expires_at : '';
                var qrUrl = link ? 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(link) : '';
                var safeLink = escapeHtml(link);
                var safeCustomer = escapeHtml(customer);
                var safeExpires = escapeHtml(expires ? formatLmExpiry(expires) : '');

                $('.view_modal').html(
                    '<div class="modal-dialog modal-sm" role="document">' +
                        '<div class="modal-content">' +
                            '<div class="modal-header">' +
                                '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                '<h4 class="modal-title"><i class="fa fa-paper-plane"></i> Connect Telegram</h4>' +
                            '</div>' +
                            '<div class="modal-body text-center">' +
                                '<p class="text-muted" style="margin-bottom:12px;">Share this link with ' + safeCustomer + '. Valid for a limited time and can only be used once.</p>' +
                                (qrUrl ? '<img src="' + qrUrl + '" alt="Telegram QR code" style="width:220px;height:220px;max-width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:8px;background:#fff;margin-bottom:12px;">' : '') +
                                '<input class="form-control text-center" readonly value="' + safeLink + '" style="margin-bottom:8px;">' +
                                (safeExpires ? '<div class="text-muted small">Expires: ' + safeExpires + '</div>' : '') +
                            '</div>' +
                            '<div class="modal-footer">' +
                                '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
                                '<a href="' + safeLink + '" target="_blank" rel="noopener" class="btn btn-primary">Open Link</a>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                ).modal('show');
            })
            .fail(function(xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message) || xhr.responseText || 'Unable to create Telegram link.';
                alert(message);
            })
            .always(function() {
                $button.prop('disabled', false).removeClass('disabled');
            });
    });
});
</script>
@endsection
