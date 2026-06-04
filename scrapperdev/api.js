// Capa de datos del gestor de plantillas.
//
// Interfaz unica usada por el popup (CRUD + auth) y, de forma indirecta, por el
// content script (que lee el cache 'templates' de chrome.storage.local).
//
// - Sin sesion  -> backend LOCAL (chrome.storage), limite freemium simulado.
// - Con sesion  -> backend REMOTO (Laravel + Sanctum), limite via middleware.
//
// Tras cada operacion se espeja la lista completa en chrome.storage.local
// ('templates') para que content.js funcione igual en ambos modos.
//
// Forma de una plantilla: { id, trigger, content, variables: string[] }

(function (global) {
	'use strict';

	function extractVariables(content) {
		const found = new Set();
		const re = /\{([a-zA-Z0-9_]+)\}/g;
		let m;
		while ((m = re.exec(content)) !== null) found.add(m[1]);
		return Array.from(found);
	}

	function normalizeTrigger(trigger) {
		return (trigger || '').trim();
	}

	async function getToken() {
		const { authToken } = await chrome.storage.local.get('authToken');
		return authToken || '';
	}

	async function setCache(templates) {
		await chrome.storage.local.set({ templates });
	}

	// ===================== Backend local (chrome.storage) =====================
	const LocalStore = {
		async list() {
			const { templates } = await chrome.storage.local.get('templates');
			return Array.isArray(templates) ? templates : [];
		},
		async getPlan() {
			const { plan } = await chrome.storage.local.get('plan');
			return plan || 'free';
		},
		async create(template) {
			const templates = await this.list();
			if ((await this.getPlan()) === 'free' && templates.length >= FREE_PLAN_TEMPLATE_LIMIT) {
				const err = new Error('Limite del plan gratuito alcanzado (' + FREE_PLAN_TEMPLATE_LIMIT + ' plantillas).');
				err.code = 'PLAN_LIMIT';
				throw err;
			}
			const trigger = normalizeTrigger(template.trigger);
			if (!trigger) throw new Error('El trigger es obligatorio.');
			if (templates.some((t) => t.trigger === trigger)) {
				throw new Error('Ya existe una plantilla con el trigger "' + trigger + '".');
			}
			const created = {
				id: (global.crypto && global.crypto.randomUUID) ? global.crypto.randomUUID() : String(Date.now()),
				trigger,
				content: template.content || '',
				variables: extractVariables(template.content || '')
			};
			templates.push(created);
			await setCache(templates);
			return created;
		},
		async update(id, patch) {
			const templates = await this.list();
			const idx = templates.findIndex((t) => String(t.id) === String(id));
			if (idx === -1) throw new Error('Plantilla no encontrada.');
			const next = { ...templates[idx] };
			if (patch.trigger !== undefined) {
				const trigger = normalizeTrigger(patch.trigger);
				if (!trigger) throw new Error('El trigger es obligatorio.');
				if (templates.some((t) => t.trigger === trigger && String(t.id) !== String(id))) {
					throw new Error('Ya existe una plantilla con el trigger "' + trigger + '".');
				}
				next.trigger = trigger;
			}
			if (patch.content !== undefined) {
				next.content = patch.content;
				next.variables = extractVariables(patch.content);
			}
			templates[idx] = next;
			await setCache(templates);
			return next;
		},
		async remove(id) {
			const templates = await this.list();
			await setCache(templates.filter((t) => String(t.id) !== String(id)));
		}
	};

	// ===================== Backend remoto (REST + Sanctum) ====================
	const RemoteStore = {
		async _fetch(path, options = {}) {
			const token = await getToken();
			const res = await fetch(API_BASE_URL + path, {
				...options,
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json',
					'Authorization': 'Bearer ' + token,
					...(options.headers || {})
				}
			});
			if (res.status === 401) {
				await chrome.storage.local.remove('authToken');
				const err = new Error('Sesion expirada, volve a iniciar sesion.');
				err.code = 'UNAUTHENTICATED';
				throw err;
			}
			if (res.status === 403 || res.status === 422) {
				const body = await res.json().catch(() => ({}));
				const err = new Error(body.message || 'Operacion rechazada.');
				err.code = body.code || (res.status === 403 ? 'PLAN_LIMIT' : 'VALIDATION');
				throw err;
			}
			if (!res.ok) throw new Error('Error de red (' + res.status + ').');
			return res.status === 204 ? null : res.json();
		},
		async list() {
			const templates = await this._fetch('/api/templates');
			await setCache(templates);
			return templates;
		},
		async getPlan() {
			const plan = (await this._fetch('/api/me')).plan || 'free';
			await chrome.storage.local.set({ plan }); // cache para pintado instantaneo
			return plan;
		},
		async create(template) {
			const created = await this._fetch('/api/templates', {
				method: 'POST',
				body: JSON.stringify({ trigger: normalizeTrigger(template.trigger), content: template.content || '' })
			});
			await this.list();
			return created;
		},
		async update(id, patch) {
			const updated = await this._fetch('/api/templates/' + id, {
				method: 'PUT',
				body: JSON.stringify({ trigger: normalizeTrigger(patch.trigger), content: patch.content })
			});
			await this.list();
			return updated;
		},
		async remove(id) {
			await this._fetch('/api/templates/' + id, { method: 'DELETE' });
			await this.list();
		}
	};

	async function backend() {
		return (await getToken()) ? RemoteStore : LocalStore;
	}

	// ===================== Auth =====================
	async function authFetch(path, payload) {
		const res = await fetch(API_BASE_URL + path, {
			method: 'POST',
			headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
			body: JSON.stringify(payload)
		});
		const body = await res.json().catch(() => ({}));
		if (!res.ok) {
			const msg = body.message
				|| (body.errors ? Object.values(body.errors)[0][0] : 'Error de autenticacion.');
			throw new Error(msg);
		}
		return body;
	}

	global.SnippetAPI = {
		extractVariables,
		freeLimit: FREE_PLAN_TEMPLATE_LIMIT,

		async isAuthenticated() { return !!(await getToken()); },

		// Copia local (chrome.storage), sin tocar la red. El popup la usa para
		// pintar al instante y despues refrescar contra el backend.
		async cachedList() {
			const { templates } = await chrome.storage.local.get('templates');
			return Array.isArray(templates) ? templates : [];
		},
		async cachedPlan() {
			const { plan } = await chrome.storage.local.get('plan');
			return plan || 'free';
		},

		async register(name, email, password) {
			const { token } = await authFetch('/api/register', { name, email, password });
			await chrome.storage.local.set({ authToken: token });
			await RemoteStore.list();
		},
		async login(email, password) {
			const { token } = await authFetch('/api/login', { email, password });
			await chrome.storage.local.set({ authToken: token });
			await RemoteStore.list();
		},
		async logout() {
			try { await RemoteStore._fetch('/api/logout', { method: 'POST' }); } catch (e) { /* ignore */ }
			// Gate total: borrar token + cache para que el expansor no funcione sin sesion.
			await chrome.storage.local.remove(['authToken', 'templates', 'plan']);
		},

		// Devuelve la URL de Stripe Checkout para pasar a Pro (USD, requiere sesion).
		async startCheckout() {
			const { url } = await RemoteStore._fetch('/api/billing/checkout', { method: 'POST' });
			return url;
		},
		// Devuelve el init_point de Mercado Pago para pagar en ARS (requiere sesion).
		async startMpCheckout() {
			const { url } = await RemoteStore._fetch('/api/billing/mp/checkout', { method: 'POST' });
			return url;
		},
		// Devuelve la URL del portal de facturacion para cancelar/gestionar.
		async openBillingPortal() {
			const { url } = await RemoteStore._fetch('/api/billing/portal', { method: 'POST' });
			return url;
		},

		async list() { return (await backend()).list(); },
		async getPlan() { return (await backend()).getPlan(); },
		async create(t) { return (await backend()).create(t); },
		async update(id, p) { return (await backend()).update(id, p); },
		async remove(id) { return (await backend()).remove(id); }
	};
})(typeof window !== 'undefined' ? window : self);
