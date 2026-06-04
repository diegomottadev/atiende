<?php

namespace Tests\Feature;

use App\Contracts\MercadoPagoGateway;
use App\Models\User;
use App\Support\MercadoPagoSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MercadoPagoTest extends TestCase
{
    use RefreshDatabase;

    public function test_signature_helper_validates_correct_and_rejects_wrong(): void
    {
        $secret = 'mp_whsec';
        $ts = '1700000000000';
        $manifest = 'id:pre_123;request-id:req-1;ts:'.$ts.';';
        $v1 = hash_hmac('sha256', $manifest, $secret);

        $this->assertTrue(MercadoPagoSignature::isValid($secret, 'pre_123', 'req-1', $ts, $v1));
        $this->assertFalse(MercadoPagoSignature::isValid($secret, 'pre_123', 'req-1', $ts, 'deadbeef'));
        $this->assertFalse(MercadoPagoSignature::isValid($secret, 'pre_123', 'req-1', $ts, null));
        $this->assertFalse(MercadoPagoSignature::isValid(null, 'pre_123', 'req-1', $ts, $v1));
    }

    /** Builds a valid x-signature header for a given data.id + request id. */
    private function signed(string $dataId, string $requestId, string $secret = 'mp_whsec'): string
    {
        config(['services.mercadopago.webhook_secret' => $secret]);
        $ts = '1700000000000';
        $manifest = 'id:'.strtolower($dataId).';request-id:'.$requestId.';ts:'.$ts.';';
        $v1 = hash_hmac('sha256', $manifest, $secret);

        return "ts={$ts},v1={$v1}";
    }

    public function test_webhook_rejects_bad_signature_and_never_fetches(): void
    {
        config(['services.mercadopago.webhook_secret' => 'mp_whsec']);
        $user = User::factory()->create(['plan' => 'free']);

        $this->mock(MercadoPagoGateway::class, function ($mock) {
            $mock->shouldReceive('getPreapproval')->never();
        });

        $res = $this->call('POST', '/api/mp/webhook?data.id=pre_123&type=preapproval', [], [], [],
            ['HTTP_X_SIGNATURE' => 'ts=1,v1=deadbeef', 'HTTP_X_REQUEST_ID' => 'req-1', 'CONTENT_TYPE' => 'application/json'], '{}');

        $res->assertStatus(400);
        $this->assertSame('free', $user->fresh()->plan);
    }

    public function test_authorized_preapproval_sets_user_to_pro(): void
    {
        $user = User::factory()->create(['plan' => 'free']);

        $this->mock(MercadoPagoGateway::class, function ($mock) use ($user) {
            $mock->shouldReceive('getPreapproval')
                ->once()
                ->with('pre_123')
                ->andReturn(['status' => 'authorized', 'external_reference' => (string) $user->id]);
        });

        $header = $this->signed('pre_123', 'req-1');
        $res = $this->call('POST', '/api/mp/webhook?data.id=pre_123&type=preapproval', [], [], [],
            ['HTTP_X_SIGNATURE' => $header, 'HTTP_X_REQUEST_ID' => 'req-1', 'CONTENT_TYPE' => 'application/json'], '{}');

        $res->assertOk();
        $fresh = $user->fresh();
        $this->assertSame('pro', $fresh->plan);
        $this->assertSame('pre_123', $fresh->mp_preapproval_id);
    }

    public function test_cancelled_preapproval_downgrades_user(): void
    {
        $user = User::factory()->create(['plan' => 'pro', 'mp_preapproval_id' => 'pre_xyz']);

        $this->mock(MercadoPagoGateway::class, function ($mock) use ($user) {
            $mock->shouldReceive('getPreapproval')
                ->once()
                ->andReturn(['status' => 'cancelled', 'external_reference' => (string) $user->id]);
        });

        $header = $this->signed('pre_xyz', 'req-2');
        $res = $this->call('POST', '/api/mp/webhook?data.id=pre_xyz&type=preapproval', [], [], [],
            ['HTTP_X_SIGNATURE' => $header, 'HTTP_X_REQUEST_ID' => 'req-2', 'CONTENT_TYPE' => 'application/json'], '{}');

        $res->assertOk();
        $this->assertSame('free', $user->fresh()->plan);
    }

    public function test_checkout_requires_auth(): void
    {
        $this->postJson('/api/billing/mp/checkout')->assertUnauthorized();
    }

    public function test_checkout_returns_init_point_url(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->mock(MercadoPagoGateway::class, function ($mock) {
            $mock->shouldReceive('createSubscription')
                ->once()
                ->andReturn('https://www.mercadopago.com/init/abc');
        });

        $this->postJson('/api/billing/mp/checkout')
            ->assertOk()
            ->assertJson(['url' => 'https://www.mercadopago.com/init/abc']);
    }
}
