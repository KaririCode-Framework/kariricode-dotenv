# SPEC-004: Validation

**Version:** 1.0
**Date:** 2024-02-05
**Applies to:** KaririCode\Dotenv 4.1+

## 1. Overview

KaririCode\Dotenv provides two validation mechanisms:

1. **Fluent DSL** — Programmatic rule definition via `EnvironmentValidator`.
2. **Schema file** — Declarative rule definition via `.env.schema` (see SPEC-005).

Both mechanisms collect all failures before throwing a single `ValidationException`.

## 2. Entry Points

### 2.1 Simple Required Check

```php
$dotenv->required('DB_HOST', 'DB_PORT', 'APP_ENV');
```

Throws `ValidationException` listing all missing variables.

### 2.2 Fluent Validator

```php
$dotenv->validate()         // Returns EnvironmentValidator
    ->required(...)
    ->isInteger(...)
    ->assert();             // Executes all rules, throws on failure
```

### 2.3 Schema Validation

```php
$dotenv->loadWithSchema(__DIR__ . '/.env.schema');
```

Parses the schema, loads `.env` if not already loaded, applies rules, and asserts.

## 3. EnvironmentValidator API

### 3.1 Targeting Methods

These methods set the current target variables for subsequent rule methods.

| Method | Behavior |
|---|---|
| `required(string ...$names): self` | Marks variables as required (must exist). Sets targets. |
| `ifPresent(string ...$names): self` | Sets targets. Rules only apply if variable exists. |

### 3.2 Rule Methods

Rule methods apply their rule to the **current targets** (set by the most recent targeting method or rule method that accepts names).

| Method | Rule Applied | Target Behavior |
|---|---|---|
| `notEmpty(string ...$names)` | `NotEmptyRule` | If `$names` given, sets new targets; otherwise uses current. |
| `isInteger(string ...$names)` | `IsIntegerRule` | Same. |
| `isBoolean(string ...$names)` | `IsBooleanRule` | Same. |
| `isNumeric(string ...$names)` | `IsNumericRule` | Same. |
| `between(int\|float $min, int\|float $max)` | `BetweenRule` | Always uses current targets (chainable). |
| `allowedValues(string $name, array $allowed)` | `AllowedValuesRule` | Sets target to `[$name]`. |
| `matchesRegex(string $name, string $pattern)` | `MatchesRegexRule` | Sets target to `[$name]`. |
| `url(string ...$names)` | `UrlRule` | If `$names` given, sets new targets. |
| `email(string ...$names)` | `EmailRule` | Same. |
| `custom(string $name, Closure $callback, string $message)` | `CustomRule` | Sets target to `[$name]`. |
| `rule(ValidationRule $rule, string ...$names)` | Arbitrary rule | If `$names` given, sets new targets. |

### 3.3 Chaining

Methods that inherit current targets enable natural chains:

```php
->isInteger('DB_PORT')->between(1, 65535)
// isInteger sets target to ['DB_PORT']
// between inherits target ['DB_PORT']
```

### 3.4 Execution

```php
public function assert(): void
```

1. Checks all `required` variables for presence.
2. Iterates all variables with registered rules.
3. Skips absent variables in conditional mode (`ifPresent`).
4. Skips absent required variables from rule execution (already reported as missing).
5. Collects all failure messages.
6. Throws `ValidationException::batchErrors($errors)` if any failures exist.

## 4. Built-in Rules

### 4.1 NotEmptyRule

```php
trim($value) !== ''
```

Message: `"{name} must not be empty."`

### 4.2 IsIntegerRule

```php
preg_match('/\A[+-]?\d+\z/', $value) === 1
```

Message: `"{name} must be an integer."`

### 4.3 IsBooleanRule

Accepted values (case-insensitive): `true`, `false`, `1`, `0`, `yes`, `no`, `on`, `off`.

Message: `"{name} must be a boolean (true/false, yes/no, on/off, 1/0)."`

### 4.4 IsNumericRule

```php
is_numeric($value)
```

Message: `"{name} must be numeric."`

### 4.5 BetweenRule

```php
is_numeric($value) && ($value + 0) >= $min && ($value + 0) <= $max
```

Message: `"{name} must be between {min} and {max}."`

### 4.6 AllowedValuesRule

```php
in_array($value, $allowed, strict: true)
```

Message: `"{name} must be one of: {comma-separated list}."`

### 4.7 MatchesRegexRule

```php
preg_match($pattern, $value) === 1
```

Message: `"{name} must match pattern {pattern}."`

### 4.8 UrlRule

```php
filter_var($value, FILTER_VALIDATE_URL) !== false
```

Message: `"{name} must be a valid URL."`

### 4.9 EmailRule

```php
filter_var($value, FILTER_VALIDATE_EMAIL) !== false
```

Message: `"{name} must be a valid email address."`

### 4.10 CustomRule

User-provided `Closure(string): bool` with custom message.

Message: User-defined (default: `"{name} failed custom validation."`)

## 5. Custom ValidationRule Contract

```php
interface ValidationRule
{
    public function passes(string $value): bool;
    public function message(): string;
}
```

The `{name}` placeholder in `message()` is replaced with the variable name at assertion time.

```php
final readonly class MinLengthRule implements ValidationRule
{
    public function __construct(private int $minLength) {}

    public function passes(string $value): bool
    {
        return strlen($value) >= $this->minLength;
    }

    public function message(): string
    {
        return "{name} must be at least {$this->minLength} characters.";
    }
}

$dotenv->validate()
    ->rule(new MinLengthRule(32), 'API_KEY')
    ->assert();
```

## 6. ValidationException

```php
final class ValidationException extends DotenvException
{
    public static function missingRequired(array $missing): self;
    public static function batchErrors(array $errors): self;
    public static function schemaViolation(string $message): self;

    /** @return list<string> All failure messages. */
    public function errors(): array;
}
```

The `errors()` method returns a flat list of failure messages, suitable for display in logs or CLI output.

## 7. Value Resolution

The `EnvironmentValidator` receives a `Closure(string): ?string` that resolves variable names to raw string values. This decouples validation from the storage mechanism:

```php
new EnvironmentValidator(
    fn (string $name): ?string => $this->resolveRawValue($name),
);
```

Resolution order inside `resolveRawValue()`: `$this->variables[$name]->rawValue` → `$_ENV[$name]` → `$_SERVER[$name]` → `null`.

Validation operates on **raw string values**, not typed values. This ensures that `isInteger('DB_PORT')` validates the string `"5432"`, not the integer `5432`.
