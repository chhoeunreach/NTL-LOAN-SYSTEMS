@php
    $businessLogoUrl = \Modules\LoanManagement\Services\BusinessSettingsService::publicLogoUrl();
    $loginBackgroundUrl = \Modules\LoanManagement\Services\BusinessSettingsService::loginBackgroundUrl();
    $businessName = \Modules\LoanManagement\Services\BusinessSettingsService::businessName();
    $headline = $settings['home_headline'] ?? 'Simple loan service for customers';
    $subtitle = $settings['home_subtitle'] ?? '';
    $body = $settings['home_body'] ?? '';
    $themeColor = $settings['theme_color'] ?? '#2563eb';
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $businessName }}</title>
    <style>
        :root { --public-primary: {{ $themeColor }}; --ink: #102033; --muted: #64748b; --line: #e2e8f0; --panel: #fff; --soft: #f5f8fc; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: var(--ink); background: var(--soft); }
        .public-shell { min-height: 100vh; display: flex; flex-direction: column; }
        .site-nav { position: sticky; top: 0; z-index: 20; background: rgba(255,255,255,.94); border-bottom: 1px solid rgba(226,232,240,.88); backdrop-filter: blur(16px); }
        .nav-inner { width: min(1180px, calc(100% - 32px)); margin: 0 auto; min-height: 72px; display: flex; align-items: center; justify-content: space-between; gap: 18px; }
        .brand { display: inline-flex; align-items: center; gap: 12px; text-decoration: none; font-weight: 800; color: #0f172a; min-width: 0; }
        .brand-logo { width: 44px; height: 44px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; overflow: hidden; background: #edf3fb; border: 1px solid #dbe4ef; color: var(--public-primary); flex: 0 0 auto; }
        .brand-logo img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .brand-text { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .menu { display: flex; align-items: center; gap: 6px; }
        .menu a { min-height: 38px; display: inline-flex; align-items: center; padding: 0 12px; border-radius: 6px; color: #334155; text-decoration: none; font-size: 14px; font-weight: 800; }
        .menu a:hover { background: #eef4fb; color: #0f172a; }
        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .button, .button-outline { min-height: 42px; display: inline-flex; align-items: center; justify-content: center; padding: 0 16px; border-radius: 6px; text-decoration: none; font-weight: 800; border: 1px solid transparent; cursor: pointer; }
        .button { color: #fff; background: var(--public-primary); border-color: var(--public-primary); }
        .button-outline { color: #0f172a; background: #fff; border-color: #dbe4ef; }
        .hero { color: #fff; background: linear-gradient(90deg, rgba(7,18,33,.88), rgba(7,18,33,.58), rgba(7,18,33,.25)), @if($loginBackgroundUrl) url('{{ $loginBackgroundUrl }}') @else linear-gradient(135deg, #12324e, #607d95) @endif; background-size: cover; background-position: center; }
        .hero-inner { width: min(1180px, calc(100% - 32px)); min-height: calc(78vh - 72px); margin: 0 auto; display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 42px; align-items: center; padding: 64px 0 92px; }
        .hero-copy { max-width: 720px; }
        .eyebrow { display: inline-flex; min-height: 32px; align-items: center; padding: 0 10px; border: 1px solid rgba(255,255,255,.34); border-radius: 999px; color: rgba(255,255,255,.88); font-size: 12px; font-weight: 800; text-transform: uppercase; }
        h1 { margin: 18px 0 0; font-size: clamp(38px, 6vw, 68px); line-height: 1.02; letter-spacing: 0; }
        .subtitle { margin: 18px 0 0; max-width: 640px; font-size: 18px; line-height: 1.7; color: rgba(255,255,255,.88); }
        .body-copy { margin: 12px 0 0; max-width: 640px; font-size: 15px; line-height: 1.7; color: rgba(255,255,255,.76); white-space: pre-wrap; }
        .hero-cta { margin-top: 30px; display: flex; gap: 12px; flex-wrap: wrap; }
        .hero-card { background: rgba(255,255,255,.96); border: 1px solid rgba(255,255,255,.58); border-radius: 8px; color: #0f172a; padding: 22px; box-shadow: 0 24px 70px rgba(0,0,0,.20); }
        .hero-card h2 { margin: 0; font-size: 20px; letter-spacing: 0; }
        .hero-card p { margin: 8px 0 18px; color: var(--muted); line-height: 1.6; }
        .quick-stat { display: flex; justify-content: space-between; gap: 12px; padding: 11px 0; border-bottom: 1px solid #edf2f7; color: #334155; }
        .quick-stat strong { color: #0f172a; }
        .section { padding: 54px 16px; }
        .section.alt { background: #fff; border-top: 1px solid var(--line); border-bottom: 1px solid var(--line); }
        .section-inner { width: min(1180px, 100%); margin: 0 auto; }
        .section-head { margin: 0 0 20px; display: flex; align-items: end; justify-content: space-between; gap: 18px; }
        .section-head h2 { margin: 0; color: #0f172a; font-size: 30px; letter-spacing: 0; }
        .section-head p { margin: 8px 0 0; max-width: 640px; color: var(--muted); line-height: 1.6; }
        .feature-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
        .feature { background: var(--panel); border: 1px solid var(--line); border-radius: 8px; padding: 20px; box-shadow: 0 12px 34px rgba(15,23,42,.06); }
        .feature-icon { width: 42px; height: 42px; border-radius: 8px; display: grid; place-items: center; background: #eef6ff; color: var(--public-primary); font-weight: 900; margin-bottom: 14px; }
        .feature strong { display: block; color: #0f172a; font-size: 16px; }
        .feature span { display: block; margin-top: 8px; color: var(--muted); line-height: 1.55; font-size: 14px; }
        .shop-inner { display: grid; grid-template-columns: minmax(0, 1fr) 330px; gap: 18px; align-items: start; }
        .product-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
        .product-card { background: #fff; border: 1px solid var(--line); border-radius: 8px; overflow: hidden; box-shadow: 0 10px 28px rgba(15,23,42,.06); display: flex; flex-direction: column; min-width: 0; }
        .product-image { aspect-ratio: 4 / 3; background: #eef4fb; display: grid; place-items: center; color: #52657c; font-weight: 800; overflow: hidden; }
        .product-image img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .product-body { padding: 14px; display: grid; gap: 9px; flex: 1; }
        .product-title { margin: 0; color: #0f172a; font-size: 15px; line-height: 1.35; letter-spacing: 0; }
        .product-sku { color: var(--muted); font-size: 12px; min-height: 16px; }
        .product-price { color: #0f172a; font-size: 18px; font-weight: 800; }
        .cart-btn { min-height: 40px; border: 0; border-radius: 6px; background: var(--public-primary); color: #fff; font-weight: 800; cursor: pointer; }
        .cart-panel { position: sticky; top: 88px; background: #fff; border: 1px solid var(--line); border-radius: 8px; box-shadow: 0 10px 28px rgba(15,23,42,.06); overflow: hidden; }
        .cart-panel h2 { margin: 0; padding: 16px; border-bottom: 1px solid #edf2f7; font-size: 18px; letter-spacing: 0; }
        .cart-items { padding: 12px 16px; display: grid; gap: 10px; max-height: 360px; overflow: auto; }
        .cart-empty { color: var(--muted); font-size: 13px; line-height: 1.5; }
        .cart-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 8px; align-items: center; border-bottom: 1px solid #edf2f7; padding-bottom: 10px; }
        .cart-item strong { display: block; color: #0f172a; font-size: 13px; overflow-wrap: anywhere; }
        .cart-item span { display: block; color: var(--muted); font-size: 12px; margin-top: 3px; }
        .qty-row { display: inline-flex; align-items: center; gap: 6px; }
        .qty-row button { width: 28px; height: 28px; border: 1px solid #dbe4ef; border-radius: 6px; background: #fff; cursor: pointer; }
        .cart-total { padding: 14px 16px; border-top: 1px solid #edf2f7; display: flex; justify-content: space-between; gap: 12px; font-weight: 800; color: #0f172a; }
        .cart-apply { margin: 0 16px 16px; width: calc(100% - 32px); min-height: 42px; border-radius: 6px; border: 0; background: var(--public-primary); color: #fff; font-weight: 800; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
        .footer { padding: 28px 16px; background: #0f172a; color: rgba(255,255,255,.78); }
        .footer-inner { width: min(1180px, 100%); margin: 0 auto; display: flex; justify-content: space-between; gap: 18px; flex-wrap: wrap; }
        .footer a { color: #fff; text-decoration: none; font-weight: 800; }
        @media (max-width: 960px) {
            .nav-inner { align-items: flex-start; flex-direction: column; padding: 14px 0; }
            .menu { flex-wrap: wrap; }
            .hero-inner, .shop-inner { grid-template-columns: 1fr; }
            .hero-inner { min-height: auto; padding: 44px 0 76px; }
            .feature-grid, .product-grid { grid-template-columns: 1fr; }
            .cart-panel { position: static; }
            .section-head { display: block; }
        }
    </style>
</head>
<body>
    <main class="public-shell">
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
                    <span class="brand-text">{{ $businessName }}</span>
                </a>
                <nav class="menu" aria-label="Main menu">
                    <a href="#home">Home</a>
                    <a href="#products">Products</a>
                    <a href="#how">How It Works</a>
                    <a href="#cart">Cart</a>
                </nav>
                <div class="nav-actions">
                    <a class="button-outline" href="{{ route('loan-management.public.customer-login') }}">Customer Login</a>
                    <a class="button" href="{{ route('loan-management.public.register') }}">Register</a>
                </div>
            </div>
        </header>

        <section class="hero" id="home">
            <div class="hero-inner">
                <div class="hero-copy">
                    <span class="eyebrow">Installment Shopping</span>
                    <h1>{{ $headline }}</h1>
                    @if($subtitle)
                        <p class="subtitle">{{ $subtitle }}</p>
                    @endif
                    @if($body)
                        <p class="body-copy">{{ $body }}</p>
                    @endif
                    <div class="hero-cta">
                        <a class="button" href="#products">Shop Products</a>
                        <a class="button-outline" href="{{ route('loan-management.public.register') }}">Apply Now</a>
                    </div>
                </div>
                <aside class="hero-card">
                    <h2>Apply in a few minutes</h2>
                    <p>Select products, submit your registration, then our team reviews your installment request.</p>
                    <div class="quick-stat"><span>Step 1</span><strong>Add products</strong></div>
                    <div class="quick-stat"><span>Step 2</span><strong>Register account</strong></div>
                    <div class="quick-stat"><span>Step 3</span><strong>Staff follow-up</strong></div>
                </aside>
            </div>
        </section>

        <section class="section alt" id="how">
            <div class="section-inner">
                <div class="section-head">
                    <div>
                        <h2>Simple customer experience</h2>
                        <p>Customers can browse products, request installment service, and return later to view their own account dashboard.</p>
                    </div>
                </div>
                <div class="feature-grid">
                    <div class="feature"><div class="feature-icon">1</div><strong>Choose product</strong><span>Add computers or other products from the catalog into your installment cart.</span></div>
                    <div class="feature"><div class="feature-icon">2</div><strong>Submit request</strong><span>Register once and send the selected cart items to the business team.</span></div>
                    <div class="feature"><div class="feature-icon">3</div><strong>Track your account</strong><span>Login to see personal information, loan records, and recent payment history.</span></div>
                </div>
            </div>
        </section>

        <section class="section" id="products">
            <div class="section-inner shop-inner">
                <div>
                    <div class="section-head">
                        <div>
                            <h2>Products for installment</h2>
                            <p>Choose products and add them to your cart. When you apply, the selected products will be sent with your registration request.</p>
                        </div>
                    </div>
                    @if(!empty($products))
                        <div class="product-grid">
                            @foreach($products as $product)
                                <article class="product-card">
                                    <div class="product-image">
                                        @if(!empty($product['image_url']))
                                            <img src="{{ $product['image_url'] }}" alt="{{ $product['name'] }}">
                                        @else
                                            <span>{{ strtoupper(mb_substr($product['name'], 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <div class="product-body">
                                        <h3 class="product-title">{{ $product['name'] }}</h3>
                                        <div class="product-sku">{{ $product['sku'] ? 'SKU: '.$product['sku'] : 'Available for installment' }}</div>
                                        <div class="product-price">${{ number_format($product['price'], 2) }}</div>
                                        <button type="button" class="cart-btn" data-product='@json($product)'>Add to Cart</button>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="feature"><strong>No products yet</strong><span>Add products in your POS/catalog and they will appear here.</span></div>
                    @endif
                </div>
                <aside class="cart-panel" id="cart">
                    <h2>Installment Cart</h2>
                    <div class="cart-items" id="cartItems"></div>
                    <div class="cart-total"><span>Total</span><span id="cartTotal">$0.00</span></div>
                    <a class="cart-apply" href="{{ route('loan-management.public.register') }}" id="cartApply">Apply Installment</a>
                </aside>
            </div>
        </section>

        <footer class="footer">
            <div class="footer-inner">
                <span>{{ $businessName }}</span>
                <span><a href="{{ route('loan-management.public.customer-login') }}">Customer Login</a></span>
            </div>
        </footer>
    </main>
    <script>
        (function () {
            var cartKey = 'loan_public_installment_cart';
            var cart = [];
            var itemsBox = document.getElementById('cartItems');
            var totalBox = document.getElementById('cartTotal');
            var apply = document.getElementById('cartApply');

            function money(value) {
                return '$' + Number(value || 0).toFixed(2);
            }

            function save() {
                localStorage.setItem(cartKey, JSON.stringify(cart));
            }

            function load() {
                try {
                    cart = JSON.parse(localStorage.getItem(cartKey) || '[]') || [];
                } catch (e) {
                    cart = [];
                }
            }

            function render() {
                var total = 0;
                itemsBox.innerHTML = '';
                if (!cart.length) {
                    itemsBox.innerHTML = '<div class="cart-empty">No products selected yet.</div>';
                }

                cart.forEach(function (item, index) {
                    total += Number(item.price || 0) * Number(item.qty || 1);
                    var row = document.createElement('div');
                    row.className = 'cart-item';
                    row.innerHTML = '<div><strong></strong><span></span></div><div class="qty-row"><button type="button" data-action="minus" data-index="' + index + '">-</button><span>' + Number(item.qty || 1) + '</span><button type="button" data-action="plus" data-index="' + index + '">+</button></div>';
                    row.querySelector('strong').textContent = item.name || 'Product';
                    row.querySelector('span').textContent = money(Number(item.price || 0) * Number(item.qty || 1));
                    itemsBox.appendChild(row);
                });

                totalBox.textContent = money(total);
                apply.href = '{{ route('loan-management.public.register') }}' + (cart.length ? '?cart=1' : '');
            }

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
                    render();
                });
            });

            itemsBox.addEventListener('click', function (event) {
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
                render();
            });

            load();
            render();
        })();
    </script>
</body>
</html>
