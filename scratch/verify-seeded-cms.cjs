const assert = require('node:assert/strict');
const {chromium} = require('C:/Users/CHHOEUNREACH/AppData/Local/npm-cache/_npx/e41f203b7505f1fb/node_modules/playwright');

(async () => {
    const browser = await chromium.launch({headless:true});
    try {
        for (const width of [1440,390]) {
            const page = await browser.newPage({viewport:{width,height:1000}});
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.goto('http://127.0.0.1:8000', {waitUntil:'domcontentloaded'});
            assert.equal(await page.locator('.product-card').count(), 3);
            assert.equal(await page.locator('.brand-partner').count(), 8);
            assert(await page.locator('#contact').textContent().then(text => text.includes('showroom@example.com')));
            const images = page.locator('.product-image-wrap img');
            assert.equal(await images.count(), 3);
            await images.evaluateAll(images => Promise.all(images.map(img => img.complete ? Promise.resolve() : Promise.race([
                new Promise(resolve => {img.addEventListener('load',resolve,{once:true});img.addEventListener('error',resolve,{once:true});}),
                new Promise(resolve => setTimeout(resolve,15000))
            ]))));
            const imageStates = await images.evaluateAll(images => images.map(img => ({src:img.src,loaded:img.naturalWidth > 0})));
            assert(imageStates.every(img => img.loaded), JSON.stringify(imageStates));
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
            assert.deepEqual(errors, []);
            await page.screenshot({path:`scratch/cms-seeded-${width}.png`,fullPage:true});
            console.log(`PASS ${width}px: 3 seeded products with photos, 8 brands, contact information, no overflow or JavaScript errors`);
            await page.close();
        }
    } finally {
        await browser.close();
    }
})().catch(error => {console.error(error);process.exitCode=1;});
