const { chromium } = require('C:/Users/CHHOEUNREACH/AppData/Local/npm-cache/_npx/e41f203b7505f1fb/node_modules/playwright');
const fs = require('node:fs');
const assert = require('node:assert/strict');

(async () => {
    const browser = await chromium.launch({ headless: true });
    try {
        for (const width of [1440, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 1000 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('**/loan-management/admin-loan', route => route.fulfill({ contentType: 'text/html', body: fs.readFileSync('scratch/admin-layout-fixture.html', 'utf8') }));
            await page.goto('http://127.0.0.1:8000/loan-management/admin-loan');
            await page.locator('#global-header').waitFor();
            await page.waitForTimeout(1500);
            assert.equal(await page.locator('#loanManagementHeader').count(), 0);
            assert.equal(await page.locator('#loanSidebarToggle').count(), 1);
            assert(await page.locator('#adminLoanFilter').isVisible());
            assert(await page.locator('#root').innerText());
            const active = page.locator('#loanManagementSidebar a[aria-current=page]');
            assert.equal(await active.count(), 1);
            assert.equal(await active.getAttribute('title'), 'Admin Installment');
            const marker = await active.evaluate(link => {
                const style = getComputedStyle(link, '::before');
                return { opacity: style.opacity, width: parseFloat(style.width), color: style.backgroundColor, primary: getComputedStyle(link).color };
            });
            assert.equal(marker.opacity, '1');
            assert(marker.width >= 3);
            assert.equal(marker.color, marker.primary);
            const tabs = page.locator('#tabs-navigation-container button');
            assert.equal(await tabs.count(), 5);
            for (let i = 0; i < 5; i++) {
                await tabs.nth(i).click();
                await page.waitForTimeout(200);
            }
            await tabs.first().click();
            assert(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'No page overflow');
            assert(await page.locator('#loanSidebarBackdrop').isHidden(), 'Backdrop hidden with closed mobile menu and on desktop');
            await page.locator(width > 992 ? '#loanSidebarCollapse' : '#loanSidebarToggle').click();
            await page.waitForTimeout(350);
            const toggled = await page.evaluate(() => document.body.classList.contains('lm-sidebar-collapsed') || document.body.classList.contains('lm-sidebar-open'));
            assert(toggled, 'Shared sidebar toggle works');
            if (width > 992) {
                assert(await page.locator('#loanSidebarBackdrop').isHidden(), 'No empty desktop backdrop button');
                await page.locator('#loanSidebarCollapse').click();
            } else {
                assert(await page.locator('#loanSidebarBackdrop').isVisible());
                await page.locator('#loanSidebarBackdrop').click({ position: { x: width - 10, y: 200 } });
                assert(await page.locator('#loanSidebarBackdrop').isHidden(), 'Backdrop closes the mobile sidebar');
            }
            await page.waitForTimeout(350);
            await page.screenshot({ path: `scratch/admin-shared-${width}.png`, fullPage: true });
            assert.deepEqual(errors, []);
            console.log(`PASS ${width}px: original layout, five report tabs, sidebar toggle, filters, no overflow or JavaScript errors`);
            await page.close();
        }
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
