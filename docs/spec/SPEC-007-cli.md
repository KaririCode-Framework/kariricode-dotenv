# SPEC-007: CLI Tooling

**Version:** 1.0
**Date:** 2024-03-01
**Applies to:** KaririCode\Dotenv 4.4+

## 1. Overview

KaririCode\Dotenv ships a CLI tool at `vendor/bin/kariricode-dotenv` (registered in `composer.json` `bin` field). It provides 9 commands for managing `.env` files without writing PHP code.

## 2. Installation

The CLI is automatically available after `composer install`/`require`. No additional setup needed.

```bash
vendor/bin/kariricode-dotenv help
```

## 3. Global Options

| Option | Default | Description |
|---|---|---|
| `--dir=<path>` | Current working directory | Project root directory containing `.env` files. |

## 4. Commands

### 4.1 `debug`

Lists all loaded variables with their detected types, source files, and override status.

```bash
vendor/bin/kariricode-dotenv debug [--dir=.]
```

**Output format:**

```
Variable        Source          Type      Overridden  Value
──────────────────────────────────────────────────────────
DB_HOST         .env.local      String    yes         localhost
DB_PORT         .env            Integer   no          5432
APP_DEBUG       .env            Boolean   no          true
APP_ENV         .env.staging    String    yes         staging
```

**Process:** Instantiates `Dotenv`, calls `bootEnv()`, then formats `debug()` output as a table.

### 4.2 `validate`

Validates the current `.env` configuration against a `.env.schema` file.

```bash
vendor/bin/kariricode-dotenv validate [--dir=.] [--schema=.env.schema]
```

| Option | Default | Description |
|---|---|---|
| `--schema=<path>` | `.env.schema` (relative to `--dir`) | Path to the schema file. |

**Exit codes:**
- `0` — All validations passed.
- `1` — One or more validation failures. Errors printed to STDERR.

**Output on failure:**

```
✗ Environment validation failed:
  - DB_HOST is required but not defined.
  - DB_PORT must be an integer.
  - APP_ENV must be one of: local, staging, production.
```

### 4.3 `encrypt`

Encrypts all plaintext values in a `.env` file using AES-256-GCM.

```bash
vendor/bin/kariricode-dotenv encrypt <file> --key=<hex64> [--output=<path>]
```

| Option | Required | Description |
|---|---|---|
| `<file>` | Yes | Path to the `.env` file to encrypt. |
| `--key=<hex64>` | Yes | 64-character hex encryption key. |
| `--output=<path>` | No | Output path. If omitted, overwrites the input file. |

**Behavior:**
1. Parses the input file line by line.
2. For each `KEY=VALUE` line where the value does not already start with `encrypted:`, encrypts the value.
3. Preserves comments, empty lines, and formatting.
4. Already-encrypted values are left unchanged (idempotent).

**Output:**

```
Encrypted 5 values in .env
  DB_PASSWORD: encrypted
  API_SECRET: encrypted
  SMTP_PASSWORD: encrypted
  JWT_KEY: encrypted
  REDIS_PASSWORD: encrypted
Skipped 8 plaintext values (not encrypted)
Skipped 2 already-encrypted values
```

### 4.4 `decrypt`

Decrypts all encrypted values in a `.env` file, producing a plaintext version.

```bash
vendor/bin/kariricode-dotenv decrypt <file> --key=<hex64> [--output=<path>]
```

Same options and behavior as `encrypt`, but in reverse. Plaintext values pass through unchanged.

### 4.5 `cache:dump`

Generates an OPcache-friendly PHP cache file from the current `.env` configuration.

```bash
vendor/bin/kariricode-dotenv cache:dump [--dir=.] [--output=.env.cache.php]
```

| Option | Default | Description |
|---|---|---|
| `--output=<path>` | `.env.cache.php` (relative to `--dir`) | Cache file path. |

**Process:** Loads `.env` via `bootEnv()`, then calls `dumpCache()`.

### 4.6 `cache:clear`

Removes the PHP cache file.

```bash
vendor/bin/kariricode-dotenv cache:clear [--dir=.] [--file=.env.cache.php]
```

### 4.7 `diff`

Compares two `.env` files, showing added, removed, and changed variables.

```bash
vendor/bin/kariricode-dotenv diff <file1> <file2>
```

**Output:**

```
Comparing .env vs .env.production:

  + REDIS_HOST=redis.internal     (added in .env.production)
  - DEV_TOOLBAR=true              (removed in .env.production)
  ~ DB_HOST: localhost → prod-db.internal
  ~ APP_DEBUG: true → false

Summary: 2 changed, 1 added, 1 removed, 8 unchanged
```

### 4.8 `example:generate`

Generates a `.env.example` file from an existing `.env` by stripping all values.

```bash
vendor/bin/kariricode-dotenv example:generate [--dir=.] [--output=.env.example]
```

**Output file:**

```ini
# Auto-generated from .env — do not edit manually.
# Copy to .env and fill in the values.

DB_HOST=
DB_PORT=
DB_NAME=
DB_USER=
DB_PASSWORD=
APP_ENV=
APP_DEBUG=
APP_URL=
```

Comments from the source file are preserved. Values are stripped to empty strings.

### 4.9 `keygen`

Generates a new encryption key pair.

```bash
vendor/bin/kariricode-dotenv keygen
```

**Output:**

```
KaririCode\Dotenv — Key Generation

  Private key: a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a7b8c9d0e1f2a3b4c5d6a7b8c9d0e1f2
  Public ID:   f7e8d9c0

  Store the private key in DOTENV_PRIVATE_KEY environment variable.
  Never commit the private key to version control.

  Usage:
    export DOTENV_PRIVATE_KEY=a1b2c3d4...
    vendor/bin/kariricode-dotenv encrypt .env --key=a1b2c3d4...
```

## 5. Exit Codes

| Code | Meaning |
|---|---|
| `0` | Success |
| `1` | Validation failure or expected error (missing file, wrong key) |
| `2` | Usage error (unknown command, missing required option) |

## 6. Error Output

All errors are written to STDERR. Normal output goes to STDOUT. This allows piping:

```bash
vendor/bin/kariricode-dotenv debug 2>/dev/null | grep DB_
```

## 7. Autoload Resolution

The CLI resolves `vendor/autoload.php` by checking two paths:
1. `__DIR__ . '/../vendor/autoload.php'` — when installed as a root dependency.
2. `__DIR__ . '/../../../autoload.php'` — when installed as a dependency of another package.

If neither path exists, the CLI prints an error to STDERR and exits with code 1.

## 8. Option Parsing

Options are parsed from `$argv` with a simple `--key=value` convention. No external option parser is used (zero dependencies). Boolean flags use `--flag` (no value).

```php
function parseOptions(array $args): array
{
    $options = [];
    foreach ($args as $arg) {
        if (str_starts_with($arg, '--')) {
            $eqPos = strpos($arg, '=');
            if ($eqPos !== false) {
                $options[substr($arg, 2, $eqPos - 2)] = substr($arg, $eqPos + 1);
            } else {
                $options[substr($arg, 2)] = true;
            }
        }
    }
    return $options;
}
```
