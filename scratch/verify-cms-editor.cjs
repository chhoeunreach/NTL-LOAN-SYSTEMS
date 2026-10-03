const { chromium } = require('C:/Users/CHHOEUNREACH/AppData/Local/npm-cache/_npx/e41f203b7505f1fb/node_modules/playwright');
const fs = require('node:fs');
const assert = require('node:assert/strict');

(async () => {
    const browser = await chromium.launch({ headless: true });
    try {
        const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        await page.route('**/loan-management/settings/cms', route => route.fulfill({ contentType: 'text/html', body: fs.readFileSync('scratch/cms-editor-fixture.html', 'utf8') }));
        await page.goto('http://127.0.0.1:8000/loan-management/settings/cms');
        assert(await page.locator('summary').filter({ hasText: 'Authorized Brands & Partners' }).evaluate(summary => summary.parentElement.open));
        const rows = page.locator('#cmsPartnerRows > .lm-partner-row');
        const initial = await rows.count();
        await page.locator('#cms_brands_source').selectOption('catalog');
        await rows.first().locator('[data-field=name]').fill('Updated Partner');
        assert.equal(await page.locator('#cms_brands_source').inputValue(), 'managed');
        await rows.first().locator('[data-field=enabled]').uncheck();
        assert.equal(await page.locator('#cmsPartnerPreviewList').getByText('Updated Partner', { exact: true }).count(), 0);
        await rows.first().locator('[data-field=enabled]').check();
        assert.equal(await page.locator('#cmsPartnerPreviewList').getByText('Updated Partner', { exact: true }).count(), 1);
        await page.locator('#cmsPartnerAdd').click();
        assert.equal(await rows.count(), initial + 1);
        await rows.last().locator('[data-field=name]').fill('New Partner');
        await rows.last().locator('[data-action=up]').click();
        assert.equal(await rows.nth(initial - 1).locator('[data-field=name]').inputValue(), 'New Partner');
        const serialized = await page.locator('form').filter({ has: page.locator('#cmsPartnerRows') }).evaluate(form => Array.from(new FormData(form).entries()));
        assert(serialized.some(([key, value]) => key === `home_cms[brands_items][${initial - 1}][name]` && value === 'New Partner'));
        await page.locator('#cms_brands').uncheck();
        assert(await page.locator('#cmsPartnerPreview').isHidden());
        await page.locator('#cms_brands').check();
        for (let count = await rows.count(); count > 0; count--) await rows.first().locator('[data-action=remove]').click();
        assert(await page.locator('#cmsPartnerEmpty').isVisible());
        assert.equal(await page.locator('#cmsPartnerStatus').innerText(), '0 visible / 0 partners');
        await page.locator('#cmsPartnerSample').click();
        assert.equal(await rows.count(), 8);
        await page.locator('#cmsPartnerSample').click();
        assert.equal(await rows.count(), 8);
        assert.deepEqual(errors, []);
        console.log('PASS CMS editor: edit, managed source, visibility, add, reorder, submitted names, delete all, empty state, sample import without duplicates');
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
