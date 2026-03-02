<?php

declare(strict_types=1);

namespace KaririCode\Dotenv\ValueObject;

use KaririCode\Dotenv\Enum\LoadMode;

/**
 * Immutable configuration for the Dotenv loader.
 *
 * Controls parsing strictness, load mode, type casting behavior,
 * environment population targets, encryption, caching, and access filtering.
 *
 * ARFA 1.3 P1: Immutable — all mutations return new instances.
 *
 * @package KaririCode\Dotenv
 * @since   4.0.0 ARFA 1.3
 */
final readonly class DotenvConfiguration
{
    /**
     * @param LoadMode      $loadMode        How to handle pre-existing variables.
     * @param bool          $strictNames     Enforce [A-Z][A-Z0-9_]* naming convention.
     * @param bool          $typeCasting     Enable automatic type detection and casting.
     * @param bool          $populateEnv     Write to $_ENV superglobal.
     * @param bool          $populateServer  Write to $_SERVER superglobal.
     * @param bool          $usePutenv       Call putenv() — disabled by default for thread safety.
     * @param string|null   $encryptionKey   Hex-encoded 256-bit key for encrypted values.
     * @param string|null   $cachePath       Path to .env.cache.php for OPcache optimization.
     * @param list<string>  $allowList       Glob patterns for allowed variable names (empty = all).
     * @param list<string>  $denyList        Glob patterns for denied variable names.
     * @param string|null   $environmentName Environment name for cascade loading (e.g., "production").
     */
    public function __construct(
        public LoadMode $loadMode = LoadMode::Immutable,
        public bool $strictNames = false,
        public bool $typeCasting = true,
        public bool $populateEnv = true,
        public bool $populateServer = true,
        public bool $usePutenv = false,
        public ?string $encryptionKey = null,
        public ?string $cachePath = null,
        public array $allowList = [],
        public array $denyList = [],
        public ?string $environmentName = null,
    ) {
    }

    public function withLoadMode(LoadMode $loadMode): self
    {
        return new self(
            loadMode: $loadMode,
            strictNames: $this->strictNames,
            typeCasting: $this->typeCasting,
            populateEnv: $this->populateEnv,
            populateServer: $this->populateServer,
            usePutenv: $this->usePutenv,
            encryptionKey: $this->encryptionKey,
            cachePath: $this->cachePath,
            allowList: $this->allowList,
            denyList: $this->denyList,
            environmentName: $this->environmentName,
        );
    }

    public function withStrictNames(bool $strict): self
    {
        return new self(
            loadMode: $this->loadMode,
            strictNames: $strict,
            typeCasting: $this->typeCasting,
            populateEnv: $this->populateEnv,
            populateServer: $this->populateServer,
            usePutenv: $this->usePutenv,
            encryptionKey: $this->encryptionKey,
            cachePath: $this->cachePath,
            allowList: $this->allowList,
            denyList: $this->denyList,
            environmentName: $this->environmentName,
        );
    }

    public function withTypeCasting(bool $enabled): self
    {
        return new self(
            loadMode: $this->loadMode,
            strictNames: $this->strictNames,
            typeCasting: $enabled,
            populateEnv: $this->populateEnv,
            populateServer: $this->populateServer,
            usePutenv: $this->usePutenv,
            encryptionKey: $this->encryptionKey,
            cachePath: $this->cachePath,
            allowList: $this->allowList,
            denyList: $this->denyList,
            environmentName: $this->environmentName,
        );
    }

    public function withEncryptionKey(?string $key): self
    {
        return new self(
            loadMode: $this->loadMode,
            strictNames: $this->strictNames,
            typeCasting: $this->typeCasting,
            populateEnv: $this->populateEnv,
            populateServer: $this->populateServer,
            usePutenv: $this->usePutenv,
            encryptionKey: $key,
            cachePath: $this->cachePath,
            allowList: $this->allowList,
            denyList: $this->denyList,
            environmentName: $this->environmentName,
        );
    }

    public function withCachePath(?string $path): self
    {
        return new self(
            loadMode: $this->loadMode,
            strictNames: $this->strictNames,
            typeCasting: $this->typeCasting,
            populateEnv: $this->populateEnv,
            populateServer: $this->populateServer,
            usePutenv: $this->usePutenv,
            encryptionKey: $this->encryptionKey,
            cachePath: $path,
            allowList: $this->allowList,
            denyList: $this->denyList,
            environmentName: $this->environmentName,
        );
    }

    /**
     * Return a new instance with the given allow-list glob patterns.
     *
     * @param  list<string> $patterns  Glob patterns for allowed variable names.
     * @since  4.0.0
     */
    public function withAllowList(array $patterns): self
    {
        return new self(
            loadMode: $this->loadMode,
            strictNames: $this->strictNames,
            typeCasting: $this->typeCasting,
            populateEnv: $this->populateEnv,
            populateServer: $this->populateServer,
            usePutenv: $this->usePutenv,
            encryptionKey: $this->encryptionKey,
            cachePath: $this->cachePath,
            allowList: $patterns,
            denyList: $this->denyList,
            environmentName: $this->environmentName,
        );
    }

    /**
     * Return a new instance with the given deny-list glob patterns.
     *
     * @param  list<string> $patterns  Glob patterns for denied variable names.
     * @since  4.0.0
     */
    public function withDenyList(array $patterns): self
    {
        return new self(
            loadMode: $this->loadMode,
            strictNames: $this->strictNames,
            typeCasting: $this->typeCasting,
            populateEnv: $this->populateEnv,
            populateServer: $this->populateServer,
            usePutenv: $this->usePutenv,
            encryptionKey: $this->encryptionKey,
            cachePath: $this->cachePath,
            allowList: $this->allowList,
            denyList: $patterns,
            environmentName: $this->environmentName,
        );
    }

    public function withEnvironmentName(?string $name): self
    {
        return new self(
            loadMode: $this->loadMode,
            strictNames: $this->strictNames,
            typeCasting: $this->typeCasting,
            populateEnv: $this->populateEnv,
            populateServer: $this->populateServer,
            usePutenv: $this->usePutenv,
            encryptionKey: $this->encryptionKey,
            cachePath: $this->cachePath,
            allowList: $this->allowList,
            denyList: $this->denyList,
            environmentName: $name,
        );
    }
}
