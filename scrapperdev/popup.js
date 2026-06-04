// Logica del popup: CRUD de plantillas usando SnippetAPI (api.js).
(function () {
	'use strict';

	const authEl = document.getElementById('auth');
	const listEl = document.getElementById('list');
	const planEl = document.getElementById('plan');
	const form = document.getElementById('form');
	const editIdEl = document.getElementById('editId');
	const triggerEl = document.getElementById('trigger');
	const contentEl = document.getElementById('content');
	const saveBtn = document.getElementById('saveBtn');
	const cancelBtn = document.getElementById('cancelBtn');
	const msgEl = document.getElementById('msg');
	const upgradeEl = document.getElementById('upgrade');
	const mpEl = document.getElementById('upgrade-mp');
	const appEl = document.getElementById('app');

	// ===================== Pack browser + onboarding =====================

	const PACKS = [
		{ id: 'legal',        label: 'Juridico' },
		{ id: 'inmobiliaria', label: 'Inmobiliaria' },
		{ id: 'ecommerce',    label: 'Ecommerce' },
		{ id: 'salud',        label: 'Salud / turnos' }
	];

	// Cache pack data after first fetch so repeated clicks don't re-fetch.
	const packCache = {};

	async function fetchPack(id) {
		if (packCache[id]) return packCache[id];
		const url = chrome.runtime.getURL('packs/' + id + '.json');
		const res = await fetch(url);
		if (!res.ok) throw new Error('No se pudo cargar el pack ' + id + '.');
		const data = await res.json();
		packCache[id] = data;
		return data;
	}

	// Returns { installed, skipped, hitLimit } where:
	//   installed = number of new templates actually created
	//   skipped   = number of triggers that already existed
	//   hitLimit  = true if we stopped because of PLAN_LIMIT
	async function installPack(packId) {
		const [packItems, currentList] = await Promise.all([
			fetchPack(packId),
			SnippetAPI.cachedList()
		]);
		const existingTriggers = new Set(currentList.map((t) => t.trigger));

		let installed = 0;
		let skipped = 0;
		let hitLimit = false;

		for (const item of packItems) {
			if (existingTriggers.has(item.trigger)) { skipped++; continue; }
			try {
				await SnippetAPI.create({ trigger: item.trigger, content: item.content });
				existingTriggers.add(item.trigger); // keep set current for dup-check
				installed++;
			} catch (err) {
				if (err.code === 'PLAN_LIMIT') { hitLimit = true; break; }
				// Any other error (e.g. dup race): count as skipped, continue.
				skipped++;
			}
		}
		return { installed, skipped, hitLimit, total: packItems.length };
	}

	function buildInstallMessage(result) {
		const { installed, skipped, hitLimit, total } = result;
		const done = installed + skipped;
		if (hitLimit) {
			const remaining = total - done;
			return {
				text: 'Instalamos ' + installed + ' plantilla' + (installed !== 1 ? 's' : '') +
				      '. Quedan ' + remaining + ' del pack disponibles en Pro.',
				kind: 'warn',
				showUpgrade: true
			};
		}
		if (installed === 0 && skipped > 0) {
			return { text: 'Todas las plantillas del pack ya estaban instaladas.', kind: 'ok', showUpgrade: false };
		}
		const msg = 'Instaladas ' + installed + ' de ' + total +
		            (skipped > 0 ? ' (' + skipped + ' ya existian)' : '') + '.';
		return { text: msg, kind: 'ok', showUpgrade: false };
	}

	// ---- Pack browser panel ----

	const packsWrapEl   = document.getElementById('packs');
	const packsToggleEl = document.getElementById('packs-toggle');
	const packsBodyEl   = document.getElementById('packs-body');
	const packGridEl    = document.getElementById('pack-grid');
	const packStatusEl  = document.getElementById('pack-status');

	function setPackStatus(text, kind) {
		packStatusEl.textContent = text || '';
		packStatusEl.className = kind || '';
	}

	// Populate grid with buttons showing name + count (fetched lazily).
	async function buildPackGrid() {
		packGridEl.innerHTML = '';
		for (const pack of PACKS) {
			const btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'pack-btn';
			btn.dataset.pack = pack.id;

			const nameEl = document.createElement('span');
			nameEl.className = 'pack-name';
			nameEl.textContent = pack.label;

			const countEl = document.createElement('span');
			countEl.className = 'pack-count';
			countEl.textContent = 'Cargando...';

			btn.append(nameEl, countEl);
			packGridEl.appendChild(btn);

			// Fetch count in background (don't block paint).
			fetchPack(pack.id).then((items) => {
				countEl.textContent = items.length + ' plantillas';
			}).catch(() => {
				countEl.textContent = 'No disponible';
				btn.disabled = true;
			});

			btn.addEventListener('click', async () => {
				setPackStatus('Instalando...', '');
				btn.disabled = true;
				try {
					const result = await installPack(pack.id);
					const { text, kind, showUpgrade } = buildInstallMessage(result);
					setPackStatus(text, kind);
					if (showUpgrade) updateUpgradeCta(true);
					await render();
				} catch (err) {
					setPackStatus(err.message || 'Error al instalar el pack.', 'error');
				} finally {
					btn.disabled = false;
				}
			});
		}
	}

	let packGridBuilt = false;
	packsToggleEl.addEventListener('click', () => {
		const isOpen = packsBodyEl.classList.toggle('visible');
		packsToggleEl.classList.toggle('open', isOpen);
		if (isOpen && !packGridBuilt) {
			packGridBuilt = true;
			buildPackGrid();
		}
	});

	// ---- First-run onboarding ----

	const onboardingEl   = document.getElementById('onboarding');
	const onbStatusEl    = document.getElementById('onb-status');
	const onbSkipEl      = document.getElementById('onb-skip');
	const onbBtns        = onboardingEl.querySelectorAll('.onb-btn[data-pack]');

	function setOnbStatus(text, kind) {
		onbStatusEl.textContent = text || '';
		onbStatusEl.className = kind || '';
	}

	async function dismissOnboarding() {
		await chrome.storage.local.set({ onboarded: true });
		onboardingEl.style.display = 'none';
		packsWrapEl.style.display = 'block'; // el instalador permanente queda disponible
	}

	async function maybeShowOnboarding(templates) {
		const { onboarded } = await chrome.storage.local.get('onboarded');
		const showOnb = !onboarded && templates.length === 0;
		onboardingEl.style.display = showOnb ? 'block' : 'none';
		// Evita el "doble instalador": mientras se ve el cuadro de bienvenida (que ya
		// ofrece los packs) ocultamos el panel permanente. Reaparece al cerrar el
		// onboarding (instalaste un pack o elegiste crear las tuyas).
		packsWrapEl.style.display = showOnb ? 'none' : 'block';
	}

	onbSkipEl.addEventListener('click', dismissOnboarding);

	onbBtns.forEach((btn) => {
		btn.addEventListener('click', async () => {
			const packId = btn.dataset.pack;
			setOnbStatus('Instalando...', '');
			onbBtns.forEach((b) => { b.disabled = true; });
			onbSkipEl.disabled = true;
			try {
				const result = await installPack(packId);
				const { text, kind, showUpgrade } = buildInstallMessage(result);
				setOnbStatus(text, kind);
				if (showUpgrade) updateUpgradeCta(true);
				await dismissOnboarding();
				await render();
			} catch (err) {
				setOnbStatus(err.message || 'Error al instalar el pack.', 'error');
				onbBtns.forEach((b) => { b.disabled = false; });
				onbSkipEl.disabled = false;
			}
		});
	});

	function showMsg(text, kind) {
		msgEl.textContent = text || '';
		msgEl.className = kind || '';
	}

	function resetForm() {
		editIdEl.value = '';
		triggerEl.value = '';
		contentEl.value = '';
		saveBtn.textContent = 'Guardar plantilla';
		cancelBtn.style.display = 'none';
		showMsg('');
	}

	function startEdit(tpl) {
		editIdEl.value = tpl.id;
		triggerEl.value = tpl.trigger;
		contentEl.value = tpl.content;
		saveBtn.textContent = 'Actualizar';
		cancelBtn.style.display = 'block';
		triggerEl.focus();
	}

	// Dibuja la lista + el estado del plan. Sin tocar la red: recibe los datos ya
	// resueltos (de la cache o del backend).
	function paint(templates, plan) {
		const atLimit = plan === 'free' && templates.length >= SnippetAPI.freeLimit;
		planEl.textContent = plan === 'free'
			? 'free ' + templates.length + '/' + SnippetAPI.freeLimit
			: 'pro';
		planEl.className = 'plan' + (atLimit ? ' limit' : '');

		// Show/hide onboarding based on current template count.
		maybeShowOnboarding(templates);

		listEl.innerHTML = '';
		if (!templates.length) {
			const empty = document.createElement('li');
			empty.className = 'hint';
			empty.textContent = 'Todavia no tenes plantillas. Crea la primera abajo.';
			listEl.appendChild(empty);
		}

		for (const tpl of templates) {
			const li = document.createElement('li');
			li.className = 'tpl';

			const trig = document.createElement('div');
			trig.className = 'trigger';
			trig.textContent = tpl.trigger;

			const content = document.createElement('div');
			content.className = 'content';
			content.textContent = tpl.content;

			const actions = document.createElement('div');
			actions.className = 'actions';

			const editBtn = document.createElement('button');
			editBtn.type = 'button';
			editBtn.className = 'link';
			editBtn.textContent = 'Editar';
			editBtn.addEventListener('click', () => startEdit(tpl));

			const delBtn = document.createElement('button');
			delBtn.type = 'button';
			delBtn.className = 'link danger';
			delBtn.textContent = 'Borrar';
			delBtn.addEventListener('click', async () => {
				await SnippetAPI.remove(tpl.id);
				if (editIdEl.value === tpl.id) resetForm();
				render();
			});

			actions.append(editBtn, delBtn);
			li.append(trig, content, actions);
			listEl.appendChild(li);
		}

		// Bloquear alta nueva si se llego al limite (no bloquea ediciones).
		saveBtn.disabled = atLimit && !editIdEl.value;

		// CTA de upgrade (async: depende de si hay sesion).
		updateUpgradeCta(atLimit);
	}

	// Muestra "Pasate a Pro" solo al limite; sin sesion, invita a entrar primero.
	async function updateUpgradeCta(atLimit) {
		if (!atLimit) {
			upgradeEl.style.display = 'none';
			mpEl.style.display = 'none';
			return;
		}
		const authed = await SnippetAPI.isAuthenticated();
		upgradeEl.style.display = 'block';
		upgradeEl.textContent = authed
			? 'Pasate a Pro (USD, tarjeta)'
			: 'Inicia sesion para pasar a Pro';
		upgradeEl.disabled = !authed;
		// Mercado Pago (ARS) solo con sesion — se cobra al usuario logueado.
		mpEl.style.display = authed ? 'block' : 'none';
		mpEl.disabled = !authed;
	}

	// Dos pasos: 1) pinta al instante con la copia local (si hay), para no esperar
	// la red; 2) refresca contra el backend y vuelve a pintar con lo ultimo.
	async function render() {
		const cached = await SnippetAPI.cachedList();
		if (cached.length) paint(cached, await SnippetAPI.cachedPlan());

		try {
			const [templates, plan] = await Promise.all([SnippetAPI.list(), SnippetAPI.getPlan()]);
			paint(templates, plan);
		} catch (err) {
			if (err.code === 'UNAUTHENTICATED') { await renderAuth(); return; }
			if (!cached.length) showMsg(err.message || 'No se pudo cargar la lista.', 'error');
		}
	}

	form.addEventListener('submit', async (e) => {
		e.preventDefault();
		const trigger = triggerEl.value.trim();
		const content = contentEl.value;
		if (!trigger) return showMsg('El trigger es obligatorio.', 'error');
		if (!content) return showMsg('El contenido es obligatorio.', 'error');

		try {
			if (editIdEl.value) {
				await SnippetAPI.update(editIdEl.value, { trigger, content });
				showMsg('Plantilla actualizada.', 'ok');
			} else {
				await SnippetAPI.create({ trigger, content });
				showMsg('Plantilla creada.', 'ok');
			}
			resetForm();
			render();
		} catch (err) {
			showMsg(err.message, 'error');
		}
	});

	cancelBtn.addEventListener('click', resetForm);

	upgradeEl.addEventListener('click', async () => {
		try {
			upgradeEl.disabled = true;
			const url = await SnippetAPI.startCheckout();
			window.open(url, '_blank');
		} catch (err) {
			showMsg(err.message, 'error');
			upgradeEl.disabled = false;
		}
	});

	// ===================== Auth (login / registro / logout) =====================
	let authMode = 'login'; // 'login' | 'register'

	async function renderAuth() {
		authEl.innerHTML = '';
		const logged = await SnippetAPI.isAuthenticated();

		if (logged) {
			const box = document.createElement('div');
			box.className = 'account';
			const info = document.createElement('span');
			info.className = 'email';
			info.textContent = 'Sesion iniciada · sincronizado';
			const out = document.createElement('button');
			out.type = 'button';
			out.className = 'btn-ghost';
			out.textContent = 'Cerrar sesion';
			out.addEventListener('click', async () => {
				await SnippetAPI.logout();
				await refresh();
			});
			box.append(info, out);
			authEl.appendChild(box);
			return;
		}

		const form = document.createElement('form');
		form.className = 'auth-form';

		const nameInput = document.createElement('input');
		nameInput.type = 'text';
		nameInput.placeholder = 'Nombre';
		if (authMode === 'register') form.appendChild(nameInput);

		const emailInput = document.createElement('input');
		emailInput.type = 'email';
		emailInput.placeholder = 'Email';

		const passInput = document.createElement('input');
		passInput.type = 'password';
		passInput.placeholder = 'Contrasena';

		const submit = document.createElement('button');
		submit.type = 'submit';
		submit.className = 'btn-primary';
		submit.style.width = '100%';
		submit.textContent = authMode === 'login' ? 'Iniciar sesion' : 'Crear cuenta';

		const authMsg = document.createElement('p');
		authMsg.id = 'authMsg';

		const toggle = document.createElement('p');
		toggle.className = 'toggle';
		const toggleBtn = document.createElement('button');
		toggleBtn.type = 'button';
		toggleBtn.className = 'link';
		toggleBtn.textContent = authMode === 'login' ? 'Crear una cuenta' : 'Ya tengo cuenta';
		toggleBtn.addEventListener('click', () => {
			authMode = authMode === 'login' ? 'register' : 'login';
			renderAuth();
		});
		toggle.appendChild(toggleBtn);

		form.append(emailInput, passInput, submit, authMsg, toggle);

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			submit.disabled = true;
			authMsg.textContent = '';
			try {
				if (authMode === 'login') {
					await SnippetAPI.login(emailInput.value.trim(), passInput.value);
				} else {
					await SnippetAPI.register(nameInput.value.trim(), emailInput.value.trim(), passInput.value);
				}
				await refresh();
			} catch (err) {
				authMsg.textContent = err.message;
				submit.disabled = false;
			}
		});

		authEl.appendChild(form);
	}

	// Guard anti-reentrada: sin esto, el refresh inicial y el de 'focus' corren a la
	// vez y renderAuth() (limpia, await, agrega) duplicaba la caja de sesion.
	let refreshing = false;
	async function refresh() {
		if (refreshing) return;
		refreshing = true;
		try {
			await renderAuth();
			// Gate total: sin sesion no se muestra nada de la app, y se purga el cache
			// local de plantillas para que el motor (content.js) no expanda nada.
			const authed = await SnippetAPI.isAuthenticated();
			appEl.style.display = authed ? 'block' : 'none';
			if (authed) {
				await render();
			} else {
				planEl.textContent = '';
				planEl.className = 'plan';
				await chrome.storage.local.remove(['templates', 'plan']);
			}
		} finally {
			refreshing = false;
		}
	}

	refresh();

	// Al volver del Checkout de Stripe (otra pestana), refresca el plan.
	window.addEventListener('focus', () => { refresh(); });
})();
