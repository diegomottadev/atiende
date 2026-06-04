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
