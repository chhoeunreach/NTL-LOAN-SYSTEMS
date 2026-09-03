@php
    $loanUser = auth()->user();
    $locationName = null;
    $loanLanguage = session('user.language', config('app.locale'));
    $welcomeName = trim(collect([
        optional($loanUser)->first_name,
        optional($loanUser)->last_name,
    ])->filter()->implode(' '));
    $welcomeName = $welcomeName ?: (optional($loanUser)->username ?? optional($loanUser)->email ?? 'Staff');
    $welcomeText = $loanLanguage === 'km' ? 'សូមស្វាគមន៍, '.$welcomeName : 'Welcome, '.$welcomeName;
    $userInitial = strtoupper(substr($welcomeName, 0, 1));

    try {
        $locationName = session('user.business_location_name')
            ?? session('business.name')
            ?? optional(session('user'))->business_location_name;
    } catch (\Throwable $e) {
        $locationName = null;
    }
@endphp

<header class="lm-header sticky-top" id="loanManagementHeader">
    <div class="lm-header-left">
        <button type="button" class="lm-sidebar-toggle" id="loanSidebarToggle" aria-label="Toggle sidebar">
            <i class="fa fa-bars"></i>
        </button>
        <div>
            <h1 class="lm-title">{{ $welcomeText }}</h1>
        </div>
    </div>

    <div class="lm-header-right">
        @if(Route::has('loan-management.loans.calculator') && \Modules\LoanManagement\Helpers\LoanMenuHelper::loanUserCan('loan_management.loans.create|loan_management.create'))
            <a href="{{ route('loan-management.loans.calculator', ['_lm_modal' => 1]) }}"
               class="btn btn-default btn-sm lm-header-action js-loan-calculator-modal"
               data-title="Loan Calculator">
                <i class="fa fa-calculator"></i> <span class="hidden-xs">Loan Calculator</span><span class="visible-xs-inline"> Calc</span>
            </a>
        @endif

        @if(Route::has('loan-management.loans.create-standalone-modal') && \Modules\LoanManagement\Helpers\LoanMenuHelper::loanUserCan('loan_management.loans.create|loan_management.create'))
            <button type="button" class="btn btn-success btn-sm lm-header-action lm-standalone-loan-trigger d-none d-lg-inline-flex"
                    data-url="{{ route('loan-management.loans.create-standalone-modal') }}"
                    data-target="#standaloneLoanModal">
                <i class="fa fa-plus-circle"></i> <span>New Loan</span>
            </button>
        @endif

        @if(Route::has('loan-management.language.switch'))
            <div class="lm-language-switch" title="Loan language">
                @foreach(['en' => 'EN', 'km' => 'ខ្មែរ'] as $languageKey => $languageLabel)
                    <form method="POST" action="{{ route('loan-management.language.switch') }}">
                        @csrf
                        <input type="hidden" name="language" value="{{ $languageKey }}">
                        <button type="submit" class="{{ $loanLanguage === $languageKey ? 'active' : '' }}" {{ $loanLanguage === $languageKey ? 'disabled' : '' }}>
                            {{ $languageLabel }}
                        </button>
                    </form>
                @endforeach
            </div>
        @endif

        <div class="dropdown lm-user-profile">
            <button type="button" class="lm-user-profile-toggle dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="lm-user-avatar">{{ $userInitial }}</span>
                <span class="lm-user-profile-text">
                    <span class="lm-user-name">{{ $loanUser->username ?? $loanUser->first_name ?? 'Staff' }}</span>
                    @if(!empty($locationName))
                        <span class="lm-location">{{ $locationName }}</span>
                    @endif
                </span>
                <i class="fa fa-angle-down"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-right lm-user-profile-menu">
                <li class="lm-user-profile-summary">
                    <span class="lm-user-name">{{ $loanUser->username ?? $loanUser->first_name ?? 'Staff' }}</span>
                    @if(!empty($locationName))
                        <span class="lm-location">{{ $locationName }}</span>
                    @endif
                </li>
                @if (Route::has('logout'))
                    <li role="separator" class="divider"></li>
                    <li>
                        <a href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('loanLogoutForm').submit();">
                            <i class="fa fa-sign-out"></i> Logout
                        </a>
                    </li>
                @endif
            </ul>
        </div>

        @if (Route::has('logout'))
            <form id="loanLogoutForm" action="{{ route('logout') }}" method="POST" style="display:none;">
                @csrf
            </form>
        @endif
    </div>
</header>
