# ADR-002: Immutable Configuration and Value Objects

**Status:** Accepted
**Date:** 2024-01-15
**Applies to:** KaririCode\Dotenv 4.0+

## Context

Environment configuration is resolved once at application bootstrap and referenced throughout the request lifecycle. Mutable configuration creates a category of bugs where middleware, service providers, or lazy-loaded components observe different configuration states depending on execution order. In multi-threaded or async runtimes (Swoole, ReactPHP, FrankenPHP), shared mutable state introduces race conditions.

ARFA 1.3 Principle P1 (Immutable State Transformation) requires that state transitions produce new instances rather than mutating existing ones.

## Decision

All value objects in KaririCode\Dotenv are declared `final readonly`:

### DotenvConfiguration

```php
final readonly class DotenvConfiguration
{
    public function __construct(
        public LoadMode $loadMode = LoadMode::Immutable,
        public bool $strictNames = false,
        public bool $typeCasting = true,
        // ... 11 parameters total
    ) {}

    public function withLoadMode(LoadMode $loadMode): self
    {
        return new self(
            loadMode: $loadMode,
            strictNames: $this->strictNames,
            // ... named arguments for all parameters
        );
    }
}
```

Each `with*()` method returns a new instance using named arguments, ensuring:
1. No parameter ordering bugs — named args are position-independent.
2. Adding a new constructor parameter requires no changes to existing `with*()` methods (if a default is provided).
3. The original instance is never modified.

### EnvironmentVariable

```php
final readonly class EnvironmentVariable
{
    public function __construct(
        public string $name,
        public string $rawValue,
        public ValueType $type,
        public mixed $value,
        public string $source = '',
        public bool $overridden = false,
    ) {}
}
```

Once a variable is parsed, typed, and stored, its representation is sealed. The `overridden` flag records lineage without mutating prior state — the previous `EnvironmentVariable` is simply replaced in the `$variables` array.

### Enums as Immutable Discriminators

`LoadMode` and `ValueType` are backed-less PHP 8.4 enums — they carry identity without mutable backing values:

```php
enum LoadMode { case Immutable; case Overwrite; case SkipExisting; }
enum ValueType { case String; case Integer; case Float; case Boolean; case Null; case Json; case Array; }
```

## Consequences

**Positive:**
- Thread-safe by construction — no locks needed in async runtimes.
- Predictable behavior — configuration observed at time T₁ is identical at T₂.
- IDE support — readonly properties enable aggressive static analysis (PHPStan level 9).
- Defensive copying is unnecessary — readonly eliminates accidental mutation.

**Negative:**
- Configuration changes require instantiating new `DotenvConfiguration` objects via `with*()` methods.
- Cannot use property hooks (PHP 8.4) on readonly promoted properties — a language-level constraint.

**Trade-off accepted:** The cost of object allocation for `with*()` calls is negligible at bootstrap time (once per application lifecycle), making immutability effectively free.
