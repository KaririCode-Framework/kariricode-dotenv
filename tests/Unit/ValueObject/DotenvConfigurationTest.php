<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\Tests\Unit\ValueObject;

use KaririCode\Dotenv\Enum\LoadMode;
use KaririCode\Dotenv\ValueObject\DotenvConfiguration;
use PHPUnit\Framework\TestCase;

final class DotenvConfigurationTest extends TestCase
{
    // ── Defaults ─────────────────────────────────────────────────────

    public function testDefaultValues(): void
    {
        $config = new DotenvConfiguration();

        self::assertSame(LoadMode::Immutable, $config->loadMode);
        self::assertFalse($config->strictNames);
        self::assertTrue($config->typeCasting);
        self::assertTrue($config->populateEnv);
        self::assertTrue($config->populateServer);
        self::assertFalse($config->usePutenv);
        self::assertNull($config->encryptionKey);
        self::assertNull($config->cachePath);
        self::assertSame([], $config->allowList);
        self::assertSame([], $config->denyList);
        self::assertNull($config->environmentName);
    }

    // ── Immutability ─────────────────────────────────────────────────

    public function testIsReadonly(): void
    {
        $config = new DotenvConfiguration();

        $reflection = new \ReflectionClass($config);
        self::assertTrue($reflection->isReadOnly());
    }

    public function testIsFinal(): void
    {
        $reflection = new \ReflectionClass(DotenvConfiguration::class);
        self::assertTrue($reflection->isFinal());
    }

    // ── with* methods return new instances ───────────────────────────

    public function testWithLoadModeReturnsNewInstance(): void
    {
        $original = new DotenvConfiguration();
        $modified = $original->withLoadMode(LoadMode::Overwrite);

        self::assertNotSame($original, $modified);
        self::assertSame(LoadMode::Immutable, $original->loadMode);
        self::assertSame(LoadMode::Overwrite, $modified->loadMode);
        // Other fields preserved
        self::assertSame($original->strictNames, $modified->strictNames);
        self::assertSame($original->typeCasting, $modified->typeCasting);
    }

    public function testWithStrictNamesReturnsNewInstance(): void
    {
        $original = new DotenvConfiguration();
        $modified = $original->withStrictNames(true);

        self::assertNotSame($original, $modified);
        self::assertFalse($original->strictNames);
        self::assertTrue($modified->strictNames);
        self::assertSame($original->loadMode, $modified->loadMode);
    }

    public function testWithTypeCastingReturnsNewInstance(): void
    {
        $original = new DotenvConfiguration();
        $modified = $original->withTypeCasting(false);

        self::assertNotSame($original, $modified);
        self::assertTrue($original->typeCasting);
        self::assertFalse($modified->typeCasting);
    }

    public function testWithEncryptionKeyReturnsNewInstance(): void
    {
        $key = str_repeat('a', 64);
        $original = new DotenvConfiguration();
        $modified = $original->withEncryptionKey($key);

        self::assertNotSame($original, $modified);
        self::assertNull($original->encryptionKey);
        self::assertSame($key, $modified->encryptionKey);
    }

    public function testWithCachePathReturnsNewInstance(): void
    {
        $original = new DotenvConfiguration();
        $modified = $original->withCachePath('/tmp/.env.cache.php');

        self::assertNotSame($original, $modified);
        self::assertNull($original->cachePath);
        self::assertSame('/tmp/.env.cache.php', $modified->cachePath);
    }

    public function testWithAllowListReturnsNewInstance(): void
    {
        $original = new DotenvConfiguration();
        $modified = $original->withAllowList(['DB_*', 'APP_*']);

        self::assertNotSame($original, $modified);
        self::assertSame([], $original->allowList);
        self::assertSame(['DB_*', 'APP_*'], $modified->allowList);
    }

    public function testWithDenyListReturnsNewInstance(): void
    {
        $original = new DotenvConfiguration();
        $modified = $original->withDenyList(['SECRET*']);

        self::assertNotSame($original, $modified);
        self::assertSame([], $original->denyList);
        self::assertSame(['SECRET*'], $modified->denyList);
    }

    public function testWithEnvironmentNameReturnsNewInstance(): void
    {
        $original = new DotenvConfiguration();
        $modified = $original->withEnvironmentName('production');

        self::assertNotSame($original, $modified);
        self::assertNull($original->environmentName);
        self::assertSame('production', $modified->environmentName);
    }

    // ── Chaining preserves all fields ────────────────────────────────

    public function testChainingPreservesAllFields(): void
    {
        $config = new DotenvConfiguration()
            ->withLoadMode(LoadMode::Overwrite)
            ->withStrictNames(true)
            ->withTypeCasting(false)
            ->withEnvironmentName('staging');

        self::assertSame(LoadMode::Overwrite, $config->loadMode);
        self::assertTrue($config->strictNames);
        self::assertFalse($config->typeCasting);
        self::assertSame('staging', $config->environmentName);
        // Untouched defaults preserved
        self::assertNull($config->encryptionKey);
        self::assertSame([], $config->allowList);
    }

    // ── Constructor override ──────────────────────────────────────────

    public function testConstructorOverridesDefaults(): void
    {
        $config = new DotenvConfiguration(
            loadMode: LoadMode::SkipExisting,
            strictNames: true,
            typeCasting: false,
            encryptionKey: 'abc',
        );

        self::assertSame(LoadMode::SkipExisting, $config->loadMode);
        self::assertTrue($config->strictNames);
        self::assertFalse($config->typeCasting);
        self::assertSame('abc', $config->encryptionKey);
    }
}
