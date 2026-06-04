# 30-Day Validation Plan — Lead Vertical: Legal (Abogados)

## Primary Goal

**50 installs + 5 paying users (Stripe checkout complete) from the legal vertical by Day 30.**

Both conditions must be met. 50 installs without 5 paying = distribution works, monetization does
not. 5 paying without 50 installs = too small a sample to trust.

## Kill Metric (stop this vertical if triggered at any point)

> **< 1 paying user by end of Week 3 AND < 20 installs from clearly legal-vertical users.**

If this fires: do not invest Week 4 in legal. Immediately pivot to the interview guide for
inmobiliaria and restart.

---

## Week 1 — Discovery + First Contact (Days 1–7)

**Goal:** 10 discovery conversations completed; ≥ 3 pre-commit YES responses.

**Actions:**
1. Find 3–5 WhatsApp groups of Argentine lawyers (start: gruposwats.com/abogados-litigantes,
   gruposwats.com/derecho-abogados). Request access as a fellow professional or tech person
   helping the profession.
2. Connect with 20 lawyers on LinkedIn (CABA + GBA), personalized note: "Vi que ejercés en [área],
   tengo una pregunta de 15 minutos sobre cómo manejás la comunicación con clientes por WhatsApp."
3. Reach out directly to Academia Somos Juristas (academia.soyjurista.com) — pitch a free webinar
   or a guest post about "Cómo sistematizar respuestas de WhatsApp en tu estudio jurídico".
4. Run 10 interviews using the guide in `interview-guides.md`.

**Measure:**
- Number of conversations completed
- Number of pre-commit YES ("mandame el CBU")
- Qualitative: what language do they use to describe the pain?

**Decision gate:** If fewer than 3 pre-commit YES in 10 interviews → trigger the Kill Metric
evaluation early. Do not build a landing page yet if discovery fails here.

---

## Week 2 — Landing Page + First Installs (Days 8–14)

**Goal:** Landing page live; 20 installs from legal-vertical users.

**Actions:**
1. Publish a one-page landing page (can be a Notion page, Carrd.co, or simple HTML on GitHub
   Pages). Use the copy below.
2. Create a "Pack Abogados" — 20 pre-loaded templates in a JSON format that users can import
   (or that are pre-loaded in the extension for the legal template pack). Templates: cotización
   de honorarios, confirmación de consulta, actualización de expediente, pedido de documentación,
   notificación de audiencia, recordatorio de vencimiento, seguimiento sin respuesta,
   rechazo de consulta fuera de especialidad, confirmación de pago de honorarios, alta del caso.
3. Post in 2 WhatsApp groups and 1 LinkedIn with the "20 plantillas gratis" CTA.
4. Personal outreach to the 3+ pre-commit YES contacts from Week 1 — send them the extension
   link and ask for Stripe payment (ARS 6.900 or use the test at ARS 4.900).

**Measure:**
- Landing page unique visitors
- Extension installs (Chrome Web Store dashboard)
- Stripe checkouts initiated vs completed
- Which template triggers users activate first (from extension analytics if available)

---

## Week 3 — Convert + Iterate (Days 15–21)

**Goal:** 35 installs total; ≥ 3 paying users.

**Actions:**
1. Email / WhatsApp follow-up to everyone who installed and hit the 5-template free limit (the
   upgrade prompt fires automatically — but follow up personally with a voice note or message:
   "Vi que ya usás las 5 plantillas — acá te dejo el link para desbloquear las 20 del pack
   completo y todas las que quieras crear.").
2. Ask the 3+ paying users for a 1-sentence testimonial.
3. Post a before/after content piece on LinkedIn: "Antes tardaba 4 minutos en redactar el
   presupuesto para un nuevo cliente. Ahora tardo 8 segundos." (Use a lawyer's words if you have
   them from interviews.)
4. Contact FACA (faca.org.ar) or AABA (colabogados.org.ar) with a short pitch: "Desarrollé una
   herramienta para abogados que ya usan [N] colegas — ¿puedo compartirla en su boletín?"

**Kill metric check:** If < 1 paying user AND < 20 installs → activate kill metric. Stop here.

---

## Week 4 — Scale Signal (Days 22–30)

**Goal:** 50 installs; 5 paying users.

**Actions:**
1. Price A/B test: split the next 100 landing page visitors into Variant A (ARS 4.900) and
   Variant B (ARS 7.900) using two separate links. See `pricing.md` for full test design.
2. Publish 1 piece of SEO/AEO content targeting: "plantillas WhatsApp para abogados Argentina"
   (blog post or YouTube short).
3. If testimonials exist, add them to the landing page.
4. Review Chrome Web Store listing and update keywords (see ASO section below).

**Decision:** At Day 30, evaluate against the primary goal.
- ≥ 50 installs + ≥ 5 paying → legal vertical confirmed, expand distribution.
- ≥ 50 installs + < 5 paying → distribution works, pricing/value prop broken. Run price test for
  another 2 weeks before quitting.
- < 50 installs + ≥ 5 paying → monetization works in a tiny sample, but top of funnel is broken.
  Invest in distribution (FACA, AABA newsletter), not more content.
- < 50 installs + < 5 paying → kill the vertical. Pivot to inmobiliaria.

---

## Landing Page Copy (es-AR)

**Headline:**
> Respondé tus consultas de WhatsApp en 3 segundos, no en 3 minutos.

**Subheadline:**
> Escribís `/cotizar` y sale tu presupuesto completo. 20 plantillas profesionales para abogados,
> listas para usar.

**CTA button:** `Instalá gratis — 5 plantillas incluidas`

**Secondary CTA (below fold):** `Ver las 20 plantillas del Pack Abogados (Pro · ARS 6.900/mes)`

**Trust line:** Para abogados que usan WhatsApp como canal principal con clientes — Chrome,
funciona en WhatsApp Web y cualquier otra página.

---

## Chrome Web Store ASO Keywords (Lead Vertical: Legal)

Use these in the extension title, short description (132 chars), and long description.
Primary target: Argentine Spanish-speaking lawyers searching for productivity tools.

1. `plantillas WhatsApp abogados`
2. `expandir texto WhatsApp Chrome`
3. `respuestas automáticas WhatsApp`
4. `snippets texto abogados`
5. `cotización honorarios WhatsApp`
6. `plantillas texto jurídico`
7. `extensión WhatsApp productividad`
8. `atajo texto WhatsApp Argentina`
9. `respuestas rápidas WhatsApp estudio jurídico`
10. `text expander español abogados`
11. `plantillas mensajes profesionales`
12. `WhatsApp Web herramientas abogados`

**Title recommendation (max 45 chars):**
`Snippet Expander — Plantillas WhatsApp`

**Short description (132 chars):**
`Expandí atajos en texto en WhatsApp Web y cualquier campo. Pack de plantillas para abogados. Escribís /cotizar, sale tu presupuesto.`
