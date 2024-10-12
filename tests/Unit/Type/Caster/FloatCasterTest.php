<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Type\Caster;

use KaririCode\Dotenv\Type\Caster\FloatCaster;
use PHPUnit\Framework\TestCase;

final class FloatCasterTest extends TestCase
{
    private FloatCaster $floatCaster;

    protected function setUp(): void
    {
        parent::setUp();
        $this->floatCaster = new FloatCaster();
    }

    /**
     * @dataProvider floatValuesProvider
     */
    public function testCast(mixed $input, float $expected): void
    {
        $result = $this->floatCaster->cast($input);
        $this->assertSame($expected, $result);
    }

    public static function floatValuesProvider(): array
    {
        return [
            'positive float string' => ['3.14', 3.14],
            'negative float string' => ['-2.5', -2.5],
            'zero float string' => ['0.0', 0.0],
            'float with leading zero' => ['0.5', 0.5],
            'float with trailing zero' => ['1.0', 1.0],
            'scientific notation' => ['1.23e-4', 1.23e-4],
            'float value' => [3.14, 3.14],
            'integer value' => [42, 42.0],
        ];
    }

    public function testCastNonNumericValue(): void
    {
        $input = 'not a float';
        $result = $this->floatCaster->cast($input);
        $this->assertSame(0.0, $result);
    }

    /**
     * @dataProvider canCastProvider
     */
    public function testCanCast(mixed $input, bool $expected): void
    {
        $result = $this->floatCaster->canCast($input);
        $this->assertSame($expected, $result);
    }

    public static function canCastProvider(): array
    {
        return [
            'float string' => ['3.14', true],
            'integer string' => ['42', false],
            'non-numeric string' => ['not a float', false],
            'float value' => [3.14, true],
            'integer value' => [42, false],
            'boolean value' => [true, false],
            'array' => [[], false],
        ];
    }
}
