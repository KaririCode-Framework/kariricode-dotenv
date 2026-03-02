# SPEC-003: Encryption

**Version:** 1.0
**Date:** 2024-02-10
**Applies to:** KaririCode\Dotenv 4.3+

## 1. Overview

KaririCode\Dotenv supports per-value AES-256-GCM encryption, allowing secrets to be committed to version control while remaining confidential. Decryption is transparent during `load()` — no application code changes are required.

## 2. Prerequisites

- PHP extension: ext-openssl (bundled in standard PHP distributions)
- A 256-bit encryption key (generated via `KeyPair::generate()` or the `keygen` CLI command)

## 3. Key Management

### 3.1 Key Generation

```php
use KaririCode\Dotenv\Security\KeyPair;

$keyPair = KeyPair::generate();
echo $keyPair->privateKey; // 64-char hex string (256-bit key)
echo $keyPair->publicId;   // 8-char identifier (first 8 chars of SHA-256 hash)
```

CLI equivalent:

```bash
vendor/bin/kariricode-dotenv keygen
```

### 3.2 Key Format

| Representation | Length | Example |
|---|---|---|
| Hex string | 64 characters | `a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a7b8c9d0e1f2a3b4c5d6a7b8c9d0e1f2` |
| Raw binary | 32 bytes | *(binary)* |

The `Encryptor` constructor accepts both formats. Hex strings are auto-detected (64 chars, all hex digits) and decoded to binary internally.

### 3.3 Key Storage

The private key must **never** be committed to version control. Recommended storage:

1. **Environment variable:** `DOTENV_PRIVATE_KEY` (set by the orchestrator)
2. **CI/CD secret:** Injected during deployment
3. **Secret manager:** Retrieved at application bootstrap

### 3.4 Key Reconstitution

```php
$keyPair = KeyPair::fromPrivateKey($hexKey);
// Derives the same publicId from the key — useful for verification
```

### 3.5 Public ID

The 8-character public ID is a non-secret identifier derived from `sha256(raw_key)[0:8]`. It allows referencing keys in multi-environment setups without exposing the key material:

```
production: key_id=f7e8d9c0
staging:    key_id=a1b2c3d4
```

## 4. Wire Format

### 4.1 Encrypted Value

```
encrypted:<base64(nonce ‖ ciphertext ‖ tag)>
```

### 4.2 Binary Layout

```
Offset    Length    Field
0         12        Nonce (random IV)
12        N         Ciphertext (AES-256-GCM output, N ≥ 0)
12+N      16        Authentication Tag (GCM MAC)
```

Total binary size: `28 + plaintext_length` bytes (before base64 encoding).

### 4.3 Detection

A value is considered encrypted if and only if it starts with the ASCII prefix `encrypted:`:

```php
Encryptor::isEncrypted($value); // str_starts_with($value, 'encrypted:')
```

## 5. Encryption Process

```
Input:   plaintext string
Output:  "encrypted:" + base64(nonce + ciphertext + tag)

1. Generate 12 random bytes (nonce) via random_bytes(12)
2. Encrypt using openssl_encrypt():
   - Algorithm: aes-256-gcm
   - Key: 32-byte binary key
   - IV: nonce (12 bytes)
   - Options: OPENSSL_RAW_DATA
   - Tag output: 16 bytes
3. Concatenate: nonce + ciphertext + tag
4. Base64-encode the concatenation
5. Prepend "encrypted:" prefix
```

## 6. Decryption Process

```
Input:   "encrypted:" + base64_payload
Output:  plaintext string

1. Verify prefix "encrypted:" — if absent, return value unchanged (passthrough)
2. Strip prefix, base64_decode the remainder
3. Validate minimum length: ≥ 28 bytes (12 nonce + 0 ciphertext + 16 tag)
4. Extract:
   - nonce:      bytes[0..11]
   - tag:        bytes[-16..]
   - ciphertext: bytes[12..-16]
5. Decrypt using openssl_decrypt():
   - Algorithm: aes-256-gcm
   - Key: 32-byte binary key
   - IV: nonce
   - Options: OPENSSL_RAW_DATA
   - Tag: 16-byte tag
6. If openssl_decrypt returns false → throw RuntimeException
7. Return plaintext
```

## 7. Integration with Load Pipeline

Decryption occurs inside `Dotenv::setVariable()`, after allow/deny filtering and before type detection:

```
Raw value from parser
  → Allow/deny check
  → Encryption detection (str_starts_with 'encrypted:')
    → Yes: decrypt → decrypted string
    → No: passthrough
  → Type detection → Type casting → Processor application → Store
```

The decrypted value is stored in `EnvironmentVariable::$rawValue` and populated into `$_ENV`/`$_SERVER`. The original encrypted string is never stored in application-accessible state.

## 8. Configuration

### 8.1 Via DotenvConfiguration

```php
$config = new DotenvConfiguration(
    encryptionKey: 'a1b2c3d4...',  // 64-char hex
);
```

### 8.2 Via Environment Variable

If `DotenvConfiguration::$encryptionKey` is null, the Dotenv constructor checks:
1. `$_SERVER['DOTENV_PRIVATE_KEY']`
2. `$_ENV['DOTENV_PRIVATE_KEY']`

This allows setting the key once in the container orchestrator.

### 8.3 No Key Configured

If no encryption key is available, encrypted values remain as-is (the literal `encrypted:...` string). No error is thrown — this allows committed `.env` files with encrypted values to be loaded in environments where decryption is not needed (e.g., build systems that only need non-secret variables).

## 9. Security Properties

| Property | Guarantee |
|---|---|
| Confidentiality | AES-256 encryption (256-bit key space) |
| Integrity | GCM authentication tag (128-bit MAC) |
| Nonce uniqueness | 96-bit random nonce per encryption (2⁹⁶ space) |
| No padding oracle | GCM is a streaming mode — no padding |
| Key validation | Rejects keys ≠ 32 bytes at construction time |
| Timing safety | `openssl_decrypt()` uses constant-time tag comparison internally |

## 10. Error Conditions

| Condition | Exception | Message |
|---|---|---|
| Key not 32 bytes | `InvalidArgumentException` | "Encryption key must be 32 bytes (256-bit) or 64-char hex string." |
| `openssl_encrypt()` fails | `RuntimeException` | "Encryption failed: {openssl_error_string}" |
| Invalid base64 payload | `RuntimeException` | "Invalid encrypted payload: malformed base64 or too short." |
| Decryption failure (wrong key, corruption) | `RuntimeException` | "Decryption failed — wrong key or corrupted payload." |

## 11. CLI Commands

### 11.1 `keygen`

Generates a new key pair and prints both values.

### 11.2 `encrypt`

Encrypts all plaintext values in a `.env` file, writing the result to the same file or a specified output path.

```bash
vendor/bin/kariricode-dotenv encrypt .env --key=a1b2c3d4...
vendor/bin/kariricode-dotenv encrypt .env --key=a1b2c3d4... --output=.env.encrypted
```

### 11.3 `decrypt`

Decrypts all encrypted values, producing a plaintext `.env` file.

```bash
vendor/bin/kariricode-dotenv decrypt .env --key=a1b2c3d4...
```
