# SEP-23: Strkeys

**Status:** ✅ Supported  
**SEP Version:** 1.3.0  
**SEP Status:** Active  
**SDK Version:** 1.15.0  
**Generated:** 2026-09-30 19:01 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0023.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0023.md)

## Overall Coverage

**Total Coverage:** 100.0% (32/32 fields)

- ✅ **Implemented:** 32/32
- ❌ **Not Implemented:** 0/32

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| Key types | 100.0% | 9 | 9 |
| Test vectors quoted in the StrKey unit test files | 100.0% | 23 | 23 |

## Key types

Version-byte table of the specification. A key type is implemented when `VersionByte` defines its base value and `StrKey` has an encode and a decode method for it.

| Feature | Status | Notes |
|---------|--------|-------|
| `STRKEY_PUBKEY` | ✅ Supported | `VersionByte::ACCOUNT_ID, StrKey::encodeAccountId() / decodeAccountId()` |
| `STRKEY_MUXED` | ✅ Supported | `VersionByte::MUXED_ACCOUNT_ID, StrKey::encodeMuxedAccountId() / decodeMuxedAccountId()` |
| `STRKEY_PRIVKEY` | ✅ Supported | `VersionByte::SEED, StrKey::encodeSeed() / decodeSeed()` |
| `STRKEY_PRE_AUTH_TX` | ✅ Supported | `VersionByte::PRE_AUTH_TX, StrKey::encodePreAuthTx() / decodePreAuthTx()` |
| `STRKEY_HASH_X` | ✅ Supported | `VersionByte::SHA256_HASH, StrKey::encodeSha256Hash() / decodeSha256Hash()` |
| `STRKEY_SIGNED_PAYLOAD` | ✅ Supported | `VersionByte::SIGNED_PAYLOAD, StrKey::encodeSignedPayload() / decodeSignedPayload()` |
| `STRKEY_CONTRACT` | ✅ Supported | `VersionByte::CONTRACT_ID, StrKey::encodeContractId() / decodeContractId()` |
| `STRKEY_LIQUIDITY_POOL` | ✅ Supported | `VersionByte::LIQUIDITY_POOL_ID, StrKey::encodeLiquidityPoolId() / decodeLiquidityPoolId()` |
| `STRKEY_CLAIMABLE_BALANCE` | ✅ Supported | `VersionByte::CLAIMABLE_BALANCE_ID, StrKey::encodeClaimableBalanceId() / decodeClaimableBalanceId()` |

## Test vectors quoted in the StrKey unit test files

Valid and invalid test cases of the specification. A vector counts when it is the complete content of a quoted string literal in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php`.

| Feature | Status | Notes |
|---------|--------|-------|
| `valid_01` | ✅ Supported | Valid non-multiplexed account, quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `valid_02` | ✅ Supported | Valid multiplexed account, quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `valid_03` | ✅ Supported | Valid multiplexed account in which unsigned id exceeds maximum signed 64-bit integer, quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `valid_04` | ✅ Supported | Valid signed payload with an ed25519 public key and a 32-byte payload, quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `valid_05` | ✅ Supported | Valid signed payload with an ed25519 public key and a 29-byte payload which becomes zero padded, quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `valid_06` | ✅ Supported | Valid contract, quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `valid_07` | ✅ Supported | Valid liquidity pool address, quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `valid_08` | ✅ Supported | Valid claimable balance address, quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `invalid_01` | ✅ Supported | Invalid length (Ed25519 should be 32 bytes, not 5), quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `invalid_02` | ✅ Supported | The unused trailing bit must be zero in the encoding of the last three bytes (24 bits) as five base-32 symbols (25 bits), quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `invalid_03` | ✅ Supported | Invalid length (congruent to 1 mod 8), quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `invalid_04` | ✅ Supported | Invalid length (base-32 decoding should yield 35 bytes, not 36), quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `invalid_05` | ✅ Supported | Invalid algorithm (low 3 bits of version byte are 7), quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `invalid_06` | ✅ Supported | Invalid length (congruent to 6 mod 8), quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `invalid_07` | ✅ Supported | Invalid length (base-32 decoding should yield 43 bytes, not 44), quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `invalid_08` | ✅ Supported | Invalid algorithm (low 3 bits of version byte are 7), quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `invalid_09` | ✅ Supported | Padding bytes are not allowed, quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `invalid_10` | ✅ Supported | Invalid checksum, quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `invalid_11` | ✅ Supported | Length prefix specifies length that is shorter than payload in signed payload, quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `invalid_12` | ✅ Supported | Length prefix specifies length that is longer than payload in signed payload, quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `invalid_13` | ✅ Supported | No zero padding in signed payload, quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `invalid_14` | ✅ Supported | The unused trailing 2-bits must be zero in the encoding of the last symbol, quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
| `invalid_15` | ✅ Supported | Invalid claimable balance type (first byte of binary key is not 0), quoted in `Soneso/StellarSDKTests/Unit/Crypto/StrKeyTest.php` |
