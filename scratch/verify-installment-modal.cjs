const assert = require('node:assert/strict');
const fs = require('node:fs');
const {chromium} = require('C:/Users/CHHOEUNREACH/AppData/Local/npm-cache/_npx/e41f203b7505f1fb/node_modules/playwright');

(async () => {
    const browser = await chromium.launch({headless: true});
    try {
        for (const viewport of [{width: 1440, height: 1000}, {width: 390, height: 844}]) {
            const page = await browser.newPage({viewport});
            const errors = [];
            page.on('pageerror', error => errors.push(error.stack));
            // Exercise the catalog flow with a fixture without adding products to the live database.
            await page.route(/^http:\/\/127\.0\.0\.1:8000\/(\?.*)?$/, route => route.fulfill({
                contentType: 'text/html', body: fs.readFileSync('scratch/installment-fixture.html', 'utf8')
            }));
            await page.goto('http://127.0.0.1:8000', {waitUntil: 'domcontentloaded'});
            await page.locator('.hero-image').evaluate(img => img.complete ? Promise.resolve() : new Promise(resolve => img.addEventListener('load', resolve, {once:true})));
            assert(await page.locator('.hero-image').evaluate(img => img.naturalWidth > 0), 'Hero asset must load');
            await page.screenshot({path:`scratch/cms-full-${viewport.width}.png`, fullPage:true});
            await page.locator('#assessmentMonths').fill('10');
            await page.locator('#assessmentDownPayment').fill('20');
            assert.equal(await page.locator('#assessmentMonthly').textContent(), '$50.00 / mo');
            await page.locator('#assessmentApply').click();
            assert.equal(await page.locator('#requestPreferredMonths').inputValue(), '10');
            assert.equal(await page.locator('#requestPreferredDownPayment').inputValue(), '20');
            await page.keyboard.press('Escape');
            await page.locator('#assessmentMonths').fill('');
            await page.waitForFunction(() => document.getElementById('assessmentMonthly').textContent === '--');
            assert.equal(await page.locator('#assessmentMonthly').textContent(), '--');
            await page.locator('#assessmentMonths').fill('12');
            await page.locator('.category-chip[data-category="printers"]').click();
            assert.equal(await page.locator('.product-card:visible').count(), 1);
            await page.locator('.category-chip[data-category="all"]').click();
            await page.locator('#catalogSearchInput').fill('CANON-1');
            assert.equal(await page.locator('.product-card:visible').count(), 1);
            await page.locator('#catalogSearchInput').fill('unmatched query');
            assert.equal(await page.locator('.product-card:visible').count(), 0);
            await page.getByRole('button', {name: 'Reset Filters'}).click();
            assert.equal(await page.locator('.product-card:visible').count(), 3);
            await page.locator('#catalogSortSelect').selectOption('price_low');
            assert.deepEqual(await page.locator('.product-card').evaluateAll(cards => cards.map(card => Number(card.dataset.price))), [180, 520, 750]);
            await page.evaluate(() => resetAllFilters());
            assert.deepEqual(await page.locator('.product-card').evaluateAll(cards => cards.map(card => Number(card.dataset.id))), [11, 9, 7]);
            await page.locator('#privacyOpen').click();
            assert.equal(await page.locator('#privacyModal').evaluate(dialog => dialog.open), true);
            await page.keyboard.press('Escape');
            await page.locator('.nav-actions > a').filter({hasText: /^Login$/}).click();
            await page.locator('#modalCustomerLogin').fill('sample');
            await page.locator('#modalCustomerPassword').fill('incorrect-password');
            await page.route('**/customer/login', route => route.request().method() === 'POST'
                ? route.fulfill({status: 422, contentType: 'application/json', body: JSON.stringify({errors: {login: ['These credentials do not match our records.']}})})
                : route.continue());
            await page.locator('#customerLoginSubmit').click();
            await page.locator('#customerLoginError').waitFor({state:'visible'});
            assert.equal(await page.locator('#modalCustomerPassword').inputValue(), '');
            await page.keyboard.press('Escape');
            if (viewport.width < 700) {
                await page.locator('#publicMenuToggle').click();
                assert.equal(await page.locator('#publicMenuToggle').getAttribute('aria-expanded'), 'true');
                await page.keyboard.press('Escape');
            }
            const productButton = page.locator('.cart-btn').first();
            assert(await productButton.count(), 'Expected a catalog product');
            const product = JSON.parse(await productButton.getAttribute('data-product'));
            await productButton.click();
            const originalUrl = page.url();
            await page.locator('#cartApply').click();
            assert.equal(page.url(), originalUrl, 'Apply should stay on the homepage');
            assert.equal(await page.locator('#installmentRequestModal').evaluate(dialog => dialog.open), true);
            assert.equal(await page.locator('#installmentRequestModal input[type=password]').count(), 0);
            assert((await page.locator('#requestSelectedProducts').inputValue()).includes(product.name));
            await page.locator('#requestFullName').fill('Sample Applicant');
            await page.locator('#requestPhone').fill('012 345 678');
            await page.locator('#requestAddress').fill('Sample Contact Address');
            await page.screenshot({path: `scratch/installment-modal-${viewport.width}.png`});
            const bounds = await page.locator('#installmentRequestModal').boundingBox();
            assert(bounds.x >= 0 && bounds.x + bounds.width <= viewport.width, 'Modal must fit horizontally');
            assert(bounds.y >= 0 && bounds.y + bounds.height <= viewport.height, 'Modal must fit vertically');
            let attempts = 0;
            await page.route('**/register', async route => {
                if (route.request().method() !== 'POST') return route.continue();
                attempts++;
                await route.fulfill({status: attempts === 1 ? 422 : 201, contentType: 'application/json', body: JSON.stringify(attempts === 1
                    ? {message: 'Validation failed', errors: {phone: ['Please check your phone number.']}}
                    : {status: 'pending', message: 'Your request is pending review. Staff will follow up.'})});
            });
            await page.locator('#installmentRequestSubmit').click();
            await page.locator('#installmentRequestError').waitFor({state: 'visible'});
            assert.equal(await page.locator('#requestFullName').inputValue(), 'Sample Applicant');
            assert(JSON.parse(await page.evaluate(() => localStorage.getItem('loan_public_installment_cart'))).length > 0);
            await page.locator('#installmentRequestSubmit').click();
            await page.locator('#installmentRequestSuccess').waitFor({state: 'visible'});
            assert.equal(await page.locator('#cartTotal').textContent(), '$0.00');
            assert.deepEqual(JSON.parse(await page.evaluate(() => localStorage.getItem('loan_public_installment_cart'))), []);
            await page.locator('#installmentRequestDone').click();
            assert.equal(await page.locator('#installmentRequestModal').evaluate(dialog => dialog.open), false);
            await page.locator('.apply-btn').first().click();
            assert((await page.locator('#requestSelectedProducts').inputValue()).includes(product.name));
            await page.keyboard.press('Escape');
            assert.equal(await page.locator('#installmentRequestModal').evaluate(dialog => dialog.open), false);
            await page.goto('http://127.0.0.1:8000/register', {waitUntil: 'domcontentloaded'});
            assert.equal(await page.locator('#installmentRequestModal').evaluate(dialog => dialog.open), true);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
            assert.deepEqual(errors, [], 'No JavaScript errors');
            console.log(`PASS ${viewport.width}px: assessment, filters, sorting, privacy, login errors, navigation, pending request, cart reset, direct URL`);
            await page.close();
        }
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
