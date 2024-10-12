<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Type\Detector;

use KaririCode\Dotenv\Type\Detector\NumericDetector;
use PHPUnit\Framework\TestCase;

final class NumericDetectorTest extends TestCase
{
    private NumericDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new NumericDetector();
    }

    /**
     * @dataProvider validNumericProvider
     */
    public function testDetectValidNumeric($input, string $expected): void
    {
        $this->assertSame($expected, $this->detector->detect($input));
    }

    public static function validNumericProvider(): array
    {
        return [
            'integer' => ['42', 'integer'],
            'negative integer' => ['-42', 'integer'],
            'zero' => ['0', 'integer'],
            'float' => ['3.14', 'float'],
            'negative float' => ['-3.14', 'float'],
            'string integer' => ['42', 'integer'],
            'string float' => ['3.14', 'float'],
        ];
    }

    /**
     * @dataProvider invalidNumericProvider
     */
    public function testDetectInvalidNumeric($input): void
    {
        $this->assertNull($this->detector->detect($input));
    }

    public static function invalidNumericProvider(): array
    {
        return [
            'string' => ['not a number'],
            'boolean' => [true],
            'null' => [null],
            'array' => [[1, 2, 3]],
            'object' => [new \stdClass()],
        ];
    }

    public function testGetPriority(): void
    {
        $this->assertSame(70, $this->detector->getPriority());
    }
}
