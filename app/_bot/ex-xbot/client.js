function waboxcli() {
	this.dtm = Math.floor(Date.now() / 1000) - 300;
	this.queue = [];
	this.uidsCache = {};
	this.lastInMsgCache = {};

	window.addEventListener('message', this.onScriptMessage);

	setInterval(function() {
		if (!window.waboxstore && document.querySelector('#side')) {
			if (typeof webpackJsonp === 'function') {
				webpackJsonp([], {'parasite': (x, y, z) => window.waboxcli.getStore(z)}, ['parasite']);
			} else {
				webpackChunkwhatsapp_web_client.push([['parasite' + new Date().getTime()], {}, function (o, e, t) { let modules = []; for (let idx in o.m) { let module = o(idx); modules.push(module); } window.waboxcli.getStore(modules); }]);
			}

			if (window.waboxstore && window.waboxstore['Chat']) {
				window.waboxcli.onStatusChange();
				setInterval(window.waboxcli.onStatusChange, 90000);

				if (window.waboxstore.Conn && window.waboxstore.Stream) {
					window.waboxstore.Conn.listenTo(window.waboxstore.Conn, 'change:me change:ref', window.waboxcli.onStatusChange);
					window.waboxstore.Stream.listenTo(window.waboxstore.Stream, 'change:phoneAuthed change:info', window.waboxcli.onStatusChange);
				} else if (window.waboxstore.State) {
					window.waboxstore.State.Socket.listenTo(window.waboxstore.State.Socket, 'change:state change:stream', window.waboxcli.onStatusChange);
				}

				window.waboxstore.Chat.on('change:unreadCount', window.waboxcli.onChatMsgChanged);

			} else {
				window.location.reload();
			}
		}
	}, 1000);
}

waboxcli.prototype.getStore = function(modules) {
	let foundCount = 0;
	let neededObjects = [
		{ id: "Store", conditions: (module) => (module.default && module.default.Chat && module.default.Msg) ? module.default  : null },
		{ id: "Stream", conditions: (module) => (module.StreamInfo && module.StreamMode) ? module.Stream : null },
		{ id: "Wap", conditions: (module) => (module.createGroup) ? module : null },
		{ id: "MediaCollection", conditions: (module) => (module.default && module.default.prototype && module.default.prototype.processAttachments) ? module.default : null },
		{ id: "MediaProcess", conditions: (module) => (module.BLOB) ? module : null },
		{ id: "WapDelete", conditions: (module) => (module.sendConversationDelete && module.sendConversationDelete.length == 2) ? module : null },
		{ id: "Conn", conditions: (module) => (module.default && module.default.ref && module.default.refTTL) ? module.default : (module.Conn && module.Conn.ref && module.Conn.refTTL ? module.Conn : null) },
		{ id: "WapQuery", conditions: (module) => (module.default && module.default.queryExist) ? module.default : null },
		{ id: "CryptoLib", conditions: (module) => (module.decryptE2EMedia) ? module : null },
		{ id: "FindChat", conditions: (module) => (module && module.findChat) ? module : null },
		{ id: "SendTextMsgToChat", conditions: (module) => (module.sendTextMsgToChat) ? module.sendTextMsgToChat : null },
		{ id: "UserConstructor", conditions: (module) => (module.default && module.default.prototype && module.default.prototype.isServer && module.default.prototype.isUser) ? module.default : null },
		{ id: "DownloadManager", conditions: (module) => { return (module.downloadManager) ? module : null }},
		{ id: "getMeUser", conditions: (module) => { return (module.getMeUser) ? module.getMeUser : null }},
		{ id: "State", conditions: (module) => (module.STATE && module.STREAM) ? module : null }
	];

	window.waboxstore = {};

	for (let idx in modules) {
		if ((typeof modules[idx] === "object") && (modules[idx] !== null)) {
			neededObjects.forEach((needObj) => {
				if (!needObj.conditions || needObj.foundedModule) {
					return;
				}

				let neededModule = needObj.conditions(modules[idx]);

				if (neededModule !== null) {
					foundCount++;
					needObj.foundedModule = neededModule;
				}
			});

			if (foundCount == neededObjects.length) {
				break;
			}
		}
	}

	let neededStore = neededObjects.find((needObj) => needObj.id === "Store");
	window.waboxstore = neededStore.foundedModule ? neededStore.foundedModule : {};
	neededObjects.splice(neededObjects.indexOf(neededStore), 1);
	neededObjects.forEach((needObj) => {
		if (needObj.foundedModule) {
			window.waboxstore[needObj.id] = needObj.foundedModule;
		}
	});
}

