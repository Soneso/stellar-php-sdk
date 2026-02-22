# SEP-53: Sign and Verify Messages

**Purpose:** Sign arbitrary messages with a Stellar private key and verify those signatures using only the public key.
**Prerequisites:** None
**SDK Namespace:** `Soneso\StellarSDK\Crypto`
**Note:** SEP-53 is currently Draft status (v0.0.1).

## Table of Contents

- [How It Works](#how-it-works)
- [Quick Example](#quick-example)
- [Method Signatures](#method-signatures)
- [Signing Messages](#signing-messages)
- [Verifying Messages](#verifying-messages)
- [Signature Encoding (base64 and hex)](#signature-encoding-base64-and-hex)
- [Signing and Verifying with Separate Keypairs](#signing-and-verifying-with-separate-keypairs)
- [Authentication Flow Example](#authentication-flow-example)
- [Test Vectors](#test-vectors)
- [Error Handling](#error-handling)
- [Common Pitfalls](#common-pitfalls)
- [Security Notes](#security-notes)

## How It Works

SEP-53 uses standard Ed25519 signing with a domain-separation prefix:

```
hash     = SHA-256("Stellar Signed Message:\n" + message)
signature = Ed25519Sign(privateKey, hash)
```

The prefix `"Stellar Signed Message:\n"` ensures a signed message can never be confused with a Stellar transaction signature. The hash and signature algorithms are the same as the rest of the Stellar network — only the prefix distinguishes message signing from transaction signing.

`calculateMessageHash()` is a `private static` method on `KeyPair`. It is not accessible from outside the class. Both `signMessage()` and `verifyMessage()` call it internally — you never need to call it directly.

## Quick Example

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$keyPair = KeyPair::random();

// Sign a message — returns 64 raw bytes or null on failure
$signature = $keyPair->signMessage("I agree to the terms of service");

// Verify using the same keypair (or a public-key-only keypair)
$isValid = $keyPair->verifyMessage("I agree to the terms of service", $signature);
echo $isValid ? "Valid\n" : "Invalid\n"; // Valid
```

## Method Signatures

Both methods are on `KeyPair` in `Soneso\StellarSDK\Crypto\KeyPair`:

```php
// Sign a message according to SEP-53.
// Returns 64 raw signature bytes, or null if signing fails.
// Throws \TypeError if the keypair has no private key (strict_types=1).
public function signMessage(string $message): ?string

// Verify a SEP-53 message signature.
// $signature must be raw bytes (not base64 or hex string).
// Returns true if valid, false otherwise.
public function verifyMessage(string $message, string $signature): bool
```

`signMessage()` accepts any string — UTF-8 text, ASCII, or arbitrary binary data. The `$message` parameter is not length-limited by the SDK.

## Signing Messages

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$keyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));

$message = "User consent granted at 2025-01-15T12:00:00Z";

// signMessage() returns raw bytes (binary string, 64 bytes)
$signature = $keyPair->signMessage($message);

if ($signature === null) {
    // null only on internal crypto failure — extremely rare with a valid keypair
    throw new \RuntimeException("Failed to sign message");
}

// Encode for storage or transmission
$base64Signature = base64_encode($signature);
echo "Signature (base64): " . $base64Signature . PHP_EOL;

$hexSignature = bin2hex($signature);
echo "Signature (hex): " . $hexSignature . PHP_EOL;
```

The raw signature is always exactly 64 bytes. SEP-53 does not mandate an encoding format for transmission — choose base64 or hex and document your choice.

## Verifying Messages

Verification requires only the public key. Create a public-key-only keypair with `KeyPair::fromAccountId()`:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

// Public-key-only keypair — cannot sign, but can verify
$publicKeyPair = KeyPair::fromAccountId("GBXFXNDLV4LSWA4VB7YIL5GBD7BVNR22SGBTDKMO2SBZZHDXSKZYCP7L");

$message = "User consent granted at 2025-01-15T12:00:00Z";

// Decode the signature back to raw bytes before passing to verifyMessage()
$rawSignature = base64_decode($receivedBase64Signature);

$isValid = $publicKeyPair->verifyMessage($message, $rawSignature);

if ($isValid) {
    echo "Signature verified\n";
} else {
    // Message was altered, signature was tampered, or wrong public key
    http_response_code(401);
    echo json_encode(["error" => "Invalid signature"]);
    exit;
}
```

## Signature Encoding (base64 and hex)

`signMessage()` returns raw bytes. Encode before storing or transmitting; decode before calling `verifyMessage()`.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$keyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$message = "Hello, World!";
$rawSignature = $keyPair->signMessage($message);

// --- base64 roundtrip ---
$base64 = base64_encode($rawSignature);
$decoded = base64_decode($base64);
$valid = $keyPair->verifyMessage($message, $decoded); // true

// --- hex roundtrip ---
$hex = bin2hex($rawSignature);
$decoded = hex2bin($hex);
$valid = $keyPair->verifyMessage($message, $decoded); // true
```

**WRONG/CORRECT — pass raw bytes, not the encoded string:**

```php
// WRONG: passing the base64-encoded string directly
$base64Signature = base64_encode($keyPair->signMessage($message));
$keyPair->verifyMessage($message, $base64Signature); // always false

// CORRECT: decode to raw bytes first
$rawSignature = base64_decode($base64Signature);
$keyPair->verifyMessage($message, $rawSignature); // true
```

## Signing and Verifying with Separate Keypairs

The signer uses `KeyPair::fromSeed()` (has private key); the verifier uses `KeyPair::fromAccountId()` (public key only):

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$specSeed    = 'SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW';
$specAddress = 'GBXFXNDLV4LSWA4VB7YIL5GBD7BVNR22SGBTDKMO2SBZZHDXSKZYCP7L';

// Signer has the private key
$signingKeyPair = KeyPair::fromSeed($specSeed);
$message        = "Cross-construction test";
$signature      = $signingKeyPair->signMessage($message);

// Verifier needs only the public key
$verifyingKeyPair = KeyPair::fromAccountId($specAddress);
$isValid          = $verifyingKeyPair->verifyMessage($message, $signature);

echo $isValid ? "Valid\n" : "Invalid\n"; // Valid
```

## Authentication Flow Example

Server generates a challenge; client signs it; server verifies key ownership:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

// === SERVER: generate a time-bound challenge ===
$challenge = "authenticate:" . bin2hex(random_bytes(16)) . ":" . time();

// === CLIENT: sign the challenge ===
$clientKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$signature     = $clientKeyPair->signMessage($challenge);

if ($signature === null) {
    throw new \RuntimeException("Signing failed");
}

$response = [
    'account_id' => $clientKeyPair->getAccountId(),
    'challenge'  => $challenge,
    'signature'  => base64_encode($signature),
];

// === SERVER: verify the response ===
$publicKeyPair = KeyPair::fromAccountId($response['account_id']);
$rawSignature  = base64_decode($response['signature']);

if ($publicKeyPair->verifyMessage($response['challenge'], $rawSignature)) {
    echo "User authenticated as " . $response['account_id'] . PHP_EOL;
} else {
    echo "Authentication failed\n";
}
```

## Test Vectors

Use these official test vectors from the SEP-53 specification to confirm your implementation is working correctly.

**Keypair for all vectors:**

```
Seed:       SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW
Account ID: GBXFXNDLV4LSWA4VB7YIL5GBD7BVNR22SGBTDKMO2SBZZHDXSKZYCP7L
```

### ASCII message

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$keyPair = KeyPair::fromSeed('SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW');

// Confirm the seed maps to the expected account ID
assert($keyPair->getAccountId() === 'GBXFXNDLV4LSWA4VB7YIL5GBD7BVNR22SGBTDKMO2SBZZHDXSKZYCP7L');

$signature = $keyPair->signMessage("Hello, World!");

$expectedBase64 = 'fO5dbYhXUhBMhe6kId/cuVq/AfEnHRHEvsP8vXh03M1uLpi5e46yO2Q8rEBzu3feXQewcQE5GArp88u6ePK6BA==';
$expectedHex    = '7cee5d6d885752104c85eea421dfdcb95abf01f1271d11c4bec3fcbd7874dccd6e2e98b97b8eb23b643cac4073bb77de5d07b0710139180ae9f3cbba78f2ba04';

assert(base64_encode($signature) === $expectedBase64, "base64 mismatch");
assert(bin2hex($signature)       === $expectedHex,    "hex mismatch");

echo "ASCII test vector passed\n";
```

### UTF-8 message (Japanese)

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$keyPair   = KeyPair::fromSeed('SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW');
$signature = $keyPair->signMessage("こんにちは、世界！");

$expectedBase64 = 'CDU265Xs8y3OWbB/56H9jPgUss5G9A0qFuTqH2zs2YDgTm+++dIfmAEceFqB7bhfN3am59lCtDXrCtwH2k1GBA==';
$expectedHex    = '083536eb95ecf32dce59b07fe7a1fd8cf814b2ce46f40d2a16e4ea1f6cecd980e04e6fbef9d21f98011c785a81edb85f3776a6e7d942b435eb0adc07da4d4604';

assert(base64_encode($signature) === $expectedBase64);
assert(bin2hex($signature)       === $expectedHex);

echo "Japanese test vector passed\n";
```

### Binary data message

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$keyPair   = KeyPair::fromSeed('SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW');
$message   = base64_decode('2zZDP1sa1BVBfLP7TeeMk3sUbaxAkUhBhDiNdrksaFo='); // raw binary
$signature = $keyPair->signMessage($message);

$expectedBase64 = 'VA1+7hefNwv2NKScH6n+Sljj15kLAge+M2wE7fzFOf+L0MMbssA1mwfJZRyyrhBORQRle10X1Dxpx+UOI4EbDQ==';
$expectedHex    = '540d7eee179f370bf634a49c1fa9fe4a58e3d7990b0207be336c04edfcc539ff8bd0c31bb2c0359b07c9651cb2ae104e4504657b5d17d43c69c7e50e23811b0d';

assert(base64_encode($signature) === $expectedBase64);
assert(bin2hex($signature)       === $expectedHex);

echo "Binary test vector passed\n";
```

### Verify test vectors with public-key-only keypair

You can also verify test vectors using `KeyPair::fromAccountId()` to confirm the verify path works separately from the sign path:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$publicKey = KeyPair::fromAccountId('GBXFXNDLV4LSWA4VB7YIL5GBD7BVNR22SGBTDKMO2SBZZHDXSKZYCP7L');

// ASCII vector
$sig = base64_decode('fO5dbYhXUhBMhe6kId/cuVq/AfEnHRHEvsP8vXh03M1uLpi5e46yO2Q8rEBzu3feXQewcQE5GArp88u6ePK6BA==');
assert($publicKey->verifyMessage("Hello, World!", $sig));

// Japanese vector
$sig = base64_decode('CDU265Xs8y3OWbB/56H9jPgUss5G9A0qFuTqH2zs2YDgTm+++dIfmAEceFqB7bhfN3am59lCtDXrCtwH2k1GBA==');
assert($publicKey->verifyMessage("こんにちは、世界！", $sig));

// Binary vector
$msg = base64_decode('2zZDP1sa1BVBfLP7TeeMk3sUbaxAkUhBhDiNdrksaFo=');
$sig = base64_decode('VA1+7hefNwv2NKScH6n+Sljj15kLAge+M2wE7fzFOf+L0MMbssA1mwfJZRyyrhBORQRle10X1Dxpx+UOI4EbDQ==');
assert($publicKey->verifyMessage($msg, $sig));

echo "All verification test vectors passed\n";
```

## Error Handling

### Signing without a private key throws TypeError

Calling `signMessage()` on a public-key-only keypair (created with `KeyPair::fromAccountId()`) throws `\TypeError` under `strict_types=1` because `getEd25519SecretKey()` returns `null` and the libsodium call rejects a null argument:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$publicOnly = KeyPair::fromAccountId('GBXFXNDLV4LSWA4VB7YIL5GBD7BVNR22SGBTDKMO2SBZZHDXSKZYCP7L');

try {
    $signature = $publicOnly->signMessage("test");
} catch (\TypeError $e) {
    echo "Cannot sign: keypair has no private key\n";
}
```

### signMessage() can return null

`signMessage()` returns `null` if the internal `sign()` call fails (e.g., a libsodium exception). This is extremely rare with a valid keypair but should be checked in production:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$keyPair   = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$signature = $keyPair->signMessage("Important message");

if ($signature === null) {
    throw new \RuntimeException("Signing failed");
}

$base64 = base64_encode($signature);
```

### verifyMessage() always returns bool — never throws

`verifyMessage()` catches internal exceptions and returns `false`. A return value of `false` means:

- The message was modified after signing
- The signature was corrupted or tampered with in transit
- The wrong public key was used for verification
- The signature was created for a different message

### Empty message is valid

An empty string is a valid message:

```php
$signature = $keyPair->signMessage("");
$valid     = $keyPair->verifyMessage("", $signature); // true
```

## Common Pitfalls

**WRONG/CORRECT — use signMessage(), not sign(), for SEP-53:**

```php
// WRONG: sign() signs raw data with no prefix — not SEP-53 compliant
$signature = $keyPair->sign($message);

// CORRECT: signMessage() applies the "Stellar Signed Message:\n" prefix per SEP-53
$signature = $keyPair->signMessage($message);
```

`sign()` and `signMessage()` produce different signatures for the same input. A signature created with `sign()` will not verify with `verifyMessage()`, and vice versa. Use `signMessage()` / `verifyMessage()` as a matched pair for SEP-53.

**WRONG/CORRECT — decode before verifying:**

```php
// WRONG: passes base64 string as-is — verifyMessage expects raw bytes
$base64Sig = base64_encode($keyPair->signMessage($message));
$keyPair->verifyMessage($message, $base64Sig); // always false

// CORRECT: decode to raw bytes first
$keyPair->verifyMessage($message, base64_decode($base64Sig)); // true

// Same applies for hex
$hexSig = bin2hex($keyPair->signMessage($message));
$keyPair->verifyMessage($message, $hexSig);        // WRONG — always false
$keyPair->verifyMessage($message, hex2bin($hexSig)); // CORRECT
```

**WRONG/CORRECT — message must be identical byte-for-byte:**

```php
// WRONG: trailing newline or whitespace changes the hash
$keyPair->verifyMessage("Hello, World!\n", $signatureOfHelloWorldWithoutNewline); // false

// CORRECT: message bytes must exactly match what was signed
$keyPair->verifyMessage("Hello, World!", $signatureOfHelloWorldWithoutNewline); // true
```

**WRONG/CORRECT — wrong keypair for verification:**

```php
$keyPairA = KeyPair::random();
$keyPairB = KeyPair::random();

$signature = $keyPairA->signMessage("test");

// WRONG: verifying with a different key always fails
$keyPairB->verifyMessage("test", $signature); // false

// CORRECT: use the account ID from the signer
$verifier = KeyPair::fromAccountId($keyPairA->getAccountId());
$verifier->verifyMessage("test", $signature); // true
```

## Security Notes

**Display the message before signing.** Never auto-sign without user review. SEP-53 signatures carry the same weight as consent — if a user signs a malicious message without reading it, the signature can be used as proof of consent.

**Key ownership vs account control.** A valid SEP-53 signature proves the signer held the private key at signing time. It does not prove current control of the account:

- Multi-sig accounts: one signature does not mean transaction authority
- Revoked signers: the key may have been removed from the account
- Weight thresholds: the key may have insufficient weight

For access-control decisions on critical operations, verify the account's current signer set and thresholds on-chain via `$sdk->requestAccount($accountId)->getSigners()`.

**Cross-SDK compatibility.** SEP-53 signatures are interoperable across all Stellar SDKs (Java, Python, Flutter, JavaScript, Kotlin KMP). A signature created in any one SDK verifies correctly in PHP, and vice versa, because they all use the same prefix, hash algorithm, and Ed25519 implementation.

## Related SEPs

- [SEP-10](sep-10.md) — Web authentication using transaction-based challenges
- SEP-07 — URI scheme for requesting signatures from wallets
