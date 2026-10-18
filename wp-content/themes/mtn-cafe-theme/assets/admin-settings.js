(function () {
	'use strict';
	var form = document.querySelector('[data-mtn-theme-settings]');
	if (!form) return;
	var tabs = Array.prototype.slice.call(form.querySelectorAll('[data-mtn-tab]'));
	var panels = Array.prototype.slice.call(form.querySelectorAll('[data-mtn-panel]'));
	function show(id, focus) {
		tabs.forEach(function (tab) {
			var active = tab.getAttribute('data-mtn-tab') === id;
			tab.classList.toggle('is-active', active);
			tab.setAttribute('aria-selected', active ? 'true' : 'false');
			tab.tabIndex = active ? 0 : -1;
			if (active && focus) tab.focus();
		});
		panels.forEach(function (panel) {
			panel.hidden = panel.getAttribute('data-mtn-panel') !== id;
		});
		try { window.sessionStorage.setItem('mtn-theme-tab', id); } catch (e) {}
	}
	tabs.forEach(function (tab, index) {
		tab.addEventListener('click', function () { show(tab.getAttribute('data-mtn-tab')); });
		tab.addEventListener('keydown', function (event) {
			var next = null;
			if (event.key === 'ArrowDown' || event.key === 'ArrowLeft') next = tabs[(index + 1) % tabs.length];
			if (event.key === 'ArrowUp' || event.key === 'ArrowRight') next = tabs[(index - 1 + tabs.length) % tabs.length];
			if (next) { event.preventDefault(); show(next.getAttribute('data-mtn-tab'), true); }
		});
	});
	var saved = null;
	try { saved = window.sessionStorage.getItem('mtn-theme-tab'); } catch (e) {}
	if (saved && form.querySelector('[data-mtn-panel="' + saved + '"]')) show(saved);
	else if (tabs.length) show(tabs[0].getAttribute('data-mtn-tab'));

	// Reveal a panel that contains an invalid field when saving fails client-side validation.
	form.addEventListener('invalid', function (event) {
		var panel = event.target.closest && event.target.closest('[data-mtn-panel]');
		if (panel && panel.hidden) show(panel.getAttribute('data-mtn-panel'));
	}, true);

	// Live colour preview.
	var preview = form.querySelector('[data-mtn-preview]');
	var map = {
		mtn_cafe_theme_accent: '--p-accent',
		mtn_cafe_theme_bg: '--p-bg',
		mtn_cafe_theme_surface: '--p-surface',
		mtn_cafe_theme_text: '--p-text',
		mtn_cafe_theme_muted: '--p-muted'
	};
	function sync(input) {
		var code = input.parentNode.querySelector('[data-mtn-color-code]');
		if (code) code.textContent = input.value;
		if (preview && map[input.name]) preview.style.setProperty(map[input.name], input.value);
	}
	Array.prototype.forEach.call(form.querySelectorAll('[data-mtn-color]'), function (input) {
		sync(input);
		input.addEventListener('input', function () { sync(input); });
	});
	var radius = form.querySelector('[name="mtn_cafe_theme_radius"]');
	if (radius && preview) {
		var applyRadius = function () { preview.style.setProperty('--p-radius', (parseInt(radius.value, 10) || 18) + 'px'); };
		applyRadius();
		radius.addEventListener('input', applyRadius);
	}
}());
