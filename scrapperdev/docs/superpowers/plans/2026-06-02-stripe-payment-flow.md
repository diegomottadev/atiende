# Stripe Payment Flow Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the "pro" plan actually purchasable — a real Stripe Checkout + webhook that flips `users.plan` to `'pro'`, plus an upgrade CTA in the extension popup — so the freemium model collects money instead of being decorative.

**Architecture:** The killer flaw (devil's-advocate crack #1) is that nothing ever writes `plan = 'pro'`. We add a thin `BillingGateway` abstraction (concrete `StripeGateway`) so the controllers stay testable offline. The backend exposes `POST /api/billing/checkout` and `POST /api/billing/portal` (Sanctum-auth) and a **public, signature-verified** `POST /api/stripe/webhook`. Only the webhook mutates `plan` — the client is never trusted. The extension popup gains an "Pasate a Pro" CTA that opens the Checkout URL and refreshes the plan on return.

**Tech Stack:** Laravel 13 (PHP 8.3) + Sanctum, `stripe/stripe-php`, PHPUnit (SQLite in-memory), plain-JS MV3 extension (no build step), es-AR copy.

---

## Context the implementer needs (read first)

- **Backend lives at `backend/`** (Laravel 13 + Sanctum). Routes in `backend/routes/api.php`; the `/api` prefix is applied automatically, so a route written as `/stripe/webhook` is reachable at `/api/stripe/webhook`.
- **API routes carry no CSRF** (only the `web` middleware group does), so the public webhook route in `api.php` needs no CSRF exemption.
- **`User` model** (`backend/app/Models/User.php`): uses the PHP attribute `#[Fillable([...])]`, has `plan` defaulting to `'free'`, `isPro()`, `templateLimit()` (returns `null` for pro, else `FREE_TEMPLATE_LIMIT = 5`). Do not change the limit logic — only `plan` flips.
- **Limit enforcement** is `EnforceTemplateLimit` middleware on `POST /api/templates`. Lifting the limit for pro is automatic once `plan = 'pro'` (because `templateLimit()` returns `null`).
- **Extension data layer** is `window.SnippetAPI` in `api.js`. `RemoteStore._fetch(path, opts)` adds the `Bearer` token and parses JSON. `getPlan()` calls `/api/me`. The popup (`popup.js` + `settings_popup.html`, inline styles) reads plan via `SnippetAPI.getPlan()` and disables "save" at the limit.
- **Cross-cutting invariant:** do not touch the variable-extraction regex or the `5` limit here — those belong to a separate plan (crack #5).
- **Stripe test card:** `4242 4242 4242 4242`, any future expiry, any CVC.

**Out of scope (separate plans):** crack #2 (positioning/packs), #3 (permissions), #4 (expansion engine UX), #5 (test suite for the existing API), #6 (TOCTOU fix). This plan only builds the payment flow — it produces working, testable software on its own.

---

## File Structure

**Create (backend):**
- `backend/app/Contracts/BillingGateway.php` — interface: `createCheckoutSession(User): string`, `createPortalSession(User): string`, `constructWebhookEvent(string, ?string): \Stripe\Event`.
- `backend/app/Services/StripeGateway.php` — concrete implementation wrapping `\Stripe\StripeClient` and `\Stripe\Webhook`.
- `backend/app/Http/Controllers/BillingController.php` — `checkout()`, `portal()`.
- `backend/app/Http/Controllers/StripeWebhookController.php` — `handle()`.
- `backend/database/migrations/2026_06_02_000000_add_stripe_columns_to_users.php` — `stripe_customer_id`, `stripe_subscription_id`.
- `backend/tests/Feature/BillingTest.php` — checkout/portal/webhook/limit-lift tests.

**Modify (backend):**
- `backend/config/services.php` — add `stripe` config block.
- `backend/app/Providers/AppServiceProvider.php` — bind `BillingGateway` → `StripeGateway`.
- `backend/app/Models/User.php` — add stripe columns to `#[Fillable(...)]`.
- `backend/routes/api.php` — add billing + webhook routes.
- `backend/routes/web.php` — add `/billing/success` and `/billing/cancel` landing pages.
- `backend/.env.example` — document `STRIPE_*` vars.

**Modify (extension):**
- `api.js` — add `startCheckout()` and `openBillingPortal()` to `SnippetAPI`.
- `popup.js` — render the upgrade CTA at the limit; refresh plan on window focus.
- `settings_popup.html` — markup + inline styles for the CTA.

---

## Task 0: Initialize git + branch

**Files:**
- Create: `.gitignore` (repo root = `scrapperdev/`)

- [ ] **Step 1: Initialize the repo (it is not one yet)**

Run:
```bash
cd "C:/Users/ACER/Downloads/whatsbus2021-main/scrapperdev"
git init
```
Expected: "Initialized empty Git repository".

- [ ] **Step 2: Create a root `.gitignore`**

Create `.gitignore`:
```gitignore
# PHP / Laravel backend
backend/vendor/
backend/node_modules/
backend/.env
backend/storage/*.key
backend/database/*.sqlite

# Node / tooling
node_modules/

# OS / editor
.DS_Store
Thumbs.db
```
(Note: `backend/.gitignore` already exists and applies to its subtree — this root file only adds repo-wide ignores.)

- [ ] **Step 3: Create the working branch**

Run:
```bash
git checkout -b feat/stripe-payment-flow
```
Expected: "Switched to a new branch 'feat/stripe-payment-flow'".

- [ ] **Step 4: Initial commit**

```bash
git add .gitignore
git commit -m "chore: init git repo and gitignore for stripe payment work"
```

---

## Task 1: Install Stripe SDK and configuration

**Files:**
- Modify: `backend/config/services.php`
- Modify: `backend/.env.example`

- [ ] **Step 1: Install the Stripe PHP SDK**

Run:
```bash
cd "C:/Users/ACER/Downloads/whatsbus2021-main/scrapperdev/backend"
composer require stripe/stripe-php
```
Expected: composer adds `stripe/stripe-php` to `require` and updates `composer.lock`.

- [ ] **Step 2: Add the `stripe` config block**

In `backend/config/services.php`, add this entry inside the returned array (alongside the other service entries):
```php
'stripe' => [
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'price_id' => env('STRIPE_PRICE_ID'),
],
```

- [ ] **Step 3: Document env vars**

Append to `backend/.env.example`:
```dotenv

# Stripe (plan Pro)
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
STRIPE_PRICE_ID=
```

- [ ] **Step 4: Verify config loads**

Run:
```bash
php artisan config:clear && php artisan tinker --execute="echo array_key_exists('stripe', config('services')) ? 'ok' : 'missing';"
```
Expected: `ok`

- [ ] **Step 5: Commit**

```bash
git add backend/composer.json backend/composer.lock backend/config/services.php backend/.env.example
git commit -m "chore: add stripe-php sdk and stripe config"
```

---

## Task 2: Add Stripe columns to users

**Files:**
- Create: `backend/database/migrations/2026_06_02_000000_add_stripe_columns_to_users.php`
- Modify: `backend/app/Models/User.php`
- Test: `backend/tests/Feature/BillingTest.php`

- [ ] **Step 1: Write the failing test (columns are mass-assignable + persist)**

Create `backend/tests/Feature/BillingTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_persists_stripe_ids(): void
    {
        $user = User::factory()->create();
        $user->update([
            'stripe_customer_id' => 'cus_123',
            'stripe_subscription_id' => 'sub_123',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'stripe_customer_id' => 'cus_123',
            'stripe_subscription_id' => 'sub_123',
        ]);
    }
}
```

- [ ] **Step 2: Run it to confirm it fails**

Run:
```bash
php artisan config:clear && php artisan test --filter=test_user_persists_stripe_ids
```
Expected: FAIL — column `stripe_customer_id` does not exist (or mass-assignment ignored).

- [ ] **Step 3: Create the migration**

Create `backend/database/migrations/2026_06_02_000000_add_stripe_columns_to_users.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('stripe_subscription_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['stripe_customer_id', 'stripe_subscription_id']);
        });
    }
};
```

- [ ] **Step 4: Make the columns fillable**

In `backend/app/Models/User.php`, change the `#[Fillable(...)]` attribute from:
```php
#[Fillable(['name', 'email', 'password', 'plan'])]
```
to:
```php
#[Fillable(['name', 'email', 'password', 'plan', 'stripe_customer_id', 'stripe_subscription_id'])]
```

- [ ] **Step 5: Run the test to confirm it passes**

Run:
```bash
php artisan test --filter=test_user_persists_stripe_ids
```
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add backend/database/migrations backend/app/Models/User.php backend/tests/Feature/BillingTest.php
git commit -m "feat(billing): add stripe id columns to users"
```

---

## Task 3: BillingGateway contract + StripeGateway + container binding

**Files:**
- Create: `backend/app/Contracts/BillingGateway.php`
- Create: `backend/app/Services/StripeGateway.php`
- Modify: `backend/app/Providers/AppServiceProvider.php`

> No standalone test here — this interface is exercised by the controller tests in Tasks 4–6. Keep it thin.

- [ ] **Step 1: Create the interface**

Create `backend/app/Contracts/BillingGateway.php`:
```php
<?php

namespace App\Contracts;

use App\Models\User;
use Stripe\Event;

interface BillingGateway
{
    /** Returns a hosted Checkout URL for the pro subscription. */
    public function createCheckoutSession(User $user): string;

    /** Returns a Billing Portal URL so the user can cancel/manage. */
    public function createPortalSession(User $user): string;

    /** Verifies the Stripe signature and returns the parsed event. Throws on bad signature. */
    public function constructWebhookEvent(string $payload, ?string $signature): Event;
}
```

- [ ] **Step 2: Create the Stripe implementation**

Create `backend/app/Services/StripeGateway.php`:
```php
<?php

namespace App\Services;

use App\Contracts\BillingGateway;
use App\Models\User;
use Stripe\Event;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeGateway implements BillingGateway
{
    public function __construct(
        private ?string $secret,
        private ?string $webhookSecret,
        private string $appUrl,
        private ?string $priceId,
    ) {}

    public function createCheckoutSession(User $user): string
    {
        $session = $this->client()->checkout->sessions->create([
            'mode' => 'subscription',
            'line_items' => [['price' => $this->priceId, 'quantity' => 1]],
            'success_url' => rtrim($this->appUrl, '/').'/billing/success',
            'cancel_url' => rtrim($this->appUrl, '/').'/billing/cancel',
            'client_reference_id' => (string) $user->id,
            'customer_email' => $user->email,
        ]);

        return $session->url;
    }

    public function createPortalSession(User $user): string
    {
        $session = $this->client()->billingPortal->sessions->create([
            'customer' => $user->stripe_customer_id,
            'return_url' => rtrim($this->appUrl, '/').'/billing/success',
        ]);

        return $session->url;
    }

    public function constructWebhookEvent(string $payload, ?string $signature): Event
    {
        return Webhook::constructEvent($payload, $signature ?? '', $this->webhookSecret);
    }

    private function client(): StripeClient
    {
        return new StripeClient($this->secret);
    }
}
```

- [ ] **Step 3: Bind the gateway in the service provider**

In `backend/app/Providers/AppServiceProvider.php`, inside `register()`, add:
```php
$this->app->bind(\App\Contracts\BillingGateway::class, function () {
    return new \App\Services\StripeGateway(
        config('services.stripe.secret'),
        config('services.stripe.webhook_secret'),
        config('app.url'),
        config('services.stripe.price_id'),
    );
});
```

- [ ] **Step 4: Verify it resolves**

Run:
```bash
php artisan config:clear && php artisan tinker --execute="echo get_class(app(App\Contracts\BillingGateway::class));"
```
Expected: `App\Services\StripeGateway`

- [ ] **Step 5: Commit**

```bash
git add backend/app/Contracts backend/app/Services backend/app/Providers/AppServiceProvider.php
git commit -m "feat(billing): add BillingGateway contract and StripeGateway binding"
```

---

## Task 4: Webhook endpoint (the only thing that flips plan)

**Files:**
- Create: `backend/app/Http/Controllers/StripeWebhookController.php`
- Modify: `backend/routes/api.php`
- Test: `backend/tests/Feature/BillingTest.php`

> Webhook tests use the **real** `StripeGateway` (signature verification is offline — pure HMAC, no network). We sign payloads with the test webhook secret.

- [ ] **Step 1: Write the failing tests**

Add to `backend/tests/Feature/BillingTest.php` — a helper plus three tests:
```php
    /** Builds [rawJson, stripeSignatureHeader] signed like Stripe does. */
    private function signedWebhook(array $payload): array
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);
        $json = json_encode($payload);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$json}", 'whsec_test');

        return [$json, "t={$timestamp},v1={$signature}"];
    }

    public function test_webhook_rejects_bad_signature(): void
    {
        config(['services.stripe.webhook_secret' => 'whsec_test']);
        $user = User::factory()->create(['plan' => 'free']);

        $response = $this->call(
            'POST',
            '/api/stripe/webhook',
            [], [], [],
            ['HTTP_STRIPE_SIGNATURE' => 't=1,v1=deadbeef', 'CONTENT_TYPE' => 'application/json'],
            json_encode(['type' => 'checkout.session.completed'])
        );

        $response->assertStatus(400);
        $this->assertSame('free', $user->fresh()->plan);
    }

    public function test_checkout_completed_sets_user_to_pro(): void
    {
        $user = User::factory()->create(['plan' => 'free']);
        [$json, $sig] = $this->signedWebhook([
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'client_reference_id' => (string) $user->id,
                'customer' => 'cus_abc',
                'subscription' => 'sub_abc',
            ]],
        ]);

        $response = $this->call('POST', '/api/stripe/webhook', [], [], [],
            ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $json);

        $response->assertOk();
        $fresh = $user->fresh();
        $this->assertSame('pro', $fresh->plan);
        $this->assertSame('cus_abc', $fresh->stripe_customer_id);
        $this->assertSame('sub_abc', $fresh->stripe_subscription_id);
    }

    public function test_subscription_deleted_downgrades_user(): void
    {
        $user = User::factory()->create([
            'plan' => 'pro',
            'stripe_subscription_id' => 'sub_xyz',
            'stripe_customer_id' => 'cus_xyz',
        ]);
        [$json, $sig] = $this->signedWebhook([
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => ['id' => 'sub_xyz', 'customer' => 'cus_xyz']],
        ]);

        $response = $this->call('POST', '/api/stripe/webhook', [], [], [],
            ['HTTP_STRIPE_SIGNATURE' => $sig, 'CONTENT_TYPE' => 'application/json'], $json);

        $response->assertOk();
        $this->assertSame('free', $user->fresh()->plan);
    }
