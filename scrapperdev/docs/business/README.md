# Snippet Expander — Business Strategy Index

## Files in this folder

| File | Contents |
|---|---|
| `README.md` | Lead vertical recommendation + falsification condition |
| `interview-guides.md` | Discovery interview guides for all 4 verticals |
| `pricing.md` | Pro pricing proposal + competitor benchmarks + A/B test design |
| `distribution.md` | Channel maps (no paid ads) for all 4 verticals |
| `30-day-plan.md` | Week-by-week validation sprint for the lead vertical |

---

## Lead Vertical Recommendation: LEGAL (Abogados / Estudios Jurídicos)

### The recommendation

Start with **abogados y estudios jurídicos** in Argentina (CABA + GBA first).

### Rationale — five concrete reasons

1. **Defined, high-frequency pain.** Lawyers on WhatsApp repeat the same 8–12 message types daily:
   initial-consultation reply, fee quote (`cotización de honorarios`), case-status update,
   document-request, hearing reminder, no-show follow-up. Each has legal boilerplate and must be
   professional in tone. Current workaround: copy-paste from a Notes document, which breaks on mobile.

2. **Already paying for adjacent tools — but mind the incumbent.** MetaJurídico (Argentine
   legaltech, 1.400+ estudios) sells a WhatsApp AI chatbot ("Justi") + case-tracking AND
   **legal-document templates** at ARS 7.999–18.399/mes (verified 2026-06-02, metajuridico.com/planes-precios).
   That proves the vertical pays for software — but note it ALSO does templates, so it's a *partial
   competitor*, not just adjacent proof. Our ARS 6.900 sits **just below its cheapest plan**, not
   "5–10× cheaper": we are not the budget version of the suite, we are a lightweight WhatsApp
   quick-replies tool. Position on simplicity/speed-to-value, not on price gap.

3. **Tight professional networks with identifiable entry points.** FACA (Federación Argentina de
   Colegios de Abogados, faca.org.ar), AABA (Asociación de Abogados de Buenos Aires), and the
   Colegio de Abogados MZA already use WhatsApp as an official member-communication channel. Somos
   Juristas (academia.soyjurista.com) runs active WhatsApp groups. These are known, reachable entry
   points — not a diffuse audience.

4. **Template pack differentiation is strongest here.** "20 plantillas para abogados laboralistas"
   is a concrete, Google-searchable product. Generic competitors (Text Blaze, TextExpander) have zero
   es-AR legal templates. This is the moat.

5. **Pro conversion signal is observable.** If a lawyer installs, creates > 5 templates, and hits
   the free-plan limit, the upgrade prompt fires automatically. High-intent professionals who are
   already copy-pasting will not tolerate losing their 6th template.

### What would FALSIFY this choice

This choice is **wrong** if, after 10 discovery interviews with Argentine lawyers:

- Fewer than 3 already pay (cash or time) for any workaround to reduce repetitive WhatsApp typing,
  AND
- Fewer than 4 report sending the same type of WhatsApp message more than 5 times per day.

Either of those thresholds failing means the pain is not acute enough to convert at any price. In
that case, pivot to **inmobiliaria** as the fallback (the "86% de consumidores usan WhatsApp con
empresas" stat below is UNVERIFIED — confirm before relying on it).

---

## Audit trail (citas verificadas — 2026-06-02)

Verificado con WebSearch/WebFetch por el orquestador:

- **Text Blaze Pro = US$3.49/mes** (US$2.99 anual). ✅ [blaze.today/plans](https://blaze.today/plans/)
- **TextExpander Individual = US$4.16/mes** (US$3.33 anual). ✅ [textexpander.com/pricing](https://textexpander.com/pricing)
- **Magical** pivotó a IA para salud (sin plan free, ~US$10/usuario/mes). ✅ [getmagical.com](https://www.getmagical.com/) — *nota: su pivote es específicamente a **salud**, así que ese vertical tiene un incumbente con fondeo entrando (producto distinto, automatización de operaciones, no expansión).*
- **dólar blue 2026-06-02 = ARS 1.435 (venta).** ✅ [indicadores.ar](https://indicadores.ar/dolar-blue-hoy)
- **MetaJurídico** es real: 1.400+ estudios, chatbot WhatsApp "Justi", **y genera documentos legales con plantillas**; ARS 7.999–18.399/mes. ✅ [metajuridico.com/planes-precios](https://metajuridico.com/planes-precios/) — **corrección material:** el kit estimó ARS 20–60k y "5–10× más barato"; el dato real es 8–18k, y nuestro precio queda *apenas por debajo* del plan más barato. Y MetaJurídico hace plantillas → es competidor parcial, no solo prueba de mercado.
- **Grupos de WhatsApp de abogados** en gruposwats.com existen (abogados / derecho-abogados / abogados-litigantes). ✅ [gruposwats.com/abogados.html](https://www.gruposwats.com/abogados.html) — *nota: son grupos públicos abiertos, no listas curadas de estudios; calidad/relevancia a verificar al entrar.*

**Sigue sin verificar:** el 86% de consumidores-WhatsApp (fallback inmobiliaria), el "8–12 mensajes/día" (hipótesis a confirmar EN las entrevistas, no antes), y cualquier organización citada en `distribution.md` que no esté en esta lista.
