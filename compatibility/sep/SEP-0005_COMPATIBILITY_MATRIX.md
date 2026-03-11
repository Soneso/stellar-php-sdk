# SEP-05: Key Derivation Methods for Stellar Keys

**Status:** ✅ Supported  
**SDK Version:** 1.9.5  
**Generated:** 2026-03-11 21:41 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md)

## Overall Coverage

**Total Coverage:** 100.0% (19/19 fields)

- ✅ **Implemented:** 19/19
- ❌ **Not Implemented:** 0/19

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| BIP-39 Mnemonic Features | 100.0% | 6 | 6 |
| BIP-32 Key Derivation | 100.0% | 4 | 4 |
| Language Support | 100.0% | 9 | 9 |

## BIP-39 Mnemonic Features

Mnemonic phrase generation, validation, and seed derivation

| Feature | Status | Notes |
|---------|--------|-------|
| `generate12WordsMnemonic` | ✅ Supported | `Mnemonic.generate12WordsMnemonic()` |
| `generate15WordsMnemonic` | ✅ Supported | `Mnemonic.generate15WordsMnemonic()` |
| `generate24WordsMnemonic` | ✅ Supported | `Mnemonic.generate24WordsMnemonic()` |
| `mnemonicFromWords` | ✅ Supported | `Mnemonic.mnemonicFromWords()` |
| `generateSeed` | ✅ Supported | `Mnemonic.generateSeed()` |
| `passphrase_support` | ✅ Supported | `Mnemonic.generateSeed($passphrase)` |

## BIP-32 Key Derivation

Hierarchical Deterministic key derivation using Ed25519

| Feature | Status | Notes |
|---------|--------|-------|
| `master_key_generation` | ✅ Supported | `HDNode.newMasterNode()` |
| `child_key_derivation` | ✅ Supported | `HDNode.derive()` |
| `path_derivation` | ✅ Supported | `HDNode.derivePath()` |
| `stellar_derivation_path` | ✅ Supported | `Mnemonic.m44148keyHex()` |

## Language Support

BIP-39 word list languages

| Feature | Status | Notes |
|---------|--------|-------|
| `english` | ✅ Supported | `english.txt` |
| `chinese_simplified` | ✅ Supported | `chinese_simplified.txt` |
| `chinese_traditional` | ✅ Supported | `chinese_traditional.txt` |
| `french` | ✅ Supported | `french.txt` |
| `italian` | ✅ Supported | `italian.txt` |
| `japanese` | ✅ Supported | `japanese.txt` |
| `korean` | ✅ Supported | `korean.txt` |
| `spanish` | ✅ Supported | `spanish.txt` |
| `malay` | ✅ Supported | `malay.txt` |
