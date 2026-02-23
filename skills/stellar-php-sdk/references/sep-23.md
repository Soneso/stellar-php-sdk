# SEP-23: Strkey Encoding

**Purpose:** Validate and convert Stellar addresses between human-readable strings and raw binary data. Covers all strkey types: account IDs (G...), secret seeds (S...), muxed accounts (M...), pre-auth TX hashes (T...), SHA-256 hashes (X...), signed payloads (P...), contract IDs (C...), liquidity pool IDs (L...), and claimable balance IDs (B...).

**Prerequisites:** None

**SDK Namespace:** `Soneso\StellarSDK\Crypto`, `Soneso\StellarSDK`

## Contents

1. [StrKey class — method reference](#strkey-class--method-reference)
2. [Account IDs and secret seeds](#account-ids-and-secret-seeds)
3. [Key derivation utilities](#key-derivation-utilities)
4. [Muxed accounts (M...)](#muxed-accounts-m)
5. [Pre-auth TX and SHA-256 hashes](#pre-auth-tx-and-sha-256-hashes)
6. [Contract IDs (C...)](#contract-ids-c)
7. [Signed payloads (P...)](#signed-payloads-p)
8. [Liquidity pool IDs (L...)](#liquidity-pool-ids-l)
9. [Claimable balance IDs (B...)](#claimable-balance-ids-b)
10. [MuxedAccount class](#muxedaccount-class)
11. [Validation and error handling](#validation-and-error-handling)
12. [Version bytes reference table](#version-bytes-reference-table)
13. [Common pitfalls](#common-pitfalls)

---

## StrKey class — method reference

`Soneso\StellarSDK\Crypto\StrKey` — all methods are `public static`.

### Account ID (G...)

```php
StrKey::isValidAccountId(string $accountId): bool
StrKey::encodeAccountId(string $data): string       // raw 32 bytes → G...
StrKey::decodeAccountId(string $accountId): string  // G... → raw 32 bytes
```

### Secret seed (S...)

```php
StrKey::isValidSeed(string $seed): bool
StrKey::encodeSeed(string $data): string   // raw 32 bytes → S...
StrKey::decodeSeed(string $seed): string   // S... → raw 32 bytes
```

### Muxed account ID (M...)

```php
StrKey::isValidMuxedAccountId(string $muxedAccountId): bool
StrKey::encodeMuxedAccountId(string $data): string          // raw 40 bytes → M...
StrKey::decodeMuxedAccountId(string $muxedAccountId): string // M... → raw 40 bytes
```

### Pre-auth TX (T...)

```php
StrKey::isValidPreAuthTx(string $preAuth): bool
StrKey::encodePreAuthTx(string $data): string    // raw 32 bytes → T...
StrKey::decodePreAuthTx(string $preAuth): string // T... → raw 32 bytes
```

### SHA-256 hash (X...)

```php
StrKey::isValidSha256Hash(string $hash): bool
StrKey::encodeSha256Hash(string $data): string  // raw 32 bytes → X...
StrKey::decodeSha256Hash(string $hash): string  // X... → raw 32 bytes
```

### Signed payload (P...)

```php
// Higher-level: accepts SignedPayloadSigner or XdrSignedPayload
StrKey::encodeSignedPayload(SignedPayloadSigner $signer): string
StrKey::decodeSignedPayload(string $signedPayload): SignedPayloadSigner

// Lower-level: works directly with XdrSignedPayload
StrKey::encodeXdrSignedPayload(XdrSignedPayload $signedPayload): string
StrKey::decodeXdrSignedPayload(string $signedPayload): XdrSignedPayload
```

### Contract ID (C...)

```php
StrKey::isValidContractId(string $contractId): bool
StrKey::encodeContractId(string $data): string         // raw 32 bytes → C...
StrKey::encodeContractIdHex(string $contractId): string // hex string → C...
StrKey::decodeContractId(string $contractId): string   // C... → raw 32 bytes
StrKey::decodeContractIdHex(string $contractId): string // C... → hex string
```

### Liquidity pool ID (L...)

```php
StrKey::isValidLiquidityPoolId(string $liquidityPoolId): bool
StrKey::encodeLiquidityPoolId(string $data): string              // raw 32 bytes → L...
StrKey::encodeLiquidityPoolIdHex(string $liquidityPoolId): string // hex string → L...
StrKey::decodeLiquidityPoolId(string $liquidityPoolId): string   // L... → raw 32 bytes
StrKey::decodeLiquidityPoolIdHex(string $liquidityPoolId): string // L... → hex string
```

### Claimable balance ID (B...)

```php
StrKey::isValidClaimableBalanceId(string $claimableBalanceId): bool
StrKey::encodeClaimableBalanceId(string $data): string              // raw bytes → B...
StrKey::encodeClaimableBalanceIdHex(string $claimableBalanceId): string // hex string → B...
StrKey::decodeClaimableBalanceId(string $claimableBalanceId): string   // B... → raw bytes
StrKey::decodeClaimableBalanceIdHex(string $claimableBalanceId): string // B... → hex string
```

### Key derivation

```php
StrKey::accountIdFromSeed(string $seed): string           // S... → G...
StrKey::accountIdFromPrivateKey(string $privateKey): string // raw 32-byte key → G...
StrKey::publicKeyFromPrivateKey(string $privateKey): string // raw 32-byte key → raw 32-byte key
```

---

## Account IDs and secret seeds

Account IDs (`G...`) are Ed25519 public keys identifying accounts on the network. Secret seeds (`S...`) are the corresponding private keys used for signing. Both encode 32 raw bytes.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;

// Generate a keypair and get its string representations
$keyPair = KeyPair::random();
$accountId  = $keyPair->getAccountId();   // G...
$secretSeed = $keyPair->getSecretSeed();  // S...

// Validate
var_dump(StrKey::isValidAccountId($accountId));   // bool(true)
var_dump(StrKey::isValidSeed($secretSeed));       // bool(true)
var_dump(StrKey::isValidAccountId($secretSeed));  // bool(false) — wrong version byte
var_dump(StrKey::isValidSeed($accountId));        // bool(false)

// Decode to raw bytes
$rawPublicKey  = StrKey::decodeAccountId($accountId);  // 32 bytes
$rawPrivateKey = StrKey::decodeSeed($secretSeed);      // 32 bytes

// Encode raw bytes back to string
$encodedId   = StrKey::encodeAccountId($rawPublicKey);   // same as $accountId
$encodedSeed = StrKey::encodeSeed($rawPrivateKey);       // same as $secretSeed

// Verify round-trip
var_dump($encodedId === $accountId);     // bool(true)
var_dump($encodedSeed === $secretSeed);  // bool(true)
```

---

## Key derivation utilities

Derive the public key or account ID directly from a private key or secret seed, without constructing a full KeyPair.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;

$seed = 'SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE';

// Derive account ID (G...) from S... seed — equivalent to KeyPair::fromSeed($seed)->getAccountId()
$accountId = StrKey::accountIdFromSeed($seed);
echo $accountId . PHP_EOL;
// GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D

// Derive account ID from raw 32-byte private key
$keyPair    = KeyPair::fromSeed($seed);
$privateKey = $keyPair->getPrivateKey(); // raw 32 bytes
$accountId2 = StrKey::accountIdFromPrivateKey($privateKey);
var_dump($accountId === $accountId2); // bool(true)

// Derive raw public key from raw private key
$publicKey = StrKey::publicKeyFromPrivateKey($privateKey); // raw 32 bytes
var_dump($publicKey === $keyPair->getPublicKey()); // bool(true)
```

---

## Muxed accounts (M...)

Muxed accounts (CAP-27) multiplex multiple virtual sub-accounts onto a single Stellar account. An M-address combines a G-address (the underlying on-chain account) with a 64-bit unsigned integer ID.

### High-level: MuxedAccount class

The `MuxedAccount` class is the preferred way to work with muxed accounts. See [MuxedAccount class](#muxedaccount-class) below for the full API.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\MuxedAccount;

$gAddress = 'GAQAA5L65LSYBER7AEES5KJEK32VGMFQ7NQQCC3OHSNNLXK7774VSSRL';

// Create muxed account: G-address + 64-bit ID
$muxed = new MuxedAccount($gAddress, 1234567890);
$mAddress = $muxed->getAccountId(); // M...

echo $muxed->getEd25519AccountId() . PHP_EOL; // G... (underlying on-chain account)
echo $muxed->getId() . PHP_EOL;               // 1234567890 (sub-account ID)
echo $mAddress . PHP_EOL;                     // M... (encoded muxed address)

// Parse existing M-address
$parsed = MuxedAccount::fromAccountId('MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUQ');
echo $parsed->getEd25519AccountId() . PHP_EOL; // G...
echo $parsed->getId() . PHP_EOL;               // 1234 (or whatever the embedded ID is)

// fromAccountId() accepts both G... and M...
$plain = MuxedAccount::fromAccountId($gAddress); // wraps as non-muxed, getId() returns null
```

### Low-level: StrKey methods for muxed accounts

The raw binary format for a muxed account is 40 bytes: 8-byte big-endian ID followed by 32-byte Ed25519 public key (in inverted byte order as XDR dictates). Use `MuxedAccount` instead of these unless you need direct binary manipulation.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\StrKey;

$mAddress = 'MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVAAAAAAAAAAAAAJLK';

// Validate
var_dump(StrKey::isValidMuxedAccountId($mAddress)); // bool(true)

// Decode to raw 40 bytes
$raw = StrKey::decodeMuxedAccountId($mAddress); // 40 bytes

// Encode raw 40 bytes back to M...
$reencoded = StrKey::encodeMuxedAccountId($raw);
var_dump($reencoded === $mAddress); // bool(true)
```

### Using muxed accounts in transactions

The underlying G-address controls signing. The muxed ID is for tracking only — the network settles against the G-address.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;

$senderKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$senderG       = $senderKeyPair->getAccountId(); // G...

// Muxed source (sender, user ID 100) and destination (user ID 200)
$muxedSource      = new MuxedAccount($senderG, 100);
$destinationG     = 'GAQAA5L65LSYBER7AEES5KJEK32VGMFQ7NQQCC3OHSNNLXK7774VSSRL';
$muxedDestination = new MuxedAccount($destinationG, 200);

$sdk     = StellarSDK::getTestNetInstance();
$network = Network::testnet();

// Must load the underlying G-address account (not M-address) for sequence number
$sourceAccount = $sdk->requestAccount($senderG);

$paymentOp = (new PaymentOperationBuilder(
    $muxedDestination->getAccountId(), // pass M-address string directly
    Asset::native(),
    '10.0'
))->build();

$transaction = (new TransactionBuilder($sourceAccount))
    ->addOperation($paymentOp)
    ->build();

$transaction->sign($senderKeyPair, $network); // sign with G-address keypair
$response = $sdk->submitTransaction($transaction);
```

---

## Pre-auth TX and SHA-256 hashes

Pre-authorized transaction hashes (`T...`) authorize a specific transaction in advance as a signer. SHA-256 hash signers (`X...`) are for hash-locked transactions — the hash preimage must be revealed to authorize.

Both types encode 32 raw bytes.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\StrKey;

// Pre-auth TX (T...)
$txHash   = random_bytes(32);   // 32-byte pre-auth transaction hash
$preAuthTx = StrKey::encodePreAuthTx($txHash);
echo $preAuthTx . PHP_EOL; // T...

var_dump(StrKey::isValidPreAuthTx($preAuthTx)); // bool(true)
var_dump(StrKey::isValidPreAuthTx('GBPXXOA5N4JYPESHAADMQKBPWZWQDQ64ZV6ZL2S3LAGW4SY7NTCMWIVL')); // bool(false)

$decoded = StrKey::decodePreAuthTx($preAuthTx);
var_dump($decoded === $txHash); // bool(true)

// SHA-256 hash signer (X...)
$hashPreimage = 'secret preimage';
$hash         = hash('sha256', $hashPreimage, true); // raw 32 bytes
$hashSigner   = StrKey::encodeSha256Hash($hash);
echo $hashSigner . PHP_EOL; // X...

var_dump(StrKey::isValidSha256Hash($hashSigner)); // bool(true)
$decoded = StrKey::decodeSha256Hash($hashSigner);
var_dump($decoded === $hash); // bool(true)
```

---

## Contract IDs (C...)

Soroban smart contracts are identified by 32-byte hashes encoded as C-addresses. Both raw binary and hex string formats are supported.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\StrKey;

$contractId = 'CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE';
$asHex      = '363eaa3867841fbad0f4ed88c779e4fe66e56a2470dc98c0ec9c073d05c7b103';

// Validate
var_dump(StrKey::isValidContractId($contractId)); // bool(true)
var_dump(StrKey::isValidContractId('GA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE')); // bool(false) — G prefix is wrong

// Decode
$rawBytes = StrKey::decodeContractId($contractId);    // raw 32 bytes
$hexStr   = StrKey::decodeContractIdHex($contractId); // hex string
var_dump($hexStr === $asHex); // bool(true)

// Encode
$fromRaw = StrKey::encodeContractId(hex2bin($asHex)); // hex2bin → raw → C...
$fromHex = StrKey::encodeContractIdHex($asHex);        // hex → C...

var_dump($fromRaw === $contractId); // bool(true)
var_dump($fromHex === $contractId); // bool(true)
```

---

## Signed payloads (P...)

Signed payloads (CAP-40) combine an Ed25519 public key with 4–64 bytes of arbitrary payload data. Used for delegated signing where the signature covers both the transaction and additional application context.

### Using SignedPayloadSigner (high-level)

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\SignedPayloadSigner;

$keyPair = KeyPair::random();
$payload = random_bytes(32); // 4–64 bytes

// Create from G-address string
$signer  = SignedPayloadSigner::fromAccountId($keyPair->getAccountId(), $payload);
$encoded = StrKey::encodeSignedPayload($signer); // P...

// Decode back to SignedPayloadSigner
$decoded = StrKey::decodeSignedPayload($encoded);
echo $decoded->getSignerAccountId()->getAccountId() . PHP_EOL; // G... (signer's account)
var_dump($decoded->getPayload() === $payload);                  // bool(true)
```

### Using XdrSignedPayload (low-level)

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Xdr\XdrSignedPayload;

$keyPair = KeyPair::random();
$payload = hex2bin('0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20');

// XdrSignedPayload takes raw 32-byte public key (not G-address)
$xdrPayload = new XdrSignedPayload($keyPair->getPublicKey(), $payload);

$encoded = StrKey::encodeXdrSignedPayload($xdrPayload); // P...
$decoded = StrKey::decodeXdrSignedPayload($encoded);

var_dump($decoded->getEd25519() === $keyPair->getPublicKey()); // bool(true)
var_dump($decoded->getPayload() === $payload);                  // bool(true)

// Round-trip
$reencoded = StrKey::encodeXdrSignedPayload($decoded);
var_dump($reencoded === $encoded); // bool(true)
```

### SignedPayloadSigner factory methods

```php
// From G-address string (most common)
$signer = SignedPayloadSigner::fromAccountId(string $accountId, string $payload): SignedPayloadSigner

// From raw 32-byte Ed25519 public key
$signer = SignedPayloadSigner::fromPublicKey(string $publicKey, string $payload): SignedPayloadSigner

// Accessors
$signer->getSignerAccountId(): XdrAccountID  // call ->getAccountId() on this to get G...
$signer->getPayload(): string                 // raw bytes
```

---

## Liquidity pool IDs (L...)

Liquidity pool IDs encode 32 raw bytes. Both raw binary and hex string formats are supported.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\StrKey;

$liquidityPoolId = 'LA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUPJN';
$asHex           = '3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a';

// Validate
var_dump(StrKey::isValidLiquidityPoolId($liquidityPoolId)); // bool(true)
var_dump(StrKey::isValidLiquidityPoolId('LB7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUPJN')); // bool(false)

// Decode
$rawBytes = StrKey::decodeLiquidityPoolId($liquidityPoolId);    // raw 32 bytes
$hexStr   = StrKey::decodeLiquidityPoolIdHex($liquidityPoolId); // hex string
var_dump($hexStr === $asHex); // bool(true)

// Encode
$fromRaw = StrKey::encodeLiquidityPoolId(hex2bin($asHex)); // raw → L...
$fromHex = StrKey::encodeLiquidityPoolIdHex($asHex);        // hex → L...

var_dump($fromRaw === $liquidityPoolId); // bool(true)
var_dump($fromHex === $liquidityPoolId); // bool(true)
```

---

## Claimable balance IDs (B...)

Claimable balance IDs encode 33 raw bytes: a 1-byte type discriminant (0x00 for CLAIMABLE_BALANCE_ID_TYPE_V0) followed by a 32-byte hash. The SDK handles the discriminant automatically in `encodeClaimableBalanceId` / `encodeClaimableBalanceIdHex`.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\StrKey;

$claimableBalanceId = 'BAAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TU';

// Validate
var_dump(StrKey::isValidClaimableBalanceId($claimableBalanceId)); // bool(true)
var_dump(StrKey::isValidClaimableBalanceId('BBAD6DBUX6J22DMZOHIEZTEQ64CVCHEDRKWZONFEUL5Q26QD7R76RGR4TU')); // bool(false)

// Decode — returns full 33 bytes (with discriminant)
$rawBytes = StrKey::decodeClaimableBalanceId($claimableBalanceId);
$hexStr   = StrKey::decodeClaimableBalanceIdHex($claimableBalanceId);
// $hexStr == '003f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a'
// First byte '00' is the discriminant

// Encode from full 33-byte hex (with discriminant prefix '00')
$fullHex = '003f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a';
$encoded = StrKey::encodeClaimableBalanceIdHex($fullHex);
var_dump($encoded === $claimableBalanceId); // bool(true)

// Encode from 32-byte hex (without discriminant) — SDK auto-prepends '00'
$hashOnly = '3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a';
$encoded  = StrKey::encodeClaimableBalanceIdHex($hashOnly);
var_dump($encoded === $claimableBalanceId); // bool(true)
```

---

## MuxedAccount class

`Soneso\StellarSDK\MuxedAccount` — the high-level API for muxed accounts.

### Constructor

```php
// $ed25519AccountId must start with 'G' or throws InvalidArgumentException
new MuxedAccount(string $ed25519AccountId, ?int $id = null)
```

- With `$id`: represents a muxed account; `getAccountId()` returns M-address
- Without `$id` (null): wraps a plain G-address; `getAccountId()` returns the G-address

### Methods

```php
$muxed->getAccountId(): string          // M... (if muxed) or G... (if not muxed)
$muxed->getEd25519AccountId(): string   // always the underlying G... address
$muxed->getId(): ?int                   // 64-bit ID, or null if not muxed
$muxed->getXdr(): XdrMuxedAccount       // XDR representation
$muxed->toXdr(): XdrMuxedAccount        // convert to XDR
```

### Static factory methods

```php
MuxedAccount::fromAccountId(string $accountId): MuxedAccount
// Accepts G... or M...; throws InvalidArgumentException for other prefixes

MuxedAccount::fromMed25519AccountId(string $med25519AccountId): MuxedAccount
// Accepts M... only

MuxedAccount::fromXdr(XdrMuxedAccount $muxedAccount): MuxedAccount
// Construct from XDR object
```

### Full example

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\MuxedAccount;

$keyPair    = KeyPair::random();
$gAddress   = $keyPair->getAccountId();

// --- Create muxed account ---
$muxed    = new MuxedAccount($gAddress, 42);
$mAddress = $muxed->getAccountId();          // M...
echo 'M-address: ' . $mAddress . PHP_EOL;
echo 'G-address: ' . $muxed->getEd25519AccountId() . PHP_EOL;
echo 'ID: '        . $muxed->getId() . PHP_EOL;  // 42

// --- Parse M-address ---
$parsed = MuxedAccount::fromAccountId($mAddress);
echo $parsed->getEd25519AccountId() . PHP_EOL; // same G-address as above
echo $parsed->getId() . PHP_EOL;               // 42

// --- Without muxed ID ---
$plain = new MuxedAccount($gAddress);              // no ID
echo $plain->getAccountId() . PHP_EOL;            // returns G... (not M...)
var_dump($plain->getId());                         // NULL

// --- fromAccountId accepts both G and M ---
$fromG = MuxedAccount::fromAccountId($gAddress);  // wraps G-address, getId() = null
$fromM = MuxedAccount::fromAccountId($mAddress);  // parses M-address, getId() = 42
```

---

## Validation and error handling

All `isValid*` methods return `bool` and never throw. All `decode*` and `encode*` methods throw `InvalidArgumentException` on invalid input.

```php
<?php declare(strict_types=1);

use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\MuxedAccount;

// --- Safe validation pattern ---
$input = $_POST['address'] ?? '';

if (StrKey::isValidAccountId($input)) {
    $raw = StrKey::decodeAccountId($input);
    echo 'G-address: ' . $input . PHP_EOL;
} elseif (StrKey::isValidMuxedAccountId($input)) {
    $muxed = MuxedAccount::fromAccountId($input);
    echo 'M-address: G=' . $muxed->getEd25519AccountId() . ' ID=' . $muxed->getId() . PHP_EOL;
} elseif (StrKey::isValidContractId($input)) {
    echo 'Contract: ' . $input . PHP_EOL;
} else {
    echo 'Unknown or invalid address format' . PHP_EOL;
}

// --- Exception path ---
try {
    // Wrong version byte (G prefix passed to decodeSeed)
    StrKey::decodeSeed('GBPXXOA5N4JYPESHAADMQKBPWZWQDQ64ZV6ZL2S3LAGW4SY7NTCMWIVL');
} catch (InvalidArgumentException $e) {
    echo 'Wrong version byte: ' . $e->getMessage() . PHP_EOL;
}

try {
    // Invalid base32 characters (contains '0')
    StrKey::decodeAccountId('GBPXX0A5N4JYPESHAADMQKBPWZWQDQ64ZV6ZL2S3LAGW4SY7NTCMWIVL');
} catch (InvalidArgumentException $e) {
    echo 'Invalid base32: ' . $e->getMessage() . PHP_EOL;
}

try {
    // Bad checksum (last character changed)
    StrKey::decodeAccountId('GBPXXOA5N4JYPESHAADMQKBPWZWQDQ64ZV6ZL2S3LAGW4SY7NTCMWIVT');
} catch (InvalidArgumentException $e) {
    echo 'Bad checksum: ' . $e->getMessage() . PHP_EOL;
}

try {
    // MuxedAccount constructor requires G prefix
    $muxed = new MuxedAccount('INVALID', 123);
} catch (InvalidArgumentException $e) {
    echo 'Bad muxed account: ' . $e->getMessage() . PHP_EOL;
}
```

### What isValid* rejects

- Wrong version byte (e.g., passing a G-address to `isValidSeed`)
- Invalid base32 characters (only A–Z and 2–7 are valid; `0`, `1`, `8`, `9` are invalid)
- Padding bytes (`=`) are not allowed in strkeys
- Wrong string length for the key type
- Invalid CRC-16 checksum
- Trailing bits that are non-zero in the last encoded group

---

## Version bytes reference table

Each strkey type has a version byte that determines its prefix character:

| Prefix | Type | Decoded payload size |
|--------|------|----------------------|
| G | Account ID (Ed25519 public key) | 32 bytes |
| S | Secret Seed (Ed25519 private key) | 32 bytes |
| M | Muxed Account (ID + public key) | 40 bytes (8-byte ID + 32-byte key) |
| T | Pre-Auth TX hash | 32 bytes |
| X | SHA-256 Hash signer | 32 bytes |
| P | Signed Payload | 40–100 bytes (32-byte key + 4-byte length + 4–64 byte payload) |
| C | Contract ID | 32 bytes |
| L | Liquidity Pool ID | 32 bytes |
| B | Claimable Balance ID | 33 bytes (1-byte discriminant + 32-byte hash) |

---

## Common pitfalls

**Passing M-address to functions expecting G-address:**

```php
// WRONG: MuxedAccount constructor requires G... not M...
$muxed = new MuxedAccount('MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUQ', 42);
// throws InvalidArgumentException: ed25519AccountId must start with G

// CORRECT: extract the G-address first, or use fromAccountId()
$muxed = MuxedAccount::fromAccountId('MA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUAAAAAAAAAAAACJUQ');
// OR: supply only the underlying G-address
$muxed = new MuxedAccount('GAQAA5L65LSYBER7AEES5KJEK32VGMFQ7NQQCC3OHSNNLXK7774VSSRL', 42);
```

**Decoding with the wrong type method:**

```php
// WRONG: using decodeAccountId on a seed, or decodeSeed on an account ID
StrKey::decodeSeed('GBPXXOA5N4JYPESHAADMQKBPWZWQDQ64ZV6ZL2S3LAGW4SY7NTCMWIVL'); // throws
StrKey::decodeAccountId('SBGWKM3CD4IL47QN6X54N6Y33T3JDNVI6AIJ6CD5IM47HG3IG4O36XCU'); // throws

// CORRECT: use the matching decode method for each type
$raw = StrKey::decodeAccountId('GCEZWKCA5VLDNRLN3RPRJMRZOX3Z6G5CHCGSNFHEYVXM3XOJMDS674JZ');
$raw = StrKey::decodeSeed('SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34RFLWOEIA5MPI7YPQAAXX');
```

**Requesting account with M-address instead of G-address:**

```php
// WRONG: Horizon requestAccount requires a G-address, not M...
$sdk->requestAccount($muxed->getAccountId()); // passes M-address, will fail

// CORRECT: use the underlying G-address for all Horizon queries
$sdk->requestAccount($muxed->getEd25519AccountId());
```

**Claimable balance hex encoding with vs. without discriminant:**

```php
use Soneso\StellarSDK\Crypto\StrKey;

// Both work — the SDK auto-adds the '00' discriminant when hex is 32 bytes
$fullHex  = '003f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a'; // 33 bytes (66 hex chars)
$hashOnly = '3f0c34bf93ad0d9971d04ccc90f705511c838aad9734a4a2fb0d7a03fc7fe89a';    // 32 bytes (64 hex chars)

$id1 = StrKey::encodeClaimableBalanceIdHex($fullHex);  // works
$id2 = StrKey::encodeClaimableBalanceIdHex($hashOnly); // also works — SDK adds '00'
var_dump($id1 === $id2); // bool(true)

// When decoding, you always get the full 33-byte hex (with '00' prefix)
$decoded = StrKey::decodeClaimableBalanceIdHex($id1);
// '003f0c34bf93...' — includes discriminant
```

**XdrSignedPayload takes raw public key bytes, not G-address:**

```php
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Xdr\XdrSignedPayload;

$keyPair = KeyPair::random();
$payload = random_bytes(32);

// WRONG: XdrSignedPayload constructor expects raw bytes, not a G... string
$xdr = new XdrSignedPayload($keyPair->getAccountId(), $payload); // wrong type

// CORRECT: pass raw 32-byte public key
$xdr = new XdrSignedPayload($keyPair->getPublicKey(), $payload);
```

**SignedPayload payload size limits:**

```php
// WRONG: payload shorter than 4 bytes or longer than 64 bytes
$signer = SignedPayloadSigner::fromAccountId($accountId, random_bytes(3));  // too short
$signer = SignedPayloadSigner::fromAccountId($accountId, random_bytes(65)); // throws InvalidArgumentException

// CORRECT: payload must be 4–64 bytes
$signer = SignedPayloadSigner::fromAccountId($accountId, random_bytes(32));
```

<!-- DISCREPANCIES AND NOTES:

1. The SDK docs sep-23.md example for signed payload uses `$signer = SignedPayloadSigner::fromAccountId($keyPair->getAccountId(), $payload)` and states payload is "4-64 bytes". The SDK source (SignedPayloadSigner.php) only enforces a maximum of SIGNED_PAYLOAD_MAX_LENGTH_BYTES (64) in the constructor, with no minimum check. The test file uses 4 bytes as the minimum (testSignedPayloadWithDifferentLengths), consistent with the SEP spec. The reference file documents the 4-64 range as the valid range per spec and test evidence.

2. The claimable balance ID auto-discriminant behavior: `encodeClaimableBalanceId()` checks if input length equals ED25519_PUBLIC_KEY_LENGTH_BYTES (32) and if so, prepends a zero byte. This means passing raw 32 bytes or the full 33 bytes (with discriminant already present) both work. This is a subtle auto-prepend behavior documented in the pitfalls section.

3. `MuxedAccount::toXdr()` is called twice per `getXdr()` call (see source: `getXdr()` assigns to $this->xdr but returns `$this->toXdr()` again — likely a minor source bug, but doesn't affect API usage). Not documented as it's an internal implementation detail.

4. `SignedPayloadSigner::getSignerAccountId()` returns `XdrAccountID`, not a string. Callers must chain `->getAccountId()` to get the G... string. This is documented in the examples.

5. The docs file states signed payload length is "4-64 bytes" but the PHP test file also uses a 29-byte payload successfully (`testSignedPayloads` decodes a known vector with 29-byte payload). The minimum enforced in StrKey::isValid is actually: decoded bytes must be >= 32+4+4=40. This translates to a minimum payload of 4 bytes in the XDR encoding (4-byte length prefix + 4-byte payload). Documented as 4–64 bytes per spec.
-->
