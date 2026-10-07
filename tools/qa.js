/**
 * AVENZA QA: renders every page in a real WordPress install at nine
 * viewports and fails on anything a visitor or client would notice.
 *
 *   node tools/qa.js [--import] [--editor] [--shots] [--only=/path/]
 *
 * --import  run the one-click setup through the admin screen first
 * --editor  open every page, service and post in the block editor and
 *           fail on any invalid block
 * --shots   save full-page screenshots for desktop and phone
 *
 * Checks per page and viewport: horizontal overflow (naming the element),
 * text contrast under WCAG AA, tap targets under 44px, prose under 15px and
 * labels under 13px, H1 count, images without alt, JavaScript errors, PHP
 * notices in the HTML, and the mobile drawer opening on screen.
 */
const fs = require('fs');
const path = require('path');
const { chromium } = require(process.env.PLAYWRIGHT_PATH || '/opt/node22/lib/node_modules/playwright');

const BASE = process.env.SITE || 'http://127.0.0.1:9400';
const ROOT = path.join(__dirname, '..');
const OUT = path.join(ROOT, 'build', 'qa');
const args = process.argv.slice(2);
const flag = (f) => args.includes(f);
const only = (args.find((a) => a.startsWith('--only=')) || '').slice(7).split(',').filter(Boolean);

const VIEWPORTS = [
	{ name: 'iphone-se', width: 320, height: 568, mobile: true },
	{ name: 'android-small', width: 360, height: 780, mobile: true },
	{ name: 'iphone-14', width: 390, height: 844, mobile: true },
	{ name: 'iphone-pro-max', width: 430, height: 932, mobile: true },
	{ name: 'tablet-portrait', width: 768, height: 1024, mobile: true },
	{ name: 'tablet-landscape', width: 1024, height: 768, mobile: false },
	{ name: 'laptop', width: 1280, height: 800, mobile: false },
	{ name: 'desktop', width: 1440, height: 900, mobile: false },
	{ name: 'wide', width: 1920, height: 1080, mobile: false },
];

const EXPECT = {};

function pagesFromManifest() {
	const m = JSON.parse(fs.readFileSync(path.join(ROOT, 'avenza', 'content', 'manifest.json'), 'utf8'));
	const list = [];
	EXPECT['/'] = 'home';
	for (const p of m.pages) {
		const url = p.slug === m.front_page ? '/' : `/${p.slug}/`;
		list.push(url);
		if (!EXPECT[url]) EXPECT[url] = p.slug === m.posts_page ? 'blog' : 'page';
	}
	for (const s of m.services) {
		list.push(`/services/${s.slug}/`);
		EXPECT[`/services/${s.slug}/`] = 'single-avenza_service';
	}
	for (const p of m.posts) {
		list.push(`/${p.slug}/`);
		EXPECT[`/${p.slug}/`] = 'single-post';
	}
	list.push('/this-page-does-not-exist/');
	EXPECT['/this-page-does-not-exist/'] = 'error404';
	list.push('/?s=seo');
	EXPECT['/?s=seo'] = 'search';
	return list;
}

