# SEP-23: Strkey Encoding

SEP-23 defines how Stellar encodes addresses between raw binary data and human-readable strings. Each address type starts with a specific letter — account IDs start with "G", secret seeds with "S", muxed accounts with "M", contracts with "C", and so on.

**When to use:** Validating user-entered addresses, converting between raw bytes and string representations, working with different key types, and creating muxed accounts for sub-account tracking.

See the [SEP-23 specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0023.md) for protocol details.

## Quick example

This example demonstrates the most common strkey operations: generating a keypair, validating addresses, and converting between formats.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;

// Generate a keypair
$keyPair = KeyPair::random();
$accountId = $keyPair->getAccountId();  // G...

// Validate an address
if (StrKey::isValidAccountId($accountId)) {
    echo "Valid account ID" . PHP_EOL;
}

// Decode to raw bytes and encode back
$rawPublicKey = StrKey::decodeAccountId($accountId);
$encoded = StrKey::encodeAccountId($rawPublicKey);
```

## Account IDs and secret seeds

Account IDs (G...) are public keys that identify accounts on the network. Secret seeds (S...) are private keys used for signing transactions — never share these publicly.

```php
<?php

use Soneso\StellarSDK\Crypto\StrKey;

$accountId = 'GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D';
$secretSeed = 'SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE';

// Validate
StrKey::isValidAccountId($accountId);  // true
StrKey::isValidSeed($secretSeed);      // true

// Decode to raw 32-byte keys
$rawPublicKey = StrKey::decodeAccountId($accountId);
$rawPrivateKey = StrKey::decodeSeed($secretSeed);

// Encode raw bytes back to string
$encoded = StrKey::encodeAccountId($rawPublicKey);
$encodedSeed = StrKey::encodeSeed($rawPrivateKey);

// The seed above belongs to the account above, so this returns $accountId
$derivedAccountId = StrKey::accountIdFromSeed($secretSeed);
```

## Muxed accounts (M...)

Muxed accounts (defined in [CAP-27](https://github.com/stellar/stellar-protocol/blob/master/core/cap-0027.md)) allow you to multiplex multiple virtual accounts onto a single Stellar account. This is useful for exchanges, payment processors, and custodial services that need to track funds for many users without creating separate on-chain accounts.

A muxed account combines:
- An Ed25519 account ID (G-address) — the underlying Stellar account
- A 64-bit unsigned integer ID — identifies the virtual sub-account

When encoded, muxed accounts start with "M" instead of "G".

### Creating muxed accounts

You can create muxed accounts by combining a G-address with a numeric ID, or by parsing an M-address string.

```php
<?php

use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\MuxedAccount;

$accountId = 'GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ';
$userId = 1234567890;

// Create a muxed account from G-address and ID
$muxedAccount = new MuxedAccount($accountId, $userId);
$muxedAccountId = $muxedAccount->getAccountId(); // M...

// Parse an existing M-address
$parsedMuxed = MuxedAccount::fromAccountId('MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAETFQC2K6JE');
```

### Extracting muxed account components

When you receive an M-address, you can extract both the underlying G-address and the numeric ID.

```php
<?php

use Soneso\StellarSDK\MuxedAccount;

$muxedAccountId = 'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAETFQC2K6JE';

$muxedAccount = MuxedAccount::fromAccountId($muxedAccountId);

// Get the underlying G-address (the actual on-chain account)
$ed25519AccountId = $muxedAccount->getEd25519AccountId();
echo "Underlying account: " . $ed25519AccountId . PHP_EOL;

// Get the 64-bit ID (identifies the virtual sub-account)
$id = $muxedAccount->getId();
echo "User ID: " . $id . PHP_EOL;

// Get the M-address (same as input for muxed, or G-address if no ID)
$accountId = $muxedAccount->getAccountId();
```

### Using muxed accounts in transactions

Muxed accounts can be used as source accounts and destinations in operations. The Stellar network processes these using the underlying G-address, while preserving the ID for tracking purposes.

```php
<?php

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

