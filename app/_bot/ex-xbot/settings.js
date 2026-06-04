if (document.querySelector('#wab_link')) {
	if (localStorage['wab_key']) {
		document.querySelector('.wab_status.ko').style.display = 'none';
		document.querySelector('.wab_status.ok').style.display = 'block';
	}
}

if (document.querySelector('#wab_validate')) {
	document.querySelector('#wab_key').value = (typeof(localStorage['wab_key']) != 'undefined' ? localStorage['wab_key'] : '');

	document.querySelector('#wab_validate').addEventListener('click', function() {
		document.querySelector('.wab_validated.ok').style.display = 'none';
		document.querySelector('.wab_validated.ko').style.display = 'none';

		document.querySelector('#wab_validate').disabled = true;
		document.querySelector('#wab_validate').value = '...';

		var xmlhttp = new XMLHttpRequest();
		var token = document.querySelector('#wab_key').value;

		xmlhttp.open('POST', wab_url_v + '/api/validate?connected=0', false);
		xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
		xmlhttp.send('token=' + token);


		document.querySelector('#wab_validate').disabled = false;
		document.querySelector('#wab_validate').value = 'Validate';

		if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
			document.querySelector('.wab_validated.ok').style.display = 'block';
			localStorage['wab_key'] = document.querySelector('#wab_key').value;
		} else {
			localStorage['wab_key'] = '';
			document.querySelector('.wab_validated.ko').style.display = 'block';
		}
	});
}