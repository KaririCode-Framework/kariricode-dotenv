<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Integration;

use KaririCode\Dotenv\Contract\Type\TypeCaster;
use KaririCode\Dotenv\Contract\Type\TypeDetector;
use KaririCode\Dotenv\DotenvFactory;
use PHPUnit\Framework\TestCase;

final class DotenvIntegrationTest extends TestCase
{
    private string $envFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->envFile = sys_get_temp_dir() . '/test_' . uniqid() . '.env';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->envFile)) {
            unlink($this->envFile);
        }
    }

    public function testLoadEnvironmentVariables(): void
    {
        $envContent = <<<EOT
STRING_VAR=Hello World
INT_VAR=42
FLOAT_VAR=3.14
BOOL_VAR=true
NULL_VAR=null
ARRAY_VAR=[1, 2, 3]
JSON_VAR={"key": "value"}
EOT;
        file_put_contents($this->envFile, $envContent);

        $dotenv = DotenvFactory::create($this->envFile);
        $dotenv->load();

        $this->assertSame('Hello World', $_ENV['STRING_VAR']);
        $this->assertSame(42, $_ENV['INT_VAR']);
        $this->assertSame(3.14, $_ENV['FLOAT_VAR']);
        $this->assertSame(true, $_ENV['BOOL_VAR']);
        $this->assertNull($_ENV['NULL_VAR']);
        $this->assertSame(['1', '2', '3'], $_ENV['ARRAY_VAR']);
        $this->assertSame(['key' => 'value'], $_ENV['JSON_VAR']);
    }

    public function testCustomTypeDetectorAndCaster(): void
    {
        $envContent = 'CUSTOM_VAR=custom_value';
        file_put_contents($this->envFile, $envContent);

        $dotenv = DotenvFactory::create($this->envFile);

        $customDetector = new class implements TypeDetector {
            public function detect(mixed $value): ?string
            {
                return 'custom_value' === $value ? 'custom' : null;
            }

            public function getPriority(): int
            {
                return 1000;
            }
        };

        $customCaster = new class implements TypeCaster {
            public function cast(mixed $value): string
            {
                return strtoupper((string) $value);
            }
        };

        $dotenv->addTypeDetector($customDetector);
        $dotenv->addTypeCaster('custom', $customCaster);
        $dotenv->load();

        $this->assertSame('CUSTOM_VALUE', $_ENV['CUSTOM_VAR']);
    }

    public function testInterpolation(): void
    {
        $envContent = <<<EOT
BASE_URL=http://example.com
API_URL=\${BASE_URL}/api
EOT;
        file_put_contents($this->envFile, $envContent);

        $dotenv = DotenvFactory::create($this->envFile);
        $dotenv->load();

        $this->assertSame('http://example.com/api', $_ENV['API_URL']);
    }
}
