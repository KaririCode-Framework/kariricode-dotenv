<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Type\Detector;

use KaririCode\Dotenv\Type\Detector\NullDetector;
use PHPUnit\Framework\TestCase;

final class NullDetectorTest extends TestCase
{
    private NullDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new NullDetector();
    }

    /**
     * @dataProvider validNullProvider
     */
    public function testDetectValidNull($input): void
    {
        $this->assertSame('null', $this->detector->detect($input));
    }

    public static function validNullProvider(): array
    {
        return [
            'null value' => [null],
            'null string' => ['null'],
            'empty string' => [''],
        ];
    }

    /**
     * @dataProvider invalidNullProvider
     */
    public function testDetectInvalidNull($input): void
    {
        $this->assertNull($this->detector->detect($input));
    }

    public static function invalidNullProvider(): array
    {
        return [
            'string' => ['not null'],
            'number' => ['42'],
            'boolean' => ['true'],
            'array' => ['[1, 2, 3]'],
            'object' => ['{}'],
        ];
    }

    public function testGetPriority(): void
    {
        $this->assertSame(95, $this->detector->getPriority());
    }
}
