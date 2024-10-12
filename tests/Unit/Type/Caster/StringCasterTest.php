<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Type\Caster;

use KaririCode\Dotenv\Type\Caster\StringCaster;
use PHPUnit\Framework\TestCase;

final class StringCasterTest extends TestCase
{
    private StringCaster $stringCaster;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stringCaster = new StringCaster();
    }

    /**
     * @dataProvider stringValuesProvider
     */
    public function testCast(mixed $input, string $expected): void
    {
        $result = $this->stringCaster->cast($input);
        $this->assertSame($expected, $result);
    }

    public static function stringValuesProvider(): array
    {
        return [
            'simple string' => ['hello', 'hello'],
            'integer' => [42, '42'],
            'float' => [3.14, '3.14'],
            'boolean true' => [true, '1'],
            'boolean false' => [false, ''],
            'null' => [null, ''],
            'object with __toString' => [new class {
                public function __toString()
                {
                    return 'object string';
                }
            }, 'object string'],
        ];
    }

    public function testCastObjectWithoutToString(): void
    {
        $this->expectException(\Error::class);
        $this->stringCaster->cast(new \stdClass());
    }

    /**
     * @dataProvider canCastProvider
     */
    public function testCanCast(mixed $input, bool $expected): void
    {
        $result = $this->stringCaster->canCast($input);
        $this->assertSame($expected, $result);
    }

    public static function canCastProvider(): array
    {
        return [
            'string' => ['hello', true],
            'integer' => [42, true],
            'float' => [3.14, true],
            'boolean' => [true, true],
            'null' => [null, true],
            'object with __toString' => [new class {
                public function __toString()
                {
                    return 'object string';
                }
            }, true],
            'object without __toString' => [new \stdClass(), false],
        ];
    }
}
