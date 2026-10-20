<?php

declare(strict_types=1);

namespace Beaver\Encryption;

class Encrypter
{
    private const CIPHER     = 'aes-256-gcm';
    private const TAG_LENGTH = 16;

    private string $key;

    public function __construct(string $key)
    {
        $decoded = base64_decode($key, true);

        if ($decoded !== false && strlen($decoded) === 32) {
            $this->key = $decoded;
        } elseif (strlen($key) === 32) {
            $this->key = $key;
        } else {
            throw new \InvalidArgumentException(
                'Encryption key must be 32 bytes (raw or base64). '
                . 'Gera com: php -r "echo base64_encode(random_bytes(32));"'
            );
        }
    }

    public function encrypt(mixed $value): string
    {
        $plaintext = serialize($value);
        $ivLength  = openssl_cipher_iv_length(self::CIPHER);
        $iv        = random_bytes($ivLength);

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
        );

        if ($ciphertext === false || !isset($tag)) {
            throw new \RuntimeException('Encryption failed');
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $payload): mixed
    {
        $decoded = base64_decode($payload, true);

        if ($decoded === false) {
            throw new \RuntimeException('Invalid base64 payload');
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $minLen   = $ivLength + self::TAG_LENGTH + 1;

        if (strlen($decoded) < $minLen) {
            throw new \RuntimeException('Payload too short');
        }

        $iv         = substr($decoded, 0, $ivLength);
        $tag        = substr($decoded, $ivLength, self::TAG_LENGTH);
        $ciphertext = substr($decoded, $ivLength + self::TAG_LENGTH);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
        );

        if ($plaintext === false) {
            throw new \RuntimeException(
                'Decryption failed — payload may be tampered, or APP_KEY changed'
            );
        }

        $value = @unserialize($plaintext);

        if ($value === false && $plaintext !== serialize(false)) {
            throw new \RuntimeException('Failed to unserialize decrypted value');
        }

        return $value;
    }
}
