# Flota de agentes — Snippet Expander (negocio multi-vertical)

Subagentes de Claude Code para sacar adelante el proyecto como negocio de nicho.
La estrategia: el moat no es un expansor genérico (categoría commodity) sino **packs
de plantillas curados por rubro** (legal, inmobiliaria, ecommerce, salud) para
WhatsApp Web en es-AR, monetizados con un plan **Pro vía Stripe**.

Invocá un agente con la herramienta Agent / Task indicando su `name`, o pidiéndomelo
en lenguaje natural ("usá el agente stripe-payments para…").

## Los 8 agentes

| Agente | Rol | Ataca la grieta… |
|---|---|---|
| `stripe-payments` | Checkout + webhook que pone `plan='pro'`, CTA de upgrade | #1 — no había forma de cobrar (la que mataba) |
| `pack-curator` | Packs profesionales por rubro (el diferenciador) | #2 — categoría commodity / sin moat |
| `laravel-backend` | API, modelos, límites, sync regex JS↔PHP, fix TOCTOU | #5/#6 — deuda backend |
| `whatsapp-web-engine` | Motor de expansión, mata `window.prompt`, robustez Lexical | #4 — motor frágil + UX |
| `extension-ux` | Popup, instalador de packs, onboarding, CTA Pro | #4 — UX y conversión |
| `qa-tester` | Tests reales (límite, ownership, webhook, regex) | #5 — cero tests en el camino que cobra |
| `store-compliance` | Permisos mínimos, privacidad, listing por vertical | #3 — `<all_urls>` = rechazo/desconfianza |
| `niche-growth` | Validación, pricing, distribución por rubro | #6 — premisa sin validar / distribución |

## Orden sugerido (de mayor leverage a menor)

1. **Validar primero** — `niche-growth`: confirmar que ≥1 vertical tiene dolor real
   y disposición a pagar antes de construir de más. Si ningún vertical valida, parar.
2. **Caja registradora** — `stripe-payments` + `laravel-backend`: sin pago real, todo
   lo demás es decorado. Esto va antes que pulir features.
3. **Moat** — `pack-curator`: los packs por rubro son la razón para elegir y pagar.
4. **Producto utilizable** — `whatsapp-web-engine` + `extension-ux`: motor sólido,
   formulario de variables inline, instalador de packs, onboarding.
5. **Calidad** — `qa-tester`: blindar el camino que cobra y la sync regex JS↔PHP.
6. **Distribución** — `store-compliance` + `niche-growth`: aprobación en la store y
   llegada al rubro.

## Invariantes que todos respetan (de CLAUDE.md)
- Espejado dual: toda mutación termina en `chrome.storage.local.templates` para que
  `content.js` funcione igual en modo local y remoto.
- Regex de variables `/\{([a-zA-Z0-9_]+)\}/` idéntico en `content.js`, `api.js` y
  `TemplateController.php`.
- Límite free = 5 en `config.js` y `User::FREE_TEMPLATE_LIMIT`.
- Forma de plantilla `{ id, trigger, content, variables[] }` (+ `category` para packs).
- Extensión sin build step; backend Laravel 13 + Sanctum; es-AR.
