<?php

declare(strict_types=1);

namespace Tests\Unit;

use KaririCode\Dotenv\Dotenv;
use KaririCode\Dotenv\DotenvFactory;
use KaririCode\Dotenv\Loader\FileLoader;
use KaririCode\Dotenv\Parser\DefaultParser;
use KaririCode\Dotenv\Parser\StrictParser;
use PHPUnit\Framework\TestCase;

final class DotenvFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $path = '/path/to/.env';
        $dotenv = DotenvFactory::create($path);

        $this->assertInstanceOf(Dotenv::class, $dotenv);

        $reflection = new \ReflectionClass($dotenv);

        $loaderProp = $reflection->getProperty('loader');
        $loaderProp->setAccessible(true);
        $loader = $loaderProp->getValue($dotenv);

        $parserProp = $reflection->getProperty('parser');
        $parserProp->setAccessible(true);
        $parser = $parserProp->getValue($dotenv);

        $this->assertInstanceOf(FileLoader::class, $loader);
        $this->assertInstanceOf(DefaultParser::class, $parser);
    }

    public function testCreateWithStrictMode(): void
    {
        $path = '/path/to/.env';
        $dotenv = DotenvFactory::create($path, true);

        $reflection = new \ReflectionClass($dotenv);

        $parserProp = $reflection->getProperty('parser');
        $parserProp->setAccessible(true);
        $parser = $parserProp->getValue($dotenv);

        $this->assertInstanceOf(StrictParser::class, $parser);
    }
}
