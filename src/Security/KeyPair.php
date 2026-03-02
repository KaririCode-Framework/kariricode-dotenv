<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Security;

/**
 * Generates encryption key pairs for .env file encryption.
 * Uses 256-bit random keys represented as hex strings.
 *
 * @package KaririCode\Dotenv
 * @since   4.3.0
 */
final class KeyPair
{
    public readonly string $privateKey;
    public readonly string $publicId;

    private function __construct(string $privateKey, string $publicId)
    {
        $this->privateKey = $privateKey;
        $this->publicId = $publicId;
    }

    /**
     * Generates a new random key pair.
     *
     * The private key is the actual 256-bit AES key (hex-encoded).
     * The public ID is an 8-char identifier for referencing this key
     * in multi-environment setups (derived from the key hash).
     */
    public static function generate(): self
    {
        $rawKey = random_bytes(32);
        $privateKey = bin2hex($rawKey);
        $publicId = substr(hash('sha256', $rawKey), 0, 8);

        return new self($privateKey, $publicId);
    }

    /**
     * Reconstitutes a KeyPair from an existing private key.
     */
    public static function fromPrivateKey(string $privateKey): self
    {
        if (\strlen($privateKey) !== 64 || ! ctype_xdigit($privateKey)) {
            throw new \InvalidArgumentException(
                'Private key must be a 64-character hex string (256-bit).',
            );
        }

        $publicId = substr(hash('sha256', (string) hex2bin($privateKey)), 0, 8);

        return new self($privateKey, $publicId);
    }
}
