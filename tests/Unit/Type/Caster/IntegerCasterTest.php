<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Type\Caster;

use KaririCode\Dotenv\Type\Caster\IntegerCaster;
use PHPUnit\Framework\TestCase;

final class IntegerCasterTest extends TestCase
{
    private IntegerCaster $integerCaster;

    protected function setUp(): void
    {
        parent::setUp();
        $this->integerCaster = new IntegerCaster();
    }

    /**
     * @dataProvider integerValuesProvider
     */
    public function testCast(mixed $input, int $expected): void
    {
        $result = $this->integerCaster->cast($input);
        $this->assertSame($expected, $result);
    }

    public function integerValuesProvider(): array
    {
        return [
            'positive integer string' => ['42', 42],
            'negative integer string' => ['-10', -10],
            'zero string' => ['0', 0],
            'integer with leading zeros' => ['007', 7],
            'integer value' => [42, 42],
            'float value' => [3.14, 3],
        ];
    }

    public function testCastNonNumericValue(): void
    {
        $input = 'not an integer';
        $result = $this->integerCaster->cast($input);
        $this->assertSame(0, $result);
    }

    /**
     * @dataProvider canCastProvider
     */
    public function testCanCast(mixed $input, bool $expected): void
    {
        $result = $this->integerCaster->canCast($input);
        $this->assertSame($expected, $result);
    }

    public function canCastProvider(): array
    {
        return [
            'integer string' => ['42', true],
            'float string' => ['3.14', false],
            'non-numeric string' => ['not an integer', false],
            'integer value' => [42, true],
            'float value' => [3.14, false],
            'boolean value' => [true, false],
            'array' => [[], false],
        ];
    }
}
