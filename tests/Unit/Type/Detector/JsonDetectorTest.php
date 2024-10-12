<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Type\Detector;

use KaririCode\Dotenv\Type\Detector\JsonDetector;
use PHPUnit\Framework\TestCase;

final class JsonDetectorTest extends TestCase
{
    private JsonDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new JsonDetector();
    }

    /**
     * @dataProvider validJsonProvider
     */
    public function testDetectValidJson(string $input): void
    {
        $this->assertSame('json', $this->detector->detect($input));
    }

    public static function validJsonProvider(): array
    {
        return [
            'simple object' => ['{"key": "value"}'],
            'nested object' => ['{"outer": {"inner": "value"}}'],
            'array of objects' => ['[{"id": 1}, {"id": 2}]'],
            'complex json' => ['{"name": "John", "age": 30, "city": "New York", "hobbies": ["reading", "cycling"]}'],
            'empty object' => ['{}'],
            'empty array' => ['[]'],
        ];
    }

    /**
     * @dataProvider invalidJsonProvider
     */
    public function testDetectInvalidJson($input): void
    {
        $this->assertNull($this->detector->detect($input));
    }

    public static function invalidJsonProvider(): array
    {
        return [
            'string' => ['not a json'],
            'number' => [42],
            'boolean' => [true],
            'null' => [null],
            'array' => [[1, 2, 3]],
            'invalid json string' => ['{key: "value"}'],
            'incomplete json' => ['{"key": "value"'],
        ];
    }

    /**
     * @dataProvider jsonArrayOfObjectsProvider
     */
    public function testIsJsonArrayOfObjects(string $input, bool $expected): void
    {
        $reflectionMethod = new \ReflectionMethod(JsonDetector::class, 'isJsonArrayOfObjects');
        $reflectionMethod->setAccessible(true);

        $this->assertSame($expected, $reflectionMethod->invoke($this->detector, $input));
    }

    public static function jsonArrayOfObjectsProvider(): array
    {
        return [
            'valid array of objects' => ['[{"id": 1}, {"id": 2}]', true],
            'empty array' => ['[]', true],
            'array with single object' => ['[{"id": 1}]', true],
            'array of mixed types' => ['[{"id": 1}, 2, "string"]', false],
            'array of arrays' => ['[[1, 2], [3, 4]]', false],
            'simple array' => ['[1, 2, 3]', false],
            'object, not array' => ['{"key": "value"}', false],
            'invalid json' => ['not json', false],
            'malformed json array' => ['[{"id": 1}, {"id": 2]', false],
            'json number' => ['42', false],
            'json string' => ['"string"', false],
            'json true' => ['true', false],
            'json false' => ['false', false],
            'json null' => ['null', false],
        ];
    }

    public function testGetPriority(): void
    {
        $this->assertSame(90, $this->detector->getPriority());
    }
}
