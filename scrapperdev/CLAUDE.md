# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

**Snippet Expander** — a Chrome MV3 extension (formerly a WhatsApp scraper, since reconverted) that
expands text triggers into templates inside any text field on any page. Type a trigger like
`/cotizar` and it expands in place. Templates can contain `{variables}`; `{fecha}` and `{hora}` are
filled automatically, any other `{name}` is prompted for at expansion time.

It ships with an optional Laravel + Sanctum backend (`backend/`) for authenticated cloud sync. UI
strings and code comments are in Spanish (es-AR).

## Architecture

Two independent halves that share one data shape:

```
{ id, trigger, content, variables: string[] }
```

### Extension (root dir, plain JS, no build step)

- **`content.js`** — runs on `<all_urls>`. Listens for `input` events (capture phase), finds the
  longest configured trigger that the text-before-caret ends with, and replaces it with the expanded
  content. Reads templates from `chrome.storage.local` (`templates` key) and reindexes on
  `storage.onChanged`. **Two insertion paths** that you must keep separate:
  - plain `<input>`/`<textarea>` → `setRangeText`.
  - `contenteditable` (e.g. WhatsApp Web's Lexical editor) → deferred `execCommand('delete')` loop
    then `execCommand('insertText')`. This dance exists because Lexical owns its own selection model
    and rejects nested `execCommand` inside the `input` event. See the long comments in
    `handleContentEditable` before touching it — there's a known edge case (trigger as entire box
    content leaves one char) that is a browser/editor limitation.
- **`api.js`** — the single data layer (`window.SnippetAPI`), used directly by the popup and
  indirectly by `content.js` (via the storage cache). **Dual backend, chosen by auth token presence:**
  - no `authToken` → `LocalStore` (chrome.storage only, freemium limit simulated client-side).
  - `authToken` present → `RemoteStore` (REST + Bearer token).
  - **After every mutation, both stores mirror the full list into `chrome.storage.local.templates`**
    so `content.js` behaves identically in either mode. Preserve this invariant.
- **`popup.js`** + **`settings_popup.html`** — the extension action popup: CRUD form, template list,
  plan badge, and login/register/logout. All styling is inline `<style>` in the HTML.
- **`config.js`** — `API_BASE_URL` and `FREE_PLAN_TEMPLATE_LIMIT` (5). Loaded first in both the
  content-script list and the popup.
- **`background.js`** — MV3 service worker. Only seeds default state (`templates: []`, `plan: 'free'`)
  on install. No messaging; all real work is in content/popup.
- Script load order matters: `config.js` → `api.js` → `content.js`/`popup.js` (set in
  `manifest.json` and the HTML).

### Backend (`backend/`, Laravel 13 + Sanctum, PHP 8.3)

REST API mirroring `SnippetAPI`. Token auth via `personal_access_tokens` (Sanctum), no SPA cookies.

- Routes: `backend/routes/api.php` — `/api/register`, `/api/login` are public; everything else is
  `auth:sanctum`. `POST /api/templates` additionally runs `EnforceTemplateLimit`.
- `TemplateController` — CRUD scoped to `$request->user()->templates()`; `extractVariables()` regex
  (`/\{([a-zA-Z0-9_]+)\}/`) **must stay in sync with the JS regex in `api.js`/`content.js`**.
  Ownership enforced by `authorizeOwner` (403) and a per-user unique `trigger` rule.
- `EnforceTemplateLimit` middleware → 403 with `code: PLAN_LIMIT` when a free user hits
  `User::FREE_TEMPLATE_LIMIT` (5). `User::templateLimit()` returns `null` for pro (unlimited).
- The extension's `RemoteStore._fetch` maps backend statuses to error codes the popup understands:
  401 → clears token + `UNAUTHENTICATED`; 403/422 → `PLAN_LIMIT`/`VALIDATION`.

**Cross-cutting invariant:** the free-plan limit (5), the variable-extraction regex, and the template
shape exist in *both* the JS and PHP sides. Changing one means changing the other.

### Billing (Stripe) — how a user becomes `pro`

Monetization lives behind a `BillingGateway` contract (`app/Contracts`), concretely `StripeGateway`
(`app/Services`), bound in `AppServiceProvider`. Env: `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`,
`STRIPE_PRICE_ID` (a recurring price). Routes: `POST /api/billing/checkout` and `/api/billing/portal`
(auth:sanctum) return a Stripe URL; `POST /api/stripe/webhook` is **public and signature-verified**
(in `api.php`, outside the auth group — API routes have no CSRF). **Only the webhook mutates `plan`:**
`checkout.session.completed` → `pro` (+ stores `stripe_customer_id`/`stripe_subscription_id`),
`customer.subscription.deleted` → `free`. The client can never set the plan. The popup shows a
"Pasate a Pro" CTA at the limit (`SnippetAPI.startCheckout()` → opens checkout in a new tab; the
popup refreshes the plan on window `focus`). Tests: `tests/Feature/BillingTest.php`.

## Commands

Extension: no build/lint/test. Load unpacked via `chrome://extensions` (Developer mode → "Load
unpacked" → select the `scrapperdev` root). Reload the extension there after editing JS.

Backend (run inside `backend/`):

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate              # SQLite by default (DB_CONNECTION=sqlite)
composer dev                     # serve + queue + pail logs + vite, concurrently
php artisan serve                # API only, http://localhost:8000
composer test                    # config:clear then php artisan test
php artisan test --filter=SomeTest   # single test
./vendor/bin/pint                # format (Laravel Pint)
```

`config.js` `API_BASE_URL` defaults to `http://localhost:8000`; prod is `https://api.clubpedidos.com`
(also in `manifest.json` `host_permissions`).
