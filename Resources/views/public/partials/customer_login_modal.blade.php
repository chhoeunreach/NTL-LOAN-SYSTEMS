@if($customerPortalEnabled && !$customerUser && !$adminUser)
<dialog class="installment-modal" id="customerLoginModal" aria-labelledby="customerLoginTitle">
    <button class="installment-close" type="button" id="customerLoginClose" aria-label="Close login" title="Close"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    <h2 id="customerLoginTitle">Customer Login</h2>
    <p>Access your loan records and installment payment history.</p>
    <div class="installment-message" id="customerLoginError" role="alert" hidden></div>
    <form method="POST" action="{{ route('loan-management.public.customer-login.store') }}" id="customerLoginForm">
        @csrf
        <div class="installment-grid">
            <div class="installment-field-full"><label for="modalCustomerLogin">Phone, Email or Username</label><input id="modalCustomerLogin" name="login" autocomplete="username" maxlength="255" required autofocus></div>
            <div class="installment-field-full"><label for="modalCustomerPassword">Password</label><input id="modalCustomerPassword" name="password" type="password" autocomplete="current-password" required></div>
        </div>
        <button class="installment-submit" type="submit" id="customerLoginSubmit">Login to Portal</button>
    </form>
    <p style="margin-top:16px;"><a href="{{ route('loan-management.public.customer-login') }}" data-full-login>Open Customer Portal</a></p>
</dialog>
<script>
(function () {
    var dialog = document.getElementById('customerLoginModal');
    var form = document.getElementById('customerLoginForm');
    var errorBox = document.getElementById('customerLoginError');
    var button = document.getElementById('customerLoginSubmit');
    var pending = false;
    var loginPath = new URL(@json(route('loan-management.public.customer-login')), location.href).pathname;
    var previousFocus;
    var previousOverflow;
    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link || link.hasAttribute('data-full-login') || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        var url = new URL(link.href, location.href);
        if (url.origin !== location.origin || url.pathname !== loginPath) return;
        event.preventDefault();
        previousFocus = document.activeElement;
        previousOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
        errorBox.hidden = true;
        dialog.showModal();
    });
    document.getElementById('customerLoginClose').addEventListener('click', function () { if (!pending) dialog.close(); });
    dialog.addEventListener('cancel', function (event) { if (pending) event.preventDefault(); });
    dialog.addEventListener('close', function () {
        document.body.style.overflow = previousOverflow || '';
        document.getElementById('modalCustomerPassword').value = '';
        if (previousFocus?.isConnected) previousFocus.focus();
    });
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (pending) return;
        pending = true;
        button.disabled = true;
        button.textContent = 'Signing in...';
        errorBox.hidden = true;
        try {
            var response = await fetch(form.action, {method:'POST', credentials:'same-origin', body:new FormData(form), headers:{Accept:'application/json', 'X-Requested-With':'XMLHttpRequest'}});
            var payload = response.headers.get('content-type')?.includes('application/json') ? await response.json() : {};
            if (!response.ok || !payload.redirect) {
                throw new Error(Object.values(payload.errors || {}).flat()[0] || (response.status === 429 ? 'Please wait a minute before trying again.' : response.status === 419 ? 'Refresh the page to renew your session.' : response.status < 500 && payload.message ? payload.message : 'Unable to sign in. Please try again.'));
            }
            location.assign(payload.redirect);
        } catch (error) {
            errorBox.textContent = error.message;
            errorBox.hidden = false;
            document.getElementById('modalCustomerPassword').value = '';
        } finally {
            pending = false;
            button.disabled = false;
            button.textContent = 'Login to Portal';
        }
    });
})();
</script>
@endif
