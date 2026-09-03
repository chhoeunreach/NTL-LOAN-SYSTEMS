@extends('loanmanagement::layouts.app')
@section('title', $isKhmer ? 'សង្ខេបការបង់ប្រាក់តាមប្រភេទ' : 'Payment Summary by Type')

@php
    $t = fn ($en, $km) => $isKhmer ? $km : $en;
    $money = fn ($value) => '$ '.number_format((float) ($value ?? 0), 2);
    $totals = [
        'count' => collect($rows)->sum('count'),
        'total' => collect($rows)->sum('total'),
        'cash' => collect($rows)->sum('cash'),
        'aba' => collect($rows)->sum('aba'),
        'acleda' => collect($rows)->sum('acleda'),
        'wing' => collect($rows)->sum('wing'),
        'et' => collect($rows)->sum('et'),
        'card' => collect($rows)->sum('card'),
        'other' => collect($rows)->sum('other'),
    ];
    $digitalTotal = $totals['aba'] + $totals['acleda'] + $totals['wing'] + $totals['et'] + $totals['card'];
@endphp

@section('loan_css')
@parent
<style>
    .lm-finance-report-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 18px;
        margin-bottom: 18px;
        padding: 20px 22px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
    }
    .lm-finance-report-eyebrow {
        display: block;
        margin-bottom: 5px;
        color: #2563eb;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .lm-finance-report-hero h2 {
        margin: 0 0 6px;
        color: #111827;
        font-size: 24px;
        font-weight: 900;
        line-height: 1.15;
    }
    .lm-finance-report-hero p {
        margin: 0;
        color: #64748b;
        font-size: 13px;
        font-weight: 600;
    }
    .lm-finance-report-actions {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }
    .lm-finance-summary-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }
    .lm-finance-summary-card {
        display: flex;
        align-items: center;
        gap: 13px;
        min-height: 112px;
        padding: 18px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
    }
    .lm-finance-summary-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 46px;
        width: 46px;
        height: 46px;
        border-radius: 8px;
        color: #fff;
        font-size: 20px;
    }
    .lm-finance-summary-copy {
        min-width: 0;
    }
    .lm-finance-summary-copy span,
    .lm-finance-summary-copy small {
        display: block;
        color: #64748b;
        line-height: 1.25;
    }
    .lm-finance-summary-copy span {
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }
    .lm-finance-summary-copy strong {
        display: block;
        margin: 6px 0 4px;
        color: #111827;
        font-size: 21px;
        font-weight: 900;
        line-height: 1.1;
        word-break: break-word;
    }
    .lm-finance-summary-copy small {
        font-size: 12px;
        font-weight: 600;
    }
    .lm-finance-summary-card.tone-green .lm-finance-summary-icon { background: #16a34a; }
    .lm-finance-summary-card.tone-blue .lm-finance-summary-icon { background: #2563eb; }
    .lm-finance-summary-card.tone-cyan .lm-finance-summary-icon { background: #0891b2; }
    .lm-finance-summary-card.tone-orange .lm-finance-summary-icon { background: #ea580c; }
    .lm-finance-summary-card.tone-slate .lm-finance-summary-icon { background: #475569; }
    .lm-payment-filter-box,
    .lm-finance-table-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, .05);
    }
    .lm-payment-filter-box .box-header,
    .lm-finance-table-card .box-header {
        border-bottom-color: #e2e8f0;
    }
    .lm-payment-filter-box .box-title,
    .lm-finance-table-card .box-title {
        color: #111827;
        font-size: 16px;
        font-weight: 900;
    }
    .lm-finance-filter-actions {
        display: flex;
        gap: 6px;
    }
    .lm-payment-summary-table th {
        background: #f8fafc;
        color: #334155;
        font-size: 12px;
        font-weight: 900;
        text-align: center;
        text-transform: uppercase;
        vertical-align: middle !important;
    }
    .lm-payment-summary-table td {
        vertical-align: middle !important;
    }
    .lm-payment-summary-table tfoot th {
        background: #eff6ff;
        color: #111827;
        font-weight: 900;
    }
    .lm-payment-print-title {
        display: none;
    }
    @media (max-width: 1400px) {
        .lm-finance-summary-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (max-width: 991px) {
        .lm-finance-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .lm-finance-report-hero {
            align-items: stretch;
            flex-direction: column;
        }
        .lm-finance-report-actions {
            justify-content: flex-start;
        }
    }
    @media (max-width: 767px) {
        .lm-finance-summary-grid {
            grid-template-columns: 1fr;
        }
        .lm-finance-summary-card {
            min-height: 94px;
            padding: 14px;
        }
        .lm-finance-summary-copy strong {
            font-size: 20px;
        }
        .lm-finance-filter-actions {
            padding-top: 0 !important;
        }
    }
    @media print {
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        .lm-sidebar,
        .lm-header,
        .lm-breadcrumb-wrap,
        .lm-payment-filter-box,
        .lm-finance-report-hero,
        .lm-finance-summary-grid,
        .lm-payment-no-print,
        .main-footer,
        .no-print {
            display: none !important;
        }
        .lm-main,
        .lm-content,
        .lm-workspace,
        .content,
        .container-fluid {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: none !important;
        }
        .lm-payment-print-title {
            display: block;
            margin: 0 0 10px;
            text-align: center;
        }
        .lm-payment-print-title h2 {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 700;
        }
        .lm-payment-print-title p {
            margin: 0;
            color: #4b5563;
            font-size: 11px;
        }
        .box {
            border: 1px solid #d1d5db !important;
            box-shadow: none !important;
        }
        .table > thead > tr > th,
        .table > tbody > tr > td,
        .table > tfoot > tr > th {
            padding: 5px 6px !important;
            border-color: #d1d5db !important;
            font-size: 11px !important;
        }
        a[href]:after {
            content: "" !important;
        }
    }
</style>
@endsection

@section('content_body')
<section class="content-header">
    <h1>{{ $t('Payment Summary by Type', 'សង្ខេបការបង់ប្រាក់តាមប្រភេទ') }}</h1>
</section>

<section class="content">
    <div class="lm-payment-print-title">
        <h2>{{ $t('Payment Summary by Type', 'សង្ខេបការបង់ប្រាក់តាមប្រភេទ') }}</h2>
        <p>
            {{ \Carbon\Carbon::parse($filters['date_from'])->format('d-m-Y') }}
            -
            {{ \Carbon\Carbon::parse($filters['date_to'])->format('d-m-Y') }}
        </p>
    </div>

    <div class="lm-finance-report-hero lm-payment-no-print">
        <div>
            <span class="lm-finance-report-eyebrow">{{ $t('Financial reports', 'របាយការណ៍ហិរញ្ញវត្ថុ') }}</span>
            <h2>{{ $t('Payment performance summary', 'សង្ខេបលទ្ធផលការបង់ប្រាក់') }}</h2>
            <p>
                {{ \Carbon\Carbon::parse($filters['date_from'])->format('d-m-Y') }}
                -
                {{ \Carbon\Carbon::parse($filters['date_to'])->format('d-m-Y') }}
            </p>
        </div>
        <div class="lm-finance-report-actions">
            <a href="{{ route('loan-management.reports.dashboard', request()->query()) }}" class="btn btn-default">
                <i class="fa fa-arrow-left"></i> {{ $t('Dashboard Reports', 'របាយការណ៍ផ្ទាំងគ្រប់គ្រង') }}
            </a>
            <button type="button" class="btn btn-success" onclick="window.print();">
                <i class="fa fa-print"></i> {{ $t('Print Report', 'បោះពុម្ពរបាយការណ៍') }}
            </button>
        </div>
    </div>

    <div class="lm-finance-summary-grid lm-payment-no-print">
        <div class="lm-finance-summary-card tone-green">
            <div class="lm-finance-summary-icon"><i class="fa fa-money"></i></div>
            <div class="lm-finance-summary-copy">
                <span>{{ $t('Total Collected', 'ប្រមូលបានសរុប') }}</span>
                <strong>{{ $money($totals['total']) }}</strong>
                <small>{{ $t('All payment channels', 'គ្រប់ប្រភពបង់ប្រាក់') }}</small>
            </div>
        </div>
        <div class="lm-finance-summary-card tone-blue">
            <div class="lm-finance-summary-icon"><i class="fa fa-list"></i></div>
            <div class="lm-finance-summary-copy">
                <span>{{ $t('Transactions', 'ប្រតិបត្តិការ') }}</span>
                <strong>{{ number_format((float) $totals['count'], 0) }}</strong>
                <small>{{ $t('Filtered records', 'ទិន្នន័យបានចម្រោះ') }}</small>
            </div>
        </div>
        <div class="lm-finance-summary-card tone-cyan">
            <div class="lm-finance-summary-icon"><i class="fa fa-credit-card"></i></div>
            <div class="lm-finance-summary-copy">
                <span>{{ $t('Digital Channels', 'បង់តាមឌីជីថល') }}</span>
                <strong>{{ $money($digitalTotal) }}</strong>
                <small>ABA, ACLEDA, WING, E&amp;T, CARD</small>
            </div>
        </div>
        <div class="lm-finance-summary-card tone-orange">
            <div class="lm-finance-summary-icon"><i class="fa fa-briefcase"></i></div>
            <div class="lm-finance-summary-copy">
                <span>{{ $t('Cash', 'លុយសុទ្ធ') }}</span>
                <strong>{{ $money($totals['cash']) }}</strong>
                <small>{{ $t('Cash collection total', 'សរុបការបង់ជាសាច់ប្រាក់') }}</small>
            </div>
        </div>
        <div class="lm-finance-summary-card tone-slate">
            <div class="lm-finance-summary-icon"><i class="fa fa-ellipsis-h"></i></div>
            <div class="lm-finance-summary-copy">
                <span>{{ $t('Other', 'ផ្សេងៗ') }}</span>
                <strong>{{ $money($totals['other']) }}</strong>
                <small>{{ $t('Unmatched payment methods', 'វិធីបង់ប្រាក់ផ្សេងៗ') }}</small>
            </div>
        </div>
    </div>

    <div class="box box-primary lm-payment-filter-box">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fa fa-filter"></i> {{ $t('Filters', 'តម្រង') }}</h3>
        </div>
        <div class="box-body">
            <form method="GET" action="{{ route('loan-management.reports.payment-summary-by-type') }}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ $t('Search', 'ស្វែងរក') }}</label>
                            <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ $t('Loan, invoice, customer, phone', 'កម្ចី វិក្កយបត្រ អតិថិជន ទូរស័ព្ទ') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>{{ $t('Location', 'ទីតាំង') }}</label>
                            <select name="location_id" class="form-control">
                                <option value="">{{ $t('All', 'ទាំងអស់') }}</option>
                                @foreach($locations as $id => $name)
                                    <option value="{{ $id }}" {{ (string) ($filters['location_id'] ?? '') === (string) $id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ $t('Date From', 'ចាប់ពីថ្ងៃ') }}</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>{{ $t('Date To', 'ដល់ថ្ងៃ') }}</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2 lm-finance-filter-actions" style="padding-top:25px;">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> {{ $t('Filter', 'ចម្រោះ') }}</button>
                        <a href="{{ route('loan-management.reports.payment-summary-by-type') }}" class="btn btn-default" title="{{ $t('Reset', 'កំណត់ឡើងវិញ') }}"><i class="fa fa-refresh"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="box box-solid lm-finance-table-card">
        <div class="box-header with-border">
            <h3 class="box-title">
                <i class="fa fa-table"></i> {{ $t('Payment Summary by Type', 'សង្ខេបការបង់ប្រាក់តាមប្រភេទ') }}
            </h3>
            <div class="box-tools pull-right lm-payment-no-print">
                <a href="{{ route('loan-management.reports.dashboard', request()->query()) }}" class="btn btn-box-tool" title="{{ $t('Back to Dashboard Reports', 'ត្រឡប់ទៅរបាយការណ៍ផ្ទាំងគ្រប់គ្រង') }}">
                    <i class="fa fa-arrow-left"></i>
                </a>
            </div>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered lm-payment-summary-table">
                <thead>
                    <tr>
                        <th>{{ $t('Type', 'ប្រភេទ') }}</th>
                        <th class="text-right">{{ $t('Count', 'ចំនួន') }}</th>
                        <th>{{ $t('Cash', 'លុយសុទ្ធ') }}</th>
                        <th>ABA</th>
                        <th>ACLEDA</th>
                        <th>WING</th>
                        <th>E&amp;T</th>
                        <th>CARD</th>
                        <th>{{ $t('Other', 'ផ្សេងៗ') }}</th>
                        <th>{{ $t('Total', 'បង់សរុប') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td><strong>{{ $row['label'] }}</strong></td>
                            <td class="text-right">{{ number_format((float) ($row['count'] ?? 0), 0) }}</td>
                            <td class="text-right">{{ $money($row['cash'] ?? 0) }}</td>
                            <td class="text-right">{{ $money($row['aba'] ?? 0) }}</td>
                            <td class="text-right">{{ $money($row['acleda'] ?? 0) }}</td>
                            <td class="text-right">{{ $money($row['wing'] ?? 0) }}</td>
                            <td class="text-right">{{ $money($row['et'] ?? 0) }}</td>
                            <td class="text-right">{{ $money($row['card'] ?? 0) }}</td>
                            <td class="text-right">{{ $money($row['other'] ?? 0) }}</td>
                            <td class="text-right"><strong>{{ $money($row['total'] ?? 0) }}</strong></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted">{{ $t('No payment summary found.', 'រកមិនឃើញសង្ខេបការបង់ប្រាក់') }}</td></tr>
                    @endforelse
                </tbody>
                @if(!empty($rows))
                    <tfoot>
                        <tr>
                            <th>{{ $t('Total', 'សរុប') }}</th>
                            <th class="text-right">{{ number_format((float) $totals['count'], 0) }}</th>
                            <th class="text-right">{{ $money($totals['cash']) }}</th>
                            <th class="text-right">{{ $money($totals['aba']) }}</th>
                            <th class="text-right">{{ $money($totals['acleda']) }}</th>
                            <th class="text-right">{{ $money($totals['wing']) }}</th>
                            <th class="text-right">{{ $money($totals['et']) }}</th>
                            <th class="text-right">{{ $money($totals['card']) }}</th>
                            <th class="text-right">{{ $money($totals['other']) }}</th>
                            <th class="text-right">{{ $money($totals['total']) }}</th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</section>
@endsection