```

- [ ] **Step 2: Run the tests to confirm they fail**

Run:
```bash
php artisan test --filter=BillingTest
```
Expected: the three webhook tests FAIL (route `/api/stripe/webhook` returns 404).

- [ ] **Step 3: Create the webhook controller**

Create `backend/app/Http/Controllers/StripeWebhookController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Contracts\BillingGateway;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function handle(Request $request, BillingGateway $gateway): JsonResponse
    {
        try {
            $event = $gateway->constructWebhookEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => 'invalid signature'], 400);
        }

        // Setting plan is naturally idempotent, so duplicate deliveries are safe.
        $object = $event->data->object;

        switch ($event->type) {
            case 'checkout.session.completed':
                $user = User::find($object->client_reference_id ?? null);
                if ($user) {
                    $user->update([
                        'plan' => 'pro',
                        'stripe_customer_id' => $object->customer ?? null,
                        'stripe_subscription_id' => $object->subscription ?? null,
                    ]);
                }
                break;

            case 'customer.subscription.deleted':
                $user = User::where('stripe_subscription_id', $object->id ?? null)
                    ->orWhere('stripe_customer_id', $object->customer ?? null)
                    ->first();
                if ($user) {
                    $user->update(['plan' => 'free']);
                }
                break;
        }

        return response()->json(['received' => true]);
    }
}
```

- [ ] **Step 4: Register the public webhook route**

In `backend/routes/api.php`, add the import at the top:
```php
use App\Http\Controllers\StripeWebhookController;
```
and add this OUTSIDE the `auth:sanctum` group (next to `/register` and `/login`):
```php
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
```

- [ ] **Step 5: Run the tests to confirm they pass**

Run:
```bash
php artisan test --filter=BillingTest
```
Expected: PASS (all webhook tests green).

- [ ] **Step 6: Commit**

```bash
git add backend/app/Http/Controllers/StripeWebhookController.php backend/routes/api.php backend/tests/Feature/BillingTest.php
git commit -m "feat(billing): stripe webhook flips plan to pro/free with signature verification"
```

---

## Task 5: Checkout + portal endpoints (auth-gated)

**Files:**
- Create: `backend/app/Http/Controllers/BillingController.php`
- Modify: `backend/routes/api.php`
- Modify: `backend/routes/web.php`
- Test: `backend/tests/Feature/BillingTest.php`

> These hit Stripe over the network in production, so the tests **mock** `BillingGateway`.

- [ ] **Step 1: Write the failing tests**

Add to `backend/tests/Feature/BillingTest.php` (add `use Laravel\Sanctum\Sanctum;` and `use App\Contracts\BillingGateway;` at the top of the file if not present):
```php
    public function test_checkout_requires_auth(): void
    {
        $this->postJson('/api/billing/checkout')->assertUnauthorized();
    }

    public function test_checkout_returns_url_for_authed_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(BillingGateway::class, function ($mock) {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn('https://checkout.stripe.test/abc');
        });

        $this->postJson('/api/billing/checkout')
            ->assertOk()
            ->assertJson(['url' => 'https://checkout.stripe.test/abc']);
    }

    public function test_portal_returns_url_for_authed_user(): void
    {
        $user = User::factory()->create(['stripe_customer_id' => 'cus_1']);
        Sanctum::actingAs($user);

        $this->mock(BillingGateway::class, function ($mock) {
            $mock->shouldReceive('createPortalSession')
                ->once()
                ->andReturn('https://portal.stripe.test/abc');
        });

        $this->postJson('/api/billing/portal')
            ->assertOk()
            ->assertJson(['url' => 'https://portal.stripe.test/abc']);
    }
