/**
 * Render the artwork pages written by make_art.py to PNG with Chromium.
 * Usage: node tools/render_art.js [name-filter]
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require(process.env.PLAYWRIGHT_PATH || '/opt/node22/lib/node_modules/playwright');

(async () => {
	const dir = path.join(__dirname, '..', 'build', 'art');
	const out = path.join(dir, 'png');
	fs.mkdirSync(out, { recursive: true });
	const jobs = JSON.parse(fs.readFileSync(path.join(dir, 'jobs.json'), 'utf8'));
	const filter = process.argv[2] || '';
	const browser = await chromium.launch();
	const page = await browser.newPage();
	for (const job of jobs) {
		if (filter && !job.name.includes(filter)) continue;
		await page.setViewportSize({ width: job.width, height: job.height });
		await page.goto('file://' + path.join(dir, job.name + '.html'));
		await page.evaluate(() => document.fonts.ready);
		await page.screenshot({ path: path.join(out, job.name + '.png'), clip: { x: 0, y: 0, width: job.width, height: job.height } });
		process.stdout.write('.');
	}
	await browser.close();
	console.log('\nrendered');
})();
