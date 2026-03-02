<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\ValueObject;

use KaririCode\Dotenv\Enum\ValueType;
use KaririCode\Dotenv\ValueObject\EnvironmentVariable;
use PHPUnit\Framework\TestCase;

final class EnvironmentVariableTest extends TestCase
{
    public function testConstructionWithAllFields(): void
    {
        $var = new EnvironmentVariable(
            name: 'DB_PORT',
            rawValue: '5432',
            type: ValueType::Integer,
            value: 5432,
            source: '.env.local',
            overridden: true,
        );

        self::assertSame('DB_PORT', $var->name);
        self::assertSame('5432', $var->rawValue);
        self::assertSame(ValueType::Integer, $var->type);
        self::assertSame(5432, $var->value);
        self::assertSame('.env.local', $var->source);
        self::assertTrue($var->overridden);
    }

    public function testDefaultSourceAndOverridden(): void
    {
        $var = new EnvironmentVariable(
            name: 'APP_NAME',
            rawValue: 'KaririCode',
            type: ValueType::String,
            value: 'KaririCode',
        );

        self::assertSame('', $var->source);
        self::assertFalse($var->overridden);
    }

    public function testIsReadonly(): void
    {
        $reflection = new \ReflectionClass(EnvironmentVariable::class);
        self::assertTrue($reflection->isReadOnly());
    }

    public function testIsFinal(): void
    {
        $reflection = new \ReflectionClass(EnvironmentVariable::class);
        self::assertTrue($reflection->isFinal());
    }

    public function testNullValueIsAllowed(): void
    {
        $var = new EnvironmentVariable(
            name: 'OPTIONAL',
            rawValue: 'null',
            type: ValueType::Null,
            value: null,
        );

        self::assertNull($var->value);
        self::assertSame(ValueType::Null, $var->type);
    }

    public function testComplexTypedValues(): void
    {
        $array = ['a', 'b'];
        $var = new EnvironmentVariable(
            name: 'ITEMS',
            rawValue: '["a","b"]',
            type: ValueType::Array,
            value: $array,
        );

        self::assertSame($array, $var->value);
        self::assertSame(ValueType::Array, $var->type);
    }

    public function testBooleanValue(): void
    {
        $var = new EnvironmentVariable(
            name: 'DEBUG',
            rawValue: 'true',
            type: ValueType::Boolean,
            value: true,
        );

        self::assertTrue($var->value);
    }
}
