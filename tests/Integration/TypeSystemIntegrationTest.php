<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Integration;

use KaririCode\Dotenv\Contract\TypeCaster;
use KaririCode\Dotenv\Contract\TypeDetector;
use KaririCode\Dotenv\Type\TypeSystem;
use PHPUnit\Framework\TestCase;

class TypeSystemIntegrationTest extends TestCase
{
    private TypeSystem $typeSystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->typeSystem = new TypeSystem();
    }

    public function testDefaultTypeDetectionAndCasting(): void
    {
        $testCases = [
            ['input' => 'string', 'expected' => 'string'],
            ['input' => '42', 'expected' => 42],
            ['input' => '3.14', 'expected' => 3.14],
            ['input' => 'true', 'expected' => true],
            ['input' => 'null', 'expected' => null],
            ['input' => '[1,2,3]', 'expected' => [1, 2, 3]],
            ['input' => '{"key":"value"}', 'expected' => ['key' => 'value']],
        ];

        foreach ($testCases as $case) {
            $result = $this->typeSystem->processValue($case['input']);
            $this->assertEquals($case['expected'], $result, "Failed processing {$case['input']}");
        }
    }

    public function testCustomTypeDetectorAndCaster(): void
    {
        $customDetector = new class implements TypeDetector {
            public function detect(mixed $value): ?string
            {
                return 'CUSTOM' === $value ? 'custom_type' : null;
            }

            public function getPriority(): int
            {
                return 1000;
            }
        };

        $customCaster = new class implements TypeCaster {
            public function cast(mixed $value): string
            {
                return "Processed: $value";
            }
        };

        $this->typeSystem->registerDetector($customDetector);
        $this->typeSystem->registerCaster('custom_type', $customCaster);

        $result = $this->typeSystem->processValue('CUSTOM');
        $this->assertSame('Processed: CUSTOM', $result);
    }

    public function testDetectorPrioritization(): void
    {
        $lowPriorityDetector = new class implements TypeDetector {
            public function detect(mixed $value): ?string
            {
                return 'low_priority';
            }

            public function getPriority(): int
            {
                return 10;
            }
        };

        $highPriorityDetector = new class implements TypeDetector {
            public function detect(mixed $value): ?string
            {
                return 'high_priority';
            }

            public function getPriority(): int
            {
                return 100;
            }
        };

        $this->typeSystem->registerDetector($lowPriorityDetector);
        $this->typeSystem->registerDetector($highPriorityDetector);

        $this->typeSystem->registerCaster('low_priority', new class implements TypeCaster {
            public function cast(mixed $value): string
            {
                return "Low: $value";
            }
        });

        $this->typeSystem->registerCaster('high_priority', new class implements TypeCaster {
            public function cast(mixed $value): string
            {
                return "High: $value";
            }
        });

        $result = $this->typeSystem->processValue('test');
        $this->assertSame('High: test', $result);
    }
}
