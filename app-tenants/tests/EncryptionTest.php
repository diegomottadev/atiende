<?php
use PHPUnit\Framework\TestCase;
use App\Encryption;

class EncryptionTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['PLATFORM_ENCRYPTION_KEY'] = bin2hex(random_bytes(32));
    }

    public function test_encrypt_then_decrypt_returns_original(): void
    {
        $original = 'EAABsbCS1iHgBOZD...my_wa_token';
        $encrypted = Encryption::encrypt($original);
        $this->assertNotEquals($original, $encrypted);
        $this->assertEquals($original, Encryption::decrypt($encrypted));
    }

    public function test_two_encryptions_produce_different_ciphertext(): void
    {
        $token = 'same_token';
        $this->assertNotEquals(Encryption::encrypt($token), Encryption::encrypt($token));
    }

    public function test_decrypt_with_wrong_key_throws(): void
    {
        $encrypted = Encryption::encrypt('secret');
        $_ENV['PLATFORM_ENCRYPTION_KEY'] = bin2hex(random_bytes(32)); // different key
        $this->expectException(\RuntimeException::class);
        Encryption::decrypt($encrypted);
    }

    public function test_mask_shows_last_eight_chars(): void
    {
        $token = 'ABCDEF1234567890';
        $this->assertEquals('...34567890', Encryption::mask($token));
    }
}
