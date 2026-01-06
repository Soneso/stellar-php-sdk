# SEP-0005 (Key Derivation Methods for Stellar Keys) Compatibility Matrix

**Generated:** 2026-01-06 16:36:03

**SEP Version:** N/A

**SEP Status:** Final

**SDK Version:** 1.9.1

**SEP URL:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md

## SEP Summary

This Stellar Ecosystem Proposal describes methods for key derivation for
Stellar. This should improve key storage and moving keys between wallets and
apps.

## Overall Coverage

**Total Coverage:** 100% (23/23 fields)

- ✅ **Implemented:** 23/23
- ❌ **Not Implemented:** 0/23

**Required Fields:** 100% (15/15)

**Optional Fields:** 100% (8/8)

## Implementation Status

✅ **Implemented**

### Implementation Files

- `Soneso/StellarSDK/SEP/Derivation/BIP39.php`
- `Soneso/StellarSDK/SEP/Derivation/Mnemonic.php`
- `Soneso/StellarSDK/SEP/Derivation/HDNode.php`
- `Soneso/StellarSDK/SEP/Derivation/WordList.php`

### Key Classes

- **`BIP39`**
- **`Mnemonic`**
- **`HDNode`**
- **`WordList`**

## Coverage by Section

| Section | Coverage | Required Coverage | Implemented | Total |
|---------|----------|-------------------|-------------|-------|
| BIP-32 Key Derivation | 100% | 100% | 4 | 4 |
| BIP-39 Mnemonic Features | 100% | 100% | 5 | 5 |
| BIP-44 Multi-Account Support | 100% | 100% | 3 | 3 |
| Key Derivation Methods | 100% | 100% | 3 | 3 |
| Language Support | 100% | 100% | 8 | 8 |

## Detailed Field Comparison

### BIP-32 Key Derivation

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `hd_key_derivation` | ✓ | ✅ | `derivePath` | BIP-32 hierarchical deterministic key derivation |
| `ed25519_curve` | ✓ | ✅ | `newMasterNode` | Support Ed25519 curve for Stellar keys |
| `master_key_generation` | ✓ | ✅ | `newMasterNode` | Generate master key from seed |
| `child_key_derivation` | ✓ | ✅ | `derive` | Derive child keys from parent keys |

### BIP-39 Mnemonic Features

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `mnemonic_generation_12_words` | ✓ | ✅ | `generate12WordsMnemonic` | Generate 12-word BIP-39 mnemonic phrase |
| `mnemonic_generation_24_words` | ✓ | ✅ | `generate24WordsMnemonic` | Generate 24-word BIP-39 mnemonic phrase |
| `mnemonic_validation` | ✓ | ✅ | `reverse` | Validate BIP-39 mnemonic phrase (word list and checksum) |
| `mnemonic_to_seed` | ✓ | ✅ | `generateSeed` | Convert BIP-39 mnemonic to seed using PBKDF2 |
| `passphrase_support` |  | ✅ | `generateSeed` | Support optional BIP-39 passphrase (25th word) |

### BIP-44 Multi-Account Support

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `stellar_derivation_path` | ✓ | ✅ | `m44148keyHex` | Support Stellar's BIP-44 derivation path: m/44'/148'/account' |
| `multiple_accounts` | ✓ | ✅ | `derive` | Derive multiple Stellar accounts from single seed |
| `account_index_support` | ✓ | ✅ | `derive` | Support account index parameter in derivation |

### Key Derivation Methods

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `keypair_from_mnemonic` | ✓ | ✅ | `generateSeed` | Generate Stellar KeyPair from mnemonic |
| `account_id_from_mnemonic` | ✓ | ✅ | `m44148keyHex` | Get Stellar account ID from mnemonic |
| `seed_from_mnemonic` | ✓ | ✅ | `generateSeed` | Convert mnemonic to raw seed bytes |

### Language Support

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `english` | ✓ | ✅ | `english.txt` | English BIP-39 word list (2048 words) |
| `chinese_simplified` |  | ✅ | `chinese_simplified.txt` | Chinese Simplified BIP-39 word list |
| `chinese_traditional` |  | ✅ | `chinese_traditional.txt` | Chinese Traditional BIP-39 word list |
| `french` |  | ✅ | `french.txt` | French BIP-39 word list |
| `italian` |  | ✅ | `italian.txt` | Italian BIP-39 word list |
| `japanese` |  | ✅ | `japanese.txt` | Japanese BIP-39 word list |
| `korean` |  | ✅ | `korean.txt` | Korean BIP-39 word list |
| `spanish` |  | ✅ | `spanish.txt` | Spanish BIP-39 word list |

## Implementation Gaps

🎉 **No gaps found!** All fields are implemented.

## Recommendations

✅ The SDK has full compatibility with SEP-0005!

## Legend

- ✅ **Implemented**: Field is implemented in SDK
- ❌ **Not Implemented**: Field is missing from SDK
- ✓ **Required**: Field is required by SEP specification
- (blank) **Optional**: Field is optional
