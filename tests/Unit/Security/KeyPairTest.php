<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Security;

use KaririCode\Dotenv\Security\KeyPair;
use PHPUnit\Framework\TestCase;

final class KeyPairTest extends TestCase
{
    public function testGenerateProducesValidKeyPair(): void
    {
        $kp = KeyPair::generate();

        $this->assertSame(64, strlen($kp->privateKey));
        $this->assertTrue(ctype_xdigit($kp->privateKey));
        $this->assertSame(8, strlen($kp->publicId));
    }

    public function testGenerateProducesUniqueKeys(): void
    {
        $kp1 = KeyPair::generate();
        $kp2 = KeyPair::generate();

        $this->assertNotSame($kp1->privateKey, $kp2->privateKey);
    }

    public function testFromPrivateKeyReconstitutes(): void
    {
        $original = KeyPair::generate();
        $restored = KeyPair::fromPrivateKey($original->privateKey);

        $this->assertSame($original->privateKey, $restored->privateKey);
        $this->assertSame($original->publicId, $restored->publicId);
    }

    public function testFromPrivateKeyRejectsInvalidLength(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        KeyPair::fromPrivateKey('abcdef');
    }

    public function testFromPrivateKeyRejectsNonHex(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        KeyPair::fromPrivateKey(str_repeat('g', 64));
    }

    public function testGeneratedKeyWorksWithEncryptor(): void
    {
        $kp = KeyPair::generate();
        $encryptor = new \KaririCode\Dotenv\Security\Encryptor($kp->privateKey);

        $encrypted = $encryptor->encrypt('test-value');
        $this->assertSame('test-value', $encryptor->decrypt($encrypted));
    }
}
