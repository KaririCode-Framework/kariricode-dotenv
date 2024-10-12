<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Type\Caster;

use KaririCode\Dotenv\Type\Caster\BooleanCaster;
use PHPUnit\Framework\TestCase;

final class BooleanCasterTest extends TestCase
{
    private BooleanCaster $booleanCaster;

    protected function setUp(): void
    {
        parent::setUp();
        $this->booleanCaster = new BooleanCaster();
    }

    /**
     * @dataProvider booleanValuesProvider
     */
    public function testCast(mixed $input, bool $expected): void
    {
        $result = $this->booleanCaster->cast($input);
        $this->assertSame($expected, $result);
    }

    public function booleanValuesProvider(): array
    {
        return [
            'true string' => ['true', true],
            'false string' => ['false', false],
            '1 string' => ['1', true],
            '0 string' => ['0', false],
            'yes string' => ['yes', true],
            'no string' => ['no', false],
            'on string' => ['on', true],
            'off string' => ['off', false],
            'TRUE uppercase' => ['TRUE', true],
            'FALSE uppercase' => ['FALSE', false],
            'Yes uppercase' => ['Yes', true],
            'No uppercase' => ['No', false],
            'ON uppercase' => ['ON', true],
            'OFF uppercase' => ['OFF', false],
            'true boolean' => [true, true],
            'false boolean' => [false, false],
        ];
    }

    public function testCastNonBooleanValue(): void
    {
        $input = 'not a boolean';
        $result = $this->booleanCaster->cast($input);
        $this->assertFalse($result);
    }

    /**
     * @dataProvider canCastProvider
     */
    public function testCanCast(mixed $input, bool $expected): void
    {
        $result = $this->booleanCaster->canCast($input);
        $this->assertSame($expected, $result);
    }

    public function canCastProvider(): array
    {
        return [
            'true string' => ['true', true],
            'false string' => ['false', true],
            '1 string' => ['1', true],
            '0 string' => ['0', true],
            'yes string' => ['yes', true],
            'no string' => ['no', true],
            'on string' => ['on', true],
            'off string' => ['off', true],
            'TRUE uppercase' => ['TRUE', true],
            'FALSE uppercase' => ['FALSE', true],
            'true boolean' => [true, true],
            'false boolean' => [false, true],
            'non-boolean string' => ['not a boolean', false],
            'integer' => [42, false],
            'array' => [[], false],
        ];
    }
}
