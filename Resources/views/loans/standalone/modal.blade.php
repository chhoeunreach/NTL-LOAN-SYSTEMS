@php
    $loanLanguage = session('user.language', config('app.locale'));
    $lmIsKhmer = $loanLanguage === 'km';
    $lmText = fn ($en, $km) => $lmIsKhmer ? $km : $en;
@endphp

<div class="lm-pro-loan-modal" style="font-family: 'Kantumruy Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f1f5f9; color: #1e293b; display: flex; flex-direction: column; max-height: calc(100vh - 24px); overflow: hidden;">
    <style>
        .lm-pro-loan-modal *, .lm-pro-loan-modal *::before, .lm-pro-loan-modal *::after { box-sizing: border-box; }
        
        /* Modal Compact Header */
        .lm-pro-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #fff;
            padding: 8px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 10;
        }
        .lm-pro-header-left { display: flex; align-items: center; gap: 10px; }
        .lm-pro-header-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(37, 99, 235, 0.2);
            border: 1px solid rgba(96, 165, 250, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            color: #60a5fa;
        }
        .lm-pro-header-title { font-size: 15px; font-weight: 700; margin: 0; color: #f8fafc; display: flex; align-items: center; gap: 8px; }
        .lm-pro-header-sub { font-size: 11px; color: #94a3b8; margin: 1px 0 0; }
        .lm-pro-badge {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(52, 211, 153, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .lm-pro-header-right { display: flex; align-items: center; gap: 8px; }
        .lm-pro-btn-calc {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.1);
            color: #e2e8f0;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.15);
            text-decoration: none;
            transition: all 0.15s;
        }
        .lm-pro-btn-calc:hover { background: rgba(255, 255, 255, 0.2); color: #fff; text-decoration: none; }
        .lm-pro-close {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: #cbd5e1;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s;
        }
        .lm-pro-close:hover { background: #ef4444; color: #fff; transform: scale(1.05); }

        /* Scrollable Workspace */
        .lm-pro-body {
            flex: 1;
            overflow-y: auto;
            padding: 10px 14px;
            background: #f1f5f9;
        }

        /* Top Settings Bar (Compact Single Row) */
        .lm-top-strip {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 8px 12px;
            margin-bottom: 10px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
            display: grid;
            grid-template-columns: 150px 150px 1fr 1fr 1.4fr;
            gap: 8px;
            align-items: center;
        }

        /* Responsive 2-Column Dense Grid */
        .lm-grid-workspace {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 10px;
            align-items: start;
        }
        .lm-col { display: flex; flex-direction: column; gap: 10px; }

        /* Compact Cards */
        .lm-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
            overflow: hidden;
        }
        .lm-card-head {
            padding: 6px 12px;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .lm-card-title {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .lm-card-title i { color: #2563eb; font-size: 13px; }
        .lm-card-body { padding: 10px 12px; }

        /* Form Controls */
        .lm-field { margin-bottom: 7px; }
        .lm-field:last-child { margin-bottom: 0; }
        .lm-label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            line-height: 1.2;
        }
        .lm-req { color: #ef4444; margin-left: 2px; }
        .lm-control {
            width: 100%;
            height: 31px;
            padding: 0 8px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: 12px;
            color: #1e293b;
            background: #fff;
            transition: all 0.15s;
        }
        .lm-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.12);
        }
        select.lm-control {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            padding-right: 24px;
            -webkit-appearance: none;
            appearance: none;
        }
        .lm-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .lm-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
        .lm-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }

        /* Customer Select & KYC Strip (Compact) */
        .lm-customer-search-box {
            display: flex;
            gap: 6px;
            align-items: center;
            margin-bottom: 8px;
            background: #f8fafc;
            padding: 6px 8px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .lm-customer-search-box .select2-container { flex: 1; min-width: 0; }
        .lm-customer-search-box .select2-selection {
            border-radius: 6px !important;
            min-height: 32px !important;
            border-color: #cbd5e1 !important;
            padding-top: 2px;
        }

        /* Smart KYC Strip (Profile + ID Card) */
        .lm-kyc-strip {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px;
        }
        .lm-kyc-box {
            background: #fff;
            border-radius: 8px;
            border: 1px dashed #cbd5e1;
            padding: 6px 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
        }
        .lm-kyc-thumb {
            width: 44px;
            height: 44px;
            border-radius: 6px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 18px;
            overflow: hidden;
            flex-shrink: 0;
            position: relative;
        }
        .lm-kyc-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .lm-kyc-details { flex: 1; min-width: 0; }
        .lm-kyc-title { font-size: 10px; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 2px; }
        .lm-kyc-btn {
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 10px;
            font-weight: 600;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #2563eb;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
        }
        .lm-kyc-btn:hover { background: #eff6ff; border-color: #2563eb; }
        .lm-kyc-remove {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #fee2e2;
            color: #ef4444;
            border: 1px solid #fca5a5;
            font-size: 9px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        /* Product Collateral Item (Compact) */
        .mob-product-item {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            margin-bottom: 8px;
            position: relative;
        }
        .mob-product-ocr-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 6px;
            padding-bottom: 6px;
            border-bottom: 1px solid #f1f5f9;
        }
        .mob-prod-num {
            font-size: 11px;
            font-weight: 800;
            color: #2563eb;
            background: #eff6ff;
            padding: 2px 8px;
            border-radius: 999px;
            border: 1px solid #bfdbfe;
        }
        .mob-product-actions { display: flex; align-items: center; gap: 6px; }
        .mob-product-photo-btn {
            padding: 3px 8px;
            border-radius: 6px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            font-size: 10px;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s;
        }
        .mob-product-photo-btn:hover { background: #eff6ff; border-color: #2563eb; color: #2563eb; }
        .mob-prod-del {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #ef4444;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 10px;
        }
        .mob-product-fields {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
        }
        .mob-product-fields .wide { grid-column: span 2; }
        .mob-product-fields .mob-field { margin-bottom: 0; }
        .mob-product-fields .mob-input {
            width: 100%;
            height: 28px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            padding: 0 8px;
            font-size: 12px;
        }
        .mob-product-fields .mob-field label {
            display: block;
            font-size: 9px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .mob-product-total {
            grid-column: span 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 4px 8px;
            height: 28px;
            margin-top: 14px;
        }
        .modal-item-total { font-weight: 800; color: #059669; font-size: 12px; }
        .mob-add-product {
            width: 100%;
            padding: 7px;
            border: 2px dashed #93c5fd;
            border-radius: 8px;
            background: #eff6ff;
            color: #2563eb;
            font-size: 11px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.15s;
        }
        .mob-add-product:hover { background: #dbeafe; border-color: #3b82f6; }

        /* Deposit / Down Payment Switch (Compact) */
        .lm-deposit-box {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 10px;
            margin-top: 8px;
        }
        .lm-switch-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            user-select: none;
        }
        .lm-switch-pill {
            width: 36px;
            height: 20px;
            border-radius: 10px;
            background: #cbd5e1;
            position: relative;
            transition: background 0.2s;
        }
        .lm-switch-pill::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s;
        }
        .lm-switch-row.on .lm-switch-pill { background: #10b981; }
        .lm-switch-row.on .lm-switch-pill::after { transform: translateX(16px); }
        .mob-deposit-fields {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr 1fr;
            gap: 6px;
            margin-top: 6px;
            background: #f8fafc;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        /* Financial Metrics Grid (Compact) */
        .lm-metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-top: 8px;
        }
        .lm-metric-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 8px;
            text-align: center;
        }
        .lm-metric-card.highlight { background: #eff6ff; border-color: #bfdbfe; }
        .lm-metric-card.success { background: #ecfdf5; border-color: #a7f3d0; }
        .lm-metric-label { font-size: 9px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 2px; }
        .lm-metric-val { font-size: 13px; font-weight: 800; color: #0f172a; }
        .lm-metric-card.highlight .lm-metric-val { color: #2563eb; }
        .lm-metric-card.success .lm-metric-val { color: #059669; }

        /* Documents Grid (Compact) */
        .mob-doc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(68px, 1fr));
            gap: 6px;
            margin-top: 6px;
        }
        .mob-doc-thumb {
            aspect-ratio: 1;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .mob-doc-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .mob-doc-thumb .mob-doc-icon { text-align: center; color: #64748b; font-size: 9px; padding: 2px; }
        .mob-doc-thumb .mob-doc-icon i { font-size: 18px; display: block; margin-bottom: 2px; }
        .mob-doc-thumb .mob-doc-remove {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: rgba(239, 68, 68, 0.9);
            color: #fff;
            border: none;
            font-size: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .mob-doc-add {
            aspect-ratio: 1;
            border: 2px dashed #cbd5e1;
            border-radius: 6px;
            background: #f8fafc;
            color: #64748b;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s;
        }
        .mob-doc-add:hover { border-color: #2563eb; color: #2563eb; background: #eff6ff; }

        /* Schedule Table (Compact) */
        .mob-schedule-wrap {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            margin-top: 8px;
        }
        .mob-schedule-tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            background: #fff;
        }
        .mob-schedule-tbl th {
            background: #f8fafc;
            padding: 5px 8px;
            font-weight: 700;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 2;
        }
        .mob-schedule-tbl td { padding: 4px 8px; border-bottom: 1px solid #f1f5f9; }
        .mob-schedule-tbl tfoot th { background: #f8fafc; font-weight: 800; border-top: 1px solid #cbd5e1; }

        /* Slim Sticky Action Footer */
        .lm-pro-footer {
            background: #fff;
            border-top: 1px solid #e2e8f0;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.03);
            position: relative;
            z-index: 10;
        }
        .lm-pro-footer-info { display: flex; align-items: center; gap: 10px; font-size: 12px; color: #475569; }
        .lm-pro-footer-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 11px;
        }
        .lm-pro-footer-pill strong { color: #0f172a; }
        .lm-pro-footer-actions { display: flex; align-items: center; gap: 8px; }
        
        .lm-btn {
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s;
        }
        .lm-btn-outline { background: #fff; border-color: #cbd5e1; color: #475569; }
        .lm-btn-outline:hover { background: #f8fafc; border-color: #94a3b8; color: #0f172a; }
        .lm-btn-secondary { background: #eff6ff; border-color: #bfdbfe; color: #2563eb; }
        .lm-btn-secondary:hover { background: #dbeafe; }
        .lm-btn-warning { background: #fffbeb; border-color: #fde68a; color: #b45309; }
        .lm-btn-warning:hover { background: #fef3c7; }
        .lm-btn-primary {
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: #fff;
            box-shadow: 0 2px 6px rgba(22, 163, 74, 0.25);
        }
        .lm-btn-primary:hover { background: linear-gradient(135deg, #15803d, #166534); transform: translateY(-1px); }

        /* Photo Sheet & Crop Overlays (Full-screen high-elevation overlays) */
        .mob-photo-sheet {
            position: fixed; inset: 0; z-index: 99998; background: rgba(15, 23, 42, 0.75);
            backdrop-filter: blur(4px);
            display: none; align-items: flex-end; justify-content: center;
        }
        .mob-photo-sheet.is-open { display: flex; }
        .mob-photo-sheet-panel {
            background: #fff; border-radius: 20px 20px 0 0; width: 100%; max-width: 440px; padding: 22px;
            box-shadow: 0 -20px 40px rgba(0, 0, 0, 0.2);
            animation: mobSheetUp 0.2s ease-out;
        }
        .mob-photo-sheet-title { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 14px; text-align: center; }
        .mob-photo-sheet-option {
            width: 100%; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; background: #f8fafc;
            font-size: 13px; font-weight: 600; color: #334155; display: flex; align-items: center; justify-content: center; gap: 8px;
            margin-bottom: 8px; cursor: pointer; transition: all 0.15s;
        }
        .mob-photo-sheet-option:hover { background: #eff6ff; border-color: #bfdbfe; color: #2563eb; }
        .mob-photo-sheet-cancel {
            width: 100%; padding: 11px; border-radius: 10px; border: none; background: #f1f5f9; color: #ef4444;
            font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.15s; margin-top: 4px;
        }
        .mob-photo-sheet-cancel:hover { background: #fee2e2; }

        .mob-product-crop-overlay {
            position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(6px);
            display: none; align-items: center; justify-content: center; padding: 20px;
        }
        .mob-product-crop-box {
            background: #fff; border-radius: 16px; width: 100%; max-width: 760px; max-height: 92vh; overflow: auto; padding: 20px 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            animation: wizPopIn 0.2s ease-out;
        }
        .mob-product-crop-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid #e2e8f0; }
        .mob-product-crop-title { font-size: 16px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .mob-product-crop-canvas { display: block; width: 100%; max-height: 55vh; border-radius: 10px; background: #0f172a; touch-action: none; border: 1px solid #334155; }
        .mob-product-crop-actions { display: flex; align-items: center; justify-content: flex-end; gap: 10px; margin-top: 14px; }
        .mob-product-crop-actions button {
            padding: 9px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: #f8fafc; font-size: 13px; font-weight: 700; color: #334155; cursor: pointer; transition: all 0.15s;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .mob-product-crop-actions button:hover { background: #e2e8f0; color: #0f172a; }
        .mob-product-crop-actions button.primary { background: #2563eb; color: #fff; border-color: #2563eb; }
        .mob-product-crop-actions button.primary:hover { background: #1d4ed8; }

        @keyframes mobSheetUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        @keyframes wizPopIn {
            from { opacity: 0; transform: scale(0.96); }
            to { opacity: 1; transform: scale(1); }
        }

        @media (max-width: 991px) {
            .lm-top-strip { grid-template-columns: 1fr 1fr; }
            .lm-grid-workspace { grid-template-columns: 1fr; }
            .lm-metrics-grid { grid-template-columns: repeat(2, 1fr); }
            .lm-pro-footer { flex-direction: column; gap: 12px; align-items: stretch; }
            .lm-pro-footer-actions { justify-content: flex-end; flex-wrap: wrap; }
        }
    </style>

    <!-- Modal Header -->
    <div class="lm-pro-header">
        <div class="lm-pro-header-left">
            <div class="lm-pro-header-icon">
                <i class="fa fa-file-text-o"></i>
            </div>
            <div>
                <h2 class="lm-pro-header-title">
                    {{ $lmText('Create New Installment Agreement', 'បង្កើតកិច្ចសន្យាកម្ចីរំលស់ថ្មី') }}
                    <span class="lm-pro-badge">{{ $lmText('Auto Ref', 'លេខស្វ័យប្រវត្តិ') }}</span>
                </h2>
                <p class="lm-pro-header-sub">{{ $lmText('Quick installment agreement with OCR ID Card, Customer KYC & Collateral Recognition', 'បង្កើតកម្ចីរហ័ស ជាមួយការស្កេនអត្តសញ្ញាណប័ណ្ណ រូបថតអតិថិជន និងទំនិញស្វ័យប្រវត្តិ') }}</p>
            </div>
        </div>
        <div class="lm-pro-header-right">
            @if(Route::has('loan-management.loans.calculator'))
                <a href="{{ route('loan-management.loans.calculator') }}" target="_blank" class="lm-pro-btn-calc" title="Open Installment Calculator">
                    <i class="fa fa-calculator"></i> {{ $lmText('Calculator', 'ម៉ាស៊ីនគណនា') }}
                </a>
            @endif
            <button type="button" class="lm-pro-close" data-dismiss="modal" aria-label="Close" title="Close modal">&times;</button>
        </div>
    </div>

    <!-- Main Scrollable Form Body -->
    <form id="standaloneLoanModalForm" method="POST" action="{{ route('loan-management.loans.store-standalone') }}" style="flex: 1; display: flex; flex-direction: column; overflow: hidden; margin: 0;">
        @csrf
        <input type="hidden" name="action_type" value="create_approve">

        <div class="lm-pro-body">
            <!-- Top Agreement Configuration Strip -->
            <div class="lm-top-strip">
                <div class="lm-field">
                    <label class="lm-label">{{ $lmText('Agreement #', 'លេខកិច្ចសន្យា') }}</label>
                    <input type="text" name="loan_number" class="lm-control" placeholder="{{ $lmText('Auto-generated', 'ស្វ័យប្រវត្តិ') }}" style="font-weight: 700; color: #2563eb;">
                </div>
                <div class="lm-field">
                    <label class="lm-label">{{ $lmText('Agreement Date', 'កាលបរិច្ឆេទ') }} <span class="lm-req">*</span></label>
                    <input type="date" name="loan_date" class="lm-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="lm-field">
                    <label class="lm-label">{{ $lmText('Business Location', 'ទីតាំងសាខា') }}</label>
                    <select name="business_location_id" class="lm-control">
                        <option value="">-- {{ $lmText('Select Location', 'ជ្រើសរើសសាខា') }} --</option>
                        @foreach($locations as $id => $name)
                            <option value="{{ $id }}" {{ (string) $id === (string) ($defaultLocationId ?? '') ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lm-field">
                    <label class="lm-label">{{ $lmText('Assigned Collector', 'បុគ្គលិកប្រមូលប្រាក់') }}</label>
                    <select name="assigned_collector_id" class="lm-control">
                        <option value="">-- {{ $lmText('Select Staff', 'ជ្រើសរើសបុគ្គលិក') }} --</option>
                        @foreach($collectors as $c)
                            <option value="{{ $c->id }}" {{ (string) $c->id === (string) ($defaultCollectorId ?? '') ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lm-field">
                    <label class="lm-label">{{ $lmText('Agreement Note / Memo', 'ចំណាំកិច្ចសន្យា') }}</label>
                    <input type="text" name="note" class="lm-control" placeholder="{{ $lmText('Optional note or agreement remark', 'កំណត់ចំណាំផ្សេងៗ...') }}">
                </div>
                <input type="hidden" name="currency" value="USD">
                <input type="hidden" name="exchange_rate" value="1">
            </div>

            <!-- Two-Column Responsive Workspace -->
            <div class="lm-grid-workspace">
                <!-- LEFT COLUMN: Customer KYC, ID OCR & Documents -->
                <div class="lm-col">
                    <!-- Customer Information Card -->
                    <div class="lm-card" id="mobCustomerInfoCard">
                        <div class="lm-card-head">
                            <h3 class="lm-card-title"><i class="fa fa-user-circle"></i> {{ $lmText('Customer KYC & Identity', 'ព័ត៌មានអតិថិជន & អត្តសញ្ញាណ') }}</h3>
                            <button type="button" class="btn btn-xs btn-default" id="modalClearCustomer" title="{{ $lmText('Clear Customer', 'ជម្រះអតិថិជន') }}">
                                <i class="fa fa-refresh"></i> {{ $lmText('Clear', 'ជម្រះ') }}
                            </button>
                        </div>
                        <div class="lm-card-body">
                            <input type="hidden" name="customer_id" id="modalCustomerId" value="">

                            <!-- Quick Search Existing Customer & Quick Add -->
                            <div class="lm-customer-search-box">
                                <div style="flex: 1;">
                                    <select id="modalCustomerSelect" class="form-control" style="width: 100%;">
                                        <option value="">{{ $lmText('Search existing customer by Name, Phone, or ID...', 'ស្វែងរកអតិថិជនចាស់ តាមឈ្មោះ លេខទូរស័ព្ទ ឬអត្តសញ្ញាណប័ណ្ណ...') }}</option>
                                    </select>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target=".contact_modal" style="height: 38px; border-radius: 8px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="fa fa-plus"></i> <span>{{ $lmText('Quick Add', 'បង្កើតថ្មី') }}</span>
                                </button>
                            </div>

                            <!-- Smart KYC Strip (Portrait Photo & National ID Scanner) -->
                            <div class="lm-kyc-strip">
                                <!-- Profile Photo Box -->
                                <div class="lm-kyc-box">
                                    <div class="lm-kyc-thumb" id="mobCustomerPhotoPreview">
                                        <i class="fa fa-user"></i>
                                    </div>
                                    <div class="lm-kyc-details">
                                        <div class="lm-kyc-title">{{ $lmText('Customer Photo', 'រូបថតអតិថិជន') }}</div>
                                        <button type="button" class="lm-kyc-btn" onclick="mobOpenPhotoSheet('profile')">
                                            <i class="fa fa-camera"></i> {{ $lmText('Capture/Upload', 'ថត / ផ្ទុកឡើង') }}
                                        </button>
                                        <input type="file" id="mobCustomerPhotoCamera" accept="image/*" capture="user" style="display:none;" onchange="mobHandleCustomerProfile(this)">
                                        <input type="file" id="mobCustomerPhotoGallery" accept="image/*" style="display:none;" onchange="mobHandleCustomerProfile(this)">
                                    </div>
                                </div>

                                <!-- ID Card OCR Box -->
                                <div class="lm-kyc-box">
                                    <div class="lm-kyc-thumb" id="mobIdCardPreview" style="border-radius: 6px;">
                                        <img id="mobIdCardImg" src="" style="display:none;">
                                        <i class="fa fa-id-card" id="mobIdCardPlaceholderIcon"></i>
                                    </div>
                                    <div class="lm-kyc-details">
                                        <div class="lm-kyc-title">{{ $lmText('National ID OCR', 'ស្កេនអត្តសញ្ញាណប័ណ្ណ') }}</div>
                                        <button type="button" class="lm-kyc-btn" onclick="mobOpenPhotoSheet('id_card')">
                                            <i class="fa fa-camera"></i> {{ $lmText('Scan ID Card', 'ស្កេនកាត') }}
                                        </button>
                                        <input type="file" id="mobIdCardCamera" accept="image/*" capture="environment" style="display:none;" onchange="mobHandleIdCard(this)">
                                        <input type="file" id="mobIdCardGallery" accept="image/*" style="display:none;" onchange="mobHandleIdCard(this)">
                                        <div id="mobIdCardOcrStatus" style="font-size: 10px; color: #2563eb; font-weight: 600; margin-top: 4px;"></div>
                                    </div>
                                    <input type="hidden" name="id_card_ocr_raw_text" id="mobIdCardOcrRawText">
                                    <input type="hidden" name="id_card_ocr_fields[id_card_number]" id="mobIdCardOcrNumber">
                                    <input type="hidden" name="id_card_ocr_fields[khmer_name]" id="mobIdCardOcrKhmerName">
                                    <input type="hidden" name="id_card_ocr_fields[english_name]" id="mobIdCardOcrEnglishName">
                                    <input type="hidden" name="id_card_ocr_fields[address]" id="mobIdCardOcrAddress">
                                </div>
                            </div>

                            <!-- Customer Fields Grid -->
                            <div class="lm-grid-2">
                                <div class="lm-field">
                                    <label class="lm-label">{{ $lmText('Name in Khmer', 'ឈ្មោះជាភាសាខ្មែរ') }} <span class="lm-req">*</span></label>
                                    <input type="text" name="customer_khmer_name" id="modalCustomerKhmerName" class="lm-control" required placeholder="{{ $lmText('Khmer Full Name', 'ឈ្មោះពេញជាភាសាខ្មែរ') }}">
                                </div>
                                <div class="lm-field">
                                    <label class="lm-label">{{ $lmText('Name in English', 'ឈ្មោះជាអក្សរឡាតាំង') }} <span class="lm-req">*</span></label>
                                    <input type="text" name="customer_english_name" id="modalCustomerEnglishName" class="lm-control" required placeholder="{{ $lmText('English Full Name', 'ឈ្មោះជាអក្សរឡាតាំង') }}">
                                    <input type="hidden" name="customer_name" id="modalCustomerName">
                                </div>
                            </div>

                            <div class="lm-grid-2">
                                <div class="lm-field">
                                    <label class="lm-label">{{ $lmText('Primary Phone', 'លេខទូរស័ព្ទចម្បង') }} <span class="lm-req">*</span></label>
                                    <div style="display: flex; gap: 6px;">
                                        <input type="text" name="customer_phone" id="modalCustomerPhone" class="lm-control" placeholder="012 345 678" required style="flex: 1;">
                                        <button type="button" class="btn btn-default btn-sm" id="modalBtnShowAlternatePhone" title="{{ $lmText('Add Alternate Phone', 'បន្ថែមលេខទូរស័ព្ទបន្ទាប់បន្សំ') }}" style="border-radius: 8px;">
                                            <i class="fa fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="lm-field">
                                    <label class="lm-label">{{ $lmText('National ID Card #', 'លេខអត្តសញ្ញាណប័ណ្ណ') }}</label>
                                    <input type="text" name="id_card_number" id="modalCustomerIdCard" class="lm-control" placeholder="010123456">
                                </div>
                            </div>

                            <div class="lm-field" id="modalAlternatePhoneGroup" style="display: none;">
                                <label class="lm-label">{{ $lmText('Alternate Phone', 'លេខទូរស័ព្ទបន្ទាប់បន្សំ') }}</label>
                                <input type="text" name="alternate_phone" id="modalAlternatePhone" class="lm-control" placeholder="{{ $lmText('Secondary Phone', 'លេខទូរស័ព្ទទីពីរ') }}">
                            </div>

                            <!-- Cambodia Administrative Hierarchy Address -->
                            <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid #f1f5f9;">
                                <label class="lm-label" style="color: #2563eb; display: flex; align-items: center; gap: 6px;">
                                    <i class="fa fa-map-marker"></i> {{ $lmText('Cambodia Administrative Address', 'អាសយដ្ឋានរដ្ឋបាលកម្ពុជា') }}
                                </label>
                                <input type="hidden" name="customer_address" id="modalCustomerAddress">
                                <div class="lm-grid-2" style="margin-top: 8px;">
                                    <div class="lm-field">
                                        <label class="lm-label">{{ $lmText('Province / City', 'រាជធានី / ខេត្ត') }}</label>
                                        <select name="province_code" id="modalProvinceSelect" class="lm-control">
                                            <option value="">-- {{ $lmText('Select Province', 'ជ្រើសរើសខេត្ត') }} --</option>
                                        </select>
                                        <input type="hidden" name="province_name" id="modalProvinceName">
                                    </div>
                                    <div class="lm-field">
                                        <label class="lm-label">{{ $lmText('District / Khan', 'ក្រុង / ស្រុក / ខណ្ឌ') }}</label>
                                        <select name="district_code" id="modalDistrictSelect" class="lm-control" disabled>
                                            <option value="">-- {{ $lmText('Select District', 'ជ្រើសរើសស្រុក') }} --</option>
                                        </select>
                                        <input type="hidden" name="district_name" id="modalDistrictName">
                                    </div>
                                    <div class="lm-field">
                                        <label class="lm-label">{{ $lmText('Commune / Sangkat', 'ឃុំ / សង្កាត់') }}</label>
                                        <select name="commune_code" id="modalCommuneSelect" class="lm-control" disabled>
                                            <option value="">-- {{ $lmText('Select Commune', 'ជ្រើសរើសឃុំ') }} --</option>
                                        </select>
                                        <input type="hidden" name="commune_name" id="modalCommuneName">
                                    </div>
                                    <div class="lm-field">
                                        <label class="lm-label">{{ $lmText('Village', 'ភូមិ') }}</label>
                                        <select name="village_code" id="modalVillageSelect" class="lm-control" disabled>
                                            <option value="">-- {{ $lmText('Select Village', 'ជ្រើសរើសភូមិ') }} --</option>
                                        </select>
                                        <input type="hidden" name="village_name" id="modalVillageName">
                                    </div>
                                </div>
                                <div id="modalAddressLoadStatus" style="font-size: 11px; color: #64748b; margin-top: 4px;"></div>
                            </div>

                            <div class="lm-field" style="margin-top: 10px;">
                                <label class="lm-label">{{ $lmText('Customer Group', 'ក្រុមអតិថិជន') }}</label>
                                <input name="customer_group_name" class="lm-control" value="រំលស់">
                            </div>
                        </div>
                    </div>

                    <!-- Supporting Documents Card -->
                    <div class="lm-card">
                        <div class="lm-card-head">
                            <h3 class="lm-card-title"><i class="fa fa-paperclip"></i> {{ $lmText('Supporting Documents & Notes', 'ឯកសារភ្ជាប់ & កំណត់ចំណាំ') }}</h3>
                        </div>
                        <div class="lm-card-body">
                            <div class="mob-doc-grid" id="mobDocGrid">
                                <label class="mob-doc-add" for="mobDocInput" title="{{ $lmText('Click or drag files here', 'ចុចដើម្បីជ្រើសរើសឯកសារ') }}">
                                    <i class="fa fa-cloud-upload" style="font-size: 24px; margin-bottom: 4px;"></i>
                                    <span>{{ $lmText('Add Files', 'បញ្ចូលឯកសារ') }}</span>
                                </label>
                            </div>
                            <input type="file" id="mobDocInput" accept="image/*,.pdf,.txt,.csv,.doc,.docx" multiple style="display:none;" onchange="mobHandleDocs(this)">
                            
                            <div style="margin-top: 12px;">
                                <label class="lm-label">{{ $lmText('Telegram Summary Note', 'កំណត់ចំណាំផ្ញើទៅ Telegram') }}</label>
                                <textarea name="document_text" class="lm-control" rows="2" placeholder="{{ $lmText('Write document note or extra details for telegram notification...', 'កំណត់ចំណាំឯកសារ ឬព័ត៌មានបន្ថែមសម្រាប់ជូនដំណឹង Telegram...') }}" style="height: auto; padding: 8px 12px;"></textarea>
                            </div>

                            <div id="mobDocumentLinks" style="margin-top: 10px;">
                                <label class="lm-label">{{ $lmText('External Document Links', 'តំណភ្ជាប់ឯកសារក្រៅ (Google Drive / Cloud)') }}</label>
                                <div class="mob-doc-link-row" style="display: flex; gap: 6px; margin-bottom: 6px;">
                                    <input type="url" name="document_links[]" class="lm-control" placeholder="https://drive.google.com/...">
                                    <button type="button" class="btn btn-default btn-sm" id="mobAddDocumentLink" title="Add another link" style="border-radius: 8px;">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 6px;">
                                <i class="fa fa-info-circle"></i> {{ $lmText('Tip: You can paste screenshots with Ctrl+V directly anywhere.', 'ជំនួយ: លោកអ្នកអាចចុច Ctrl+V ដើម្បី Paste រូបភាពបានភ្លាមៗ។') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Collateral Items, Down Payment, Terms & Financial Summary -->
                <div class="lm-col">
                    <!-- Collateral Items Card -->
                    <div class="lm-card">
                        <div class="lm-card-head">
                            <h3 class="lm-card-title"><i class="fa fa-cubes"></i> {{ $lmText('Collateral / Products for Installment', 'ទំនិញ / ទ្រព្យបញ្ចាំបង់រំលស់') }}</h3>
                            <button type="button" class="btn btn-primary btn-xs" id="modalBtnAddItem" style="border-radius: 6px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                <i class="fa fa-plus-circle"></i> {{ $lmText('Add Product', 'បន្ថែមទំនិញ') }}
                            </button>
                        </div>
                        <div class="lm-card-body">
                            <div id="mobProductList">
                                <!-- Dynamic Items will be rendered here -->
                            </div>
                            <button type="button" class="mob-add-product" id="modalBtnAddItemSecondary" onclick="document.getElementById('modalBtnAddItem').click()">
                                <i class="fa fa-plus-circle"></i> {{ $lmText('Add Another Item / Product', 'បន្ថែមទំនិញមួយទៀត') }}
                            </button>
                        </div>
                    </div>

                    <!-- Customer Deposit / Down Payment Card -->
                    <div class="lm-card">
                        <div class="lm-card-head">
                            <h3 class="lm-card-title"><i class="fa fa-money"></i> {{ $lmText('Customer Deposit / Down Payment', 'ប្រាក់កក់ / បង់មុន (Down Payment)') }}</h3>
                            <div class="lm-switch-row" id="mobDpToggle" onclick="this.classList.toggle('on'); document.getElementById('mobDpFields').style.display = this.classList.contains('on') ? 'block' : 'none';">
                                <div class="lm-switch-pill"></div>
                            </div>
                        </div>
                        <div class="lm-card-body">
                            <div id="mobDpFields" style="display: none;">
                                <input type="hidden" id="modalDownPaymentHidden" name="down_payment" value="0">
                                <div id="mobDepositPayments">
                                    <div class="mob-payment-row" data-payment-index="0" style="margin-bottom: 10px;">
                                        <div class="mob-deposit-fields">
                                            <div class="lm-field amount" style="margin: 0;">
                                                <label class="lm-label">{{ $lmText('Deposit Amount ($)', 'ចំនួនប្រាក់កក់ ($)') }}</label>
                                                <input type="number" step="0.01" name="payments[0][amount]" class="lm-control modal-payment-amount" value="0" min="0" style="font-weight: 700; color: #059669;">
                                            </div>
                                            <div class="lm-field" style="margin: 0;">
                                                <label class="lm-label">{{ $lmText('Paid Date', 'ថ្ងៃបង់ប្រាក់') }}</label>
                                                <input type="date" name="payments[0][paid_date]" class="lm-control" value="{{ date('Y-m-d') }}">
                                            </div>
                                            <div class="lm-field" style="margin: 0;">
                                                <label class="lm-label">{{ $lmText('Payment Method', 'វិធីសាស្ត្របង់') }}</label>
                                                {!! Form::select('payments[0][method]', $paymentTypes ?? [], $defaultPaymentMethod ?? 'cash', ['class' => 'lm-control modal-payment-method']) !!}
                                            </div>
                                            <div class="lm-field" style="margin: 0;">
                                                <label class="lm-label">{{ $lmText('Ref / Transaction #', 'លេខយោង') }}</label>
                                                <input name="payments[0][reference_number]" class="lm-control" placeholder="TXN / Ref">
                                            </div>
                                            <input type="hidden" name="payments[0][currency]" value="USD">
                                            <input type="hidden" name="payments[0][exchange_rate]" value="1">
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-default btn-xs" id="modalBtnAddPayment" style="margin-top: 8px; border-radius: 6px; font-weight: 600;">
                                    <i class="fa fa-plus-circle"></i> {{ $lmText('Add Split Payment', 'បន្ថែមការបង់ប្រាក់បំបែក') }}
                                </button>
                            </div>
                            <div id="mobDpEmptyHint" style="font-size: 12px; color: #94a3b8; font-style: italic;">
                                {{ $lmText('No deposit enabled. Full amount will be financed.', 'មិនមានប្រាក់កក់ទេ - ទំហំទឹកប្រាក់សរុបនឹងត្រូវគណនាជាកម្ចីរំលស់។') }}
                            </div>
                        </div>
                    </div>

                    <!-- Installment Financing Terms Card -->
                    <div class="lm-card">
                        <div class="lm-card-head">
                            <h3 class="lm-card-title"><i class="fa fa-calculator"></i> {{ $lmText('Installment Loan Terms', 'លក្ខខណ្ឌគណនាកម្ចី') }}</h3>
                        </div>
                        <div class="lm-card-body">
                            <div class="lm-field">
                                <label class="lm-label">{{ $lmText('Principal Financed (Net Loan Amount)', 'ប្រាក់ដើមកម្ចីសុទ្ធ (បន្ទាប់ពីកាត់ប្រាក់កក់)') }} <span class="lm-req">*</span></label>
                                <input type="number" step="0.01" id="modalPrincipalAmount" name="principal_amount" class="lm-control" min="0.01" required placeholder="0.00" readonly style="font-size: 16px; font-weight: 800; color: #2563eb; background: #f8fafc;">
                            </div>

                            <div class="lm-grid-2">
                                <div class="lm-field">
                                    <label class="lm-label">{{ $lmText('Interest Rate (%)', 'អត្រាការប្រាក់ (%)') }}</label>
                                    <input type="number" step="0.01" name="interest_rate" class="lm-control" value="{{ old('interest_rate', 4) }}" min="0" style="font-weight: 700;">
                                </div>
                                <div class="lm-field">
                                    <label class="lm-label">{{ $lmText('Interest Type', 'ប្រភេទការប្រាក់') }} <span class="lm-req">*</span></label>
                                    <select name="interest_type" class="lm-control">
                                        <option value="flat">{{ $lmText('Flat Rate (ថេរ)', 'Flat Rate (ថេរ)') }}</option>
                                        <option value="reducing_balance">{{ $lmText('Reducing Balance (ថយចុះ)', 'Reducing Balance (ថយចុះ)') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="lm-grid-2">
                                <div class="lm-field">
                                    <label class="lm-label">{{ $lmText('Duration (Months)', 'រយៈពេល (ខែ)') }} <span class="lm-req">*</span></label>
                                    <input type="number" name="duration_months" class="lm-control" min="1" max="360" value="12" required style="font-weight: 700;">
                                </div>
                                <div class="lm-field">
                                    <label class="lm-label">{{ $lmText('Payment Frequency', 'ភាពញឹកញាប់នៃការបង់') }} <span class="lm-req">*</span></label>
                                    <select name="payment_frequency" class="lm-control">
                                        <option value="monthly">{{ $lmText('Monthly (ប្រចាំខែ)', 'Monthly (ប្រចាំខែ)') }}</option>
                                        <option value="weekly">{{ $lmText('Weekly (ប្រចាំសប្ដាហ៍)', 'Weekly (ប្រចាំសប្ដាហ៍)') }}</option>
                                        <option value="daily">{{ $lmText('Daily (ប្រចាំថ្ងៃ)', 'Daily (ប្រចាំថ្ងៃ)') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="lm-grid-2">
                                <div class="lm-field">
                                    <label class="lm-label">{{ $lmText('First Due Date', 'ថ្ងៃបង់ប្រាក់លើកទី១') }} <span class="lm-req">*</span></label>
                                    <input type="date" name="first_due_date" class="lm-control" value="{{ \Carbon\Carbon::today()->addMonth()->format('Y-m-d') }}">
                                </div>
                                <div class="lm-field">
                                    <label class="lm-label">{{ $lmText('Late Penalty Type', 'ប្រភេទពិន័យយឺតយ៉ាវ') }}</label>
                                    <select name="penalty_type" class="lm-control">
                                        <option value="fixed">{{ $lmText('Fixed Amount ($)', 'ទឹកប្រាក់ថេរ ($)') }}</option>
                                        <option value="percentage">{{ $lmText('Percentage (%)', 'ភាគរយ (%)') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="lm-field">
                                <label class="lm-label">{{ $lmText('Penalty Amount / Rate', 'ទំហំពិន័យ') }}</label>
                                <input type="number" step="0.01" name="penalty_amount" class="lm-control" value="0" min="0">
                            </div>

                            <!-- Financial Metrics Overview -->
                            <div class="lm-metrics-grid">
                                <div class="lm-metric-card">
                                    <div class="lm-metric-label">{{ $lmText('Total Items', 'តម្លៃទំនិញសរុប') }}</div>
                                    <div class="lm-metric-val" id="modalSummaryTotal">$0.00</div>
                                </div>
                                <div class="lm-metric-card">
                                    <div class="lm-metric-label">{{ $lmText('Deposit Paid', 'ប្រាក់កក់') }}</div>
                                    <div class="lm-metric-val" id="modalSummaryDownPayment">$0.00</div>
                                </div>
                                <div class="lm-metric-card highlight">
                                    <div class="lm-metric-label">{{ $lmText('Net Loan Due', 'ប្រាក់ដើមសុទ្ធ') }}</div>
                                    <div class="lm-metric-val" id="modalSummaryDue">$0.00</div>
                                </div>
                                <div class="lm-metric-card success">
                                    <div class="lm-metric-label">{{ $lmText('Est. Monthly Due', 'បង់ប្រចាំខែ') }}</div>
                                    <div class="lm-metric-val" id="modalSummaryMonthly">$0.00</div>
                                </div>
                            </div>

                            <!-- Schedule Preview Section -->
                            <div id="modalScheduleSection" style="display: none; margin-top: 14px;">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <label class="lm-label" style="color: #2563eb; margin: 0;"><i class="fa fa-calendar"></i> {{ $lmText('Amortization Schedule Preview', 'កាលវិភាគបង់ប្រាក់សាកល្បង') }}</label>
                                    <span style="font-size: 11px; color: #64748b;">{{ $lmText('Live calculated breakdown', 'តារាងគណនាលម្អិត') }}</span>
                                </div>
                                <div class="mob-schedule-wrap">
                                    <table class="mob-schedule-tbl" id="modalScheduleTable">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>{{ $lmText('Due Date', 'ថ្ងៃផុតកំណត់') }}</th>
                                                <th class="text-right">{{ $lmText('Principal', 'ប្រាក់ដើម') }}</th>
                                                <th class="text-right">{{ $lmText('Interest', 'ការប្រាក់') }}</th>
                                                <th class="text-right">{{ $lmText('Total', 'សរុប') }}</th>
                                                <th class="text-right">{{ $lmText('Balance', 'សមតុល្យ') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="2" class="text-right">{{ $lmText('Total', 'សរុប') }}</th>
                                                <th class="text-right">$0.00</th>
                                                <th class="text-right">$0.00</th>
                                                <th class="text-right">$0.00</th>
                                                <th class="text-right">$0.00</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Professional Action Footer -->
        <div class="lm-pro-footer">
            <div class="lm-pro-footer-info">
                <span class="lm-pro-footer-pill">
                    <i class="fa fa-cube" style="color: #2563eb;"></i>
                    <span>{{ $lmText('Principal:', 'ប្រាក់ដើម:') }} <strong id="modalFooterPrincipal">$0.00</strong></span>
                </span>
                <span class="lm-pro-footer-pill">
                    <i class="fa fa-clock-o" style="color: #10b981;"></i>
                    <span>{{ $lmText('Monthly Due:', 'បង់ប្រចាំខែ:') }} <strong id="modalFooterMonthly">$0.00</strong></span>
                </span>
            </div>
            <div class="lm-pro-footer-actions">
                <button type="button" class="lm-btn lm-btn-secondary" id="mobBtnPreviewSchedule" onclick="mobPreviewSchedule()">
                    <i class="fa fa-table"></i> {{ $lmText('Preview Schedule', 'គណនាកាលវិភាគ') }}
                </button>
                <button type="button" class="lm-btn lm-btn-warning" onclick="mobSubmit('draft')">
                    <i class="fa fa-save"></i> {{ $lmText('Save Draft', 'រក្សាទុកព្រាង') }}
                </button>
                <button type="button" class="lm-btn lm-btn-primary" onclick="mobSubmit('create_approve')">
                    <i class="fa fa-check-circle"></i> {{ $lmText('Create & Approve Agreement', 'បង្កើត & អនុម័តកម្ចី') }}
                </button>
                <button type="button" class="lm-btn lm-btn-outline" data-dismiss="modal">
                    {{ $lmText('Cancel', 'បោះបង់') }}
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Modal Quick Add Customer include -->
<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="contactModalLabel">
    @include('contact.create', ['quick_add' => true, 'selected_type' => 'customer'])
</div>

<!-- Photo Sheet Popup -->
<div class="mob-photo-sheet" id="mobPhotoSheet" aria-hidden="true" onclick="mobClosePhotoSheet()">
    <div class="mob-photo-sheet-panel" onclick="event.stopPropagation()">
        <div class="mob-photo-sheet-title" id="mobPhotoSheetTitle">{{ $lmText('Add Photo', 'បន្ថែមរូបថត') }}</div>
        <button type="button" class="mob-photo-sheet-option" onclick="mobChoosePhotoSource('camera')">
            <i class="fa fa-camera" style="color: #2563eb;"></i> {{ $lmText('Take Photo with Camera', 'ថតរូបដោយកាមេរ៉ា') }}
        </button>
        <button type="button" class="mob-photo-sheet-option" onclick="mobChoosePhotoSource('library')">
            <i class="fa fa-image" style="color: #10b981;"></i> {{ $lmText('Choose from Photo Library', 'ជ្រើសរើសពីវិចិត្រសាល') }}
        </button>
        <button type="button" class="mob-photo-sheet-cancel" onclick="mobClosePhotoSheet()">{{ $lmText('Cancel', 'បោះបង់') }}</button>
    </div>
</div>

<!-- Cropper Overlays -->
<div class="mob-product-crop-overlay" id="mobProductCropOverlay" aria-hidden="true">
    <div class="mob-product-crop-box">
        <div class="mob-product-crop-head">
            <div class="mob-product-crop-title"><i class="fa fa-crop"></i> {{ $lmText('Crop Product Photo', 'កាត់រូបថតទំនិញ') }}</div>
            <button type="button" class="mob-prod-del" onclick="mobCancelProductCrop()" style="position:static;">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <canvas class="mob-product-crop-canvas" id="mobProductCropCanvas"></canvas>
        <div class="mob-product-crop-status" id="mobProductCropStatus" style="font-size: 11px; color: #64748b; margin-top: 8px;">Drag the box or corners to keep only the product label.</div>
        <div class="mob-product-crop-actions">
            <button type="button" onclick="mobResetProductCrop()"><i class="fa fa-refresh"></i> Reset</button>
            <button type="button" onclick="mobUseOriginalProductPhoto()"><i class="fa fa-image"></i> Original</button>
            <button type="button" class="primary" onclick="mobUseCroppedProductPhoto()"><i class="fa fa-check"></i> Use Cropped Photo</button>
        </div>
    </div>
</div>

<div class="mob-product-crop-overlay" id="mobIdCardCropOverlay" aria-hidden="true">
    <div class="mob-product-crop-box">
        <div class="mob-product-crop-head">
            <div class="mob-product-crop-title"><i class="fa fa-crop"></i> {{ $lmText('Crop ID Card Photo', 'កាត់រូបថតអត្តសញ្ញាណប័ណ្ណ') }}</div>
            <button type="button" class="mob-prod-del" onclick="mobCancelIdCardCrop()" style="position:static;">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <canvas class="mob-product-crop-canvas" id="mobIdCardCropCanvas"></canvas>
        <div class="mob-product-crop-status" id="mobIdCardCropStatus" style="font-size: 11px; color: #64748b; margin-top: 8px;">Drag the box or corners to keep only the ID card.</div>
        <div class="mob-product-crop-actions">
            <button type="button" onclick="mobResetIdCardCrop()"><i class="fa fa-refresh"></i> Reset</button>
            <button type="button" onclick="mobUseOriginalIdCardPhoto()"><i class="fa fa-image"></i> Original</button>
            <button type="button" class="primary" onclick="mobUseCroppedIdCardPhoto()"><i class="fa fa-check"></i> Use Cropped Photo</button>
        </div>
    </div>
</div>

<div class="mob-product-crop-overlay" id="mobProfileCropOverlay" aria-hidden="true">
    <div class="mob-product-crop-box">
        <div class="mob-product-crop-head">
            <div class="mob-product-crop-title"><i class="fa fa-crop"></i> {{ $lmText('Crop Profile Photo', 'កាត់រូបថតផ្ទាល់ខ្លួន') }}</div>
            <button type="button" class="mob-prod-del" onclick="mobCancelProfileCrop()" style="position:static;">
                <i class="fa fa-times"></i>
            </button>
        </div>
        <canvas class="mob-product-crop-canvas" id="mobProfileCropCanvas"></canvas>
        <div class="mob-product-crop-status" id="mobProfileCropStatus" style="font-size: 11px; color: #64748b; margin-top: 8px;">Drag the box or corners to keep the customer's face centered.</div>
        <div class="mob-product-crop-actions">
            <button type="button" onclick="mobResetProfileCrop()"><i class="fa fa-refresh"></i> Reset</button>
            <button type="button" onclick="mobUseOriginalProfilePhoto()"><i class="fa fa-image"></i> Original</button>
            <button type="button" class="primary" onclick="mobUseCroppedProfilePhoto()"><i class="fa fa-check"></i> Use Cropped Photo</button>
        </div>
    </div>
</div>

<script>
// ==================== JAVASCRIPT CONTROLLERS ====================
var mobIdCardData = '';
var mobCustomerProfileData = '';
var mobPhotoTarget = '';
var mobPhotoTargetCard = null;
var mobProfileCropper = null;
var mobProfileCropFile = null;
var mobIdCardCropper = null;
var mobIdCardCropFile = null;
var mobDocFiles = [];

// Initialize first item if empty
jQuery(function($) {
    var $list = $('#mobProductList');
    if ($list.length && !$list.find('.mob-product-item').length) {
        $('#modalBtnAddItem').trigger('click');
    }
    
    // Sync deposit hint
    $('#mobDpToggle').on('click', function() {
        var on = $(this).hasClass('on');
        $('#mobDpEmptyHint').toggle(!on);
    });

    // Auto-update summary pill on terms change
    $('input[name="interest_rate"], select[name="interest_type"], input[name="duration_months"]').on('input change', function() {
        updateFooterPills();
    });
});

function updateFooterPills() {
    var p = parseFloat(document.getElementById('modalPrincipalAmount')?.value || 0) || 0;
    var dur = parseInt(document.querySelector('input[name="duration_months"]')?.value || 12) || 12;
    var rate = parseFloat(document.querySelector('input[name="interest_rate"]')?.value || 0) || 0;
    var monthly = dur > 0 ? (p + (p * rate / 100 * dur)) / dur : 0;

    var elP = document.getElementById('modalFooterPrincipal');
    if (elP) elP.textContent = '$' + p.toFixed(2);
    var elM = document.getElementById('modalFooterMonthly');
    if (elM) elM.textContent = '$' + monthly.toFixed(2);
}

function mobCompressImage(file, maxW, maxH, quality) {
    return new Promise(function(resolve) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var img = new Image();
            img.onload = function() {
                var w = img.width, h = img.height;
                if (w > maxW) { h = Math.round(h * maxW / w); w = maxW; }
                if (h > maxH) { w = Math.round(w * maxH / h); h = maxH; }
                var canvas = document.createElement('canvas');
                canvas.width = w; canvas.height = h;
                canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                resolve(canvas.toDataURL('image/jpeg', quality));
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
}

function mobOpenPhotoSheet(target) {
    mobPhotoTarget = target;
    mobPhotoTargetCard = null;
    var sheet = document.getElementById('mobPhotoSheet');
    var title = document.getElementById('mobPhotoSheetTitle');
    if (title) {
        title.textContent = target === 'profile' ? 'Add Profile Photo' : 'Add ID Card Photo';
    }
    if (sheet) {
        sheet.classList.add('is-open');
        sheet.setAttribute('aria-hidden', 'false');
    }
}

function mobOpenProductPhotoSheet(button) {
    mobPhotoTarget = 'product';
    mobPhotoTargetCard = button ? button.closest('.mob-product-item') : null;
    var sheet = document.getElementById('mobPhotoSheet');
    var title = document.getElementById('mobPhotoSheetTitle');
    if (title) {
        title.textContent = 'Take or Upload Product Photo';
    }
    if (sheet) {
        sheet.classList.add('is-open');
        sheet.setAttribute('aria-hidden', 'false');
    }
}

function mobClosePhotoSheet() {
    var sheet = document.getElementById('mobPhotoSheet');
    if (sheet) {
        sheet.classList.remove('is-open');
        sheet.setAttribute('aria-hidden', 'true');
    }
}

function mobChoosePhotoSource(source) {
    var target = mobPhotoTarget;
    mobClosePhotoSheet();
    var inputId = '';
    if (target === 'profile') {
        inputId = source === 'camera' ? 'mobCustomerPhotoCamera' : 'mobCustomerPhotoGallery';
    } else if (target === 'id_card') {
        inputId = source === 'camera' ? 'mobIdCardCamera' : 'mobIdCardGallery';
    } else if (target === 'product' && mobPhotoTargetCard) {
        var selector = source === 'camera' ? 'input[id^="mobProductCamera"]' : 'input[id^="mobProductUpload"]';
        var productInput = mobPhotoTargetCard.querySelector(selector);
        if (productInput) productInput.click();
        return;
    }
    var input = inputId ? document.getElementById(inputId) : null;
    if (input) input.click();
}

function mobHandleCustomerProfile(input) {
    var file = input.files && input.files[0];
    if (!file) return;
    mobStartProfileCrop(file);
    input.value = '';
}

function mobApplyCustomerProfileData(dataUri) {
    mobCustomerProfileData = dataUri;
    var preview = document.getElementById('mobCustomerPhotoPreview');
    if (preview) {
        preview.innerHTML = '<img src="' + dataUri + '" alt="Customer profile preview">' +
            '<button type="button" class="lm-kyc-remove" onclick="mobRemoveCustomerProfile()" title="Remove photo"><i class="fa fa-times"></i></button>';
    }
}

function mobShowProfileCropOverlay() {
    var overlay = document.getElementById('mobProfileCropOverlay');
    if (overlay) overlay.style.display = 'flex';
}

function mobHideProfileCropOverlay() {
    var overlay = document.getElementById('mobProfileCropOverlay');
    if (overlay) overlay.style.display = 'none';
}

function mobSetProfileCropStatus(message, isError) {
    var el = document.getElementById('mobProfileCropStatus');
    if (!el) return;
    el.style.color = isError ? '#dc2626' : '#64748b';
    el.textContent = message || '';
}

function mobStartProfileCrop(file) {
    mobProfileCropper = null;
    mobProfileCropFile = file;
    mobShowProfileCropOverlay();
    mobSetProfileCropStatus('Preparing photo...');

    var reader = new FileReader();
    var image = new Image();
    reader.onload = function(event) {
        image.onload = function() {
            mobProfileCropper = mobCreateProductCropper(
                document.getElementById('mobProfileCropCanvas'),
                image,
                {x: 0.18, y: 0.08, width: 0.64, height: 0.84}
            );
            mobSetProfileCropStatus('Drag the box or corners to center face.');
        };
        image.onerror = function() { mobUseOriginalProfilePhoto(); };
        image.src = event.target.result;
    };
    reader.readAsDataURL(file);
}

function mobResetProfileCrop() { if (mobProfileCropper) mobProfileCropper.reset(); }
function mobCancelProfileCrop() { mobProfileCropper = null; mobProfileCropFile = null; mobHideProfileCropOverlay(); }
function mobUseOriginalProfilePhoto() {
    if (!mobProfileCropFile) { mobCancelProfileCrop(); return; }
    var file = mobProfileCropFile;
    mobCancelProfileCrop();
    mobCompressImage(file, 900, 900, 0.82).then(function(dataUri) { mobApplyCustomerProfileData(dataUri); });
}
function mobUseCroppedProfilePhoto() {
    if (!mobProfileCropper) { mobUseOriginalProfilePhoto(); return; }
    mobProfileCropper.getDataUrl(function(dataUri) {
        mobCancelProfileCrop();
        mobApplyCustomerProfileData(dataUri);
    });
}
function mobRemoveCustomerProfile() {
    mobCustomerProfileData = '';
    var preview = document.getElementById('mobCustomerPhotoPreview');
    if (preview) preview.innerHTML = '<i class="fa fa-user"></i>';
}

// ==================== ID CARD SCANNER & OCR ====================
function mobHandleIdCard(input) {
    var file = input.files && input.files[0];
    if (!file) return;
    mobSetOcrStatus('Preparing photo for crop...');
    mobStartIdCardCrop(file);
    input.value = '';
}

function mobApplyIdCardImageData(dataUri) {
    mobIdCardData = dataUri;
    var preview = document.getElementById('mobIdCardPreview');
    if (preview) {
        preview.innerHTML = '<img id="mobIdCardImg" src="' + dataUri + '" style="width:100%;height:100%;object-fit:cover;">' +
            '<button type="button" class="lm-kyc-remove" onclick="mobRemoveIdCard()" title="Remove ID card"><i class="fa fa-times"></i></button>';
    }
    mobScanIdCard(dataUri);
}

function mobShowIdCardCropOverlay() { var o = document.getElementById('mobIdCardCropOverlay'); if (o) o.style.display = 'flex'; }
function mobHideIdCardCropOverlay() { var o = document.getElementById('mobIdCardCropOverlay'); if (o) o.style.display = 'none'; }
function mobSetIdCardCropStatus(m, e) { var el = document.getElementById('mobIdCardCropStatus'); if (el) { el.style.color = e ? '#dc2626' : '#64748b'; el.textContent = m || ''; } }

function mobStartIdCardCrop(file) {
    mobIdCardCropper = null;
    mobIdCardCropFile = file;
    mobShowIdCardCropOverlay();
    mobSetIdCardCropStatus('Preparing ID card...');
    var reader = new FileReader();
    var image = new Image();
    reader.onload = function(event) {
        image.onload = function() {
            mobIdCardCropper = mobCreateProductCropper(document.getElementById('mobIdCardCropCanvas'), image);
            mobSetIdCardCropStatus('Drag to fit ID card borders.');
        };
        image.onerror = function() { mobUseOriginalIdCardPhoto(); };
        image.src = event.target.result;
    };
    reader.readAsDataURL(file);
}

function mobResetIdCardCrop() { if (mobIdCardCropper) mobIdCardCropper.reset(); }
function mobCancelIdCardCrop() { mobIdCardCropper = null; mobIdCardCropFile = null; mobHideIdCardCropOverlay(); }
function mobUseOriginalIdCardPhoto() {
    if (!mobIdCardCropFile) { mobCancelIdCardCrop(); return; }
    var file = mobIdCardCropFile;
    mobCancelIdCardCrop();
    mobSetOcrStatus('Preparing photo...');
    mobCompressImage(file, 1600, 1000, 0.76).then(function(dataUri) { mobApplyIdCardImageData(dataUri); });
}
function mobUseCroppedIdCardPhoto() {
    if (!mobIdCardCropper) { mobUseOriginalIdCardPhoto(); return; }
    mobIdCardCropper.getDataUrl(function(dataUri) {
        mobCancelIdCardCrop();
        mobApplyIdCardImageData(dataUri);
    });
}
function mobRemoveIdCard() {
    mobIdCardData = '';
    var preview = document.getElementById('mobIdCardPreview');
    if (preview) preview.innerHTML = '<i class="fa fa-id-card"></i>';
    mobSetOcrStatus('');
    ['mobIdCardOcrRawText', 'mobIdCardOcrNumber', 'mobIdCardOcrKhmerName', 'mobIdCardOcrEnglishName', 'mobIdCardOcrAddress'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.value = '';
    });
}

function mobSetOcrStatus(message, isError) {
    var el = document.getElementById('mobIdCardOcrStatus');
    if (!el) return;
    el.style.color = isError ? '#dc2626' : '#2563eb';
    el.innerHTML = message ? '<i class="fa ' + (isError ? 'fa-exclamation-triangle' : 'fa-spinner fa-spin') + '"></i> ' + message : '';
    if (!isError && message.indexOf('filled') !== -1) {
        el.innerHTML = '<i class="fa fa-check-circle" style="color:#10b981;"></i> ' + message;
        el.style.color = '#10b981';
    }
}

function mobFillIfEmpty(id, value) {
    var el = document.getElementById(id);
    if (el && value && !String(el.value || '').trim()) {
        el.value = value;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }
}

function mobApplyIdCardFields(fields, rawText) {
    fields = fields || {};
    document.getElementById('mobIdCardOcrRawText').value = rawText || '';
    document.getElementById('mobIdCardOcrNumber').value = fields.id_card_number || '';
    document.getElementById('mobIdCardOcrKhmerName').value = fields.khmer_name || '';
    document.getElementById('mobIdCardOcrEnglishName').value = fields.english_name || '';
    document.getElementById('mobIdCardOcrAddress').value = fields.address || '';
    mobFillIfEmpty('modalCustomerIdCard', fields.id_card_number);
    mobFillIfEmpty('modalCustomerKhmerName', fields.khmer_name);
    mobFillIfEmpty('modalCustomerEnglishName', fields.english_name);
    mobFillIfEmpty('modalCustomerAddress', fields.address);
    document.getElementById('modalCustomerName').value = document.getElementById('modalCustomerKhmerName').value || document.getElementById('modalCustomerEnglishName').value || '';
}

function mobScanIdCard(dataUri) {
    mobSetOcrStatus('Reading Cambodian National ID Card with AI Vision...');
    jQuery.ajax({
        url: "{{ route('loan-management.loans.ajax.scan-id-card') }}",
        method: 'POST',
        data: {
            _token: document.querySelector('meta[name="csrf-token"]').content,
            id_card_image: dataUri
        },
        success: function(res) {
            if (res && res.success) {
                var data = res.data || {};
                mobApplyIdCardFields(data.fields || {}, data.raw_text || '');
                mobSetOcrStatus(Object.keys(data.fields || {}).length ? 'ID Card scanned & details filled.' : 'OCR finished.');
            } else {
                mobSetOcrStatus((res && res.message) || 'OCR unavailable.', true);
            }
        },
        error: function(xhr) {
            mobSetOcrStatus(xhr.responseJSON?.message || 'OCR scan failed.', true);
        }
    });
}

// Alternate phone toggle
document.getElementById('modalBtnShowAlternatePhone')?.addEventListener('click', function() {
    var group = document.getElementById('modalAlternatePhoneGroup');
    var input = document.getElementById('modalAlternatePhone');
    if (group) group.style.display = group.style.display === 'none' ? 'block' : 'none';
    if (input && group.style.display === 'block') input.focus();
});

// External Document Links
jQuery(document).on('click', '#mobAddDocumentLink', function() {
    jQuery('#mobDocumentLinks').append(
        '<div class="mob-doc-link-row" style="display:flex; gap:6px; margin-bottom:6px;">' +
            '<input type="url" name="document_links[]" class="lm-control" placeholder="https://...">' +
            '<button type="button" class="btn btn-default btn-sm mob-remove-document-link" title="Remove link" style="border-radius:8px;"><i class="fa fa-times"></i></button>' +
        '</div>'
    );
});
jQuery(document).on('click', '.mob-remove-document-link', function() { jQuery(this).closest('.mob-doc-link-row').remove(); });

// ==================== PRODUCT OCR & SCANNER ====================
function mobSetProductOcrStatus(card, message, isError) {
    var el = card ? card.querySelector('.mob-product-ocr-status') : null;
    if (!el) return;
    el.style.color = isError ? '#dc2626' : '#2563eb';
    el.textContent = message || '';
}

function mobSetProductField(card, field, value) {
    if (!value) return;
    var el = card.querySelector('[name*="[' + field + ']"]');
    if (!el) return;
    el.value = value;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
}

function mobApplyProductPhotoFields(card, fields, rawText) {
    fields = fields || {};
    mobSetProductField(card, 'product_name', fields.product_name);
    mobSetProductField(card, 'color', fields.color);
    mobSetProductField(card, 'storage', fields.storage);
    mobSetProductField(card, 'serial_number', fields.serial_number);
    mobSetProductField(card, 'imei', fields.imei);
    var rawInput = card.querySelector('.modal-item-ocr-raw');
    if (rawInput) rawInput.value = rawText || '';
}

function mobScanProductPhoto(card, dataUri) {
    mobSetProductOcrStatus(card, 'Analyzing product photo with AI Vision...');
    jQuery.ajax({
        url: "{{ route('loan-management.loans.ajax.scan-product-photo') }}",
        method: 'POST',
        data: {
            _token: document.querySelector('meta[name="csrf-token"]').content,
            product_image: dataUri
        },
        success: function(res) {
            if (res && res.success) {
                var data = res.data || {};
                mobApplyProductPhotoFields(card, data.fields || {}, data.raw_text || '');
                mobSetProductOcrStatus(card, 'Product details detected and filled.');
            } else {
                mobSetProductOcrStatus(card, (res && res.message) || 'Product OCR unavailable.', true);
            }
        },
        error: function(xhr) {
            mobSetProductOcrStatus(card, xhr.responseJSON?.message || 'Product OCR failed.', true);
        }
    });
}

var mobProductCropper = null;
var mobProductCropCard = null;
var mobProductCropFile = null;

function mobApplyProductPhotoData(card, dataUri) {
    var hidden = card.querySelector('.modal-item-image');
    var preview = card.querySelector('.mob-product-photo-preview');
    var icon = card.querySelector('.mob-prod-img > i');
    if (hidden) hidden.value = dataUri;
    if (preview) { preview.src = dataUri; preview.style.display = 'block'; }
    if (icon) icon.style.display = 'none';
    mobScanProductPhoto(card, dataUri);
}

function mobShowProductCropOverlay() { var o = document.getElementById('mobProductCropOverlay'); if (o) o.style.display = 'flex'; }
function mobHideProductCropOverlay() { var o = document.getElementById('mobProductCropOverlay'); if (o) o.style.display = 'none'; }
function mobSetProductCropStatus(m, e) { var el = document.getElementById('mobProductCropStatus'); if (el) { el.style.color = e ? '#dc2626' : '#64748b'; el.textContent = m || ''; } }

function mobStartProductCrop(card, file) {
    mobProductCropper = null;
    mobProductCropCard = card;
    mobProductCropFile = file;
    mobShowProductCropOverlay();
    mobSetProductCropStatus('Preparing product photo...');
    var reader = new FileReader();
    var image = new Image();
    reader.onload = function(event) {
        image.onload = function() {
            mobProductCropper = mobCreateProductCropper(document.getElementById('mobProductCropCanvas'), image);
            mobSetProductCropStatus('Drag to focus on label/specs.');
        };
        image.onerror = function() { mobUseOriginalProductPhoto(); };
        image.src = event.target.result;
    };
    reader.readAsDataURL(file);
}

function mobResetProductCrop() { if (mobProductCropper) mobProductCropper.reset(); }
function mobCancelProductCrop() { mobProductCropper = null; mobProductCropCard = null; mobProductCropFile = null; mobHideProductCropOverlay(); }
function mobUseOriginalProductPhoto() {
    if (!mobProductCropCard || !mobProductCropFile) { mobCancelProductCrop(); return; }
    var card = mobProductCropCard;
    var file = mobProductCropFile;
    mobCancelProductCrop();
    mobSetProductOcrStatus(card, 'Preparing photo...');
    mobCompressImage(file, 1400, 1400, 0.72).then(function(dataUri) { mobApplyProductPhotoData(card, dataUri); });
}
function mobUseCroppedProductPhoto() {
    if (!mobProductCropper || !mobProductCropCard) { mobUseOriginalProductPhoto(); return; }
    var card = mobProductCropCard;
    mobSetProductCropStatus('Cropping...');
    mobProductCropper.getDataUrl(function(dataUri) {
        mobCancelProductCrop();
        mobApplyProductPhotoData(card, dataUri);
    });
}

function mobHandleProductPhoto(input) {
    var file = input.files && input.files[0];
    if (!file) return;
    var card = input.closest('.mob-product-item');
    if (!card) return;
    mobSetProductOcrStatus(card, 'Preparing photo...');
    mobStartProductCrop(card, file);
    input.value = '';
}

// Canvas Cropper Helper
function mobCreateProductCropper(canvas, image, initialCrop) {
    var context = canvas.getContext('2d');
    var maxWidth = Math.min(760, image.width);
    var scale = maxWidth / image.width;
    var canvasWidth = Math.round(image.width * scale);
    var canvasHeight = Math.round(image.height * scale);
    var dragMode = null;
    var lastPoint = null;
    var handleSize = 16;
    var crop = {};
    canvas.width = canvasWidth;
    canvas.height = canvasHeight;

    function reset() {
        var preset = initialCrop || {x: 0.08, y: 0.12, width: 0.84, height: 0.72};
        crop = {
            x: Math.round(canvasWidth * preset.x),
            y: Math.round(canvasHeight * preset.y),
            width: Math.round(canvasWidth * preset.width),
            height: Math.round(canvasHeight * preset.height)
        };
        constrainCrop();
        draw();
    }
    function drawHandle(x, y) {
        context.fillStyle = '#2563eb';
        context.fillRect(x - handleSize / 2, y - handleSize / 2, handleSize, handleSize);
    }
    function draw() {
        context.clearRect(0, 0, canvasWidth, canvasHeight);
        context.drawImage(image, 0, 0, canvasWidth, canvasHeight);
        context.fillStyle = 'rgba(15, 23, 42, 0.45)';
        context.fillRect(0, 0, canvasWidth, canvasHeight);
        context.drawImage(image, crop.x / scale, crop.y / scale, crop.width / scale, crop.height / scale, crop.x, crop.y, crop.width, crop.height);
        context.strokeStyle = '#2563eb';
        context.lineWidth = 3;
        context.strokeRect(crop.x, crop.y, crop.width, crop.height);
        drawHandle(crop.x, crop.y);
        drawHandle(crop.x + crop.width, crop.y);
        drawHandle(crop.x, crop.y + crop.height);
        drawHandle(crop.x + crop.width, crop.y + crop.height);
    }
    function getPoint(event) {
        var source = event.touches && event.touches.length ? event.touches[0] : event;
        var rect = canvas.getBoundingClientRect();
        return {
            x: (source.clientX - rect.left) * (canvas.width / rect.width),
            y: (source.clientY - rect.top) * (canvas.height / rect.height)
        };
    }
    function getDragMode(point) {
        var handles = {
            nw: {x: crop.x, y: crop.y}, ne: {x: crop.x + crop.width, y: crop.y},
            sw: {x: crop.x, y: crop.y + crop.height}, se: {x: crop.x + crop.width, y: crop.y + crop.height}
        };
        for (var mode in handles) {
            if (Math.abs(point.x - handles[mode].x) <= handleSize && Math.abs(point.y - handles[mode].y) <= handleSize) return mode;
        }
        if (point.x >= crop.x && point.x <= crop.x + crop.width && point.y >= crop.y && point.y <= crop.y + crop.height) return 'move';
        return null;
    }
    function constrainCrop() {
        var minSize = 40;
        crop.width = Math.max(minSize, crop.width);
        crop.height = Math.max(minSize, crop.height);
        crop.x = Math.max(0, Math.min(crop.x, canvasWidth - crop.width));
        crop.y = Math.max(0, Math.min(crop.y, canvasHeight - crop.height));
        if (crop.x + crop.width > canvasWidth) crop.width = canvasWidth - crop.x;
        if (crop.y + crop.height > canvasHeight) crop.height = canvasHeight - crop.y;
    }
    function resizeCrop(mode, deltaX, deltaY) {
        if (mode.indexOf('n') !== -1) { crop.y += deltaY; crop.height -= deltaY; }
        if (mode.indexOf('s') !== -1) { crop.height += deltaY; }
        if (mode.indexOf('w') !== -1) { crop.x += deltaX; crop.width -= deltaX; }
        if (mode.indexOf('e') !== -1) { crop.width += deltaX; }
    }
    function startDrag(e) { var p = getPoint(e); dragMode = getDragMode(p); lastPoint = p; if (dragMode) e.preventDefault(); }
    function moveDrag(e) {
        if (!dragMode) return;
        var p = getPoint(e);
        var dx = p.x - lastPoint.x, dy = p.y - lastPoint.y;
        if (dragMode === 'move') { crop.x += dx; crop.y += dy; } else { resizeCrop(dragMode, dx, dy); }
        constrainCrop(); lastPoint = p; draw(); e.preventDefault();
    }
    function endDrag() { dragMode = null; lastPoint = null; }

    canvas.onmousedown = startDrag; canvas.onmousemove = moveDrag; canvas.onmouseup = endDrag; canvas.onmouseleave = endDrag;
    canvas.ontouchstart = startDrag; canvas.ontouchmove = moveDrag; canvas.ontouchend = endDrag;
    reset();

    return {
        reset: reset,
        getDataUrl: function(callback) {
            var cw = Math.round(crop.width / scale), ch = Math.round(crop.height / scale);
            var maxOut = 1600;
            var outScale = Math.min(1, maxOut / Math.max(cw, ch));
            var out = document.createElement('canvas');
            out.width = Math.max(1, Math.round(cw * outScale));
            out.height = Math.max(1, Math.round(ch * outScale));
            out.getContext('2d').drawImage(image, crop.x / scale, crop.y / scale, crop.width / scale, crop.height / scale, 0, 0, out.width, out.height);
            callback(out.toDataURL('image/jpeg', 0.88));
        }
    };
}

// ==================== DOCUMENTS & CLIPBOARD ====================
function mobHandleDocs(input) {
    var files = Array.from(input.files || []);
    if (!files.length) return;
    var grid = document.getElementById('mobDocGrid');
    var addBtn = grid.querySelector('.mob-doc-add');
    files.forEach(function(file) {
        var thumb = document.createElement('div');
        thumb.className = 'mob-doc-thumb';
        thumb.innerHTML = '<div style="color:#2563eb;"><i class="fa fa-spinner fa-spin"></i></div>';
        grid.insertBefore(thumb, addBtn);

        if (file.type && file.type.indexOf('image/') === 0) {
            mobCompressImage(file, 1200, 800, 0.65).then(function(dataUri) {
                var idx = mobDocFiles.length;
                mobDocFiles.push({ dataUri: dataUri, name: file.name, type: 'image' });
                thumb.innerHTML = '<img src="' + dataUri + '">' +
                    '<button type="button" class="mob-doc-remove" onclick="mobRemoveDoc(this, ' + idx + ')"><i class="fa fa-times"></i></button>';
            });
        } else {
            var reader = new FileReader();
            reader.onload = function(e) {
                var idx = mobDocFiles.length;
                mobDocFiles.push({ dataUri: e.target.result, name: file.name, type: 'file' });
                thumb.innerHTML = '<div class="mob-doc-icon"><i class="fa fa-file-text-o"></i><span>' + file.name.substring(0, 10) + '</span></div>' +
                    '<button type="button" class="mob-doc-remove" onclick="mobRemoveDoc(this, ' + idx + ')"><i class="fa fa-times"></i></button>';
            };
            reader.readAsDataURL(file);
        }
    });
    input.value = '';
}
function mobRemoveDoc(btn, idx) { mobDocFiles[idx] = null; btn.closest('.mob-doc-thumb').remove(); }

document.addEventListener('paste', function(e) {
    var items = e.clipboardData && e.clipboardData.items;
    if (!items) return;
    var handled = false;
    for (var i = 0; i < items.length; i++) {
        if (items[i].type && items[i].type.indexOf('image/') === 0) {
            var file = items[i].getAsFile();
            if (file) {
                mobCompressImage(file, 1200, 800, 0.65).then(function(dataUri) {
                    var grid = document.getElementById('mobDocGrid');
                    if (!grid) return;
                    var addBtn = grid.querySelector('.mob-doc-add');
                    var thumb = document.createElement('div');
                    thumb.className = 'mob-doc-thumb';
                    var idx = mobDocFiles.length;
                    mobDocFiles.push({ dataUri: dataUri, name: 'paste-' + Date.now() + '.png', type: 'image' });
                    thumb.innerHTML = '<img src="' + dataUri + '"><button type="button" class="mob-doc-remove" onclick="mobRemoveDoc(this, ' + idx + ')"><i class="fa fa-times"></i></button>';
                    grid.insertBefore(thumb, addBtn);
                });
                handled = true;
            }
        }
    }
    if (handled) e.preventDefault();
});

// ==================== PREVIEW & SUBMISSION ====================
function mobPreviewSchedule() {
    var $form = jQuery('#standaloneLoanModalForm');
    document.getElementById('modalCustomerName').value = document.getElementById('modalCustomerKhmerName').value || document.getElementById('modalCustomerEnglishName').value || '';
    var urls = { previewSchedule: "{{ route('loan-management.loans.preview-standalone-schedule') }}" };
    
    jQuery.post(urls.previewSchedule, $form.serialize(), function(res) {
        var rows = res.data || [];
        var $tb = jQuery('#modalScheduleTable tbody');
        var $table = $tb.closest('table');
        var totalP = 0, totalI = 0, totalA = 0, totalB = 0;
        $tb.empty();
        rows.forEach(function(r) {
            totalP += Number(r.principal || 0);
            totalI += Number(r.interest || 0);
            totalA += Number(r.total || 0);
            totalB += Number(r.balance || 0);
            $tb.append('<tr><td>'+r.schedule_no+'</td><td>'+r.due_date+'</td><td class="text-right">$'+Number(r.principal||0).toFixed(2)+'</td><td class="text-right">$'+Number(r.interest||0).toFixed(2)+'</td><td class="text-right">$'+Number(r.total||0).toFixed(2)+'</td><td class="text-right">$'+Number(r.balance||0).toFixed(2)+'</td></tr>');
        });
        $table.find('tfoot th').eq(1).text('$' + totalP.toFixed(2));
        $table.find('tfoot th').eq(2).text('$' + totalI.toFixed(2));
        $table.find('tfoot th').eq(3).text('$' + totalA.toFixed(2));
        $table.find('tfoot th').eq(4).text('$' + totalB.toFixed(2));
        document.getElementById('modalScheduleSection').style.display = 'block';
        
        var months = parseInt(document.querySelector('input[name="duration_months"]').value) || 1;
        var monthly = (totalA / months).toFixed(2);
        document.getElementById('modalSummaryMonthly').textContent = '$' + monthly;
        document.getElementById('modalFooterMonthly').textContent = '$' + monthly;
    }).fail(function(xhr) {
        if (window.toastr) toastr.error(xhr.responseJSON?.message || 'Failed to calculate schedule');
    });
}

function mobSubmit(action) {
    document.querySelector('#standaloneLoanModalForm input[name="action_type"]').value = action;
    document.getElementById('modalCustomerName').value = document.getElementById('modalCustomerKhmerName').value || document.getElementById('modalCustomerEnglishName').value || '';
    var form = document.getElementById('standaloneLoanModalForm');
    if (form.checkValidity && !form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    var fd = new FormData(form);
    if (mobIdCardData) fd.append('id_card_image', mobIdCardData);
    if (mobCustomerProfileData) fd.append('customer_profile_image', mobCustomerProfileData);
    mobDocFiles.forEach(function(d) { if (d) fd.append('documents[]', d.dataUri); });
    fd.append('_token', document.querySelector('meta[name="csrf-token"]').content);
    
    var urls = { storeLoan: "{{ route('loan-management.loans.store-standalone') }}", loanViewBase: "{{ url('/loan-management/loans') }}" };
    var $btns = jQuery('.lm-pro-footer button').prop('disabled', true);
    
    jQuery.ajax({
        url: urls.storeLoan,
        method: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: function(res) {
            if (window.toastr) toastr.success(res.message || 'Installment Agreement Created Successfully');
            jQuery('#standaloneLoanModal').modal('hide');
            if (res?.data?.loan_id) {
                var loanUrl = urls.loanViewBase + '/' + res.data.loan_id + '/view?_lm_modal=1';
                if (window.jQuery && window.jQuery('.view_modal').length) {
                    window.jQuery('.view_modal').html('<div class="text-center" style="padding:48px;"><i class="fa fa-spinner fa-spin fa-2x"></i><p>Loading loan...</p></div>').modal('show');
                    window.jQuery.ajax({
                        url: loanUrl, dataType: 'html',
                        success: function(html) { window.jQuery('.view_modal').html(html); },
                        error: function() { window.location.href = loanUrl; }
                    });
                } else {
                    window.location.href = loanUrl;
                }
            } else {
                location.reload();
            }
        },
        error: function(xhr) {
            var msg = 'Failed to create installment';
            if (xhr.status === 422 && xhr.responseJSON?.errors) {
                var errors = xhr.responseJSON.errors;
                msg = errors[Object.keys(errors)[0]][0] || msg;
            } else {
                msg = xhr.responseJSON?.message || msg;
            }
            if (window.toastr) toastr.error(msg); else alert(msg);
        },
        complete: function() { $btns.prop('disabled', false); }
    });
}
</script>
