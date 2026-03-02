# ADR-004: AES-256-GCM Encryption Format

**Status:** Accepted
**Date:** 2024-02-10
**Applies to:** KaririCode\Dotenv 4.3+

## Context

Committing secrets to version control is the #1 source of credential leaks in modern applications. The traditional mitigation — external secret managers (Vault, AWS Secrets Manager, GCP KMS) — adds operational complexity and a runtime dependency on network availability. For many teams, the pragmatic solution is encrypting secrets directly in `.env` files, allowing them to be committed safely alongside application code.

Requirements:
1. Per-value encryption (not whole-file) — plaintext and encrypted values coexist.
2. Authenticated encryption — ciphertext integrity must be verifiable (no silent corruption).
3. No external tools or runtimes — pure PHP using bundled extensions.
4. Unique ciphertext per encryption — same plaintext must produce different ciphertext each time.
5. Self-describing format — encrypted values must be distinguishable from plaintext without metadata.

## Decision

### Algorithm: AES-256-GCM

AES-256-GCM is an AEAD (Authenticated Encryption with Associated Data) cipher that provides:
- **Confidentiality:** 256-bit AES encryption.
- **Integrity:** 128-bit GCM authentication tag detects tampering.
- **Performance:** Hardware-accelerated via AES-NI on modern CPUs.

PHP's ext-openssl (bundled in standard distributions) provides `openssl_encrypt()` / `openssl_decrypt()` with native GCM support.

### Wire Format

```
encrypted:<base64(nonce ‖ ciphertext ‖ tag)>
```

| Field | Size | Description |
|---|---|---|
| Prefix | 10 bytes | ASCII literal `encrypted:` — enables `str_starts_with()` detection. |
| Nonce | 12 bytes | Random IV per encryption. GCM requires exactly 96 bits. |
| Ciphertext | variable | AES-256-GCM output. 0 bytes for empty plaintext. |
| Tag | 16 bytes | GCM authentication tag (128 bits). |

The entire binary payload (nonce + ciphertext + tag) is base64-encoded into a single string.

### Key Format

Keys are 256-bit (32 bytes) values. Two representations are accepted:
- **Hex string:** 64 hexadecimal characters (e.g., `a1b2c3d4...`). Stored in `DOTENV_PRIVATE_KEY`.
- **Raw binary:** 32 bytes. Used internally after hex decoding.

The `KeyPair` class generates keys via `random_bytes(32)` and derives an 8-character public ID from `sha256(raw_key)[0:8]` for multi-environment key management.

### Encryption Flow

```
plaintext → random_bytes(12) → openssl_encrypt(AES-256-GCM) → "encrypted:" + base64(nonce ‖ ciphertext ‖ tag)
```

### Decryption Flow

```
"encrypted:..." → strip prefix → base64_decode → split(nonce, ciphertext, tag) → openssl_decrypt → plaintext
```

Non-encrypted values (`Encryptor::isEncrypted() === false`) pass through unchanged.

### Security Properties

1. **Nonce uniqueness:** `random_bytes(12)` ensures probabilistic uniqueness. Nonce reuse with the same key is cryptographically catastrophic for GCM; random generation provides 2⁹⁶ space.
2. **Authentication:** GCM tag prevents bit-flipping and truncation attacks. `openssl_decrypt()` returns `false` on tag mismatch.
3. **Key validation:** Constructor rejects keys that are not exactly 32 bytes after hex decoding.
4. **No padding oracle:** GCM is a streaming mode — no padding, no padding oracle.

## Consequences

**Positive:**
- Secrets can be safely committed to VCS — only the private key must be protected.
- No runtime dependency on external services — decryption is local and instant.
- Self-describing format — `encrypted:` prefix enables transparent decryption in the load pipeline.
- Each encryption produces unique ciphertext — safe for audit logs and diff tools.

**Negative:**
- Key rotation requires re-encrypting all values. The CLI `encrypt` command handles this.
- ext-openssl must be present. It is bundled in all standard PHP distributions but may be absent in Alpine-based minimal images.
- No key derivation function (KDF) — the key must be high-entropy (random bytes), not a password.

**Rejected alternatives:**
- **libsodium (XChaCha20-Poly1305):** Stronger nonce safety (192-bit nonce) but ext-sodium is not bundled in all distributions. AES-256-GCM with random 96-bit nonces is safe for the expected volume (< 2³² encryptions per key).
- **Whole-file encryption:** Would prevent `git diff` on plaintext values and require decrypting everything to read a single variable.
- **Envelope encryption (AWS KMS pattern):** Adds network dependency and complexity disproportionate to the use case.
