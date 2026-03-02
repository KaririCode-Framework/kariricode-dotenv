<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Enum;

use KaririCode\Dotenv\Enum\LoadMode;
use KaririCode\Dotenv\Enum\ValueType;
use PHPUnit\Framework\TestCase;

final class EnumTest extends TestCase
{
    // ── LoadMode ──────────────────────────────────────────────────────

    public function testLoadModeCasesExist(): void
    {
        self::assertSame('Immutable', LoadMode::Immutable->name);
        self::assertSame('Overwrite', LoadMode::Overwrite->name);
        self::assertSame('SkipExisting', LoadMode::SkipExisting->name);
    }

    public function testLoadModeHasThreeCases(): void
    {
        self::assertCount(3, LoadMode::cases());
    }

    public function testLoadModeIsBackedByNothing(): void
    {
        // Pure enum (no backing type)
        $reflection = new \ReflectionEnum(LoadMode::class);
        self::assertFalse($reflection->isBacked());
    }

    public function testLoadModeIdentity(): void
    {
        self::assertSame(LoadMode::Immutable, LoadMode::Immutable);
        self::assertNotSame(LoadMode::Immutable, LoadMode::Overwrite);
        self::assertNotSame(LoadMode::Immutable, LoadMode::SkipExisting);
    }

    // ── ValueType ─────────────────────────────────────────────────────

    public function testValueTypeCasesExist(): void
    {
        self::assertSame('String', ValueType::String->name);
        self::assertSame('Integer', ValueType::Integer->name);
        self::assertSame('Float', ValueType::Float->name);
        self::assertSame('Boolean', ValueType::Boolean->name);
        self::assertSame('Null', ValueType::Null->name);
        self::assertSame('Json', ValueType::Json->name);
        self::assertSame('Array', ValueType::Array->name);
    }

    public function testValueTypeHasSevenCases(): void
    {
        self::assertCount(7, ValueType::cases());
    }

    public function testValueTypeIsBackedByNothing(): void
    {
        $reflection = new \ReflectionEnum(ValueType::class);
        self::assertFalse($reflection->isBacked());
    }

    public function testValueTypeIdentity(): void
    {
        self::assertSame(ValueType::String, ValueType::String);
        self::assertNotSame(ValueType::String, ValueType::Integer);
        self::assertNotSame(ValueType::Boolean, ValueType::Integer);
    }
}
