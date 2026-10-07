/**
 * AVENZA front-end behaviour. No dependencies.
 *
 * Sticky header state, accessible sub-menus, the mobile drawer with a focus
 * trap, back to top, and the article table of contents highlight.
 */
(function () {
	'use strict';

	var doc = document;
	var header = doc.getElementById('avz-header');
	var drawer = doc.getElementById('avz-drawer');
	var burger = doc.querySelector('.avz-burger');
	var toTop = doc.querySelector('.avz-float--top');
	var lastFocus = null;

	/* Header shadow and back-to-top visibility. */
	var ticking = false;
	function onScroll() {
		var y = window.scrollY || doc.documentElement.scrollTop;
		if (header) {
			header.classList.toggle('is-scrolled', y > 8);
		}
		if (toTop) {
			toTop.hidden = y < 700;
		}
		ticking = false;
	}
	window.addEventListener('scroll', function () {
		if (!ticking) {
			window.requestAnimationFrame(onScroll);
			ticking = true;
		}
	}, { passive: true });
	onScroll();

	if (toTop) {
		toTop.addEventListener('click', function () {
			var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
			window.scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' });
			var main = doc.getElementById('main');
			if (main) {
				main.focus({ preventScroll: true });
			}
		});
	}

	/* Sub-menu toggles, desktop and drawer. */
	function closeSubs(except) {
		doc.querySelectorAll('.avz-menu .has-sub.is-open').forEach(function (li) {
			if (li !== except) {
				li.classList.remove('is-open');
				var btn = li.querySelector('.avz-sub-toggle');
				if (btn) {
					btn.setAttribute('aria-expanded', 'false');
				}
			}
		});
	}

	doc.querySelectorAll('.avz-sub-toggle').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			var li = btn.closest('li');
			var open = !li.classList.contains('is-open');
			if (btn.closest('.avz-menu')) {
				closeSubs(li);
			}
			li.classList.toggle('is-open', open);
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	});

	doc.addEventListener('click', function (e) {
		if (!e.target.closest('.avz-menu .has-sub')) {
			closeSubs(null);
		}
	});

	doc.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			var openLi = doc.querySelector('.avz-menu .has-sub.is-open');
			if (openLi) {
				closeSubs(null);
				var t = openLi.querySelector('.avz-sub-toggle');
				if (t) {
					t.focus();
				}
			}
		}
	});

	/* Mobile drawer. */
	function focusables() {
		return Array.prototype.filter.call(
			drawer.querySelectorAll('a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])'),
			function (el) { return el.offsetParent !== null; }
		);
	}

	function openDrawer() {
		lastFocus = doc.activeElement;
		drawer.hidden = false;
		doc.body.classList.add('avz-lock');
		burger.setAttribute('aria-expanded', 'true');
		window.requestAnimationFrame(function () {
			drawer.classList.add('is-open');
			var close = drawer.querySelector('.avz-drawer__close');
			if (close) {
				close.focus();
			}
		});
	}

	function closeDrawer() {
		drawer.classList.remove('is-open');
		doc.body.classList.remove('avz-lock');
		burger.setAttribute('aria-expanded', 'false');
		var finish = function () {
			drawer.hidden = true;
		};
		if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			finish();
		} else {
			window.setTimeout(finish, 250);
		}
		if (lastFocus && lastFocus.focus) {
			lastFocus.focus();
		}
	}

	if (drawer && burger) {
		burger.addEventListener('click', openDrawer);
		drawer.querySelector('.avz-drawer__close').addEventListener('click', closeDrawer);

		drawer.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') {
				e.preventDefault();
				closeDrawer();
				return;
			}
			if (e.key !== 'Tab') {
				return;
			}
			var items = focusables();
			if (!items.length) {
				return;
			}
			var first = items[0];
			var last = items[items.length - 1];
			if (e.shiftKey && doc.activeElement === first) {
				e.preventDefault();
				last.focus();
			} else if (!e.shiftKey && doc.activeElement === last) {
				e.preventDefault();
				first.focus();
			}
		});

		drawer.addEventListener('click', function (e) {
			var link = e.target.closest('a[href]');
			if (link && link.getAttribute('href').charAt(0) === '#') {
				closeDrawer();
			}
		});

		window.addEventListener('resize', function () {
			if (window.innerWidth >= 1200 && !drawer.hidden) {
				closeDrawer();
			}
		});
	}

	/* Table of contents: highlight the section in view. */
	var toc = doc.querySelector('.avz-toc');
	if (toc && 'IntersectionObserver' in window) {
		var links = {};
		toc.querySelectorAll('a[href^="#"]').forEach(function (a) {
			links[a.getAttribute('href').slice(1)] = a;
		});
		var observer = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				if (entry.isIntersecting && links[entry.target.id]) {
					toc.querySelectorAll('a.is-current').forEach(function (a) { a.classList.remove('is-current'); });
					links[entry.target.id].classList.add('is-current');
				}
			});
		}, { rootMargin: '-20% 0px -70% 0px' });
		Object.keys(links).forEach(function (id) {
			var el = doc.getElementById(id);
			if (el) {
				observer.observe(el);
			}
		});
	}

	/* Move focus to the form message after a redirect. */
	var alert = doc.querySelector('.avz-alert');
	if (alert) {
		alert.focus();
	}
})();
