<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\Cache;

use KaririCode\Dotenv\Cache\PhpFileCache;
use PHPUnit\Framework\TestCase;

final class PhpFileCacheTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/kariricode_dotenv_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        // glob('*') misses dotfiles (e.g. .env.cache.php) — use iterator instead.
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->tempDir);
    }

    public function testDumpAndLoadRoundTrip(): void
    {
        $cache = new PhpFileCache();
        $path = $this->tempDir . '/.env.cache.php';
        $variables = [
            'DB_HOST' => 'localhost',
            'DB_PORT' => '5432',
            'APP_DEBUG' => 'true',
        ];

        $cache->dump($path, $variables, 'abc123');

        $loaded = $cache->load($path, 'abc123');

        $this->assertSame($variables, $loaded);
    }

    public function testLoadReturnsNullWhenFileMissing(): void
    {
        $cache = new PhpFileCache();
        $this->assertNull($cache->load($this->tempDir . '/nonexistent.php'));
    }

    public function testLoadReturnsNullWhenHashMismatch(): void
    {
        $cache = new PhpFileCache();
        $path = $this->tempDir . '/.env.cache.php';

        $cache->dump($path, ['KEY' => 'value'], 'hash_v1');

        // Load with different expected hash
        $this->assertNull($cache->load($path, 'hash_v2'));
    }

    public function testLoadIgnoresHashWhenExpectedIsEmpty(): void
    {
        $cache = new PhpFileCache();
        $path = $this->tempDir . '/.env.cache.php';

        $cache->dump($path, ['KEY' => 'value'], 'some_hash');

        $loaded = $cache->load($path, '');
        $this->assertSame(['KEY' => 'value'], $loaded);
    }

    public function testClearRemovesFile(): void
    {
        $cache = new PhpFileCache();
        $path = $this->tempDir . '/.env.cache.php';

        $cache->dump($path, ['KEY' => 'value']);
        $this->assertFileExists($path);

        $cache->clear($path);
        $this->assertFileDoesNotExist($path);
    }

    public function testClearSilentlyIgnoresMissingFile(): void
    {
        $cache = new PhpFileCache();
        $cache->clear($this->tempDir . '/nonexistent.php');
        $this->assertTrue(true);
    }

    public function testComputeSourceHashDeterministic(): void
    {
        $envFile = $this->tempDir . '/.env';
        file_put_contents($envFile, "DB_HOST=localhost\n");

        $cache = new PhpFileCache();

        $hash1 = $cache->computeSourceHash([$envFile]);
        $hash2 = $cache->computeSourceHash([$envFile]);

        $this->assertSame($hash1, $hash2);
    }

    public function testComputeSourceHashChangesWhenFileChanges(): void
    {
        $envFile = $this->tempDir . '/.env';
        file_put_contents($envFile, "DB_HOST=localhost\n");

        $cache = new PhpFileCache();
        $hash1 = $cache->computeSourceHash([$envFile]);

        // Touch to change mtime
        sleep(1);
        file_put_contents($envFile, "DB_HOST=production\n");
        clearstatcache();

        $hash2 = $cache->computeSourceHash([$envFile]);

        $this->assertNotSame($hash1, $hash2);
    }

    public function testDumpedFileIsValidPhp(): void
    {
        $cache = new PhpFileCache();
        $path = $this->tempDir . '/.env.cache.php';

        $cache->dump($path, ['A' => 'B'], 'hash');

        $content = file_get_contents($path);
        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('Auto-generated', $content);
        $this->assertStringContainsString("'A' => 'B'", $content);
    }

    public function testDumpHandlesSpecialCharacters(): void
    {
        $cache = new PhpFileCache();
        $path = $this->tempDir . '/.env.cache.php';

        $variables = [
            'DSN' => "pgsql:host=localhost;dbname=ar_online",
            'QUOTE' => "He said \"hello\"",
            'NEWLINE' => "line1\nline2",
        ];

        $cache->dump($path, $variables, 'hash');
        $loaded = $cache->load($path, 'hash');

        $this->assertSame($variables, $loaded);
    }
}
