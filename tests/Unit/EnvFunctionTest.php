<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit;

use PHPUnit\Framework\TestCase;

use function KaririCode\Dotenv\env;

final class EnvFunctionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_ENV = [];
        $_SERVER = [];
        putenv('TEST_ENV_VAR=');
    }

    public function testEnvReturnsValueFromEnv(): void
    {
        $_ENV['TEST_VAR'] = 'env_value';
        $this->assertEquals('env_value', env('TEST_VAR'));
    }

    public function testEnvReturnsValueFromServer(): void
    {
        $_SERVER['TEST_VAR'] = 'server_value';
        $this->assertEquals('server_value', env('TEST_VAR'));
    }

    public function testEnvReturnsValueFromGetenv(): void
    {
        putenv('TEST_VAR=getenv_value');
        $this->assertEquals('getenv_value', env('TEST_VAR'));
    }

    public function testEnvPrioritizesEnvOverServerAndGetenv(): void
    {
        $_ENV['TEST_VAR'] = 'env_value';
        $_SERVER['TEST_VAR'] = 'server_value';
        putenv('TEST_VAR=getenv_value');
        $this->assertEquals('env_value', env('TEST_VAR'));
    }

    public function testEnvReturnsDefaultValueWhenNotSet(): void
    {
        $this->assertEquals('default', env('NONEXISTENT_VAR', 'default'));
    }

    public function testEnvReturnsNullForNonexistentVarWithoutDefault(): void
    {
        $this->assertNull(env('NONEXISTENT_VAR'));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_ENV = [];
        $_SERVER = [];
        putenv('TEST_ENV_VAR=');
    }
}
