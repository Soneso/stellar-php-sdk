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

$accountId = 'GCEZWKCA5VLDNRLN3RPRJMRZOX3Z6G5CHCGSNFHEYVXM3XOJMDS674JZ';
$secretSeed = 'SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34RFLWOEIA5MPI7YPQAAXX';

// Validate
StrKey::isValidAccountId($accountId);  // true
StrKey::isValidSeed($secretSeed);      // true

// Decode to raw 32-byte keys
$rawPublicKey = StrKey::decodeAccountId($accountId);
$rawPrivateKey = StrKey::decodeSeed($secretSeed);

// Encode raw bytes back to string
$encoded = StrKey::encodeAccountId($rawPublicKey);
$encodedSeed = StrKey::encodeSeed($rawPrivateKey);

// Derive account ID from seed
$accountId = StrKey::accountIdFromSeed($secretSeed);
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

$accountId = 'GAQAA5L65LSYBER7AEES5KJEK32VGMFQ7NQQCC3OHSNNLXK7774VSSRL';
$userId = 1234567890;

// Create a muxed account from G-address and ID
$muxedAccount = new MuxedAccount($accountId, $userId);
$muxedAccountId = $muxedAccount->getAccountId(); // M...

// Parse an existing M-address
$parsedMuxed = MuxedAccount::fromAccountId('MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUQ');
```

### Extracting muxed account components

When you receive an M-address, you can extract both the underlying G-address and the numeric ID.

```php
<?php

use Soneso\StellarSDK\MuxedAccount;

$muxedAccountId = 'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUQ';

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
$senderKeyPair = KeyPair::fromSeed('SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34RFLWOEIA5MPI7YPQAAXX');
$senderAccountId = $senderKeyPair->getAccountId();

// Create muxed source account (sender with user ID 100)
$muxedSource = new MuxedAccount($senderAccountId, 100);

// Create muxed destination (recipient with user ID 200)
$destinationAccountId = 'GAQAA5L65LSYBER7AEES5KJEK32VGMFQ7NQQCC3OHSNNLXK7774VSSRL';
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

$muxedAccountId = 'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUQ';

// Validate M-address format
StrKey::isValidMuxedAccountId($muxedAccountId); // true

// Decode to raw binary (40 bytes: 8-byte ID + 32-byte public key)
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

$contractId = 'CDCGEWX4TENKBQFSG5ISXR5QNKECBCHSOHNLNPXZXOTXRELAJRAMVHTR';

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
$payload = random_bytes(32); // 4-64 bytes

$signer = SignedPayloadSigner::fromAccountId($keyPair->getAccountId(), $payload);
$signedPayload = StrKey::encodeSignedPayload($signer); // P...

$decoded = StrKey::decodeSignedPayload($signedPayload);
echo $decoded->getSignerAccountId()->getAccountId() . PHP_EOL;
```

## Liquidity pool and claimable balance IDs

Pool IDs (L...) identify AMM liquidity pools. Claimable balance IDs (B...) reference claimable balance entries. Both support hex encoding for interoperability with APIs.

```php
<?php

use Soneso\StellarSDK\Crypto\StrKey;

// Liquidity pool ID (L...)
$poolHex = 'dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7';
$poolId = StrKey::encodeLiquidityPoolIdHex($poolHex);
StrKey::isValidLiquidityPoolId($poolId); // true
$decoded = StrKey::decodeLiquidityPoolIdHex($poolId);

// Claimable balance ID (B...)
$balanceHex = '00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfd';
$balanceId = StrKey::encodeClaimableBalanceIdHex($balanceHex);
StrKey::isValidClaimableBalanceId($balanceId); // true
$decoded = StrKey::decodeClaimableBalanceIdHex($balanceId);
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

## Related specifications

- [SEP-05 Key Derivation](sep-05.md) — Deriving keypairs from mnemonic phrases
- [SEP-10 Web Authentication](sep-10.md) — Uses account IDs for authentication challenges
- [SEP-45 Web Authentication for Contract Accounts](sep-45.md) — Authentication for Soroban contract accounts (C... addresses)

---

[Back to SEP Overview](README.md)
