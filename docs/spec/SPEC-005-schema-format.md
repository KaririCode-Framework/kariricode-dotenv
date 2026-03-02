# SPEC-005: Schema Format (.env.schema)

**Version:** 1.0
**Date:** 2024-03-01
**Applies to:** KaririCode\Dotenv 4.4+

## 1. Overview

The `.env.schema` file provides declarative validation rules for environment variables using an INI-like syntax. It eliminates the need for programmatic validation code and serves as living documentation of the application's environment contract.

## 2. File Format

### 2.1 Encoding

UTF-8. Line endings: LF or CRLF (normalized to LF internally).

### 2.2 Structure

```ini
# Comments start with # or ;
; INI-style comments also supported

[VARIABLE_NAME]
directive = value
directive = value

[ANOTHER_VARIABLE]
directive = value
```

### 2.3 Section Headers

```
[NAME]
```

Pattern: `[A-Za-z_][A-Za-z0-9_]*` enclosed in square brackets. Each section defines rules for one environment variable.

### 2.4 Directives

```
key = value
```

The `=` separator is required. Leading and trailing whitespace around both key and value is trimmed.

### 2.5 Comments and Empty Lines

Lines starting with `#` or `;` (after optional whitespace) are ignored. Empty lines are ignored.

## 3. Supported Directives

### 3.1 `required`

**Values:** `true`, `false`, `1`, `0`, `yes`, `no`, `on`, `off` (case-insensitive)
**Default:** `false`

When `true`, the variable must exist (non-null). Missing required variables are reported as errors.

```ini
[DB_HOST]
required = true
```

### 3.2 `type`

**Values:** `string`, `integer`, `boolean`, `numeric`, `email`, `url`

Applies the corresponding type validation rule.

| Type | Validator Rule | Description |
|---|---|---|
| `string` | *(none)* | No additional validation — all values are strings. |
| `integer` | `IsIntegerRule` | Must match `/\A[+-]?\d+\z/`. |
| `boolean` | `IsBooleanRule` | Must be true/false/1/0/yes/no/on/off. |
| `numeric` | `IsNumericRule` | Must pass `is_numeric()`. |
| `email` | `EmailRule` | Must pass `FILTER_VALIDATE_EMAIL`. |
| `url` | `UrlRule` | Must pass `FILTER_VALIDATE_URL`. |

Unknown types throw `ValidationException::schemaViolation()` at schema application time.

```ini
[DB_PORT]
type = integer
```

### 3.3 `notEmpty`

**Values:** Boolean (same as `required`)

When `true`, the variable's trimmed value must not be an empty string.

```ini
[DB_HOST]
required = true
notEmpty = true
```

### 3.4 `min` / `max`

**Values:** Numeric (integer or float)

Defines a numeric range. Both `min` and `max` must be present for the range check to apply. The underlying rule is `BetweenRule`, which requires the value to be numeric.

```ini
[DB_PORT]
type = integer
min = 1
max = 65535
```

If only `min` or only `max` is specified, the range check is not applied. This prevents ambiguous open-ended ranges.

### 3.5 `allowed`

**Values:** Comma-separated list of acceptable values. Whitespace around each value is trimmed.

```ini
[APP_ENV]
allowed = local, staging, production
```

Produces: `AllowedValuesRule(['local', 'staging', 'production'])`.

### 3.6 `regex`

**Values:** A PCRE pattern including delimiters.

```ini
[API_KEY]
regex = /^[a-f0-9]{32}$/
```

Produces: `MatchesRegexRule('/^[a-f0-9]{32}$/')`.

### 3.7 `default`

**Values:** Any string.

Informational only — documents the default value but does **not** inject it into the environment. Default injection is out of scope for the schema parser; the `.env` file should contain the actual default.

```ini
[DB_PORT]
default = 5432
```

## 4. Processing Order

For each variable section, directives are applied in this order:

1. **required / optional** — Register as required or conditional (`ifPresent`).
2. **notEmpty** — Add `NotEmptyRule` if `true`.
3. **type** — Add the corresponding type rule.
4. **min + max** — Add `BetweenRule` if both are present.
5. **allowed** — Add `AllowedValuesRule`.
6. **regex** — Add `MatchesRegexRule`.

This order ensures that presence is checked before content, and type is validated before range.

## 5. Interaction with Fluent DSL

Schema validation and fluent DSL validation are not mutually exclusive. Both can be used in the same application:

```php
$dotenv->loadWithSchema(__DIR__ . '/.env.schema'); // Declarative rules
$dotenv->validate()
    ->custom('DB_DSN', fn($v) => str_starts_with($v, 'pgsql:'))
    ->assert(); // Additional programmatic rules
```

## 6. Full Example

```ini
# .env.schema — Application Environment Contract
# Generated: 2024-03-01

[APP_ENV]
required = true
allowed = local, staging, production

[APP_DEBUG]
required = true
type = boolean

[APP_URL]
required = true
type = url
notEmpty = true

[DB_HOST]
required = true
notEmpty = true

[DB_PORT]
required = true
type = integer
min = 1
max = 65535
default = 5432

[DB_NAME]
required = true
notEmpty = true

[DB_USER]
required = true
notEmpty = true

[DB_PASSWORD]
required = true

[REDIS_HOST]
required = false
type = string

[REDIS_PORT]
required = false
type = integer
min = 1
max = 65535

[ADMIN_EMAIL]
type = email

[API_KEY]
regex = /^[a-f0-9]{32}$/

[LOG_LEVEL]
allowed = debug, info, warning, error, critical
default = info
```

## 7. Error Reporting

Schema validation failures are reported through the same `ValidationException::batchErrors()` mechanism as fluent validation. All failures across all variables are collected before throwing.

```
Environment validation failed:
- DB_HOST is required but not defined.
- DB_PORT must be an integer.
- APP_ENV must be one of: local, staging, production.
- API_KEY must match pattern /^[a-f0-9]{32}$/.
```
