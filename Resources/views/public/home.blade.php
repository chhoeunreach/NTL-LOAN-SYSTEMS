@php
    $businessLogoUrl = \Modules\LoanManagement\Services\BusinessSettingsService::publicLogoUrl();
    $businessName = \Modules\LoanManagement\Services\BusinessSettingsService::businessName();
    $headline = $settings['home_headline'] ?? 'Simple loan service for customers';
    $subtitle = $settings['home_subtitle'] ?? '';
    $body = $settings['home_body'] ?? '';
    $cms = \Modules\LoanManagement\Services\CmsHomeService::normalize($settings['home_cms'] ?? []);
    $menuSections = ['home' => 'hero', 'products' => 'catalog', 'how' => 'guide', 'cart' => 'cart', 'about' => 'about', 'contact' => 'contact'];
    $visibleMenu = array_filter($menuSections, fn ($section, $key) => $cms['menu_'.$key] && $cms[$section] && ($key !== 'cart' || $cms['catalog']), ARRAY_FILTER_USE_BOTH);

    $customerUser = Auth::guard('customer_loan')->user();
    $adminUser = Auth::guard('web')->user() ?? Auth::user();

    $customerPhotoUrl = $customerUser ? $customerUser->customer_photo_url : null;
    $adminPhotoUrl = null;
    if ($adminUser) {
        if (!empty($adminUser->profile_photo_url)) {
            $adminPhotoUrl = $adminUser->profile_photo_url;
        } elseif (!empty($adminUser->profile_photo)) {
            $adminPhotoUrl = asset('uploads/profile_photos/' . $adminUser->profile_photo);
        } elseif (session()->has('user.profile_photo_url')) {
            $adminPhotoUrl = session('user.profile_photo_url');
        }
    }
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $businessName }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --public-primary:#e06b29; --ink:#202020; --muted:#667085; --line:#e4e7ec; --panel:#fff; --soft:#f8f9fb; }
        * { box-sizing:border-box; letter-spacing:0; }
        html { scroll-behavior:smooth; scroll-padding-top:90px; }
        body { margin:0; font:14px/1.5 Arial,sans-serif; color:var(--ink); background:var(--soft); }
        a { color:inherit; text-decoration:none; }
        button,input,select { font:inherit; }
        button,a,input,select { -webkit-tap-highlight-color:transparent; }
        button { cursor:pointer; }
        :focus-visible { outline:3px solid #36a3bc; outline-offset:3px; }
        svg { width:18px; height:18px; flex-shrink:0; }
        .announcement { background:#202020; color:#ddd; font-size:12px; padding:9px 0; }
        .announcement-inner,.nav-inner,.section-inner,.footer-inner,.brand-strip-inner { max-width:1240px; width:calc(100% - 48px); margin:auto; }
        .announcement-inner { display:flex; justify-content:space-between; gap:16px; }
        .announcement-links { display:flex; flex-wrap:wrap; gap:16px; }
        .announcement i { color:#f69b64; margin-right:6px; }
        .site-nav { position:sticky; top:0; z-index:50; background:#fff; border-bottom:1px solid var(--line); }
        .nav-inner { display:flex; align-items:center; justify-content:space-between; min-height:78px; gap:22px; }
        .brand { display:flex; align-items:center; gap:10px; min-width:0; }
        .brand-logo { width:48px; height:44px; background:#202020; color:#fff; border-radius:4px; display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:800; flex-shrink:0; overflow:hidden; }
        .brand-logo img { width:100%; height:100%; object-fit:contain; background:#fff; }
        .brand-text { font-size:18px; font-weight:800; overflow-wrap:anywhere; }
        .brand-text small { display:block; color:var(--muted); font-size:10px; font-weight:400; }
        .menu { display:flex; gap:22px; align-items:center; font-weight:600; font-size:13px; }
        .menu a { padding:12px 0; overflow-wrap:anywhere; }
        .menu a.active,.menu a:hover { color:var(--public-primary); }
        .nav-actions { display:flex; align-items:center; gap:10px; }
        .button,.button-outline,.cart-apply { display:inline-flex; justify-content:center; align-items:center; gap:8px; min-height:42px; padding:10px 20px; border-radius:6px; background:var(--public-primary); color:#fff; border:1px solid var(--public-primary); font-weight:700; text-align:center; }
        .button:hover,.cart-apply:hover { background:#bd5019; }
        .button-outline { background:#fff; color:#303030; border-color:var(--line); }
        .button-outline:hover { border-color:var(--public-primary); color:var(--public-primary); }
        .menu-toggle { display:none; width:40px; height:40px; border:1px solid var(--line); background:#fff; border-radius:4px; }
        .hero { position:relative; color:#fff; background:#222; }
        .hero-image { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; object-position:center 55%; }
        .hero::after { content:''; position:absolute; inset:0; background:rgba(0,0,0,.50); }
        .hero-inner { position:relative; z-index:1; max-width:1240px; width:calc(100% - 48px); margin:auto; min-height:410px; padding:55px 0; display:flex; align-items:center; }
        .hero-copy { max-width:640px; }
        .eyebrow { font-size:12px; font-weight:700; color:#ffc39e; }
        .hero h1 { margin:12px 0; font-size:44px; line-height:1.15; overflow-wrap:anywhere; }
        .hero .subtitle { font-size:20px; margin:0 0 10px; }
        .hero .body-copy { color:#f2f2f2; font-size:15px; max-width:540px; margin:0 0 22px; }
        .hero-cta { display:flex; flex-wrap:wrap; gap:12px; }
        .hero .button-outline { background:rgba(0,0,0,.25); color:#fff; border-color:#fff; }
        .brand-strip { padding:20px 0; background:#fff; border-bottom:1px solid var(--line); }
        .brand-strip-inner { display:flex; flex-wrap:wrap; align-items:center; justify-content:center; gap:24px; }
        .brand-strip small { color:var(--muted); font-size:11px; }
        .brand-strip span { font-size:17px; font-weight:700; color:#555; }
        .section { padding:44px 0; }
        .section.alt { background:#fff; }
        .section-head { margin-bottom:24px; }
        .section h2 { margin:0 0 8px; font-size:26px; line-height:1.25; }
        .section-head p { color:var(--muted); margin:0; }
        .feature-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:30px; }
        .feature { padding:16px 0; min-width:0; }
        .feature-icon { width:40px; height:40px; display:grid; place-items:center; background:#fff0e6; color:#bd5019; font-weight:700; border-radius:6px; margin-bottom:14px; }
        .feature strong { display:block; font-size:18px; margin-bottom:6px; }
        .feature span { display:block; color:var(--muted); }
        .shop-inner { display:grid; grid-template-columns:minmax(0,1fr) 330px; gap:28px; align-items:start; }
        .shop-inner > div { min-width:0; }
        .shop-inner.without-cart { grid-template-columns:minmax(0,1fr); }
        .contact-details { display:flex; flex-wrap:wrap; gap:20px; }
        .contact-details p,.section p { white-space:pre-line; overflow-wrap:anywhere; }
        .catalog-filter-bar { margin-bottom:20px; }
        .catalog-search-row { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:12px; }
        .search-input-box { position:relative; flex:1; min-width:180px; }
        .search-input-box > svg { position:absolute; left:12px; top:13px; color:var(--muted); }
        .search-input-box input { width:100%; height:44px; border:1px solid var(--line); border-radius:6px; padding:0 36px; background:#fff; }
        .search-clear-btn { position:absolute; right:8px; top:7px; border:0; background:none; font-size:22px; height:30px; }
        .sort-select-box select { height:44px; max-width:100%; border:1px solid var(--line); border-radius:6px; background:#fff; padding:0 10px; }
        .category-chips-scroll { display:flex; gap:8px; flex-wrap:wrap; }
        .category-chip { min-height:34px; border:1px solid var(--line); background:#fff; padding:6px 12px; border-radius:4px; font-size:12px; }
        .category-chip.active { background:var(--public-primary); border-color:var(--public-primary); color:#fff; }
        .chip-count { margin-left:6px; font-weight:700; }
        .product-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; }
        .product-card { display:flex; flex-direction:column; min-width:0; overflow:hidden; background:#fff; border:1px solid var(--line); border-radius:8px; }
        .product-image-wrap { position:relative; aspect-ratio:4/3; background:#f1f3f5; display:grid; place-items:center; }
        .product-image-wrap img { width:100%; height:100%; object-fit:contain; padding:24px; }
        .product-fallback-icon { color:#9aa4b2; }
        .card-badge-top-left,.card-badge-top-right { position:absolute; top:10px; background:#fff; font-size:10px; font-weight:700; padding:4px 7px; border-radius:4px; }
        .card-badge-top-left { left:10px; color:#187651; }
        .card-badge-top-right { right:10px; color:#77411c; }
        .product-body { display:flex; flex-direction:column; gap:8px; padding:18px; flex:1; }
        .product-brand-tag { color:var(--muted); font-size:11px; }
        .product-title { margin:0; font-size:17px; line-height:1.4; min-height:48px; overflow-wrap:anywhere; }
        .product-sku { color:var(--muted); font-size:11px; }
        .product-price-row { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:8px; margin-top:auto; padding-top:8px; }
        .price-cash { font-size:22px; font-weight:800; color:#bd5019; }
        .price-monthly-tag { font-size:11px; color:#187651; }
        .product-actions-grid { display:flex; gap:8px; margin-top:8px; }
        .cart-btn,.apply-btn { display:flex; align-items:center; justify-content:center; gap:6px; border:1px solid #252525; background:#252525; color:#fff; min-height:40px; border-radius:6px; font-size:12px; padding:8px 10px; font-weight:600; }
        .cart-btn { flex:1; }
        .apply-btn { background:#fff; color:#252525; }
        .cart-btn:hover { background:var(--public-primary); border-color:var(--public-primary); }
        .cart-panel { position:sticky; top:100px; padding:22px; background:#fff; border:1px solid var(--line); border-radius:8px; }
        .cart-panel-head { display:flex; align-items:center; justify-content:space-between; gap:8px; padding-bottom:14px; border-bottom:1px solid var(--line); }
        .cart-panel h2 { font-size:18px; margin:0; }
        .cart-count-pill { font-size:11px; color:#a24415; white-space:nowrap; }
        .cart-items { padding:12px 0; }
        .cart-empty { font-size:13px; color:var(--muted); padding:14px 0; }
        .cart-item { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:12px 0; border-bottom:1px solid var(--line); }
        .cart-item > div:first-child { min-width:0; }
        .cart-item strong { display:block; font-size:12px; overflow-wrap:anywhere; }
        .cart-item span { display:block; font-size:12px; margin-top:4px; }
        .qty-row { display:flex; align-items:center; gap:8px; flex-shrink:0; }
        .qty-row button { width:28px; height:28px; background:#fff; border:1px solid var(--line); border-radius:4px; }
        .cart-total { display:flex; justify-content:space-between; gap:10px; border-top:1px solid var(--line); padding:18px 0; font-weight:700; }
        .cart-apply { width:100%; font-size:13px; }
        .footer { background:#202020; color:#b8bec8; padding:40px 0 24px; }
        .footer-grid { display:grid; grid-template-columns:2fr 1fr 1fr; gap:32px; margin-bottom:28px; }
        .footer h3,.footer h4 { color:#fff; margin:0 0 12px; font-size:16px; }
        .footer p { font-size:13px; max-width:400px; }
        .footer-links { display:flex; flex-direction:column; gap:9px; font-size:13px; }
        .footer a:hover { color:#ffc39e; }
        .footer-bottom { border-top:1px solid #414141; padding-top:18px; font-size:12px; }
        .user-dropdown-wrapper { position:relative; }
        .user-profile-btn { display:flex; align-items:center; gap:8px; background:#fff; border:1px solid var(--line); border-radius:6px; min-height:42px; padding:6px 10px; }
        .user-profile-name { max-width:120px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:600; }
        .user-avatar-img,.user-avatar-badge { width:28px; height:28px; object-fit:cover; border-radius:50%; display:grid; place-items:center; background:#fce8da; }
        .user-dropdown-menu { display:none; position:absolute; right:0; top:calc(100% + 8px); width:270px; max-width:calc(100vw - 24px); background:#fff; border:1px solid var(--line); border-radius:8px; box-shadow:0 10px 28px #0002; overflow:hidden; z-index:80; }
        .user-dropdown-wrapper.open .user-dropdown-menu { display:block; }
        .dropdown-header-box { display:flex; gap:10px; padding:16px; align-items:center; }
        .dropdown-avatar-circle,.dropdown-avatar-circle-fallback { width:36px; height:36px; border-radius:50%; background:#fce8da; object-fit:cover; display:grid; place-items:center; flex-shrink:0; }
        .dropdown-user-name { font-weight:700; overflow-wrap:anywhere; }
        .dropdown-user-sub { color:var(--muted); font-size:11px; overflow-wrap:anywhere; }
        .dropdown-item { display:flex; gap:10px; align-items:center; width:100%; padding:12px 16px; background:#fff; border:0; font-size:12px; text-align:left; }
        .dropdown-item:hover { background:#f4f5f7; }
        .dropdown-item.danger { color:#ba2929; }
        .dropdown-divider { border-top:1px solid var(--line); }
        .mobile-bottom-nav { display:none; }
        @media (max-width:1050px) {
            .menu { gap:12px; font-size:12px; }
            .nav-inner { gap:12px; }
            .brand-text { font-size:15px; }
            .shop-inner { grid-template-columns:minmax(0,1fr) 290px; gap:18px; }
        }
        @media (max-width:1100px) {
            .announcement-inner,.nav-inner,.section-inner,.footer-inner,.brand-strip-inner,.hero-inner { width:calc(100% - 32px); }
            .menu-toggle { display:block; }
            .menu { display:none; position:absolute; top:100%; left:0; right:0; background:#fff; border-bottom:1px solid var(--line); padding:12px 16px; flex-direction:column; align-items:stretch; gap:0; }
            .menu.is-open { display:flex; }
            .menu a { padding:12px 0; }
            .nav-inner { min-height:68px; }
            .shop-inner { grid-template-columns:1fr; }
            .cart-panel { position:static; }
            .feature-grid { gap:16px; }
            .hero h1 { font-size:36px; }
            .hero-inner { min-height:350px; padding:38px 0; }
        }
        @media (max-width:520px) {
            .announcement-inner { flex-direction:column; gap:4px; }
            .announcement { font-size:11px; }
            .brand-logo { width:34px; height:36px; font-size:15px; }
            .brand-text { font-size:13px; max-width:125px; }
            .brand-text small { font-size:8px; }
            .nav-inner { gap:6px; }
            .nav-actions { gap:6px; }
            .nav-actions > .button,.nav-actions > .button-outline { padding:7px 9px; min-height:36px; font-size:11px; }
            .menu-toggle { width:34px; height:36px; }
            .user-profile-name { max-width:60px; font-size:11px; }
            .hero h1 { font-size:32px; }
            .hero .subtitle { font-size:17px; }
            .hero .body-copy { font-size:13px; }
            .hero .button,.hero .button-outline { padding:9px 14px; font-size:12px; }
            .section { padding:28px 0; }
            .section h2 { font-size:23px; }
            .feature-grid { grid-template-columns:1fr; gap:0; }
            .feature { display:grid; grid-template-columns:40px 1fr; gap:4px 14px; border-bottom:1px solid var(--line); }
            .feature-icon { grid-row:span 2; margin:0; }
            .feature strong { margin:0; font-size:16px; }
            .feature span { font-size:13px; }
            .product-grid { gap:12px; }
            .product-body { padding:12px; }
            .product-title { font-size:14px; min-height:42px; }
            .product-image-wrap img { padding:16px; }
            .product-price-row { align-items:start; flex-direction:column; }
            .price-cash { font-size:18px; }
            .product-actions-grid { flex-direction:column; }
            .card-badge-top-left { left:6px; top:6px; }
            .card-badge-top-right { right:6px; top:auto; bottom:6px; }
            .footer-grid { grid-template-columns:1fr 1fr; gap:24px; }
            .footer-grid > div:first-child { grid-column:1/-1; }
        }
        @media (prefers-reduced-motion:reduce) { html { scroll-behavior:auto; } }
    </style>
</head>
<body>
    <main class="public-shell">
        @if($cms['announcement'])
        <div class="announcement"><div class="announcement-inner">
            <span>{{ $businessName }} &middot; {{ $cms['announcement_text'] }}</span>
            <div class="announcement-links"><a href="{{ route('loan-management.public.customer-login') }}">Customer Portal</a><a href="{{ route('login') }}">Staff Login</a></div>
        </div></div>
        @endif
        <header class="site-nav">
            <div class="nav-inner">
                <a class="brand" href="{{ route('loan-management.public.home') }}">
                    <span class="brand-logo">
                        @if($businessLogoUrl)
                            <img src="{{ $businessLogoUrl }}" alt="{{ $businessName }}">
                        @else
                            {{ strtoupper(mb_substr($businessName, 0, 1)) }}
                        @endif
                    </span>
                    <span class="brand-text">{{ $businessName }}<small>{{ $cms['tagline'] }}</small></span>
                </a>
                @if(!empty($visibleMenu))<button type="button" class="menu-toggle" id="publicMenuToggle" aria-label="Toggle navigation" aria-controls="publicMainMenu" aria-expanded="false"><i class="fa-solid fa-bars" aria-hidden="true"></i></button>@endif
                <nav class="menu" id="publicMainMenu" aria-label="Main menu">
                    @foreach($visibleMenu as $key => $section)
                        <a href="#{{ $key }}" data-section-link="{{ $key }}" @class(['active' => $loop->first])>{{ $cms['label_'.$key] }}</a>
                    @endforeach
                </nav>
                <div class="nav-actions">
                    @if($adminUser)
                        <div class="user-dropdown-wrapper" id="adminDropdownWrapper">
                            <button type="button" class="user-profile-btn admin-profile-btn" id="adminProfileToggle" aria-expanded="false">
                                @if($adminPhotoUrl)
                                    <img src="{{ $adminPhotoUrl }}" class="user-avatar-img" alt="Admin">
                                @else
                                    <span class="user-avatar-badge admin-avatar-badge">⚡</span>
                                @endif
                                <span class="user-profile-name">{{ $adminUser->name ?? $adminUser->username ?? 'Admin' }}</span>
                                <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <div class="user-dropdown-menu" id="adminDropdownMenu">
                                <div class="dropdown-header-box">
                                    @if($adminPhotoUrl)
                                        <img src="{{ $adminPhotoUrl }}" class="dropdown-avatar-circle" alt="Admin">
                                    @else
                                        <div class="dropdown-avatar-circle-fallback admin-avatar-circle-fallback">⚡</div>
                                    @endif
                                    <div style="min-width: 0;">
                                        <div class="dropdown-user-name">{{ $adminUser->name ?? $adminUser->username ?? 'Administrator' }}</div>
                                        <div class="dropdown-user-sub">{{ $adminUser->email ?? 'Admin & Staff Portal' }}</div>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a href="{{ route('loan-management.dashboard') }}" class="dropdown-item">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                    Admin Dashboard
                                </a>
                                <a href="{{ Route::has('logout') ? route('logout') : url('/logout') }}?redirect={{ urlencode(route('loan-management.public.customer-login')) }}" class="dropdown-item" onclick="return confirm('Are you sure you want to log out and switch to Customer Portal?');" title="Switch or login as customer">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                    Switch to Customer
                                </a>
                                <a href="{{ Route::has('logout') ? route('logout') : url('/logout') }}?redirect={{ urlencode(route('login')) }}" class="dropdown-item" onclick="return confirm('Are you sure you want to log out and switch admin account?');" title="Switch or login as another admin user">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                    Switch / Other Admin
                                </a>
                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ Route::has('logout') ? route('logout') : url('/logout') }}" style="margin: 0;" onsubmit="return confirm('Are you sure you want to log out?');">
                                    @csrf
                                    <button type="submit" class="dropdown-item danger">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                        Logout Admin
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif

                    @if($customerUser)
                        <div class="user-dropdown-wrapper" id="customerDropdownWrapper">
                            <button type="button" class="user-profile-btn" id="customerProfileToggle" aria-expanded="false">
                                @if($customerPhotoUrl)
                                    <img src="{{ $customerPhotoUrl }}" class="user-avatar-img" alt="{{ $customerUser->name ?: 'Customer' }}">
                                @else
                                    <span class="user-avatar-badge">{{ strtoupper(mb_substr($customerUser->name ?: 'C', 0, 1)) }}</span>
                                @endif
                                <span class="user-profile-name">{{ $customerUser->name ?: 'Customer' }}</span>
                                <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                            <div class="user-dropdown-menu" id="customerDropdownMenu">
                                <div class="dropdown-header-box">
                                    @if($customerPhotoUrl)
                                        <img src="{{ $customerPhotoUrl }}" class="dropdown-avatar-circle" alt="{{ $customerUser->name ?: 'Customer' }}">
                                    @else
                                        <div class="dropdown-avatar-circle-fallback">{{ strtoupper(mb_substr($customerUser->name ?: 'C', 0, 1)) }}</div>
                                    @endif
                                    <div style="min-width: 0;">
                                        <div class="dropdown-user-name">{{ $customerUser->name ?: 'Customer' }}</div>
                                        <div class="dropdown-user-sub">{{ $customerUser->phone ?: $customerUser->username }}</div>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a href="{{ route('loan-management.public.customer-dashboard') }}" class="dropdown-item">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                                    My Dashboard
                                </a>
                                <a href="{{ route('loan-management.public.customer-logout') }}?redirect={{ urlencode(route('loan-management.public.customer-login')) }}" class="dropdown-item" onclick="return confirm('Are you sure you want to log out and switch to another account?');" title="Switch or add another customer account">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                    Switch Account
                                </a>
                                @if(! $adminUser)
                                    <a href="{{ route('loan-management.public.customer-logout') }}?redirect={{ urlencode(route('login')) }}" class="dropdown-item" onclick="return confirm('Are you sure you want to log out and go to Admin Login?');">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                        Admin Login
                                    </a>
                                @endif
                                <div class="dropdown-divider"></div>
                                <form method="POST" action="{{ route('loan-management.public.customer-logout') }}" style="margin: 0;" onsubmit="return confirm('Are you sure you want to log out?');">
                                    @csrf
                                    <button type="submit" class="dropdown-item danger">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                        Logout Customer
                                    </button>
                                </form>
                            </div>
                        </div>
                    @elseif(! $adminUser)
                        <a class="button-outline" href="{{ route('loan-management.public.customer-login') }}">Login</a>
                        <a class="button" href="{{ route('loan-management.public.register') }}">Register</a>
                    @endif
                </div>
            </div>
        </header>

        @if($cms['hero'])
        <section class="hero" id="home">
            <img class="hero-image" src="{{ route('loan-management.public.home-image') }}" alt="Computer and technology showroom" fetchpriority="high">
            <div class="hero-inner">
                <div class="hero-copy">
                    <span class="eyebrow">{{ $cms['hero_eyebrow'] }}</span>
                    <h1>{{ $businessName }}</h1>
                    <p class="subtitle">{{ $headline }}</p>
                    @if($subtitle)
                        <p class="subtitle">{{ $subtitle }}</p>
                    @endif
                    @if($body)
                        <p class="body-copy">{{ $body }}</p>
                    @endif
                    <div class="hero-cta">
                        @if($customerUser)
                            <a class="button" href="{{ route('loan-management.public.customer-dashboard') }}">Go to My Dashboard</a>
                            @if($cms['catalog'])<a class="button-outline" href="#products">Shop Products</a>@endif
                        @elseif($adminUser)
                            <a class="button" href="{{ route('loan-management.dashboard') }}">Open Admin Dashboard</a>
                            <a class="button-outline" href="{{ route('loan-management.public.customer-login') }}" onclick="return confirm('You are currently signed in as Administrator ({{ $adminUser->name ?? $adminUser->username ?? 'Admin' }}). Are you sure you want to log out first to switch to Customer Portal?');">Customer Login / Switch</a>
                        @else
                            @if($cms['catalog'])<a class="button" href="#products">Shop Products</a>@endif
                            <a class="button-outline" href="{{ route('loan-management.public.register') }}">Apply Now</a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
        @endif

        @if($cms['brands'] && !empty($brands))
            <div class="brand-strip"><div class="brand-strip-inner"><strong>{{ $cms['brands_title'] }}</strong>@foreach($brands as $brand)<span>{{ $brand }}</span>@endforeach</div></div>
        @endif

        @if($cms['guide'])
        <section class="section alt" id="how">
            <div class="section-inner">
                <div class="section-head">
                    <div>
                        <h2>{{ $cms['guide_title'] }}</h2>
                        <p>{{ $cms['guide_description'] }}</p>
                    </div>
                </div>
                <div class="feature-grid">
                    @foreach([1, 2, 3] as $step)
                        <div class="feature"><div class="feature-icon">{{ $step }}</div><strong>{{ $cms['step_'.$step.'_title'] }}</strong><span>{{ $cms['step_'.$step.'_body'] }}</span></div>
                    @endforeach
                </div>
            </div>
        </section>
        @endif

        @if($cms['catalog'])
        <section class="section" id="products">
            <div @class(['section-inner', 'shop-inner', 'without-cart' => !$cms['cart']])>
                <div>
                    <div class="section-head">
                        <div>
                            <h2>{{ $cms['catalog_title'] }}</h2>
                            <p>{{ $cms['catalog_description'] }}</p>
                        </div>
                    </div>

                    {{-- Search & Category Filter Toolbar --}}
                    <div class="catalog-filter-bar">
                        <div class="catalog-search-row">
                            <div class="search-input-box">
                                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input type="text" id="catalogSearchInput" aria-label="Search products" placeholder="Search products, brand, SKU..." autocomplete="off">
                                <button type="button" class="search-clear-btn" id="searchClearBtn" style="display: none;" title="Clear search">&times;</button>
                            </div>
                            <div class="sort-select-box">
                                <select id="catalogSortSelect" aria-label="Sort products">
                                    <option value="default">Featured / Newest</option>
                                    <option value="price_low">Price: Low to High</option>
                                    <option value="price_high">Price: High to Low</option>
                                    <option value="name_asc">Name: A to Z</option>
                                </select>
                            </div>
                        </div>

                        {{-- Category Filter Chips --}}
                        <div class="category-chips-scroll" id="categoryChipsContainer">
                            <button type="button" class="category-chip active" data-category="all">
                                All Products <span class="chip-count" id="totalCountPill">{{ count($products) }}</span>
                            </button>
                            @foreach($categories ?? [] as $cat)
                                @php
                                    $catCount = collect($products)->where('category', $cat)->count();
                                @endphp
                                <button type="button" class="category-chip" data-category="{{ strtolower($cat) }}">
                                    {{ $cat }} <span class="chip-count">{{ $catCount }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Product Grid --}}
                    @if(!empty($products))
                        <div class="product-grid" id="productsCatalogGrid">
                            @foreach($products as $product)
                                @php
                                    $pPrice = (float) ($product['price'] ?? 0);
                                    $pName = (string) ($product['name'] ?? 'Product');
                                    $pSku = (string) ($product['sku'] ?? '');
                                    $pBrand = (string) ($product['brand'] ?? '');
                                    $pCat = (string) ($product['category'] ?? 'General');
                                    $pMinDp = (float) ($product['min_down_payment_percent'] ?? 0);
                                    $estMonthly = $pPrice > 0 ? round(($pPrice * 1.18) / 12, 2) : 0;
                                @endphp
                                <article class="product-card"
                                    data-name="{{ strtolower($pName) }}"
                                    data-sku="{{ strtolower($pSku) }}"
                                    data-brand="{{ strtolower($pBrand) }}"
                                    data-category="{{ strtolower($pCat) }}"
                                    data-price="{{ $pPrice }}"
                                    data-id="{{ $product['id'] ?? $loop->index }}">

                                    <div class="product-image-wrap">
                                        @if(!empty($product['image_url']))
                                            <img src="{{ $product['image_url'] }}" alt="{{ $pName }}" loading="lazy">
                                        @else
                                            <div class="product-fallback-icon">
                                                <svg width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                                            </div>
                                        @endif

                                        @if($pCat && strtolower($pCat) !== 'general')
                                            <span class="card-badge-top-left">{{ $pCat }}</span>
                                        @endif

                                        @if($pMinDp > 0)
                                            <span class="card-badge-top-right">Min {{ $pMinDp }}% DP</span>
                                        @else
                                            <span class="card-badge-top-right">0% Down Payment</span>
                                        @endif
                                    </div>

                                    <div class="product-body">
                                        @if($pBrand)
                                            <div class="product-brand-tag">{{ $pBrand }}</div>
                                        @endif
                                        <h3 class="product-title">{{ $pName }}</h3>
                                        <div class="product-sku">{{ $pSku ? 'SKU: ' . $pSku : 'Installment Eligible' }}</div>

                                        <div class="product-price-row">
                                            <div class="price-cash">${{ number_format($pPrice, 2) }}</div>
                                            @if($estMonthly > 0)
                                                <div class="price-monthly-tag">From ${{ number_format($estMonthly, 2) }}/mo</div>
                                            @endif
                                        </div>

                                        <div class="product-actions-grid">
                                            @if($cms['cart'])<button type="button" class="cart-btn" data-product='@json($product)'>
                                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                                                Add to Cart
                                            </button>@endif
                                            @if($customerUser)
                                                <a href="{{ route('loan-management.public.customer-loan-request', ['product_id' => $product['id']]) }}" class="apply-btn" title="Direct Installment Request">
                                                    Apply
                                                </a>
                                            @else
                                                <a href="{{ route('loan-management.public.register') }}" class="apply-btn" title="Direct Installment Request">
                                                    Apply
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                        <div id="noProductsFoundMsg" style="display: none; padding: 48px 20px; text-align: center; background: #fff; border: 1px dashed #cbd5e1; border-radius: 12px; margin-top: 10px;">
                            <div style="font-size: 38px; color: #94a3b8; margin-bottom: 8px;">🔍</div>
                            <h4 style="margin: 0 0 4px; font-weight: 800; color: #0f172a;">No matching products found</h4>
                            <p style="margin: 0 0 14px; color: #64748b; font-size: 13px;">Try searching with another keyword or resetting the category filter.</p>
                            <button type="button" class="button-outline" onclick="resetAllFilters()" style="min-height: 36px; padding: 0 14px; font-size: 13px;">Reset Filters</button>
                        </div>
                    @else
                        <div class="feature">
                            <strong>No installment products available</strong>
                            <span>Please check back soon for new products.</span>
                        </div>
                    @endif
                </div>

                @if($cms['cart'])<aside class="cart-panel" id="cart">
                    <div class="cart-panel-head">
                        <h2>Installment Cart</h2>
                        <span class="cart-count-pill" id="cartCountPill">0 items</span>
                    </div>
                    <div class="cart-items" id="cartItems"></div>
                    <div class="cart-total">
                        <span>Estimated Total</span>
                        <span id="cartTotal">$0.00</span>
                    </div>
                    @if($customerUser)
                        <a class="cart-apply" href="{{ route('loan-management.public.customer-loan-request') }}" id="cartApply">
                            Apply Installment
                        </a>
                    @else
                        <a class="cart-apply" href="{{ route('loan-management.public.register') }}" id="cartApply">
                            Apply Installment
                        </a>
                    @endif
                </aside>@endif
            </div>
        </section>
        @endif

        @if($cms['about'])
        <section class="section alt" id="about"><div class="section-inner">
            <h2>{{ $cms['about_title'] }}</h2>
            <p>{{ $body ?: $subtitle }}</p>
        </div></section>
        @endif
        @if($cms['contact'])
            <section class="section" id="contact"><div class="section-inner">
                <h2>{{ $cms['contact_title'] }}</h2>
                <div class="contact-details">
                    @if($cms['contact_phone'])<p><i class="fa-solid fa-phone" aria-hidden="true"></i> <a href="tel:{{ preg_replace('/[^0-9+]/', '', $cms['contact_phone']) }}">{{ $cms['contact_phone'] }}</a></p>@endif
                    @if($cms['contact_email'])<p><i class="fa-solid fa-envelope" aria-hidden="true"></i> <a href="mailto:{{ $cms['contact_email'] }}">{{ $cms['contact_email'] }}</a></p>@endif
                    @if($cms['contact_address'])<p><i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $cms['contact_address'] }}</p>@endif
                    @if($cms['contact_hours'])<p><i class="fa-solid fa-clock" aria-hidden="true"></i> {{ $cms['contact_hours'] }}</p>@endif
                </div>
                <a class="button-outline" href="{{ route('loan-management.public.customer-login') }}">Customer Portal</a>
            </div></section>
        @endif

        @if($cms['footer'])<footer class="footer">
            <div class="footer-inner">
                <div class="footer-grid">
                <div><h3>{{ $businessName }}</h3><p>{{ $cms['tagline'] }}</p><p>{{ $cms['footer_text'] }}</p></div>
                <div><h3>Quick Links</h3><div class="footer-links">@foreach($visibleMenu as $key => $section)<a href="#{{ $key }}">{{ $cms['label_'.$key] }}</a>@endforeach</div></div>
                <div><h3>Accounts</h3><div class="footer-links">
                    @if($customerUser)
                        <a href="{{ route('loan-management.public.customer-dashboard') }}">Customer Dashboard</a>
                    @endif
                    @if($adminUser)
                        <a href="{{ route('loan-management.dashboard') }}">Admin Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" @if($customerUser) onclick="return confirm('You are currently signed in as Customer ({{ $customerUser->name }}). Are you sure you want to log out first to access Admin Login?');" @endif>Admin Login</a>
                    @endif
                    <a href="{{ route('loan-management.public.customer-login') }}" @if($adminUser) onclick="return confirm('You are currently signed in as Administrator ({{ $adminUser->name ?? 'Admin' }}). Are you sure you want to log out first to switch to Customer Portal?');" @elseif($customerUser) onclick="return confirm('You are currently signed in as Customer ({{ $customerUser->name }}). Are you sure you want to log out first to switch accounts?');" @endif>Customer Portal</a>
                </div></div></div>
                <div class="footer-bottom">&copy; {{ date('Y') }} {{ $businessName }}. All rights reserved.</div>
            </div>
        </footer>@endif

    </main>

    <script>
        (function () {
            var menuToggle = document.getElementById('publicMenuToggle');
            var mainMenu = document.getElementById('publicMainMenu');
            function closeMenu() {
                if (!menuToggle) return;
                mainMenu.classList.remove('is-open');
                menuToggle.setAttribute('aria-expanded', 'false');
            }
            if (menuToggle) menuToggle.addEventListener('click', function () {
                var open = mainMenu.classList.toggle('is-open');
                menuToggle.setAttribute('aria-expanded', String(open));
            });
            mainMenu.addEventListener('click', function (event) {
                if (event.target.closest('a')) closeMenu();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') closeMenu();
            });
            var cartKey = 'loan_public_installment_cart';
            var cart = [];
            var itemsBox = document.getElementById('cartItems');
            var totalBox = document.getElementById('cartTotal');
            var countPill = document.getElementById('cartCountPill');
            var apply = document.getElementById('cartApply');
            var mobCartBadge = document.getElementById('mobileCartBadge');
            var isCustomerLoggedIn = {{ $customerUser ? 'true' : 'false' }};
            var applyBaseUrl = isCustomerLoggedIn
                ? '{{ route('loan-management.public.customer-loan-request') }}'
                : '{{ route('loan-management.public.register') }}';

            function money(value) {
                return '$' + Number(value || 0).toFixed(2);
            }

            function save() {
                try { localStorage.setItem(cartKey, JSON.stringify(cart)); } catch (e) {}
            }

            function load() {
                try {
                    cart = JSON.parse(localStorage.getItem(cartKey) || '[]') || [];
                    if (!Array.isArray(cart)) cart = [];
                    cart = cart.filter(function (item) { return item && typeof item === 'object' && Number.isFinite(Number(item.price)) && Number(item.price) >= 0 && Number.isInteger(Number(item.qty)) && Number(item.qty) > 0; });
                } catch (e) {
                    cart = [];
                }
            }

            function renderCart() {
                if (!itemsBox || !apply) return;
                var total = 0;
                var totalItems = 0;
                itemsBox.innerHTML = '';
                if (!cart.length) {
                    itemsBox.innerHTML = '<div class="cart-empty">No products selected yet.</div>';
                }

                cart.forEach(function (item, index) {
                    var qty = Number(item.qty || 1);
                    var price = Number(item.price || 0);
                    total += price * qty;
                    totalItems += qty;

                    var row = document.createElement('div');
                    row.className = 'cart-item';
                    row.innerHTML = '<div><strong></strong><span></span></div><div class="qty-row"><button type="button" data-action="minus" data-index="' + index + '">-</button><span>' + qty + '</span><button type="button" data-action="plus" data-index="' + index + '">+</button></div>';
                    row.querySelector('strong').textContent = item.name || 'Product';
                    row.querySelector('span').textContent = money(price * qty);
                    itemsBox.appendChild(row);
                });

                totalBox.textContent = money(total);
                if (countPill) countPill.textContent = totalItems + (totalItems === 1 ? ' item' : ' items');
                if (mobCartBadge) {
                    mobCartBadge.textContent = totalItems;
                    mobCartBadge.style.display = totalItems > 0 ? 'inline-block' : 'none';
                }
                apply.href = applyBaseUrl + (cart.length ? '?cart=1' : '');
            }

            // Add to Cart Buttons
            document.querySelectorAll('.cart-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    var product = JSON.parse(button.getAttribute('data-product') || '{}');
                    var key = String(product.id || product.product_id || product.name);
                    var existing = cart.find(function (item) { return String(item.id || item.product_id || item.name) === key; });
                    if (existing) {
                        existing.qty = Number(existing.qty || 1) + 1;
                    } else {
                        product.qty = 1;
                        cart.push(product);
                    }
                    save();
                    renderCart();

                    // Button feedback animation
                    var originalHtml = button.innerHTML;
                    button.style.background = '#16a34a';
                    button.innerHTML = '✓ Added!';
                    setTimeout(function() {
                        button.style.background = '';
                        button.innerHTML = originalHtml;
                    }, 1200);
                });
            });

            // Cart Quantity Adjustment
            if (itemsBox) itemsBox.addEventListener('click', function (event) {
                var button = event.target.closest('button[data-action]');
                if (!button) return;
                var index = Number(button.getAttribute('data-index'));
                if (!cart[index]) return;
                if (button.getAttribute('data-action') === 'plus') {
                    cart[index].qty = Number(cart[index].qty || 1) + 1;
                } else {
                    cart[index].qty = Number(cart[index].qty || 1) - 1;
                    if (cart[index].qty <= 0) cart.splice(index, 1);
                }
                save();
                renderCart();
            });

            load();
            renderCart();

            var sectionLinks = Array.from(document.querySelectorAll('[data-section-link]'));
            var trackedSections = sectionLinks
                .map(function (link) {
                    var id = link.getAttribute('data-section-link');
                    return { id: id, link: link, section: document.getElementById(id) };
                })
                .filter(function (item) { return !!item.section; });

            function setActiveSection(id) {
                sectionLinks.forEach(function (link) {
                    var active = link.getAttribute('data-section-link') === id;
                    link.classList.toggle('active', active);
                    if (active) link.setAttribute('aria-current', 'location');
                    else link.removeAttribute('aria-current');
                });
            }

            if (trackedSections.length) {
                // Nested sticky panels are navigation targets, not page-section boundaries.
                var pageSections = trackedSections.filter(function (item) {
                    return !trackedSections.some(function (other) {
                        return other !== item && other.section.contains(item.section);
                    });
                }).sort(function (a, b) {
                    return a.section.compareDocumentPosition(b.section) & 4 ? -1 : 1;
                });
                var selectedTarget = null;
                var header = document.querySelector('.site-nav');

                function updateActiveSection() {
                    if (selectedTarget) {
                        setActiveSection(selectedTarget);
                        return;
                    }
                    var activeId = pageSections[0].id;
                    var offset = header.getBoundingClientRect().height + 14;
                    pageSections.forEach(function (item) {
                        if (item.section.getBoundingClientRect().top <= offset) activeId = item.id;
                    });
                    setActiveSection(activeId);
                }

                function selectTarget(hash) {
                    var item = trackedSections.find(function (item) { return '#' + item.id === hash; });
                    selectedTarget = item ? item.id : null;
                    updateActiveSection();
                }

                document.querySelector('.public-shell').addEventListener('click', function (event) {
                    var link = event.target.closest('a[href^="#"]');
                    if (link && !event.ctrlKey && !event.metaKey && !event.shiftKey && !event.altKey) selectTarget(link.getAttribute('href'));
                });
                window.addEventListener('hashchange', function () { selectTarget(window.location.hash); });
                window.addEventListener('scroll', updateActiveSection, { passive: true });
                ['wheel', 'touchmove'].forEach(function (type) {
                    window.addEventListener(type, function () { selectedTarget = null; }, { passive: true });
                });
                window.addEventListener('keydown', function (event) {
                    if (event.target.closest('input, textarea, select, button, [contenteditable]')) return;
                    if (['ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' '].includes(event.key)) selectedTarget = null;
                });
                window.addEventListener('resize', function () {
                    document.documentElement.style.scrollPaddingTop = (header.getBoundingClientRect().height + 12) + 'px';
                    updateActiveSection();
                }, { passive: true });
                document.documentElement.style.scrollPaddingTop = (header.getBoundingClientRect().height + 12) + 'px';
                selectTarget(window.location.hash);
            }

            // Category & Search Engine
            var currentCategory = 'all';
            var searchInput = document.getElementById('catalogSearchInput');
            var searchClearBtn = document.getElementById('searchClearBtn');
            var sortSelect = document.getElementById('catalogSortSelect');
            var productGrid = document.getElementById('productsCatalogGrid');
            var noProductsMsg = document.getElementById('noProductsFoundMsg');

            function applyFilter() {
                var query = (searchInput ? searchInput.value : '').toLowerCase().trim();
                if (searchClearBtn) {
                    searchClearBtn.style.display = query.length > 0 ? 'block' : 'none';
                }

                var cards = document.querySelectorAll('#productsCatalogGrid .product-card');
                var visibleCount = 0;

                cards.forEach(function (card) {
                    var cCat = (card.getAttribute('data-category') || '').toLowerCase();
                    var cName = (card.getAttribute('data-name') || '').toLowerCase();
                    var cSku = (card.getAttribute('data-sku') || '').toLowerCase();
                    var cBrand = (card.getAttribute('data-brand') || '').toLowerCase();

                    var matchesCat = (currentCategory === 'all' || cCat === currentCategory);
                    var matchesSearch = (query === '' || cName.includes(query) || cSku.includes(query) || cBrand.includes(query) || cCat.includes(query));

                    if (matchesCat && matchesSearch) {
                        card.style.display = 'flex';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (noProductsMsg) {
                    noProductsMsg.style.display = (visibleCount === 0 && cards.length > 0) ? 'block' : 'none';
                }
            }

            // Category Chip Click
            var chips = document.querySelectorAll('.category-chip');
            chips.forEach(function (chip) {
                chip.addEventListener('click', function () {
                    chips.forEach(function (c) { c.classList.remove('active'); });
                    chip.classList.add('active');
                    currentCategory = chip.getAttribute('data-category') || 'all';
                    applyFilter();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', applyFilter);
            }

            if (searchClearBtn) {
                searchClearBtn.addEventListener('click', function () {
                    searchInput.value = '';
                    applyFilter();
                    searchInput.focus();
                });
            }

            window.resetAllFilters = function () {
                if (searchInput) searchInput.value = '';
                currentCategory = 'all';
                chips.forEach(function (c) {
                    if (c.getAttribute('data-category') === 'all') c.classList.add('active');
                    else c.classList.remove('active');
                });
                if (sortSelect) sortSelect.value = 'default';
                applyFilter();
            };

            // Sorting Engine
            if (sortSelect && productGrid) {
                sortSelect.addEventListener('change', function () {
                    var val = sortSelect.value;
                    var cards = Array.from(productGrid.querySelectorAll('.product-card'));

                    cards.sort(function (a, b) {
                        var priceA = parseFloat(a.getAttribute('data-price')) || 0;
                        var priceB = parseFloat(b.getAttribute('data-price')) || 0;
                        var nameA = (a.getAttribute('data-name') || '').toLowerCase();
                        var nameB = (b.getAttribute('data-name') || '').toLowerCase();
                        var idA = parseInt(a.getAttribute('data-id')) || 0;
                        var idB = parseInt(b.getAttribute('data-id')) || 0;

                        if (val === 'price_low') return priceA - priceB;
                        if (val === 'price_high') return priceB - priceA;
                        if (val === 'name_asc') return nameA.localeCompare(nameB);
                        return idB - idA; // default newest
                    });

                    cards.forEach(function (card) {
                        productGrid.appendChild(card);
                    });
                });
            }

            // Profile Dropdowns Toggle
            document.addEventListener('click', function (e) {
                var custWrapper = document.getElementById('customerDropdownWrapper');
                var custToggle = document.getElementById('customerProfileToggle');
                if (custWrapper && custToggle) {
                    if (custToggle.contains(e.target)) {
                        custWrapper.classList.toggle('open');
                        custToggle.setAttribute('aria-expanded', custWrapper.classList.contains('open') ? 'true' : 'false');
                        var admWrapper = document.getElementById('adminDropdownWrapper');
                        if (admWrapper) admWrapper.classList.remove('open');
                    } else if (!custWrapper.contains(e.target)) {
                        custWrapper.classList.remove('open');
                        custToggle.setAttribute('aria-expanded', 'false');
                    }
                }

                var admWrapper = document.getElementById('adminDropdownWrapper');
                var admToggle = document.getElementById('adminProfileToggle');
                if (admWrapper && admToggle) {
                    if (admToggle.contains(e.target)) {
                        admWrapper.classList.toggle('open');
                        admToggle.setAttribute('aria-expanded', admWrapper.classList.contains('open') ? 'true' : 'false');
                        if (custWrapper) custWrapper.classList.remove('open');
                    } else if (!admWrapper.contains(e.target)) {
                        admWrapper.classList.remove('open');
                        admToggle.setAttribute('aria-expanded', 'false');
                    }
                }
            });
        })();
    </script>
</body>
</html>
