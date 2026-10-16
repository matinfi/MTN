(function () {
	'use strict';
	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-mtn-otp-login]').forEach(function (root) {
			var form = root.querySelector('form');
			var phone = root.querySelector('[name="phone"]');
			var code = root.querySelector('[name="code"]');
			var codeField = root.querySelector('.mtn-otp-code-field');
			var requestButton = root.querySelector('[data-otp-request]');
			var verifyButton = root.querySelector('[data-otp-verify]');
			var status = root.querySelector('[role="status"]');
			var nonce = root.querySelector('[name="_wpnonce"]');
			function send(path, payload) {
				return fetch(MTNOTP.restUrl + path, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce ? nonce.value : '' },
					body: JSON.stringify(payload)
				}).then(function (response) {
					return response.json().then(function (data) {
						if (!response.ok) throw new Error(data.message || MTNOTP.error);
						return data;
					});
				});
			}
			requestButton.addEventListener('click', function () {
				status.textContent = MTNOTP.sending;
				requestButton.disabled = true;
				send('auth/otp/request', { phone: phone.value.trim() }).then(function (data) {
					status.textContent = data.message || MTNOTP.requested;
					codeField.hidden = false;
					verifyButton.hidden = false;
					code.focus();
				}).catch(function (error) {
					status.textContent = error.message || MTNOTP.error;
				}).finally(function () { requestButton.disabled = false; });
			});
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				if (codeField.hidden) {
					status.textContent = MTNOTP.requested;
					phone.focus();
					return;
				}
				status.textContent = MTNOTP.verifying;
				verifyButton.disabled = true;
				send('auth/otp/verify', { phone: phone.value.trim(), code: code.value.trim() }).then(function (data) {
					status.textContent = MTNOTP.success;
					window.location.assign(data.redirect || MTNOTP.redirectUrl);
				}).catch(function (error) {
					status.textContent = error.message || MTNOTP.error;
				}).finally(function () { verifyButton.disabled = false; });
			});
		});
	});
}());
