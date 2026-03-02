<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Integration;

use KaririCode\Dotenv\Dotenv;
use KaririCode\Dotenv\Enum\LoadMode;
use KaririCode\Dotenv\Exception\FileNotFoundException;
use KaririCode\Dotenv\Exception\ValidationException;
use KaririCode\Dotenv\Processor\CsvToArrayProcessor;
use KaririCode\Dotenv\Processor\UrlNormalizerProcessor;
use KaririCode\Dotenv\Security\Encryptor;
use KaririCode\Dotenv\Security\KeyPair;
use KaririCode\Dotenv\ValueObject\DotenvConfiguration;
use PHPUnit\Framework\TestCase;

use function KaririCode\Dotenv\env;

final class DotenvIntegrationTest extends TestCase
{
    private string $fixturesDir;

    /** @var array<string, string> $_ENV snapshot before each test. */
    private array $envSnapshot;

    /** @var array<string, mixed> $_SERVER snapshot before each test. */
    private array $serverSnapshot;

    protected function setUp(): void
    {
        $this->fixturesDir = sys_get_temp_dir() . '/kariricode-dotenv-test-' . uniqid();
        mkdir($this->fixturesDir, 0755, true);

        // Snapshot environment to restore in tearDown
        $this->envSnapshot = $_ENV;
        $this->serverSnapshot = $_SERVER;
    }

    protected function tearDown(): void
    {
        // Restore $_ENV and $_SERVER to pre-test state
        $addedEnvKeys = array_diff_key($_ENV, $this->envSnapshot);
        foreach ($addedEnvKeys as $key => $_) {
            unset($_ENV[$key]);
            putenv($key);
        }

        $addedServerKeys = array_diff_key($_SERVER, $this->serverSnapshot);
        foreach ($addedServerKeys as $key => $_) {
            unset($_SERVER[$key]);
        }

        // Restore any values that were overwritten
        $_ENV = $this->envSnapshot;
        $_SERVER = $this->serverSnapshot;

        $this->removeDirectory($this->fixturesDir);
    }

    // ── Loading ───────────────────────────────────────────────────────

    public function testLoadsEnvFile(): void
    {
        $this->createEnvFile('.env', <<<'ENV'
            TEST_STRING=hello world
            TEST_INT=42
            TEST_FLOAT=3.14
            TEST_BOOL=true
            TEST_NULL=null
            TEST_JSON={"key": "value"}
            TEST_ARRAY=["a", "b", "c"]
            ENV);

        $dotenv = new Dotenv($this->fixturesDir);
        $dotenv->load();

        self::assertTrue($dotenv->isLoaded());

        // Typed values via get()
        self::assertSame('hello world', $dotenv->get('TEST_STRING'));
        self::assertSame(42, $dotenv->get('TEST_INT'));
        self::assertSame(3.14, $dotenv->get('TEST_FLOAT'));
        self::assertTrue($dotenv->get('TEST_BOOL'));
        self::assertNull($dotenv->get('TEST_NULL'));
        self::assertSame(['key' => 'value'], $dotenv->get('TEST_JSON'));
        self::assertSame(['a', 'b', 'c'], $dotenv->get('TEST_ARRAY'));

        // Raw values in $_ENV
        self::assertSame('42', $_ENV['TEST_INT']);
        self::assertSame('true', $_ENV['TEST_BOOL']);
    }

    public function testEnvHelperFunction(): void
    {
        $this->createEnvFile('.env', <<<'ENV'
            TEST_INT=99
            TEST_BOOL=false
            ENV);

        $dotenv = new Dotenv($this->fixturesDir);
        $dotenv->load();

        self::assertSame(99, env('TEST_INT'));
        self::assertFalse(env('TEST_BOOL'));
        self::assertSame('default', env('NONEXISTENT', 'default'));
    }

    public function testVariableInterpolation(): void
    {
        $this->createEnvFile('.env', <<<'ENV'
            APP_NAME=KaririCode
            GREETING="Welcome to ${APP_NAME}"
            ENV);

        $dotenv = new Dotenv($this->fixturesDir);
        $dotenv->load();

        self::assertSame('Welcome to KaririCode', $dotenv->get('GREETING'));
    }

    // ── Load Modes ────────────────────────────────────────────────────