waboxcli.prototype.onScriptMessage = function(e) {
	if (e.data.type && e.data.type.indexOf('_wabc_') > -1) {
		window.waboxcli.queue.push(e.data);
		window.waboxcli.processQueue();
	}
}

waboxcli.prototype.onStatusChange = function(e) {
	var data, stream = {}, conn = {};

	try {
		if (window.waboxstore.Stream) {
			stream = window.waboxcli.extract(typeof(window.waboxstore.Stream.all) != 'undefined' ? window.waboxstore.Stream.all : window.waboxstore.Stream.attributes);
		} else if (window.waboxstore.State) {
			stream = {
				info: waboxstore.State.Socket.stream,
				mode: waboxstore.State.Socket.state,
				phoneAuthed: (typeof(waboxstore.getMeUser) != 'undefined' ? waboxstore.getMeUser().user : null)
			};
		}

		if (window.waboxstore.Conn) {
			conn = window.waboxcli.extract(typeof(window.waboxstore.Conn.all) != 'undefined' ? window.waboxstore.Conn.all : window.waboxstore.Conn.attributes);
		}

		if (!conn.me) {
			conn.me = (typeof(waboxstore.getMeUser) != 'undefined' ? window.waboxstore.getMeUser()._serialized : null);
		}

		data = {
			stream: {
				info: stream.info,
				mode: stream.mode,
				phoneAuthed: stream.phoneAuthed
			},
			conn: conn
		};

		window.waboxcli.toScript('_wabs_status', data);

		if (typeof webpackJsonp === 'function') {
			webpackJsonp([], {'parasite': (x, y, z) => window.waboxcli.getStore(z)}, ['parasite']);
		} else {
			webpackChunkwhatsapp_web_client.push([['parasite' + new Date().getTime()], {}, function (o, e, t) { let modules = []; for (let idx in o.m) { let module = o(idx); modules.push(module); } window.waboxcli.getStore(modules); }]);
		}

	} catch (err) {
		window.waboxcli.catch(err);
	}
}

waboxcli.prototype.onChatMsgChanged = function(chat) {
	if (chat && chat.id) {
		var msg = chat.msgs.last();

		if (msg && msg.id) {
			if (!window.waboxcli.lastInMsgCache[chat.id] || window.waboxcli.lastInMsgCache[chat.id] != msg.id.id) {
				//chat.sendSeen(false);
				window.waboxcli.lastInMsgCache[chat.id] = msg.id.id;
				window.waboxcli.onMsg(msg);
			}
		}
	}
}

waboxcli.prototype.onMsg = function(model) {
	var data;

	setTimeout(function() {
		try {
			if (model.t > window.waboxcli.dtm && !(model.id.id in window.waboxcli.uidsCache)) {
				if (model.type == 'document') {
					model.isDoc = true;
				}

				if (model.type == 'audio' || model.type == 'ptt') {
					model.isMMS = true;
				}

				if ((model.isMedia || model.isDoc || model.isMMS) && model.mediaData && model.mediaData.mediaStage.toLowerCase() != 'resolved') {
					model.mediaData.parent = model;
					model.mediaData.on('change:mediaStage', window.waboxcli.onMediaData);
					(typeof(model.forceDownloadMedia) == 'function' ?  model.forceDownloadMedia() : model.downloadMedia(true));

				} else if ((model.isMedia || model.isDoc || model.isMMS) && model.mediaData.mediaBlob) {
					window.waboxcli.blobToBase64(model.mediaData.mediaBlob._blob, function(d) {
						if (d) {
							var rfn = window.waboxcli.randomFN(model.mimetype);
							window.waboxcli.toScript('_wabs_file', { fn: rfn, blob: d });

							data = {
								msg: window.waboxcli.extractMsg(model),
								fn: rfn
							};

							window.waboxcli.toScript('_wabs_msg', data);
						}
					});


				} else if (model.isMedia || model.isDoc || model.isMMS) {
					window.waboxcli.getMsgBlob(model, function(d) {
						if (d) {
							var rfn = window.waboxcli.randomFN(model.mimetype);
							window.waboxcli.toScript('_wabs_file', { fn: rfn, blob: d });

							data = {
								msg: window.waboxcli.extractMsg(model),
								fn: rfn
							};

							window.waboxcli.toScript('_wabs_msg', data);
						}
					});

				} else {
					data = {
						msg: window.waboxcli.extractMsg(model)
					};

					window.waboxcli.toScript('_wabs_msg', data);
				}
			}

		} catch (err) {
			window.waboxcli.catch(err);
		}
	}, 250);
}

