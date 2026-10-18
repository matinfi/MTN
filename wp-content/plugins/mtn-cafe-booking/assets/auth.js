(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-mtn-reveal]').forEach(function (button) {
			var input = document.getElementById(button.getAttribute('aria-controls'));
			if (!input) return;
			button.addEventListener('click', function () {
				var show = input.type === 'password';
				input.type = show ? 'text' : 'password';
				button.textContent = show ? 'پنهان' : 'نمایش';
				button.setAttribute('aria-pressed', show ? 'true' : 'false');
			});
		});
		document.querySelectorAll('.mtn-auth-form').forEach(function (form) {
			form.addEventListener('input', function (event) {
				if (event.target && event.target.setCustomValidity) event.target.setCustomValidity('');
			});
			form.addEventListener('submit', function (event) {
				var pwd = form.querySelector('[name="pwd"], [name="new_pwd"]');
				var pwd2 = form.querySelector('[name="pwd2"], [name="new_pwd2"]');
				if (pwd && pwd2 && pwd.value !== pwd2.value) {
					event.preventDefault();
					pwd2.setCustomValidity('گذرواژه و تکرار آن یکسان نیستند.');
					pwd2.reportValidity();
					return;
				}
				var submit = form.querySelector('[type="submit"]');
				if (submit) window.setTimeout(function () { submit.disabled = true; }, 0);
			});
		});
	});
}());
