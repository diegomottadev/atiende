<?php

namespace Tests\Feature;

use App\Contracts\BillingGateway;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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
}
