<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Type\Detector;

use KaririCode\Dotenv\Contract\TypeDetector;
use KaririCode\Dotenv\Type\Detector\TypeDetectorRegistry;
use PHPUnit\Framework\TestCase;

final class TypeDetectorRegistryTest extends TestCase
{
    private TypeDetectorRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new TypeDetectorRegistry();
    }

    public function testRegisterDetector(): void
    {
        $mockDetector = $this->createMock(TypeDetector::class);
        $mockDetector->method('getPriority')->willReturn(100);
        $mockDetector->method('detect')->willReturn('mock_type');

        $this->registry->registerDetector($mockDetector);

        $result = $this->registry->detectType('test_value');
        $this->assertSame('mock_type', $result);
    }

    public function testDetectTypeWithMultipleDetectors(): void
    {
        $mockDetector1 = $this->createMock(TypeDetector::class);
        $mockDetector1->method('getPriority')->willReturn(50);
        $mockDetector1->method('detect')->willReturn(null);

        $mockDetector2 = $this->createMock(TypeDetector::class);
        $mockDetector2->method('getPriority')->willReturn(100);
        $mockDetector2->method('detect')->willReturn('high_priority_type');

        $mockDetector3 = $this->createMock(TypeDetector::class);
        $mockDetector3->method('getPriority')->willReturn(75);
        $mockDetector3->method('detect')->willReturn('medium_priority_type');

        $this->registry->registerDetector($mockDetector1);
        $this->registry->registerDetector($mockDetector2);
        $this->registry->registerDetector($mockDetector3);

        $result = $this->registry->detectType('test_value');
        $this->assertSame('high_priority_type', $result);
    }

    public function testDetectTypeWithNoMatchingDetectors(): void
    {
        $mockDetector = $this->createMock(TypeDetector::class);
        $mockDetector->method('getPriority')->willReturn(100);
        $mockDetector->method('detect')->willReturn(null);

        $this->registry->registerDetector($mockDetector);

        $result = $this->registry->detectType('test_value');
        $this->assertSame('string', $result);
    }

    public function testDefaultDetectors(): void
    {
        $testCases = [
            'array' => '[1,2,3]',
            'json' => '{"key":"value"}',
            'null' => 'null',
            'boolean' => 'true',
            'integer' => '42',
            'float' => '3.14',
            'string' => 'hello world',
        ];

        foreach ($testCases as $expectedType => $value) {
            $detectedType = $this->registry->detectType($value);
            $this->assertSame($expectedType, $detectedType, "Failed to detect {$expectedType} for value: {$value}");
        }
    }

    public function testDetectorPrioritization(): void
    {
        $lowPriorityDetector = $this->createMock(TypeDetector::class);
        $lowPriorityDetector->method('getPriority')->willReturn(50);
        $lowPriorityDetector->method('detect')->willReturn('low_priority_type');

        $highPriorityDetector = $this->createMock(TypeDetector::class);
        $highPriorityDetector->method('getPriority')->willReturn(150);
        $highPriorityDetector->method('detect')->willReturn('high_priority_type');

        $this->registry->registerDetector($lowPriorityDetector);
        $this->registry->registerDetector($highPriorityDetector);

        $result = $this->registry->detectType('test_value');
        $this->assertSame('high_priority_type', $result);
    }
}
