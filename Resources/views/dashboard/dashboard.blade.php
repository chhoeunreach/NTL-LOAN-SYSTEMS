@php
    $cards = [
        ['key' => 'due_today', 'label' => 'Due Today', 'icon' => 'fa fa-calendar-check-o', 'tone' => 'blue', 'url' => route('loan-management.operations.page', ['page' => 'due-today'])],
        ['key' => 'overdue_accounts', 'label' => 'Overdue Accounts', 'icon' => 'fa fa-exclamation-triangle', 'tone' => 'red', 'url' => route('loan-management.collection.page', ['page' => 'overdue-accounts'])],
        ['key' => 'broken_ptp', 'label' => 'Broken PTP', 'icon' => 'fa fa-chain-broken', 'tone' => 'amber', 'url' => route('loan-management.collection.page', ['page' => 'broken-promise'])],
        ['key' => 'collection_amount_today', 'label' => 'Collection Amount Today', 'icon' => 'fa fa-dollar', 'tone' => 'green', 'url' => route('loan-management.payments.index', ['date_from' => now()->toDateString(), 'date_to' => now()->toDateString()])],
    ];
    $dashboardBadgeCounts = \Modules\LoanManagement\Helpers\LoanMenuHelper::badgeCounts();
    $dashboardUnreadChats = (int) ($dashboardBadgeCounts['unread_chat'] ?? 0);
    $dashboardPendingVisits = (int) ($dashboardBadgeCounts['pending_visits'] ?? 0);
    $dashboardOverdue = (int) ($quickCards['overdue_accounts'] ?? 0);
    $dashboardDueToday = (int) ($quickCards['due_today'] ?? 0);
    $dashboardBrokenPtp = (int) ($quickCards['broken_ptp'] ?? 0);
    $dashboardHighRisk = (int) ($quickCards['high_risk_customers'] ?? 0);
    $dashboardTodayCollection = (float) ($quickCards['today_collection'] ?? ($quickCards['collection_amount_today'] ?? 0));
    $dashboardMonthlyIncome = (float) ($quickCards['monthly_income'] ?? 0);
    $dashboardPriorityTotal = $dashboardOverdue + $dashboardDueToday + $dashboardBrokenPtp + $dashboardHighRisk + $dashboardPendingVisits + $dashboardUnreadChats;
@endphp

