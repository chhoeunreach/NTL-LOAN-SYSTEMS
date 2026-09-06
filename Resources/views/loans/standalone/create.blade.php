@extends('loanmanagement::layouts.app')
@section('title', 'Create Installment')

@php
    $loanLanguage = session('user.language', config('app.locale'));
    $lmIsKhmer = $loanLanguage === 'km';
    $lmText = fn ($en, $km) => $lmIsKhmer ? $km : $en;
@endphp

@section('content_body')
<style>
    /* =========================================================
       LM PRO MODERN INSTALLMENT FORM STYLES
       ========================================================= */
    .lm-standalone-wrap {
        font-family: 'Kantumruy Pro', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        color: #1e293b;
        max-width: 1400px;
        margin: 0 auto;
        padding-bottom: 40px;
    }

    /* Page Header */
    .lm-page-head {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        padding: 16px 20px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .lm-page-head-left { display: flex; align-items: center; gap: 14px; }
    .lm-page-head-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
    }
    .lm-page-head-title { margin: 0; font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.2; }
    .lm-page-head-sub { margin: 3px 0 0; font-size: 13px; color: #64748b; }
    .lm-page-head-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

    /* Flow Stepper Navigation */
    .lm-stepper-bar {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 12px 16px;
        margin-bottom: 16px;
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .lm-step-nav-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        border-radius: 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none !important;
        color: #475569;
    }
    .lm-step-nav-item:hover {
        background: #eff6ff;
        border-color: #bfdbfe;
        color: #1d4ed8;
    }
    .lm-step-nav-item.active {
        background: #eff6ff;
        border-color: #3b82f6;
        color: #1e40af;
        box-shadow: 0 2px 6px rgba(59, 130, 246, 0.12);
    }
    .lm-step-nav-num {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #e2e8f0;
        color: #475569;
        font-weight: 700;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .lm-step-nav-item.active .lm-step-nav-num {
        background: #2563eb;
        color: #fff;
    }
    .lm-step-nav-text { min-width: 0; }
    .lm-step-nav-label { font-size: 13px; font-weight: 700; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lm-step-nav-desc { font-size: 11px; color: #94a3b8; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lm-step-nav-item.active .lm-step-nav-desc { color: #3b82f6; }

    /* Flow Guide & Smart Suggestions Banner */
    .lm-guide-card {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1px solid #cbd5e1;
        border-left: 5px solid #2563eb;
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        position: relative;
    }
    .lm-guide-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }
    .lm-guide-title {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .lm-guide-toggle-btn {
        font-size: 12px;
        color: #2563eb;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        font-weight: 600;
    }
    .lm-guide-body { font-size: 13px; color: #334155; line-height: 1.5; }
    
    /* Smart Preset Chips */
    .lm-suggestions-bar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed #cbd5e1;
    }
    .lm-sug-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; margin-right: 4px; }
    .lm-sug-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 999px;
        background: #fff;
        border: 1px solid #cbd5e1;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        transition: all 0.15s;
    }
    .lm-sug-chip:hover {
        background: #2563eb;
        border-color: #2563eb;
        color: #fff;
        box-shadow: 0 2px 6px rgba(37,99,235,0.25);
    }
    .lm-sug-chip i { font-size: 11px; color: #f59e0b; }
    .lm-sug-chip:hover i { color: #fff; }

    /* Live KPI Financial Summary Bar */
    .lm-kpi-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }
    .lm-kpi-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 18px;
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
        display: flex;
        align-items: center;
        gap: 14px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .lm-kpi-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06); }
    .lm-kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .lm-kpi-blue { background: #eff6ff; color: #2563eb; }
    .lm-kpi-emerald { background: #ecfdf5; color: #059669; }
    .lm-kpi-amber { background: #fffbeb; color: #d97706; }
    .lm-kpi-violet { background: #f5f3ff; color: #7c3aed; }
    .lm-kpi-meta { min-width: 0; }
    .lm-kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.3px; margin-bottom: 2px; }
    .lm-kpi-value { font-size: 20px; font-weight: 800; color: #0f172a; line-height: 1.2; }
    .lm-kpi-hint { font-size: 11px; color: #94a3b8; margin-top: 2px; }

    /* Step Cards Base */
    .lm-step-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.05);
        margin-bottom: 20px;
        overflow: hidden;
        transition: border-color 0.2s;
    }
    .lm-step-card:focus-within { border-color: #93c5fd; }
    .lm-step-card-header {
        padding: 14px 20px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    .lm-step-card-title-wrap { display: flex; align-items: center; gap: 12px; }
    .lm-step-badge {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        background: #2563eb;
        color: #fff;
        font-size: 14px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.25);
    }
    .lm-step-badge-icon { background: #0891b2; box-shadow: 0 2px 6px rgba(8, 145, 178, 0.25); }
    .lm-step-title { margin: 0; font-size: 15px; font-weight: 700; color: #0f172a; }
    .lm-step-subtitle { margin: 2px 0 0; font-size: 12px; color: #64748b; }
    .lm-step-card-body { padding: 20px; }

    /* Form Controls & Styling */
    .lm-form-group { margin-bottom: 14px; }
    .lm-field-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        margin-bottom: 6px;
    }
    .lm-field-label-sub {
        display: block;
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 4px;
    }
    .lm-input-styled {
        height: 38px;
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        padding: 0 12px;
        font-size: 13px;
        color: #1e293b;
        background: #fff;
        transition: all 0.15s ease;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .lm-input-styled:focus {
        border-color: #2563eb;
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }
    .lm-input-highlight {
        font-weight: 700;
        font-size: 15px;
        color: #1d4ed8;
        background: #f8fafc;
    }
    .lm-input-emerald {
        font-weight: 700;
        color: #059669;
        background: #f0fdf4;
    }
    .lm-textarea-styled {
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        padding: 8px 12px;
        font-size: 13px;
        color: #1e293b;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .lm-textarea-styled:focus {
        border-color: #2563eb;
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }
    .lm-input-group { border-radius: 8px; overflow: hidden; display: flex; }
    .lm-btn-addon {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-left: 0;
        color: #475569;
        height: 38px;
        padding: 0 14px;
    }

    /* Labels with quick shortcut buttons */
    .lm-label-with-shortcuts {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 6px;
    }
    .lm-label-with-shortcuts .lm-field-label { margin-bottom: 0; }
    .lm-quick-presets { display: flex; gap: 4px; }
    .lm-preset-btn {
        padding: 1px 6px;
        border-radius: 4px;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #475569;
        font-size: 10px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.12s;
    }
    .lm-preset-btn:hover { background: #e2e8f0; color: #0f172a; }
    .lm-preset-btn.active { background: #2563eb; border-color: #2563eb; color: #fff; }

    /* Divider titles */
    .lm-divider-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 700;
        color: #334155;
        margin: 16px 0 12px;
        padding-bottom: 6px;
        border-bottom: 1px solid #f1f5f9;
    }

    /* Badges & Pills */
    .lm-badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .lm-badge-pill-amber { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .lm-badge-pill-emerald { background: #d1fae5; color: #047857; border: 1px solid #a7f3d0; }

    /* Buttons */
    .lm-btn-action {
        border-radius: 8px;
        font-weight: 700;
        font-size: 12px;
        padding: 6px 14px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.15s ease;
    }
    .lm-btn-clean {
        background: #fff;
        border: 1px solid #cbd5e1;
        color: #475569;
        border-radius: 6px;
        font-weight: 600;
    }
    .lm-btn-clean:hover { background: #f8fafc; color: #0f172a; }

    /* Customer search dropdown */
    .lm-customer-search-wrap { position: relative; }
    .lm-customer-search-results {
        position: absolute; top: 100%; left: 0; right: 0; z-index: 1000;
        background: #fff; border: 1px solid #cbd5e1; border-radius: 8px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15); max-height: 260px; overflow-y: auto;
        display: none; margin-top: 4px;
    }
    .lm-customer-search-results .lm-cs-item {
        padding: 10px 14px; cursor: pointer; border-bottom: 1px solid #f1f5f9;
        transition: background 0.1s ease;
    }
    .lm-customer-search-results .lm-cs-item:last-child { border-bottom: none; }
    .lm-customer-search-results .lm-cs-item:hover { background: #eff6ff; }
    .lm-customer-search-results .lm-cs-item .lm-cs-name { font-weight: 700; color: #0f172a; font-size: 13px; }
    .lm-customer-search-results .lm-cs-item .lm-cs-phone { color: #64748b; font-size: 12px; margin-top: 2px; }

    /* OCR Bar & Previews */
    .lm-ocr-action-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 8px; }
    .lm-ocr-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 14px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 12px;
        background: #fff;
        border: 1px solid #cbd5e1;
        cursor: pointer;
        transition: all 0.15s;
    }
    .lm-ocr-btn:hover { background: #f8fafc; border-color: #94a3b8; }
    .lm-id-preview-box { margin-top: 8px; border-radius: 8px; overflow: hidden; border: 1px solid #cbd5e1; background: #f8fafc; max-width: 260px; }
    .lm-id-preview-box img { width: 100%; height: auto; display: block; }
    .lm-ocr-status-text { font-size: 12px; font-weight: 600; color: #2563eb; margin: 4px 0 0; min-height: 18px; }

    /* Document Grid */
    .lm-doc-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 10px; margin-top: 8px; }
    .lm-doc-thumb {
        position: relative; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;
        background: #f8fafc; aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
    }
    .lm-doc-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .lm-doc-thumb .lm-doc-icon { text-align: center; color: #64748b; }
    .lm-doc-thumb .lm-doc-icon i { font-size: 26px; display: block; margin-bottom: 4px; }
    .lm-doc-thumb .lm-doc-icon span { font-size: 9px; word-break: break-all; display: block; padding: 0 4px; }
    .lm-doc-thumb .lm-doc-remove {
        position: absolute; top: 4px; right: 4px; width: 20px; height: 20px; border-radius: 50%;
        background: rgba(15,23,42,0.65); color: #fff; border: none; font-size: 10px; cursor: pointer;
        display: flex; align-items: center; justify-content: center; line-height: 1;
    }
    .lm-doc-thumb .lm-doc-badge {
        position: absolute; bottom: 4px; left: 4px; background: rgba(15,23,42,0.7); color: #fff;
        font-size: 9px; padding: 1px 5px; border-radius: 4px; font-weight: 600;
    }
    .lm-doc-add {
        border: 2px dashed #cbd5e1; border-radius: 8px; display: flex; flex-direction: column;
        align-items: center; justify-content: center; cursor: pointer; color: #64748b;
        transition: all .15s; min-height: 100px; text-align: center; padding: 8px; background: #fff;
    }
    .lm-doc-add:hover { border-color: #2563eb; color: #2563eb; background: #eff6ff; }
    .lm-doc-add i { font-size: 24px; margin-bottom: 4px; color: #3b82f6; }
    .lm-doc-add span { font-size: 11px; font-weight: 600; }
    .lm-doc-paste-hint {
        margin-top: 10px; padding: 8px 12px; background: #f0fdf4; border: 1px solid #bbf7d0;
        border-radius: 8px; font-size: 12px; color: #166534; display: flex; align-items: center; gap: 8px;
    }

    /* Items Table */
    .lm-table-responsive-clean { border-radius: 8px; border: 1px solid #e2e8f0; overflow: hidden; margin-bottom: 8px; }
    .lm-items-table { margin-bottom: 0; background: #fff; }
    .lm-items-table th { background: #f8fafc; font-weight: 700; font-size: 12px; color: #475569; text-transform: uppercase; letter-spacing: 0.2px; padding: 10px 12px; border-bottom: 1px solid #e2e8f0 !important; }
    .lm-items-table td { vertical-align: middle !important; padding: 8px 10px; }
    .lm-items-table input { font-size: 13px; border-radius: 6px; }
    .lm-table-total-row td { background: #f8fafc; font-size: 14px; padding: 12px 14px; border-top: 2px solid #e2e8f0; }
    .lm-badge-total { font-size: 16px; font-weight: 800; color: #2563eb; }
    .lm-item-hint-strip {
        padding: 8px 12px; background: #fffbeb; border: 1px solid #fde68a;
        border-radius: 8px; font-size: 12px; color: #92400e; display: flex; align-items: center; gap: 8px;
    }

    /* Item Photo in Table */
    .lm-item-photo-control { display: flex; align-items: center; gap: 6px; }
    .lm-item-photo-thumb {
        width: 36px; height: 36px; border: 1px dashed #cbd5e1; border-radius: 6px;
        background: #f8fafc; display: flex; align-items: center; justify-content: center;
        color: #94a3b8; overflow: hidden; flex-shrink: 0;
    }
    .lm-item-photo-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .lm-item-photo-status { display: block; font-size: 10px; color: #059669; font-weight: 600; }

    /* Schedule Table */
    .lm-schedule-table { margin-bottom: 0; background: #fff; }
    .lm-schedule-table th { background: #f8fafc; font-weight: 700; font-size: 12px; color: #475569; text-transform: uppercase; }
    .lm-schedule-tfoot-row th { background: #f8fafc; font-size: 13px; font-weight: 800; color: #0f172a; }

    /* Bottom Action Bar (Floating / Solid) */
    .lm-bottom-actions-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px 20px;
        box-shadow: 0 10px 25px -5px rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 24px;
    }
    .lm-action-buttons-wrap { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .lm-btn-lg-action {
        height: 42px;
        padding: 0 20px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        transition: all 0.15s ease;
    }
    .lm-btn-lg-action:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); }

    /* Recent Loans Box */
    .lm-recent-box {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.05);
        overflow: hidden;
    }
    .lm-recent-header {
        padding: 14px 20px;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .lm-recent-title { margin: 0; font-size: 15px; font-weight: 700; color: #0f172a; }
    .lm-recent-status-pill {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 800; text-transform: uppercase;
    }
    .lm-recent-status-pill.active,
    .lm-recent-status-pill.approved,
    .lm-recent-status-pill.completed { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
    .lm-recent-status-pill.draft,
    .lm-recent-status-pill.pending { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
    .lm-recent-status-pill.rejected,
    .lm-recent-status-pill.cancelled,
    .lm-recent-status-pill.defaulted { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

    /* Crop Modal */
    .lm-id-crop-overlay {
        position: fixed; inset: 0; z-index: 1060; display: none; align-items: center; justify-content: center;
        background: rgba(15, 23, 42, 0.75); padding: 18px; backdrop-filter: blur(2px);
    }
    .lm-id-crop-box {
        width: min(860px, 96vw); max-height: 94vh; overflow: auto; background: #fff;
        border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); padding: 20px;
    }
    .lm-id-crop-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
    .lm-id-crop-title { margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; }
    .lm-id-crop-canvas { display: block; width: 100%; max-height: 65vh; border: 1px solid #cbd5e1; border-radius: 8px; touch-action: none; background: #f8fafc; }
    .lm-id-crop-status { min-height: 18px; margin-top: 10px; color: #64748b; font-size: 13px; }
    .lm-id-crop-actions { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 8px; margin-top: 14px; }

    /* Responsive */
    @media (max-width: 991px) {
        .lm-stepper-bar { grid-template-columns: repeat(2, 1fr); }
        .lm-kpi-summary-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 767px) {
        .lm-stepper-bar { grid-template-columns: 1fr; }
        .lm-kpi-summary-grid { grid-template-columns: 1fr; }
        .lm-page-head { flex-direction: column; align-items: flex-start; }
        .lm-page-head-right { width: 100%; }
        .lm-page-head-right .btn { width: 100%; }
        .lm-bottom-actions-card { flex-direction: column; align-items: stretch; }
        .lm-action-buttons-wrap { flex-direction: column; }
        .lm-action-buttons-wrap .btn { width: 100%; justify-content: center; }
    }
</style>

<div class="lm-standalone-wrap no-print">
    <!-- Top Header -->
    <div class="lm-page-head">
        <div class="lm-page-head-left">
            <div class="lm-page-head-icon">
                <i class="fa fa-pencil-square-o"></i>
            </div>
            <div>
                <h1 class="lm-page-head-title">{{ $lmText('Create New Installment', 'បង្កើតកិច្ចសន្យាបង់រំលស់ថ្មី') }}</h1>
                <p class="lm-page-head-sub">{{ $lmText('Professional end-to-end installment workflow with automated KYC, items pricing, and live schedule amortization.', 'ដំណើរការបង្កើតកម្ចីស្ដង់ដារ បំពេញអត្តសញ្ញាណប័ណ្ណស្វ័យប្រវត្តិ និងគណនាកាលវិភាគបង់ប្រាក់ភ្លាមៗ') }}</p>
            </div>
        </div>
        <div class="lm-page-head-right">
            <a href="{{ route('loan-management.loans.calculator') }}" class="btn btn-default btn-sm lm-btn-clean" target="_blank">
                <i class="fa fa-calculator text-primary"></i> {{ $lmText('Open Calculator', 'បើកម៉ាស៊ីនគណនា') }}
            </a>
            <a href="{{ route('loan-management.loans') }}" class="btn btn-default btn-sm lm-btn-clean">
                <i class="fa fa-list text-muted"></i> {{ $lmText('All Installments', 'បញ្ជីកម្ចីទាំងអស់') }}
            </a>
        </div>
    </div>

    <!-- 4-Step Interactive Navigation -->
    <div class="lm-stepper-bar">
        <a href="#sectionCustomer" class="lm-step-nav-item active" data-step="1">
            <span class="lm-step-nav-num">1</span>
            <div class="lm-step-nav-text">
                <span class="lm-step-nav-label">{{ $lmText('Customer & KYC', 'អតិថិជន & KYC') }}</span>
                <span class="lm-step-nav-desc">{{ $lmText('Identity & profile', 'អត្តសញ្ញាណ និងព័ត៌មាន') }}</span>
            </div>
        </a>
        <a href="#sectionItems" class="lm-step-nav-item" data-step="2">
            <span class="lm-step-nav-num">2</span>
            <div class="lm-step-nav-text">
                <span class="lm-step-nav-label">{{ $lmText('Products & Items', 'ទំនិញ & ឧបករណ៍') }}</span>
                <span class="lm-step-nav-desc">{{ $lmText('IMEI / Serial lookup', 'ទាញយក IMEI/ស៊េរី') }}</span>
            </div>
        </a>
        <a href="#sectionTerms" class="lm-step-nav-item" data-step="3">
            <span class="lm-step-nav-num">3</span>
            <div class="lm-step-nav-text">
                <span class="lm-step-nav-label">{{ $lmText('Financing Terms', 'លក្ខខណ្ឌកម្ចី') }}</span>
                <span class="lm-step-nav-desc">{{ $lmText('Interest & Duration', 'ការប្រាក់ និងរយៈពេល') }}</span>
            </div>
        </a>
        <a href="#sectionPayment" class="lm-step-nav-item" data-step="4">
            <span class="lm-step-nav-num">4</span>
            <div class="lm-step-nav-text">
                <span class="lm-step-nav-label">{{ $lmText('Down Payment & Plan', 'ប្រាក់កក់ & កាលវិភាគ') }}</span>
                <span class="lm-step-nav-desc">{{ $lmText('Upfront & schedule', 'ទូទាត់មុន និងកាលវិភាគ') }}</span>
            </div>
        </a>
    </div>

    <!-- Step Flow Introduction & Smart Suggestions Guide -->
    <div class="lm-guide-card" id="lmGuideBanner">
        <div class="lm-guide-top">
            <h4 class="lm-guide-title">
                <i class="fa fa-info-circle text-primary"></i> 
                {{ $lmText('Workflow Guide & Smart Suggestions', 'ការណែនាំដំណាក់កាល និងគន្លឹះគណនារហ័ស') }}
            </h4>
            <button type="button" class="lm-guide-toggle-btn" id="btnToggleGuide">
                <i class="fa fa-chevron-up"></i> <span id="guideToggleText">{{ $lmText('Hide Guide', 'លាក់ការណែនាំ') }}</span>
            </button>
        </div>
        <div class="lm-guide-body" id="lmGuideContent">
            <p style="margin-bottom:8px;">
                <strong>{{ $lmText('How it works:', 'របៀបបំពេញបែបបទ៖') }}</strong>
                {{ $lmText('1. Choose or scan an ID card to populate the customer profile. 2. Add product items or enter serials for auto price calculation. 3. Configure loan duration and interest. 4. Adjust down payment and preview amortization schedule before saving.', '១. ស្វែងរកអតិថិជន ឬស្កេនអត្តសញ្ញាណប័ណ្ណដើម្បីបំពេញទិន្នន័យ។ ២. បញ្ចូលមុខទំនិញ ឬវាយបញ្ចូល IMEI/Serial។ ៣. កំណត់រយៈពេល និងការប្រាក់។ ៤. បញ្ចូលប្រាក់កក់ និងពិនិត្យកាលវិភាគបង់ប្រាក់មុនពេលរក្សាទុក។') }}
            </p>
            
            <div class="lm-suggestions-bar">
                <span class="lm-sug-label"><i class="fa fa-bolt text-warning"></i> {{ $lmText('Quick Suggestions:', 'គម្រោងពេញនិយម៖') }}</span>
                <button type="button" class="lm-sug-chip js-quick-plan" data-rate="4" data-mode="flat" data-months="12" data-down-pct="0">
                    <i class="fa fa-star"></i> {{ $lmText('Standard 12 Months @ 4% Flat', '១២ ខែ | ការប្រាក់ 4% ថេរ') }}
                </button>
                <button type="button" class="lm-sug-chip js-quick-plan" data-rate="3" data-mode="flat" data-months="6" data-down-pct="20">
                    <i class="fa fa-tag"></i> {{ $lmText('6 Months (20% Down) @ 3%', '៦ ខែ (កក់ 20%) | ការប្រាក់ 3%') }}
                </button>
                <button type="button" class="lm-sug-chip js-quick-plan" data-rate="3.5" data-mode="reducing_balance" data-months="24" data-down-pct="10">
                    <i class="fa fa-line-chart"></i> {{ $lmText('24 Months Reducing Balance @ 3.5%', '២៤ ខែ បង់ថយ | ការប្រាក់ 3.5%') }}
                </button>
                <button type="button" class="lm-sug-chip js-quick-plan" data-rate="0" data-mode="flat" data-months="3" data-down-pct="30">
                    <i class="fa fa-gift"></i> {{ $lmText('3 Months 0% Promotion', '៣ ខែ ការប្រាក់ 0% (កក់ 30%)') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Live KPI Financial Summary Bar -->
    <div class="lm-kpi-summary-grid">
        <div class="lm-kpi-card">
            <div class="lm-kpi-icon lm-kpi-blue">
                <i class="fa fa-shopping-cart"></i>
            </div>
            <div class="lm-kpi-meta">
                <div class="lm-kpi-label">{{ $lmText('Total Product Price', 'តម្លៃទំនិញសរុប') }}</div>
                <div class="lm-kpi-value"><span id="summaryTotal">0.00</span> <small style="font-size:12px; color:#64748b;">USD</small></div>
                <div class="lm-kpi-hint">{{ $lmText('From line items', 'សរុបពីមុខទំនិញ') }}</div>
            </div>
        </div>

        <div class="lm-kpi-card">
            <div class="lm-kpi-icon lm-kpi-emerald">
                <i class="fa fa-arrow-down"></i>
            </div>
            <div class="lm-kpi-meta">
                <div class="lm-kpi-label">{{ $lmText('Down Payment Upfront', 'ប្រាក់កក់ដំបូង') }}</div>
                <div class="lm-kpi-value"><span id="summaryDownPayment">0.00</span> <small style="font-size:12px; color:#64748b;">USD</small></div>
                <div class="lm-kpi-hint" id="summaryDownPaymentPct">0% upfront</div>
            </div>
        </div>

        <div class="lm-kpi-card">
            <div class="lm-kpi-icon lm-kpi-amber">
                <i class="fa fa-money"></i>
            </div>
            <div class="lm-kpi-meta">
                <div class="lm-kpi-label">{{ $lmText('Principal Financed', 'ប្រាក់ដើមត្រូវបង់រំលស់') }}</div>
                <div class="lm-kpi-value"><span id="summaryDue">0.00</span> <small style="font-size:12px; color:#64748b;">USD</small></div>
                <div class="lm-kpi-hint">{{ $lmText('Principal loan base', 'ប្រាក់ខ្ចីជាក់ស្តែង') }}</div>
            </div>
        </div>

        <div class="lm-kpi-card">
            <div class="lm-kpi-icon lm-kpi-violet">
                <i class="fa fa-calendar-check-o"></i>
            </div>
            <div class="lm-kpi-meta">
                <div class="lm-kpi-label">{{ $lmText('Estimated / Period', 'ប៉ាន់ស្មានត្រូវបង់/ខែ') }}</div>
                <div class="lm-kpi-value"><span id="summaryMonthly">0.00</span> <small style="font-size:12px; color:#64748b;">USD</small></div>
                <div class="lm-kpi-hint" id="summaryMonthlyHint">12 periods @ 4% Flat</div>
            </div>
        </div>
    </div>

    <!-- Main Loan Form -->
    <form id="standaloneLoanForm" method="POST" action="{{ route('loan-management.loans.store-standalone') }}">
        @csrf
        <input type="hidden" name="action_type" value="create_approve">

        @include('loanmanagement::loans.standalone.partials.customer_section')
        @include('loanmanagement::loans.standalone.partials.items_section')
        @include('loanmanagement::loans.standalone.partials.loan_terms', ['locations' => $locations, 'collectors' => $collectors, 'loanLocations' => $loanLocations])
        @include('loanmanagement::loans.standalone.partials.payment_section', ['paymentTypes' => $paymentTypes, 'defaultPaymentMethod' => $defaultPaymentMethod])
        @include('loanmanagement::loans.standalone.partials.schedule_preview')

        <!-- Bottom Action Bar -->
        <div class="lm-bottom-actions-card">
            <div>
                <span style="font-weight:700; color:#334155; font-size:14px;">
                    <i class="fa fa-shield text-success"></i> {{ $lmText('Ready to Finalize Installment Application', 'រួចរាល់សម្រាប់ការបង្កើតកិច្ចសន្យា') }}
                </span>
                <div style="font-size:12px; color:#64748b;">{{ $lmText('Review details or preview the schedule before proceeding.', 'ពិនិត្យទិន្នន័យឱ្យបានត្រឹមត្រូវមុននឹងចុចបង្កើត') }}</div>
            </div>
            <div class="lm-action-buttons-wrap">
                <button type="button" class="btn btn-info lm-btn-lg-action" id="btnPreviewSchedule">
                    <i class="fa fa-table"></i> {{ $lmText('Preview Schedule', 'គណនាកាលវិភាគ') }}
                </button>
                <button type="button" class="btn btn-success lm-btn-lg-action" id="btnCreateLoan" data-action="create_approve">
                    <i class="fa fa-check-circle"></i> {{ $lmText('Create & Approve Installment', 'បង្កើត និងអនុម័តកម្ចី') }}
                </button>
                <a href="{{ route('loan-management.loans') }}" class="btn btn-default lm-btn-lg-action lm-btn-clean">
                    <i class="fa fa-times text-danger"></i> {{ $lmText('Cancel', 'បោះបង់') }}
                </a>
            </div>
        </div>
    </form>

    <!-- Recently Created Installments -->
    <div class="lm-recent-box">
        <div class="lm-recent-header">
            <div>
                <h3 class="lm-recent-title"><i class="fa fa-clock-o text-primary"></i> {{ $lmText('Recently Created Installments', 'កម្ចីដែលបានបង្កើតថ្មីៗ') }}</h3>
                <p class="lm-recent-subtitle" style="margin:2px 0 0; font-size:12px; color:#64748b;">{{ $lmText('Review recent loans and immediate repayment records.', 'បញ្ជីកម្ចីចុងក្រោយសម្រាប់ការផ្ទៀងផ្ទាត់') }}</p>
            </div>
            <a href="{{ route('loan-management.loans') }}" class="btn btn-default btn-sm lm-btn-clean">
                <i class="fa fa-list"></i> {{ $lmText('View All', 'មើលទាំងអស់') }}
            </a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover lm-recent-loans-table" style="margin-bottom:0;">
                <thead>
                    <tr style="background:#f8fafc; font-size:11px; color:#64748b; text-transform:uppercase;">
                        <th>{{ $lmText('Installment #', 'លេខកូដកម្ចី') }}</th>
                        <th>{{ $lmText('Customer Name', 'ឈ្មោះអតិថិជន') }}</th>
                        <th>{{ $lmText('Contract Date', 'កាលបរិច្ឆេទ') }}</th>
                        <th class="text-right">{{ $lmText('Principal Amount', 'ប្រាក់ដើម') }}</th>
                        <th class="text-right">{{ $lmText('Remaining Balance', 'សមតុល្យនៅសល់') }}</th>
                        <th class="text-center">{{ $lmText('Status', 'ស្ថានភាព') }}</th>
                        <th class="text-center">{{ $lmText('Action', 'សកម្មភាព') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentLoans ?? collect() as $loan)
                        @php
                            $status = strtolower((string) ($loan->status ?? ''));
                            $statusClass = preg_replace('/[^a-z0-9_-]+/', '-', $status) ?: 'unknown';
                            $currency = $loan->currency ?? 'USD';
                            $loanDate = ($loan->loan_date ?? '') ?: (substr((string) ($loan->created_at ?? ''), 0, 10) ?: '-');
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('loan-management.loans.view', $loan->id) }}" style="font-weight:700; color:#2563eb;">
                                    {{ $loan->loan_number ?? ('#'.$loan->id) }}
                                </a>
                                <span style="display:block; color:#94a3b8; font-size:11px;">#{{ $loan->id }}</span>
                            </td>
                            <td>
                                <strong style="color:#0f172a;">{{ $loan->customer_name_snapshot ?? '-' }}</strong>
                                <span style="display:block; color:#64748b; font-size:12px;">{{ $loan->customer_phone_snapshot ?? '-' }}</span>
                            </td>
                            <td>{{ $loanDate }}</td>
                            <td class="text-right" style="font-weight:600;">{{ number_format((float) ($loan->principal_amount ?? 0), 2) }} {{ $currency }}</td>
                            <td class="text-right" style="font-weight:700; color:#0f172a;">{{ number_format((float) ($loan->balance_amount ?? 0), 2) }} {{ $currency }}</td>
                            <td class="text-center"><span class="lm-recent-status-pill {{ $statusClass }}">{{ $loan->status ?? '-' }}</span></td>
                            <td class="text-center">
                                <a href="{{ route('loan-management.loans.view', $loan->id) }}" class="btn btn-xs btn-primary">
                                    <i class="fa fa-eye"></i> {{ $lmText('View', 'មើល') }}
                                </a>
                                <a href="#" class="btn btn-xs btn-success btn-modal" data-href="{{ route('loan-management.loans.payment.quick-pay', $loan->id) }}" data-container=".view_modal">
                                    <i class="fa fa-money"></i> {{ $lmText('Pay', 'បង់ប្រាក់') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted" style="padding:20px;">{{ $lmText('No loans created yet.', 'មិនទាន់មានទិន្នន័យកម្ចីនៅឡើយ') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ID Card Cropper Modal -->
<div class="lm-id-crop-overlay" id="lmIdCardCropOverlay" aria-hidden="true">
    <div class="lm-id-crop-box">
        <div class="lm-id-crop-head">
            <h3 class="lm-id-crop-title"><i class="fa fa-crop text-primary"></i> {{ $lmText('Crop ID Card Photo for OCR', 'កាត់តម្រឹមរូបអត្តសញ្ញាណប័ណ្ណ') }}</h3>
            <button type="button" class="btn btn-default btn-sm" id="btnCancelIdCrop"><i class="fa fa-times"></i></button>
        </div>
        <canvas class="lm-id-crop-canvas" id="lmIdCardCropCanvas"></canvas>
        <div class="lm-id-crop-status" id="lmIdCardCropStatus">{{ $lmText('Drag the box or corners to keep only the ID card.', 'ទាញជ្រុងដើម្បីតម្រឹមយកតែផ្ទាំងអត្តសញ្ញាណប័ណ្ណ') }}</div>
        <div class="lm-id-crop-actions">
            <button type="button" class="btn btn-default btn-sm" id="btnResetIdCrop"><i class="fa fa-refresh"></i> {{ $lmText('Reset', 'កំណត់ឡើងវិញ') }}</button>
            <button type="button" class="btn btn-default btn-sm" id="btnUseOriginalIdPhoto"><i class="fa fa-image"></i> {{ $lmText('Use Original', 'យករូបដើម') }}</button>
            <button type="button" class="btn btn-primary btn-sm" id="btnUseCroppedIdPhoto"><i class="fa fa-check"></i> {{ $lmText('Use Cropped Photo', 'យករូបដែលបានកាត់') }}</button>
        </div>
    </div>
</div>
@endsection

@section('loan_js')
<script>
(function($){
    var urls = {
        searchCustomers: "{{ route('loan-management.loans.ajax.search-customers') }}",
        scanIdCard: "{{ route('loan-management.loans.ajax.scan-id-card') }}",
        previewSchedule: "{{ route('loan-management.loans.preview-standalone-schedule') }}",
        storeLoan: "{{ route('loan-management.loans.store-standalone') }}",
        loanList: "{{ route('loan-management.loans') }}",
        productBySerial: "{{ route('loan-management.loans.ajax.product-by-serial') }}"
    };

    var serialLookupTimers = {};

    function lookupProductBySerial($row) {
        var serial = $row.find('.item-imei').val().trim();
        if (serial.length < 3) return;

        var existingName = $row.find('.item-name').val().trim();
        if (existingName) return;

        $.get(urls.productBySerial, { serial: serial }, function (res) {
            if (res.success && res.data && res.data.product_name) {
                var $nameField = $row.find('.item-name');
                if (!$nameField.val().trim()) {
                    $nameField.val(res.data.product_name);
                }
            }
        });
    }

    $(document).on('input', '.item-imei', function () {
        var $row = $(this).closest('tr');
        var serial = $(this).val().trim();
        var rowId = $row.index();

        if (serialLookupTimers[rowId]) {
            clearTimeout(serialLookupTimers[rowId]);
        }

        if (serial.length < 3) return;

        serialLookupTimers[rowId] = setTimeout(function () {
            lookupProductBySerial($row);
        }, 600);
    });

    var searchTimer = null;
    var idCardImageData = '';
    var idCardCropper = null;
    var idCardCropFile = null;
    var lmDocFiles = [];

    function lmGetFileIcon(name) {
        var ext = (name || '').split('.').pop().toLowerCase();
        var icons = { pdf: 'fa-file-pdf-o', txt: 'fa-file-text-o', csv: 'fa-file-text-o', doc: 'fa-file-word-o', docx: 'fa-file-word-o' };
        return icons[ext] || 'fa-file-o';
    }

    function lmIsImageFile(file) {
        return file && file.type && file.type.indexOf('image/') === 0;
    }

    function lmAddDocThumb(dataUri, fileName, fileSize, isText) {
        var grid = document.getElementById('lmDocGrid');
        if (!grid) return;
        var addBtn = grid.querySelector('.lm-doc-add');
        var thumb = document.createElement('div');
        thumb.className = 'lm-doc-thumb';
        var idx = lmDocFiles.length;
        lmDocFiles.push({ dataUri: dataUri, name: fileName || 'document', type: isText ? 'text' : 'file' });
        var sizeKb = fileSize ? Math.round(fileSize / 1024) : Math.round((dataUri.length * 3 / 4) / 1024);

        if (isText) {
            thumb.innerHTML = '<div class="lm-doc-icon"><i class="fa fa-file-text-o"></i><span>' + (fileName || 'text') + '</span></div>' +
                '<button type="button" class="lm-doc-remove" onclick="lmRemoveDoc(' + idx + ')"><i class="fa fa-times"></i></button>' +
                '<span class="lm-doc-badge">' + sizeKb + 'KB</span>';
        } else {
            thumb.innerHTML = '<img src="' + dataUri + '" alt="">' +
                '<button type="button" class="lm-doc-remove" onclick="lmRemoveDoc(' + idx + ')"><i class="fa fa-times"></i></button>' +
                '<span class="lm-doc-badge">' + sizeKb + 'KB</span>';
        }
        grid.insertBefore(thumb, addBtn);
    }

    window.lmRemoveDoc = function(idx) {
        lmDocFiles[idx] = null;
        var grid = document.getElementById('lmDocGrid');
        if (!grid) return;
        var thumbs = grid.querySelectorAll('.lm-doc-thumb');
        if (thumbs[idx]) thumbs[idx].remove();
    };

    function lmCompressImageFile(file, maxW, maxH, quality) {
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

    function lmReadTextFile(file) {
        return new Promise(function(resolve) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var text = e.target.result || '';
                var dataUri = 'data:text/plain;base64,' + btoa(unescape(encodeURIComponent(text)));
                resolve(dataUri);
            };
            reader.readAsText(file);
        });
    }

    function lmHandleDocFiles(files) {
        Array.from(files || []).forEach(function(file) {
            if (lmIsImageFile(file)) {
                lmCompressImageFile(file, 1200, 800, 0.65).then(function(dataUri) {
                    lmAddDocThumb(dataUri, file.name, file.size, false);
                });
            } else if (file.type === 'text/plain' || (file.name && file.name.match(/\.(txt|csv|log)$/i))) {
                lmReadTextFile(file).then(function(dataUri) {
                    lmAddDocThumb(dataUri, file.name, file.size, true);
                });
            } else {
                var reader = new FileReader();
                reader.onload = function(e) {
                    lmAddDocThumb(e.target.result, file.name, file.size, false);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    var docInput = document.getElementById('lmDocInput');
    if (docInput) {
        docInput.addEventListener('change', function() {
            lmHandleDocFiles(this.files);
            this.value = '';
        });
    }

    $(document).on('click', '#btnAddDocumentLink', function() {
        $('#lmDocumentLinks').append(
            '<div class="input-group lm-input-group" style="margin-bottom:6px;">' +
                '<input type="url" name="document_links[]" class="form-control lm-input-styled" placeholder="https://...">' +
                '<span class="input-group-btn">' +
                    '<button type="button" class="btn btn-default lm-btn-addon btn-remove-document-link" title="Remove link"><i class="fa fa-times text-danger"></i></button>' +
                '</span>' +
            '</div>'
        );
    });

    $(document).on('click', '.btn-remove-document-link', function() {
        $(this).closest('.input-group').remove();
    });

    document.addEventListener('paste', function(e) {
        var items = e.clipboardData && e.clipboardData.items;
        if (!items) return;
        var handled = false;
        for (var i = 0; i < items.length; i++) {
            if (items[i].type && items[i].type.indexOf('image/') === 0) {
                var file = items[i].getAsFile();
                if (file) {
                    lmCompressImageFile(file, 1200, 800, 0.65).then(function(dataUri) {
                        lmAddDocThumb(dataUri, 'pasted-image-' + Date.now() + '.png', file.size, false);
                    });
                    handled = true;
                }
            }
        }
        if (handled) {
            e.preventDefault();
        }
    });

    function money(value) {
        var n = parseFloat(value || 0);
        return Number.isFinite(n) ? n.toFixed(2) : '0.00';
    }

    function parseNum(v) {
        var n = parseFloat(String(v || '').replace(/,/g, '').trim());
        return Number.isFinite(n) ? n : 0;
    }

    function recalcItemTotals() {
        var total = 0;
        $('#itemsTable tbody tr').each(function(){
            var qty = parseNum($(this).find('.item-qty').val());
            var price = parseNum($(this).find('.item-price').val());
            var lineTotal = Math.round(qty * price * 100) / 100;
            $(this).find('.item-total').text(money(lineTotal));
            total += lineTotal;
        });
        $('#computedPrincipal').text(money(total));
        
        var currentPrincipal = parseNum($('#principal_amount_input').val());
        var downPayment = parseNum($('#payment_amount_input').val());
        var newPrincipal = Math.max(0, total - downPayment);
        
        $('#principal_amount_input').val(newPrincipal > 0 ? newPrincipal.toFixed(2) : (total > 0 ? total.toFixed(2) : ''));
        recalcSummary();
    }

    function recalcSummary() {
        var totalProduct = parseNum($('#computedPrincipal').text());
        var downPayment = parseNum($('#payment_amount_input').val());
        var principalFinanced = parseNum($('#principal_amount_input').val());
        
        if (totalProduct > 0 && (!principalFinanced || principalFinanced === totalProduct)) {
            principalFinanced = Math.max(0, totalProduct - downPayment);
            $('#principal_amount_input').val(principalFinanced > 0 ? principalFinanced.toFixed(2) : '');
        }

        var baseTotal = totalProduct > 0 ? totalProduct : (principalFinanced + downPayment);
        var pct = baseTotal > 0 ? Math.round((downPayment / baseTotal) * 100) : 0;

        $('#summaryTotal').text(money(baseTotal));
        $('#summaryDownPayment').text(money(downPayment));
        $('#summaryDue').text(money(principalFinanced));
        $('#summaryDownPaymentPct').text(pct + '% upfront');
        $('#down_payment_hidden').val(downPayment.toFixed(2));

        // Estimate monthly payment
        var rate = parseNum($('#interest_rate_input').val());
        var months = parseNum($('#duration_months_input').val()) || 12;
        var mode = $('#interest_type_select').val() || 'flat';
        
        var monthlyEst = 0;
        if (months > 0 && principalFinanced > 0) {
            if (mode === 'flat') {
                var totalInterest = principalFinanced * (rate / 100) * (months / 12);
                monthlyEst = (principalFinanced + totalInterest) / months;
            } else {
                var monthlyRate = (rate / 100) / 12;
                if (monthlyRate > 0) {
                    monthlyEst = (principalFinanced * monthlyRate * Math.pow(1 + monthlyRate, months)) / (Math.pow(1 + monthlyRate, months) - 1);
                } else {
                    monthlyEst = principalFinanced / months;
                }
            }
        }
        $('#summaryMonthly').text(money(monthlyEst));
        $('#summaryMonthlyHint').text(months + ' periods @ ' + rate + '% ' + (mode === 'flat' ? 'Flat' : 'Reducing'));
    }

    function addItemRow() {
        var idx = $('#itemsTable tbody tr').length;
        var row = '<tr>' +
            '<td><input type="text" name="items['+idx+'][product_name]" class="form-control lm-input-styled item-name" placeholder="e.g. iPhone 15 Pro 128GB"></td>' +
            '<td><input type="text" name="items['+idx+'][sku]" class="form-control lm-input-styled item-sku" placeholder="SKU-1001"></td>' +
            '<td><input type="text" name="items['+idx+'][imei]" class="form-control lm-input-styled item-imei" placeholder="3528... or Serial"></td>' +
            '<td class="text-center">' +
                '<div class="lm-item-photo-control" style="justify-content:center;">' +
                    '<label class="btn btn-default btn-xs" style="margin:0; border-radius:6px;">' +
                        '<i class="fa fa-camera text-primary"></i>' +
                        '<input type="file" accept="image/*" capture="environment" class="item-photo-input" style="display:none;">' +
                    '</label>' +
                    '<span class="lm-item-photo-thumb"><i class="fa fa-image"></i></span>' +
                '</div>' +
                '<input type="hidden" name="items['+idx+'][product_photo]" class="item-photo-data">' +
                '<span class="lm-item-photo-status"></span>' +
            '</td>' +
            '<td><input type="number" name="items['+idx+'][qty]" class="form-control lm-input-styled item-qty" min="1" value="1" style="text-align:center;"></td>' +
            '<td><input type="number" name="items['+idx+'][unit_price]" class="form-control lm-input-styled item-price" min="0" step="0.01" value="0" style="text-align:right;"></td>' +
            '<td class="item-total text-right" style="font-weight:700; color:#0f172a;">0.00</td>' +
            '<td class="text-center"><button type="button" class="btn btn-xs btn-danger btn-remove-item" style="border-radius:6px;"><i class="fa fa-trash"></i></button></td>' +
            '</tr>';
        $('#itemsTable tbody').append(row);
    }

    function setItemPhoto($row, dataUri) {
        $row.find('.item-photo-data').val(dataUri || '');
        var $thumb = $row.find('.lm-item-photo-thumb');
        if (dataUri) {
            $thumb.html('<img src="' + dataUri + '" alt="">');
            $row.find('.lm-item-photo-status').text('Ready');
        } else {
            $thumb.html('<i class="fa fa-image"></i>');
            $row.find('.lm-item-photo-status').text('');
        }
    }

    function compressImage(file, maxW, maxH, quality) {
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

    function applyIdCardImage(dataUri) {
        idCardImageData = dataUri;
        $('#customer_id_card_photo_preview img').attr('src', dataUri);
        $('#customer_id_card_photo_preview').show();
        $('#customer_info_fields').slideDown();
        scanIdCard(dataUri);
    }

    function setIdCropStatus(message, isError) {
        $('#lmIdCardCropStatus').text(message || '').css('color', isError ? '#ef4444' : '#64748b');
    }

    function showIdCropOverlay() {
        $('#lmIdCardCropOverlay').css('display', 'flex').attr('aria-hidden', 'false');
    }

    function hideIdCropOverlay() {
        $('#lmIdCardCropOverlay').hide().attr('aria-hidden', 'true');
    }

    function cancelIdCrop() {
        idCardCropper = null;
        idCardCropFile = null;
        hideIdCropOverlay();
        $('#customer_id_card_photo_input').val('');
        $('#customer_id_card_camera_input').val('');
    }

    function startIdCardCrop(file) {
        idCardCropFile = file;
        idCardCropper = null;
        showIdCropOverlay();
        setIdCropStatus('Preparing photo for crop...');

        if (!window.FileReader) {
            useOriginalIdPhoto();
            return;
        }

        var reader = new FileReader();
        var image = new Image();
        reader.onload = function(event) {
            image.onload = function() {
                idCardCropper = createIdCardCropper(document.getElementById('lmIdCardCropCanvas'), image);
                setIdCropStatus('Drag the box or corners to keep only the ID card.');
            };
            image.onerror = function() {
                setIdCropStatus('This browser cannot preview this image. Using original photo.', true);
                useOriginalIdPhoto();
            };
            image.src = event.target.result;
        };
        reader.onerror = function() {
            setIdCropStatus('This browser cannot preview this image. Using original photo.', true);
            useOriginalIdPhoto();
        };
        reader.readAsDataURL(file);
    }

    function useOriginalIdPhoto() {
        if (!idCardCropFile) {
            cancelIdCrop();
            return;
        }

        var file = idCardCropFile;
        cancelIdCrop();
        setOcrStatus('Preparing ID card photo...');
        compressImage(file, 1600, 1000, 0.76).then(applyIdCardImage);
    }

    function useCroppedIdPhoto() {
        if (!idCardCropper) {
            useOriginalIdPhoto();
            return;
        }

        setIdCropStatus('Cropping photo...');
        idCardCropper.getDataUrl(function(dataUri) {
            cancelIdCrop();
            setOcrStatus('Preparing cropped ID card photo...');
            applyIdCardImage(dataUri);
        });
    }

    function createIdCardCropper(canvas, image) {
        var context = canvas.getContext('2d');
        var maxWidth = Math.min(820, image.width);
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
            crop = {
                x: Math.round(canvasWidth * 0.05),
                y: Math.round(canvasHeight * 0.08),
                width: Math.round(canvasWidth * 0.90),
                height: Math.round(canvasHeight * 0.78)
            };
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

        function pointFromEvent(event) {
            var source = event.touches && event.touches.length ? event.touches[0] : event;
            var rect = canvas.getBoundingClientRect();
            return {
                x: (source.clientX - rect.left) * (canvas.width / rect.width),
                y: (source.clientY - rect.top) * (canvas.height / rect.height)
            };
        }

        function dragModeFor(point) {
            var handles = {
                nw: {x: crop.x, y: crop.y},
                ne: {x: crop.x + crop.width, y: crop.y},
                sw: {x: crop.x, y: crop.y + crop.height},
                se: {x: crop.x + crop.width, y: crop.y + crop.height}
            };
            for (var mode in handles) {
                if (Math.abs(point.x - handles[mode].x) <= handleSize && Math.abs(point.y - handles[mode].y) <= handleSize) {
                    return mode;
                }
            }
            return point.x >= crop.x && point.x <= crop.x + crop.width && point.y >= crop.y && point.y <= crop.y + crop.height ? 'move' : null;
        }

        function constrain() {
            var minSize = 50;
            crop.width = Math.max(minSize, crop.width);
            crop.height = Math.max(minSize, crop.height);
            crop.x = Math.max(0, Math.min(crop.x, canvasWidth - crop.width));
            crop.y = Math.max(0, Math.min(crop.y, canvasHeight - crop.height));
            if (crop.x + crop.width > canvasWidth) crop.width = canvasWidth - crop.x;
            if (crop.y + crop.height > canvasHeight) crop.height = canvasHeight - crop.y;
        }

        function resize(mode, dx, dy) {
            if (mode.indexOf('n') !== -1) { crop.y += dy; crop.height -= dy; }
            if (mode.indexOf('s') !== -1) crop.height += dy;
            if (mode.indexOf('w') !== -1) { crop.x += dx; crop.width -= dx; }
            if (mode.indexOf('e') !== -1) crop.width += dx;
        }

        function start(event) {
            var point = pointFromEvent(event);
            dragMode = dragModeFor(point);
            lastPoint = point;
            if (dragMode) event.preventDefault();
        }

        function move(event) {
            if (!dragMode) return;
            var point = pointFromEvent(event);
            var dx = point.x - lastPoint.x;
            var dy = point.y - lastPoint.y;
            if (dragMode === 'move') {
                crop.x += dx;
                crop.y += dy;
            } else {
                resize(dragMode, dx, dy);
            }
            constrain();
            lastPoint = point;
            draw();
            event.preventDefault();
        }

        function end() {
            dragMode = null;
            lastPoint = null;
        }

        canvas.onmousedown = start;
        canvas.onmousemove = move;
        canvas.onmouseup = end;
        canvas.onmouseleave = end;
        canvas.ontouchstart = start;
        canvas.ontouchmove = move;
        canvas.ontouchend = end;
        reset();

        return {
            reset: reset,
            getDataUrl: function(callback) {
                var cropWidth = Math.round(crop.width / scale);
                var cropHeight = Math.round(crop.height / scale);
                var outputScale = Math.min(1, 1800 / Math.max(cropWidth, cropHeight));
                var output = document.createElement('canvas');
                output.width = Math.max(1, Math.round(cropWidth * outputScale));
                output.height = Math.max(1, Math.round(cropHeight * outputScale));
                output.getContext('2d').drawImage(image, crop.x / scale, crop.y / scale, crop.width / scale, crop.height / scale, 0, 0, output.width, output.height);
                callback(output.toDataURL('image/jpeg', 0.9));
            }
        };
    }

    function setOcrStatus(message, isError) {
        $('#id_card_ocr_status').text(message || '').css('color', isError ? '#ef4444' : '#2563eb');
    }

    function fillIfEmpty(selector, value) {
        if (value && !String($(selector).val() || '').trim()) {
            $(selector).val(value).trigger('change');
        }
    }

    function applyIdCardFields(fields, rawText) {
        fields = fields || {};
        $('#id_card_ocr_raw_text_input').val(rawText || '');
        $('#id_card_ocr_number_input').val(fields.id_card_number || '');
        $('#id_card_ocr_khmer_name_input').val(fields.khmer_name || '');
        $('#id_card_ocr_english_name_input').val(fields.english_name || '');
        $('#id_card_ocr_address_input').val(fields.address || '');
        fillIfEmpty('#customer_id_card_input', fields.id_card_number);
        fillIfEmpty('#customer_khmer_name_input', fields.khmer_name);
        fillIfEmpty('#customer_english_name_input', fields.english_name);
        fillIfEmpty('#customer_address_input', fields.address);
        $('#customer_name_input').val($('#customer_khmer_name_input').val() || $('#customer_english_name_input').val() || '');
    }

    function scanIdCard(dataUri) {
        setOcrStatus('Reading ID card with OCR...');
        $.ajax({
            url: urls.scanIdCard,
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                id_card_image: dataUri
            },
            success: function(res) {
                if (res && res.success) {
                    var data = res.data || {};
                    applyIdCardFields(data.fields || {}, data.raw_text || '');
                    setOcrStatus(Object.keys(data.fields || {}).length ? 'ID card auto-filled successfully.' : 'OCR complete.');
                } else {
                    setOcrStatus((res && res.message) || 'OCR unavailable.', true);
                }
            },
            error: function(xhr) {
                setOcrStatus(xhr.responseJSON?.message || 'OCR failed.', true);
            }
        });
    }

    $(document).on('click', '#btnAddItem', function(){ addItemRow(); });
    $(document).on('click', '.btn-remove-item', function(){
        $(this).closest('tr').remove();
        recalcItemTotals();
    });
    $(document).on('input change', '.item-qty, .item-price', function(){ recalcItemTotals(); });
    $(document).on('change', '.item-photo-input', function(){
        var input = this;
        var file = input.files && input.files[0];
        var $row = $(input).closest('tr');
        if (!file) return;

        $row.find('.lm-item-photo-status').text('Preparing photo...');
        compressImage(file, 1200, 900, 0.75).then(function(dataUri) {
            setItemPhoto($row, dataUri);
            input.value = '';
        });
    });

    $('#payment_amount_input, #principal_amount_input, #interest_rate_input, #duration_months_input, #interest_type_select').on('input change', recalcSummary);

    // Customer search
    $('#customerSearchInput').on('input', function(){
        var q = $(this).val().trim();
        clearTimeout(searchTimer);
        if (q.length < 2) {
            $('.lm-customer-search-results').hide();
            return;
        }
        searchTimer = setTimeout(function(){
            $.get(urls.searchCustomers, {q: q}, function(res){
                var $box = $('.lm-customer-search-results');
                $box.empty();
                if (res.data && res.data.length) {
                    res.data.forEach(function(c){
                        var primaryName = (c.khmer_name || c.name || '');
                        var secondaryName = (c.name && c.name !== primaryName) ? c.name : '';
                        $box.append(
                            '<div class="lm-cs-item" data-id="'+c.id+'" data-name="'+(c.name||'')+'" data-khmer-name="'+(c.khmer_name||'')+'" data-phone="'+(c.phone||'')+'" data-alternate-phone="'+(c.alternate_phone||'')+'" data-address="'+(c.address||'')+'" data-idcard="'+(c.id_card_number||'')+'">' +
                            '<div class="lm-cs-name">'+primaryName+'</div>' +
                            (secondaryName ? '<div class="lm-cs-phone">'+secondaryName+'</div>' : '') +
                            '<div class="lm-cs-phone">'+(c.phone||'')+' '+(c.customer_code||'')+'</div>' +
                            '</div>'
                        );
                    });
                    $box.show();
                } else {
                    $box.hide();
                }
            });
        }, 300);
    });

    $(document).on('click', '.lm-cs-item', function(){
        var $item = $(this);
        $('#customer_id_input').val($item.data('id'));
        $('#customer_name_input').val($item.data('khmer-name') || $item.data('name'));
        $('#customer_english_name_input').val($item.data('name'));
        $('#customer_khmer_name_input').val($item.data('khmer-name'));
        $('#customer_phone_input').val($item.data('phone'));
        $('#alternate_phone_input').val($item.data('alternate-phone'));
        $('#alternate_phone_group').toggle(!!String($item.data('alternate-phone') || '').trim());
        $('#customer_address_input').val($item.data('address'));
        $('#customer_id_card_input').val($item.data('idcard'));
        $('#customer_info_fields').slideDown();
        $('.lm-customer-search-results').hide();
        $('#customerSearchInput').val('');
    });

    $(document).on('click', function(e){
        if (!$(e.target).closest('.lm-customer-search-wrap').length) {
            $('.lm-customer-search-results').hide();
        }
    });

    $('#btnClearCustomer').on('click', function(){
        $('#customer_id_input').val('');
        $('#customer_name_input').val('');
        $('#customer_english_name_input').val('');
        $('#customer_khmer_name_input').val('');
        $('#customer_phone_input').val('');
        $('#alternate_phone_input').val('');
        $('#alternate_phone_group').hide();
        $('#customer_address_input').val('');
        $('#customer_id_card_input').val('');
        $('#customer_id_card_photo_preview').hide();
        idCardImageData = '';
        $('#id_card_ocr_status').text('');
    });

    $('#btnShowAlternatePhone').on('click', function(){
        $('#alternate_phone_group').slideDown();
        $('#alternate_phone_input').focus();
    });

    $('#customer_id_card_photo_input, #customer_id_card_camera_input').on('change', function(){
        var file = this.files && this.files[0];
        if (!file) return;
        startIdCardCrop(file);
    });

    $('#btnCancelIdCrop').on('click', cancelIdCrop);
    $('#btnUseOriginalIdPhoto').on('click', useOriginalIdPhoto);
    $('#btnUseCroppedIdPhoto').on('click', useCroppedIdPhoto);
    $('#btnResetIdCrop').on('click', function(){
        if (idCardCropper) {
            idCardCropper.reset();
            setIdCropStatus('Crop reset. Drag the box or corners to adjust.');
        }
    });

    // Toggle guide banner
    $('#btnToggleGuide').on('click', function(){
        $('#lmGuideContent').slideToggle(200, function(){
            var isVisible = $(this).is(':visible');
            $('#guideToggleText').text(isVisible ? 'Hide Guide' : 'Show Guide');
            $('#btnToggleGuide i').toggleClass('fa-chevron-up fa-chevron-down');
        });
    });

    // Quick suggestion presets
    $('.js-quick-plan').on('click', function(){
        var rate = $(this).data('rate');
        var mode = $(this).data('mode');
        var months = $(this).data('months');
        var downPct = $(this).data('down-pct');

        $('#interest_rate_input').val(rate).trigger('change');
        $('#interest_type_select').val(mode).trigger('change');
        $('#duration_months_input').val(months).trigger('change');

        // Apply down payment %
        var totalProduct = parseNum($('#computedPrincipal').text());
        if (totalProduct > 0) {
            var downAmt = Math.round(totalProduct * (downPct / 100) * 100) / 100;
            $('#payment_amount_input').val(downAmt).trigger('change');
        }

        // Highlight preset buttons
        $('#interestRatePresets .lm-preset-btn').removeClass('active').filter('[data-val="'+rate+'"]').addClass('active');
        $('#durationPresets .lm-preset-btn').removeClass('active').filter('[data-val="'+months+'"]').addClass('active');
        $('#downPaymentPercentPresets .lm-preset-btn').removeClass('active').filter('[data-pct="'+downPct+'"]').addClass('active');
        
        recalcSummary();
    });

    // Preset button clicks
    $('#durationPresets').on('click', '.lm-preset-btn', function(){
        $('#durationPresets .lm-preset-btn').removeClass('active');
        $(this).addClass('active');
        $('#duration_months_input').val($(this).data('val')).trigger('change');
    });

    $('#interestRatePresets').on('click', '.lm-preset-btn', function(){
        $('#interestRatePresets .lm-preset-btn').removeClass('active');
        $(this).addClass('active');
        $('#interest_rate_input').val($(this).data('val')).trigger('change');
    });

    $('#downPaymentPercentPresets').on('click', '.lm-preset-btn', function(){
        $('#downPaymentPercentPresets .lm-preset-btn').removeClass('active');
        $(this).addClass('active');
        var pct = parseNum($(this).data('pct'));
        var totalProduct = parseNum($('#computedPrincipal').text()) || parseNum($('#principal_amount_input').val());
        var downAmt = Math.round(totalProduct * (pct / 100) * 100) / 100;
        $('#payment_amount_input').val(downAmt).trigger('change');
    });

    // Stepper smooth scroll
    $('.lm-step-nav-item').on('click', function(e){
        var target = $(this).attr('href');
        if (target && target.startsWith('#') && $(target).length) {
            e.preventDefault();
            $('.lm-step-nav-item').removeClass('active');
            $(this).addClass('active');
            $('html, body').animate({
                scrollTop: $(target).offset().top - 90
            }, 300);
        }
    });

    // Preview Schedule
    function doPreviewSchedule() {
        var form = $('#standaloneLoanForm');
        $('#customer_name_input').val($('#customer_khmer_name_input').val() || $('#customer_english_name_input').val() || '');
        
        var $btn = $('#btnPreviewSchedule, #btnPreviewScheduleTop');
        $btn.prop('disabled', true);

        $.post(urls.previewSchedule, form.serialize(), function(res){
            var rows = res.data || [];
            var $tb = $('#schedulePreviewTable tbody');
            var $table = $tb.closest('table');
            var totalP = 0, totalI = 0, totalA = 0, totalB = 0;
            $tb.empty();
            
            if (rows.length === 0) {
                $tb.append('<tr><td colspan="6" class="text-center text-muted">No schedule rows generated</td></tr>');
            } else {
                rows.forEach(function(r){
                    totalP += Number(r.principal || 0);
                    totalI += Number(r.interest || 0);
                    totalA += Number(r.total || 0);
                    totalB += Number(r.balance || 0);
                    $tb.append('<tr><td class="text-center" style="font-weight:700;">'+r.schedule_no+'</td><td>'+r.due_date+'</td><td class="text-right">'+money(r.principal)+'</td><td class="text-right">'+money(r.interest)+'</td><td class="text-right" style="font-weight:700; color:#0f172a;">'+money(r.total)+'</td><td class="text-right">'+money(r.balance)+'</td></tr>');
                });
            }

            $table.find('tfoot th').eq(1).text(totalP.toFixed(2));
            $table.find('tfoot th').eq(2).text(totalI.toFixed(2));
            $table.find('tfoot th').eq(3).text(totalA.toFixed(2));
            $table.find('tfoot th').eq(4).text(totalB.toFixed(2));

            // Scroll to schedule
            $('html, body').animate({
                scrollTop: $('#sectionSchedule').offset().top - 90
            }, 300);
        }).fail(function(xhr){
            alert(xhr.responseJSON?.message || 'Failed to preview schedule');
        }).always(function(){
            $btn.prop('disabled', false);
        });
    }

    $('#btnPreviewSchedule, #btnPreviewScheduleTop').on('click', doPreviewSchedule);

    $('#btnCreateLoan').on('click', function(){
        $('#standaloneLoanForm').find('input[name="action_type"]').val($(this).data('action'));
        $('#standaloneLoanForm').trigger('submit');
    });

    $('#standaloneLoanForm').on('submit', function(e){
        e.preventDefault();
        var $form = $(this);
        var $buttons = $('#btnCreateLoan');
        $('#customer_name_input').val($('#customer_khmer_name_input').val() || $('#customer_english_name_input').val() || '');
        if (this.checkValidity && ! this.checkValidity()) {
            this.reportValidity();
            return;
        }
        var fd = new FormData(this);
        if (idCardImageData) {
            fd.append('id_card_image', idCardImageData);
        }
        lmDocFiles.forEach(function(d, i) { if (d) fd.append('documents[]', d.dataUri); });
        $buttons.prop('disabled', true);
        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function(res){
                if (window.toastr) {
                    toastr.success(res.message || 'Installment created successfully');
                } else {
                    alert(res.message || 'Installment created successfully');
                }
                if (res?.data?.loan_id) {
                    window.location.href = urls.loanList + '/' + res.data.loan_id + '/view';
                }
            },
            error: function(xhr){
                if (xhr.status === 422 && xhr.responseJSON?.errors) {
                    var errors = xhr.responseJSON.errors;
                    var firstKey = Object.keys(errors)[0];
                    alert(errors[firstKey][0] || xhr.responseJSON?.message || 'Validation failed');
                } else {
                    alert(xhr.responseJSON?.message || 'Failed to create loan');
                }
            },
            complete: function(){ $buttons.prop('disabled', false); }
        });
    });

    if ($.fn.select2) {
        $('.select2').select2();
    }

    addItemRow();
    recalcSummary();
})(jQuery);
</script>
@endsection
