<?php

declare(strict_types=1);

namespace KaririCode\Dotenv;

use KaririCode\Dotenv\Cache\PhpFileCache;
use KaririCode\Dotenv\Contract\TypeCaster;
use KaririCode\Dotenv\Contract\TypeDetector;
use KaririCode\Dotenv\Contract\VariableProcessor;
use KaririCode\Dotenv\Core\DotenvParser;
use KaririCode\Dotenv\Enum\LoadMode;
use KaririCode\Dotenv\Enum\ValueType;
use KaririCode\Dotenv\Exception\FileNotFoundException;
use KaririCode\Dotenv\Exception\ImmutableException;
use KaririCode\Dotenv\Exception\ValidationException;
use KaririCode\Dotenv\Schema\SchemaParser;
use KaririCode\Dotenv\Security\Encryptor;
use KaririCode\Dotenv\Type\TypeSystem;
use KaririCode\Dotenv\Validation\EnvironmentValidator;
use KaririCode\Dotenv\ValueObject\DotenvConfiguration;
use KaririCode\Dotenv\ValueObject\EnvironmentVariable;

/**
 * Production-grade .env file loader for the KaririCode Framework.
 *
 * The first and only PHP dotenv that combines: auto type casting,
 * native AES-256-GCM encryption, OPcache-friendly caching,
 * fluent validation DSL, environment-aware cascade loading,
 * and variable processors — all with zero external dependencies.
 *
 * ## Quick Start
 *
 * ```php
 * $dotenv = new Dotenv('/path/to/project');
 * $dotenv->load();
 *
 * $debug = env('APP_DEBUG'); // bool: true
 * $port  = env('DB_PORT');   // int: 5432
 * ```
 *
 * ## Encrypted .env
 *
 * ```php
 * $config = new DotenvConfiguration(
 *     encryptionKey: $_SERVER['DOTENV_PRIVATE_KEY'] ?? null,
 * );
 * $dotenv = new Dotenv('/path/to/project', $config);
 * $dotenv->load(); // Transparently decrypts "encrypted:..." values
 * ```
 *
 * ## Validation DSL
 *
 * ```php
 * $dotenv->validate()
 *     ->required('DB_HOST', 'DB_PORT')
 *     ->isInteger('DB_PORT')->between(1, 65535)
 *     ->isBoolean('APP_DEBUG')
 *     ->allowedValues('APP_ENV', ['local', 'staging', 'production'])
 *     ->assert();
 * ```
 *
 * ## Environment-Aware Loading
 *
 * ```php
 * $dotenv = new Dotenv('/path/to/project');
 * $dotenv->bootEnv(); // .env → .env.local → .env.{APP_ENV} → .env.{APP_ENV}.local
 * ```
 *
 * ARFA 1.3 Compliance:
 * - P1 Immutable State: Configuration and EnvironmentVariable are readonly.
 * - P3 Adaptive Context: TypeSystem + processors are extensible.
 * - P4 Protocol Agnostic: Parser accepts any string content.
 * - P5 Continuous Observability: debug() provides full variable introspection.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final class Dotenv
{
    private readonly DotenvParser $parser;
    private readonly TypeSystem $typeSystem;
    private readonly DotenvConfiguration $configuration;
    private readonly string $directory;

    /** @var list<string> Resolved file paths to load. */
    private array $filePaths = [];

    /** @var array<string, EnvironmentVariable> Loaded variables keyed by name. */
    private array $variables = [];

    /** @var array<string, list<VariableProcessor>> Pattern → processors. */
    private array $processors = [];

    private ?Encryptor $encryptor = null;
    private bool $loaded = false;

    public function __construct(
        string $directory,
        ?DotenvConfiguration $configuration = null,
        string ...$fileNames,
    ) {
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
        $this->parser = new DotenvParser();
        $this->typeSystem = new TypeSystem();
        $this->configuration = $configuration ?? new DotenvConfiguration();

        // Initialize encryptor if key provided via config or environment
        $encryptionKey = $this->configuration->encryptionKey
            ?? $_SERVER['DOTENV_PRIVATE_KEY'] ?? $_ENV['DOTENV_PRIVATE_KEY'] ?? null;

        if ($encryptionKey !== null && $encryptionKey !== '') {
            $this->encryptor = new Encryptor($encryptionKey);
        }

        if ($fileNames === []) {
            $fileNames = ['.env'];
        }

        foreach ($fileNames as $fileName) {
            $this->filePaths[] = $this->directory . DIRECTORY_SEPARATOR . $fileName;
        }
    }

    // ── Public API ────────────────────────────────────────────────────

    /**
     * Loads all configured .env files. If a cache file exists and is fresh,
     * loads from cache instead (zero parsing cost via OPcache).
     *
     * @throws FileNotFoundException When a required file does not exist.
     * @throws ImmutableException    When a variable conflicts in Immutable mode.
     */
    public function load(): void
    {
        if ($this->loadFromCache()) {
            $this->loaded = true;

            return;
        }

        foreach ($this->filePaths as $filePath) {
            $this->loadFile($filePath, required: true);
        }

        $this->loaded = true;
    }

    /**
     * Loads .env files that exist, silently skipping missing ones.
     */
    public function safeLoad(): void
    {
        if ($this->loadFromCache()) {
            $this->loaded = true;

            return;
        }

        foreach ($this->filePaths as $filePath) {
            $this->loadFile($filePath, required: false);
        }

        $this->loaded = true;
    }

    /**
     * Environment-aware cascade loading (inspired by Symfony's bootEnv).
     *
     * Loads files in order:
     * 1. `.env` — base defaults (committed)
     * 2. `.env.local` — local overrides (gitignored)
     * 3. `.env.{env}` — environment-specific defaults (committed)
     * 4. `.env.{env}.local` — environment-specific local overrides (gitignored)
     *
     * The environment name is resolved from $environmentName parameter,
     * configuration's environmentName, the APP_ENV variable, or defaults to "dev".
     */
    public function bootEnv(?string $environmentName = null): void
    {
        if ($this->loadFromCache()) {
            $this->loaded = true;

            return;
        }

        $basePath = $this->directory . DIRECTORY_SEPARATOR . '.env';

        // 1. Base .env
        $this->loadFile($basePath, required: false);

        // 2. .env.local (always loaded for local overrides)
        $this->loadFile($basePath . '.local', required: false);

        // Determine environment name
        $envName = $environmentName
            ?? $this->configuration->environmentName
            ?? $this->resolveRawValue('APP_ENV')
            ?? 'dev';

        // 3. .env.{env} (committed env-specific defaults)
        $this->loadFile("{$basePath}.{$envName}", required: false);

        // 4. .env.{env}.local (skip for "test" to ensure reproducibility)
        if ($envName !== 'test') {
            $this->loadFile("{$basePath}.{$envName}.local", required: false);
        }

        $this->loaded = true;
    }

    // ── Validation ────────────────────────────────────────────────────

    /**
     * Simple required-presence check (backward compatible with v4.0).
     *
     * @throws ValidationException When any required variable is missing.
     */
    public function required(string ...$names): void
    {
        $missing = array_filter(
            $names,
            fn (string $name): bool => !isset($this->variables[$name])
                && !isset($_ENV[$name])
                && !isset($_SERVER[$name]),
        );

        if ($missing !== []) {
            throw ValidationException::missingRequired(array_values($missing));
        }
    }

    /**
     * Returns a fluent validation builder. Call ->assert() to execute.
     *
     * ```php
     * $dotenv->validate()
     *     ->required('DB_HOST', 'DB_PORT')
     *     ->isInteger('DB_PORT')->between(1, 65535)
     *     ->assert();
     * ```
     */
    public function validate(): EnvironmentValidator
    {
        return new EnvironmentValidator(
            fn (string $name): ?string => $this->resolveRawValue($name),
        );
    }

    /**
     * Loads and applies a .env.schema file for declarative validation.
     *
     * @throws ValidationException   On any schema violation.
     * @throws FileNotFoundException When the schema file is missing.
     */
    public function loadWithSchema(string $schemaPath): void
    {
        if (!$this->loaded) {
            $this->load();
        }

        if (!is_file($schemaPath) || !is_readable($schemaPath)) {
            throw FileNotFoundException::forPath($schemaPath);
        }

        $content = file_get_contents($schemaPath);

        if ($content === false) {
            throw FileNotFoundException::forPath($schemaPath);
        }

        $schemaParser = new SchemaParser();
        $schema = $schemaParser->parse($content);
        $validator = $this->validate();
        $schemaParser->applyToValidator($schema, $validator);
        $validator->assert();
    }

    // ── Accessors ─────────────────────────────────────────────────────

    public function get(string $name, mixed $default = null): mixed
    {
        return isset($this->variables[$name])
            ? $this->variables[$name]->value
            : $default;
    }

    /** @return array<string, EnvironmentVariable> */
    public function variables(): array
    {
        return $this->variables;
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    // ── Debug & Introspection ─────────────────────────────────────────

    /**
     * Returns a debug report with source tracking, types, and override info.
     *
     * @return array<string, array{source: string, rawValue: string, type: string, value: mixed, overridden: bool}>
     */
    public function debug(): array
    {
        $report = [];

        foreach ($this->variables as $name => $var) {
            $report[$name] = [
                'source' => $var->source,
                'rawValue' => $var->rawValue,
                'type' => $var->type->name,
                'value' => $var->value,
                'overridden' => $var->overridden,
            ];
        }

        return $report;
    }

    // ── Cache ─────────────────────────────────────────────────────────

    /**
     * Dumps all loaded variables to a PHP array cache file.
     * OPcache compiles this once — subsequent requests load from shared memory.
     */
    public function dumpCache(string $path): void
    {
        $cache = new PhpFileCache();
        $rawVariables = [];

        foreach ($this->variables as $name => $var) {
            $rawVariables[$name] = $var->rawValue;
        }

        $hash = $cache->computeSourceHash($this->filePaths);
        $cache->dump($path, $rawVariables, $hash);
    }

    public function clearCache(string $path): void
    {
        (new PhpFileCache())->clear($path);
    }

    // ── Extension Points ──────────────────────────────────────────────

    public function addTypeDetector(TypeDetector $detector): void
    {
        $this->typeSystem->addDetector($detector);
    }

    public function addTypeCaster(ValueType $type, TypeCaster $caster): void
    {
        $this->typeSystem->addCaster($type, $caster);
    }

    /**
     * Registers a post-load processor for variables matching a name or glob pattern.
     *
     * @param string            $pattern   Exact name or glob (e.g., "*_URL", "ALLOWED_IPS").
     * @param VariableProcessor $processor Transformer to apply after type casting.
     */
    public function addProcessor(string $pattern, VariableProcessor $processor): void
    {
        $this->processors[$pattern][] = $processor;
    }

    // ── Internal ──────────────────────────────────────────────────────

    private function loadFromCache(): bool
    {
        $cachePath = $this->configuration->cachePath;

        if ($cachePath === null) {
            return false;
        }

        $cache = new PhpFileCache();
        $hash = $cache->computeSourceHash($this->filePaths);
        $cached = $cache->load($cachePath, $hash);

        if ($cached === null) {
            return false;
        }

        foreach ($cached as $name => $rawValue) {
            $this->setVariable($name, $rawValue, 'cache');
        }

        return true;
    }

    private function loadFile(string $filePath, bool $required): void
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            if ($required) {
                throw FileNotFoundException::forPath($filePath);
            }

            return;
        }

        $content = file_get_contents($filePath);

        if ($content === false) {
            if ($required) {
                throw FileNotFoundException::forPath($filePath);
            }

            return;
        }

        $rawVariables = $this->parser->parse(
            $content,
            $filePath,
            $this->configuration->strictNames,
        );

        $source = basename($filePath);

        foreach ($rawVariables as $name => $rawValue) {
            $this->setVariable($name, $rawValue, $source);
        }
    }

    private function setVariable(string $name, string $rawValue, string $source): void
    {
        // Allow/Deny list filtering
        if (!$this->isAllowed($name)) {
            return;
        }

        $alreadyExists = isset($_ENV[$name]) || isset($_SERVER[$name]) || getenv($name) !== false;

        // Immutable: throw if variable existed BEFORE this instance loaded it
        if ($this->configuration->loadMode === LoadMode::Immutable
            && $alreadyExists
            && !isset($this->variables[$name])
        ) {
            throw ImmutableException::alreadyDefined($name);
        }

        // SkipExisting: silently skip pre-existing environment variables
        if ($this->configuration->loadMode === LoadMode::SkipExisting && $alreadyExists) {
            return;
        }

        // Decrypt encrypted values transparently
        $decryptedValue = $rawValue;
        if ($this->encryptor !== null && Encryptor::isEncrypted($rawValue)) {
            $decryptedValue = $this->encryptor->decrypt($rawValue);
        }

        // Type detection and casting
        $type = ValueType::String;
        $typedValue = $decryptedValue;

        if ($this->configuration->typeCasting) {
            $type = $this->typeSystem->detect($decryptedValue);
            $typedValue = $this->typeSystem->cast($decryptedValue, $type);
        }

        // Apply registered processors
        $typedValue = $this->applyProcessors($name, $decryptedValue, $typedValue);

        $overridden = isset($this->variables[$name]);
        $this->variables[$name] = new EnvironmentVariable(
            $name, $decryptedValue, $type, $typedValue, $source, $overridden,
        );

        // Populate environment with decrypted raw string
        if ($this->configuration->populateEnv) {
            $_ENV[$name] = $decryptedValue;
        }

        if ($this->configuration->populateServer) {
            $_SERVER[$name] = $decryptedValue;
        }

        if ($this->configuration->usePutenv) {
            putenv("{$name}={$decryptedValue}");
        }
    }

    private function isAllowed(string $name): bool
    {
        // Deny list takes precedence
        foreach ($this->configuration->denyList as $pattern) {
            if ($this->matchGlob($pattern, $name)) {
                return false;
            }
        }

        // Empty allow list means everything allowed
        if ($this->configuration->allowList === []) {
            return true;
        }

        foreach ($this->configuration->allowList as $pattern) {
            if ($this->matchGlob($pattern, $name)) {
                return true;
            }
        }

        return false;
    }

    private function matchGlob(string $pattern, string $name): bool
    {
        if ($pattern === $name) {
            return true;
        }

        $regex = '/\A' . str_replace(
            ['\*', '\?'],
            ['.*', '.'],
            preg_quote($pattern, '/'),
        ) . '\z/';

        return preg_match($regex, $name) === 1;
    }

    private function applyProcessors(string $name, string $rawValue, mixed $typedValue): mixed
    {
        foreach ($this->processors as $pattern => $processorList) {
            if ($this->matchGlob($pattern, $name)) {
                foreach ($processorList as $processor) {
                    $typedValue = $processor->process($rawValue, $typedValue);
                }
            }
        }

        return $typedValue;
    }

    /**
     * @return null|scalar|string[]
     *
     * @psalm-return non-empty-list<string>|null|scalar
     */
    private function resolveRawValue(string $name)
    {
        if (isset($this->variables[$name])) {
            return $this->variables[$name]->rawValue;
        }

        return $_ENV[$name] ?? $_SERVER[$name] ?? null;
    }
}