waboxcli.prototype.onMediaData = function(mediaData) {
	if (mediaData.mediaStage.toLowerCase() == 'resolved') {
		window.waboxcli.onMsg(mediaData.parent);
	}
}

waboxcli.prototype.onAck = function(model) {
	try {
		var data = {
			id: model.id.id,
			ack: model.ack,
			muid: window.waboxcli.uidsCache[model.id.id]
		};

		window.waboxcli.toScript('_wabs_ack', data);

		if (model.ack > 2) {
			delete window.waboxcli.uidsCache[model.id.id];
		}

	} catch (err) {
		window.waboxcli.catch(err);
	}
}

waboxcli.prototype.toScript = function(type, msg) {
	window.postMessage({ type: type, msg: msg }, '*');
}

waboxcli.prototype.catch = function(err) {
	window.waboxcli.toScript('_wabs_err', { error: err.message, stack: err.stack });
}

waboxcli.prototype.processQueue = function() {
	try {
		var msg, chat, lastMsg;

		//if ((window.waboxstore.Conn && !window.waboxstore.Conn.blockStoreAdds) || (window.waboxstore.State && waboxstore.State.Socket.canSend)) {
		if (this.queue.length) {
			msg = this.queue.pop().msg;

			this.upsertChat(msg.msg.to, function(chat) {
				lastMsg = false;

				if (chat) {
					switch (msg.cmd) {
						case 'chat':
							if (msg.msg.body.text) {
								chat.sendMessage(msg.msg.body.text);
								lastMsg = chat.msgs.models[chat.msgs.models.length - 1];
							}
							break;

						case 'media':
							if (msg.msg.body.url) {
								chat.sendMessage(msg.msg.body.url, { linkPreview: {
										title: msg.msg.body.title,
										description: msg.msg.body.desc,
										canonicalUrl: msg.msg.body.url,
										matchedText: msg.msg.body.url,
										thumbnail: msg.msg.body.thumb
									}});
								lastMsg = chat.msgs.models[chat.msgs.models.length - 1];
							}
							break;
					}

					if (lastMsg) {
						window.waboxcli.uidsCache[lastMsg.id.id] = msg.msg.custom_uid;
						lastMsg.on('change:ack', window.waboxcli.onAck);
					}
				}
			});
		}
		//}

	} catch (err) {
		window.waboxcli.catch(err);
	}
}

waboxcli.prototype.upsertChat = function(cuid, cb) {
	var fcfn = (window.waboxstore.FindChat && window.waboxstore.FindChat.findChat ? window.waboxstore.FindChat.findChat : window.waboxstore.Chat.find);
	fcfn(this.cuidToJid(cuid)).then(function(chat) {
		chat.sendMessage = (chat.sendMessage ? chat.sendMessage : function(e) { return window.waboxstore.SendTextMsgToChat(this, ...arguments); });
		cb(chat);
	}, function(fail) {
		const iu = new window.waboxstore.UserConstructor(window.waboxcli.cuidToJid(cuid), { intentionallyUsePrivateConstructor: true });
		fcfn(iu).then(function(chat) {
			chat.sendMessage = (chat.sendMessage ? chat.sendMessage : function(e) { return window.waboxstore.SendTextMsgToChat(this, ...arguments); });
			cb(chat);
		}, function(fail) {
			window.waboxstore.WapQuery.queryExist(window.waboxcli.cuidToJid(cuid)).then(function(contact) {
				if (contact && contact.jid) {
					chat = window.waboxstore.Chat.gadd(contact.jid);
					chat.sendMessage = (chat.sendMessage ? chat.sendMessage : function(e) { return window.waboxstore.SendTextMsgToChat(this, ...arguments); });
					cb(chat);
				}
			}, function(fail) {
			});
		});
	});
}

