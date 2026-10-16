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
 *  2. The sidebar modules are an accordion, INSIDE the sidebar: each module
 *     row is the only control — click it to drop its submenu down, click it
 *     again to close. Opening one row closes every other. The selected
 *     module stays open. There is NO separate icon/button on the row (the
 *     caret is pure CSS), so a row always has a single, predictable action.
 *  3. Own the hamburger: narrow screens toggle the drawer; on desktop it
 *     collapses the fixed sidebar off-canvas.
 *  4. Re-hydrate both after the sidebar is replaced by AJAX (sidefunc=update).
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
	// 2. Sidebar accordion
	//    Submenus drop down INSIDE the sidebar. Each module row is the only
	//    control for its submenu: clicking the row toggles it, clicking any
	//    other row closes this one and opens that one (strict accordion).
	//    The selected module (marked #selectedModuleLink by the app's own
	//    openMenu()) is open by default.
	//    There is deliberately no icon/button element on the row — a second
	//    control would have a second function (the bug this replaces). The
	//    caret is drawn in CSS on the row itself.
	// ------------------------------------------------------------------
	function sidebarModules() {
		return Array.prototype.slice.call(
			document.querySelectorAll( '.adminmenu > .menu-module' )
		);
	}

	function selectedModule() {
		var link = document.querySelector( '.adminmenu a#selectedModuleLink' );

		return link && link.closest ? link.closest( '.menu-module' ) : null;
	}

	function setModuleOpen( module, open ) {
		module.classList.toggle( 'open', !! open );

		var top = module.querySelector( ':scope > a.menu-top' );

		if ( top ) {
			top.setAttribute( 'aria-expanded', String( !! open ) );
		}
	}

	function syncAccordion() {
		var selected = selectedModule();

		sidebarModules().forEach( function ( module ) {
			setModuleOpen( module, module === selected );
		} );
	}

	// Capture phase: this runs BEFORE the app's own `a` click handler, so a
	// module row opens its submenu instead of navigating (submenu links do).
	document.addEventListener( 'click', function ( e ) {
		var target = e.target;

		if ( ! target || ! target.closest ) { return; }

		var top = target.closest( '.adminmenu > .menu-module > a.menu-top' );

		if ( ! top ) { return; }

		e.preventDefault();
		e.stopPropagation();

		var module = top.parentNode,
			willOpen = ! module.classList.contains( 'open' );

		// Strict accordion: close every module, then open only this one.
		sidebarModules().forEach( function ( m ) { setModuleOpen( m, false ); } );

		setModuleOpen( module, willOpen );
	}, true );

	// ------------------------------------------------------------------
	// 3. Re-hydrate whenever the sidebar is replaced (sidefunc=update)
	// ------------------------------------------------------------------
	function rehydrate() {
		if (window.__ethioRehydrating) { return; }
		window.__ethioRehydrating = true;
		try {
			hydrateContext();
			syncAccordion();
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
		var menuButton = document.getElementById('BottomButtonMenu');

		if (!menu) { return; }

		if (open) {
			menu.classList.remove('hide');	// legacy .hide is display:none
			menu.classList.add('slide');
		} else {
			menu.classList.remove('slide');
			document.body.style.overflow = '';
		}

		if (menuButton) {
			menuButton.setAttribute('aria-expanded', String(!!open));
		}
	}

	document.addEventListener('click', function (e) {
		var target = e.target;

		if (!target || !target.closest) { return; }

		// Hamburger: narrow screen → toggle the drawer; desktop → collapse
		// the fixed sidebar off-canvas (capture-phase wins over the app's
		// own expandMenu(), which does nothing useful under our layout).
		if (target.closest('#BottomButtonMenu')) {
			e.preventDefault();
			e.stopPropagation();

			if (window.innerWidth > MOBILE_BP) {
				var collapsed = document.body.classList.toggle('ethio-sidebar-collapsed');

				target.closest('#BottomButtonMenu').setAttribute('aria-expanded', String(!collapsed));
			} else {
				setDrawer(!drawerOpen());
				document.body.style.overflow = drawerOpen() ? 'hidden' : '';
			}

			return;
		}

		if (window.innerWidth > MOBILE_BP) { return; }

		// Tap the scrim or press Escape to dismiss the drawer.
		if (drawerOpen() && !target.closest('#menu')) {
			setDrawer(false);
			return;
		}

		// Close the drawer after navigating with a menu link.
		if (drawerOpen() && (target.closest('.adminmenu a') || target.closest('#menu-user'))) {
			setDrawer(false);
		}
	}, true);

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape' && drawerOpen()) {
			setDrawer(false);
		}
	});

	// ------------------------------------------------------------------
	// 5. The identity card is the Home button
	//    Side.php renders it as a real <a href="...Portal.php">, and the
	//    app's own click handler normally navigates it (AJAX). This makes
	//    that guaranteed: the very same AJAX navigation when the app's
	//    handler is available, a real page load otherwise. Registered
	//    after the drawer handler above so closing the drawer (narrow
	//    screens) still happens first.
	// ------------------------------------------------------------------
	document.addEventListener('click', function (e) {
		var target = e.target;

		if (!target || !target.closest) { return; }

		var card = target.closest('#menu-user');

		if (!card) { return; }

		e.preventDefault();
		e.stopImmediatePropagation();	// skip the app's link handler (we do it here)

		var handled = false;

		if (typeof ajaxLink === 'function') {
			// ajaxLink() returns false once it started the AJAX navigation.
			handled = ajaxLink(card) === false;
		}

		if (!handled) {
			// card.href is the resolved absolute URL.
			window.location.href = card.href;
		}
	}, true);

	// Initial pass.
	rehydrate();

	// ------------------------------------------------------------------
	// 6. Light / dark scheme toggle (top bar).
	//    The scheme lives in the ethio_scheme cookie, which Warehouse.php
	//    stamps onto <html data-theme> on the next document. Inline
	//    <script> is banned by the CSP, so this click just writes the
	//    cookie and flips the attribute in place: the current page restyles
	//    instantly, and every later full page load starts in that scheme.
	// ------------------------------------------------------------------
	var SCHEME_COOKIE = 'ethio_scheme';
	var SCHEME_COOKIE_DAYS = 365;

	function currentScheme() {
		return document.documentElement.getAttribute('data-theme') === 'dark'
			? 'dark'
			: 'light';
	}

	function writeSchemeCookie(scheme) {
		var expires = new Date();

		expires.setFullYear(expires.getFullYear() + SCHEME_COOKIE_DAYS);

		// path=/ so the choice survives navigation into modules/ and assets/.
		document.cookie = SCHEME_COOKIE + '=' + scheme
			+ '; expires=' + expires.toUTCString()
			+ '; path=/; SameSite=Lax';
	}

	function applyScheme(scheme) {
		document.documentElement.setAttribute('data-theme', scheme);

		var button = document.getElementById('BottomButtonScheme');

		if (!button) { return; }

		var toLight = scheme === 'dark';
		var label = toLight ? 'Light mode' : 'Dark mode';

		button.setAttribute('data-scheme', scheme);
		button.setAttribute('aria-pressed', String(toLight));
		button.setAttribute('aria-label', label);
		button.setAttribute('title', label);

		var text = button.querySelector('span');

		if (text) { text.textContent = label; }
	}

	document.addEventListener('click', function (e) {
		var target = e.target;

		if (!target || !target.closest) { return; }

		if (!target.closest('#BottomButtonScheme')) { return; }

		e.preventDefault();

		var scheme = currentScheme() === 'dark' ? 'light' : 'dark';

		writeSchemeCookie(scheme);
		applyScheme(scheme);
	});
})();