    public function testSkipExistingMode(): void
    {
        $_ENV['IMMUTABLE_VAR'] = 'original';

        $this->createEnvFile('.env', "IMMUTABLE_VAR=overwritten");

        $config = new DotenvConfiguration(loadMode: LoadMode::SkipExisting);
        $dotenv = new Dotenv($this->fixturesDir, $config);
        $dotenv->load();

        self::assertSame('original', $_ENV['IMMUTABLE_VAR']);
    }

    public function testOverwriteMode(): void
    {
        $_ENV['IMMUTABLE_VAR'] = 'original';

        $this->createEnvFile('.env', "IMMUTABLE_VAR=overwritten");

        $config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
        $dotenv = new Dotenv($this->fixturesDir, $config);
        $dotenv->load();

        self::assertSame('overwritten', $_ENV['IMMUTABLE_VAR']);
    }

    // ── Validation ────────────────────────────────────────────────────

    public function testRequiredVariablesPass(): void
    {
        $this->createEnvFile('.env', "FOO=bar\nBAZ=qux");

        $dotenv = new Dotenv($this->fixturesDir);
        $dotenv->load();

        // Should not throw
        $dotenv->required('FOO', 'BAZ');

        self::assertTrue(true); // Reached without exception
    }

    public function testRequiredVariablesThrowOnMissing(): void
    {
        $this->createEnvFile('.env', 'FOO=bar');

        $dotenv = new Dotenv($this->fixturesDir);
        $dotenv->load();

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('MISSING_VAR');

        $dotenv->required('FOO', 'MISSING_VAR');
    }

    // ── Error Handling ────────────────────────────────────────────────

    public function testThrowsOnMissingFile(): void
    {
        $this->expectException(FileNotFoundException::class);

        $dotenv = new Dotenv($this->fixturesDir . '/nonexistent');
        $dotenv->load();
    }

    public function testSafeLoadSkipsMissingFile(): void
    {
        $dotenv = new Dotenv($this->fixturesDir . '/nonexistent');
        $dotenv->safeLoad();

        self::assertTrue($dotenv->isLoaded());
        self::assertSame([], $dotenv->variables());
    }

    // ── Multiple Files ────────────────────────────────────────────────

    public function testLoadsMultipleFiles(): void
    {
        $this->createEnvFile('.env', "FOO=base\nBAR=base");
        $this->createEnvFile('.env.local', 'BAR=override');

        $config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
        $dotenv = new Dotenv($this->fixturesDir, $config, '.env', '.env.local');
        $dotenv->load();

        self::assertSame('base', $dotenv->get('FOO'));
        self::assertSame('override', $dotenv->get('BAR'));
    }

    // ── Configuration ─────────────────────────────────────────────────

    public function testDisableTypeCasting(): void
    {
        $this->createEnvFile('.env', 'TEST_INT=42');

        $config = new DotenvConfiguration(typeCasting: false);
        $dotenv = new Dotenv($this->fixturesDir, $config);
        $dotenv->load();

        self::assertSame('42', $dotenv->get('TEST_INT'));
    }

    // ── Fluent Validation DSL ─────────────────────────────────────────

    public function testValidationDslPasses(): void
    {
        $this->createEnvFile('.env', <<<'ENV'
            DB_HOST=localhost
            DB_PORT=5432
            APP_DEBUG=true
            APP_ENV=production
            APP_URL=https://example.com
            ADMIN_EMAIL=admin@example.com
            ENV);

        $config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
        $dotenv = new Dotenv($this->fixturesDir, $config);
        $dotenv->load();

        $dotenv->validate()
            ->required('DB_HOST', 'DB_PORT')
            ->notEmpty('DB_HOST')
            ->isInteger('DB_PORT')->between(1, 65535)
            ->isBoolean('APP_DEBUG')
            ->allowedValues('APP_ENV', ['local', 'staging', 'production'])
            ->url('APP_URL')
            ->email('ADMIN_EMAIL')
            ->assert();

        self::assertTrue(true);
    }

