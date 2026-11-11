import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import puppeteer from 'puppeteer';

const url = process.env.THEME_TEST_URL ?? 'https://software-on-the-web-lara-new.test';
const systemChrome = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const browser = await puppeteer.launch({
    headless: true,
    executablePath: existsSync(systemChrome) ? systemChrome : undefined,
});

try {
    const page = await browser.newPage();
    await page.setViewport({ width: 1280, height: 800 });
    await page.emulateMediaFeatures([{ name: 'prefers-color-scheme', value: 'dark' }]);
    await page.goto(url, { waitUntil: 'networkidle2' });
    await page.evaluate(() => localStorage.setItem('sotw-theme', 'dark'));
    await page.reload({ waitUntil: 'networkidle2' });

    async function assertLightTheme() {
        const scheme = await page.$eval('html', (element) => getComputedStyle(element).colorScheme);
        assert.equal(scheme, 'light');
        assert.equal(await page.$('html.dark'), null);
        assert.equal(await page.$('[data-theme-toggle]'), null);
    }

    await assertLightTheme();

    const weekLink = await page.$('a[href*="/week/"]');
    assert.ok(weekLink, 'Expected a week navigation link on the homepage.');
    await weekLink.click();
    await page.waitForFunction(() => window.location.pathname.includes('/week/'));
    await assertLightTheme();

    console.log(`Theme smoke test passed: ${url}`);
} finally {
    await browser.close();
}