```

- [ ] **Step 2: Run the tests to confirm they fail**

Run:
```bash
php artisan test --filter=BillingTest
```
Expected: the three new tests FAIL (routes 404; guest test may already pass — confirm the two authed ones fail).

- [ ] **Step 3: Create the billing controller**

Create `backend/app/Http/Controllers/BillingController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Contracts\BillingGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function checkout(Request $request, BillingGateway $gateway): JsonResponse
    {
        return response()->json([
            'url' => $gateway->createCheckoutSession($request->user()),
        ]);
    }

    public function portal(Request $request, BillingGateway $gateway): JsonResponse
    {
        return response()->json([
            'url' => $gateway->createPortalSession($request->user()),
        ]);
    }
}
```

- [ ] **Step 4: Register the auth-gated routes**

In `backend/routes/api.php`, add the import:
```php
use App\Http\Controllers\BillingController;
```
and inside the existing `Route::middleware('auth:sanctum')->group(...)` block add:
```php
Route::post('/billing/checkout', [BillingController::class, 'checkout']);
Route::post('/billing/portal', [BillingController::class, 'portal']);
```

- [ ] **Step 5: Add the success/cancel landing pages**

In `backend/routes/web.php`, add:
```php
Route::get('/billing/success', fn () => response(
    '<!doctype html><meta charset="utf-8"><title>Pago confirmado</title>'
    .'<body style="font-family:system-ui;text-align:center;padding:3rem">'
    .'<h1>Pago confirmado</h1><p>Ya tenés el plan Pro. Volvé a la extensión y cerrá esta pestaña.</p>'
)->header('Content-Type', 'text/html'));

