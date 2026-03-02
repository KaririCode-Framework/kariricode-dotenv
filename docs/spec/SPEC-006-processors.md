# SPEC-006: Variable Processors

**Version:** 1.0
**Date:** 2024-03-01
**Applies to:** KaririCode\Dotenv 4.4+

## 1. Overview

Variable processors are post-load transformers that modify environment variable values after parsing and type casting. They enable domain-specific transformations (splitting CSV into arrays, normalizing URLs, decoding base64) without polluting the parser or type system.

## 2. Contract

```php
interface VariableProcessor
{
    public function process(string $rawValue, mixed $typedValue): mixed;
}
```

| Parameter | Description |
|---|---|
| `$rawValue` | The raw string from the `.env` file (after decryption, before type casting). |
| `$typedValue` | The value after type detection and casting. |
| **Returns** | The transformed value that replaces `$typedValue` in the `EnvironmentVariable`. |

Processors receive both the raw and typed values, allowing them to choose which representation to work with. Most processors operate on `$rawValue` (string manipulation), but pipeline processors may chain off `$typedValue`.

## 3. Registration

```php
$dotenv->addProcessor(string $pattern, VariableProcessor $processor): void
```

| Parameter | Description |
|---|---|
| `$pattern` | Exact variable name or glob pattern. |
| `$processor` | Instance implementing `VariableProcessor`. |

Multiple processors can be registered for the same pattern. They execute in registration order.

### 3.1 Glob Patterns

| Pattern | Matches | Does Not Match |
|---|---|---|
| `ALLOWED_IPS` | `ALLOWED_IPS` | `ALLOWED_IPS_V6` |
| `*_URL` | `APP_URL`, `API_URL`, `WEBHOOK_URL` | `URL_PREFIX` |
| `DB_*` | `DB_HOST`, `DB_PORT`, `DB_NAME` | `REDIS_HOST` |
| `*_SECRET*` | `DB_SECRET`, `API_SECRET_KEY` | `SECRET` (no prefix) |

Glob matching uses `*` (any characters) and `?` (single character). The implementation converts globs to PCRE:

```php
$regex = '/\A' . str_replace(['\*', '\?'], ['.*', '.'], preg_quote($pattern, '/')) . '\z/';
```

## 4. Execution Order

Processors execute within `Dotenv::setVariable()`, after type casting and before storage:

```
Raw value → Decryption → Type detection → Type casting → Processor(s) → EnvironmentVariable
```

For a given variable, processors are applied in pattern registration order:

```php
$dotenv->addProcessor('*_URL', new TrimProcessor());           // Runs first
$dotenv->addProcessor('*_URL', new UrlNormalizerProcessor());  // Runs second
```

If multiple patterns match the same variable, all matching processors run in registration order across all patterns.

## 5. Built-in Processors

### 5.1 CsvToArrayProcessor

Splits a comma-delimited string into an array of trimmed values.

```php
final readonly class CsvToArrayProcessor implements VariableProcessor
{
    public function __construct(private string $separator = ',') {}

    public function process(string $rawValue, mixed $typedValue): array
    {
        if (trim($rawValue) === '') {
            return [];
        }
        return array_map(trim(...), explode($this->separator, $rawValue));
    }
}
```

| Input | Output |
|---|---|
| `"192.168.1.1, 10.0.0.1, 172.16.0.1"` | `['192.168.1.1', '10.0.0.1', '172.16.0.1']` |
| `""` | `[]` |
| `"   "` | `[]` |

Custom separator:

```php
new CsvToArrayProcessor(separator: '|')  // "a|b|c" → ["a", "b", "c"]
```

### 5.2 Base64DecodeProcessor

Decodes a base64-encoded raw value. Throws on invalid input.

```php
final readonly class Base64DecodeProcessor implements VariableProcessor
{
    public function process(string $rawValue, mixed $typedValue): string
    {
        $decoded = base64_decode($rawValue, strict: true);
        if ($decoded === false) {
            throw new \RuntimeException("Invalid base64 value: cannot decode.");
        }
        return $decoded;
    }
}
```

Use case: certificates, binary keys, or opaque tokens stored as base64 in `.env`.

### 5.3 TrimProcessor

Trims whitespace (or custom characters) from the raw value.

```php
final readonly class TrimProcessor implements VariableProcessor
{
    public function __construct(private string $characters = " \t\n\r\0\x0B") {}

    public function process(string $rawValue, mixed $typedValue): string
    {
        return trim($rawValue, $this->characters);
    }
}
```

### 5.4 UrlNormalizerProcessor

Ensures URLs end with a trailing slash. Removes duplicate trailing slashes.

```php
final readonly class UrlNormalizerProcessor implements VariableProcessor
{
    public function process(string $rawValue, mixed $typedValue): string
    {
        return rtrim($rawValue, '/') . '/';
    }
}
```

| Input | Output |
|---|---|
| `"https://api.example.com"` | `"https://api.example.com/"` |
| `"https://api.example.com/"` | `"https://api.example.com/"` |
| `"https://api.example.com//"` | `"https://api.example.com/"` |

## 6. Custom Processors

Implement `VariableProcessor` for application-specific transformations:

```php
final readonly class JsonDecodeProcessor implements VariableProcessor
{
    public function process(string $rawValue, mixed $typedValue): array
    {
        return json_decode($rawValue, true, 512, JSON_THROW_ON_ERROR);
    }
}

final readonly class UpperCaseProcessor implements VariableProcessor
{
    public function process(string $rawValue, mixed $typedValue): string
    {
        return strtoupper($rawValue);
    }
}

final readonly class PrefixProcessor implements VariableProcessor
{
    public function __construct(private string $prefix) {}

    public function process(string $rawValue, mixed $typedValue): string
    {
        return $this->prefix . $rawValue;
    }
}
```

## 7. Interaction with Type Casting

Processors run **after** type casting. The processor's return value replaces the typed value in the `EnvironmentVariable`:

```php
// .env: ALLOWED_IPS=192.168.1.1, 10.0.0.1
// Without processor: type=String, value="192.168.1.1, 10.0.0.1"
// With CsvToArrayProcessor: type=String, value=["192.168.1.1", "10.0.0.1"]
```

Note that `EnvironmentVariable::$type` reflects the **original** detection (String), not the processor's output type. The `$value` field holds the processor's output.

## 8. Interaction with Caching

Processors are **not** invoked when loading from cache. The cache stores raw string values, and type casting + processors run on every load (cache or file). This ensures that processor registration at runtime is always respected.

## 9. Error Handling

Processor exceptions propagate uncaught from `Dotenv::load()`. This is intentional — a processor failure (e.g., invalid base64) indicates a configuration error that should halt application startup.
