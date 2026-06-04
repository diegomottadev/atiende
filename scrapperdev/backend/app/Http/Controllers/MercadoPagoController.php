<?php

namespace App\Http\Controllers;

use App\Contracts\MercadoPagoGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MercadoPagoController extends Controller
{
    public function checkout(Request $request, MercadoPagoGateway $gateway): JsonResponse
    {
        return response()->json([
            'url' => $gateway->createSubscription($request->user()),
        ]);
    }
}