Route::get('/billing/cancel', fn () => response(
    '<!doctype html><meta charset="utf-8"><title>Pago cancelado</title>'
    .'<body style="font-family:system-ui;text-align:center;padding:3rem">'
    .'<h1>Pago cancelado</h1><p>No se hizo ningún cobro. Podés volver a la extensión.</p>'
)->header('Content-Type', 'text/html'));
```

- [ ] **Step 6: Run the tests to confirm they pass**

Run:
```bash
php artisan test --filter=BillingTest
```
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add backend/app/Http/Controllers/BillingController.php backend/routes/api.php backend/routes/web.php backend/tests/Feature/BillingTest.php
git commit -m "feat(billing): checkout and portal endpoints + return pages"
```

---

## Task 6: Integration test — pro lifts the template limit

**Files:**
- Test: `backend/tests/Feature/BillingTest.php`

> This proves the business outcome end-to-end: a free user is capped at 5, and after upgrade (plan=pro) the 6th template saves. No production code change expected — if it fails, the bug is real.

- [ ] **Step 1: Write the tests**

Add to `backend/tests/Feature/BillingTest.php`:
```php
    public function test_free_user_blocked_at_sixth_template(): void
    {
        $user = User::factory()->create(['plan' => 'free']);
        Sanctum::actingAs($user);

        for ($i = 1; $i <= 5; $i++) {
            $this->postJson('/api/templates', ['trigger' => "/t{$i}", 'content' => "x{$i}"])
                ->assertCreated();
        }

        $this->postJson('/api/templates', ['trigger' => '/t6', 'content' => 'x6'])
            ->assertStatus(403)
            ->assertJson(['code' => 'PLAN_LIMIT']);
    }

    public function test_pro_user_can_exceed_free_limit(): void
    {
        $user = User::factory()->create(['plan' => 'pro']);
        Sanctum::actingAs($user);

        for ($i = 1; $i <= 6; $i++) {
            $this->postJson('/api/templates', ['trigger' => "/p{$i}", 'content' => "y{$i}"])
                ->assertCreated();
        }

        $this->assertSame(6, $user->templates()->count());
    }
```

