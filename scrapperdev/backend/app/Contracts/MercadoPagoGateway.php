<?php

namespace App\Contracts;

use App\Models\User;

interface MercadoPagoGateway
{
    /** Creates a preapproval (subscription) and returns its init_point URL. */
    public function createSubscription(User $user): string;

    /** Fetches a preapproval. Returns ['status' => ?string, 'external_reference' => ?string]. */
    public function getPreapproval(string $id): array;
}
