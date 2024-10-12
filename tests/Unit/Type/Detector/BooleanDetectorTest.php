<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Type\Detector;

use KaririCode\Dotenv\Type\Detector\BooleanDetector;
use PHPUnit\Framework\TestCase;

final class BooleanDetectorTest extends TestCase
{
    private BooleanDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new BooleanDetector();
    }

    /**
     * @dataProvider validBooleanProvider
     */
    public function testDetectValidBoolean($input): void
    {
        $this->assertSame('boolean', $this->detector->detect($input));
    }

    public static function validBooleanProvider(): array
    {
        return [
            'true' => [true],
            'false' => [false],
            'string true' => ['true'],
            'string false' => ['false'],
            'string 1' => ['1'],
            'string 0' => ['0'],
            'string yes' => ['yes'],
            'string no' => ['no'],
            'string on' => ['on'],
            'string off' => ['off'],
            'uppercase TRUE' => ['TRUE'],
            'uppercase FALSE' => ['FALSE'],
        ];
    }

    /**
     * @dataProvider invalidBooleanProvider
     */
    public function testDetectInvalidBoolean($input): void
    {
        $this->assertNull($this->detector->detect($input));
    }

    public static function invalidBooleanProvider(): array
    {
        return [
            'string' => ['not a boolean'],
            'number' => [42],
            'array' => [[1, 2, 3]],
            'null' => [null],
            'object' => [new \stdClass()],
        ];
    }

    public function testGetPriority(): void
    {
        $this->assertSame(80, $this->detector->getPriority());
    }
}
