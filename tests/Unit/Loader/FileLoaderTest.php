<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Loader;

use KaririCode\Dotenv\Exception\InvalidFileException;
use KaririCode\Dotenv\Loader\FileLoader;
use PHPUnit\Framework\TestCase;

final class FileLoaderTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/dotenv_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->removeDirectory($this->tempDir);
    }

    public function testLoad(): void
    {
        $filePath = $this->tempDir . '/test.env';
        file_put_contents($filePath, 'TEST_KEY=test_value');

        $loader = new FileLoader($filePath);
        $result = $loader->load();

        $this->assertSame('TEST_KEY=test_value', $result);
    }

    public function testLoadNonExistentFile(): void
    {
        $filePath = $this->tempDir . '/non_existent.env';

        $loader = new FileLoader($filePath);

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('The environment file ' . $filePath . ' does not exist.');

        $loader->load();
    }

    public function testLoadUnreadableFile(): void
    {
        $filePath = $this->tempDir . '/unreadable.env';
        file_put_contents($filePath, 'TEST_KEY=test_value');

        $loader = $this->getMockBuilder(FileLoader::class)
            ->setConstructorArgs([$filePath])
            ->onlyMethods(['getFileContents'])
            ->getMock();

        $loader->expects($this->once())
            ->method('getFileContents')
            ->willReturn(false);

        $this->expectException(InvalidFileException::class);
        $this->expectExceptionMessage('Unable to read the environment file at ' . $filePath);

        $loader->load();
    }

    private function removeDirectory(string $dir): void
    {
        if (!file_exists($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
