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
