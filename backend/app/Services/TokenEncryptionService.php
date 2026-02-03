<?php

namespace App\Services;

use RuntimeException;

class TokenEncryptionService
{
    private string $key;
    private string $cipher = 'aes-256-gcm';

    public function __construct()
    {
        $key = config('app.token_encryption_key');
        
        if (empty($key)) {
            // In development/testing, use a default key
            if (app()->environment('local', 'testing')) {
                $key = base64_encode(str_repeat('0', 32));
            } else {
                throw new RuntimeException('TOKEN_ENCRYPTION_KEY not configured');
            }
        }
        
        $this->key = base64_decode($key);

        if (strlen($this->key) !== 32) {
            throw new RuntimeException('TOKEN_ENCRYPTION_KEY must be 32 bytes (base64 encoded)');
        }
    }

    public function encrypt(string $plaintext): string
    {
        $iv = random_bytes(12); // 96 bits for GCM
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16 // Tag length
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Encryption failed');
        }

        // Format: base64(iv + tag + ciphertext)
        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $encrypted): string
    {
        $data = base64_decode($encrypted);

        if ($data === false || strlen($data) < 28) { // 12 + 16 minimum
            throw new RuntimeException('Invalid encrypted data');
        }

        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $ciphertext = substr($data, 28);

        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException('Decryption failed - data may be corrupted or key changed');
        }

        return $plaintext;
    }
}
