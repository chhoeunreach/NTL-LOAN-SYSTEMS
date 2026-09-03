@extends('loanmanagement::layouts.app')
@section('title', 'Installment List')
@section('hide_breadcrumb', '1')

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
        <div class="lm-loan-list-stats">
            <div class="lm-loan-list-stat"><span>{{ $text('Total Records', 'ចំនួនសរុប') }}</span><strong id="loanStatTotal">0</strong></div>
            <div class="lm-loan-list-stat"><span>{{ $text('Showing', 'កំពុងបង្ហាញ') }}</span><strong id="loanStatShowing">0</strong></div>
            <div class="lm-loan-list-stat"><span>{{ $text('Active Page', 'សកម្មលើទំព័រ') }}</span><strong id="loanStatActive">0</strong></div>
            <div class="lm-loan-list-stat"><span>{{ $text('Balance Page', 'សមតុល្យលើទំព័រ') }}</span><strong id="loanStatBalance">$0.00</strong></div>
        </div>

        <div class="lm-loan-list-filter">
            <div class="lm-loan-list-filter-toggle" id="loanFilterToggle">
                <span class="lm-loan-list-filter-toggle-label">{{ $text('Filters', 'តម្រង') }}</span>
                <span class="lm-loan-list-filter-toggle-actions">
                    <a href="javascript:void(0)" id="loanFilterReset" class="lm-loan-list-reset">{{ $text('Reset', 'កំណត់ឡើងវិញ') }}</a>
                    <i class="fa fa-chevron-up" id="loanFilterToggleIcon"></i>
                </span>
            </div>
            <div class="lm-loan-list-filter-body">
            <div class="lm-loan-list-field wide">
                <label for="loan_global_search">{{ $text('Search', 'ស្វែងរក') }}</label>
                <div class="lm-loan-list-input-icon">
                    <i class="fa fa-search"></i>
                    <input type="search" id="loan_global_search" class="form-control" placeholder="{{ $text('Loan #, invoice, customer, phone...', 'លេខកម្ចី វិក្កយបត្រ អតិថិជន ទូរស័ព្ទ...') }}">
                </div>
            </div>
            <div class="lm-loan-list-field">
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
            startDate: moment().startOf('month'),
            endDate: moment(),
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
        dom: "<'row lm-dt-top'<'col-sm-12'l>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row lm-dt-bottom'<'col-sm-5'i><'col-sm-7'p>>",
        language: {
            processing: loanListText.processing,
            search: loanListText.search + ':',
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

    $('#loan_global_search').on('input', debounce(function(){
        loanTable.search(this.value || '').draw();
    }, 300));

    $(document).on('change', '#status,#location_name,#collector_name', function(){
        loanTable.ajax.reload();
    });

    $('#customer').on('input', debounce(function(){
        loanTable.ajax.reload();
    }, 300));

    $(document).on('click', '#loanFilterReset', function(){
        $('#sell_list_filter_date_range,#start_date,#end_date,#customer,#loan_global_search').val('');
        $('#status,#location_name,#collector_name').val('').trigger('change.select2');
        loanTable.search('').ajax.reload();
    });

    var $loanFilter = $('.lm-loan-list-filter');
    var loanFilterStateKey = 'lm_loan_filter_collapsed';
    if ($loanFilter.length && localStorage) {
        if (localStorage.getItem(loanFilterStateKey) === '1') {
            $loanFilter.addClass('collapsed');
        }
        $('#loanFilterToggle').on('click', function(e){
            if ($(e.target).closest('#loanFilterReset').length) {
                return;
            }
            var isCollapsed = $loanFilter.toggleClass('collapsed').hasClass('collapsed');
            try { localStorage.setItem(loanFilterStateKey, isCollapsed ? '1' : '0'); } catch(err){}
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
