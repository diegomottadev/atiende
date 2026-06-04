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
