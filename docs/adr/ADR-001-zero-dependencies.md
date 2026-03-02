# ADR-001: Zero External Dependencies

**Status:** Accepted
**Date:** 2024-01-15
**Applies to:** KaririCode\Dotenv 4.x

## Context

Environment variable loading is a foundational concern — it runs before the application container, before the framework, before anything else. Any dependency the dotenv loader carries becomes a transitive dependency for the entire application. Dependency conflicts, security advisories, and version constraints in foundational packages cascade into every downstream consumer.

Additionally, the KaririCode Framework follows ARFA 1.3 Principle P4 (Protocol Agnostic), which requires that foundation-layer components operate independently of external protocols, libraries, or frameworks.

## Decision

KaririCode\Dotenv has **zero external runtime dependencies**. The only requirement is PHP ≥ 8.4.

All functionality — parsing, type detection, casting, validation, encryption, caching, schema parsing, CLI tooling — is implemented using PHP standard library functions and extensions:

- **Parsing:** `str_starts_with()`, `preg_replace_callback()`, `explode()`
- **Type casting:** PHP type juggling with explicit casts
- **Encryption:** `openssl_encrypt()` / `openssl_decrypt()` (ext-openssl, bundled)
- **Caching:** `var_export()`, `include`, `rename()`, `opcache_invalidate()`
- **Validation:** `filter_var()`, `is_numeric()`, `preg_match()`
- **CLI:** `$argv`, `fwrite(STDOUT)`, `file_get_contents()`

Development dependencies (PHPUnit, PHPStan, PHP-CS-Fixer) are in `require-dev` only.

## Consequences

**Positive:**
- No dependency conflicts — safe to install in any project regardless of existing packages.
- No security advisory surface from transitive dependencies.
- Minimal autoloader footprint — only `KaririCode\Dotenv\` namespace.
- No version constraint negotiation during `composer update`.
- Portable: works in any PHP 8.4+ environment without additional setup.

**Negative:**
- Features like YAML parsing, remote secret fetching, or Vault integration must be implemented from scratch or delegated to application-level code.
- Encryption requires ext-openssl (bundled in standard PHP distributions but may be absent in minimal Docker images).

**Mitigations:**
- ext-openssl is listed in `suggest` rather than `require` — encryption is opt-in.
- The `VariableProcessor` contract allows application-level extensions without modifying the package.
