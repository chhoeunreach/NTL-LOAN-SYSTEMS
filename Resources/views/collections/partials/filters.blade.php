@php
    $collectionFilterKey = 'lm_collection_filters_v2_'.preg_replace('/[^a-z0-9_]/i', '_', $page ?? $report ?? 'default');
@endphp

<form method="get" class="loan-collection-filters" id="loanCollectionFiltersForm" data-collapse-key="{{ $collectionFilterKey }}">
    <div class="box box-default lm-collection-filter-panel is-collapsed" id="loanCollectionFilterPanel">
        <div class="box-header with-border">
            <h3 class="box-title">
                <button type="button" class="lm-collection-filter-title" id="loanCollectionFilterTitle" aria-expanded="false" aria-controls="loanCollectionFilterBody">
                    <i class="fa fa-filter"></i> Filters
                </button>
            </h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool lm-collection-filter-toggle" id="loanCollectionFilterToggle" aria-expanded="false" aria-controls="loanCollectionFilterBody">
                    <span id="loanCollectionFilterToggleText">Expand</span>
                    <i class="fa fa-chevron-down" id="loanCollectionFilterToggleIcon" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="box-body" id="loanCollectionFilterBody">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Collection Status</label>
                        <select name="collection_status" class="form-control">
                            <option value="">All</option>
                            @foreach($options['statuses'] ?? [] as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['collection_status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Overdue Bucket</label>
                        <select name="overdue_bucket" class="form-control">
                            <option value="">All</option>
                            @foreach($options['buckets'] ?? [] as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['overdue_bucket'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Collector</label>
                        <select name="collector_id" class="form-control">
                            <option value="">All</option>
                            @foreach($options['collectors'] ?? [] as $key => $label)
                                <option value="{{ $key }}" {{ (string)($filters['collector_id'] ?? '') === (string)$key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Business Location</label>
                        <select name="business_location_id" class="form-control">
                            <option value="">All</option>
                            @foreach($options['locations'] ?? [] as $key => $label)
                                <option value="{{ $key }}" {{ (string)($filters['business_location_id'] ?? '') === (string)$key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Risk Level</label>
                        <select name="risk_level" class="form-control">
                            <option value="">All</option>
                            @foreach($options['riskLevels'] ?? [] as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['risk_level'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Payment Status</label>
                        <select name="payment_status" class="form-control">
                            <option value="">All</option>
                            @foreach(['unpaid' => 'Unpaid', 'partial' => 'Partial', 'paid' => 'Paid', 'confirmed' => 'Confirmed'] as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['payment_status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Skip Level</label>
                        <select name="skip_level" class="form-control">
                            <option value="">All</option>
                            @foreach($options['skipLevels'] ?? [] as $key => $label)
                                <option value="{{ $key }}" {{ ($filters['skip_level'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Legal Status</label>
                        <input type="text" name="legal_status" class="form-control" value="{{ $filters['legal_status'] ?? '' }}">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
                    </div>
                </div>
                <div class="col-md-6 text-right" style="padding-top:25px;">
                    <button class="btn btn-primary" type="submit" id="loanCollectionFilterApply"><i class="fa fa-filter"></i> Apply</button>
                    <a href="{{ url()->current() }}" class="btn btn-default" id="loanCollectionFilterReset">Reset</a>
                </div>
            </div>
        </div>
    </div>
</form>

@once
    <style>
        .lm-collection-filter-panel .box-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .lm-collection-filter-title,
        .lm-collection-filter-toggle {
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .lm-collection-filter-title {
            border: 0;
            padding: 0;
            background: transparent;
            color: #111827;
            font-weight: 700;
        }
        .lm-collection-filter-toggle {
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
        .lm-collection-filter-toggle:hover,
        .lm-collection-filter-toggle:focus {
            border-color: var(--lm-primary-200, #bfdbfe);
            background: var(--lm-primary-50, #eff6ff);
            color: var(--lm-primary, #2563eb);
            outline: 0;
        }
        .lm-collection-filter-panel.is-collapsed #loanCollectionFilterBody {
            display: none;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('loanCollectionFiltersForm');
            var panel = document.getElementById('loanCollectionFilterPanel');
            var title = document.getElementById('loanCollectionFilterTitle');
            var toggle = document.getElementById('loanCollectionFilterToggle');
            var toggleText = document.getElementById('loanCollectionFilterToggleText');
            var toggleIcon = document.getElementById('loanCollectionFilterToggleIcon');
            var reset = document.getElementById('loanCollectionFilterReset');
            if (!form || !panel || !toggle || !toggleText || !toggleIcon) return;

            var storageKey = form.getAttribute('data-collapse-key') || 'lm_collection_filters_default';

            function setCollapsed(collapsed) {
                panel.classList.toggle('is-collapsed', collapsed);
                toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                if (title) title.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
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
            if (title) title.addEventListener('click', togglePanel);
            form.addEventListener('submit', function () { setCollapsed(true); });
            if (reset) reset.addEventListener('click', function () {
                try { window.localStorage.setItem(storageKey, '1'); } catch (e) {}
            });
        });
    </script>
@endonce