// Sender keypair (must control the underlying G-address)
$senderKeyPair = KeyPair::fromSeed('SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE');
$senderAccountId = $senderKeyPair->getAccountId();

// Create muxed source account (sender with user ID 100)
$muxedSource = new MuxedAccount($senderAccountId, 100);

// Create muxed destination (recipient with user ID 200)
$destinationAccountId = 'GCEZWKCA5VLDNRLN3RPRJMRZOX3Z6G5CHCGSNFHEYVXM3XOJMDS674JZ';
$muxedDestination = new MuxedAccount($destinationAccountId, 200);

// Build payment operation with muxed destination
$paymentOp = (new PaymentOperationBuilder(
    $muxedDestination->getAccountId(), // Can use M-address directly
    Asset::native(),
    '10.0'
))->build();

// Note: The source account for signing must be the underlying G-address
$sdk = StellarSDK::getTestNetInstance();
$sourceAccount = $sdk->requestAccount($senderAccountId);

$transaction = (new TransactionBuilder($sourceAccount))
    ->addOperation($paymentOp)
    ->build();

$transaction->sign($senderKeyPair, Network::testnet());
```

### Low-level muxed account encoding

For direct manipulation of muxed account binary data, use the StrKey class methods.

```php
<?php

use Soneso\StellarSDK\Crypto\StrKey;

$muxedAccountId = 'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAETFQC2K6JE';

// Validate M-address format
StrKey::isValidMuxedAccountId($muxedAccountId); // true

// Decode to raw binary (40 bytes: 32-byte public key + 8-byte ID)
$rawData = StrKey::decodeMuxedAccountId($muxedAccountId);

// Encode raw binary back to M-address
$encoded = StrKey::encodeMuxedAccountId($rawData);
```

## Pre-auth TX and SHA-256 hashes

Pre-auth transaction hashes (T...) authorize specific transactions in advance. SHA-256 hashes (X...) are for hash-locked transactions that require revealing a preimage to sign.

```php
<?php

use Soneso\StellarSDK\Crypto\StrKey;

// Pre-auth TX (T...)
$transactionHash = random_bytes(32);
$preAuthTx = StrKey::encodePreAuthTx($transactionHash);
StrKey::isValidPreAuthTx($preAuthTx); // true
$decoded = StrKey::decodePreAuthTx($preAuthTx);

// SHA-256 hash signer (X...)
$hash = hash('sha256', 'secret preimage', true);
$hashSigner = StrKey::encodeSha256Hash($hash);
StrKey::isValidSha256Hash($hashSigner); // true
$decoded = StrKey::decodeSha256Hash($hashSigner);
```

## Contract IDs (C...)

Soroban smart contracts are identified by C-addresses. These encode the 32-byte contract hash.

```php
<?php

use Soneso\StellarSDK\Crypto\StrKey;

$contractId = 'CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE';

// Validate
StrKey::isValidContractId($contractId); // true

// Decode to raw bytes or hex
$raw = StrKey::decodeContractId($contractId);
$hex = StrKey::decodeContractIdHex($contractId);

// Encode from raw bytes or hex
$encoded = StrKey::encodeContractId($raw);
$encodedFromHex = StrKey::encodeContractIdHex($hex);
```

## Signed payloads (P...)

Signed payloads (defined in [CAP-40](https://github.com/stellar/stellar-protocol/blob/master/core/cap-0040.md)) combine a public key with arbitrary payload data. They're used for delegated signing scenarios where a signature covers both the transaction and additional application-specific data.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\SignedPayloadSigner;

$keyPair = KeyPair::random();
$payload = random_bytes(32); // 1-64 bytes

$signer = SignedPayloadSigner::fromAccountId($keyPair->getAccountId(), $payload);
$signedPayload = StrKey::encodeSignedPayload($signer); // P...

$decoded = StrKey::decodeSignedPayload($signedPayload);
echo $decoded->getSignerAccountId()->getAccountId() . PHP_EOL;

StrKey::isValidSignedPayload($signedPayload); // true
```

