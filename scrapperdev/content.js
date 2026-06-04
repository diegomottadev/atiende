// Content script: detecta el campo de texto enfocado y expande triggers.
// Corre en <all_urls> pero queda inactivo hasta que el texto previo al cursor
// termina en un trigger configurado.

(function () {
	'use strict';

	let templates = [];
	let maxTriggerLen = 0;
	// Evita re-entradas: nuestra propia inserción dispara otro evento 'input'.
	let busy = false;

	function indexTemplates(list) {
		templates = Array.isArray(list) ? list : [];
		maxTriggerLen = templates.reduce((m, t) => Math.max(m, (t.trigger || '').length), 0);
	}

	// Carga inicial + refresco cuando el popup edita plantillas.
	chrome.storage.local.get('templates').then((r) => indexTemplates(r.templates));
	chrome.storage.onChanged.addListener((changes, area) => {
		if (area === 'local' && changes.templates) indexTemplates(changes.templates.newValue);
	});

	// --- expansion de variables ---
	function expandTemplate(content) {
		const now = new Date();
		let out = content
			.replace(/\{fecha\}/g, now.toLocaleDateString('es-AR'))
			.replace(/\{hora\}/g, now.toLocaleTimeString('es-AR', { hour: '2-digit', minute: '2-digit' }));

		const vars = new Set();
		let m;
		const re = /\{([a-zA-Z0-9_]+)\}/g;
		while ((m = re.exec(out)) !== null) vars.add(m[1]);

		for (const name of vars) {
			const value = window.prompt('Valor para {' + name + '}:', '');
			out = out.split('{' + name + '}').join(value === null ? '' : value);
		}
		return out;
	}

	// Busca el trigger mas largo con el que termina el texto previo al cursor.
	function matchTrigger(textBeforeCaret) {
		if (!templates.length) return null;
		const tail = textBeforeCaret.slice(-maxTriggerLen);
		let best = null;
		for (const t of templates) {
			const trig = t.trigger || '';
			if (trig && tail.endsWith(trig)) {
				if (!best || trig.length > best.trigger.length) best = t;
			}
		}
		return best;
	}

	function isEditableInput(el) {
		if (!el) return false;
		if (el.tagName === 'TEXTAREA') return true;
		if (el.tagName === 'INPUT') {
			const t = (el.type || 'text').toLowerCase();
			return ['text', 'search', 'url', 'tel', 'email', ''].includes(t);
		}
		return false;
	}

	// --- expansion en input / textarea ---
	function handleInputElement(el) {
		const caret = el.selectionStart;
		if (caret == null || caret !== el.selectionEnd) return; // hay seleccion, no expandir
		const before = el.value.slice(0, caret);
		const tpl = matchTrigger(before);
		if (!tpl) return;

		const expanded = expandTemplate(tpl.content);
		const start = caret - tpl.trigger.length;
		busy = true;
		el.setRangeText(expanded, start, caret, 'end');
		el.dispatchEvent(new Event('input', { bubbles: true }));
		busy = false;
	}

	// --- expansion en contenteditable ---
	// Editores como Lexical (WhatsApp Web) mantienen su PROPIO modelo de seleccion
	// e ignoran cambios manuales en la Selection del DOM. Operamos sobre el cursor
	// propio del editor: borramos el trigger hacia atras (delete) e insertamos.
	function handleContentEditable() {
		const sel = window.getSelection();
		if (!sel || !sel.isCollapsed || sel.rangeCount === 0) return;
		const node = sel.anchorNode;
		if (!node || node.nodeType !== Node.TEXT_NODE) return;

		const offset = sel.anchorOffset;
		const before = node.textContent.slice(0, offset);
		const tpl = matchTrigger(before);
		if (!tpl) return;

		const expanded = expandTemplate(tpl.content);
		const root = (node.parentElement && node.parentElement.closest('[contenteditable="true"]')) || document.activeElement;
		const rootText = (root && root.textContent) || '';
		busy = true;

		// Lexical agrupa execCommand('delete') sincronos, por eso borramos de a uno
		// por tarea (setTimeout) y medimos el largo hasta quitar el trigger completo.
		// Nota: si el trigger es TODO el contenido (caja vacia + trigger pegado al
		// inicio), WhatsApp no deja borrar el ultimo caracter para no vaciar la caja,
		// asi que queda el primer caracter del trigger. Con cualquier caracter antes
		// (un espacio o texto) la expansion queda limpia. Es un limite del editor.
		const need = tpl.trigger.length;
		const startLen = rootText.length;
		const maxAttempts = need + 6;
		let attempts = 0;
		const removedSoFar = () => startLen - ((root && root.textContent) ? root.textContent.length : 0);

		// Inserta el texto expandido. execCommand('insertText') con un bloque que tiene
		// saltos de linea (\n) DESORDENA los parrafos en Lexical (WhatsApp). Por eso
		// primero intentamos un evento 'paste' sintetico: Lexical tiene un handler de
		// paste que inserta texto plano multi-linea en el orden correcto. Si el editor
		// no lo maneja (no hace preventDefault), caemos a insertar token por token
		// (linea + salto) de a uno y diferido, que evita el agrupamiento que rompe.
		function doInsert() {
			const target = (root && root.isContentEditable) ? root : document.activeElement;
			try {
				const dt = new DataTransfer();
				dt.setData('text/plain', expanded);
				const ev = new ClipboardEvent('paste', { clipboardData: dt, bubbles: true, cancelable: true });
				const handled = !target.dispatchEvent(ev); // false si Lexical hizo preventDefault
				if (handled) { busy = false; return; }
			} catch (e) { /* sigue al fallback */ }

			const tokens = [];
			expanded.split('\n').forEach((line, i) => { if (i > 0) tokens.push('\n'); tokens.push(line); });
			let ti = 0;
			const ins = () => {
				if (ti < tokens.length) {
					const t = tokens[ti++];
					if (t) document.execCommand('insertText', false, t);
					setTimeout(ins, 0);
					return;
				}
				busy = false;
			};
			ins();
		}

		const step = () => {
			if (removedSoFar() < need && attempts < maxAttempts) {
				document.execCommand('delete');
				attempts++;
				setTimeout(step, 0);
				return;
			}
			doInsert();
		};
		step();
	}

	document.addEventListener('input', (e) => {
		if (e.isComposing || busy) return;
		const el = e.target;
		if (isEditableInput(el)) {
			handleInputElement(el);
		} else if (el && el.isContentEditable) {
			// Editores tipo Lexical (WhatsApp Web): execCommand anidado dentro del
			// propio evento 'input' es rechazado por el navegador (devuelve false).
			// Lo diferimos una tarea para que la inserción se aplique de verdad.
			setTimeout(handleContentEditable, 0);
		}
	}, true);
})();
