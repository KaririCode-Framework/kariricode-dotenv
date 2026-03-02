# ADR-003: Pluggable Type System with Priority-Based Detection

**Status:** Accepted
**Date:** 2024-01-20
**Applies to:** KaririCode\Dotenv 4.0+

## Context

Environment variables are strings by definition (POSIX, IEEE Std 1003.1). However, application code universally needs typed values: booleans for feature flags, integers for ports, arrays for IP whitelists. The conversion from string to typed value involves two distinct operations:

1. **Detection** — determining the semantic type of a string value.
2. **Casting** — converting the string to the detected PHP type.

These operations must be extensible (applications may define custom types) and deterministic (same input always produces the same type).

## Decision

### Two-Phase Architecture

The `TypeSystem` orchestrates detection and casting as separate, composable phases:

```
Raw String → [Detector₁, Detector₂, ..., Detectorₙ] → ValueType → Caster → Typed Value
```

**Phase 1 — Detection:** Detectors are sorted by priority (descending). The first detector returning a non-null `ValueType` wins. If no detector matches, the value remains `ValueType::String`.

**Phase 2 — Casting:** The `ValueType` selects the corresponding `TypeCaster`. `ValueType::String` short-circuits — no caster is invoked.

### Priority Order

| Detector | Priority | Rationale |
|---|---|---|
| NullDetector | 200 | `"null"` must not be detected as a string. |
| BooleanDetector | 190 | `"true"`/`"false"` must not be detected as strings. |
| IntegerDetector | 180 | `"42"` must be integer, not float. |
| FloatDetector | 170 | `"3.14"` must contain `.` or `e` to avoid integer confusion. |
| JsonDetector | 160 | `{...}` objects before array check. |
| ArrayDetector | 150 | `[...]` arrays. |
| *(String)* | *(fallback)* | Everything else. |

The gap between priorities (10) allows custom detectors to be inserted at any position.

### Contracts

```php
interface TypeDetector {
    public function priority(): int;
    public function detect(string $value): ?ValueType;
}

interface TypeCaster {
    public function cast(string $value): mixed;
}
```

### Lazy Sorting

Detectors are sorted only when `detect()` is first called, and re-sorted only when `addDetector()` is called. A `$sorted` flag avoids redundant `usort()` calls.

### Extension Point

```php
$dotenv->addTypeDetector(new SemVerDetector());     // Custom detector at any priority
$dotenv->addTypeCaster(ValueType::Integer, new StrictIntCaster()); // Replace default
```

Adding a detector invalidates the sort. Adding a caster replaces the existing one for that `ValueType`.

## Consequences

**Positive:**
- Open/Closed Principle — new types require no modification to `TypeSystem`.
- Deterministic — priority ordering eliminates ambiguity (`"true"` is always boolean, never string).
- Testable — each detector/caster is a pure function, testable in isolation.
- Zero overhead for strings — fallback path invokes no caster.

**Negative:**
- Priority collisions: two detectors at the same priority have undefined ordering (stable sort, but insertion-order dependent).
- No composite types: a value cannot be detected as multiple types simultaneously.

**Mitigations:**
- Document that custom detectors should use priorities not in the default range (100–200).
- The `resolve()` convenience method chains detect + cast for the common case.
