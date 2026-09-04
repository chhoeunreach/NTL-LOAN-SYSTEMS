import re

path = r"c:\xampp\htdocs\apply like facebook\LoanManagement\Resources\views\public\customer_login.blade.php"

with open(path, "r", encoding="utf-8") as f:
    content = f.read()

# 1. Update the top PHP block to prepare demo customer credentials
old_php_head = """@php
    $businessLogoUrl = \\Modules\\LoanManagement\\Services\\BusinessSettingsService::publicLogoUrl();
    $businessName = \\Modules\\LoanManagement\\Services\\BusinessSettingsService::businessName();
    $themeColor = $settings['theme_color'] ?? '#2563eb';
    $loginBackgroundUrl = \\Modules\\LoanManagement\\Services\\BusinessSettingsService::loginBackgroundUrl();
@endphp"""

new_php_head = """@php
    $businessLogoUrl = \\Modules\\LoanManagement\\Services\\BusinessSettingsService::publicLogoUrl();
    $businessName = \\Modules\\LoanManagement\\Services\\BusinessSettingsService::businessName();
    $themeColor = $settings['theme_color'] ?? '#2563eb';
    $loginBackgroundUrl = \\Modules\\LoanManagement\\Services\\BusinessSettingsService::loginBackgroundUrl();

    $primaryDemo = (isset($demoCustomers) && $demoCustomers->isNotEmpty()) ? $demoCustomers->first() : null;
    $defaultDemoPhone = $primaryDemo ? ($primaryDemo->phone ?: $primaryDemo->username) : '010111001';
    $defaultDemoName = $primaryDemo ? $primaryDemo->name : 'Sok Dara';
    $defaultDemoPassword = 'password';
@endphp"""

if old_php_head in content:
    content = content.replace(old_php_head, new_php_head, 1)
else:
    print("WARNING: old_php_head not found!")

# 2. Add styles for the demo credentials box before </style>
demo_css = """
        /* Demo Credentials Widget */
        .demo-box {
            margin-top: 18px;
            background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
            border: 1.5px dashed #cbd5e1;
            border-radius: 14px;
            padding: 14px 16px;
            transition: border-color .2s, box-shadow .2s, transform .2s;
        }
        .demo-box:hover {
            border-color: var(--primary);
            box-shadow: 0 8px 24px -6px rgba(15, 23, 42, .08);
        }
        .demo-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .demo-title {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #334155;
        }
        .demo-title svg { color: var(--primary); }
        .demo-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            background: rgba(37, 99, 235, .1);
            color: var(--primary);
            border: 1px solid rgba(37, 99, 235, .2);
        }
        .demo-customer-name {
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 10px;
            font-size: 13px;
            color: #1e293b;
        }
        .demo-customer-name strong { font-weight: 800; }
        .demo-tag {
            font-size: 10px;
            padding: 1px 6px;
            border-radius: 6px;
            background: #dcfce7;
            color: #15803d;
            font-weight: 700;
        }
        .demo-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 12px;
        }
        @media (max-width: 360px) {
            .demo-grid { grid-template-columns: 1fr; }
        }
        .demo-chip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 7px 10px;
            cursor: pointer;
            transition: all .15s ease;
            user-select: none;
        }
        .demo-chip:hover {
            border-color: var(--primary);
            background: #f8fafc;
            transform: translateY(-1px);
        }
        .chip-content {
            display: flex;
            flex-direction: column;
            min-width: 0;
            overflow: hidden;
        }
        .chip-label {
            font-size: 10px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .chip-val {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .btn-copy-chip {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #64748b;
            width: 26px;
            height: 26px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: all .15s ease;
            margin-left: 6px;
            padding: 0;
        }
        .demo-chip:hover .btn-copy-chip {
            background: var(--primary);
            border-color: var(--primary);
            color: #fff;
        }
        .demo-switch-row {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #e2e8f0;
            flex-wrap: wrap;
        }
        .demo-switch-label {
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
        }
        .btn-acc-pill {
            background: #fff;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 3px 8px;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            transition: all .15s ease;
        }
        .btn-acc-pill:hover, .btn-acc-pill.active {
            background: rgba(37, 99, 235, .1);
            border-color: var(--primary);
            color: var(--primary);
        }
        .btn-demo-autofill {
            width: 100%;
            height: 42px;
            background: #fff;
            border: 1.5px solid var(--primary);
            color: var(--primary);
            border-radius: 10px;
            font: inherit;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all .2s ease;
            box-shadow: 0 2px 8px -2px rgba(37, 99, 235, .15);
        }
        .btn-demo-autofill:hover {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 6px 16px -4px rgba(37, 99, 235, .4);
            transform: translateY(-1px);
        }
        .btn-demo-autofill.filled {
            background: #10b981 !important;
            border-color: #10b981 !important;
            color: #fff !important;
            box-shadow: 0 6px 16px -4px rgba(16, 185, 129, .5) !important;
        }
        .demo-fill-highlight {
            animation: inputPulse .7s ease-in-out;
            border-color: #10b981 !important;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, .25) !important;
        }
        @keyframes inputPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }
"""