- [ ] **Step 2: Run the tests**

Run:
```bash
php artisan test --filter=BillingTest
```
Expected: PASS. If `test_free_user_blocked_at_sixth_template` is flaky under concurrency, that is the known TOCTOU bug (crack #6) — note it but do NOT fix it here (separate plan); these tests are sequential and should pass.

- [ ] **Step 3: Run the full backend suite**

Run:
```bash
composer test
```
Expected: all green. (The legacy `ExampleTest` still exists and passes; removing it belongs to the test-suite plan, crack #5.)

- [ ] **Step 4: Commit**

```bash
git add backend/tests/Feature/BillingTest.php
git commit -m "test(billing): prove pro plan lifts the template limit"
```

---

## Task 7: Extension data layer — checkout + portal calls

**Files:**
- Modify: `api.js`

> No JS test harness exists in this repo (per CLAUDE.md), so verification is manual. Keep the diff minimal and consistent with the existing `RemoteStore._fetch` style.

- [ ] **Step 1: Add `startCheckout` and `openBillingPortal` to `SnippetAPI`**

In `api.js`, inside the `global.SnippetAPI = { ... }` object (after `logout()` and before `list()`), add:
```javascript
		async startCheckout() {
			const { url } = await RemoteStore._fetch('/api/billing/checkout', { method: 'POST' });
			return url;
		},
		async openBillingPortal() {
			const { url } = await RemoteStore._fetch('/api/billing/portal', { method: 'POST' });
			return url;
		},
```

- [ ] **Step 2: Sanity-check syntax**

Run:
```bash
node --check api.js
```
Expected: no output (valid JS).

- [ ] **Step 3: Commit**

```bash
git add api.js
git commit -m "feat(billing): SnippetAPI.startCheckout and openBillingPortal"
```

---

## Task 8: Extension popup — upgrade CTA

**Files:**
- Modify: `settings_popup.html`
- Modify: `popup.js`

- [ ] **Step 1: Add CTA markup + styles**

In `settings_popup.html`, add a button right after the element that shows the plan badge (the element with `id="plan"`). If the badge sits in a header container, place this adjacent to it:
```html
<button id="upgrade" type="button" class="btn-primary" style="display:none; width:100%; margin:8px 0;">Pasate a Pro — plantillas ilimitadas</button>
```
If `.btn-primary` is not already styled in the file's `<style>` block, add:
```css
.btn-primary { background:#1a7f37; color:#fff; border:0; border-radius:6px; padding:8px 12px; cursor:pointer; font-size:13px; }
.btn-primary:hover { background:#176c2f; }
```

- [ ] **Step 2: Wire the CTA in `popup.js`**

In `popup.js`, near the other `getElementById` calls at the top, add:
```javascript
	const upgradeEl = document.getElementById('upgrade');
```
At the end of the `render()` function (after `saveBtn.disabled = ...`), add:
```javascript
		const authed = await SnippetAPI.isAuthenticated();
		if (atLimit && authed) {
			upgradeEl.style.display = 'block';
			upgradeEl.textContent = 'Pasate a Pro — plantillas ilimitadas';
			upgradeEl.disabled = false;
		} else if (atLimit && !authed) {
			upgradeEl.style.display = 'block';
			upgradeEl.textContent = 'Iniciá sesión para pasar a Pro';
			upgradeEl.disabled = true;
		} else {
			upgradeEl.style.display = 'none';
		}
```
And register the click handler once, near the other listeners (e.g. after `cancelBtn.addEventListener(...)`):
```javascript
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
```

- [ ] **Step 3: Refresh plan when the user returns from Checkout**

In `popup.js`, after the initial `refresh();` call at the bottom, add:
```javascript
	window.addEventListener('focus', () => { refresh(); });
```

- [ ] **Step 4: Sanity-check syntax**

Run:
```bash
node --check popup.js
```
Expected: no output.

- [ ] **Step 5: Commit**

```bash
git add settings_popup.html popup.js
git commit -m "feat(billing): popup upgrade CTA wired to stripe checkout"
```

---

## Task 9: End-to-end manual verification (Stripe test mode)

**Files:** none (verification only)

> Requires a Stripe test account, a recurring Price, and the Stripe CLI. This is the real proof the killer flaw is fixed.

- [ ] **Step 1: Configure `.env`**

Set in `backend/.env`: `STRIPE_SECRET=sk_test_...`, `STRIPE_PRICE_ID=price_...` (a recurring price), and `APP_URL=http://localhost:8000`. Run `php artisan config:clear`.

- [ ] **Step 2: Start the API and the webhook forwarder**

Run in one terminal:
```bash
php artisan serve
```
Run in another:
```bash
stripe listen --forward-to localhost:8000/api/stripe/webhook
```
Copy the printed `whsec_...` into `backend/.env` as `STRIPE_WEBHOOK_SECRET`, then `php artisan config:clear`.

- [ ] **Step 3: Drive the flow from the extension**

Load the unpacked extension, register/login, create 5 templates, confirm the 6th is blocked and the "Pasate a Pro" CTA appears. Click it, pay with `4242 4242 4242 4242`.

- [ ] **Step 4: Verify the upgrade**

Expected: the Stripe CLI shows `checkout.session.completed` forwarded and a `200`. Confirm in tinker:
```bash
php artisan tinker --execute="echo App\Models\User::latest()->first()->plan;"
```
Expected: `pro`. Back in the popup (it refreshes on focus), the badge reads `pro` and the 6th template now saves.

- [ ] **Step 5: Verify downgrade**

Cancel the subscription via the Billing Portal (or `stripe trigger customer.subscription.deleted`). Expected: the user's `plan` returns to `free` and the limit re-applies.

- [ ] **Step 6: Update docs**

In `CLAUDE.md`, under the backend section, add a short "Billing (Stripe)" note: env vars, the public `/api/stripe/webhook` route, `BillingGateway`/`StripeGateway`, and that **only the webhook flips `plan`**. Commit:
```bash
git add CLAUDE.md
git commit -m "docs: document stripe billing flow"
```

---

## Definition of Done

- A free user at 5 templates sees "Pasate a Pro", pays in Stripe test mode, the signed webhook sets `plan='pro'`, and the 6th template saves.
- Cancelling the subscription returns the user to `free` and re-locks the limit.
- `composer test` passes, including `BillingTest` (webhook signature valid/invalid, plan flip, portal, and limit lift).
- Only the webhook mutates `plan`; the client never can.

## Follow-up plans (NOT in this plan)

- **Positioning / vertical packs** (crack #2) — the moat; see `.claude/agents/pack-curator.md`.
- **Permissions hardening** `<all_urls>` → opt-in (crack #3) — `store-compliance`.
- **Expansion engine: inline variable form, drop `window.prompt`** (crack #4) — `whatsapp-web-engine`.
- **Test suite for the existing API + regex-sync guard, delete `ExampleTest`** (crack #5) — `qa-tester`.
- **Fix `EnforceTemplateLimit` TOCTOU race** (crack #6) — `laravel-backend`.
