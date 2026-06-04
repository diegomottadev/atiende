<?php

namespace App\Services;

use App\Contracts\MercadoPagoGateway;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class MercadoPagoApiGateway implements MercadoPagoGateway
{
    private const BASE = 'https://api.mercadopago.com';

    public function __construct(
        private ?string $accessToken,
        private string $appUrl,
        private float $amount,
        private string $currency,
        private string $reason,
    ) {}

    public function createSubscription(User $user): string
    {
        $res = Http::withToken($this->accessToken)
            ->acceptJson()
            ->post(self::BASE.'/preapproval', [
                'reason' => $this->reason,
                'external_reference' => (string) $user->id,
                'payer_email' => $user->email,
                'back_url' => rtrim($this->appUrl, '/').'/billing/success',
                'status' => 'pending',
                'auto_recurring' => [
                    'frequency' => 1,
                    'frequency_type' => 'months',
                    'transaction_amount' => $this->amount,
                    'currency_id' => $this->currency,
                ],
            ])
            ->throw()
            ->json();

        return $res['init_point'];
    }

    public function getPreapproval(string $id): array
    {
        $res = Http::withToken($this->accessToken)
            ->acceptJson()
            ->get(self::BASE.'/preapproval/'.$id)
            ->throw()
            ->json();

        return [
            'status' => $res['status'] ?? null,
            'external_reference' => $res['external_reference'] ?? null,
        ];
    }
}
