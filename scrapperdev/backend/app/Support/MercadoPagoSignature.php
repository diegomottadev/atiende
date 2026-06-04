<?php

namespace App\Support;

class MercadoPagoSignature
{
    /**
     * Validates the Mercado Pago webhook x-signature.
     * Manifest: id:<data.id>;request-id:<x-request-id>;ts:<ts>;  (data.id lowercased)
     * HMAC-SHA256(manifest, secret) in hex must equal v1.
     */
    public static function isValid(?string $secret, ?string $dataId, ?string $requestId, ?string $ts, ?string $v1): bool
    {
        if (! $secret || ! $dataId || ! $ts || ! $v1) {
            return false;
        }

        $manifest = 'id:'.strtolower($dataId).';request-id:'.($requestId ?? '').';ts:'.$ts.';';
        $computed = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($computed, $v1);
    }

    /** Parses an "ts=...,v1=..." header into ['ts' => ?string, 'v1' => ?string]. */
    public static function parseHeader(?string $header): array
    {
        $out = ['ts' => null, 'v1' => null];
        foreach (explode(',', (string) $header) as $part) {
            $kv = explode('=', $part, 2);
            if (count($kv) === 2) {
                $out[trim($kv[0])] = trim($kv[1]);
            }
        }

        return $out;
    }
}