    public function testValidationDslCollectsAllErrors(): void
    {
        $this->createEnvFile('.env', <<<'ENV'
            DB_PORT=invalid
            APP_ENV=wrong
            ENV);

        $config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
        $dotenv = new Dotenv($this->fixturesDir, $config);
        $dotenv->load();

        try {
            $dotenv->validate()
                ->required('DB_HOST', 'DB_PORT')
                ->isInteger('DB_PORT')
                ->allowedValues('APP_ENV', ['local', 'staging', 'production'])
                ->assert();
            self::fail('Expected ValidationException');
        } catch (ValidationException $e) {
            // DB_HOST missing + DB_PORT not integer + APP_ENV not in allowed
            self::assertCount(3, $e->errors());
        }
    }

    public function testValidationIfPresentSkipsMissing(): void
    {
        $this->createEnvFile('.env', 'DB_HOST=localhost');

        $dotenv = new Dotenv($this->fixturesDir);
        $dotenv->load();

        $dotenv->validate()
            ->ifPresent('REDIS_HOST')->notEmpty()
            ->assert();

        self::assertTrue(true);
    }

    public function testValidationCustomCallback(): void
    {
        $this->createEnvFile('.env', 'DB_HOST=pgsql:host=localhost');

        $dotenv = new Dotenv($this->fixturesDir);
        $dotenv->load();

        $dotenv->validate()
            ->custom('DB_HOST', fn (string $v): bool => str_starts_with($v, 'pgsql:'))
            ->assert();

        self::assertTrue(true);
    }

    // ── Encryption ────────────────────────────────────────────────────

    public function testEncryptionRoundTrip(): void
    {
        $keyPair = KeyPair::generate();
        $encryptor = new Encryptor($keyPair->privateKey);

        // Create .env with encrypted value
        $encryptedSecret = $encryptor->encrypt('my-secret-password');
        $this->createEnvFile('.env', "SECRET={$encryptedSecret}\nFOO=plain");

        $config = new DotenvConfiguration(encryptionKey: $keyPair->privateKey);
        $dotenv = new Dotenv($this->fixturesDir, $config);
        $dotenv->load();

        self::assertSame('my-secret-password', $dotenv->get('SECRET'));
        self::assertSame('plain', $dotenv->get('FOO'));

        // $_ENV should also have decrypted value
        self::assertSame('my-secret-password', $_ENV['SECRET']);
    }

    public function testPlaintextValuesPassThroughWithEncryptionEnabled(): void
    {
        $keyPair = KeyPair::generate();

        $this->createEnvFile('.env', 'FOO=plaintext-value');

        $config = new DotenvConfiguration(encryptionKey: $keyPair->privateKey);
        $dotenv = new Dotenv($this->fixturesDir, $config);
        $dotenv->load();

        self::assertSame('plaintext-value', $dotenv->get('FOO'));
    }

    // ── Cache ─────────────────────────────────────────────────────────

    public function testCacheDumpAndLoad(): void
    {
        $this->createEnvFile('.env', "DB_HOST=localhost\nDB_PORT=5432");

        $cachePath = $this->fixturesDir . '/.env.cache.php';

        // First: load and dump cache
        $dotenv1 = new Dotenv($this->fixturesDir);
        $dotenv1->load();
        $dotenv1->dumpCache($cachePath);

        self::assertFileExists($cachePath);

        // Clean env for second load
        unset($_ENV['DB_HOST'], $_ENV['DB_PORT'], $_SERVER['DB_HOST'], $_SERVER['DB_PORT']);
        putenv('DB_HOST');
        putenv('DB_PORT');

        // Second: load from cache
        $config = new DotenvConfiguration(
            loadMode: LoadMode::Overwrite,
            cachePath: $cachePath,
        );
        $dotenv2 = new Dotenv($this->fixturesDir, $config);
        $dotenv2->load();

        self::assertSame('localhost', $dotenv2->get('DB_HOST'));
        self::assertSame(5432, $dotenv2->get('DB_PORT'));
    }

    public function testCacheClear(): void
    {
        $cachePath = $this->fixturesDir . '/.env.cache.php';
        file_put_contents($cachePath, '<?php return [];');

        $dotenv = new Dotenv($this->fixturesDir);
        $dotenv->clearCache($cachePath);

        self::assertFileDoesNotExist($cachePath);
    }

    // ── Environment-Aware Loading (bootEnv) ───────────────────────────