## Liquidity pool and claimable balance IDs

Pool IDs (L...) identify AMM liquidity pools. Claimable balance IDs (B...) reference claimable balance entries. Both support hex encoding for interoperability with APIs.

A pool id is the bare 32-byte pool hash, so its hex form is 64 characters. A claimable balance id carries a type discriminant ahead of its 32-byte hash, and Horizon spells that discriminant as 4 bytes: the ids you read from the Horizon API are 72 hex characters. `encodeClaimableBalanceIdHex()` takes the hash on its own (64 characters), the hash behind the 1-byte strkey discriminant (66 characters), which is the form `decodeClaimableBalanceIdHex()` returns, or the 72-character Horizon form.

```php
<?php

use Soneso\StellarSDK\Crypto\StrKey;

// Liquidity pool ID (L...) from the 32-byte pool hash
$poolHex = 'dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7';
$poolId = StrKey::encodeLiquidityPoolIdHex($poolHex);
StrKey::isValidLiquidityPoolId($poolId); // true
$decodedPoolHex = StrKey::decodeLiquidityPoolIdHex($poolId); // same as $poolHex

// Claimable balance ID (B...) straight from what Horizon reports
$horizonBalanceId = '00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072';
$balanceId = StrKey::encodeClaimableBalanceIdHex($horizonBalanceId);
StrKey::isValidClaimableBalanceId($balanceId); // true

// Decoding gives back the 33-byte payload: discriminant byte, then the hash
$decodedBalanceHex = StrKey::decodeClaimableBalanceIdHex($balanceId);
```

## Version bytes reference

Each strkey type has a unique version byte that determines its prefix character:

| Prefix | Type | Description |
|--------|------|-------------|
| G | Account ID | Ed25519 public key |
| S | Secret Seed | Ed25519 private key |
| M | Muxed Account | Account ID + 64-bit ID |
| T | Pre-Auth TX | Pre-authorized transaction hash |
| X | SHA-256 Hash | Hash signer |
| P | Signed Payload | Public key + payload |
| C | Contract ID | Soroban smart contract |
| L | Liquidity Pool ID | AMM liquidity pool |
| B | Claimable Balance | Claimable balance entry |

## Validation rules

`decode*` and `isValid*` apply the same rule to the same string. Every rejection on the decode path raises `InvalidArgumentException`, and `isValid*` returns `false` for exactly those inputs.

Every malformed input — wrong length, non-canonical base32, wrong version byte, bad checksum, wrong payload size — raises `InvalidArgumentException`, and no PHP warning precedes the exception, even for empty input.

| Prefix | Encoded characters | Payload bytes |
|--------|--------------------|---------------|
| G, S, T, X, C, L | 56 | 32 |
| M | 69 | 40 |
| B | 58 | 33 |
| P | 69 to 165 | variable, set by the framing rules below |

```php
<?php

use Soneso\StellarSDK\Crypto\StrKey;

$accountId = 'GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D';

// This validates, so it also decodes
StrKey::isValidAccountId($accountId);       // true
$raw = StrKey::decodeAccountId($accountId); // 32 bytes

try {
    StrKey::decodeAccountId('');
} catch (InvalidArgumentException $e) {
    echo $e->getMessage() . PHP_EOL; // G-strkey must be 56 characters long, 0 characters given
}
```

### Signed payload framing

A P-strkey holds the 32-byte signer key, a 4-byte payload length prefix, and the payload padded to a multiple of 4 bytes. Decoding requires all three of:

- a payload of 1 to 64 bytes, since a zero-length payload has no strkey representation
- an exact fit, with no bytes after the padded payload
- every padding byte zero, the fill RFC 4506 requires XDR to write

The zero-padding rule leaves a signer with exactly one spelling, so validated P-addresses can be compared as strings (allowlists, deduplication, equality).

### Claimable balance discriminant

