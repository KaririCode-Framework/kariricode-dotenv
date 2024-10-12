<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests;

use KaririCode\Dotenv\Contract\Loader;
use KaririCode\Dotenv\Contract\Parser;
use KaririCode\Dotenv\Contract\TypeCaster;
use KaririCode\Dotenv\Contract\TypeDetector;
use KaririCode\Dotenv\Dotenv;
use KaririCode\Dotenv\Exception\InvalidValueException;
use KaririCode\Dotenv\Type\TypeSystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DotenvFunctionalTest extends TestCase
{
    private Dotenv $dotenv;
    private Parser|MockObject $parser;
    private Loader|MockObject $loader;
    private TypeSystem|MockObject $typeSystem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = $this->createMock(Parser::class);
        $this->loader = $this->createMock(Loader::class);
        $this->typeSystem = $this->createMock(TypeSystem::class);
        $this->dotenv = new Dotenv($this->parser, $this->loader, $this->typeSystem);
    }

    public function testLoad(): void
    {
        $envContent = "KEY1=value1\nKEY2=value2";
        $parsedContent = ['KEY1' => 'value1', 'KEY2' => 'value2'];

        $this->loader->expects($this->once())
            ->method('load')
            ->willReturn($envContent);

        $this->parser->expects($this->once())
            ->method('parse')
            ->with($envContent)
            ->willReturn($parsedContent);

        $this->typeSystem->expects($this->exactly(2))
            ->method('processValue')
            ->willReturnArgument(0);

        $this->dotenv->load();

        $this->assertSame('value1', $_ENV['KEY1']);
        $this->assertSame('value2', $_ENV['KEY2']);
        $this->assertSame('value1', $_SERVER['KEY1']);
        $this->assertSame('value2', $_SERVER['KEY2']);
    }

    public function testAddTypeDetector(): void
    {
        $detector = $this->createMock(TypeDetector::class);

        $this->typeSystem->expects($this->once())
            ->method('registerDetector')
            ->with($detector);

        $result = $this->dotenv->addTypeDetector($detector);

        $this->assertSame($this->dotenv, $result);
    }

    public function testAddTypeCaster(): void
    {
        $type = 'custom_type';
        $caster = $this->createMock(TypeCaster::class);

        $this->typeSystem->expects($this->once())
            ->method('registerCaster')
            ->with($type, $caster);

        $result = $this->dotenv->addTypeCaster($type, $caster);

        $this->assertSame($this->dotenv, $result);
    }

    public function testLoadWithTypeProcessing(): void
    {
        $envContent = "KEY1=true\nKEY2=123";
        $parsedContent = ['KEY1' => 'true', 'KEY2' => '123'];

        $this->loader->expects($this->once())
            ->method('load')
            ->willReturn($envContent);

        $this->parser->expects($this->once())
            ->method('parse')
            ->with($envContent)
            ->willReturn($parsedContent);

        $this->typeSystem->expects($this->exactly(2))
            ->method('processValue')
            ->willReturnMap([
                ['true', true],
                ['123', 123],
            ]);

        $this->dotenv->load();

        $this->assertSame(true, $_ENV['KEY1']);
        $this->assertSame(123, $_ENV['KEY2']);
        $this->assertSame(true, $_SERVER['KEY1']);
        $this->assertSame(123, $_SERVER['KEY2']);
    }

    public function testLoadWithInvalidValue(): void
    {
        $envContent = 'INVALID_KEY=invalid_value';
        $parsedContent = ['INVALID_KEY' => 'invalid_value'];

        $this->loader->expects($this->once())
            ->method('load')
            ->willReturn($envContent);

        $this->parser->expects($this->once())
            ->method('parse')
            ->with($envContent)
            ->willReturn($parsedContent);

        $this->typeSystem->expects($this->once())
            ->method('processValue')
            ->willThrowException(new InvalidValueException('Invalid value'));

        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid value');

        $this->dotenv->load();
    }

    public function testLoadWithEmptyContent(): void
    {
        $envContent = '';
        $parsedContent = [];

        $this->loader->expects($this->once())
            ->method('load')
            ->willReturn($envContent);

        $this->parser->expects($this->once())
            ->method('parse')
            ->with($envContent)
            ->willReturn($parsedContent);

        $this->typeSystem->expects($this->never())
            ->method('processValue');

        $this->dotenv->load();

        $this->assertEmpty($_ENV);
        $this->assertEmpty($_SERVER);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $_ENV = [];
        $_SERVER = [];
    }
}
