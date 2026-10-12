/**
 * EthioSIS theme JS
 *
 * Loaded (deferred) on every page when assets/themes/<THEME>/js/theme.js exists.
 * Jobs:
 *  1. Relocate the context dropdowns (school, year, quarter, course period)
 *     to the top bar: a button per field ("School", "Year", "Quarter")
 *     that opens a dropdown listing the values (AASS, 2025-2026, …).
 *     The original selects stay inside their <form> (hidden on desktop)
 *     so the app's `onchange-ajax-post-form` flow keeps working; choosing
 *     a value mirrors it to the hidden original and we re-hydrate
 *     whenever the menu re-renders.
 *  2. Add a collapse/expand button to every sidebar module. Submenus are
 *     closed by default; the selected module's menu drops down.
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

	// Build one control per context field in the top bar: a BUTTON named
	// after the field ("School", "Year", "Quarter") whose dropdown lists
	// the values (AASS, 2025-2026, Quarter 1…). The originals stay hidden
	// in the sidebar form (desktop) or visible inside the drawer (narrow
	// screens); choosing a value mirrors it to the original so the app's
	// `onchange-ajax-post-form` flow keeps working.
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

			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'topbar-btn';
			btn.setAttribute('aria-haspopup', 'listbox');
			btn.setAttribute('aria-expanded', 'false');
			btn.innerHTML = '<span class="topbar-btn-label"></span><span class="topbar-btn-caret" aria-hidden="true"></span>';
			btn.firstChild.textContent = f.label;

			var menu = document.createElement('ul');
			menu.className = 'topbar-menu';
			menu.setAttribute('role', 'listbox');
			menu.hidden = true;

			Array.prototype.forEach.call(orig.options, function (o) {
				var li = document.createElement('li');
				var optBtn = document.createElement('button');
				optBtn.type = 'button';
				optBtn.setAttribute('role', 'option');
				optBtn.setAttribute('aria-selected', String(o.selected));
				optBtn.dataset.value = o.value;
				optBtn.textContent = o.text;
				li.appendChild(optBtn);
				menu.appendChild(li);
			});

			field.appendChild(btn);
			field.appendChild(menu);
			host.appendChild(field);
		});
	}

	function allMenus() {
		return Array.prototype.slice.call(document.querySelectorAll('.topbar-menu'));
	}

	function setMenuOpen(menu, open) {
		menu.hidden = !open;

		var field = menu.closest ? menu.closest('.topbar-field') : null;
		var btn = field ? field.querySelector('.topbar-btn') : null;

		if (btn) { btn.setAttribute('aria-expanded', String(open)); }
	}

	function closeAllMenus() {
		allMenus().forEach(function (m) {
			if (!m.hidden) { setMenuOpen(m, false); }
		});
	}

	// Opening a menu highlights the currently selected value.
	function markSelected(menu) {
		var field = menu.closest ? menu.closest('.topbar-field') : null;
		var origId = field ? field.dataset.for : null;
		var orig = origId ? document.getElementById(origId) : null;
		var selected = orig ? String(orig.value) : null;

		Array.prototype.forEach.call(menu.querySelectorAll('li button'), function (b) {
			b.setAttribute('aria-selected', String(b.dataset.value === selected));
		});
	}

	function chooseOption(optBtn) {
		var field = optBtn.closest ? optBtn.closest('.topbar-field') : null;
		var origId = field ? field.dataset.for : null;
		var orig = origId ? document.getElementById(origId) : null;

		closeAllMenus();

		if (orig && optBtn.dataset.value && orig.value !== optBtn.dataset.value) {
			orig.value = optBtn.dataset.value;

			// Let the app's (document-wide) `onchange-ajax-post-form` handler fire.
			orig.dispatchEvent(new Event('change', { bubbles: true }));

			// Give the AJAX response a moment to update the menu before re-cloning.
			setTimeout(hydrateContext, 350);
		}
	}

	// Capture-phase: wins before the app's own click handling.
	document.addEventListener('click', function (e) {
		var target = e.target;

		if (!target || !target.closest) { return; }

		// Clicking a control button toggles its dropdown.
		if (target.closest('.topbar-btn')) {
			var field = target.closest('.topbar-field');
			var menu = field ? field.querySelector('.topbar-menu') : null;

			if (menu) {
				if (menu.hidden) {
					closeAllMenus();
					markSelected(menu);
					setMenuOpen(menu, true);
				} else {
					setMenuOpen(menu, false);
				}
			}

			return;
		}

		// Choosing a value mirrors it to the hidden original select.
		if (target.closest('.topbar-menu li button')) {
			chooseOption(target.closest('.topbar-menu li button'));
			return;
		}

		// Any other click closes open dropdowns.
		closeAllMenus();
	}, true);

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') { closeAllMenus(); }
	});

	// ------------------------------------------------------------------
	// 2. Per-module collapse buttons
	//    Submenus are closed by default; only the selected module's menu
	//    is open. The button on the selected module toggles `.collapsed`
	//    (hide it); on any other module it toggles `.open` (preview its
	//    items in place without navigating).
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
			btn.innerHTML = '<span class="menu-collapse-icon" aria-hidden="true"></span>';

			btn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();

				var isActive = !!li.querySelector(':scope > a#selectedModuleLink');

				if (isActive) {
					var collapsed = li.classList.toggle('collapsed');
					li.classList.remove('open');
					btn.setAttribute('aria-expanded', String(!collapsed));
				} else {
					var open = li.classList.toggle('open');
					li.classList.remove('collapsed');
					btn.setAttribute('aria-expanded', String(open));
				}
			});

			li.appendChild(btn);
		});

		syncCollapseState();
	}

	// Keep aria-expanded / stale classes in sync with the displayed state:
	// the newly selected module must always drop down, so any leftover
	// `.collapsed`/`.open` from a previous visit is cleared.
	function syncCollapseState() {
		Array.prototype.forEach.call(document.querySelectorAll('.adminmenu .menu-module'), function (li) {
			var btn = li.querySelector(':scope > .menu-collapse');
			var sub = li.querySelector(':scope > .wp-submenu');

			if (!btn || !sub) { return; }

			var isActive = !!li.querySelector(':scope > a#selectedModuleLink');

			// The selected module must drop down.
			if (isActive) {
				li.classList.remove('open');
				li.classList.remove('collapsed');
			}

			var visible = getComputedStyle(sub).display !== 'none';

			btn.setAttribute('aria-expanded', String(visible));
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
			syncCollapseState();
		} finally {
			window.__ethioRehydrating = false;
		}
	}

	var observer = new MutationObserver(function () { rehydrate(); });

	// Watch the sidebar only (NOT #footerwrap: #topbar-context is rebuilt by
	// hydrateContext() itself, so observing it would loop forever). The id
	// attribute filter catches warehouse.js moving `#selectedModuleLink`.
	function watch(target) {
		if (target) {
			observer.observe(target, {
				childList: true,
				subtree: true,
				attributes: true,
				attributeFilter: ['id']
			});
		}
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