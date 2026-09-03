@extends('loanmanagement::layouts.app')
@section('title', 'Collection Visits')

@php
    $resultBadge = function ($result) {
        $result = strtolower((string) $result);
        return match ($result) {
            'visited', 'completed', 'success', 'paid', 'promise_to_pay' => 'success',
            'pending', 'scheduled', 'open' => 'warning',
            'failed', 'cancelled', 'not_home', 'no_answer' => 'danger',
            default => 'default',
        };
    };
@endphp

@section('content_body')
<section class="content-header">
    <h1>Collection Visits</h1>
</section>

<section class="content">
    <div class="lm-visit-summary-grid">
        <div class="lm-visit-summary-card tone-blue">
            <div class="lm-visit-summary-icon"><i class="fa fa-street-view"></i></div>
            <div class="lm-visit-summary-copy">
                <span>Total Visits</span>
                <strong>{{ number_format($summary['total'] ?? 0) }}</strong>
                <small>Matching current filters</small>
            </div>
        </div>
        <div class="lm-visit-summary-card tone-green">
            <div class="lm-visit-summary-icon"><i class="fa fa-calendar-check-o"></i></div>
            <div class="lm-visit-summary-copy">
                <span>Today</span>
                <strong>{{ number_format($summary['today'] ?? 0) }}</strong>
                <small>Visited today</small>
            </div>
        </div>
        <div class="lm-visit-summary-card tone-orange">
            <div class="lm-visit-summary-icon"><i class="fa fa-clock-o"></i></div>
            <div class="lm-visit-summary-copy">
                <span>Pending</span>
                <strong>{{ number_format($summary['pending'] ?? 0) }}</strong>
                <small>Needs follow-up</small>
            </div>
        </div>
        <div class="lm-visit-summary-card tone-violet">
            <div class="lm-visit-summary-icon"><i class="fa fa-check-circle"></i></div>
            <div class="lm-visit-summary-copy">
                <span>Completed</span>
                <strong>{{ number_format($summary['completed'] ?? 0) }}</strong>
                <small>Finished visits</small>
            </div>
        </div>
    </div>

    <div class="box box-primary lm-visit-filter-panel is-collapsed" id="loanVisitFilterPanel">
        <div class="box-header with-border">
            <h3 class="box-title">
                <button type="button" class="lm-visit-filter-title" id="loanVisitFilterTitle" aria-expanded="false" aria-controls="loanVisitFilterBody">
                    <i class="fa fa-filter"></i> Filters
                </button>
            </h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool lm-visit-filter-toggle" id="loanVisitFilterToggle" aria-expanded="false" aria-controls="loanVisitFilterBody">
                    <span id="loanVisitFilterToggleText">Expand</span>
                    <i class="fa fa-chevron-down" id="loanVisitFilterToggleIcon" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="box-body" id="loanVisitFilterBody">
            <form method="GET" action="{{ route('loan-management.collection-visits.index') }}" id="loanVisitFilterForm">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Loan, customer, phone, address">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Collector</label>
                            <select name="collector" class="form-control">
                                <option value="">All</option>
                                @foreach($collectors as $value => $label)
                                    <option value="{{ $value }}" {{ ($filters['collector'] ?? '') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Result</label>
                            <select name="result" class="form-control">
                                <option value="">All</option>
                                @foreach($results as $value => $label)
                                    <option value="{{ $value }}" {{ ($filters['result'] ?? '') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
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
                    <div class="col-md-1 lm-visit-filter-actions">
                        <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-filter"></i> Apply</button>
                        <a href="{{ route('loan-management.collection-visits.index') }}" class="btn btn-default btn-block" id="loanVisitFilterReset">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="box box-solid lm-visit-table-card">
        <div class="box-header with-border">
            <h3 class="box-title">Visit Records</h3>
        </div>
        <div class="box-body table-responsive">
            <table class="table table-bordered table-hover lm-visit-table">
                <thead>
                    <tr>
                        <th>Visit Date</th>
                        <th>Loan #</th>
                        <th>Customer</th>
                        <th>Collector</th>
                        <th>Result</th>
                        <th>Location</th>
                        <th>Note</th>
                        <th style="width:90px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visits as $visit)
                        @php
                            $mapUrl = !empty($visit->latitude) && !empty($visit->longitude)
                                ? 'https://www.google.com/maps?q='.$visit->latitude.','.$visit->longitude
                                : null;
                        @endphp
                        <tr>
                            <td>{{ !empty($visit->visited_at) ? \Carbon\Carbon::parse($visit->visited_at)->format('d-m-Y H:i') : '-' }}</td>
                            <td>
                                @if(Route::has('loan-management.loans.view') && !empty($visit->loan_id))
                                    <a href="{{ route('loan-management.loans.view', $visit->loan_id) }}">{{ $visit->loan_number ?? ('Loan #'.$visit->loan_id) }}</a>
                                @else
                                    {{ $visit->loan_number ?? '-' }}
                                @endif
                            </td>
                            <td>
                                <strong>{{ $visit->customer_name ?? '-' }}</strong><br>
                                <small class="text-muted">{{ $visit->customer_phone ?? '' }}</small>
                            </td>
                            <td>{{ $visit->collector_name ?? '-' }}</td>
                            <td><span class="label label-{{ $resultBadge($visit->result ?? '') }}">{{ ucwords(str_replace('_', ' ', $visit->result ?? 'pending')) }}</span></td>
                            <td>
                                {{ $visit->address_snapshot ?? '-' }}
                                @if($mapUrl)
                                    <br><a href="{{ $mapUrl }}" target="_blank" rel="noopener" class="lm-visit-map-link"><i class="fa fa-map-marker"></i> Map</a>
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Str::limit($visit->note ?? '-', 90) }}</td>
                            <td>
                                @if($mapUrl)
                                    <a class="btn btn-xs btn-default" href="{{ $mapUrl }}" target="_blank" rel="noopener"><i class="fa fa-location-arrow"></i> Open</a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted">No collection visits found.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @if(method_exists($visits, 'links'))
                <div class="text-center">{{ $visits->links() }}</div>
            @endif
        </div>
    </div>
</section>
@endsection

@section('loan_css')
    <style>
        .lm-visit-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }
        .lm-visit-summary-card {
            display: flex;
            align-items: center;
            gap: 13px;
            min-height: 108px;
            padding: 18px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .06);
        }
        .lm-visit-summary-icon {
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
        .lm-visit-summary-copy span,
        .lm-visit-summary-copy small {
            display: block;
            color: #64748b;
            line-height: 1.25;
        }
        .lm-visit-summary-copy span {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }
        .lm-visit-summary-copy strong {
            display: block;
            margin: 6px 0 4px;
            color: #111827;
            font-size: 24px;
            font-weight: 900;
            line-height: 1.1;
        }
        .lm-visit-summary-card.tone-blue .lm-visit-summary-icon { background: #2563eb; }
        .lm-visit-summary-card.tone-green .lm-visit-summary-icon { background: #16a34a; }
        .lm-visit-summary-card.tone-orange .lm-visit-summary-icon { background: #ea580c; }
        .lm-visit-summary-card.tone-violet .lm-visit-summary-icon { background: #7c3aed; }
        .lm-visit-filter-panel .box-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .lm-visit-filter-title,
        .lm-visit-filter-toggle {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .lm-visit-filter-title {
            border: 0;
            padding: 0;
            background: transparent;
            color: #111827;
            font-weight: 700;
        }
        .lm-visit-filter-toggle {
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
        .lm-visit-filter-toggle:hover,
        .lm-visit-filter-toggle:focus {
            border-color: var(--lm-primary-200, #bfdbfe);
            background: var(--lm-primary-50, #eff6ff);
            color: var(--lm-primary, #2563eb);
            outline: 0;
        }
        .lm-visit-filter-panel.is-collapsed #loanVisitFilterBody {
            display: none;
        }
        .lm-visit-filter-actions {
            padding-top: 25px;
        }
        .lm-visit-table-card {
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 28px rgba(15, 23, 42, .05);
        }
        .lm-visit-table > thead > tr > th {
            background: #f8fafc;
            color: #334155;
            font-size: 12px;
            text-transform: uppercase;
        }
        .lm-visit-map-link {
            font-size: 12px;
            font-weight: 700;
        }
        @media (max-width: 1200px) {
            .lm-visit-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (max-width: 767px) {
            .lm-visit-summary-grid {
                grid-template-columns: 1fr;
            }
            .lm-visit-summary-card {
                min-height: 94px;
                padding: 14px;
            }
            .lm-visit-filter-actions {
                padding-top: 0;
            }
        }
    </style>
@endsection

@section('loan_js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var panel = document.getElementById('loanVisitFilterPanel');
            var title = document.getElementById('loanVisitFilterTitle');
            var toggle = document.getElementById('loanVisitFilterToggle');
            var toggleText = document.getElementById('loanVisitFilterToggleText');
            var toggleIcon = document.getElementById('loanVisitFilterToggleIcon');
            var form = document.getElementById('loanVisitFilterForm');
            var reset = document.getElementById('loanVisitFilterReset');
            var storageKey = 'lm_collection_visit_filters_collapsed_v1';

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
