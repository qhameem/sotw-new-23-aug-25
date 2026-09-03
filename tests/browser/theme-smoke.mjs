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
    await page.evaluate(() => localStorage.removeItem('sotw-theme'));
    await page.reload({ waitUntil: 'networkidle2' });

    assert.equal(await page.$eval('html', (element) => element.dataset.theme), 'light');
    assert.equal(await page.$eval('html', (element) => element.dataset.themePreference), 'light');
    assert.equal(await page.$eval('[data-theme-toggle]', (element) => Boolean(element)), true);

    await page.click('[data-theme-toggle]');
    assert.equal(await page.$eval('html', (element) => element.dataset.theme), 'dark');
    assert.equal(await page.$eval('html', (element) => element.dataset.themePreference), 'dark');

    await page.reload({ waitUntil: 'networkidle2' });
    assert.equal(await page.$eval('html', (element) => element.dataset.themePreference), 'dark');

    const persisted = await page.evaluate(() => localStorage.getItem('sotw-theme'));
    assert.equal(persisted, 'dark');

    const weekLink = await page.$('a[href*="/week/"]');
    assert.ok(weekLink, 'Expected a week navigation link on the homepage.');
    await weekLink.click();
    await page.waitForFunction(() => window.location.pathname.includes('/week/'));
    assert.equal(await page.$eval('html', (element) => element.dataset.theme), 'dark');
    assert.equal(await page.$eval('html', (element) => element.dataset.themePreference), 'dark');
    assert.equal(await page.$eval('[data-theme-toggle]', (element) => Boolean(element)), true);

    console.log(`Theme smoke test passed: ${url}`);
} finally {
    await browser.close();
}
