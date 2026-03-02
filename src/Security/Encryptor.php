<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Security;

/**
 * AES-256-GCM encryption for individual environment variable values.
 *
 * Format: `encrypted:<base64(nonce . ciphertext . tag)>`
 *
 * Uses PHP's ext-openssl (bundled in standard PHP distributions).
 * No external tools or Node.js CLI required — unlike dotenvx.
 *
 * ```php
 * $encryptor = new Encryptor($privateKey);
 * $encrypted = $encryptor->encrypt('my-secret');
 * // → "encrypted:base64..."
 *
 * $decrypted = $encryptor->decrypt($encrypted);
 * // → "my-secret"
 * ```
 *
 * @package KaririCode\Dotenv
 * @since   4.3.0
 */
final class Encryptor
{
    private const string CIPHER = 'aes-256-gcm';
    private const int NONCE_LENGTH = 12;
    private const int TAG_LENGTH = 16;
    private const string PREFIX = 'encrypted:';

    private readonly string $key;

    /**
     * @param string $key 64-char hex string (256-bit key) or 32-byte raw binary.
     */
    public function __construct(string $key)
    {
        $this->key = strlen($key) === 64 && ctype_xdigit($key)
            ? hex2bin($key)
            : $key;

        if (strlen($this->key) !== 32) {
            throw new \InvalidArgumentException(
                'Encryption key must be 32 bytes (256-bit) or 64-char hex string.'
            );
        }
    }

    public function encrypt(string $plaintext): string
    {
        $nonce = random_bytes(self::NONCE_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            self::TAG_LENGTH,
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('Encryption failed: ' . openssl_error_string());
        }

        return self::PREFIX . base64_encode($nonce . $ciphertext . $tag);
    }

    public function decrypt(string $payload): string
    {
        if (!self::isEncrypted($payload)) {
            return $payload;
        }

        $raw = base64_decode(substr($payload, strlen(self::PREFIX)), true);

        if ($raw === false || strlen($raw) < self::NONCE_LENGTH + self::TAG_LENGTH) {
            throw new \RuntimeException('Invalid encrypted payload: malformed base64 or too short.');
        }

        $nonce = substr($raw, 0, self::NONCE_LENGTH);
        $tag = substr($raw, -self::TAG_LENGTH);
        $ciphertext = substr($raw, self::NONCE_LENGTH, -self::TAG_LENGTH);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
        );

        if ($plaintext === false) {
            throw new \RuntimeException(
                'Decryption failed — wrong key or corrupted payload.'
            );
        }

        return $plaintext;
    }

    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }
}