waboxcli.prototype.cuidToJid = function(cuid) {
	return (cuid.indexOf('@') < 0 ? cuid + '@c.us' : cuid);
}

waboxcli.prototype.getMsgBlob = function(msg, cb) {
	if (window.waboxstore.DownloadManager) {
		var directPath = msg.directPath;
		var encFilehash = msg.encFilehash;
		var filehash = msg.filehash;
		var mediaKey = msg.mediaKey;
		var type = msg.type;

		const dd = { directPath, encFilehash, filehash, mediaKey,  type: type, signal: (new AbortController).signal };
		const ff = (window.waboxstore.DownloadManager.downloadAndDecrypt ? window.waboxstore.DownloadManager.downloadAndDecrypt : window.waboxstore.DownloadManager.downloadManager.downloadAndDecrypt);

		ff(dd).then(function(ab) {
			window.waboxcli.arrayBufferToBase64(ab, function(ab64) {
				cb('data:' + msg.mimetype + ';base64,' + ab64);
			});
		});

	} else if (window.waboxstore.CryptoLib) {
		let xhr = new XMLHttpRequest();

		xhr.onload = function () {
			if (xhr.readyState == 4) {
				if (xhr.status == 200) {
					window.waboxstore.CryptoLib.decryptE2EMedia(msg.type, xhr.response, msg.mediaKey, msg.mimetype).then(function(a) {
						window.waboxcli.blobToBase64(a._blob, cb);
					});

				} else {
					cb(false);
				}
			} else {
				cb(false);
			}
		};

		xhr.open('GET', (msg.clientUrl ? msg.clientUrl : msg.deprecatedMms3Url), true);
		xhr.responseType = 'arraybuffer';
		xhr.send(null);
	}
}

waboxcli.prototype.arrayBufferToBase64 = function(ab, cb) {
	try {
		let binary = '';
		const bytes = new Uint8Array(ab);
		const len = bytes.byteLength;

		for (let i = 0; i < len; i++) {
			binary += String.fromCharCode(bytes[i]);
		}

		cb(window.btoa(binary));

	} catch (err) {
		window.waboxcli.catch(err);
	}
}

waboxcli.prototype.blobToBase64 = function(blob, cb) {
	try {
		var reader = new window.FileReader();
		reader.readAsDataURL(blob);
		reader.onloadend = function() {
			cb(reader.result);
		};

	} catch (err) {
		window.waboxcli.catch(err);
	}
}

waboxcli.prototype.randomFN = function(mimetype) {
	var chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	var ext = mimetype.split('/')[1].substr(0, 3);
	var ret = '';

	if (ext == 'jpe') { ext = 'jpg'; }

	for (var i = 32; i > 0; --i) {
		ret += chars[Math.floor(Math.random() * chars.length)];
	}

	return ret + '.' + ext;
}

waboxcli.prototype.extract = function(o) {
	var ret = {};

	for (var k in o) {
		if (typeof(o[k]) != 'object' && typeof(o[k]) != 'function') {
			ret[k] = o[k];
		} else if (k == 'chat' || k == 'senderObj') {
			ret[k] = window.waboxcli.extract(typeof(o[k].all) != 'undefined' ? o[k].all : o[k].attributes);
		} else if (k == 'id' || k == 'me' || k == 'wid') {
			ret[k] = window.waboxcli.extract(o[k]);
		}
	}

	return ret;
}

waboxcli.prototype.extractMsg = function(o) {
	var ret = window.waboxcli.extract(typeof(o.all) != 'undefined' ? o.all : o.attributes);

	if (typeof(ret.chat) == 'undefined' && typeof(o.chat) != 'undefined') {
		ret['chat'] = window.waboxcli.extract(typeof(o.chat.all) != 'undefined' ? o.chat.all : o.chat.attributes);
	}

	return ret;
}

window.waboxcli = new waboxcli();