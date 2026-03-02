<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Type;

use KaririCode\Dotenv\Contract\TypeCaster;
use KaririCode\Dotenv\Contract\TypeDetector;
use KaririCode\Dotenv\Enum\ValueType;
use KaririCode\Dotenv\Type\TypeSystem;
use PHPUnit\Framework\TestCase;

final class TypeSystemTest extends TestCase
{
    private TypeSystem $typeSystem;

    protected function setUp(): void
    {
        $this->typeSystem = new TypeSystem();
    }

    // ── Null Detection ────────────────────────────────────────────────

    public function testDetectsNull(): void
    {
        self::assertSame(ValueType::Null, $this->typeSystem->detect('null'));
        self::assertSame(ValueType::Null, $this->typeSystem->detect('NULL'));
        self::assertSame(ValueType::Null, $this->typeSystem->detect('(null)'));
    }

    public function testEmptyStringIsNotNull(): void
    {
        self::assertSame(ValueType::String, $this->typeSystem->detect(''));
        self::assertSame('', $this->typeSystem->resolve(''));
    }

    public function testCastsNull(): void
    {
        self::assertNull($this->typeSystem->resolve('null'));
        self::assertNull($this->typeSystem->resolve('NULL'));
        self::assertNull($this->typeSystem->resolve('(null)'));
    }

    // ── Boolean Detection ─────────────────────────────────────────────

    public function testDetectsBoolean(): void
    {
        self::assertSame(ValueType::Boolean, $this->typeSystem->detect('true'));
        self::assertSame(ValueType::Boolean, $this->typeSystem->detect('false'));
        self::assertSame(ValueType::Boolean, $this->typeSystem->detect('TRUE'));
        self::assertSame(ValueType::Boolean, $this->typeSystem->detect('FALSE'));
        self::assertSame(ValueType::Boolean, $this->typeSystem->detect('yes'));
        self::assertSame(ValueType::Boolean, $this->typeSystem->detect('no'));
        self::assertSame(ValueType::Boolean, $this->typeSystem->detect('on'));
        self::assertSame(ValueType::Boolean, $this->typeSystem->detect('off'));
    }

    public function testDetectsBooleanMixedCase(): void
    {
        self::assertSame(ValueType::Boolean, $this->typeSystem->detect('True'));
        self::assertSame(ValueType::Boolean, $this->typeSystem->detect('False'));
        self::assertSame(ValueType::Boolean, $this->typeSystem->detect('Yes'));
        self::assertSame(ValueType::Boolean, $this->typeSystem->detect('No'));
        self::assertSame(ValueType::Boolean, $this->typeSystem->detect('On'));
        self::assertSame(ValueType::Boolean, $this->typeSystem->detect('Off'));
    }

    public function testCastsBooleanTrue(): void
    {
        self::assertTrue($this->typeSystem->resolve('true'));
        self::assertTrue($this->typeSystem->resolve('TRUE'));
        self::assertTrue($this->typeSystem->resolve('yes'));
        self::assertTrue($this->typeSystem->resolve('on'));
        self::assertTrue($this->typeSystem->resolve('(true)'));
    }

    public function testCastsBooleanFalse(): void
    {
        self::assertFalse($this->typeSystem->resolve('false'));
        self::assertFalse($this->typeSystem->resolve('FALSE'));
        self::assertFalse($this->typeSystem->resolve('no'));
        self::assertFalse($this->typeSystem->resolve('off'));
    }

    // ── Integer Detection ─────────────────────────────────────────────

    public function testDetectsInteger(): void
    {
        self::assertSame(ValueType::Integer, $this->typeSystem->detect('42'));
        self::assertSame(ValueType::Integer, $this->typeSystem->detect('0'));
        self::assertSame(ValueType::Integer, $this->typeSystem->detect('-10'));
        self::assertSame(ValueType::Integer, $this->typeSystem->detect('+99'));
    }

    public function testCastsInteger(): void
    {
        self::assertSame(42, $this->typeSystem->resolve('42'));
        self::assertSame(0, $this->typeSystem->resolve('0'));
        self::assertSame(-10, $this->typeSystem->resolve('-10'));
    }

    // ── Float Detection ───────────────────────────────────────────────

    public function testDetectsFloat(): void
    {
        self::assertSame(ValueType::Float, $this->typeSystem->detect('3.14'));
        self::assertSame(ValueType::Float, $this->typeSystem->detect('-0.5'));
        self::assertSame(ValueType::Float, $this->typeSystem->detect('1e10'));
        self::assertSame(ValueType::Float, $this->typeSystem->detect('2.5E-3'));
    }

    public function testCastsFloat(): void
    {
        self::assertSame(3.14, $this->typeSystem->resolve('3.14'));
        self::assertSame(-0.5, $this->typeSystem->resolve('-0.5'));
    }

    // ── JSON Detection ────────────────────────────────────────────────

    public function testDetectsJson(): void
    {
        self::assertSame(ValueType::Json, $this->typeSystem->detect('{"key": "value"}'));
        self::assertSame(ValueType::Json, $this->typeSystem->detect('{"nested": {"a": 1}}'));
    }

    public function testCastsJson(): void
    {
        $result = $this->typeSystem->resolve('{"key": "value", "num": 42}');

        self::assertIsArray($result);
        self::assertSame('value', $result['key']);
        self::assertSame(42, $result['num']);
    }

    public function testInvalidJsonNotDetected(): void
    {
        self::assertSame(ValueType::String, $this->typeSystem->detect('{invalid json}'));
    }

    // ── Array Detection ───────────────────────────────────────────────

    public function testDetectsArray(): void
    {
        self::assertSame(ValueType::Array, $this->typeSystem->detect('["a", "b", "c"]'));
        self::assertSame(ValueType::Array, $this->typeSystem->detect('[1, 2, 3]'));
    }

    public function testCastsArray(): void
    {
        $result = $this->typeSystem->resolve('["item1", "item2"]');

        self::assertIsArray($result);
        self::assertSame(['item1', 'item2'], $result);
    }

    // ── String Fallback ───────────────────────────────────────────────

    public function testFallsBackToString(): void
    {
        self::assertSame(ValueType::String, $this->typeSystem->detect('hello world'));
        self::assertSame(ValueType::String, $this->typeSystem->detect('/usr/local/bin'));
        self::assertSame(ValueType::String, $this->typeSystem->detect('https://kariricode.org'));
    }

    public function testStringValuesReturnedAsIs(): void
    {
        self::assertSame('hello world', $this->typeSystem->resolve('hello world'));
    }

    // ── Custom Detector/Caster ────────────────────────────────────────

    public function testCustomDetectorIsRespected(): void
    {
        $customDetector = new class () implements TypeDetector {
            public function priority(): int
            {
                return 999; // Highest priority
            }

            public function detect(string $value): ?ValueType
            {
                return str_starts_with($value, 'CUSTOM:') ? ValueType::String : null;
            }
        };

        $this->typeSystem->addDetector($customDetector);

        // Custom detector matches first, but returns String
        self::assertSame(ValueType::String, $this->typeSystem->detect('CUSTOM:42'));
    }

    public function testCustomCasterOverridesDefault(): void
    {
        $customCaster = new class () implements TypeCaster {
            public function cast(string $value): int
            {
                return (int) $value * 100;
            }
        };

        $this->typeSystem->addCaster(ValueType::Integer, $customCaster);

        self::assertSame(4200, $this->typeSystem->resolve('42'));
    }
}
