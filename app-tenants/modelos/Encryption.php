<?php
namespace App;

class Encryption
{
    private const CIPHER = 'aes-256-gcm';
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;

    public static function encrypt(string $plaintext): string
    {
        $key = self::getKey();
        $iv  = random_bytes(self::IV_LENGTH);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::TAG_LENGTH);
        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed');
        }
        return base64_encode($iv . $tag . $ciphertext);
    }

    public static function decrypt(string $encoded): string
    {
        $key  = self::getKey();
        $raw  = base64_decode($encoded, true);
        if ($raw === false || strlen($raw) < self::IV_LENGTH + self::TAG_LENGTH + 1) {
            throw new \RuntimeException('Invalid ciphertext');
        }
        $iv         = substr($raw, 0, self::IV_LENGTH);
        $tag        = substr($raw, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($raw, self::IV_LENGTH + self::TAG_LENGTH);
        $plaintext  = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            throw new \RuntimeException('Decryption failed — wrong key or tampered data');
        }
        return $plaintext;
    }

    public static function mask(string $token): string
    {
        return '...' . substr($token, -8);
    }

    private static function getKey(): string
    {
        $hex = $_ENV['PLATFORM_ENCRYPTION_KEY'] ?? '';
        if (strlen($hex) !== 64) {
            throw new \RuntimeException('PLATFORM_ENCRYPTION_KEY must be 64 hex chars (32 bytes)');
        }
        return hex2bin($hex);
    }
}
