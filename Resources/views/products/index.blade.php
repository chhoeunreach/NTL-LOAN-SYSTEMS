@extends('loanmanagement::layouts.app')
@section('title', 'Products for Installment')

@php
    $lmIsKhmer = session('user.language', config('app.locale')) === 'km';
    $lmText = fn ($en, $km) => $lmIsKhmer ? $km : $en;
@endphp

@section('loan_css')
<style>
    .product-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
        margin-bottom: 18px;
    }
    .product-kpi-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 2px 8px rgba(15,23,42,0.03);
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .product-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15,23,42,0.06);
    }
    .kpi-icon-wrap {
        width: 44px;
        height: 44px;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .kpi-icon-blue { background: #eff6ff; color: #2563eb; }
    .kpi-icon-green { background: #ecfdf5; color: #16a34a; }
    .kpi-icon-amber { background: #fffbeb; color: #d97706; }
    .kpi-icon-red { background: #fef2f2; color: #dc2626; }
    .kpi-icon-purple { background: #faf5ff; color: #9333ea; }
    .kpi-meta { min-width: 0; }
    .kpi-title { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
    .kpi-val { font-size: 20px; font-weight: 800; color: #0f172a; margin-top: 1px; line-height: 1.15; }
    .kpi-sub { font-size: 11px; color: #94a3b8; font-weight: 400; margin-top: 2px; }

    .product-thumb {
        width: 46px;
        height: 46px;
        border-radius: 8px;
        object-fit: cover;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        transition: transform .15s ease;
    }
    .product-thumb:hover {
        transform: scale(1.15);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .product-thumb-fallback {
        width: 46px;
        height: 46px;
        border-radius: 8px;
        background: #f1f5f9;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }
    .stock-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 11.5px;
        font-weight: 700;
        white-space: nowrap;
    }
    .stock-badge.in-stock { background: #dcfce7; color: #15803d; }
    .stock-badge.low-stock { background: #fef3c7; color: #b45309; }
    .stock-badge.out-of-stock { background: #fee2e2; color: #dc2626; }

    .quick-qty-stepper {
        display: inline-flex;
        align-items: center;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .quick-qty-btn {
        background: #f8fafc;
        border: none;
        padding: 3px 8px;
        cursor: pointer;
        font-weight: 800;
        color: #334155;
        transition: background .12s ease;
        line-height: 1;
    }
    .quick-qty-btn:hover { background: #e2e8f0; color: #0f172a; }
    .quick-qty-val {
        padding: 2px 8px;
        font-weight: 700;
        min-width: 30px;
        text-align: center;
        font-size: 12px;
        cursor: pointer;
    }
    .quick-qty-val:hover { background: #eff6ff; text-decoration: underline; }

    .bulk-bar {
        display: none;
        background: #1e293b;
        color: #fff;
        padding: 10px 16px;
        border-radius: 8px;
        margin-bottom: 14px;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
    }

    .sim-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 12px;
    }
    .sim-pill {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: #fff;
        font-weight: 700;
        cursor: pointer;
        margin: 0 4px 6px 0;
        font-size: 12px;
        transition: all .15s ease;
    }
    .sim-pill.active {
        background: #2563eb;
        color: #fff;
        border-color: #2563eb;
    }

    /* Modal z-index guarantee over any overlays or floating widgets */
    #simulatorModal, #photoModal {
        z-index: 10500 !important;
    }
    .modal-backdrop {
        z-index: 10490 !important;
    }
</style>
@endsection

@section('content_body')
<div class="content-header" style="margin-bottom: 15px;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="margin:0; font-size: 22px; font-weight: 800; color: #0f172a;">
                <i class="fa fa-cubes text-primary" style="margin-right: 8px;"></i>
                {{ $lmText('Products for Installment', 'ទំនិញបង់រំលស់') }}
            </h1>
            <p style="margin: 4px 0 0; color: #64748b; font-size: 13px;">
                {{ $lmText('Manage installment catalog products, cash pricing, available stock units, and loan terms.', 'គ្រប់គ្រងទំនិញបង់រំលស់ តម្លៃលក់ ស្តុក និងលក្ខខណ្ឌស្នើសុំកម្ចី។') }}
            </p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="{{ route('loan-management.products.export-csv', request()->query()) }}" class="btn btn-default btn-flat" style="border-radius: 6px; font-weight: 700;">
                <i class="fa fa-download" style="margin-right: 5px;"></i> {{ $lmText('Export CSV', 'ទាញយក CSV') }}
            </a>
            <a href="{{ route('loan-management.products.create') }}" class="btn btn-primary btn-flat" style="border-radius: 6px; font-weight: 700;">
                <i class="fa fa-plus-circle" style="margin-right: 5px;"></i> {{ $lmText('Add Installment Product', 'បន្ថែមទំនិញបង់រំលស់') }}
            </a>
        </div>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success alert-dismissible" style="border-radius: 8px;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <i class="fa fa-check-circle"></i> {{ session('status') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible" style="border-radius: 8px;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <i class="fa fa-exclamation-triangle"></i> {{ $errors->first() }}
    </div>
@endif

{{-- KPI Metric Cards --}}
<div class="product-kpi-grid">
    <div class="product-kpi-card">
        <div class="kpi-icon-wrap kpi-icon-blue"><i class="fa fa-cubes"></i></div>
        <div class="kpi-meta">
            <div class="kpi-title">{{ $lmText('Total Catalog', 'ទំនិញសរុប') }}</div>
            <div class="kpi-val">{{ number_format($totalProducts) }}</div>
            <div class="kpi-sub">{{ number_format($totalStockQty) }} units available</div>
        </div>
    </div>
    <div class="product-kpi-card">
        <div class="kpi-icon-wrap kpi-icon-green"><i class="fa fa-check-circle"></i></div>
        <div class="kpi-meta">
            <div class="kpi-title">{{ $lmText('In Stock', 'មានក្នុងស្តុក') }}</div>
            <div class="kpi-val text-success">{{ number_format($inStockCount) }}</div>
            <div class="kpi-sub">> 5 units available</div>
        </div>
    </div>
    <div class="product-kpi-card">
        <div class="kpi-icon-wrap kpi-icon-amber"><i class="fa fa-exclamation-triangle"></i></div>
        <div class="kpi-meta">
            <div class="kpi-title">{{ $lmText('Low Stock', 'ស្តុកជិតអស់') }}</div>
            <div class="kpi-val text-warning">{{ number_format($lowStockCount) }}</div>
            <div class="kpi-sub">1 - 5 units left</div>
        </div>
    </div>
    <div class="product-kpi-card">
        <div class="kpi-icon-wrap kpi-icon-red"><i class="fa fa-times-circle"></i></div>
        <div class="kpi-meta">
            <div class="kpi-title">{{ $lmText('Out of Stock', 'អស់ពីស្តុក') }}</div>
            <div class="kpi-val text-danger">{{ number_format($outOfStockCount) }}</div>
            <div class="kpi-sub">0 units available</div>
        </div>
    </div>
    <div class="product-kpi-card">
        <div class="kpi-icon-wrap kpi-icon-purple"><i class="fa fa-archive"></i></div>
        <div class="kpi-meta">
            <div class="kpi-title">{{ $lmText('Retail Stock Value', 'តម្លៃលក់សរុប') }}</div>
            <div class="kpi-val">${{ number_format($totalRetailValue, 2) }}</div>
            <div class="kpi-sub">Cost: ${{ number_format($totalCostValue, 2) }}</div>
        </div>
    </div>
</div>

{{-- Filters Card --}}
<div class="box box-default" style="border-radius: 8px; border-top: 3px solid #3c8dbc; margin-bottom: 18px;">
    <div class="box-body" style="padding: 16px;">
        <form method="GET" action="{{ route('loan-management.products.index') }}" class="row" style="margin: 0;">
            <div class="col-md-3 col-sm-6" style="padding: 0 6px 10px 0;">
                <label style="font-size: 11.5px; font-weight: 700; color: #475569;">{{ $lmText('Search Name / SKU / IMEI', 'ស្វែងរក ឈ្មោះ/SKU/IMEI') }}</label>
                <div class="input-group" style="width: 100%;">
                    <span class="input-group-addon" style="background: #f8fafc;"><i class="fa fa-search"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control input-sm" placeholder="{{ $lmText('e.g. iPhone 15, Samsung...', 'ឧទាហរណ៍ iPhone 15...') }}">
                </div>
            </div>
            
            <div class="col-md-2 col-sm-6" style="padding: 0 6px 10px 0;">
                <label style="font-size: 11.5px; font-weight: 700; color: #475569;">{{ $lmText('Category', 'ប្រភេទ') }}</label>
                <select name="category" class="form-control input-sm">
                    <option value="">{{ $lmText('-- All Categories --', '-- គ្រប់ប្រភេទ --') }}</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 col-sm-6" style="padding: 0 6px 10px 0;">
                <label style="font-size: 11.5px; font-weight: 700; color: #475569;">{{ $lmText('Brand', 'ម៉ាកយីហោ') }}</label>
                <select name="brand" class="form-control input-sm">
                    <option value="">{{ $lmText('-- All Brands --', '-- គ្រប់ម៉ាក --') }}</option>
                    @foreach($brands as $br)
                        <option value="{{ $br }}" {{ request('brand') === $br ? 'selected' : '' }}>{{ $br }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 col-sm-6" style="padding: 0 6px 10px 0;">
                <label style="font-size: 11.5px; font-weight: 700; color: #475569;">{{ $lmText('Location', 'សាខា') }}</label>
                <select name="location_id" class="form-control input-sm">
                    <option value="">{{ $lmText('-- All Locations --', '-- គ្រប់សាខា --') }}</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ (string) request('location_id') === (string) $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 col-sm-6" style="padding: 0 6px 10px 0;">
                <label style="font-size: 11.5px; font-weight: 700; color: #475569;">{{ $lmText('Stock Status', 'ស្ថានភាពស្តុក') }}</label>
                <select name="stock_status" class="form-control input-sm">
                    <option value="">{{ $lmText('-- All Stock --', '-- ទាំងអស់ --') }}</option>
                    <option value="in_stock" {{ request('stock_status') === 'in_stock' ? 'selected' : '' }}>{{ $lmText('In Stock (> 0)', 'មានក្នុងស្តុក') }}</option>
                    <option value="low_stock" {{ request('stock_status') === 'low_stock' ? 'selected' : '' }}>{{ $lmText('Low Stock (1-5)', 'ស្តុកជិតអស់') }}</option>
                    <option value="out_of_stock" {{ request('stock_status') === 'out_of_stock' ? 'selected' : '' }}>{{ $lmText('Out of Stock (0)', 'អស់ពីស្តុក') }}</option>
                </select>
            </div>

            <div class="col-md-1 col-sm-6" style="padding: 0 0 10px 0; display: flex; align-items: flex-end; gap: 4px;">
                <button type="submit" class="btn btn-primary btn-sm btn-block" style="font-weight: 700; height: 30px;" title="Filter">
                    <i class="fa fa-filter"></i>
                </button>
                @if(request()->hasAny(['search', 'category', 'brand', 'location_id', 'stock_status', 'sort']))
                    <a href="{{ route('loan-management.products.index') }}" class="btn btn-default btn-sm" title="Reset Filters" style="height: 30px;">
                        <i class="fa fa-refresh"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Bulk Action Bar --}}
<form id="bulkActionForm" method="POST" action="{{ route('loan-management.products.bulk-action') }}">
    @csrf
    <div id="bulkBar" class="bulk-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-weight: 800; font-size: 13px;"><i class="fa fa-check-square-o"></i> <span id="selectedCount">0</span> selected</span>
            <select name="bulk_action" id="bulkActionSelect" class="form-control input-sm" style="width: 170px; display: inline-block; background: #334155; color: #fff; border-color: #475569;">
                <option value="">-- Choose Action --</option>
                <option value="delete">Delete Selected</option>
                <option value="stock_in_stock">Set Available (Qty: 1)</option>
                <option value="assign_location">Assign Branch Location</option>
            </select>
            <select name="bulk_location_id" id="bulkLocationSelect" class="form-control input-sm" style="width: 150px; display: none; background: #334155; color: #fff; border-color: #475569;">
                <option value="">None / All Branches</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button type="button" class="btn btn-success btn-sm" onclick="submitBulkAction()" style="font-weight: 700;">
                <i class="fa fa-bolt"></i> Apply Bulk Action
            </button>
            <button type="button" class="btn btn-default btn-sm" onclick="deselectAll()" style="color: #333; margin-left: 6px;">
                Cancel
            </button>
        </div>
    </div>

    {{-- Product List Table --}}
    <div class="box box-primary" style="border-radius: 8px;">
        <div class="box-body table-responsive no-padding">
            <table class="table table-hover table-striped" style="margin-bottom: 0;">
                <thead>
                    <tr style="background: #f8fafc; color: #475569; font-size: 11.5px; text-transform: uppercase;">
                        <th style="width: 35px; text-align: center; vertical-align: middle;">
                            <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)">
                        </th>
                        <th style="width: 55px; text-align: center;">{{ $lmText('Photo', 'រូបភាព') }}</th>
                        <th>{{ $lmText('Product Name & Code', 'ឈ្មោះទំនិញ & កូដ') }}</th>
                        <th>{{ $lmText('Category & Brand', 'ប្រភេទ & ម៉ាក') }}</th>
                        <th>{{ $lmText('Selling Price', 'តម្លៃលក់') }}</th>
                        <th>{{ $lmText('Cost & Margin', 'ថ្លៃដើម & ចំណេញ') }}</th>
                        <th style="text-align: center;">{{ $lmText('Stock Qty', 'ចំនួនស្តុក') }}</th>
                        <th>{{ $lmText('Location', 'សាខា') }}</th>
                        <th style="width: 180px; text-align: right;">{{ $lmText('Actions', 'សកម្មភាព') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                        @php
                            $qty = (int) ($p->qty_available ?? 0);
                            $img = $p->image_url;
                            $brand = $p->brand;
                            $cat = $p->category;
                            $cost = (float) ($p->cost_price ?? 0);
                            $selling = (float) $p->selling_price;
                            $margin = $selling - $cost;
                            $marginPercent = $selling > 0 ? ($margin / $selling) * 100 : 0;
                        @endphp
                        <tr id="productRow-{{ $p->id }}">
                            <td style="text-align: center; vertical-align: middle;">
                                <input type="checkbox" name="selected_ids[]" value="{{ $p->id }}" class="product-checkbox" onchange="updateSelectedCount()">
                            </td>
                            <td style="text-align: center; vertical-align: middle;">
                                @if($img)
                                    <img src="{{ $img }}" alt="{{ $p->name }}" class="product-thumb" onclick="openPhotoModal('{{ $img }}', '{{ addslashes($p->name) }}')">
                                @else
                                    <div class="product-thumb-fallback"><i class="fa fa-cube"></i></div>
                                @endif
                            </td>
                            <td style="vertical-align: middle;">
                                <a href="{{ route('loan-management.products.show', $p->id) }}" style="font-weight: 800; font-size: 13.5px; color: #1e293b; text-decoration: none;">
                                    {{ $p->name }}
                                </a>
                                <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                                    <strong>SKU:</strong> <code>{{ $p->sku ?: '-' }}</code>
                                    @if($p->imei)
                                        · <strong>IMEI:</strong> <span class="text-muted">{{ $p->imei }}</span>
                                    @endif
                                </div>
                            </td>
                            <td style="vertical-align: middle;">
                                @if($cat)
                                    <span class="label label-info" style="font-size: 10.5px; margin-right: 2px;">{{ $cat }}</span>
                                @endif
                                @if($brand)
                                    <span class="label label-default" style="font-size: 10.5px;">{{ $brand }}</span>
                                @endif
                                @if(! $cat && ! $brand)
                                    <span class="text-muted" style="font-size: 12px;">-</span>
                                @endif
                            </td>
                            <td style="vertical-align: middle;">
                                <span style="font-size: 14.5px; font-weight: 800; color: #0f172a;">${{ number_format($selling, 2) }}</span>
                                @if($p->min_down_payment_percent > 0)
                                    <div style="font-size: 11px; color: #d97706; font-weight: 700;">
                                        Min DP: {{ $p->min_down_payment_percent }}% (${{ number_format($selling * ($p->min_down_payment_percent / 100), 2) }})
                                    </div>
                                @endif
                            </td>
                            <td style="vertical-align: middle; font-size: 12px;">
                                <div style="color: #64748b;">${{ number_format($cost, 2) }}</div>
                                <span class="label {{ $margin >= 0 ? 'label-success' : 'label-danger' }}" style="font-size: 10px;">
                                    +${{ number_format($margin, 2) }} ({{ number_format($marginPercent, 1) }}%)
                                </span>
                            </td>
                            <td style="text-align: center; vertical-align: middle;">
                                <div style="margin-bottom: 4px;">
                                    <span id="stockBadge-{{ $p->id }}" class="stock-badge {{ $qty > 5 ? 'in-stock' : ($qty > 0 ? 'low-stock' : 'out-of-stock') }}">
                                        <i class="fa {{ $qty > 5 ? 'fa-check' : ($qty > 0 ? 'fa-exclamation-triangle' : 'fa-times-circle') }}"></i>
                                        <span id="stockBadgeText-{{ $p->id }}">{{ $qty > 5 ? "{$qty} units" : ($qty > 0 ? "{$qty} low" : '0 Out') }}</span>
                                    </span>
                                </div>
                                {{-- Quick Stepper --}}
                                <div class="quick-qty-stepper" title="Click to adjust quantity">
                                    <button type="button" class="quick-qty-btn" onclick="adjustStock({{ $p->id }}, -1)">-</button>
                                    <span class="quick-qty-val" id="qtyVal-{{ $p->id }}" onclick="promptExactStock({{ $p->id }}, {{ $qty }})">{{ $qty }}</span>
                                    <button type="button" class="quick-qty-btn" onclick="adjustStock({{ $p->id }}, 1)">+</button>
                                </div>
                            </td>
                            <td style="vertical-align: middle; font-size: 12px; color: #475569;">
                                <i class="fa fa-map-marker text-muted" style="margin-right: 3px;"></i>
                                {{ $p->location->name ?? $lmText('All Branches', 'គ្រប់សាខា') }}
                            </td>
                            <td style="text-align: right; vertical-align: middle;">
                                <div class="btn-group" style="display: inline-flex; gap: 3px;">
                                    {{-- Create Installment for this product --}}
                                    <a href="{{ route('loan-management.loans.create', ['product_id' => $p->id, 'product_name' => $p->name, 'principal_amount' => $p->selling_price]) }}" class="btn btn-xs btn-success" title="{{ $lmText('Create Installment for this Product', 'បង្កើតកម្ចីសម្រាប់ទំនិញនេះ') }}">
                                        <i class="fa fa-plus-circle"></i>
                                    </a>
                                    {{-- Simulator Modal trigger --}}
                                    <button type="button" class="btn btn-xs btn-info" onclick="openSimulatorModal({{ $p->id }})" title="{{ $lmText('Instant Installment Calculator', 'គណនាបង់រំលស់រហ័ស') }}">
                                        <i class="fa fa-calculator"></i>
                                    </button>
                                    {{-- Show details --}}
                                    <a href="{{ route('loan-management.products.show', $p->id) }}" class="btn btn-xs btn-default" title="{{ $lmText('View Details', 'មើលព័ត៌មាន') }}">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    {{-- Edit --}}
                                    <a href="{{ route('loan-management.products.edit', $p->id) }}" class="btn btn-xs btn-primary" title="{{ $lmText('Edit Product', 'កែប្រែ') }}">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                    {{-- Delete --}}
                                    <button type="button" class="btn btn-xs btn-danger" onclick="deleteSingleProduct({{ $p->id }}, '{{ addslashes($p->name) }}')" title="{{ $lmText('Delete Product', 'លុប') }}">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 40px 20px;">
                                <div style="font-size: 44px; color: #cbd5e1; margin-bottom: 12px;"><i class="fa fa-cube"></i></div>
                                <h4 style="margin: 0 0 6px; font-weight: 800; color: #334155;">{{ $lmText('No installment products found', 'មិនមានទំនិញបង់រំលស់នៅឡើយទេ') }}</h4>
                                <p style="color: #64748b; font-size: 13px; margin-bottom: 16px;">{{ $lmText('Add your available phones, electronics, motorcycles or goods for installment loans.', 'បន្ថែមទំនិញដូចជា ទូរស័ព្ទ គ្រឿងអេឡិចត្រូនិក ម៉ូតូ សម្រាប់អតិថិជនស្នើសុំបង់រំលស់។') }}</p>
                                <a href="{{ route('loan-management.products.create') }}" class="btn btn-primary" style="font-weight: 700; border-radius: 6px;">
                                    <i class="fa fa-plus-circle"></i> {{ $lmText('Add First Installment Product', 'បន្ថែមទំនិញដំបូង') }}
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="box-footer clearfix" style="border-top: 1px solid #f1f5f9;">
                <div class="pull-left" style="padding-top: 8px; color: #64748b; font-size: 13px;">
                    Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} products
                </div>
                <div class="pull-right">
                    {{ $products->links() }}
                </div>
            </div>
        @endif
    </div>
</form>

{{-- Hidden Single Delete Form --}}
<form id="singleDeleteForm" method="POST" action="" style="display:none;">
    @csrf
    @method('DELETE')
</form>

{{-- Installment Calculator Modal --}}
<div class="modal fade" id="simulatorModal" tabindex="-1" role="dialog" aria-labelledby="simulatorModalLabel">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content" style="border-radius: 10px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
            <div class="modal-header" style="background: #1e293b; color: #fff;">
                <button type="button" class="close" data-dismiss="modal" style="color: #fff; opacity: .8;">&times;</button>
                <h4 class="modal-title" id="simulatorModalLabel" style="font-weight: 800; font-size: 16px;">
                    <i class="fa fa-calculator text-primary"></i> <span id="modalProductName">Installment Calculator</span>
                </h4>
            </div>
            <div class="modal-body" style="padding: 20px; background: #fff;">
                <div style="display: flex; gap: 14px; align-items: center; margin-bottom: 16px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
                    <img id="modalProductImg" src="" alt="" style="width: 56px; height: 56px; border-radius: 8px; object-fit: cover; border: 1px solid #e2e8f0; display: none;">
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700;" id="modalProductSku"></div>
                        <div style="font-size: 22px; font-weight: 800; color: #0f172a;" id="modalProductPrice">$0.00</div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label style="font-weight: 700; font-size: 12px;">Down Payment ($)</label>
                            <input type="number" id="modalDownPayment" class="form-control input-sm" value="0" min="0" step="1" oninput="recalcModalSim()">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label style="font-weight: 700; font-size: 12px;">Monthly Interest Rate (%)</label>
                            <input type="number" id="modalInterestRate" class="form-control input-sm" value="1.5" min="0" max="100" step="0.1" oninput="recalcModalSim()">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label style="font-weight: 700; font-size: 12px; display: block;">Select Loan Term</label>
                    <div id="modalTermPills">
                        <span class="sim-pill" onclick="selectModalTerm(3, this)">3 Months</span>
                        <span class="sim-pill" onclick="selectModalTerm(6, this)">6 Months</span>
                        <span class="sim-pill active" onclick="selectModalTerm(12, this)">12 Months</span>
                        <span class="sim-pill" onclick="selectModalTerm(18, this)">18 Months</span>
                        <span class="sim-pill" onclick="selectModalTerm(24, this)">24 Months</span>
                    </div>
                </div>

                <div class="sim-card">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px;">
                        <span style="color: #64748b;">Financed Principal:</span>
                        <strong id="simFinancedAmount" style="color: #0f172a;">$0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px;">
                        <span style="color: #64748b;">Total Interest:</span>
                        <strong id="simTotalInterest" style="color: #d97706;">$0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px;">
                        <span style="color: #64748b;">Total Repayment:</span>
                        <strong id="simTotalPayable" style="color: #0f172a;">$0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-top: 8px; border-top: 1px dashed #cbd5e1; font-size: 16px; font-weight: 800;">
                        <span style="color: #2563eb;">Estimated Monthly:</span>
                        <span id="simMonthlyAmount" style="color: #2563eb; font-size: 20px;">$0.00 / mo</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <a id="modalCreateLoanBtn" href="#" class="btn btn-success" style="font-weight: 700;">
                    <i class="fa fa-arrow-right"></i> Proceed to Create Loan
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Photo Lightbox Modal --}}
<div class="modal fade" id="photoModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document" style="margin-top: 80px;">
        <div class="modal-content" style="border-radius: 10px; overflow: hidden; text-align: center; background: #0f172a; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.5);">
            <div class="modal-body" style="padding: 10px;">
                <img id="lightboxImg" src="" alt="" style="max-width: 100%; max-height: 400px; border-radius: 6px;">
                <div id="lightboxCaption" style="color: #fff; font-weight: 700; margin-top: 8px; font-size: 13px;"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('loan_js')
<script>
    var currentSimProduct = null;
    var currentSelectedMonths = 12;

    // Ensure modals are appended to <body> so they escape any parent overflow / stacking context
    $(document).ready(function() {
        $('#simulatorModal').appendTo('body');
        $('#photoModal').appendTo('body');
    });

    // Checkbox and Bulk Actions
    function toggleSelectAll(master) {
        var checkboxes = document.querySelectorAll('.product-checkbox');
        checkboxes.forEach(function(cb) {
            cb.checked = master.checked;
        });
        updateSelectedCount();
    }

    function deselectAll() {
        var master = document.getElementById('selectAllCheckbox');
        if (master) master.checked = false;
        var checkboxes = document.querySelectorAll('.product-checkbox');
        checkboxes.forEach(function(cb) { cb.checked = false; });
        updateSelectedCount();
    }

    function updateSelectedCount() {
        var checked = document.querySelectorAll('.product-checkbox:checked');
        var count = checked.length;
        var countSpan = document.getElementById('selectedCount');
        var bulkBar = document.getElementById('bulkBar');
        if (countSpan) countSpan.textContent = count;
        if (bulkBar) bulkBar.style.display = count > 0 ? 'flex' : 'none';
    }

    var bulkSelectEl = document.getElementById('bulkActionSelect');
    if (bulkSelectEl) {
        bulkSelectEl.addEventListener('change', function() {
            var locSelect = document.getElementById('bulkLocationSelect');
            if (this.value === 'assign_location') {
                locSelect.style.display = 'inline-block';
            } else {
                locSelect.style.display = 'none';
            }
        });
    }

    function submitBulkAction() {
        var action = document.getElementById('bulkActionSelect').value;
        if (!action) {
            alert('Please select an action to perform.');
            return;
        }
        var count = document.querySelectorAll('.product-checkbox:checked').length;
        if (count === 0) {
            alert('Please select at least one product.');
            return;
        }
        if (action === 'delete') {
            if (!confirm('Are you sure you want to delete ' + count + ' selected product(s)? Products with active loans will be protected.')) {
                return;
            }
        }
        document.getElementById('bulkActionForm').submit();
    }

    // Single Delete
    function deleteSingleProduct(id, name) {
        if (confirm('Are you sure you want to delete "' + name + '"?')) {
            var form = document.getElementById('singleDeleteForm');
            form.action = '/loan-management/products/' + id;
            form.submit();
        }
    }

    // Quick Live Stock Adjust
    function adjustStock(productId, delta) {
        fetch('/loan-management/products/' + productId + '/stock-adjust', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ change_qty: delta })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                var valEl = document.getElementById('qtyVal-' + productId);
                if (valEl) valEl.textContent = data.new_qty;

                var badgeEl = document.getElementById('stockBadge-' + productId);
                var textEl = document.getElementById('stockBadgeText-' + productId);
                if (badgeEl && textEl) {
                    badgeEl.className = 'stock-badge ' + data.status_class;
                    textEl.textContent = data.status_text;
                    var iconEl = badgeEl.querySelector('i');
                    if (iconEl) {
                        iconEl.className = 'fa ' + (data.status_class === 'in-stock' ? 'fa-check' : (data.status_class === 'low-stock' ? 'fa-exclamation-triangle' : 'fa-times-circle'));
                    }
                }
            }
        })
        .catch(function(err) {
            console.error(err);
            alert('Failed to update stock.');
        });
    }

    function promptExactStock(productId, currentVal) {
        var input = prompt('Enter exact stock quantity for this product:', currentVal);
        if (input === null) return;
        var newQty = parseInt(input, 10);
        if (isNaN(newQty) || newQty < 0) {
            alert('Please enter a valid positive number.');
            return;
        }

        fetch('/loan-management/products/' + productId + '/stock-adjust', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ set_qty: newQty })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                var valEl = document.getElementById('qtyVal-' + productId);
                if (valEl) valEl.textContent = data.new_qty;

                var badgeEl = document.getElementById('stockBadge-' + productId);
                var textEl = document.getElementById('stockBadgeText-' + productId);
                if (badgeEl && textEl) {
                    badgeEl.className = 'stock-badge ' + data.status_class;
                    textEl.textContent = data.status_text;
                }
            }
        })
        .catch(function(err) {
            console.error(err);
        });
    }

    // Photo Lightbox
    function openPhotoModal(imgUrl, caption) {
        $('#photoModal').appendTo('body');
        document.getElementById('lightboxImg').src = imgUrl;
        document.getElementById('lightboxCaption').textContent = caption;
        $('#photoModal').modal('show');
    }

    // Interactive Simulator Modal
    function openSimulatorModal(productId) {
        $('#simulatorModal').appendTo('body');
        fetch('/loan-management/products/' + productId + '/calculator-data')
            .then(function(res) { return res.json(); })
            .then(function(product) {
                currentSimProduct = product;
                document.getElementById('modalProductName').textContent = product.name;
                document.getElementById('modalProductSku').textContent = 'SKU: ' + (product.sku || '-');
                document.getElementById('modalProductPrice').textContent = '$' + Number(product.selling_price).toFixed(2);
                
                var imgEl = document.getElementById('modalProductImg');
                if (product.image_url) {
                    imgEl.src = product.image_url;
                    imgEl.style.display = 'block';
                } else {
                    imgEl.style.display = 'none';
                }

                var defaultDp = 0;
                if (product.min_down_payment_percent > 0) {
                    defaultDp = Math.round(product.selling_price * (product.min_down_payment_percent / 100));
                }
                document.getElementById('modalDownPayment').value = defaultDp;
                document.getElementById('modalDownPayment').max = product.selling_price;
                document.getElementById('modalCreateLoanBtn').href = product.create_loan_url;

                recalcModalSim();
                $('#simulatorModal').modal('show');
            })
            .catch(function(err) {
                console.error(err);
                alert('Could not load calculator data.');
            });
    }

    function selectModalTerm(months, pillEl) {
        currentSelectedMonths = months;
        var pills = document.querySelectorAll('#modalTermPills .sim-pill');
        pills.forEach(function(p) { p.classList.remove('active'); });
        pillEl.classList.add('active');
        recalcModalSim();
    }

    function recalcModalSim() {
        if (!currentSimProduct) return;
        var price = parseFloat(currentSimProduct.selling_price) || 0;
        var dp = parseFloat(document.getElementById('modalDownPayment').value) || 0;
        var rate = (parseFloat(document.getElementById('modalInterestRate').value) || 0) / 100;
        var months = currentSelectedMonths || 12;

        var financed = Math.max(0, price - dp);
        var monthlyPrincipal = financed / months;
        var monthlyInterest = financed * rate;
        var monthlyTotal = monthlyPrincipal + monthlyInterest;
        var totalInterest = monthlyInterest * months;
        var totalPayable = financed + totalInterest;

        document.getElementById('simFinancedAmount').textContent = '$' + financed.toFixed(2);
        document.getElementById('simTotalInterest').textContent = '$' + totalInterest.toFixed(2);
        document.getElementById('simTotalPayable').textContent = '$' + totalPayable.toFixed(2);
        document.getElementById('simMonthlyAmount').textContent = '$' + monthlyTotal.toFixed(2) + ' / mo';

        // Update Create Loan URL with down payment and term
        var baseUrl = currentSimProduct.create_loan_url;
        var updatedUrl = baseUrl + '&down_payment=' + dp + '&duration_months=' + months + '&interest_rate=' + (rate * 100);
        document.getElementById('modalCreateLoanBtn').href = updatedUrl;
    }
</script>
@endsection