if "</style>" in content:
    content = content.replace("    </style>", demo_css + "    </style>", 1)
else:
    print("WARNING: </style> not found!")

# 3. Add demo card below the submit button inside the form
target_button = """                    <button class="button" type="submit" id="submitBtn">
                        <span class="spinner"></span><span id="submitLabel">Sign in</span>
                    </button>
                </form>"""

replacement_button_and_demo = """                    <button class="button" type="submit" id="submitBtn">
                        <span class="spinner"></span><span id="submitLabel">Sign in</span>
                    </button>

                    <!-- Demo Customer Credentials (Click to Auto-Fill & Copy) -->
                    <div class="demo-box" id="demoBox">
                        <div class="demo-head">
                            <div class="demo-title">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                                <span>Demo Customer Login</span>
                            </div>
                            <span class="demo-badge">1-Click Demo</span>
                        </div>

                        <div class="demo-customer-name">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <strong id="demoCustomerNameDisplay">{{ $defaultDemoName }}</strong>
                            <span class="demo-tag">Active Customer</span>
                        </div>

                        <div class="demo-grid">
                            <div class="demo-chip" id="chipPhone" data-field="login" data-val="{{ $defaultDemoPhone }}" title="Click to copy & fill Phone">
                                <div class="chip-content">
                                    <span class="chip-label">Phone / User</span>
                                    <span class="chip-val" id="demoPhoneDisplay">{{ $defaultDemoPhone }}</span>
                                </div>
                                <button type="button" class="btn-copy-chip" title="Copy Phone" aria-label="Copy Phone">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                </button>
                            </div>

                            <div class="demo-chip" id="chipPass" data-field="password" data-val="{{ $defaultDemoPassword }}" title="Click to copy & fill Password">
                                <div class="chip-content">
                                    <span class="chip-label">Password</span>
                                    <span class="chip-val" id="demoPassDisplay">{{ $defaultDemoPassword }}</span>
                                </div>
                                <button type="button" class="btn-copy-chip" title="Copy Password" aria-label="Copy Password">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>
                                </button>
                            </div>
                        </div>

                        @if(isset($demoCustomers) && count($demoCustomers) > 1)
                        <div class="demo-switch-row">
                            <span class="demo-switch-label">Other accounts:</span>
                            @foreach($demoCustomers as $idx => $dc)
                                @php
                                    $dcPhone = $dc->phone ?: $dc->username;
                                @endphp
                                <button type="button" class="btn-acc-pill {{ $idx === 0 ? 'active' : '' }}"
                                        data-name="{{ $dc->name }}"
                                        data-phone="{{ $dcPhone }}"
                                        data-pass="{{ $defaultDemoPassword }}">
                                    {{ $dc->name }}
                                </button>
                            @endforeach
                        </div>
                        @endif

                        <button type="button" class="btn-demo-autofill" id="btnQuickFillDemo">
                            <svg id="btnFillIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                            <span id="btnFillText">Click to Copy & Auto-Fill Demo</span>
                        </button>
                    </div>
                </form>"""

if target_button in content:
    content = content.replace(target_button, replacement_button_and_demo, 1)
else:
    print("WARNING: target_button not found!")

# 4. Add the JavaScript for Auto-Fill & Copy
target_script_end = """            var form = document.getElementById('loginForm');
            var submit = document.getElementById('submitBtn');
            form.addEventListener('submit', function () {
                submit.classList.add('loading');
                submit.disabled = true;
            });
        })();"""

