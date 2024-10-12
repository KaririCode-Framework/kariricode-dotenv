<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Type\Detector;

use KaririCode\Dotenv\Type\Detector\StringDetector;
use PHPUnit\Framework\TestCase;

final class StringDetectorTest extends TestCase
{
    private StringDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new StringDetector();
    }

    /**
     * @dataProvider validStringProvider
     */
    public function testDetectValidString(string $input): void
    {
        $this->assertSame('string', $this->detector->detect($input));
    }

    public static function validStringProvider(): array
    {
        return [
            'simple string' => ['hello'],
            'empty string' => [''],
            'numeric string' => ['42'],
            'special characters' => ['!@#$%^&*()'],
            'multi-line string' => ["line1\nline2"],
        ];
    }

    /**
     * @dataProvider invalidStringProvider
     */
    public function testDetectInvalidString($input): void
    {
        $this->assertNull($this->detector->detect($input));
    }

    public static function invalidStringProvider(): array
    {
        return [
            'integer' => [42],
            'float' => [3.14],
            'boolean' => [true],
            'null' => [null],
            'array' => [[1, 2, 3]],
            'object' => [new \stdClass()],
        ];
    }

    public function testGetPriority(): void
    {
        $this->assertSame(10, $this->detector->getPriority());
    }
}
