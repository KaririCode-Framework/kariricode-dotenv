<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Type\Caster;

use KaririCode\DataStructure\Collection\ArrayList;
use KaririCode\Dotenv\Contract\Type\TypeCaster;
use KaririCode\Dotenv\Contract\Type\TypeCasterRegistry;
use KaririCode\Dotenv\Type\Caster\DotenvTypeCasterRegistry;
use PHPUnit\Framework\TestCase;

final class DotenvTypeCasterRegistryTest extends TestCase
{
    private TypeCasterRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new DotenvTypeCasterRegistry();
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

    public function testCastWithVariousTypes(): void
    {
        $testCases = [
            'registered_type' => ['string', 42, '42'],
            'null_type' => ['null', 'null', null],
        ];

        foreach ($testCases as $type => [$castType, $input, $expected]) {
            $result = $this->registry->cast($castType, $input);
            $this->assertEquals($expected, $result, "Casting '{$input}' as '{$castType}' should return '{$expected}'");
        }

        // Test for unregistered type
        $this->expectException(\OutOfRangeException::class);
        $this->registry->cast('unregistered', 'value');
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

    public function testFallbackWhenGetReturnsNonTypeCaster(): void
    {
        $mockArrayList = $this->createMock(ArrayList::class);
        $mockArrayList->method('get')
            ->willReturn(new \stdClass()); // Retorna um objeto que não é TypeCaster

        $reflectionProperty = new \ReflectionProperty(DotenvTypeCasterRegistry::class, 'casters');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->registry, $mockArrayList);

        $input = 'test_value';
        $result = $this->registry->cast('any_type', $input);

        $this->assertSame($input, $result, "Should return original value when get() returns non-TypeCaster");
    }

    public function testFallbackWhenGetReturnsNull(): void
    {
        $mockArrayList = $this->createMock(ArrayList::class);
        $mockArrayList->method('get')
            ->willReturn(null);

        $reflectionProperty = new \ReflectionProperty(DotenvTypeCasterRegistry::class, 'casters');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($this->registry, $mockArrayList);

        $input = 'test_value';
        $result = $this->registry->cast('any_type', $input);

        $this->assertSame($input, $result, "Should return original value when get() returns null");
    }

    public function testRegisterNonTypeCompliantCaster(): void
    {
        $this->expectException(\TypeError::class);
        $this->registry->register('non_compliant', new \stdClass());
    }
}
