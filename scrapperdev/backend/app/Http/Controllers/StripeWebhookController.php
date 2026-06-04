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
