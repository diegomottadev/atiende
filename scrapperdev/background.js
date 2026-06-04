// Service worker (MV3). El gestor de snippets v1 funciona local (chrome.storage),
// asi que el worker solo siembra estado por defecto al instalar.
chrome.runtime.onInstalled.addListener(async () => {
	const { templates, plan } = await chrome.storage.local.get(['templates', 'plan']);
	if (!Array.isArray(templates)) {
		await chrome.storage.local.set({ templates: [] });
	}
	if (!plan) {
		await chrome.storage.local.set({ plan: 'free' });
	}
});
