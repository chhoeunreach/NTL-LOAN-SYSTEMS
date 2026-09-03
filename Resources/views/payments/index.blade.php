@extends('loanmanagement::layouts.app')
@section('title', 'Payments')

@section('content_body')
<section class="content-header">
    <h1>Payments</h1>
</section>

<section class="content">
    <div class="lm-payment-summary-grid">
        <div class="lm-payment-summary-card tone-green">
            <div class="lm-payment-summary-icon"><i class="fa fa-money"></i></div>
            <div class="lm-payment-summary-copy">
                <span>Filtered Amount</span>
                <strong>$ {{ number_format($summary['amount'] ?? 0, 2) }}</strong>
                <small>Total value for current filters</small>
            </div>
        </div>

        <div class="lm-payment-summary-card tone-cyan">
            <div class="lm-payment-summary-icon"><i class="fa fa-list"></i></div>
            <div class="lm-payment-summary-copy">
                <span>Payments</span>
                <strong>{{ number_format($summary['count'] ?? 0) }}</strong>
                <small>Matching payment records</small>
            </div>
        </div>

        <div class="lm-payment-summary-card tone-blue">
            <div class="lm-payment-summary-icon"><i class="fa fa-bank"></i></div>
            <div class="lm-payment-summary-copy">
                <span>Loan Payments</span>
                <strong>$ {{ number_format($summary['loan_amount'] ?? 0, 2) }}</strong>
                <small>{{ number_format($summary['loan_count'] ?? 0) }} records</small>
            </div>
        </div>

        <div class="lm-payment-summary-card tone-violet">
            <div class="lm-payment-summary-icon"><i class="fa fa-calendar"></i></div>
            <div class="lm-payment-summary-copy">
                <span>Monthly Payments</span>
                <strong>$ {{ number_format($summary['monthly_amount'] ?? 0, 2) }}</strong>
                <small>{{ number_format($summary['monthly_count'] ?? 0) }} records</small>
            </div>
        </div>

        <div class="lm-payment-summary-card tone-orange">
            <div class="lm-payment-summary-icon"><i class="fa fa-check-circle"></i></div>
            <div class="lm-payment-summary-copy">
                <span>Pay Off</span>
                <strong>$ {{ number_format($summary['payoff_amount'] ?? 0, 2) }}</strong>
                <small>{{ number_format($summary['payoff_count'] ?? 0) }} records</small>
            </div>
        </div>
    </div>

    <div class="box box-primary lm-payment-filter-panel is-collapsed" id="loanPaymentFilterPanel">
        <div class="box-header with-border">
            <h3 class="box-title">
                <button type="button" class="lm-payment-filter-title" id="loanPaymentFilterTitle" aria-expanded="false" aria-controls="loanPaymentFilterBody">
                    <i class="fa fa-filter"></i> Filters
                </button>
            </h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool lm-payment-filter-toggle" id="loanPaymentFilterToggle" aria-expanded="false" aria-controls="loanPaymentFilterBody">
                    <span id="loanPaymentFilterToggleText">Expand</span>
                    <i class="fa fa-chevron-down" id="loanPaymentFilterToggleIcon" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="box-body" id="loanPaymentFilterBody">
            <form method="GET" action="{{ route('loan-management.payments.index') }}" id="loanPaymentFilterForm">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Receipt, reference, loan, customer">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Loan #</label>
                            <input type="text" name="loan_number" class="form-control" value="{{ $filters['loan_number'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Customer</label>
                            <input type="text" name="customer" class="form-control" value="{{ $filters['customer'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Method</label>
                            <select name="method" class="form-control">
                                <option value="">All</option>
                                @foreach($methods as $key => $label)
                                    <option value="{{ $label }}" {{ ($filters['method'] ?? '') == $label || ($filters['method'] ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Type</label>
                            <select name="payment_type" class="form-control">
                                <option value="">All</option>
                                <option value="loan" {{ ($filters['payment_type'] ?? '') === 'loan' ? 'selected' : '' }}>Loan</option>
                                <option value="monthly" {{ ($filters['payment_type'] ?? '') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                <option value="payoff" {{ ($filters['payment_type'] ?? '') === 'payoff' ? 'selected' : '' }}>Pay Off</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Location</label>
                            <select name="location_id" class="form-control">
                                <option value="">All</option>
                                @foreach($locations as $id => $name)
                                    <option value="{{ $id }}" {{ (string)($filters['location_id'] ?? '') === (string)$id ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">All</option>
                                @foreach($statuses as $status => $label)
                                    <option value="{{ $status }}" {{ ($filters['status'] ?? '') == $status ? 'selected' : '' }}>{{ ucfirst($label) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Date From</label>
                            <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Date To</label>
                            <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                        </div>
                    </div>
                    <div class="col-md-6 text-right" style="padding-top:25px;">
                        <button type="submit" class="btn btn-primary" id="loanPaymentFilterApply"><i class="fa fa-filter"></i> Filter</button>
                        <a href="{{ route('loan-management.payments.index') }}" class="btn btn-default" id="loanPaymentFilterReset"><i class="fa fa-refresh"></i> Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">Payment List</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Paid Date</th>
                        <th>Loan #</th>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Method</th>
                        <th class="text-right">Amount</th>
                        <th>Status</th>
                        <th>Reference</th>
                        <th>Received By</th>
                        <th style="width:145px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                        <tr>
                            <td>
                                <a href="{{ route('loan-management.payments.show', $payment->id) }}">
                                    {{ $payment->receipt_number ?? ('#'.$payment->id) }}
                                </a>
                            </td>
                            <td>{{ ! empty($payment->paid_date) ? \Carbon\Carbon::parse($payment->paid_date)->format('d-m-Y') : '-' }}</td>
                            <td>
                                @if(Route::has('loan-management.loans.view') && ! empty($payment->loan_id))
                                    <a href="{{ route('loan-management.loans.view', $payment->loan_id) }}">{{ $payment->loan_number ?? ('Loan #'.$payment->loan_id) }}</a>
                                @else
                                    {{ $payment->loan_number ?? '-' }}
                                @endif
                            </td>
                            <td>
                                <strong>{{ $payment->customer_name ?? '-' }}</strong><br>
                                <small class="text-muted">{{ $payment->customer_phone ?? '' }}</small>
                            </td>
                            <td>
                                <span class="label label-{{ \Modules\LoanManagement\Http\Controllers\LoanPaymentController::paymentTypeLabelClass($payment->payment_type ?? 'monthly') }}">
                                    {{ \Modules\LoanManagement\Http\Controllers\LoanPaymentController::paymentTypeLabel($payment->payment_type ?? 'monthly') }}
                                </span>
                            </td>
                            <td>{{ $payment->payment_method ?? '-' }}</td>
                            <td class="text-right">$ {{ number_format((float) ($payment->amount ?? 0), 2) }}</td>
                            <td><span class="label label-{{ in_array($payment->status, ['paid', 'confirmed', 'completed']) ? 'success' : 'default' }}">{{ ucfirst($payment->status ?? '-') }}</span></td>
                            <td>{{ $payment->reference_number ?? '-' }}</td>
                            <td>{{ $payment->received_by ?? '-' }}</td>
                            <td>
                                <a href="{{ route('loan-management.payments.show', $payment->id) }}" class="btn btn-xs btn-info"><i class="fa fa-eye"></i> View</a>
                                @if(\Modules\LoanManagement\Helpers\LoanMenuHelper::loanUserCan('loan_management.payment|loan_management.payments.create|loan_management.edit'))
                                    <a href="{{ route('loan-management.payments.edit', $payment->id) }}" class="btn btn-xs btn-primary"><i class="fa fa-edit"></i> Edit</a>
                                    <form method="POST" action="{{ route('loan-management.payments.destroy', $payment->id) }}" style="display:inline;" onsubmit="return confirm('Delete this payment? This will update loan totals.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                                    </form>
                                @else
                                    <span class="text-muted">View only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="text-center text-muted">No payments found.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="text-center">
                {{ $payments->links() }}
            </div>
        </div>
    </div>
</section>
@endsection

@section('loan_css')
    <style>
        .lm-payment-summary-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }
        .lm-payment-summary-card {
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
        .lm-payment-summary-icon {
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
        .lm-payment-summary-copy {
            min-width: 0;
        }
        .lm-payment-summary-copy span,
        .lm-payment-summary-copy small {
            display: block;
            color: #64748b;
            line-height: 1.25;
        }
        .lm-payment-summary-copy span {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .lm-payment-summary-copy strong {
            display: block;
            margin: 6px 0 4px;
            color: #111827;
            font-size: 22px;
            font-weight: 900;
            line-height: 1.1;
            word-break: break-word;
        }
        .lm-payment-summary-copy small {
            font-size: 12px;
            font-weight: 600;
        }
        .lm-payment-summary-card.tone-green .lm-payment-summary-icon { background: #16a34a; }
        .lm-payment-summary-card.tone-cyan .lm-payment-summary-icon { background: #0891b2; }
        .lm-payment-summary-card.tone-blue .lm-payment-summary-icon { background: #2563eb; }
        .lm-payment-summary-card.tone-violet .lm-payment-summary-icon { background: #7c3aed; }
        .lm-payment-summary-card.tone-orange .lm-payment-summary-icon { background: #ea580c; }

        .lm-payment-filter-panel .box-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .lm-payment-filter-title,
        .lm-payment-filter-toggle {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .lm-payment-filter-title {
            border: 0;
            padding: 0;
            background: transparent;
            color: #111827;
            font-weight: 700;
        }
        .lm-payment-filter-toggle {
            min-height: 30px;
            padding: 0 10px;
            border: 1px solid #d8e0ea;
            border-radius: 6px;
            background: #fff;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .lm-payment-filter-toggle:hover,
        .lm-payment-filter-toggle:focus {
            border-color: var(--lm-primary-200, #bfdbfe);
            background: var(--lm-primary-50, #eff6ff);
            color: var(--lm-primary, #2563eb);
            outline: 0;
        }
        .lm-payment-filter-panel.is-collapsed #loanPaymentFilterBody {
            display: none;
        }
        @media (max-width: 1400px) {
            .lm-payment-summary-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }
        @media (max-width: 767px) {
            .lm-payment-summary-grid {
                grid-template-columns: 1fr;
            }
            .lm-payment-summary-card {
                min-height: 94px;
                padding: 14px;
            }
            .lm-payment-summary-copy strong {
                font-size: 20px;
            }
        }
    </style>
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
                toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                if (title) {
                    title.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                }
                toggleText.textContent = collapsed ? 'Expand' : 'Collapse';
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
