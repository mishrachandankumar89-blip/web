/**
 * Functional checks against a running AVENZA install:
 * contact form (error and success paths, lead stored), llms.txt, robots.txt,
 * JSON-LD schema per template, meta title and description lengths,
 * Customizer and setup screens, and keyboard behaviour of the mobile drawer.
 *
 *   node tools/functional.js
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require(process.env.PLAYWRIGHT_PATH || '/opt/node22/lib/node_modules/playwright');

const BASE = process.env.SITE || 'http://127.0.0.1:9400';
const results = [];
const check = (name, ok, detail = '') => {
	results.push({ name, ok, detail });
	console.log(`${ok ? 'PASS' : 'FAIL'}  ${name}${detail ? '  (' + detail + ')' : ''}`);
};

(async () => {
	const browser = await chromium.launch();
	const guest = await browser.newContext({ viewport: { width: 1280, height: 900 } });
	await guest.addCookies([{ name: 'playground_auto_login_already_happened', value: '1', url: BASE }]);
	const page = await guest.newPage();
	const jsErrors = [];
	page.on('pageerror', (e) => jsErrors.push(e.message));

	/* Contact form: error path. */
	await page.goto(BASE + '/contact/', { waitUntil: 'networkidle' });
	await page.fill('#avz-name', 'QA Tester');
	await page.fill('#avz-email', 'not-an-email');
	await page.waitForTimeout(3500);
	await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.click('.avz-form button[type=submit]')]);
	const errText = await page.textContent('.avz-alert--error').catch(() => '');
	check('Form shows errors for bad input', /valid email/i.test(errText) && /tell us a little more/i.test(errText) && /consent/i.test(errText));
	check('Form keeps typed values after an error', (await page.inputValue('#avz-name')) === 'QA Tester');

	/* Contact form: success path. */
	await page.goto(BASE + '/contact/', { waitUntil: 'networkidle' });
	await page.fill('#avz-name', 'QA Tester');
	await page.fill('#avz-email', 'qa@example.com');
	await page.fill('#avz-phone', '+91 98765 43210');
	await page.fill('#avz-website', 'https://example.com');
	await page.selectOption('#avz-service', { index: 1 });
	await page.fill('#avz-message', 'We would like to understand how our clinic appears in AI answers and Google Maps.');
	await page.check('#avz-consent');
	await page.waitForTimeout(3500);
	await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.click('.avz-form button[type=submit]')]);
	const okText = await page.textContent('.avz-alert--ok').catch(() => '');
	check('Form success message after valid submit', /reached us/i.test(okText));

	/* Bot submit (too fast) is silently dropped. */
	await page.goto(BASE + '/contact/', { waitUntil: 'networkidle' });
	await page.fill('#avz-name', 'Fast Bot');
	await page.fill('#avz-email', 'bot@example.com');
	await page.fill('#avz-message', 'This message arrives far too quickly to come from a person.');
	await page.check('#avz-consent');
	await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle' }), page.click('.avz-form button[type=submit]')]);

	/* llms.txt and robots.txt. */
	const llms = await page.request.get(BASE + '/llms.txt');
	const llmsText = await llms.text();
	check('llms.txt served as text', llms.status() === 200 && /text\/plain/.test(llms.headers()['content-type'] || ''));
	check('llms.txt lists services with absolute URLs', /^# AVENZA/m.test(llmsText) && /\[Generative Engine Optimization \(GEO\)\]\(http/.test(llmsText) && /## Insights/.test(llmsText));
	const robots = await (await page.request.get(BASE + '/robots.txt')).text();
	check('robots.txt welcomes AI crawlers', ['GPTBot', 'OAI-SearchBot', 'ClaudeBot', 'PerplexityBot', 'Google-Extended'].every((b) => robots.includes('User-agent: ' + b)));
	check('robots.txt points to sitemap and llms.txt', /Sitemap: http.*wp-sitemap\.xml/.test(robots) && /llms\.txt/.test(robots));
	const sitemap = await page.request.get(BASE + '/wp-sitemap.xml');
	check('XML sitemap available', sitemap.status() === 200);
	const leadsInSitemap = await (await page.request.get(BASE + '/wp-sitemap-posts-avenza_lead-1.xml')).status();
	check('Enquiries never appear in the sitemap', leadsInSitemap === 404);

	/* Schema and meta per template. */
	const templates = {
		'/': ['Organization', 'WebSite', 'WebPage', 'FAQPage'],
		'/services/generative-engine-optimization/': ['Service', 'BreadcrumbList', 'FAQPage'],
		'/services/': ['ItemList', 'BreadcrumbList'],
		'/what-is-generative-engine-optimization/': ['BlogPosting', 'BreadcrumbList', 'FAQPage'],
		'/about/': ['AboutPage'],
		'/contact/': ['ContactPage'],
		'/insights/': ['CollectionPage'],
	};
	for (const [url, types] of Object.entries(templates)) {
		await page.goto(BASE + url, { waitUntil: 'domcontentloaded' });
		const data = await page.evaluate(() => {
			const blocks = Array.from(document.querySelectorAll('script[type="application/ld+json"]')).map((s) => s.textContent);
			const title = document.title;
			const desc = (document.querySelector('meta[name=description]') || {}).content || '';
			const canonical = (document.querySelector('link[rel=canonical]') || {}).href || '';
			const og = (document.querySelector('meta[property="og:image"]') || {}).content || '';
			return { blocks, title, desc, canonical, og };
		});
		let found = [];
		let valid = true;
		for (const b of data.blocks) {
			try {
				const j = JSON.parse(b);
				for (const n of j['@graph'] || [j]) found = found.concat(n['@type']);
			} catch (e) { valid = false; }
		}
		const missing = types.filter((t) => !found.includes(t));
		check(`Schema on ${url}`, valid && !missing.length, missing.length ? 'missing ' + missing.join(', ') : found.join(', '));
		check(`Meta on ${url}`, data.title.length > 10 && data.title.length <= 65 && data.desc.length >= 70 && data.desc.length <= 160 && /^https?:\/\//.test(data.canonical) && /^https?:\/\//.test(data.og), `title ${data.title.length}, description ${data.desc.length}`);
	}

	/* Drawer keyboard behaviour on a phone. */
	const phone = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
	await phone.addCookies([{ name: 'playground_auto_login_already_happened', value: '1', url: BASE }]);
	const mp = await phone.newPage();
	await mp.goto(BASE + '/', { waitUntil: 'networkidle' });
	await mp.click('.avz-burger');
	await mp.waitForTimeout(350);
	const focusInDrawer = await mp.evaluate(() => document.getElementById('avz-drawer').contains(document.activeElement));
	await mp.click('.avz-drawer-menu .has-sub > .avz-sub-toggle');
	const subOpen = await mp.evaluate(() => getComputedStyle(document.querySelector('.avz-drawer-menu .has-sub > .sub-menu')).display !== 'none');
	await mp.keyboard.press('Escape');
	await mp.waitForTimeout(350);
	const closed = await mp.evaluate(() => document.getElementById('avz-drawer').hidden && document.activeElement.classList.contains('avz-burger'));
	check('Drawer moves focus inside, opens Services, closes on Escape', focusInDrawer && subOpen && closed);
	await phone.close();

	check('No JavaScript errors on public pages', jsErrors.length === 0, jsErrors.slice(0, 2).join(' | '));

	/* Admin screens. */
	const admin = await browser.newContext({ viewport: { width: 1440, height: 900 } });
	const ap = await admin.newPage();
	const adminErrors = [];
	ap.on('pageerror', (e) => adminErrors.push(e.message));
	await ap.goto(BASE + '/wp-admin/', { waitUntil: 'networkidle' });
	await ap.goto(BASE + '/wp-admin/edit.php?post_type=avenza_lead', { waitUntil: 'networkidle' });
	const leadsHtml = await ap.content();
	check('Valid enquiry stored as a private lead', leadsHtml.includes('QA Tester') && leadsHtml.includes('qa@example.com'));
	check('Bot enquiry was not stored', !leadsHtml.includes('Fast Bot'));
	await ap.goto(BASE + '/wp-admin/themes.php?page=avenza-setup', { waitUntil: 'networkidle' });
	check('Setup screen renders', (await ap.textContent('h1')).includes('AVENZA Setup'));
	await ap.goto(BASE + '/wp-admin/customize.php', { waitUntil: 'networkidle' });
	const panel = await ap.$('#accordion-panel-avenza');
	check('Customizer shows the AVENZA Settings panel', !!panel);
	await ap.goto(BASE + '/wp-admin/nav-menus.php', { waitUntil: 'networkidle' });
	check('Menus created', (await ap.content()).includes('AVENZA Primary'));
	check('No JavaScript errors in admin screens', adminErrors.length === 0, adminErrors.slice(0, 2).join(' | '));
	await admin.close();

	await browser.close();
	const failed = results.filter((r) => !r.ok).length;
	fs.mkdirSync(path.join(__dirname, '..', 'build', 'qa'), { recursive: true });
	fs.writeFileSync(path.join(__dirname, '..', 'build', 'qa', 'functional.json'), JSON.stringify(results, null, 1));
	console.log(`\n${results.length - failed} of ${results.length} checks passed`);
	process.exit(failed ? 1 : 0);
})();
