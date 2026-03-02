<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Security;

use KaririCode\Dotenv\Security\Encryptor;
use PHPUnit\Framework\TestCase;

final class EncryptorTest extends TestCase
{
    private string $hexKey;

    protected function setUp(): void
    {
        $this->hexKey = bin2hex(random_bytes(32));
    }

    public function testEncryptDecryptRoundTrip(): void
    {
        $encryptor = new Encryptor($this->hexKey);
        $plaintext = 'super-secret-database-password';

        $encrypted = $encryptor->encrypt($plaintext);
        $decrypted = $encryptor->decrypt($encrypted);

        $this->assertSame($plaintext, $decrypted);
    }

    public function testEncryptedValueHasPrefix(): void
    {
        $encryptor = new Encryptor($this->hexKey);
        $encrypted = $encryptor->encrypt('test');

        $this->assertTrue(Encryptor::isEncrypted($encrypted));
        $this->assertStringStartsWith('encrypted:', $encrypted);
    }

    public function testDecryptReturnsPlaintextIfNotEncrypted(): void
    {
        $encryptor = new Encryptor($this->hexKey);
        $plaintext = 'just-a-regular-value';

        $this->assertSame($plaintext, $encryptor->decrypt($plaintext));
    }

    public function testIsEncryptedDetectsPrefix(): void
    {
        $this->assertTrue(Encryptor::isEncrypted('encrypted:abc123'));
        $this->assertFalse(Encryptor::isEncrypted('plain-value'));
        $this->assertFalse(Encryptor::isEncrypted(''));
    }

    public function testDifferentNoncesProduceDifferentCiphertexts(): void
    {
        $encryptor = new Encryptor($this->hexKey);
        $plaintext = 'same-input';

        $enc1 = $encryptor->encrypt($plaintext);
        $enc2 = $encryptor->encrypt($plaintext);

        $this->assertNotSame($enc1, $enc2);

        // Both decrypt to the same plaintext
        $this->assertSame($plaintext, $encryptor->decrypt($enc1));
        $this->assertSame($plaintext, $encryptor->decrypt($enc2));
    }

    public function testWrongKeyFailsDecryption(): void
    {
        $encryptor1 = new Encryptor($this->hexKey);
        $encrypted = $encryptor1->encrypt('secret');

        $wrongKey = bin2hex(random_bytes(32));
        $encryptor2 = new Encryptor($wrongKey);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Decryption failed');

        $encryptor2->decrypt($encrypted);
    }

    public function testCorruptedPayloadThrows(): void
    {
        $encryptor = new Encryptor($this->hexKey);

        $this->expectException(\RuntimeException::class);

        $encryptor->decrypt('encrypted:' . base64_encode('short'));
    }

    public function testInvalidKeyLengthThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('32 bytes');

        new Encryptor('too-short');
    }

    public function testAcceptsRawBinaryKey(): void
    {
        $rawKey = random_bytes(32);
        $encryptor = new Encryptor($rawKey);

        $encrypted = $encryptor->encrypt('binary-key-test');
        $this->assertSame('binary-key-test', $encryptor->decrypt($encrypted));
    }

    public function testEncryptsEmptyString(): void
    {
        $encryptor = new Encryptor($this->hexKey);

        $encrypted = $encryptor->encrypt('');
        $this->assertSame('', $encryptor->decrypt($encrypted));
    }

    public function testEncryptsUnicode(): void
    {
        $encryptor = new Encryptor($this->hexKey);
        $plaintext = 'Régua de cobrança — ICP-Brasil 🇧🇷';

        $encrypted = $encryptor->encrypt($plaintext);
        $this->assertSame($plaintext, $encryptor->decrypt($encrypted));
    }

    public function testEncryptsLargePayload(): void
    {
        $encryptor = new Encryptor($this->hexKey);
        $plaintext = str_repeat('A', 10_000);

        $encrypted = $encryptor->encrypt($plaintext);
        $this->assertSame($plaintext, $encryptor->decrypt($encrypted));
    }
}
