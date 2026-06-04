<?php

namespace App\Http\Controllers;

use App\Contracts\MercadoPagoGateway;
use App\Models\User;
use App\Support\MercadoPagoSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MercadoPagoWebhookController extends Controller
{
    public function handle(Request $request, MercadoPagoGateway $gateway): JsonResponse
    {
        // PHP mangles "data.id" query keys to "data_id".
        $dataId = $request->query('data_id')
            ?? $request->query('id')
            ?? data_get($request->json()->all(), 'data.id');

        if (! $dataId) {
            return response()->json(['received' => true]); // non-actionable ping
        }

        $sig = MercadoPagoSignature::parseHeader($request->header('x-signature'));
        $valid = MercadoPagoSignature::isValid(
            config('services.mercadopago.webhook_secret'),
            (string) $dataId,
            $request->header('x-request-id'),
            $sig['ts'],
            $sig['v1'],
        );

        if (! $valid) {
            return response()->json(['error' => 'invalid signature'], 400);
        }

        $pre = $gateway->getPreapproval((string) $dataId);
        $user = User::find($pre['external_reference'] ?? null);

        if ($user) {
            if (($pre['status'] ?? null) === 'authorized') {
                $user->update(['plan' => 'pro', 'mp_preapproval_id' => $dataId]);
            } elseif (in_array($pre['status'] ?? null, ['cancelled', 'paused'], true)) {
                $user->update(['plan' => 'free']);
            }
        }

        return response()->json(['received' => true]);
    }
}
