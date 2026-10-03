@extends('loanmanagement::layouts.app')
@section('title', 'Dashboard')

@section('loan_css')
<style>
    /* ========================================================
       ENTERPRISE FINTECH DASHBOARD - MODERN EXECUTIVE REDESIGN
       Matching Business Settings & Payment Methods Design System
       ======================================================== */
    .lm-dashboard {
        display: flex;
        flex-direction: column;
        gap: 22px;
        color: #0f172a;
        padding-bottom: 50px;
    }

    /* Executive Command Header */
    .lm-dashboard-command {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 24px 28px;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 22px;
        position: relative;
        overflow: hidden;
    }
    .lm-dashboard-command::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--lm-primary, #6366f1) 0%, #38bdf8 50%, #10b981 100%);
    }
    .lm-dashboard-command__main {
        max-width: 660px;
    }
    .lm-dashboard-command__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--lm-primary, #6366f1);
        margin-bottom: 6px;
    }
    .lm-dashboard-command__title {
        margin: 0;
        font-size: 26px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.02em;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .lm-dashboard-command__subtitle {
        margin: 6px 0 0;
        font-size: 13.5px;
        color: #64748b;
        line-height: 1.5;
    }
    .lm-dashboard-command__chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }
    .lm-dashboard-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        background: #f8fafc;
        color: #334155;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 2px rgba(15,23,42,0.03);
        transition: all 0.15s ease;
    }
    .lm-dashboard-chip i { font-size: 13px; color: var(--lm-primary, #6366f1); }
    .lm-dashboard-chip--success { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .lm-dashboard-chip--success i { color: #059669; }
    .lm-dashboard-chip--warning { background: #fffbeb; color: #b45309; border-color: #fde68a; }
    .lm-dashboard-chip--warning i { color: #d97706; }
    .lm-dashboard-chip--danger { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    .lm-dashboard-chip--danger i { color: #dc2626; }

    /* Segmented Tab Switcher */
    .lm-dashboard-tabs {
        display: inline-flex;
        align-items: center;
        background: #f1f5f9;
        padding: 4px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        gap: 4px;
        margin-top: 14px;
    }
    .lm-dashboard-tab {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 16px;
        border-radius: 8px;
        border: none;
        background: transparent;
        color: #64748b;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .lm-dashboard-tab:hover {
        color: #0f172a;
    }
    .lm-dashboard-tab.is-active {
        background: #ffffff;
        color: var(--lm-primary, #6366f1);
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
    }

    /* Pane visibility */
    .lm-dashboard-pane {
        display: none !important;
    }
    .lm-dashboard-pane.is-active {
        display: block !important;
    }

    /* Action Launcher Buttons */
    .lm-dashboard-command__actions {
        display: grid;
        grid-template-columns: repeat(3, minmax(136px, 1fr));
        gap: 10px;
    }
    .lm-dashboard-action {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #334155;
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
    }
    .lm-dashboard-action:hover {
        background: #ffffff;
        color: #0f172a;
        text-decoration: none;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px -4px rgba(15, 23, 42, 0.1);
        border-color: #cbd5e1;
    }
    .lm-dashboard-action span {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        flex-shrink: 0;
        transition: transform 0.2s ease;
    }
    .lm-dashboard-action:hover span {
        transform: scale(1.1);
    }
    .lm-dashboard-action strong {
        font-size: 12.5px;
        font-weight: 700;
        line-height: 1.25;
    }
    .lm-dashboard-action--primary span { background: #e0e7ff; color: #4338ca; }
    .lm-dashboard-action--success span { background: #dcfce7; color: #15803d; }
    .lm-dashboard-action--danger span { background: #fee2e2; color: #b91c1c; }
    .lm-dashboard-action--info span { background: #e0f2fe; color: #0369a1; }
    .lm-dashboard-action--neutral span { background: #f1f5f9; color: #475569; }

    /* Summary KPI Strip */
    .lm-dashboard-summary-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }
    .lm-dashboard-summary-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px 20px;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: all 0.2s ease;
    }
    .lm-dashboard-summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -4px rgba(15, 23, 42, 0.08);
        border-color: #cbd5e1;
    }
    .lm-dashboard-summary-card__icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .lm-dashboard-summary-card__icon.lm-tone-green { background: #dcfce7; color: #15803d; }
    .lm-dashboard-summary-card__icon.lm-tone-blue { background: #dbeafe; color: #1d4ed8; }
    .lm-dashboard-summary-card__icon.lm-tone-amber { background: #fef3c7; color: #b45309; }
    .lm-dashboard-summary-card__icon.lm-tone-slate { background: #f1f5f9; color: #475569; }

    .lm-dashboard-summary-card__label {
        font-size: 11.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        margin-bottom: 2px;
    }
    .lm-dashboard-summary-card__value {
        font-size: 24px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.15;
    }

    /* 6 Primary Stat Cards Grid */
    .lm-dashboard-cards {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 20px;
    }
    .lm-stat-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 16px 18px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 126px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        color: inherit;
        position: relative;
        overflow: hidden;
    }
    .lm-stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 24px -4px rgba(15, 23, 42, 0.09);
        border-color: #cbd5e1;
        text-decoration: none;
        color: inherit;
    }
    .lm-stat-card__icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
        margin-bottom: 10px;
    }
    .lm-stat-card__icon.lm-tone-slate { background: #f1f5f9; color: #475569; }
    .lm-stat-card__icon.lm-tone-amber { background: #fef3c7; color: #b45309; }
    .lm-stat-card__icon.lm-tone-blue { background: #dbeafe; color: #1d4ed8; }
    .lm-stat-card__icon.lm-tone-red { background: #fee2e2; color: #b91c1c; }

    .lm-stat-card__label {
        font-size: 11.5px;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .lm-stat-card__value {
        font-size: 26px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1.1;
    }
    .lm-stat-card__meta {
        font-size: 11px;
        font-weight: 700;
        color: var(--lm-primary, #6366f1);
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 8px;
        transition: gap 0.15s ease;
    }
    .lm-stat-card:hover .lm-stat-card__meta {
        gap: 7px;
    }

    /* Grid Layout for Panels */
    .lm-dashboard-grid {
        display: grid;
        grid-template-columns: 1.22fr 0.78fr;
        gap: 20px;
        align-items: start;
        margin-bottom: 20px;
    }
    .lm-side-stack {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    /* Panels & Containers */
    .lm-dashboard-panel {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
        overflow: hidden;
        margin-bottom: 0;
    }
    .lm-dashboard-panel__header {
        padding: 18px 22px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: wrap;
    }
    .lm-dashboard-panel__title {
        margin: 0;
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
        letter-spacing: -0.01em;
        display: flex;
        align-items: center;
    }
    .lm-dashboard-panel__hint {
        margin: 2px 0 0;
        font-size: 12px;
        color: #64748b;
    }
    .lm-dashboard-panel__badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
        background: #f1f5f9;
        color: #334155;
    }
    .lm-dashboard-panel__badge--danger {
        background: #fef2f2;
        color: #b91c1c;
    }
    .lm-dashboard-panel__body {
        padding: 20px 22px;
    }

    /* Quick Payment Collect Box */
    .lm-quick-box--loan {
        background: transparent;
    }
    .lm-quick-box__topline {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 6px;
    }
    .lm-quick-box__title {
        margin: 0;
        font-size: 17px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .lm-quick-box__icon--pay {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #dcfce7;
        color: #15803d;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
    }
    .lm-quick-box__meta {
        display: flex;
        align-items: center;
        gap: 6px;
        flex-wrap: wrap;
    }
    .lm-quick-box__chip--pay {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        padding: 4px 10px;
        font-size: 11.5px;
        font-weight: 700;
        color: #475569;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .lm-quick-box__subtitle {
        color: #64748b;
        font-size: 13px;
        margin-top: 4px;
        margin-bottom: 16px;
    }
    .lm-quick-box__footer {
        margin-top: 14px;
        padding-top: 12px;
        border-top: 1px dashed #e2e8f0;
        color: #64748b;
        font-size: 12px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Inputs inside Dashboard */
    .lm-quick-filter-row {
        display: flex;
        gap: 12px;
        margin-bottom: 16px;
    }
    .lm-quick-input--search { flex: 1.5; margin-bottom: 0; }
    .lm-quick-input--location { flex: 1; margin-bottom: 0; }
    .lm-quick-filter-row .input-group-addon {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #64748b;
        border-radius: 8px 0 0 8px;
    }
    .lm-quick-filter-row .form-control {
        border-color: #cbd5e1;
        border-radius: 0 8px 8px 0;
        height: 42px;
        font-size: 13.5px;
    }
    .lm-quick-filter-row .form-control:focus {
        border-color: var(--lm-primary, #6366f1);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    /* Overdue Search */
    .lm-overdue-search {
        margin-bottom: 14px;
    }
    .lm-overdue-search .input-group-addon {
        background: #f8fafc;
        border-color: #cbd5e1;
        color: #64748b;
        border-radius: 8px 0 0 8px;
    }
    .lm-overdue-search .form-control {
        border-color: #cbd5e1;
        border-radius: 0 8px 8px 0;
        height: 38px;
        font-size: 13px;
    }
    .lm-overdue-search .form-control:focus {
        border-color: var(--lm-primary, #6366f1);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
    }

    /* Tables */
    .lm-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 10px;
    }
    .lm-dashboard-table {
        margin-bottom: 0;
        width: 100%;
    }
    .lm-dashboard-table > thead > tr > th {
        background: #f8fafc;
        color: #475569;
        font-size: 11.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        border-bottom: 1px solid #e2e8f0;
        padding: 11px 14px;
        white-space: nowrap;
    }
    .lm-dashboard-table > tbody > tr > td {
        vertical-align: middle;
        padding: 12px 14px;
        border-top: 1px solid #f1f5f9;
        font-size: 13px;
        color: #334155;
    }
    .lm-dashboard-table > tbody > tr:hover {
        background: #f8fafc;
    }

    /* Customer Profile in Table */
    .lm-customer-profile {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .lm-customer-profile__avatar {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
        color: #4338ca;
        font-weight: 800;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        overflow: hidden;
    }
    .lm-customer-profile__avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .lm-row-title {
        font-weight: 700;
        color: #0f172a;
        text-decoration: none;
        display: block;
        font-size: 13.5px;
    }
    .lm-row-title:hover {
        color: var(--lm-primary, #6366f1);
        text-decoration: underline;
    }
    .lm-row-subtitle {
        font-size: 11.5px;
        color: #64748b;
        display: block;
        margin-top: 1px;
    }

    /* Buttons inside Dashboard */
    .lm-dashboard .btn-success {
        background-color: #10b981;
        border-color: #059669;
        font-weight: 700;
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(16, 185, 129, 0.2);
    }
    .lm-dashboard .btn-success:hover {
        background-color: #059669;
        border-color: #047857;
    }
    .lm-dashboard .btn-primary {
        background-color: var(--lm-primary, #6366f1);
        border-color: #4f46e5;
        font-weight: 700;
        border-radius: 8px;
    }
    .lm-dashboard .btn-default {
        border-color: #cbd5e1;
        color: #334155;
        font-weight: 700;
        border-radius: 8px;
        background: #ffffff;
    }
    .lm-dashboard .btn-default:hover {
        background: #f8fafc;
        color: #0f172a;
    }

    /* Status Snapshot Chart Shell */
    .lm-chart-shell {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
    }
    .lm-chart-copy strong {
        display: block;
        font-size: 14px;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 4px;
    }
    .lm-chart-copy small {
        font-size: 12px;
        color: #64748b;
    }

    /* Live Chat Shell */
    .lm-live-chat-shell {
        display: grid;
        grid-template-columns: 300px minmax(0,1fr) 280px;
        min-height: 68vh;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        overflow: hidden;
        background: #fff;
        box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
    }
    .lm-live-chat-inbox {
        display: flex;
        flex-direction: column;
        min-height: 0;
        border-right: 1px solid #f1f5f9;
        background: #ffffff;
    }
    .lm-live-chat-toolbar {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
    }
    .lm-live-chat-toolbar h4 {
        margin: 0 0 10px;
        color: #0f172a;
        font-size: 18px;
        font-weight: 800;
    }
    .lm-live-chat-search {
        width: 100%;
        height: 38px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 0 14px;
        outline: none;
        background: #f8fafc;
        font-size: 13px;
        transition: all 0.15s ease;
    }
    .lm-live-chat-search:focus {
        border-color: var(--lm-primary, #6366f1);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        background: #ffffff;
    }
    .lm-live-chat-list {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 8px;
    }
    .lm-live-chat-main {
        display: flex;
        flex-direction: column;
        min-height: 0;
        background: #f8fafc;
        border-right: 1px solid #f1f5f9;
    }
    .lm-live-chat-mainbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 14px 18px;
        border-bottom: 1px solid #e2e8f0;
        background: #ffffff;
    }
    .lm-live-chat-main-title {
        margin: 0;
        color: #0f172a;
        font-size: 16px;
        font-weight: 800;
    }
    .lm-live-chat-main-subtitle {
        margin: 3px 0 0;
        color: #64748b;
        font-size: 12px;
    }
    .lm-live-chat-frame {
        flex: 1 1 auto;
        min-height: 0;
        width: 100%;
        border: 0;
        background: #ffffff;
    }
    .lm-live-chat-side {
        padding: 18px;
        background: #ffffff;
        overflow-y: auto;
    }
    .lm-live-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        background: #ecfdf5;
        color: #047857;
        font-size: 11.5px;
        font-weight: 700;
        border: 1px solid #a7f3d0;
    }
    .lm-live-badge__dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #10b981;
    }

    @media (max-width: 1280px) {
        .lm-dashboard-cards { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .lm-dashboard-summary-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .lm-dashboard-command__actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .lm-dashboard-grid { grid-template-columns: 1fr; }
        .lm-live-chat-shell { grid-template-columns: 260px minmax(0,1fr); }
        .lm-live-chat-side { display: none; }
    }
    @media (max-width: 768px) {
        .lm-dashboard-cards { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .lm-dashboard-summary-strip { grid-template-columns: 1fr; }
        .lm-dashboard-command { flex-direction: column; align-items: stretch; }
        .lm-dashboard-command__actions { grid-template-columns: 1fr; }
        .lm-quick-filter-row { flex-direction: column; }
        .lm-live-chat-shell { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content_body')
    @include('loanmanagement::dashboard.dashboard')
@endsection