    public function testBootEnvCascadeLoading(): void
    {
        $this->createEnvFile('.env', "APP_ENV=staging\nDB_HOST=base-host\nDB_PORT=5432");
        $this->createEnvFile('.env.local', 'DB_HOST=local-host');
        $this->createEnvFile('.env.staging', 'DB_PORT=5433');
        $this->createEnvFile('.env.staging.local', 'DB_NAME=staging_local_db');

        $config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
        $dotenv = new Dotenv($this->fixturesDir, $config);
        $dotenv->bootEnv();

        // base → .env.local overrides DB_HOST → .env.staging overrides DB_PORT
        self::assertSame('local-host', $dotenv->get('DB_HOST'));
        self::assertSame(5433, $dotenv->get('DB_PORT'));
        self::assertSame('staging_local_db', $dotenv->get('DB_NAME'));
    }

    public function testBootEnvWithExplicitEnvironment(): void
    {
        $this->createEnvFile('.env', "DB_HOST=base\nAPP_ENV=ignored");
        $this->createEnvFile('.env.production', 'DB_HOST=prod-host');

        $config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
        $dotenv = new Dotenv($this->fixturesDir, $config);
        $dotenv->bootEnv('production');

        self::assertSame('prod-host', $dotenv->get('DB_HOST'));
    }

    public function testBootEnvSkipsTestLocalFile(): void
    {
        $this->createEnvFile('.env', 'APP_ENV=test');
        $this->createEnvFile('.env.test', 'DB_HOST=test-host');
        $this->createEnvFile('.env.test.local', 'DB_HOST=should-not-load');

        $config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
        $dotenv = new Dotenv($this->fixturesDir, $config);
        $dotenv->bootEnv('test');

        // .env.test.local is skipped for "test" environment
        self::assertSame('test-host', $dotenv->get('DB_HOST'));
    }

    public function testBootEnvReadsAppEnvFromEnvironment(): void
    {
        $_ENV['APP_ENV'] = 'production';

        $this->createEnvFile('.env', 'DB_HOST=base');
        $this->createEnvFile('.env.production', 'DB_HOST=prod-host');

        $config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
        $dotenv = new Dotenv($this->fixturesDir, $config);
        $dotenv->bootEnv();

        self::assertSame('prod-host', $dotenv->get('DB_HOST'));
    }

    // ── Allow/Deny List ───────────────────────────────────────────────

    public function testAllowListFilters(): void
    {
        $this->createEnvFile('.env', "DB_HOST=localhost\nDB_PORT=5432\nSECRET=hidden\nAPI_KEY=abc123");

        $config = new DotenvConfiguration(
            loadMode: LoadMode::Overwrite,
            allowList: ['DB_*'],
        );
        $dotenv = new Dotenv($this->fixturesDir, $config);
        $dotenv->load();

        self::assertSame('localhost', $dotenv->get('DB_HOST'));
        self::assertSame(5432, $dotenv->get('DB_PORT'));
        self::assertNull($dotenv->get('SECRET'));
        self::assertNull($dotenv->get('API_KEY'));
    }

    public function testDenyListFilters(): void
    {
        $this->createEnvFile('.env', "DB_HOST=localhost\nSECRET=hidden\nAPI_KEY=abc123");

        $config = new DotenvConfiguration(
            loadMode: LoadMode::Overwrite,
            denyList: ['SECRET*'],
        );
        $dotenv = new Dotenv($this->fixturesDir, $config);
        $dotenv->load();

        self::assertSame('localhost', $dotenv->get('DB_HOST'));
        self::assertSame('abc123', $dotenv->get('API_KEY'));
        self::assertNull($dotenv->get('SECRET'));
    }

    // ── Processors ────────────────────────────────────────────────────

    public function testCsvProcessorSplitsValues(): void
    {
        $this->createEnvFile('.env', 'ALLOWED_IPS=192.168.1.1, 10.0.0.1, 172.16.0.1');

        $dotenv = new Dotenv($this->fixturesDir);
        $dotenv->addProcessor('ALLOWED_IPS', new CsvToArrayProcessor());
        $dotenv->load();

        self::assertSame(
            ['192.168.1.1', '10.0.0.1', '172.16.0.1'],
            $dotenv->get('ALLOWED_IPS'),
        );
    }

