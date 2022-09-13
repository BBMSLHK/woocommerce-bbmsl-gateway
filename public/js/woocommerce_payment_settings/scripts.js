window.addEventListener('DOMContentLoaded', function () {
	document.addEventListener('keyup', function (event) {
		event.preventDefault();
		if (event.isTrusted && event.key == 'Enter') {
			document.querySelectorAll('#save')[0].click();
		}
	});
	[...document.querySelectorAll('.bbmsl-settings .menu-item')].map(function (e) {
		e.addEventListener('click', function (event) {
			[...document.querySelectorAll('.bbmsl-settings .menu-item')].map(function (ee) { ee.classList.remove('active'); });
			event.target.classList.add('active');
			document.getElementById('mobile-menu').checked = false;
			let pane_id = event.target.getAttribute('data-pane');
			let target = document.getElementById(pane_id);
			let elems = [...document.querySelectorAll('.bbmsl-settings .pane')];
			elems.map(function (e) { e.style.display = 'none'; });
			target.style.display = 'block';
			[...document.querySelectorAll('.bbmsl-settings input[name="ui_last_pane_state"][type="hidden"]')].map(function (ee) { ee.value = pane_id; })
		});
	});
	[...document.querySelectorAll('[data-copy-source]')].map(function (e) {
		e.addEventListener('click', function (event) {
			let target_id = event.target.getAttribute('data-copy-source');
			let source = document.getElementById(target_id);
			if (source) {
				source.select();
				source.setSelectionRange(0, 99999); /* For mobile devices */
				document.execCommand('copy');
				navigator.clipboard.writeText(source.value.trim());
				alert('Copied.');
			}
		});
	});
	document.getElementById('bbmsl-gateway-select-all').addEventListener('click', function () {
		[...document.querySelectorAll('.payment-method input[type="checkbox"]')].map(function (e) { e.checked = true; });
	});
	document.getElementById('bbmsl-gateway-deselect-all').addEventListener('click', function () {
		[...document.querySelectorAll('.payment-method input[type="checkbox"]')].map(function (e) { e.checked = false; });
	});
	document.getElementById('toggle_site_checkbox').addEventListener('change', function () {
		[...document.querySelectorAll('.toggle_site')].map(function (e) { e.classList.remove('disabled'); });
		if (this.checked) {
			document.getElementById('toggle_site_live').classList.add('disabled');
		} else {
			document.getElementById('toggle_site_testing').classList.add('disabled');
		}
	})
});