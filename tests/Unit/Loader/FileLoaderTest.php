<?php

declare(strict_types=1);

namespace Tests\Unit\Loader;

use KaririCode\Dotenv\Exception\InvalidFileException;
use KaririCode\Dotenv\Loader\FileLoader;
use PHPUnit\Framework\TestCase;

final class FileLoaderTest extends TestCase
{
    private string $testFilePath;

    protected function setUp(): void
    {
        $this->testFilePath = sys_get_temp_dir() . '/test_env_file';
        file_put_contents($this->testFilePath, "KEY1=value1\nKEY2=value2");
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testFilePath)) {
            unlink($this->testFilePath);
        }
    }

    public function testLoad(): void
    {
        $loader = new FileLoader($this->testFilePath);
        $result = $loader->load();

        $expected = "KEY1=value1\nKEY2=value2";
        $this->assertEquals($expected, $result);
    }

    public function testLoadNonExistentFile(): void
    {
        $this->expectException(InvalidFileException::class);

        $loader = new FileLoader('/non/existent/file');
        $loader->load();
    }

    public function testLoadUnreadableFile(): void
    {
        $this->expectException(InvalidFileException::class);

        chmod($this->testFilePath, 0000);
        $loader = new FileLoader($this->testFilePath);

        try {
            $loader->load();
        } finally {
            chmod($this->testFilePath, 0644);
        }
    }
}
