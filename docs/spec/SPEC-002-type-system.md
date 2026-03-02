# SPEC-002: Type System

**Version:** 1.0
**Date:** 2024-01-20
**Applies to:** KaririCode\Dotenv 4.0+

## 1. Overview

The Type System performs automatic detection and casting of environment variable values from their raw string representation into typed PHP values. It operates in two phases: detection (string → ValueType) and casting (string × ValueType → mixed).

## 2. Value Types

```php
enum ValueType {
    case String;    // Default fallback
    case Integer;   // Whole numbers with optional sign
    case Float;     // Decimal numbers and scientific notation
    case Boolean;   // true/false, yes/no, on/off
    case Null;      // null, NULL, (null)
    case Json;      // JSON objects: {...}
    case Array;     // JSON arrays: [...]
}
```

## 3. Detection Rules

Detectors execute in priority order (highest first). The first non-null result wins.

### 3.1 Null (Priority 200)

Matches exactly: `"null"`, `"NULL"`, `"(null)"`.

Empty string (`""`) is **not** null — it is `ValueType::String`. This distinction is intentional: `FOO=` (empty string) and `FOO=null` (explicit null) carry different semantics.

### 3.2 Boolean (Priority 190)

Case-insensitive match against:

| Truthy | Falsy |
|---|---|
| `true`, `TRUE`, `True` | `false`, `FALSE`, `False` |
| `yes`, `YES`, `Yes` | `no`, `NO`, `No` |
| `on`, `ON`, `On` | `off`, `OFF`, `Off` |
| `(true)` | `(false)` |

### 3.3 Integer (Priority 180)

Pattern: `/\A[+-]?\d+\z/`

Matches: `42`, `-10`, `+99`, `0`, `00042`
Does not match: `""`, `"-"`, `"+"`, `"3.14"`, `"1e10"`

### 3.4 Float (Priority 170)

Pattern: `/\A[+-]?(\d+\.?\d*|\d*\.?\d+)([eE][+-]?\d+)?\z/`

**Prerequisite:** The value must contain a decimal point (`.`) or an exponent marker (`e`/`E`). This prevents `"42"` from being detected as float.

Matches: `3.14`, `-0.5`, `1e10`, `2.5E-3`, `.5`, `3.`
Does not match: `42` (no dot or exponent — detected as integer)

### 3.5 JSON Object (Priority 160)

Conditions:
1. Trimmed value starts with `{` and ends with `}`.
2. `json_decode()` succeeds (`json_last_error() === JSON_ERROR_NONE`).

Matches: `{"key": "value"}`, `{"nested": {"a": 1}}`
Does not match: `{invalid json}`, `{` (unclosed)

### 3.6 JSON Array (Priority 150)

Conditions:
1. Trimmed value starts with `[` and ends with `]`.
2. `json_decode(..., true)` returns a PHP array.

Matches: `["a", "b"]`, `[1, 2, 3]`
Does not match: `[invalid]`, `[` (unclosed)

### 3.7 String (Fallback)

If no detector matches, the value is `ValueType::String`. No casting is applied.

## 4. Casting Rules

### 4.1 Null → `null`

Always returns PHP `null`.

### 4.2 Boolean → `bool`

Truthy values: `true`, `yes`, `on`, `(true)` (case-insensitive) → `true`
Everything else in the boolean detection set → `false`

### 4.3 Integer → `int`

Direct cast: `(int) $value`

### 4.4 Float → `float`

Direct cast: `(float) $value`

### 4.5 JSON Object → `array`

`json_decode($value, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR)`

Returns an associative array. Throws `\JsonException` on malformed input (fail-fast).

### 4.6 JSON Array → `array`

Same as JSON Object — `json_decode()` with `associative: true`.

### 4.7 String → `string`

Identity function — value returned as-is.

## 5. Extension API

### 5.1 Custom Detector

```php
use KaririCode\Dotenv\Contract\TypeDetector;
use KaririCode\Dotenv\Enum\ValueType;

final readonly class UuidDetector implements TypeDetector
{
    public function priority(): int { return 195; } // Between Null and Boolean

    public function detect(string $value): ?ValueType
    {
        return preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/i', $value) === 1
            ? ValueType::String  // Detected as UUID, but cast as string
            : null;
    }
}

$dotenv->addTypeDetector(new UuidDetector());
```

### 5.2 Custom Caster

```php
use KaririCode\Dotenv\Contract\TypeCaster;

$dotenv->addTypeCaster(ValueType::Integer, new class implements TypeCaster {
    public function cast(string $value): int
    {
        return abs((int) $value); // Always positive
    }
});
```

Custom casters replace the default for the given `ValueType`.

### 5.3 Priority Guidelines

| Range | Reserved For |
|---|---|
| 200–250 | Null-like types (highest precedence) |
| 150–199 | Scalar types (boolean, integer, float) |
| 100–149 | Structured types (JSON, arrays) |
| 50–99 | Application-specific detectors |
| 1–49 | Low-priority catch-alls |

## 6. Disable Type Casting

```php
$config = new DotenvConfiguration(typeCasting: false);
```

When disabled:
- All values remain `ValueType::String`.
- No detector or caster is invoked.
- `env()` returns raw strings.
- `EnvironmentVariable::$type` is always `ValueType::String`.

## 7. The `env()` Helper

The global `env()` function resolves variables from `$_ENV` → `$_SERVER` → `getenv()` and applies the default `TypeSystem` pipeline. It maintains a static `TypeSystem` instance for the lifetime of the request.

```php
function env(string $key, mixed $default = null): mixed
```

If the variable is not found in any source, `$default` is returned without type casting.
