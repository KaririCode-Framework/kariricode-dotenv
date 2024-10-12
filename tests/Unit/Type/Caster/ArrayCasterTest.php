<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Type\Caster;

use KaririCode\Dotenv\Type\Caster\ArrayCaster;
use PHPUnit\Framework\TestCase;

final class ArrayCasterTest extends TestCase
{
    private ArrayCaster $arrayCaster;

    protected function setUp(): void
    {
        parent::setUp();
        $this->arrayCaster = new ArrayCaster();
    }

    /**
     * @dataProvider validArrayStringProvider
     */
    public function testCastValidArrayString(string $input, array $expected): void
    {
        $result = $this->arrayCaster->cast($input);
        $this->assertSame($expected, $result);
    }

    public function validArrayStringProvider(): array
    {
        return [
            'simple array' => ['[1, 2, 3]', ['1', '2', '3']],
            'array with spaces' => ['[ 1, 2, 3 ]', ['1', '2', '3']],
            'array with strings' => ['["a", "b", "c"]', ['a', 'b', 'c']],
            'array with mixed types' => ['[1, "two", 3.0]', ['1', 'two', '3.0']],
            'empty array' => ['[]', []],
            'array with single item' => ['[42]', ['42']],
        ];
    }

    public function testCastNonStringValue(): void
    {
        $input = ['already', 'an', 'array'];
        $result = $this->arrayCaster->cast($input);
        $this->assertSame($input, $result);
    }

    public function testCastNonArrayString(): void
    {
        $input = 'not an array';
        $result = $this->arrayCaster->cast($input);
        $this->assertSame(['not an array'], $result);
    }
}
