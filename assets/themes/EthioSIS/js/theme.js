/**
 * EthioSIS theme JS
 *
 * Loaded (deferred) on every page when assets/themes/<THEME>/js/theme.js exists.
 * Jobs:
 *  1. Relocate the context selects (school, year, quarter, course period)
 *     from the sidebar into the top bar (#topbar-context). The originals
 *     stay inside their <form> (hidden on desktop) so the app's
 *     `onchange-ajax-post-form` flow keeps working; we mirror any change
 *     back to the hidden original and re-clone whenever the menu re-renders.
 *  2. Add a collapse/expand button to every sidebar module.
 *  3. Re-hydrate both after the sidebar is replaced by AJAX (sidefunc=update).
 */
(function () {
	'use strict';

	if (window.__ethioTheme) { return; }
	window.__ethioTheme = true;

	// ------------------------------------------------------------------
	// 1. Context selects -> top bar
	// ------------------------------------------------------------------
	var FIELDS = [
		{ id: 'school', label: 'School' },
		{ id: 'student_id', label: 'Student' },
		{ id: 'syear', label: 'Year' },
		{ id: 'mp', label: 'Quarter' },
		{ id: 'period', label: 'Period' }
	];

	function contextHost() {
		var host = document.getElementById('topbar-context');

		if (!host) {
			var wrap = document.getElementById('footerwrap');

			if (!wrap) { return null; }

			host = document.createElement('div');
			host.id = 'topbar-context';
			host.setAttribute('aria-label', 'Context');

			var menuBtn = document.getElementById('BottomButtonMenu');

			wrap.insertBefore(host, menuBtn ? menuBtn.nextSibling : wrap.firstChild);
		}

		return host;
	}

	// Copy `<select>` markup into the top bar; originals stay hidden in the
	// sidebar form (desktop) or visible inside the drawer (narrow screens).
	function hydrateContext() {
		var host = contextHost();

		if (!host) { return; }

		host.innerHTML = '';

		FIELDS.forEach(function (f) {
			var orig = document.getElementById(f.id);

			if (!orig || !orig.options) { return; }

			var field = document.createElement('span');
			field.className = 'topbar-field';
			field.dataset.for = orig.id;

			var label = document.createElement('label');
			label.textContent = f.label;
			label.setAttribute('for', 'tb-' + orig.id);

			var clone = document.createElement('select');
			clone.id = 'tb-' + orig.id;
			clone.className = 'topbar-select';
			clone.setAttribute('aria-label', f.label);

			Array.prototype.forEach.call(orig.options, function (o) {
				clone.add(new Option(o.text, o.value, o.defaultSelected, o.selected));
			});

			clone.value = orig.value;

			field.appendChild(label);
			field.appendChild(clone);
			host.appendChild(field);
		});
	}

	function onContextChange(e) {
		var target = e.target;

		if (!target || target.tagName !== 'SELECT' || !target.classList.contains('topbar-select')) {
			return;
		}

		var field = target.closest ? target.closest('.topbar-field') : null;
		var origId = field ? field.dataset.for : null;
		var orig = origId ? document.getElementById(origId) : null;

		if (orig && orig.value !== target.value) {
			orig.value = target.value;

			// Let the app's (document-wide) `onchange-ajax-post-form` handler fire.
			orig.dispatchEvent(new Event('change', { bubbles: true }));

			// Give the AJAX response a moment to update the menu before re-cloning.
			setTimeout(hydrateContext, 350);
		}
	}

	document.addEventListener('change', onContextChange, true);

	// ------------------------------------------------------------------
	// 2. Per-module collapse buttons
	// ------------------------------------------------------------------
	function initCollapse() {
		var modules = document.querySelectorAll('.adminmenu .menu-module');

		Array.prototype.forEach.call(modules, function (li) {
			if (li.querySelector(':scope > .menu-collapse')) { return; }

			var btn = document.createElement('button');

			btn.type = 'button';
			btn.className = 'menu-collapse';
			btn.title = 'Toggle section';
			btn.setAttribute('aria-label', 'Toggle section');
			btn.setAttribute('aria-expanded', 'true');
			btn.innerHTML = '<span class="menu-collapse-icon" aria-hidden="true"></span>';

			btn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();

				var collapsed = li.classList.toggle('collapsed');

				btn.setAttribute('aria-expanded', String(!collapsed));
			});

			li.appendChild(btn);
		});
	}

	// ------------------------------------------------------------------
	// 3. Re-hydrate whenever the sidebar is replaced (sidefunc=update)
	// ------------------------------------------------------------------
	function rehydrate() {
		if (window.__ethioRehydrating) { return; }
		window.__ethioRehydrating = true;
		try {
			hydrateContext();
			initCollapse();
		} finally {
			window.__ethioRehydrating = false;
		}
	}

	var observer = new MutationObserver(function () { rehydrate(); });

	// Watch the sidebar only (NOT #footerwrap: #topbar-context is rebuilt by
	// hydrateContext() itself, so observing it would loop forever).
	function watch(target) {
		if (target) { observer.observe(target, { childList: true, subtree: true }); }
	}

	watch(document.getElementById('menu-top'));
	watch(document.getElementById('menu'));

	// ------------------------------------------------------------------
	// 4. Own the drawer toggle on narrow screens.
	//    The app's expandMenu() decides "mobile" via isMobileMenu()
	//    (#menu width === viewport width), which never matches our 320px
	//    drawer, so the hamburger would take the desktop path and do
	//    nothing. Capture-phase here wins before the app's handler.
	// ------------------------------------------------------------------
	var MOBILE_BP = 874;

	function drawerOpen() {
		var menu = document.getElementById('menu');

		return !!menu && menu.classList.contains('slide');
	}

	function setDrawer(open) {
		var menu = document.getElementById('menu');

		if (!menu) { return; }

		if (open) {
			menu.classList.remove('hide');	// legacy .hide is display:none
			menu.classList.add('slide');
		} else {
			menu.classList.remove('slide');
			document.body.style.overflow = '';
		}
	}

	document.addEventListener('click', function (e) {
		if (window.innerWidth > MOBILE_BP) { return; }

		var target = e.target;

		if (target && target.closest && target.closest('#BottomButtonMenu')) {
			e.preventDefault();
			e.stopPropagation();
			setDrawer(!drawerOpen());
			document.body.style.overflow = drawerOpen() ? 'hidden' : '';
			return;
		}

		// Close the drawer after navigating with a menu link.
		if (drawerOpen() && target && target.closest && (target.closest('.adminmenu a') || target.closest('#menu-user a'))) {
			setDrawer(false);
		}
	}, true);

	// Initial pass.
	rehydrate();
})();