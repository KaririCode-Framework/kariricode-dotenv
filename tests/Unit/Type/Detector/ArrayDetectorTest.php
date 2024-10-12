<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Type\Detector;

use KaririCode\Dotenv\Type\Detector\ArrayDetector;
use PHPUnit\Framework\TestCase;

final class ArrayDetectorTest extends TestCase
{
    private ArrayDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new ArrayDetector();
    }

    /**
     * @dataProvider validArrayProvider
     */
    public function testDetectValidArray(string $input): void
    {
        $this->assertSame('array', $this->detector->detect($input));
    }

    public static function validArrayProvider(): array
    {
        return [
            'simple array' => ['[1, 2, 3]'],
            'nested array' => ['[1, [2, 3], 4]'],
            'array with strings' => ['["a", "b", "c"]'],
            'array with mixed types' => ['[1, "two", 3.0, true]'],
            'array with spaces' => ['[ 1, 2, 3 ]'],
            'empty array' => ['[]'],
        ];
    }

    /**
     * @dataProvider invalidArrayProvider
     */
    public function testDetectInvalidArray($input): void
    {
        $this->assertNull($this->detector->detect($input));
    }

    public static function invalidArrayProvider(): array
    {
        return [
            'string' => ['not an array'],
            'number' => [42],
            'boolean' => [true],
            'null' => [null],
            'object notation' => ['{"key": "value"}'],
            'unclosed bracket' => ['[1, 2, 3'],
            'no brackets' => ['1, 2, 3'],
        ];
    }

    public function testGetPriority(): void
    {
        $this->assertSame(100, $this->detector->getPriority());
    }
}
