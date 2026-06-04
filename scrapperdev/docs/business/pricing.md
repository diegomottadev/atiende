# Pricing Strategy — Snippet Expander Pro

## Competitor Benchmarks (all figures retrieved June 2, 2026)

| Product | Free Tier | Paid Individual | Source |
|---|---|---|---|
| **Text Blaze** | Yes (limited snippets) | **$3.49/month** (or $2.99/mo billed annually) | [blaze.today/plans](https://blaze.today/plans/) |
| **TextExpander** | No (free trial only) | **$4.16/month** billed monthly / **$3.33/mo** billed annually (Life Hacker plan) | [textexpander.com/pricing](https://textexpander.com/pricing) |
| **Magical** | Shifted to healthcare AI agents; individual text expansion ~**$10/user/month** (Core) | Not clearly published as of 2026 — company has pivoted away from general text expansion | [getmagical.com](https://www.getmagical.com/); [G2 review page](https://www.g2.com/products/magical/reviews) |

**Key observation:** The two main generic competitors (Text Blaze, TextExpander) price between
$3.33–$4.16/month individual in USD. Magical is exiting the space. There is a clear gap for a
vertical-specific, es-AR product.

---

## Exchange Rate Reference (June 2, 2026)

- USD 1 = ARS 1.435 (dólar blue / informal, the rate Argentine consumers actually use for
  software subscriptions paid abroad).
  Source: [indicadores.ar/dolar-blue-hoy](https://indicadores.ar/dolar-blue-hoy), June 2, 2026.

---

## Proposed Pro Price

**ARS 6.900 / mes** (≈ USD 4.80 at dólar blue)

Rationale:
- Slightly above Text Blaze Pro ($3.49 = ~ARS 5.000) and TextExpander ($4.16 = ~ARS 5.970), but
  justified by the vertical-specific value: pre-loaded professional template packs in es-AR that
  generic tools do not provide.
- MetaJurídico (the real es-AR incumbent: WhatsApp AI "Justi" + case-tracking + **legal-document
  templates**, 1.400+ estudios) charges **ARS 7.999–18.399/mes** for its full suite — verified at
  metajuridico.com/planes-precios (2026-06-02), NOT the 20–60k previously estimated. Our ARS 6.900
  therefore sits **just under its cheapest plan**, not far below it. Do not sell on "much cheaper
  than the suite"; sell on lightweight/quick-replies vs. a heavy CRM. MetaJurídico also doing
  templates means it is a partial competitor, not just proof people pay.
- Annual option: ARS 69.000 / año (≈ 2 months free), communicated as "ARS 5.750/mes".
- USD card option for nomads / international users: USD 5/month (Stripe).

---

## Two-Price A/B Test

### Design

Test two price points simultaneously using two landing-page variants. Split traffic 50/50 via a
short.link (e.g., Linktree A/B or two separate landing pages with UTM tracking).

| Variant | Monthly Price | Annual Price |
|---|---|---|
| **A (lower anchor)** | ARS 4.900/mes | ARS 49.000/año |
| **B (higher anchor)** | ARS 7.900/mes | ARS 79.000/año |

**How to run it:**
1. Build two static landing pages (can be identical except for the price + CTA button).
2. Distribute each link to separate but demographically equivalent cohorts (e.g., two different
   WhatsApp groups of lawyers, or alternate days on the same LinkedIn post).
3. Track: clicks on "Empezar Pro" button (intent) and actual Stripe checkouts (conversion).
4. Run for 4 weeks or until each variant has ≥ 50 clicks on the CTA, whichever comes first.

**Sample size:** ≥ 50 CTA clicks per variant before drawing conclusions. With 100 total CTA
clicks, a 2× difference in conversion rate (e.g., 20% vs 10%) is detectable without formal stats.

**Success threshold that decides the price:**
- If Variant B (ARS 7.900) achieves ≥ 60% of Variant A's conversion rate → go with B (revenue
  per user is higher; minor conversion loss is offset).
- If Variant B achieves < 60% of A's conversion rate → go with A (ARS 4.900/mes).

**What "kill the paid plan" looks like:**
- If both variants combined produce fewer than 3 actual Stripe checkouts after 4 weeks of active
  outreach (not passive traffic) in the lead vertical → the paid plan is not viable at any price
  tested. Do not raise price; instead, re-examine whether the free-plan limit (5 templates) is
  triggering the upgrade prompt at all, and whether Pro features are differentiated enough.

---

## Freemium Mechanics (current + recommended)

| Tier | Current | Recommended tweak |
|---|---|---|
| Free | 5 templates | Keep at 5; add a "starter pack" of 3 pre-loaded professional templates so users immediately feel the value and hit the limit faster |
| Pro | Unlimited templates | Add: team sharing (future), priority template packs per profession, CSV export |

The 5-template free limit is tight enough to trigger upgrades for active users but generous enough
for casual testers to experience the core loop. Do NOT raise it.