<div class="lm-dashboard">
    <div class="lm-dashboard-pane is-active" data-dashboard-pane="overview">
    <section class="lm-dashboard-cards">
        @foreach($cards as $card)
            @php $val = $quickCards[$card['key']] ?? 0; @endphp
            <a href="{{ $card['url'] }}" class="lm-stat-card lm-stat-card--link">
                <span class="lm-stat-card__icon lm-tone-{{ $card['tone'] }}"><i class="{{ $card['icon'] }}"></i></span>
                <div>
                    <span class="lm-stat-card__label">{{ $card['label'] }}</span>
                    <span class="lm-stat-card__value" data-loan-card="{{ $card['key'] }}" data-format="{{ in_array($card['key'], ['collection_amount_today']) ? 'money' : 'int' }}">{{ in_array($card['key'], ['collection_amount_today']) ? number_format((float) $val, 2) : (int) $val }}</span>
                    <span class="lm-stat-card__meta">Click to view details</span>
                </div>
            </a>
        @endforeach
    </section>

    <section class="lm-dashboard-grid">
        <div class="lm-dashboard-panel lm-dashboard-panel--feature lm-dashboard-panel--quick-payment">
            <div class="lm-dashboard-panel__header">
                <div>
                    <h3 class="lm-dashboard-panel__title">Quick Actions</h3>
                    <p class="lm-dashboard-panel__hint">Search loans, collect payment, create new loans.</p>
                </div>
                <div class="lm-dashboard-panel__actions">
                    <span class="lm-dashboard-panel__badge"><i class="fa fa-bolt"></i> 2 smart tools</span>
                    <a href="{{ route('loan-management.settings.cms') }}" class="btn btn-default btn-sm">
                        <i class="fa fa-newspaper-o"></i> CMS Manager
                    </a>
                </div>
            </div>
            <div class="lm-dashboard-panel__body lm-dashboard-panel__body--quick-actions">
                <div class="lm-quick-grid">
                    <div class="lm-quick-box lm-quick-box--loan">
                        <h4 class="lm-quick-box__title"><span class="lm-quick-box__icon lm-quick-box__icon--pay"><i class="fa fa-money"></i></span> Collect Payment</h4>
                        <p class="lm-quick-box__subtitle">Search by name, phone, or loan # to collect payment.</p>
                        <div class="lm-quick-box__meta">
                            <span class="lm-quick-box__chip lm-quick-box__chip--pay"><i class="fa fa-calendar"></i> Due Date</span>
                            <span class="lm-quick-box__chip lm-quick-box__chip--pay"><i class="fa fa-money"></i> Balance</span>
                            <span class="lm-quick-box__chip lm-quick-box__chip--pay"><i class="fa fa-check-circle"></i> Quick Pay</span>
                        </div>
                        <div class="form-group lm-quick-input" style="margin-bottom:12px;">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-search"></i></span>
                                <input type="text" class="form-control" id="loanDashboardQuickSearchInput" placeholder="Search loan #, customer name, phone...">
                            </div>
                        </div>
                        <div class="form-group lm-quick-input" style="margin-bottom:12px;">
                            <div class="input-group">
                                <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
                                <select class="form-control" id="loanDashboardQuickLocationFilter">
                                    <option value="">All Locations</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location['id'] }}">{{ $location['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive lm-table-wrap lm-table-wrap--hover-actions lm-collect-payment-scroll">
                            <table class="table table-condensed table-bordered lm-dashboard-table lm-mini-table" id="loanDashboardQuickSearchTable">
                                <thead><tr><th>Customer</th><th>Due</th><th class="text-right">Balance</th><th class="text-center">Pay</th></tr></thead>
                                <tbody data-loan-table="dashboard_quick_search">
                                    <tr><td colspan="4" class="text-center text-muted">Type to search for payment collection.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="lm-collect-payment-mobile-list" id="loanDashboardQuickSearchMobile">
                            <div class="lm-mobile-loan-empty">Type to search for payment collection.</div>
                        </div>
                        <div class="lm-quick-box__footer"><i class="fa fa-bolt"></i> Search customer name or phone for fast payment.</div>
                    </div>

                </div>
            </div>
        </div>

        <div class="lm-customer-chat-source" id="lmCustomerChatPanel">
        <div class="lm-dashboard-panel lm-dashboard-panel--feature">
            <div class="lm-dashboard-panel__header">
                <div class="lm-chat-header-text">
                    <h3 class="lm-dashboard-panel__title">Customer Chat</h3>
                    <p class="lm-dashboard-panel__hint">Recent conversations before field follow-up.</p>
                </div>
                <div class="lm-chat-header-actions">
                    <span class="lm-dashboard-panel__badge"><i class="fa fa-comments"></i> {{ $dashboardUnreadChats }} unread</span>
                </div>
            </div>
            <div class="lm-dashboard-panel__body">
                <div class="lm-chat-card">
                    <div class="lm-chat-card__toolbar">
                        <h4 class="lm-chat-card__title">áž€áž¶ážšáž‡áž‡áŸ‚áž€</h4>
                        <div class="lm-chat-card__actions">
                            <span class="lm-chat-card__icon" aria-hidden="true"><i class="fa fa-ellipsis-h"></i></span>
                            <span class="lm-chat-card__icon" aria-hidden="true"><i class="fa fa-expand"></i></span>
                            @if(Route::has('loan-management.chat.index'))
                                <a href="{{ route('loan-management.chat.index') }}" class="lm-chat-card__icon" title="Open Messenger style inbox">
                                    <i class="fa fa-pencil-square-o"></i>
                                </a>
                            @else
                                <span class="lm-chat-card__icon" aria-hidden="true"><i class="fa fa-pencil-square-o"></i></span>
                            @endif
                        </div>
                    </div>

                    <div class="lm-chat-card__search">
                        <i class="fa fa-search"></i>
                        <span>ážŸáŸ’ážœáŸ‚áž„ážšáž€ Messenger</span>
                    </div>

                    <div class="lm-chat-card__tabs">
                        <span class="lm-chat-card__tab is-active">áž‘áž¶áŸ†áž„áž¢ážŸáŸ‹</span>
                        <span class="lm-chat-card__tab">áž˜áž·áž“áž‘áž¶áž“áŸ‹áž¢áž¶áž“</span>
                        <span class="lm-chat-card__tab">áž€áŸ’ážšáž»áž˜</span>
                        <span class="lm-chat-card__tab"><i class="fa fa-ellipsis-h"></i></span>
                    </div>

                    <div class="lm-chat-card__summary">
                        <div class="lm-chat-card__summary-box">
                            <span class="lm-chat-card__summary-label">Unread queue</span>
                            <span class="lm-chat-card__summary-value">{{ $dashboardUnreadChats }}</span>
                            <span class="lm-chat-card__summary-note">Messages waiting for staff reply</span>
                        </div>
                        <div class="lm-chat-card__summary-box">
                            <span class="lm-chat-card__summary-label">Pending visits</span>
                            <span class="lm-chat-card__summary-value">{{ $dashboardPendingVisits }}</span>
                            <span class="lm-chat-card__summary-note">Field follow-up cases linked to chat</span>
                        </div>
                    </div>

                    <div class="lm-chat-card__list">
                        <div class="lm-chat-card__request">
                            <span class="lm-chat-card__request-avatar"><i class="fa fa-comments"></i></span>
                            <div>
                                <p class="lm-chat-card__request-title">New message request</p>
                                <p class="lm-chat-card__request-subtitle">
                                    {{ $dashboardUnreadChats > 0 ? $dashboardUnreadChats.' unread customer chat(s) waiting for reply.' : 'No unread customer chats right now.' }}
                                </p>
                            </div>
                            <span class="lm-chat-card__request-arrow"><i class="fa fa-angle-right"></i></span>
                        </div>

                        @if(!empty($recentChats))
                            @foreach($recentChats as $chat)
                                @php
                                    $chatUrl = Route::has('loan-management.chat.detail')
                                        ? route('loan-management.chat.detail', $chat['id'])
                                        : (Route::has('loan-management.chat.index') ? route('loan-management.chat.index') : '#');
                                    $chatName = trim((string) ($chat['display_name'] ?? 'Customer Chat'));
                                    $avatarSeed = mb_substr($chatName !== '' ? $chatName : 'C', 0, 1);
                                    $previewText = trim((string) ($chat['last_message'] ?: ($chat['display_subtitle'] ?: 'Open the conversation to continue the follow-up.')));
                                    $timeText = !empty($chat['last_message_at']) ? \Carbon\Carbon::parse($chat['last_message_at'])->diffForHumans() : ucfirst((string) ($chat['status'] ?: 'open'));
                                @endphp
                                <a href="{{ $chatUrl }}" class="lm-chat-card__item">
                                    <span class="lm-chat-card__avatar-wrap">
                                        <span class="lm-chat-card__avatar">{{ $avatarSeed }}</span>
                                        <span class="lm-chat-card__presence"></span>
                                    </span>
                                    <span>
                                        <span class="lm-chat-card__name">{{ \Illuminate\Support\Str::limit($chatName, 34) }}</span>
                                        <span class="lm-chat-card__preview">
                                            {{ \Illuminate\Support\Str::limit($previewText, 60) }}
                                            <span class="lm-chat-card__time">&middot; {{ $timeText }}</span>
                                        </span>
                                    </span>
                                    @if(($chat['unread_count'] ?? 0) > 0)
                                        <span class="lm-chat-card__dot" title="{{ (int) $chat['unread_count'] }} unread"></span>
                                    @else
                                        <span></span>
                                    @endif
                                </a>
                            @endforeach
                        @else
                            <div class="lm-chat-card__empty">
                                <p style="margin:0 0 12px;">
                                    {{ $dashboardPendingVisits > 0 ? $dashboardPendingVisits.' pending collection visit(s) still need follow-up.' : 'No recent customer chats yet. Open the inbox to start a conversation.' }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        </div>

        <div class="lm-side-stack">
            <div class="lm-dashboard-panel">
                <div class="lm-dashboard-panel__header">
                    <div>
                        <h3 class="lm-dashboard-panel__title">Overdue Customers</h3>
                        <p class="lm-dashboard-panel__hint">Need immediate follow-up today.</p>
                    </div>
                </div>
                <div class="lm-dashboard-panel__body">
                    <div class="lm-overdue-search">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-search"></i></span>
                            <input type="text" class="form-control" id="loanOverdueCustomersSearch" placeholder="Search overdue customer, loan #, phone...">
                        </div>
                    </div>
                    <div class="lm-table-wrap lm-overdue-customers-scroll">
                    <table class="table table-condensed lm-dashboard-table lm-mini-table" id="loanOverdueCustomersTable">
                        <thead><tr><th>Customer</th><th>Pay Date</th><th>Days</th><th class="text-right">Paid</th><th class="text-right">Due</th><th class="text-right">Payoff</th><th class="text-center">Pay</th></tr></thead>
                        <tbody data-loan-table="overdue_customers">
                        @forelse(($overdueCustomers ?? []) as $row)
                            @php
                                $overdueName = trim((string) ($row['customer'] ?? '-'));
                                $overdueInitial = mb_substr($overdueName !== '' && $overdueName !== '-' ? $overdueName : 'C', 0, 1);
                            @endphp
                            <tr>
                                <td>
                                    <div class="lm-customer-profile">
                                        <span class="lm-customer-profile__avatar">
                                            @if(!empty($row['customer_photo_url']))
                                                <img src="{{ $row['customer_photo_url'] }}" alt="">
                                            @else
                                                {{ $overdueInitial }}
                                            @endif
                                        </span>
                                        <span class="lm-customer-profile__info">
                                            <span class="lm-row-title">{{ $overdueName }}</span>
                                            <span class="lm-row-subtitle">{{ $row['loan_number'] ?? '' }}{{ !empty($row['phone']) ? ' Â· '.$row['phone'] : '' }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td>{{ $row['date_to_pay'] ?? '-' }}</td>
                                <td>{{ (int)($row['overdue_days'] ?? 0) }} day(s)</td>
                                <td class="text-right">{{ number_format((float)($row['total_paid'] ?? 0), 2) }}</td>
                                <td class="text-right">{{ number_format((float)($row['total_not_yet_paid'] ?? ($row['overdue_amount'] ?? 0)), 2) }}</td>
                                <td class="text-right">{{ number_format((float)($row['pay_off_now'] ?? 0), 2) }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-success btn-xs btn-modal" data-href="{{ url('loan-management/loans/'.($row['id'] ?? 0).'/payment/create?return_to='.rawurlencode(route('loan-management.dashboard'))) }}" data-container=".view_modal">
                                        <i class="fa fa-money"></i> Pay
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center">No overdue customers.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <div class="lm-dashboard-panel">
                <div class="lm-dashboard-panel__header">
                    <div>
                        <h3 class="lm-dashboard-panel__title">Loan Status Overview</h3>
                        <p class="lm-dashboard-panel__hint">Loan status distribution.</p>
                    </div>
                </div>
                <div class="lm-dashboard-panel__body">
                    <div class="lm-chart-shell">
                        <div class="lm-chart-copy">
                            <strong>Status Snapshot</strong>
                            <small id="loanStatusChartText" data-loan-chart="loan_status">Status labels: {{ implode(', ', $loanStatusChart['labels'] ?? []) }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="lm-dashboard-grid">
        <div class="lm-dashboard-panel">
            <div class="lm-dashboard-panel__header">
                <div>
                    <h3 class="lm-dashboard-panel__title">Visit Schedule</h3>
                    <p class="lm-dashboard-panel__hint">Pending fieldwork assignments.</p>
                </div>
            </div>
            <div class="lm-dashboard-panel__body lm-table-wrap">
                <table class="table table-bordered table-condensed lm-dashboard-table" id="loanVisitScheduleTable">
                    <thead><tr><th>Customer</th><th>Date</th><th>Status</th><th>Staff</th></tr></thead>
                    <tbody data-loan-table="follow_up_customers">
                    @forelse(($visitSchedule ?? []) as $row)
                        <tr>
                            <td><span class="lm-row-title">{{ $row['customer'] ?? '-' }}</span></td>
                            <td>{{ $row['follow_up_date'] ?? '-' }}</td>
                            <td>{{ $row['status'] ?? '-' }}</td>
                            <td>{{ $row['assigned_staff'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No pending visits.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="lm-dashboard-panel">
            <div class="lm-dashboard-panel__header">
                <div>
                    <h3 class="lm-dashboard-panel__title">Collector Performance</h3>
                    <p class="lm-dashboard-panel__hint">Output, loans, and visits by collector.</p>
                </div>
            </div>
            <div class="lm-dashboard-panel__body lm-table-wrap">
                <table class="table table-striped table-bordered lm-dashboard-table" id="loanCollectorPerformanceTable">
                    <thead><tr><th>Collector</th><th>Assigned Loans</th><th class="text-right">Collected</th><th>Visits</th></tr></thead>
                    <tbody data-loan-table="collector_performance">
                    @forelse(($collectorPerformance ?? []) as $row)
                        <tr>
                            <td><span class="lm-row-title">{{ $row['collector'] ?? '-' }}</span></td>
                            <td>{{ (int)($row['assigned_loans'] ?? 0) }}</td>
                            <td class="text-right">{{ number_format((float)($row['collected_amount'] ?? 0), 2) }}</td>
                            <td>{{ (int)($row['visit_count'] ?? 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No collector performance data.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

    <div class="lm-dashboard-pane" data-dashboard-pane="live">
        @php
            $initialLiveChat = collect($recentChats ?? [])->first();
        @endphp
        <section class="lm-dashboard-panel lm-dashboard-panel--feature">
            <div class="lm-dashboard-panel__header">
                <div>
                    <h3 class="lm-dashboard-panel__title">Live Chat</h3>
                    <p class="lm-dashboard-panel__hint">Conversations, unread queues, and support activity.</p>
                </div>
                <span class="lm-live-badge"><span class="lm-live-badge__dot"></span> Auto refresh 30s</span>
            </div>
            <div class="lm-dashboard-panel__body">
                <div class="lm-live-chat-shell">
                    <aside class="lm-live-chat-inbox">
                        <div class="lm-live-chat-toolbar">
                            <h4>Chats</h4>
                            <input type="text" class="lm-live-chat-search" id="loanDashboardLiveChatSearch" placeholder="Search Messenger style inbox">
                        </div>
                        <div class="lm-live-chat-list" id="loanDashboardLiveChatList">
                            <div class="lm-live-chat-empty">Loading live chats...</div>
                        </div>
                    </aside>

                    <main class="lm-live-chat-main">
                        <div class="lm-live-chat-mainbar">
                            <div>
                                <h4 class="lm-live-chat-main-title" id="loanDashboardLiveChatTitle">{{ $initialLiveChat['display_name'] ?? 'Select a chat' }}</h4>
                                <p class="lm-live-chat-main-subtitle" id="loanDashboardLiveChatSubtitle">{{ $initialLiveChat['display_subtitle'] ?? 'Open a customer conversation from the inbox list.' }}</p>
                            </div>
                            <div class="lm-live-chat-main-actions">
                            <a href="{{ route('loan-management.live-chat') }}" class="btn btn-default btn-sm">
                                <i class="fa fa-external-link"></i> Open Full Inbox
                            </a>
                            @if(!empty($initialLiveChat['id']))
                                <a href="{{ route('loan-management.live-chat.detail', $initialLiveChat['id']) }}" class="btn btn-primary btn-sm" id="loanDashboardLiveChatOpenBtn">
                                    <i class="fa fa-comments"></i> Open Conversation
                                    </a>
                                @else
                                    <a href="{{ route('loan-management.live-chat') }}" class="btn btn-primary btn-sm" id="loanDashboardLiveChatOpenBtn">
                                        <i class="fa fa-comments"></i> Open Conversation
                                    </a>
                                @endif
                            </div>
                        </div>
                        <iframe
                            id="loanDashboardLiveChatFrame"
                            class="lm-live-chat-frame"
                            src="{{ !empty($initialLiveChat['id']) ? route('loan-management.live-chat.detail', ['thread' => $initialLiveChat['id'], '_lm_embed' => 1]) : route('loan-management.live-chat', ['_lm_embed' => 1]) }}"
                            title="Loan live chat dashboard"></iframe>
                    </main>

                    <aside class="lm-live-chat-side">
                        <div class="lm-live-chat-profile">
                            <div class="lm-live-chat-profile-avatar" id="loanDashboardLiveChatProfileAvatar">
                                {{ strtoupper(substr((string) ($initialLiveChat['display_name'] ?? 'C'), 0, 1)) }}
                            </div>
                            <h4 class="lm-live-chat-profile-name" id="loanDashboardLiveChatProfileName">{{ $initialLiveChat['display_name'] ?? 'Customer Chat' }}</h4>
                            <p class="lm-live-chat-profile-subtitle" id="loanDashboardLiveChatProfileSubtitle">{{ $initialLiveChat['display_subtitle'] ?? 'Loan support inbox' }}</p>
                            <p class="lm-live-chat-profile-time" id="loanDashboardLiveChatProfileTime">
                                {{ !empty($initialLiveChat['last_message_at']) ? \Carbon\Carbon::parse($initialLiveChat['last_message_at'])->diffForHumans() : 'Waiting for live activity' }}
                            </p>
                        </div>

                        <div class="lm-live-chat-side-section">
                            <h5 class="lm-live-chat-side-title">Conversation Summary</h5>
                            <div class="lm-live-chat-side-row"><span>Status</span><span id="loanDashboardLiveChatStatus">{{ ucfirst((string) ($initialLiveChat['status'] ?? 'open')) }}</span></div>
                            <div class="lm-live-chat-side-row"><span>Priority</span><span id="loanDashboardLiveChatPriority">{{ ucfirst((string) ($initialLiveChat['priority'] ?? 'normal')) }}</span></div>
                            <div class="lm-live-chat-side-row"><span>Assigned Team</span><span id="loanDashboardLiveChatTeam">{{ $initialLiveChat['assigned_team'] ?? 'Support' }}</span></div>
                            <div class="lm-live-chat-side-row"><span>Unread</span><span id="loanDashboardLiveChatUnread">{{ (int) ($initialLiveChat['unread_count'] ?? 0) }}</span></div>
                        </div>

                        <div class="lm-live-chat-side-section">
                            <h5 class="lm-live-chat-side-title">Last Message</h5>
                            <div id="loanDashboardLiveChatLastMessage" style="color:#334155; font-size:13px; line-height:1.6;">
                                {{ $initialLiveChat['last_message'] ?? 'No recent message yet.' }}
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>
    </div>
</div>

@section('loan_js')
@parent
<script>
    (function ($) {
        if (!window.jQuery) {
            return;
        }

        var liveUrl = "{{ route('loan-management.dashboard.data', [], true) }}";
        var quickSearchUrl = "{{ route('loan-management.dashboard.quick-search', [], true) }}";
        var refreshMs = 30000;
        var loading = false;
        var timer = null;
        var quickSearchTimer = null;
        var liveTabLoaded = false;
        var liveChatSearchTimer = null;
        var liveChatThreads = [];
        var activeLiveChatId = {{ (int) ($initialLiveChat['id'] ?? 0) }};
        var liveChatApiUrl = "{{ route('loan-management.chat-api.index') }}";
        var liveChatFrameBaseUrl = "{{ url('loan-management/live-chat') }}";

        function money(value) {
            var number = parseFloat(value || 0);
            return Number.isFinite(number) ? number.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '0.00';
        }

        function intValue(value) {
            var number = parseInt(value || 0, 10);
            return Number.isFinite(number) ? String(number) : '0';
        }

        function esc(value) {
            return $('<div>').text(value == null ? '-' : value).html();
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

        function dashboardDate(value) {
            if (!value) {
                return '-';
            }

            if (typeof moment === 'function') {
                var date = moment(value);
                if (date.isValid()) {
                    return date.format(moment_date_format);
                }
            }

            return esc(value);
        }

        function copyDashboardText(text) {
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

        function openDashboardIframeModal(title, url) {
            if (!url || !$('.view_modal').length) {
                return;
            }

            var html = '' +
                '<div class="modal-dialog modal-xl lm-dashboard-iframe-modal" role="document">' +
                    '<div class="modal-content">' +
                        '<div class="modal-header">' +
                            '<button type="button" class="close" data-dismiss="modal" aria-label="Close">' +
                                '<span aria-hidden="true">&times;</span>' +
                            '</button>' +
                            '<h4 class="modal-title">' + esc(title || 'Detail') + '</h4>' +
                        '</div>' +
                        '<div class="modal-body">' +
                            '<iframe src="' + esc(url) + '" title="' + esc(title || 'Detail') + '"></iframe>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            $('.view_modal')
                .html(html)
                .modal('show');
        }

        function updateCards(cards) {
            $('[data-loan-card]').each(function () {
                var key = $(this).data('loan-card');
                var value = cards && Object.prototype.hasOwnProperty.call(cards, key) ? cards[key] : 0;
                $(this).text($(this).data('format') === 'money' ? money(value) : intValue(value));
            });
        }

        function renderRecentPayments(rows) {
            var html = '';
            (rows || []).forEach(function (row) {
                html += '<tr><td>'+esc(row.paid_date)+'</td><td>'+esc(row.customer_name_snapshot)+'</td><td>'+esc(row.loan_number)+'</td><td>'+esc(row.payment_method)+'</td><td class="text-right">'+money(row.paid_amount)+'</td></tr>';
            });
            $('[data-loan-table="recent_payments"]').html(html || '<tr><td colspan="5" class="text-center">No recent payments found.</td></tr>');
        }

        function renderOverdueCustomers(rows) {
            var html = '';
            (rows || []).forEach(function (row) {
                var payUrl = "{{ url('loan-management/loans') }}/" + row.id + "/payment/create?return_to={{ rawurlencode(route('loan-management.dashboard')) }}";
                var customerName = row.customer || '-';
                var customerInitial = customerName && customerName !== '-' ? String(customerName).charAt(0).toUpperCase() : 'C';
                var customerAvatar = row.customer_photo_url
                    ? '<span class="lm-customer-profile__avatar"><img src="' + esc(row.customer_photo_url) + '" alt=""></span>'
                    : '<span class="lm-customer-profile__avatar">' + esc(customerInitial) + '</span>';
                var customerSub = (row.loan_number || '') + (row.phone ? ' &middot; ' + esc(row.phone) : '');
                html += '<tr>'
                    + '<td><div class="lm-customer-profile">' + customerAvatar + '<span class="lm-customer-profile__info"><span class="lm-row-title">'+esc(customerName)+'</span><span class="lm-row-subtitle">'+customerSub+'</span></span></div></td>'
                    + '<td>'+esc(row.date_to_pay || '-')+'</td>'
                    + '<td>'+intValue(row.overdue_days)+' day(s)</td>'
                    + '<td class="text-right">'+money(row.total_paid || 0)+'</td>'
                    + '<td class="text-right">'+money(row.total_not_yet_paid || row.overdue_amount || 0)+'</td>'
                    + '<td class="text-right">'+money(row.pay_off_now || 0)+'</td>'
                    + '<td class="text-center"><button type="button" class="btn btn-success btn-xs btn-modal" data-href="'+payUrl+'" data-container=".view_modal"><i class="fa fa-money"></i> Pay</button></td>'
                    + '</tr>';
            });
            $('[data-loan-table="overdue_customers"]').html(html || '<tr><td colspan="7" class="text-center">No overdue customers.</td></tr>');
            filterOverdueCustomers();
        }

        function filterOverdueCustomers() {
            var query = String($('#loanOverdueCustomersSearch').val() || '').toLowerCase().trim();
            var visibleCount = 0;
            var $tbody = $('[data-loan-table="overdue_customers"]');

            $tbody.find('tr.lm-overdue-no-results').remove();
            $tbody.find('tr').each(function () {
                var $row = $(this);
                if ($row.find('td').length === 1) {
                    $row.toggle(!query);
                    return;
                }

                var matches = !query || $row.text().toLowerCase().indexOf(query) !== -1;
                $row.toggle(matches);
                if (matches) {
                    visibleCount++;
                }
            });

            if (query && visibleCount === 0) {
                $tbody.append('<tr class="lm-overdue-no-results"><td colspan="7" class="text-center text-muted">No overdue customers match your search.</td></tr>');
            }
        }

        var quickSearchRows = [];

        function quickSearchUrls(row) {
            var detailUrl = "{{ url('loan-management/loans') }}/" + row.id + "/view?_lm_modal=1";
            var editUrl = "{{ url('loan-management/loans') }}/" + row.id + "/edit?_lm_modal=1";
            var printModalUrl = "{{ url('loan-management/loans') }}/" + row.id + "/print-modal";
            var payUrl = "{{ url('loan-management/loans') }}/" + row.id + "/payment/create?return_to={{ rawurlencode(route('loan-management.dashboard')) }}";
            var collectionUrl = "{{ url('loan-management/loans') }}/" + row.id + "/payments/collection-modal";
            var copyInfoUrl = "{{ url('loan-management/loans') }}/" + row.id + "/payment/copy-info";
            return {
                detail: detailUrl,
                edit: editUrl,
                printModal: printModalUrl,
                pay: payUrl,
                collection: collectionUrl,
                copyInfo: copyInfoUrl
            };
        }

        function quickSearchRowHtml(row) {
            var urls = quickSearchUrls(row);
            var telegramLinkUrl = row.customer_id ? "{{ url('loan-management/customers') }}/" + row.customer_id + "/telegram/link" : '';
            var telegramAction = '';
            var tgStatus = row.telegram_linked
                ? '<span class="lm-customer-hover__status linked"><i class="fa fa-check-circle"></i> Telegram connected</span>'
                : '<span class="lm-customer-hover__status"><i class="fa fa-paper-plane"></i> Telegram not connected</span>';
            var hoverTelegram = row.customer_id
                ? '<div class="lm-customer-hover__panel">' +
                    tgStatus +
                    '<div class="lm-customer-hover__actions">' +
                        '<button type="button" class="primary js-dashboard-open-telegram" data-customer-id="' + esc(row.customer_id) + '" data-customer-name="' + esc(row.customer_name) + '" data-telegram-linked="' + (row.telegram_linked ? '1' : '0') + '" data-loan-id="' + esc(row.id) + '" data-loan-number="' + esc(row.loan_number) + '" data-balance="' + esc(row.balance_amount) + '"><i class="fa fa-telegram"></i> Chat</button>' +
                    '</div>' +
                '</div>'
                : '';
            if (row.telegram_linked) {
                telegramAction = '<li><button type="button" disabled class="text-muted"><i class="fa fa-check-circle"></i> Telegram Connected</button></li>';
            } else if (telegramLinkUrl) {
                telegramAction = '<li><button type="button" class="js-dashboard-telegram-link" data-url="' + telegramLinkUrl + '" data-customer="' + esc(row.customer_name) + '"><i class="fa fa-paper-plane"></i> Connect Telegram</button></li>';
            }
            var dueLabel = row.next_due_date ? esc(row.next_due_date) : '<span class="text-muted">-</span>';
            var isOverdue = row.status && (String(row.status).toLowerCase() === 'overdue' || String(row.status).toLowerCase() === 'late');
            var statusBadge = isOverdue
                ? '<span class="lm-pay-status lm-pay-status--overdue">OVERDUE</span>'
                : (row.status && String(row.status).toLowerCase() !== 'active' ? '<span class="lm-pay-status">' + esc(row.status) + '</span>' : '');
            var customerInitial = (row.customer_name && row.customer_name !== '-' ? String(row.customer_name).charAt(0).toUpperCase() : 'C');
            var customerAvatar = row.customer_photo_url
                ? '<span class="lm-customer-profile__avatar"><img src="' + esc(row.customer_photo_url) + '" alt=""></span>'
                : '<span class="lm-customer-profile__avatar">' + esc(customerInitial) + '</span>';

            return '<tr class="lm-pay-row" data-loan-id="' + esc(row.id) + '">'
                + '<td>'
                + '<div class="lm-customer-profile">'
                + customerAvatar
                + '<div class="lm-customer-profile__info">'
                    + '<div class="lm-customer-cell">'
                    + '<div class="lm-customer-hover">'
                    + '<a href="#" class="lm-row-title lm-dashboard-frame-link js-loan-detail-modal" data-title="Loan Detail" data-url="' + urls.detail + '">' + esc(row.customer_name) + '</a>'
                    + hoverTelegram
                    + '</div>'
                    + '<span class="lm-row-subtitle">' + esc(row.loan_number) + (row.customer_phone && row.customer_phone !== '-' ? ' &middot; ' + esc(row.customer_phone) : '') + (row.location_name ? ' &middot; ' + esc(row.location_name) : '') + '</span>'
                    + statusBadge
                    + '</div>'
                + '</div>'
                + '</div>'
                + '</td>'
                + '<td class="lm-pay-due">' + dueLabel + '</td>'
                + '<td class="text-right lm-pay-balance">' + money(row.balance_amount) + '</td>'
                + '<td class="text-center lm-pay-action">'
                + '<button type="button" class="btn btn-success btn-xs lm-pay-btn btn-modal" data-href="' + urls.pay + '" data-container=".view_modal" title="Collect payment for ' + esc(row.customer_name) + '"><i class="fa fa-money"></i> <span>Pay</span></button>'
                + '<button type="button" class="btn btn-default btn-xs lm-print-btn btn-modal" data-href="' + urls.printModal + '" data-container=".view_modal" title="Print loan for ' + esc(row.customer_name) + '"><i class="fa fa-print"></i> <span>Print</span></button>'
                + '<div class="lm-pay-more dropdown">'
                + '<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown" title="More actions"><i class="fa fa-ellipsis-h"></i></button>'
                + '<ul class="dropdown-menu dropdown-menu-right lm-action-menu__list">'
                + '<li><button type="button" class="js-loan-detail-modal" data-title="Loan Detail" data-url="' + urls.detail + '"><i class="fa fa-eye"></i> View Loan</button></li>'
                + telegramAction
                + '</ul>'
                + '</div>'
                + '</td>'
                + '</tr>';
        }

        function quickSearchMobileCardHtml(row) {
            var urls = quickSearchUrls(row);
            var dueLabel = row.next_due_date ? esc(row.next_due_date) : '-';
            var loanMeta = esc(row.loan_number || '-') + (row.customer_phone && row.customer_phone !== '-' ? ' &middot; ' + esc(row.customer_phone) : '');
            var customerInitial = (row.customer_name && row.customer_name !== '-' ? String(row.customer_name).charAt(0).toUpperCase() : 'C');
            var customerAvatar = row.customer_photo_url
                ? '<span class="lm-customer-profile__avatar"><img src="' + esc(row.customer_photo_url) + '" alt=""></span>'
                : '<span class="lm-customer-profile__avatar">' + esc(customerInitial) + '</span>';
            var isOverdue = row.status && (String(row.status).toLowerCase() === 'overdue' || String(row.status).toLowerCase() === 'late');
            var statusClass = isOverdue ? ' lm-pay-status--overdue' : '';
            var statusBadge = row.status ? '<span class="lm-pay-status' + statusClass + '">' + esc(row.status) + '</span>' : '';

            return '<article class="lm-collect-payment-card" data-loan-id="' + esc(row.id) + '">'
                + '<div class="lm-collect-payment-card__header">'
                + '<div class="lm-customer-profile">' + customerAvatar
                + '<span class="lm-customer-profile__info">'
                + '<a href="#" class="lm-row-title lm-dashboard-frame-link js-loan-detail-modal" data-title="Loan Detail" data-url="' + urls.detail + '">' + esc(row.customer_name || '-') + '</a>'
                + '<span class="lm-row-subtitle">' + loanMeta + '</span>'
                + '</span></div>'
                + statusBadge
                + '</div>'
                + '<div class="lm-collect-payment-card__grid">'
                + '<div><small>Due</small><strong>' + dueLabel + '</strong></div>'
                + '<div><small>Balance</small><strong>' + money(row.balance_amount) + '</strong></div>'
                + (row.location_name ? '<div><small>Location</small><strong>' + esc(row.location_name) + '</strong></div>' : '')
                + '</div>'
                + '<div class="lm-collect-payment-card__actions">'
                + '<button type="button" class="btn btn-success btn-sm btn-modal" data-href="' + urls.pay + '" data-container=".view_modal"><i class="fa fa-money"></i> Pay</button>'
                + '<button type="button" class="btn btn-default btn-sm btn-modal" data-href="' + urls.printModal + '" data-container=".view_modal"><i class="fa fa-print"></i> Print</button>'
                + '<button type="button" class="btn btn-default btn-sm js-loan-detail-modal" data-title="Loan Detail" data-url="' + urls.detail + '"><i class="fa fa-eye"></i> View</button>'
                + '</div>'
                + '</article>';
        }

        function renderQuickSearch(rows) {
            var html = '';
            var mobileHtml = '';
            quickSearchRows = rows || [];
            (rows || []).forEach(function (row) {
                html += quickSearchRowHtml(row);
                mobileHtml += quickSearchMobileCardHtml(row);
            });
            $('[data-loan-table="dashboard_quick_search"]').html(html || '<tr><td colspan="4" class="text-center">No loans found for this search.</td></tr>');
            $('#loanDashboardQuickSearchMobile').html(mobileHtml || '<div class="lm-mobile-loan-empty">No loans found for this search.</div>');
        }

        function refreshQuickSearchRow(loanId) {
            var locationId = $('#loanDashboardQuickLocationFilter').val() || '';
            return fetch(quickSearchUrl + '?loan_id=' + encodeURIComponent(loanId) + '&location_id=' + encodeURIComponent(locationId), {
                headers: { 'Accept': 'application/json' }
            })
                .then(function (response) { return response.json(); })
                .then(function (json) {
                    var row = json && json.data && json.data.length ? json.data[0] : null;
                    var $oldRow = $('[data-loan-table="dashboard_quick_search"] tr[data-loan-id="' + loanId + '"]');
                    if (!row) {
                        $oldRow.remove();
                        return;
                    }

                    var $newRow = $(quickSearchRowHtml(row));
                    if ($oldRow.length) {
                        $oldRow.replaceWith($newRow);
                    } else {
                        $('[data-loan-table="dashboard_quick_search"]').prepend($newRow);
                    }
                    quickSearchRows = quickSearchRows.filter(function (item) {
                        return String(item.id) !== String(loanId);
                    });
                    quickSearchRows.unshift(row);
                    $('#loanDashboardQuickSearchMobile').html(quickSearchRows.map(quickSearchMobileCardHtml).join(''));
                    $newRow.addClass('success');
                    window.setTimeout(function () { $newRow.removeClass('success'); }, 900);
                });
        }

        $(document).on('click', '.lm-dashboard-refresh-schedule-btn', function (event) {
            event.preventDefault();

            var $button = $(this);
            var url = $button.data('url');
            if (!url) {
                return;
            }

            if (!window.confirm('Refresh this loan payment schedule from the loan data and imported payments?')) {
                return;
            }

            var originalHtml = $button.html();
            $button.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <span>Refreshing</span>');

            $.ajax({
                url: url,
                method: 'POST',
                dataType: 'json',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    sections_context: 'show'
                },
                success: function (res) {
                    if (res && res.success) {
                        if (window.toastr) {
                            toastr.success(res.message || 'Payment schedule refreshed successfully.');
                        }
                        refreshQuickSearchRow($button.data('loan-id') || $button.closest('tr[data-loan-id]').data('loan-id'));
                    } else if (window.toastr) {
                        toastr.error((res && res.message) || 'Unable to refresh payment schedule.');
                    }
                },
                error: function (xhr) {
                    var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Unable to refresh payment schedule.';
                    if (window.toastr) {
                        toastr.error(message);
                    } else {
                        alert(message);
                    }
                },
                complete: function () {
                    $button.prop('disabled', false).html(originalHtml);
                }
            });
        });

        function runQuickSearch() {
            var term = $.trim($('#loanDashboardQuickSearchInput').val() || '');
            var locationId = $('#loanDashboardQuickLocationFilter').val() || '';

            fetch(quickSearchUrl + '?q=' + encodeURIComponent(term) + '&location_id=' + encodeURIComponent(locationId), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) { return response.ok ? response.json() : null; })
                .then(function (res) {
                    renderQuickSearch(res && res.data ? res.data : []);
                })
                .catch(function () {
                    $('[data-loan-table="dashboard_quick_search"]').html('<tr><td colspan="4" class="text-center text-danger">Search failed.</td></tr>');
                    $('#loanDashboardQuickSearchMobile').html('<div class="lm-mobile-loan-empty text-danger">Search failed.</div>');
                });
        }

        $(document).on('click', '.js-copy-loan-payment-info', function (event) {
            event.preventDefault();

            var $button = $(this);
            var url = $button.data('url');
            if (!url) {
                return;
            }

            $button.prop('disabled', true);
            fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    return response.ok ? response.json() : Promise.reject();
                })
                .then(function (res) {
                    var text = res && res.data ? (res.data.text || '') : '';
                    return copyDashboardText(text);
                })
                .then(function () {
                    if (window.toastr) {
                        toastr.success('Copied loan information');
                    }
                })
                .catch(function () {
                    if (window.toastr) {
                        toastr.error('Unable to copy loan information');
                    } else {
                        alert('Unable to copy loan information');
                    }
                })
                .finally(function () {
                    $button.prop('disabled', false);
                });
        });

        $(document).on('click', '.js-dashboard-telegram-link', function (event) {
            event.preventDefault();

            var $button = $(this);
            var url = $button.data('url');
            var customer = $button.data('customer') || 'customer';
            if (!url || !$('.view_modal').length) {
                return;
            }

            $button.prop('disabled', true);
            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: '{}'
            })
                .then(function (response) {
                    return response.json().then(function (json) {
                        if (!response.ok) {
                            throw new Error(json.message || 'Unable to create Telegram link.');
                        }
                        return json;
                    });
                })
                .then(function (res) {
                    var link = res && res.link ? res.link : '';
                    var expiresText = res && res.expires_at ? formatLmExpiry(res.expires_at) : '';
                    var qrUrl = link ? 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(link) : '';

                    $('.view_modal').html(
                        '<div class="modal-dialog modal-sm" role="document">' +
                            '<div class="modal-content">' +
                                '<div class="modal-header">' +
                                    '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
                                    '<h4 class="modal-title"><i class="fa fa-paper-plane"></i> Connect Telegram</h4>' +
                                '</div>' +
                                '<div class="modal-body text-center">' +
                                    '<p class="text-muted" style="margin-bottom:12px;">Share this link with ' + esc(customer) + '. Valid for a limited time and can only be used once.</p>' +
                                    (qrUrl ? '<img src="' + qrUrl + '" alt="Telegram QR code" style="width:220px;height:220px;max-width:100%;border:1px solid #e5e7eb;border-radius:8px;padding:8px;background:#fff;margin-bottom:12px;">' : '') +
                                    '<input class="form-control text-center" readonly value="' + esc(link) + '" style="margin-bottom:8px;">' +
                                    (expiresText ? '<div class="text-muted small">Expires: ' + esc(expiresText) + '</div>' : '') +
                                '</div>' +
                                '<div class="modal-footer">' +
                                    '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
                                    '<a href="' + esc(link) + '" target="_blank" rel="noopener" class="btn btn-primary">Open Link</a>' +
                                '</div>' +
                            '</div>' +
                        '</div>'
                    ).modal('show');
                })
                .catch(function (error) {
                    if (window.toastr) {
                        toastr.error(error.message || 'Unable to create Telegram link.');
                    } else {
                        alert(error.message || 'Unable to create Telegram link.');
                    }
                })
                .finally(function () {
                    $button.prop('disabled', false);
                });
        });

        function dashboardTelegramContext($button, action) {
            return {
                loan_id: $button.data('loan-id') || '',
                loan_number: $button.data('loan-number') || '',
                balance_amount: $button.data('balance') || '',
                auto_action: action || ''
            };
        }

        $(document).on('click', '.js-dashboard-open-telegram, .js-dashboard-telegram-invoice, .js-dashboard-telegram-pay', function (event) {
            event.preventDefault();
            event.stopPropagation();

            var $button = $(this);
            var customerId = $button.data('customer-id');
            if (!customerId || typeof window.loanManagementOpenTelegramCustomer !== 'function') {
                return;
            }

            var action = $button.hasClass('js-dashboard-telegram-invoice')
                ? 'invoice'
                : ($button.hasClass('js-dashboard-telegram-pay') ? 'pay' : '');

            window.loanManagementOpenTelegramCustomer(
                customerId,
                $button.data('customer-name') || 'Customer',
                String($button.data('telegram-linked')) === '1',
                dashboardTelegramContext($button, action)
            );
        });

        function renderFollowUps(rows) {
            var html = '';
            (rows || []).forEach(function (row) {
                html += '<tr><td><span class="lm-row-title">'+esc(row.customer)+'</span></td><td>'+esc(row.follow_up_date)+'</td><td>'+esc(row.status)+'</td><td>'+esc(row.assigned_staff)+'</td></tr>';
            });
            $('[data-loan-table="follow_up_customers"]').html(html || '<tr><td colspan="4" class="text-center">No pending visits.</td></tr>');
        }

        function renderCollectorPerformance(rows) {
            var html = '';
            (rows || []).forEach(function (row) {
                html += '<tr><td><span class="lm-row-title">'+esc(row.collector)+'</span></td><td>'+intValue(row.assigned_loans)+'</td><td class="text-right">'+money(row.collected_amount)+'</td><td>'+intValue(row.visit_count)+'</td></tr>';
            });
            $('[data-loan-table="collector_performance"]').html(html || '<tr><td colspan="4" class="text-center">No collector performance data.</td></tr>');
        }

        function updateChartText(chart) {
            if (!chart || !chart.labels) {
                return;
            }
            $('#loanStatusChartText').text('Status labels: ' + chart.labels.join(', '));
        }

        function compactLabel(value) {
            var raw = String(value == null ? '' : value);
            return raw.length > 10 ? raw.slice(2) : raw;
        }

        function renderLiveBarChart(containerSelector, config) {
            var container = $(containerSelector);
            if (!container.length) {
                return;
            }

            var labels = (config && config.labels) ? config.labels : [];
            var series = (config && config.series) ? config.series : [];
            var legends = (config && config.legends) ? config.legends : [];
            var maxValue = 0;

            series.forEach(function (row) {
                (row && row.values ? row.values : []).forEach(function (value) {
                    maxValue = Math.max(maxValue, Number(value || 0));
                });
            });

            if (!labels.length || !series.length || maxValue <= 0) {
                container.html('<div class="lm-live-chart__empty">No live chart data for this filter range.</div>');
                return;
            }

            var canvas = '<div class="lm-live-chart__canvas">';
            labels.forEach(function (label, index) {
                canvas += '<div class="lm-live-chart__bar-group">';
                canvas += '<div class="lm-live-chart__value">';
                series.forEach(function (row, rowIndex) {
                    var rawValue = Number((row.values || [])[index] || 0);
                    canvas += (rowIndex > 0 ? '<br>' : '') + esc(row.format === 'money' ? money(rawValue) : intValue(rawValue));
                });
                canvas += '</div>';
                canvas += '<div class="lm-live-chart__bar-stack">';
                series.forEach(function (row) {
                    var rawValue = Number((row.values || [])[index] || 0);
                    var percent = maxValue > 0 ? Math.max(4, (rawValue / maxValue) * 100) : 4;
                    canvas += '<span class="lm-live-chart__bar ' + esc(row.className || '') + '" style="height:' + percent + '%"></span>';
                });
                canvas += '</div>';
                canvas += '<div class="lm-live-chart__label">' + esc(compactLabel(label)) + '</div>';
                canvas += '</div>';
            });
            canvas += '</div>';

            var legendHtml = '';
            if (legends.length) {
                legendHtml += '<div class="lm-live-chart__legend">';
                legends.forEach(function (legend) {
                    legendHtml += '<span class="lm-live-chart__legend-item"><span class="lm-live-chart__legend-swatch ' + esc(legend.className || '') + '"></span>' + esc(legend.label || '') + '</span>';
                });
                legendHtml += '</div>';
            }

            container.html(canvas + legendHtml);
        }

        function renderLiveCharts(charts) {
            charts = charts || {};

            renderLiveBarChart('#loanLiveMonthlyLoanChart', {
                labels: charts.monthly_loan ? charts.monthly_loan.labels : [],
                series: [
                    { values: charts.monthly_loan ? charts.monthly_loan.count : [], format: 'int', className: '' },
                    { values: charts.monthly_loan ? charts.monthly_loan.principal : [], format: 'money', className: 'lm-live-chart__bar--accent' }
                ],
                legends: [
                    { label: 'Loan Count', className: '' },
                    { label: 'Principal', className: 'lm-live-chart__legend-swatch--accent' }
                ]
            });

            renderLiveBarChart('#loanLiveDailyCollectionChart', {
                labels: charts.daily_collection ? charts.daily_collection.labels : [],
                series: [
                    { values: charts.daily_collection ? charts.daily_collection.amount : [], format: 'money', className: 'lm-live-chart__bar--accent' }
                ],
                legends: [
                    { label: 'Collected Amount', className: 'lm-live-chart__legend-swatch--accent' }
                ]
            });

            renderLiveBarChart('#loanLiveStatusChart', {
                labels: charts.loan_status ? charts.loan_status.labels : [],
                series: [
                    { values: charts.loan_status ? charts.loan_status.series : [], format: 'int', className: 'lm-live-chart__bar--warn' }
                ],
                legends: [
                    { label: 'Loans', className: 'lm-live-chart__legend-swatch--warn' }
                ]
            });

            renderLiveBarChart('#loanLivePaymentMethodChart', {
                labels: charts.payment_method ? charts.payment_method.labels : [],
                series: [
                    { values: charts.payment_method ? charts.payment_method.amount : [], format: 'money', className: '' }
                ],
                legends: [
                    { label: 'Payment Total', className: '' }
                ]
            });
        }

        function activateDashboardTab(tabKey) {
            $('[data-dashboard-tab]').removeClass('is-active').attr('aria-pressed', 'false');
            $('[data-dashboard-pane]').removeClass('is-active');
            $('[data-dashboard-tab="' + tabKey + '"]').addClass('is-active').attr('aria-pressed', 'true');
            $('[data-dashboard-pane="' + tabKey + '"]').addClass('is-active');

            if (tabKey === 'live' && !liveTabLoaded) {
                liveTabLoaded = true;
                loadLiveChatThreads();
                refreshLoanDashboard();
            }
        }

        function formatLiveChatTime(value) {
            if (!value) {
                return '';
            }

            var date = new Date(value);
            if (isNaN(date.getTime())) {
                return String(value);
            }

            return date.toLocaleString();
        }

        function setLiveChatProfile(thread) {
            thread = thread || {};
            var name = thread.display_name || 'Customer Chat';
            $('#loanDashboardLiveChatTitle, #loanDashboardLiveChatProfileName').text(name);
            $('#loanDashboardLiveChatSubtitle, #loanDashboardLiveChatProfileSubtitle').text(thread.display_subtitle || 'Loan support inbox');
            $('#loanDashboardLiveChatProfileAvatar').text((name.charAt(0) || 'C').toUpperCase());
            $('#loanDashboardLiveChatProfileTime').text(thread.last_message_at ? formatLiveChatTime(thread.last_message_at) : 'Waiting for live activity');
            $('#loanDashboardLiveChatStatus').text(thread.status ? String(thread.status).replace(/_/g, ' ') : 'open');
            $('#loanDashboardLiveChatPriority').text(thread.priority ? String(thread.priority).replace(/_/g, ' ') : 'normal');
            $('#loanDashboardLiveChatTeam').text(thread.assigned_team || 'Support');
            $('#loanDashboardLiveChatUnread').text(intValue(thread.unread_count || 0));
            $('#loanDashboardLiveChatLastMessage').text(thread.last_message || 'No recent message yet.');

            var openUrl = thread.id ? (liveChatFrameBaseUrl + '/' + encodeURIComponent(thread.id)) : liveChatFrameBaseUrl;
            var embedUrl = openUrl + (openUrl.indexOf('?') === -1 ? '?' : '&') + '_lm_embed=1';
            $('#loanDashboardLiveChatOpenBtn').attr('href', openUrl);
            $('#loanDashboardLiveChatFrame').attr('src', embedUrl);
        }

        function renderLiveChatThreads() {
            var list = $('#loanDashboardLiveChatList');
            if (!list.length) {
                return;
            }

            var term = $.trim($('#loanDashboardLiveChatSearch').val() || '').toLowerCase();
            var rows = liveChatThreads.filter(function (thread) {
                if (!term) {
                    return true;
                }

                var hay = [
                    thread.display_name,
                    thread.display_subtitle,
                    thread.last_message,
                    thread.assigned_team,
                    thread.priority
                ].join(' ').toLowerCase();

                return hay.indexOf(term) !== -1;
            });

            if (!rows.length) {
                list.html('<div class="lm-live-chat-empty">No live chats found.</div>');
                return;
            }

            var html = '';
            rows.forEach(function (thread) {
                var activeClass = String(activeLiveChatId || '') === String(thread.id || '') ? ' is-active' : '';
                var unread = Number(thread.unread_count || 0);
                html += '<button type="button" class="lm-live-chat-item' + activeClass + '" data-live-chat-id="' + esc(thread.id || '') + '">'
                    + '<span class="lm-live-chat-avatar">' + esc((thread.display_name || 'C').charAt(0).toUpperCase()) + '</span>'
                    + '<span>'
                    + '<span class="lm-live-chat-name">' + esc(thread.display_name || 'Customer Chat') + '</span>'
                    + '<span class="lm-live-chat-preview">' + esc(thread.last_message || thread.display_subtitle || 'Open conversation') + '</span>'
                    + '</span>'
                    + '<span class="lm-live-chat-meta">'
                    + '<span class="lm-live-chat-time">' + esc(thread.last_message_time || '') + '</span>'
                    + (unread > 0 ? '<span class="lm-live-chat-badge">' + esc(intValue(unread)) + '</span>' : '')
                    + '</span>'
                    + '</button>';
            });

            list.html(html);
        }

        function loadLiveChatThreads() {
            fetch(liveChatApiUrl + '?view=all', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) { return response.ok ? response.json() : null; })
                .then(function (res) {
                    liveChatThreads = res && res.data ? res.data : [];
                    renderLiveChatThreads();

                    if (!liveChatThreads.length) {
                        setLiveChatProfile({});
                        return;
                    }

                    var selected = liveChatThreads.find(function (thread) {
                        return String(thread.id || '') === String(activeLiveChatId || '');
                    }) || liveChatThreads[0];

                    activeLiveChatId = selected.id || 0;
                    setLiveChatProfile(selected);
                    renderLiveChatThreads();
                })
                .catch(function () {
                    $('#loanDashboardLiveChatList').html('<div class="lm-live-chat-empty">Unable to load live chats right now.</div>');
                });
        }

        function refreshLoanDashboard() {
            if (loading || document.hidden) {
                return;
            }

            loading = true;
            fetch(liveUrl + window.location.search + (window.location.search ? '&' : '?') + 'realtime=1', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(function (response) {
                    var contentType = response.headers.get('content-type') || '';
                    if (!response.ok || contentType.indexOf('application/json') === -1) {
                        if (timer) {
                            window.clearInterval(timer);
                            timer = null;
                        }
                        return null;
                    }

                    return response.json();
                })
                .then(function (res) {
                    if (!res) {
                        return;
                    }

                    var data = res && res.data ? res.data : {};
                    updateCards(data.quick_cards || data.cards || {});
                    renderOverdueCustomers(data.tables ? data.tables.overdue_customers : []);
                    renderFollowUps(data.tables ? data.tables.follow_up_customers : []);
                    renderCollectorPerformance(data.charts ? data.charts.collector_performance : []);
                    updateChartText(data.charts ? data.charts.loan_status : null);
                    renderLiveCharts(data.charts || {});
                    if ($('[data-dashboard-pane="live"]').hasClass('is-active')) {
                        loadLiveChatThreads();
                    }
                })
                .catch(function () {})
                .finally(function () {
                    loading = false;
                });
        }

        $(function () {
            if (!$('#loanManagementApp').length) {
                return;
            }

            if (window.loanDashboardRealtimeTimer) {
                window.clearInterval(window.loanDashboardRealtimeTimer);
            }

            $('#loanDashboardQuickSearchInput').on('input', function () {
                window.clearTimeout(quickSearchTimer);
                quickSearchTimer = window.setTimeout(runQuickSearch, 250);
            });
            $('#loanDashboardQuickLocationFilter').on('change', function () {
                runQuickSearch();
            });
            $('#loanDashboardLiveChatSearch').on('input', function () {
                window.clearTimeout(liveChatSearchTimer);
                liveChatSearchTimer = window.setTimeout(renderLiveChatThreads, 160);
            });
            $(document).on('click', '[data-dashboard-tab]', function () {
                activateDashboardTab($(this).data('dashboard-tab'));
            });
            $(document).on('input', '#loanOverdueCustomersSearch', filterOverdueCustomers);
            $(document).on('click', '[data-live-chat-id]', function () {
                var threadId = $(this).data('live-chat-id');
                var selected = liveChatThreads.find(function (thread) {
                    return String(thread.id || '') === String(threadId || '');
                });
                if (!selected) {
                    return;
                }

                activeLiveChatId = selected.id || 0;
                setLiveChatProfile(selected);
                renderLiveChatThreads();
            });
            runQuickSearch();
            renderLiveCharts({});
            $(document).on('click', '.js-loan-detail-modal', function (event) {
                event.preventDefault();
                openDashboardIframeModal($(this).data('title') || 'Detail', $(this).data('url'));
            });

            timer = window.setInterval(refreshLoanDashboard, refreshMs);
            window.loanDashboardRealtimeTimer = timer;
            document.addEventListener('visibilitychange', function () {
                if (!document.hidden) {
                    refreshLoanDashboard();
                }
            });
        });
    })(jQuery);
</script>
@endsection
