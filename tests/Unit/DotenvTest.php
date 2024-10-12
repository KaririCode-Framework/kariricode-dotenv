<?php

declare(strict_types=1);

namespace Tests\Unit;

use KaririCode\Dotenv\Contract\Loader;
use KaririCode\Dotenv\Contract\Parser;
use KaririCode\Dotenv\Dotenv;
use KaririCode\Dotenv\Type\TypeSystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DotenvTest extends TestCase
{
    private Loader|MockObject $loader;
    private Parser|MockObject $parser;
    private TypeSystem|MockObject $typeSystem;
    private Dotenv $dotenv;

    protected function setUp(): void
    {
        $this->loader = $this->createMock(Loader::class);
        $this->parser = $this->createMock(Parser::class);
        $this->typeSystem = $this->createMock(TypeSystem::class);
        $this->dotenv = new Dotenv($this->parser, $this->loader, $this->typeSystem);
    }

    public function testLoad(): void
    {
        $this->loader->expects($this->once())
            ->method('load')
            ->willReturn('KEY=value');

        $this->parser->expects($this->once())
            ->method('parse')
            ->with('KEY=value')
            ->willReturn(['KEY' => 'value']);

        $this->typeSystem->expects($this->once())
            ->method('processValue')
            ->with('value')
            ->willReturn('processed_value');

        $this->dotenv->load();

        $this->assertEquals('processed_value', $_ENV['KEY']);
        $this->assertEquals('processed_value', $_SERVER['KEY']);
    }

    public function testAddTypeDetector(): void
    {
        $detector = $this->createMock(\KaririCode\Dotenv\Contract\TypeDetector::class);

        $this->typeSystem->expects($this->once())
            ->method('registerDetector')
            ->with($detector);

        $result = $this->dotenv->addTypeDetector($detector);

        $this->assertSame($this->dotenv, $result);
    }

    public function testAddTypeCaster(): void
    {
        $caster = $this->createMock(\KaririCode\Dotenv\Contract\TypeCaster::class);

        $this->typeSystem->expects($this->once())
            ->method('registerCaster')
            ->with('test_type', $caster);

        $result = $this->dotenv->addTypeCaster('test_type', $caster);

        $this->assertSame($this->dotenv, $result);
    }
}