/* Runs inside the page. */
function audit() {
	const out = { overflow: [], contrast: [], taps: [], tiny: [], h1: 0, noAlt: [] };
	const vw = document.documentElement.clientWidth;
	out.scrollWidth = document.documentElement.scrollWidth;
	out.vw = vw;

	const isHidden = (el) => {
		const s = getComputedStyle(el);
		return s.display === 'none' || s.visibility === 'hidden' || s.opacity === '0';
	};
	const inScroller = (el) => {
		for (let p = el.parentElement; p && p !== document.body; p = p.parentElement) {
			const ox = getComputedStyle(p).overflowX;
			if (ox === 'auto' || ox === 'scroll' || ox === 'hidden' || ox === 'clip') return true;
		}
		return false;
	};
	const label = (el) => {
		let s = el.tagName.toLowerCase();
		if (el.id) s += '#' + el.id;
		const cls = (typeof el.className === 'string' ? el.className : (el.className && el.className.baseVal) || '').trim();
		if (cls) s += '.' + cls.split(/\s+/).slice(0, 3).join('.');
		return s;
	};

	const all = document.querySelectorAll('body *');
	for (const el of all) {
		if (el.closest('#wpadminbar, .avz-drawer[hidden], .screen-reader-text, .avz-form__hp, .avz-skip')) continue;
		if (isHidden(el)) continue;
		const st = getComputedStyle(el);
		if (st.position === 'fixed') continue;
		const r = el.getBoundingClientRect();
		if (!r.width && !r.height) continue;
		if ((r.right > vw + 1 || r.left < -1) && !inScroller(el)) {
			out.overflow.push(`${label(el)} [${Math.round(r.left)}, ${Math.round(r.right)}]`);
		}
	}

	/* Contrast for elements that hold text directly. */
	const parse = (c) => {
		const m = c.match(/rgba?\(([^)]+)\)/);
		if (!m) return null;
		const p = m[1].split(/[\s,\/]+/).filter(Boolean).map(Number);
		return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 };
	};
	const lum = (c) => {
		const f = (v) => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
		return 0.2126 * f(c.r) + 0.7152 * f(c.g) + 0.0722 * f(c.b);
	};
	const blend = (top, bottom) => ({
		r: top.r * top.a + bottom.r * (1 - top.a),
		g: top.g * top.a + bottom.g * (1 - top.a),
		b: top.b * top.a + bottom.b * (1 - top.a),
		a: 1,
	});
	const bgOf = (el) => {
		const layers = [];
		for (let p = el; p; p = p.parentElement) {
			const c = parse(getComputedStyle(p).backgroundColor);
			if (c && c.a > 0) {
				layers.push(c);
				if (c.a >= 1) break;
			}
		}
		let base = { r: 255, g: 255, b: 255, a: 1 };
		for (let i = layers.length - 1; i >= 0; i--) base = blend(layers[i], base);
		return base;
	};
	const ratio = (a, b) => {
		const l1 = lum(a), l2 = lum(b);
		return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
	};

	for (const el of all) {
		if (el.closest('#wpadminbar, .screen-reader-text, .avz-form__hp, .avz-skip, .avz-drawer[hidden], svg')) continue;
		const own = Array.from(el.childNodes).some((n) => n.nodeType === 3 && n.textContent.trim().length > 1);
		if (!own || isHidden(el)) continue;
		const r = el.getBoundingClientRect();
		if (!r.width || !r.height) continue;
		const st = getComputedStyle(el);
		const fg = parse(st.color);
		if (!fg) continue;
		const bg = bgOf(el);
		const color = fg.a < 1 ? blend(fg, bg) : fg;
		const size = parseFloat(st.fontSize);
		const bold = parseInt(st.fontWeight, 10) >= 700;
		const large = size >= 24 || (size >= 18.66 && bold);
		const need = large ? 3 : 4.5;
		const cr = ratio(color, bg);
		if (cr < need) {
			out.contrast.push(`${label(el)} "${el.textContent.trim().slice(0, 40)}" ${cr.toFixed(2)} < ${need}`);
		}

		const tag = el.tagName.toLowerCase();
		const isLabelish = el.closest('.avz-eyebrow, .avz-num, .avz-card__num, .avz-card__more, .avz-crumbs, .avz-footer__bar, .avz-footer__title, .avz-contact-list__label, th, .avz-toc__title, figcaption, .wp-element-caption, cite, label, .avz-menu, .avz-btn, .wp-block-button__link, small, .avz-form__note, .avz-hero__meta, code');
		const isProse = ['p', 'li', 'td', 'dd', 'summary'].includes(tag) && !isLabelish;
		if (isProse && size < 15) out.tiny.push(`${label(el)} ${size}px prose`);
		else if (size < 13) out.tiny.push(`${label(el)} ${size}px`);
	}

	/* Tap targets. */
	const tapSel = 'button, .wp-block-button__link, .avz-btn, input:not([type=hidden]), select, textarea, nav a, .avz-float, .avz-sub-toggle, summary, .avz-pagination .page-numbers';
	for (const el of document.querySelectorAll(tapSel)) {
		if (el.closest('#wpadminbar, .avz-form__hp, .avz-drawer[hidden], .screen-reader-text')) continue;
		if (isHidden(el)) continue;
		const r = el.getBoundingClientRect();
		if (!r.width || !r.height) continue;
		if (el.type === 'checkbox' || el.type === 'radio') {
			if (r.width < 22 || r.height < 22) out.taps.push(`${label(el)} ${Math.round(r.width)}x${Math.round(r.height)}`);
			continue;
		}
		const inline = el.closest('.avz-crumbs');
		const min = inline ? 24 : 44;
		if (r.height < min - 0.5 || r.width < Math.min(min, 24) - 0.5) out.taps.push(`${label(el)} ${Math.round(r.width)}x${Math.round(r.height)}`);
	}

	out.h1 = document.querySelectorAll('h1').length;
	for (const img of document.querySelectorAll('img')) {
		if (!img.hasAttribute('alt')) out.noAlt.push(img.currentSrc || img.src);
	}
	return out;
}

