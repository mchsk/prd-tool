<?php

namespace Tests\Unit;

use App\Services\TokenEncryptionService;
use Tests\TestCase;

class TokenEncryptionServiceTest extends TestCase
{
    private TokenEncryptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.token_encryption_key' => base64_encode(str_repeat('a', 32))]);
        $this->service = new TokenEncryptionService();
    }

    /**
     * Test encryption and decryption works correctly.
     */
    public function test_encrypt_and_decrypt_works(): void
    {
        $original = 'ya29.a0AfB_byC1234567890_test_token';

        $encrypted = $this->service->encrypt($original);
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertNotEquals($original, $encrypted);
        $this->assertEquals($original, $decrypted);
    }

    /**
     * Test each encryption produces different output (due to random IV).
     */
    public function test_encrypted_values_are_different_each_time(): void
    {
        $original = 'test_token_value';

        $encrypted1 = $this->service->encrypt($original);
        $encrypted2 = $this->service->encrypt($original);

        $this->assertNotEquals($encrypted1, $encrypted2);

        // But both decrypt to the same value
        $this->assertEquals($original, $this->service->decrypt($encrypted1));
        $this->assertEquals($original, $this->service->decrypt($encrypted2));
    }

    /**
     * Test decryption fails on tampered data.
     */
    public function test_decrypt_fails_on_tampered_data(): void
    {
        $this->expectException(\RuntimeException::class);

        $tampered = base64_encode(str_repeat('x', 50));
        $this->service->decrypt($tampered);
    }

    /**
     * Test decryption fails on invalid base64.
     */
    public function test_decrypt_fails_on_invalid_data(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->service->decrypt('not-valid-base64!!!');
    }

    /**
     * Test empty strings are handled.
     */
    public function test_handles_empty_strings(): void
    {
        $encrypted = $this->service->encrypt('');
        $decrypted = $this->service->decrypt($encrypted);

        $this->assertEquals('', $decrypted);
    }
}