replacement_script = """            var form = document.getElementById('loginForm');
            var submit = document.getElementById('submitBtn');
            form.addEventListener('submit', function () {
                submit.classList.add('loading');
                submit.disabled = true;
            });

            // Demo Auto-Fill & Copy Logic
            var loginInput = document.getElementById('login');
            var passwordInput = document.getElementById('password');
            var fillBtn = document.getElementById('btnQuickFillDemo');
            var fillText = document.getElementById('btnFillText');
            var fillIcon = document.getElementById('btnFillIcon');
            var customerNameEl = document.getElementById('demoCustomerNameDisplay');
            var phoneDisplay = document.getElementById('demoPhoneDisplay');
            var passDisplay = document.getElementById('demoPassDisplay');

            var currentCredentials = {
                phone: '{{ $defaultDemoPhone }}',
                password: '{{ $defaultDemoPassword }}',
                name: '{{ $defaultDemoName }}'
            };

            function copyToClipboard(text) {
                if (navigator.clipboard && window.isSecureContext) {
                    return navigator.clipboard.writeText(text).catch(function () {
                        fallbackCopy(text);
                    });
                } else {
                    fallbackCopy(text);
                }
            }

            function fallbackCopy(text) {
                var textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                } catch (e) {}
                document.body.removeChild(textArea);
            }

            function autofillAndCopy(creds, showFeedback) {
                if (!loginInput || !passwordInput) return;

                loginInput.value = creds.phone;
                passwordInput.value = creds.password;

                loginInput.dispatchEvent(new Event('input', { bubbles: true }));
                loginInput.dispatchEvent(new Event('change', { bubbles: true }));
                passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
                passwordInput.dispatchEvent(new Event('change', { bubbles: true }));

                copyToClipboard(creds.phone + ' / ' + creds.password);

                loginInput.classList.remove('demo-fill-highlight');
                passwordInput.classList.remove('demo-fill-highlight');
                void loginInput.offsetWidth;
                loginInput.classList.add('demo-fill-highlight');
                passwordInput.classList.add('demo-fill-highlight');

                setTimeout(function () {
                    loginInput.classList.remove('demo-fill-highlight');
                    passwordInput.classList.remove('demo-fill-highlight');
                }, 1500);

                if (showFeedback && fillBtn && fillText) {
                    var prevText = fillText.textContent;
                    fillBtn.classList.add('filled');
                    fillText.textContent = '✓ Completed & Copied to Form!';
                    setTimeout(function () {
                        fillBtn.classList.remove('filled');
                        fillText.textContent = prevText;
                    }, 2400);
                }
            }

            if (fillBtn) {
                fillBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    autofillAndCopy(currentCredentials, true);
                });
            }

            var chips = document.querySelectorAll('.demo-chip');
            chips.forEach(function (chip) {
                chip.addEventListener('click', function (e) {
                    var targetField = chip.getAttribute('data-field');
                    var val = chip.getAttribute('data-val');
                    if (targetField === 'login' && loginInput) {
                        loginInput.value = val;
                        loginInput.dispatchEvent(new Event('input', { bubbles: true }));
                        loginInput.dispatchEvent(new Event('change', { bubbles: true }));
                        loginInput.classList.add('demo-fill-highlight');
                        setTimeout(function () { loginInput.classList.remove('demo-fill-highlight'); }, 1200);
                        copyToClipboard(val);
                        showChipFeedback(chip, 'Phone Copied & Filled!');
                    } else if (targetField === 'password' && passwordInput) {
                        passwordInput.value = val;
                        passwordInput.dispatchEvent(new Event('input', { bubbles: true }));
                        passwordInput.dispatchEvent(new Event('change', { bubbles: true }));
                        passwordInput.classList.add('demo-fill-highlight');
                        setTimeout(function () { passwordInput.classList.remove('demo-fill-highlight'); }, 1200);
                        copyToClipboard(val);
                        showChipFeedback(chip, 'Password Copied & Filled!');
                    }
                });
            });

            function showChipFeedback(chip, text) {
                var valEl = chip.querySelector('.chip-val');
                if (!valEl) return;
                var prevText = valEl.textContent;
                valEl.textContent = text;
                valEl.style.color = '#10b981';
                setTimeout(function () {
                    valEl.textContent = prevText;
                    valEl.style.color = '';
                }, 1600);
            }

            var accPills = document.querySelectorAll('.btn-acc-pill');
            accPills.forEach(function (pill) {
                pill.addEventListener('click', function (e) {
                    e.preventDefault();
                    accPills.forEach(function (p) { p.classList.remove('active'); });
                    pill.classList.add('active');

                    var name = pill.getAttribute('data-name');
                    var phone = pill.getAttribute('data-phone');
                    var pass = pill.getAttribute('data-pass');

                    currentCredentials = { phone: phone, password: pass, name: name };

                    if (customerNameEl) customerNameEl.textContent = name;
                    if (phoneDisplay) phoneDisplay.textContent = phone;
                    if (passDisplay) passDisplay.textContent = pass;

                    var phoneChip = document.getElementById('chipPhone');
                    if (phoneChip) phoneChip.setAttribute('data-val', phone);

                    autofillAndCopy(currentCredentials, true);
                });
            });
        })();"""

if target_script_end in content:
    content = content.replace(target_script_end, replacement_script, 1)
else:
    print("WARNING: target_script_end not found!")

with open(path, "w", encoding="utf-8") as f:
    f.write(content)

print("SUCCESS: Updated customer_login.blade.php")
