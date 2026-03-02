# ADR-006: Three Load Modes for Environment Variable Precedence

**Status:** Accepted
**Date:** 2024-01-20
**Applies to:** KaririCode\Dotenv 4.0+

## Context

When a `.env` file declares `DB_HOST=localhost` but the operating system already has `DB_HOST=production-host` set via the container orchestrator, which value wins? The answer depends on the deployment model:

- **Containerized (Kubernetes, ECS):** The orchestrator injects real secrets via environment variables. The `.env` file contains development defaults. Real environment must take precedence.
- **Traditional server:** The `.env` file is the source of truth. No environment variables are pre-set.
- **Development:** Developers want `.env` to override everything for local testing.

A single behavior cannot satisfy all three models.

## Decision

Three mutually exclusive load modes, selected via `DotenvConfiguration::$loadMode`:

### `LoadMode::Immutable` (Default)

Throws `ImmutableException` if a `.env` variable conflicts with a pre-existing environment variable (one that existed **before** `load()` was called). Variables loaded by the Dotenv instance itself can be overridden across multiple files (e.g., `.env` → `.env.local`).

**Rationale:** The safest default. If the container orchestrator set `DB_PASSWORD`, the `.env` file's value is almost certainly stale. Throwing an exception surfaces the conflict immediately rather than silently using the wrong value.

**Scope of immutability:** Only variables that existed in `$_ENV`, `$_SERVER`, or `getenv()` before the Dotenv instance started loading are protected. Variables introduced by earlier `.env` files in the same load sequence are not protected — this allows cascade loading (`.env` → `.env.local`) to work naturally.

### `LoadMode::Overwrite`

Always overwrites, regardless of prior state. The `.env` file is the absolute source of truth.

**Use case:** Development environments, test suites, and applications where the `.env` file is managed by deployment tooling (Ansible, Chef) and is the canonical source.

### `LoadMode::SkipExisting`

Silently skips any variable that already exists in the environment. No exception, no overwrite.

**Use case:** Docker images that set sensible defaults in `.env` but allow runtime overrides via `docker run -e DB_HOST=...`. The `.env` provides fallbacks only.

### Decision Matrix

| Scenario | Pre-existing var | .env var | Immutable | Overwrite | SkipExisting |
|---|---|---|---|---|---|
| Conflict | `DB_HOST=prod` | `DB_HOST=dev` | ❌ throws | `dev` wins | `prod` wins |
| No conflict | *(absent)* | `DB_HOST=dev` | `dev` set | `dev` set | `dev` set |
| Cascade | `.env`: `X=a` | `.env.local`: `X=b` | `b` wins | `b` wins | `b` wins |

### Implementation

The check occurs in `Dotenv::setVariable()`:

```php
$alreadyExists = isset($_ENV[$name]) || isset($_SERVER[$name]) || getenv($name) !== false;

if ($this->configuration->loadMode === LoadMode::Immutable
    && $alreadyExists
    && !isset($this->variables[$name])  // Not loaded by this instance
) {
    throw ImmutableException::alreadyDefined($name);
}

if ($this->configuration->loadMode === LoadMode::SkipExisting && $alreadyExists) {
    return;
}
```

The `!isset($this->variables[$name])` guard is critical — it distinguishes between "existed before Dotenv" (immutable violation) and "loaded by a prior .env file in this instance" (cascade override, allowed).

## Consequences

**Positive:**
- Explicit control over precedence — no guessing which value is active.
- Safe default (Immutable) prevents silent credential conflicts in production.
- All three modes share the same code path — the only difference is the conflict resolution branch.

**Negative:**
- Immutable mode can cause startup failures if the deployment pipeline inadvertently sets variables that conflict with `.env`. This is by design — the failure surfaces the misconfiguration.
- SkipExisting mode can silently mask stale `.env` values. Developers must understand that pre-existing environment variables always win.