async function scrollThrough(page) {
	await page.evaluate(async () => {
		const step = Math.max(400, window.innerHeight * 0.9);
		for (let y = 0; y < document.documentElement.scrollHeight; y += step) {
			window.scrollTo(0, y);
			await new Promise((r) => setTimeout(r, 40));
		}
		window.scrollTo(0, 0);
	});
	await page.waitForLoadState('networkidle').catch(() => {});
}

async function runImport(browser) {
	const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
	const page = await ctx.newPage();
	await page.goto(BASE + '/wp-admin/', { waitUntil: 'networkidle' });
	await page.goto(BASE + '/wp-admin/themes.php?page=avenza-setup', { waitUntil: 'networkidle' });
	const label = await page.textContent('#avenza-run');
	await page.click('#avenza-run');
	await page.waitForSelector('#avenza-log li:has-text("Done")', { timeout: 240000 });
	const log = await page.$$eval('#avenza-log li', (lis) => lis.map((l) => l.textContent));
	await ctx.close();
	return { label: label.trim(), log };
}

async function editorCheck(browser) {
	const ctx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
	const page = await ctx.newPage();
	await page.goto(BASE + '/wp-admin/', { waitUntil: 'networkidle' });
	const items = [];
	for (const type of ['pages', 'avenza_service', 'posts']) {
		const res = await page.request.get(`${BASE}/wp-json/wp/v2/${type}?per_page=100&_fields=id,slug`);
		for (const it of await res.json()) items.push({ type, ...it });
	}
	const problems = [];
	for (const it of items) {
		await page.goto(`${BASE}/wp-admin/post.php?post=${it.id}&action=edit`, { waitUntil: 'domcontentloaded' });
		await page.waitForFunction(() => window.wp && wp.data && wp.data.select('core/block-editor') && wp.data.select('core/block-editor').getBlocks().length > 0, null, { timeout: 60000 }).catch(() => {});
		await page.waitForTimeout(500);
		const bad = await page.evaluate(() => {
			const out = [];
			const walk = (blocks) => blocks.forEach((b) => {
				if (!b.isValid) out.push(b.name + ' ' + JSON.stringify(b.attributes).slice(0, 120));
				walk(b.innerBlocks || []);
			});
			walk(wp.data.select('core/block-editor').getBlocks());
			return out;
		});
		if (bad.length) problems.push({ slug: it.slug, bad });
		process.stdout.write(bad.length ? 'x' : '.');
	}
	await ctx.close();
	return { checked: items.length, problems };
}

async function drawerCheck(page) {
	const burger = await page.$('.avz-burger');
	if (!burger || !(await burger.isVisible())) return null;
	await burger.click();
	await page.waitForTimeout(400);
	return page.evaluate(() => {
		const d = document.getElementById('avz-drawer');
		const close = d.querySelector('.avz-drawer__close');
		const r = close.getBoundingClientRect();
		const hit = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
		const dr = d.getBoundingClientRect();
		const ok = !d.hidden && dr.left >= -1 && dr.right <= window.innerWidth + 1 && close.contains(hit);
		return ok ? 'ok' : `drawer hidden=${d.hidden} rect=${Math.round(dr.left)},${Math.round(dr.right)} closeHit=${hit && hit.className}`;
	});
}

