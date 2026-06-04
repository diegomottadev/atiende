<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Contracts\BillingGateway::class, function () {
            return new \App\Services\StripeGateway(
                config('services.stripe.secret'),
                config('services.stripe.webhook_secret'),
                config('app.url'),
                config('services.stripe.price_id'),
            );
        });

        $this->app->bind(\App\Contracts\MercadoPagoGateway::class, function () {
            return new \App\Services\MercadoPagoApiGateway(
                config('services.mercadopago.access_token'),
                config('app.url'),
                config('services.mercadopago.amount'),
                config('services.mercadopago.currency'),
                config('services.mercadopago.reason'),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
