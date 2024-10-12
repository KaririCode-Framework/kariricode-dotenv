<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Type\Caster;

use KaririCode\Dotenv\Type\Caster\JsonCaster;
use PHPUnit\Framework\TestCase;

final class JsonCasterTest extends TestCase
{
    private JsonCaster $jsonCaster;

    protected function setUp(): void
    {
        parent::setUp();
        $this->jsonCaster = new JsonCaster();
    }

    /**
     * @dataProvider jsonValuesProvider
     */
    public function testCast(string $input, mixed $expected): void
    {
        $result = $this->jsonCaster->cast($input);
        $this->assertEquals($expected, $result);
    }

    public function jsonValuesProvider(): array
    {
        return [
            'simple object' => ['{"key":"value"}', ['key' => 'value']],
            'nested object' => ['{"outer":{"inner":"value"}}', ['outer' => ['inner' => 'value']]],
            'array' => ['[1,2,3]', [1, 2, 3]],
            'array of objects' => ['[{"id":1},{"id":2}]', [['id' => 1], ['id' => 2]]],
            'string' => ['"hello"', 'hello'],
            'number' => ['42', 42],
            'boolean' => ['true', true],
            'null' => ['null', null],
        ];
    }

    public function testCastInvalidJson(): void
    {
        $input = 'not a json';
        $result = $this->jsonCaster->cast($input);
        $this->assertSame($input, $result);
    }

    public function testCastNonStringValue(): void
    {
        $input = ['already', 'an', 'array'];
        $result = $this->jsonCaster->cast($input);
        $this->assertSame($input, $result);
    }

    public function testCastWithSurroundingQuotes(): void
    {
        $input = '"{"key":"value"}"';
        $expected = ['key' => 'value'];
        $result = $this->jsonCaster->cast($input);
        $this->assertEquals($expected, $result);
    }
}
