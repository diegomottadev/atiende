function waboxapp(url, token) {
	this.token = token;
	this.ws_uri = url+'/'+this.token+'/ws';
	this.ws = false;
	this.ws_to = null;
	this.ws_alive_to = null;
	this.ws_queue = [];
	this.upload_uri = wab_url_v+'/api/upload';
	this.status = {};
	console.log("entre if this.token",this.token);
	if (this.token) {
		this.wsConnect(function(msg) {
			console.log("msg.data",msg.data);
			window.waboxapp.wsReceive(JSON.parse(msg.data));
		});
		window.addEventListener('message', this.onClientMsg);
		this.injectClient();
	}
}

waboxapp.prototype.injectClient = function() {
	try {
		(function() {
			var res = document.createElement('script');
			res.src = chrome.extension.getURL('client.js');
			document.body.appendChild(res);
		})();
	} catch (err) {
		window.waboxapp.catch(err);
	}
}

waboxapp.prototype.toClient = function(type, msg) {
	window.postMessage({ type: type, msg: msg }, '*');
}

waboxapp.prototype.catch = function(err) {
	window.waboxapp.onClientMsg({ type: '_wabs_err', data: { error: err.message, stack: err.stack } });
}

waboxapp.prototype.wsConnect = function(onmessageFn) {
	if (this.ws_to) { clearTimeout(this.ws_to); }
	if (this.ws) { this.ws.close(); }

	this.ws = new WebSocket(this.ws_uri + '?&token=' + this.token);
	this.ws.onopen = function(msg) {
		chrome.extension.sendMessage({method: '_wab_connection', connected: true});

		if (window.waboxapp.ws_alive_to) { clearInterval(window.waboxapp.ws_alive_to); }
		window.waboxapp.ws_alive_to = setInterval(window.waboxapp.wsAlive, 60000);
		window.waboxapp.wsAlive();
		window.waboxapp.wsProcessQueue();
	};
	this.ws.onclose = function(e) {
		chrome.extension.sendMessage({method: '_wab_connection', connected: false});

		if (!e.wasClean) {
			this.ws = false;
			this.ws_to = setTimeout(function() { window.waboxapp.wsConnect(onmessageFn); }, 500);
		}
	};
	this.ws.onmessage = onmessageFn;
}

waboxapp.prototype.wsReceive = function(m) {
	m = (typeof m == 'string' ? JSON.parse(m) : m);

	if (m.cmd && m.msg) {
		window.waboxapp.toClient('_wabc_', m);
	}
}

waboxapp.prototype.wsSend = function(m) {
	this.ws_queue.push(m);
	this.wsProcessQueue();
}

waboxapp.prototype.wsAlive = function() {
	window.waboxapp.wsSend({
		type: '_wabs_alive'
	});
}

waboxapp.prototype.wsProcessQueue = function() {
	while (this.ws.readyState == window.WebSocket.OPEN && this.ws_queue.length) {
		var m = this.ws_queue.pop();
		m.token = waboxapp.token;
		m.me = (waboxapp.status && waboxapp.status.conn && waboxapp.status.conn.me ? waboxapp.status.conn.me : (waboxapp.status && waboxapp.status.conn && waboxapp.status.conn.wid ? waboxapp.status.conn.wid._serialized : false));
		this.ws.send(JSON.stringify(m));
	}
}

waboxapp.prototype.onClientMsg = function(e) {
	if (e.data.type && e.data.type.indexOf('_wabs_') > -1) {
		if (e.data.type == '_wabs_status') {
			waboxapp.status = e.data.msg;
		}

		if (e.data.type == '_wabs_file') {
			window.waboxapp.upload(e.data.msg);
		} else {
			window.waboxapp.wsSend(e.data);
		}
	}
}

waboxapp.prototype.upload = function(m) {
	try {
		var fd = new FormData();
		fd.append('token', window.waboxapp.token);
		fd.append('fn', m.fn);
		fd.append('blob', m.blob);

		var xmlhttp = new XMLHttpRequest();
		xmlhttp.open('POST', window.waboxapp.upload_uri, true);
		xmlhttp.send(fd);
	} catch (err) {
		window.waboxapp.catch(err);
	}
}

chrome.extension.sendMessage({method: '_wab_settings'}, function (response) {
	if (response && response.wab_key) {
		window.waboxapp = new waboxapp(wab_url, response.wab_key);
	}
});