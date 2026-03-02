# ADR-007: Environment-Aware Cascade Loading

**Status:** Accepted
**Date:** 2024-02-20
**Applies to:** KaririCode\Dotenv 4.2+

## Context

Real-world applications operate across multiple environments: development, testing, staging, production. Each environment shares a common base configuration but overrides specific values. The traditional approach — maintaining separate `.env.production`, `.env.staging` files — leads to duplication and drift.

A cascade loading strategy loads multiple files in a defined order, where later files override earlier ones. This allows:
- Base defaults in `.env` (committed to VCS)
- Local developer overrides in `.env.local` (gitignored)
- Environment-specific values in `.env.{env}` (committed)
- Environment-specific local overrides in `.env.{env}.local` (gitignored)

## Decision

### `bootEnv()` Method

The `Dotenv::bootEnv()` method implements a four-layer cascade:

```
Layer 1: .env                    (base defaults, committed)
Layer 2: .env.local              (local overrides, gitignored)
Layer 3: .env.{APP_ENV}          (environment defaults, committed)
Layer 4: .env.{APP_ENV}.local    (environment local overrides, gitignored)
```

Each layer is loaded with `required: false` — missing files are silently skipped.

### Environment Name Resolution

The environment name is resolved in priority order:

1. **Explicit parameter:** `$dotenv->bootEnv('production')` — highest priority.
2. **Configuration:** `DotenvConfiguration::$environmentName`.
3. **Loaded variable:** The `APP_ENV` variable from Layer 1 or Layer 2.
4. **System environment:** `$_ENV['APP_ENV']` or `$_SERVER['APP_ENV']`.
5. **Default:** `'dev'`.

This order allows `.env` to declare `APP_ENV=staging` and have Layer 3 load `.env.staging` accordingly.

### Test Environment Safety

When the resolved environment is `test`, Layer 4 (`.env.test.local`) is **not loaded**. This ensures test suite reproducibility — local developer overrides must not affect CI/CD test runs.

```php
if ($envName !== 'test') {
    $this->loadFile("{$basePath}.{$envName}.local", required: false);
}
```

### Gitignore Convention

The following `.gitignore` entries are recommended:

```gitignore
.env.local
.env.*.local
.env.cache.php
```

This keeps local overrides out of VCS while allowing `.env`, `.env.staging`, and `.env.production` to be committed as shared configuration.

### Cache Integration

`bootEnv()` checks for a cache file **before** any cascade loading. If the cache is fresh, no files are parsed. The cache should be dumped after the full cascade has been resolved (i.e., in the deployment pipeline).

```php
public function bootEnv(?string $environmentName = null): void
{
    if ($this->loadFromCache()) {
        $this->loaded = true;
        return;
    }
    // ... cascade loading
}
```

## Consequences

**Positive:**
- Single `.env` committed to VCS with environment-specific overrides — no duplication.
- Developers customize freely via `.env.local` without affecting teammates.
- Test reproducibility guaranteed by skipping `.env.test.local`.
- Compatible with cache: deploy pipeline dumps cache after cascade resolution.

**Negative:**
- Four-layer cascade can be confusing for debugging ("which file set this value?"). The `debug()` method mitigates this by reporting the source file for each variable.
- Layer 2 (`.env.local`) loads before the environment is resolved, meaning it cannot be environment-specific. This is intentional — `.env.local` is for machine-specific overrides (paths, ports), not environment-specific logic.
