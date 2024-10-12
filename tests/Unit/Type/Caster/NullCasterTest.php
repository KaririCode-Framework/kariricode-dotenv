<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Type\Caster;

use KaririCode\Dotenv\Type\Caster\NullCaster;
use PHPUnit\Framework\TestCase;

final class NullCasterTest extends TestCase
{
    private NullCaster $nullCaster;

    protected function setUp(): void
    {
        parent::setUp();
        $this->nullCaster = new NullCaster();
    }

    /**
     * @dataProvider nullValuesProvider
     */
    public function testCast(mixed $input): void
    {
        $result = $this->nullCaster->cast($input);
        $this->assertNull($result);
    }

    public function nullValuesProvider(): array
    {
        return [
            'null string' => ['null'],
            'empty string' => [''],
            'NULL uppercase' => ['NULL'],
            'null value' => [null],
        ];
    }

    /**
     * @dataProvider canCastProvider
     */
    public function testCanCast(mixed $input, bool $expected): void
    {
        $result = $this->nullCaster->canCast($input);
        $this->assertSame($expected, $result);
    }

    public function canCastProvider(): array
    {
        return [
            'null string' => ['null', true],
            'empty string' => ['', true],
            'NULL uppercase' => ['NULL', true],
            'non-null string' => ['not null', false],
            'integer' => [0, false],
            'boolean' => [false, false],
            'array' => [[], false],
        ];
    }

    public function testCastNonNullValue(): void
    {
        $input = 'not null';
        $result = $this->nullCaster->cast($input);
        $this->assertNull($result);
    }
}
