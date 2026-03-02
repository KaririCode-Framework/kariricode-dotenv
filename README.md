# KaririCode Dotenv

<div align="center">

[![PHP 8.4+](https://img.shields.io/badge/PHP-8.4%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License: MIT](https://img.shields.io/badge/License-MIT-22c55e.svg)](LICENSE)
[![PHPStan Level 9](https://img.shields.io/badge/PHPStan-Level%209-4F46E5)](https://phpstan.org/)
[![Tests](https://img.shields.io/badge/Tests-205%20passing-22c55e)](https://kariricode.org)
[![Zero Dependencies](https://img.shields.io/badge/Dependencies-0-22c55e)](composer.json)
[![ARFA](https://img.shields.io/badge/ARFA-1.3-orange)](https://kariricode.org)
[![KaririCode Framework](https://img.shields.io/badge/KaririCode-Framework-orange)](https://kariricode.org)

**The only PHP dotenv with AES-256-GCM encryption, OPcache caching, fluent validation DSL,  
environment-aware cascade loading, and auto type casting — zero dependencies, PHP 8.4+.**

[Installation](#installation) · [Quick Start](#quick-start) · [Features](#features) · [Validation DSL](#validation-dsl) · [Encryption](#encryption) · [Architecture](#architecture)

</div>

---

## The Problem

Every PHP project reinvents the same wheel:

```php
// No type safety — you get raw strings everywhere
$_ENV['DB_PORT']  // "5432" (string, not int)
$_ENV['DEBUG']    // "true" (string, not bool)

// No validation — missing vars discovered at runtime
// No encryption — secrets sit as plaintext in .env files
// No cascade — can't load .env.local over .env automatically
```

## The Solution

```php
$dotenv = new Dotenv(__DIR__);
$dotenv->load();

// Auto-typed
env('DB_PORT');   // 5432 (int)
env('DEBUG');     // true (bool)

// Validated before service boot
$dotenv->validate()
    ->required('DB_HOST', 'DB_PORT')
    ->isInteger('DB_PORT')->between(1, 65535)
    ->url('APP_URL')
    ->email('ADMIN_EMAIL')
    ->assert();

// Encrypted secrets — transparent decryption
// SECRET=encrypted:base64data...
$dotenv->get('SECRET');  // "my-actual-secret"
```

---

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.4 or higher |
| ext-openssl | Optional (required for encryption) |

---

## Installation

```bash
composer require kariricode/dotenv
```

---

## Quick Start

```bash
# 1. Create your .env
APP_ENV=production
APP_URL=https://myapp.com
DB_HOST=localhost
DB_PORT=5432
APP_DEBUG=false
```

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use KaririCode\Dotenv\Dotenv;
use function KaririCode\Dotenv\env;

// Load .env from project root
$dotenv = new Dotenv(__DIR__);
$dotenv->load();

// Typed access via helper
$port  = env('DB_PORT');    // int: 5432
$debug = env('APP_DEBUG');  // bool: false
$host  = env('DB_HOST');    // string: "localhost"
```

---

## Features

### Auto Type Casting

All values are automatically cast to their native PHP type:

```env
STRING_VAR=Hello World
INT_VAR=42
FLOAT_VAR=3.14
BOOL_VAR=true
NULL_VAR=null
JSON_VAR={"key": "value", "nested": {"subkey": "subvalue"}}
ARRAY_VAR=["item1", "item2", "item3"]
```

```php
env('STRING_VAR');  // string:  "Hello World"
env('INT_VAR');     // int:     42
env('FLOAT_VAR');   // float:   3.14
env('BOOL_VAR');    // bool:    true
env('NULL_VAR');    // null
env('JSON_VAR');    // array:   ["key" => "value", "nested" => [...]]
env('ARRAY_VAR');   // array:   ["item1", "item2", "item3"]
```

### Variable Interpolation

```env
APP_NAME=KaririCode
GREETING="Welcome to ${APP_NAME}"              # → "Welcome to KaririCode"
HAS_REDIS=${REDIS_HOST:+yes}                   # "yes" if REDIS_HOST is set
FALLBACK=${MISSING_VAR:-default-value}         # "default-value" if unset
```

### Load Modes

```php
use KaririCode\Dotenv\Enum\LoadMode;
use KaririCode\Dotenv\ValueObject\DotenvConfiguration;

// Immutable (default) — skip vars already in environment
$config = new DotenvConfiguration(loadMode: LoadMode::Immutable);

// SkipExisting — keep existing $_ENV values, skip .env values
$config = new DotenvConfiguration(loadMode: LoadMode::SkipExisting);

// Overwrite — .env always wins
$config = new DotenvConfiguration(loadMode: LoadMode::Overwrite);
```

### Environment Cascade (`bootEnv`)

Loads files in priority order, later files overriding earlier ones:

```
.env  →  .env.local  →  .env.{APP_ENV}  →  .env.{APP_ENV}.local
```

```php
$dotenv = new Dotenv(__DIR__, new DotenvConfiguration(loadMode: LoadMode::Overwrite));
$dotenv->bootEnv();          // reads APP_ENV from environment automatically
$dotenv->bootEnv('staging'); // explicit environment
```

> `.env.test.local` is **always skipped** when `APP_ENV=test` — ensuring reproducible test runs.

### Multiple Files

```php
$dotenv = new Dotenv(__DIR__, $config, '.env', '.env.local');
$dotenv->load();
```

### Allow / Deny Lists (glob patterns)

```php
// Only load DB_* variables
$config = new DotenvConfiguration(allowList: ['DB_*']);

// Load everything except SECRET*
$config = new DotenvConfiguration(denyList: ['SECRET*']);
```

---

## Validation DSL

Fluent, chainable validation with **all errors collected before throwing** (no fail-fast):

```php
$dotenv->validate()
    ->required('DB_HOST', 'DB_PORT', 'APP_ENV')
    ->notEmpty('DB_HOST')
    ->isInteger('DB_PORT')->between(1, 65535)
    ->isBoolean('APP_DEBUG')
    ->allowedValues('APP_ENV', ['local', 'staging', 'production'])
    ->url('APP_URL')
    ->email('ADMIN_EMAIL')
    ->matchesRegex('BUILD_SHA', '/^[a-f0-9]{40}$/')
    ->ifPresent('REDIS_HOST')->notEmpty()
    ->custom('DB_DSN', fn(string $v): bool => str_starts_with($v, 'pgsql:'))
    ->assert(); // throws ValidationException with ALL failures at once
```

### Schema-Based Validation (`.env.schema`)

```ini
[DB_HOST]
required = true
notEmpty = true

[DB_PORT]
required = true
type     = integer
min      = 1
max      = 65535

[APP_ENV]
required = true
allowed  = local, staging, production
```

```php
$dotenv->loadWithSchema('/path/to/.env.schema');
```

---

## Encryption

AES-256-GCM authenticated encryption for secrets. Encrypted values use the `encrypted:` prefix and are **transparently decrypted** on load.

```php
use KaririCode\Dotenv\Security\KeyPair;
use KaririCode\Dotenv\Security\Encryptor;

// Generate a key pair (store the private key securely!)
$keyPair  = KeyPair::generate();
$encryptor = new Encryptor($keyPair->privateKey);

// Encrypt a secret
$encrypted = $encryptor->encrypt('my-secret-password');
// → "encrypted:base64encodedpayload..."
```

```env
# .env — commit this (value is opaque ciphertext)
DB_PASSWORD=encrypted:aGVsbG8gd29ybGQ...
```

```php
// Decryption happens transparently during load
$config = new DotenvConfiguration(encryptionKey: $keyPair->privateKey);
$dotenv = new Dotenv(__DIR__, $config);
$dotenv->load();

$dotenv->get('DB_PASSWORD');  // "my-secret-password"
```

---

## OPcache Caching

Compile parsed variables into an OPcache-friendly PHP file. Subsequent requests load from shared memory with **zero parsing cost**:

```php
$dotenv->load();
$dotenv->dumpCache('/path/to/.env.cache.php');

// Next request:
$config = new DotenvConfiguration(cachePath: '/path/to/.env.cache.php');
$dotenv = new Dotenv(__DIR__, $config);
$dotenv->load(); // loaded from OPcache — no file I/O
```

---

## Variable Processors

Transform values after parsing, with **glob pattern matching** for key selection:

```php
use KaririCode\Dotenv\Processor\CsvToArrayProcessor;
use KaririCode\Dotenv\Processor\UrlNormalizerProcessor;
use KaririCode\Dotenv\Processor\TrimProcessor;
use KaririCode\Dotenv\Processor\Base64DecodeProcessor;

$dotenv->addProcessor('ALLOWED_IPS', new CsvToArrayProcessor());   // "a, b, c" → ["a","b","c"]
$dotenv->addProcessor('*_URL', new UrlNormalizerProcessor());       // glob: all *_URL keys
$dotenv->addProcessor('API_TOKEN', new Base64DecodeProcessor());
$dotenv->addProcessor('DB_HOST', new TrimProcessor());
```

---

## Debug & Introspection

```php
$dotenv->load();

// Source tracking — where did each variable come from?
$debug = $dotenv->debug();
// ['DB_HOST' => ['source' => '.env.local', 'type' => 'String', 'value' => 'localhost', 'overridden' => true]]

// All loaded variables as EnvironmentVariable value objects
$vars = $dotenv->variables();

// Safe load — skip missing files instead of throwing
$dotenv->safeLoad();
```

---

## Architecture

### Source layout

```
src/
├── Cache/           OPcache-friendly PHP file cache (PhpFileCache)
├── Contract/        Interfaces: TypeCaster · TypeDetector · ValidationRule · VariableProcessor
├── Core/            DotenvParser — full .env syntax support
├── Dotenv.php       Main facade — load · validate · encrypt · cache · bootEnv
├── Enum/            LoadMode · ValueType
├── Exception/       DotenvException hierarchy (5 classes)
├── Processor/       CsvToArray · UrlNormalizer · Trim · Base64Decode
├── Schema/          SchemaParser — .env.schema declarative validation
├── Security/        Encryptor (AES-256-GCM) · KeyPair
├── Type/            TypeSystem + 6 Detectors + 6 Casters
├── Validation/      EnvironmentValidator (fluent DSL) + 10 Rule classes
├── ValueObject/     DotenvConfiguration (immutable) · EnvironmentVariable (immutable)
└── env.php          Global env() helper
```

### Key design decisions

| Decision | Rationale | ADR |
|---|---|---|
| Zero dependencies | No version conflicts, sub-ms boot | [ADR-001](docs/adr/ADR-001-zero-dependencies.md) |
| Immutable configuration | Thread-safe, ARFA 1.3 compliant | [ADR-002](docs/adr/ADR-002-immutable-configuration.md) |
| Pluggable type system | Extend without modifying framework code | [ADR-003](docs/adr/ADR-003-type-system.md) |
| AES-256-GCM encryption | Authenticated encryption, nonce-per-value | [ADR-004](docs/adr/ADR-004-encryption-format.md) |
| OPcache caching | Zero parse overhead in production | [ADR-005](docs/adr/ADR-005-opcache-cache.md) |
| Fluent validation DSL | Collect all errors before throwing | [ADR-008](docs/adr/ADR-008-validation-strategy.md) |

### Specifications

| Spec | Covers |
|---|---|
| [SPEC-001](docs/spec/SPEC-001-env-syntax.md) | `.env` file syntax and parsing rules |
| [SPEC-002](docs/spec/SPEC-002-type-system.md) | Type detection and casting |
| [SPEC-003](docs/spec/SPEC-003-encryption.md) | Encryption format and key derivation |
| [SPEC-004](docs/spec/SPEC-004-validation.md) | Validation DSL API |
| [SPEC-005](docs/spec/SPEC-005-schema-format.md) | `.env.schema` format |
| [SPEC-006](docs/spec/SPEC-006-processors.md) | Variable processor contract |
| [SPEC-007](docs/spec/SPEC-007-cli.md) | CLI tooling |

---

## Project Stats

| Metric | Value |
|---|---|
| PHP source files | 38 |
| External runtime dependencies | 0 |
| Test suite | 205 tests · 396 assertions |
| PHPStan level | 9 |
| PHP version | 8.4+ |
| ARFA compliance | 1.3 |
| Encryption | AES-256-GCM |
| Type detection | 7 built-in types (extensible) |
| Validation rules | 10 built-in rules (extensible) |
| Variable processors | 4 built-in (extensible) |

---

## Contributing

```bash
git clone https://github.com/KaririCode-Framework/kariricode-dotenv.git
cd kariricode-dotenv
composer install
kcode init
kcode quality  # Must pass before opening a PR
```

---

## License

[MIT License](LICENSE) © [Walmir Silva](mailto:community@kariricode.org)

---

<div align="center">

Part of the **[KaririCode Framework](https://kariricode.org)** ecosystem.

[kariricode.org](https://kariricode.org) · [GitHub](https://github.com/KaririCode-Framework/kariricode-dotenv) · [Packagist](https://packagist.org/packages/kariricode/dotenv) · [Issues](https://github.com/KaririCode-Framework/kariricode-dotenv/issues)

</div>
