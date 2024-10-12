<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Type\Caster;

use KaririCode\Dotenv\Contract\TypeCaster;
use KaririCode\Dotenv\Type\Caster\TypeCasterRegistry;
use PHPUnit\Framework\TestCase;

final class TypeCasterRegistryTest extends TestCase
{
    private TypeCasterRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new TypeCasterRegistry();
    }

    public function testRegisterAndCast(): void
    {
        $mockCaster = $this->createMock(TypeCaster::class);
        $mockCaster->expects($this->once())
            ->method('cast')
            ->with('input')
            ->willReturn('casted');

        $this->registry->register('test_type', $mockCaster);

        $result = $this->registry->cast('test_type', 'input');
        $this->assertSame('casted', $result);
    }

    public function testCastWithUnregisteredType(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $this->expectExceptionMessage('Key not found: unregistered_type');

        $this->registry->cast('unregistered_type', 'input');
    }

    public function testDefaultCasters(): void
    {
        $testCases = [
            'array' => ['[1,2,3]', [1, 2, 3]],
            'json' => ['{"key":"value"}', ['key' => 'value']],
            'null' => ['null', null],
            'boolean' => ['true', true],
            'integer' => ['42', 42],
            'float' => ['3.14', 3.14],
            'string' => [42, '42'],
        ];

        foreach ($testCases as $type => [$input, $expected]) {
            $result = $this->registry->cast($type, $input);
            $this->assertEquals($expected, $result, "Default caster for '{$type}' should modify the input correctly");
        }
    }

    public function testOverrideDefaultCaster(): void
    {
        $mockCaster = $this->createMock(TypeCaster::class);
        $mockCaster->expects($this->once())
            ->method('cast')
            ->with('input')
            ->willReturn('custom_casted');

        $this->registry->register('string', $mockCaster);

        $result = $this->registry->cast('string', 'input');
        $this->assertSame('custom_casted', $result);
    }

    public function testRegisterNonTypeCompliantCaster(): void
    {
        $this->expectException(\TypeError::class);
        $this->registry->register('non_compliant', new \stdClass());
    }
}