    public function testGlobPatternProcessor(): void
    {
        $this->createEnvFile('.env', "APP_URL=https://example.com\nAPI_KEY=secret");

        $dotenv = new Dotenv($this->fixturesDir);
        $dotenv->addProcessor('*_URL', new UrlNormalizerProcessor());
        $dotenv->load();

        self::assertSame('https://example.com/', $dotenv->get('APP_URL'));
        self::assertSame('secret', $dotenv->get('API_KEY')); // Not affected
    }

    // ── Schema Validation ─────────────────────────────────────────────

    public function testSchemaValidationPasses(): void
    {
        $this->createEnvFile('.env', "DB_HOST=localhost\nDB_PORT=5432\nAPP_ENV=production");

        $schemaPath = $this->fixturesDir . '/.env.schema';
        file_put_contents($schemaPath, <<<'SCHEMA'
            [DB_HOST]
            required = true
            notEmpty = true

            [DB_PORT]
            required = true
            type = integer
            min = 1
            max = 65535

            [APP_ENV]
            required = true
            allowed = local, staging, production
            SCHEMA);

        $config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
        $dotenv = new Dotenv($this->fixturesDir, $config);
        $dotenv->loadWithSchema($schemaPath);

        self::assertTrue(true);
    }

    public function testSchemaValidationFails(): void
    {
        $this->createEnvFile('.env', "DB_PORT=invalid\nAPP_ENV=wrong");

        $schemaPath = $this->fixturesDir . '/.env.schema';
        file_put_contents($schemaPath, <<<'SCHEMA'
            [DB_HOST]
            required = true

            [DB_PORT]
            required = true
            type = integer

            [APP_ENV]
            allowed = local, staging, production
            SCHEMA);

        $config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
        $dotenv = new Dotenv($this->fixturesDir, $config);

        $this->expectException(ValidationException::class);

        $dotenv->loadWithSchema($schemaPath);
    }

    // ── Debug / Introspection ─────────────────────────────────────────

    public function testDebugReturnsSourceTracking(): void
    {
        $this->createEnvFile('.env', "DB_HOST=localhost\nDB_PORT=5432");

        $dotenv = new Dotenv($this->fixturesDir);
        $dotenv->load();

        $debug = $dotenv->debug();

        self::assertArrayHasKey('DB_HOST', $debug);
        self::assertSame('.env', $debug['DB_HOST']['source']);
        self::assertSame('String', $debug['DB_HOST']['type']);
        self::assertSame('localhost', $debug['DB_HOST']['value']);
        self::assertFalse($debug['DB_HOST']['overridden']);
    }

    public function testDebugTracksOverrides(): void
    {
        $this->createEnvFile('.env', 'DB_HOST=base');
        $this->createEnvFile('.env.local', 'DB_HOST=override');

        $config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
        $dotenv = new Dotenv($this->fixturesDir, $config, '.env', '.env.local');
        $dotenv->load();

        $debug = $dotenv->debug();

        self::assertSame('.env.local', $debug['DB_HOST']['source']);
        self::assertTrue($debug['DB_HOST']['overridden']);
        self::assertSame('override', $debug['DB_HOST']['value']);
    }

    // ── ${VAR:+alternate} Syntax ──────────────────────────────────────

    public function testAlternateSyntaxInIntegration(): void
    {
        $this->createEnvFile('.env', "REDIS_HOST=redis.local\nHAS_CACHE=\${REDIS_HOST:+yes}");

        $dotenv = new Dotenv($this->fixturesDir);
        $dotenv->load();

        // TypeSystem casts 'yes' → bool true; verify raw value also carried correctly
        $vars = $dotenv->variables();
        self::assertArrayHasKey('HAS_CACHE', $vars);
        self::assertSame('yes', $vars['HAS_CACHE']->rawValue);
        // The typed value 'yes' is cast to boolean true by TypeSystem
        self::assertTrue($dotenv->get('HAS_CACHE'));
    }

    // ── Helpers ───────────────────────────────────────────────────────

    private function createEnvFile(string $filename, string $content): void
    {
        file_put_contents(
            $this->fixturesDir . '/' . $filename,
            ltrim($content),
        );
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($dir);
    }
}
