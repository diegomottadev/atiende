chrome.webRequest.onHeadersReceived.addListener(function (details) {
	try {
		for (i = 0; i < details.responseHeaders.length; i++) {
			if (details.responseHeaders[i].name.toUpperCase() == "CONTENT-SECURITY-POLICY") {
				details.responseHeaders[i].value = "";
			}
		}
	} catch (err) {
		void(0);
	}

	return {
		responseHeaders : details.responseHeaders
	};
}, {
	urls : ['<all_urls>'],
	types : ['main_frame', 'sub_frame', 'stylesheet', 'script', 'image', 'object', 'xmlhttprequest', 'other']
},
	['blocking', 'responseHeaders']
);

chrome.extension.onMessage.addListener(function (request, sender, sendResponse) {
	if (request.method == '_wab_settings') {
		wbxValidate();
		sendResponse(localStorage);
	
	} else if (request.method == '_wab_connection') {
		const Http = new XMLHttpRequest();
	    const url=wab_url_v +"/api/validate.php?empresa="+localStorage['wab_key']+"&connected="+request.connected ;
	    Http.open("GET", url);
	    Http.send();
	  
	    Http.onreadystatechange = (e) => {
	    //console.log(Http.responseText);
	    }
		chrome.browserAction.setIcon({path: (request.connected ? 'icon48.png' : 'icon48_off.png')});
	}
});

function wbxValidate() {
	var xmlhttp = new XMLHttpRequest();


	xmlhttp.open('POST',  wab_url_v + '/api/validate', false);
	xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	xmlhttp.send('token=' + localStorage['wab_key']);

    if (xmlhttp.readyState == 4 && xmlhttp.status != 200) {
    	localStorage['wab_key'] = '';
    }
}

wbxValidate();