The first payload byte of a B-strkey is the `ClaimableBalanceID` union discriminant. `CLAIMABLE_BALANCE_ID_TYPE_V0` (0) is the only case that union defines, so `decodeClaimableBalanceId()`, `decodeClaimableBalanceIdHex()` and `isValidClaimableBalanceId()` reject any other value.

### Encoding

`encode*` rejects a payload whose length is wrong for the type. `encodeAccountId()`, `encodeSeed()`, `encodePreAuthTx()`, `encodeSha256Hash()`, `encodeContractId()` and `encodeLiquidityPoolId()` take 32 bytes; `encodeMuxedAccountId()` takes 40; `encodeClaimableBalanceId()` takes 33 bytes led by the zero discriminant, the bare 32-byte hash, which it prefixes with that discriminant itself, or the 36-byte XDR form, whose 4-byte discriminant it narrows to one byte.

`encodeContractIdHex()`, `encodeLiquidityPoolIdHex()` and `encodeClaimableBalanceIdHex()` reject input that is not valid hexadecimal with `InvalidArgumentException`, before any decoding happens.

```php
<?php

use Soneso\StellarSDK\Crypto\StrKey;

// A payload of the wrong size is rejected
try {
    StrKey::encodeAccountId(random_bytes(20));
} catch (InvalidArgumentException $e) {
    echo $e->getMessage() . PHP_EOL; // G-strkey requires a payload of 32 bytes, 20 bytes given
}

// The hex encoders check the hexadecimal first
try {
    StrKey::encodeContractIdHex('zz');
} catch (InvalidArgumentException $e) {
    // $contractId must contain only hexadecimal characters [0-9a-fA-F], "z" found at index 0
    echo $e->getMessage() . PHP_EOL;
}
```

## Error handling

Invalid addresses throw `InvalidArgumentException`. Use validation methods to check addresses before decoding to avoid exceptions in user-facing code.

```php
<?php

use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\MuxedAccount;

// Invalid checksum or wrong version byte throws
try {
    StrKey::decodeAccountId('GINVALIDADDRESS...');
} catch (InvalidArgumentException $e) {
    echo "Invalid: " . $e->getMessage() . PHP_EOL;
}

// Use validation to avoid exceptions
$input = 'user-provided-address';
if (StrKey::isValidAccountId($input)) {
    $raw = StrKey::decodeAccountId($input);
} elseif (StrKey::isValidMuxedAccountId($input)) {
    $muxed = MuxedAccount::fromAccountId($input);
    $raw = StrKey::decodeAccountId($muxed->getEd25519AccountId());
} else {
    echo "Invalid address format" . PHP_EOL;
}

// MuxedAccount validates on construction
try {
    // Must start with G (Ed25519 account ID)
    $muxed = new MuxedAccount('INVALID', 123);
} catch (InvalidArgumentException $e) {
    echo "Invalid: " . $e->getMessage() . PHP_EOL;
}
```

### Common validation errors

The SEP-23 spec defines several invalid strkey cases that implementations must reject:

- **Invalid length**: Strkey length must match the expected format
- **Invalid checksum**: The CRC-16 checksum at the end must be valid  
- **Wrong version byte**: The first character must match the expected type
- **Invalid base32 characters**: Only A-Z and 2-7 are valid
- **Invalid padding**: Strkeys must not contain `=` padding characters
- **Wrong payload length**: The decoded payload must be the size the type requires
- **Non-zero signed payload padding**: The bytes filling a P-strkey payload up to a 4-byte boundary must all be zero
- **Unknown claimable balance type**: The first payload byte of a B-strkey must be 0

## Related specifications

- [SEP-05 Key Derivation](sep-05.md) — Deriving keypairs from mnemonic phrases
- [SEP-10 Web Authentication](sep-10.md) — Uses account IDs for authentication challenges
- [SEP-45 Web Authentication for Contract Accounts](sep-45.md) — Authentication for Soroban contract accounts (C... addresses)

---

[Back to SEP Overview](README.md)
