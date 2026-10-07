/**
 * Quick screenshots: node tools/shot.js <path> <width> <out.png> [full]
 */
const { chromium } = require(process.env.PLAYWRIGHT_PATH || '/opt/node22/lib/node_modules/playwright');
(async () => {
	const [, , path, width, out, full] = process.argv;
	const base = process.env.SITE || 'http://127.0.0.1:9400';
	const browser = await chromium.launch();
	const w = parseInt(width, 10);
	const ctx = await browser.newContext({ viewport: { width: w, height: w < 800 ? 844 : 900 }, deviceScaleFactor: 1, isMobile: w < 800, hasTouch: w < 800 });
	await ctx.addCookies([{ name: 'playground_auto_login_already_happened', value: '1', url: base }]);
	const page = await ctx.newPage();
	const errors = [];
	page.on('pageerror', (e) => errors.push(e.message));
	await page.goto(base + path, { waitUntil: 'networkidle' });
	await page.evaluate(() => document.fonts.ready);
	await page.screenshot({ path: out, fullPage: full === 'full' });
	if (errors.length) console.log('JS errors:', errors);
	await browser.close();
})();
