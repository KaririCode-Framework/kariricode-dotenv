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

    public function testGetPriority(): void
    {
        $this->assertSame(90, $this->detector->getPriority());
    }
}
