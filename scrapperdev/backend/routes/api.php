<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\MercadoPagoController;
use App\Http\Controllers\MercadoPagoWebhookController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TemplateController;
use App\Http\Middleware\EnforceTemplateLimit;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public: payment providers call these. Verified by signature, not by Sanctum.
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);
Route::post('/mp/webhook', [MercadoPagoWebhookController::class, 'handle']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/templates', [TemplateController::class, 'index']);
    Route::post('/templates', [TemplateController::class, 'store'])
        ->middleware(EnforceTemplateLimit::class);
    Route::put('/templates/{template}', [TemplateController::class, 'update']);
    Route::delete('/templates/{template}', [TemplateController::class, 'destroy']);

    Route::post('/billing/checkout', [BillingController::class, 'checkout']);
    Route::post('/billing/portal', [BillingController::class, 'portal']);
    Route::post('/billing/mp/checkout', [MercadoPagoController::class, 'checkout']);
});