(async () => {
	fs.mkdirSync(path.join(OUT, 'shots'), { recursive: true });
	const browser = await chromium.launch();
	const report = { pages: {}, failures: 0 };

	if (flag('--import')) {
		report.import = await runImport(browser);
		console.log('Import:', report.import);
	}

	let pages = pagesFromManifest();
	if (only.length) pages = pages.filter((p) => only.includes(p));

	for (const vp of VIEWPORTS) {
		const ctx = await browser.newContext({ viewport: { width: vp.width, height: vp.height }, isMobile: vp.mobile, hasTouch: vp.mobile, deviceScaleFactor: 1 });
		await ctx.addCookies([{ name: 'playground_auto_login_already_happened', value: '1', url: BASE }]);
		const page = await ctx.newPage();
		let errors = [];
		page.on('pageerror', (e) => errors.push(e.message));
		page.on('console', (m) => { if (m.type() === 'error' && !/favicon|Failed to load resource/.test(m.text())) errors.push(m.text()); });

		for (const p of pages) {
			errors = [];
			const resp = await page.goto(BASE + p, { waitUntil: 'networkidle' });
			await page.evaluate(() => document.fonts.ready);
			await scrollThrough(page);
			const html = await page.content();
			const res = await page.evaluate(audit);
			res.status = resp ? resp.status() : 0;
			res.bodyClass = await page.evaluate(() => document.body ? document.body.className : '');
			res.php = (html.match(/<b>(Warning|Notice|Deprecated|Fatal error)<\/b>[^<]*/g) || []).slice(0, 3);
			res.js = errors.slice();
			if (vp.mobile && vp.width <= 430) res.drawer = await drawerCheck(page);

			const fails = [];
			if (res.scrollWidth > res.vw + 1) fails.push(`page scrolls sideways (${res.scrollWidth} > ${res.vw})`);
			if (res.overflow.length) fails.push('overflow: ' + res.overflow.slice(0, 4).join(' | '));
			if (res.contrast.length) fails.push('contrast: ' + res.contrast.slice(0, 4).join(' | '));
			if (res.taps.length) fails.push('tap targets: ' + res.taps.slice(0, 4).join(' | '));
			if (res.tiny.length) fails.push('small text: ' + res.tiny.slice(0, 4).join(' | '));
			if (res.h1 !== 1) fails.push(`${res.h1} H1 elements`);
			if (res.noAlt.length) fails.push('img without alt: ' + res.noAlt.slice(0, 2).join(' | '));
			if (res.js.length) fails.push('JS: ' + res.js.slice(0, 2).join(' | '));
			if (res.php.length) fails.push('PHP: ' + res.php.join(' | '));
			if (res.drawer && res.drawer !== 'ok') fails.push('drawer: ' + res.drawer);
			const expected = p.includes('does-not-exist') ? 404 : 200;
			if (res.status !== expected) fails.push(`HTTP ${res.status}`);
			if (EXPECT[p] && !res.bodyClass.split(/\s+/).includes(EXPECT[p])) fails.push(`expected template "${EXPECT[p]}", got body class "${res.bodyClass.slice(0, 80)}"`);

			report.pages[p] = report.pages[p] || {};
			report.pages[p][vp.name] = fails;
			if (fails.length) {
				report.failures++;
				console.log(`FAIL ${vp.name} ${p}\n   - ${fails.join('\n   - ')}`);
			}

			if (flag('--shots') && (vp.name === 'desktop' || vp.name === 'iphone-14')) {
				const name = (p === '/' ? 'home' : p.replace(/[^a-z0-9]+/gi, '-').replace(/^-|-$/g, '')) + '-' + vp.name + '.png';
				if (res.drawer) await page.reload({ waitUntil: 'networkidle' });
				await scrollThrough(page);
				await page.screenshot({ path: path.join(OUT, 'shots', name), fullPage: true });
			}
		}
		await ctx.close();
		process.stdout.write(`${vp.name} done\n`);
	}

	if (flag('--editor')) {
		report.editor = await editorCheck(browser);
		console.log(`\nEditor: ${report.editor.checked} items, ${report.editor.problems.length} with invalid blocks`);
		for (const pr of report.editor.problems) console.log('  ', pr.slug, pr.bad.slice(0, 3));
		if (report.editor.problems.length) report.failures++;
	}

	await browser.close();
	fs.writeFileSync(path.join(OUT, 'report.json'), JSON.stringify(report, null, 1));
	const renders = Object.keys(report.pages).length * VIEWPORTS.length;
	console.log(`\n${Object.keys(report.pages).length} pages x ${VIEWPORTS.length} viewports = ${renders} renders, ${report.failures} failing`);
	process.exit(report.failures ? 1 : 0);
})();
