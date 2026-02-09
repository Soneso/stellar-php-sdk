# SEP-53: Sign and Verify Messages

Prove ownership of a Stellar private key by signing arbitrary messages.

## Overview

> **Note:** SEP-53 is currently in Draft status (v0.0.1). The specification may evolve before reaching final status.

SEP-53 defines how to sign and verify messages with Stellar keypairs. Use it when you need to:

- Authenticate users by proving key ownership
- Sign attestations or consent agreements
- Verify signatures from other Stellar SDKs
- Create provable off-chain statements

The protocol adds a prefix (`"Stellar Signed Message:\n"`) before hashing, which prevents signed messages from being confused with transaction signatures.

## Quick example

Sign a message and verify the signature:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

// Generate a random keypair (or use KeyPair::fromSeed() for an existing key)
$keyPair = KeyPair::random();

// Sign a message
$signature = $keyPair->signMessage("I agree to the terms of service");

// Verify the signature
$isValid = $keyPair->verifyMessage("I agree to the terms of service", $signature);
echo $isValid ? "Valid\n" : "Invalid\n";
```

## Detailed usage

### Signing messages

Sign a message and encode the signature for transmission. The raw signature is 64 bytes, so you'll typically encode it as base64 or hex:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$keyPair = KeyPair::fromSeed("SXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");

$message = "User consent granted at 2025-01-15T12:00:00Z";
$signature = $keyPair->signMessage($message);

if ($signature === null) {
    // Signing can fail if the keypair has no private key or on crypto errors
    throw new RuntimeException("Failed to sign message");
}

// Encode as base64 for transmission
$base64Signature = base64_encode($signature);
echo "Signature: " . $base64Signature . "\n";

// Or encode as hex
$hexSignature = bin2hex($signature);
echo "Signature (hex): " . $hexSignature . "\n";
```

### Verifying messages

Verify a signature using only the public key. This is typically done server-side after receiving a signed message from a client:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

// Create keypair from public key only (no private key needed for verification)
$publicKey = KeyPair::fromAccountId("GABC...");

$message = "User consent granted at 2025-01-15T12:00:00Z";
$base64Signature = "..."; // Received from client

$signature = base64_decode($base64Signature);
$isValid = $publicKey->verifyMessage($message, $signature);

if ($isValid) {
    echo "Signature verified\n";
} else {
    echo "Invalid signature\n";
}
```

### Verifying hex-encoded signatures

If the signature was transmitted as a hex string, decode it with `hex2bin()` before verification:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$publicKey = KeyPair::fromAccountId("GABC...");

$message = "Cross-platform message";
$hexSignature = "a1b2c3d4..."; // Received as hex
$signature = hex2bin($hexSignature);

$isValid = $publicKey->verifyMessage($message, $signature);
```

### Signing binary data

The message doesn't have to be text. You can sign any binary data such as file contents:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$keyPair = KeyPair::fromSeed("SXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");

// Sign file contents
$fileContents = file_get_contents("document.pdf");
$signature = $keyPair->signMessage($fileContents);

if ($signature !== null) {
    $base64Signature = base64_encode($signature);
    echo "Document signature: " . $base64Signature . "\n";
}
```

### Authentication flow example

A complete authentication flow where the server generates a challenge and the client proves key ownership:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

// === SERVER: Generate a challenge ===
$challenge = "authenticate:" . bin2hex(random_bytes(16)) . ":" . time();

// === CLIENT: Sign the challenge ===
$clientKeyPair = KeyPair::fromSeed("SXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
$signature = $clientKeyPair->signMessage($challenge);

if ($signature === null) {
    throw new RuntimeException("Failed to sign challenge");
}

$response = [
    'account_id' => $clientKeyPair->getAccountId(),
    'signature' => base64_encode($signature),
    'challenge' => $challenge
];

// === SERVER: Verify the response ===
$publicKey = KeyPair::fromAccountId($response['account_id']);
$signature = base64_decode($response['signature']);

if ($publicKey->verifyMessage($response['challenge'], $signature)) {
    echo "User authenticated as " . $response['account_id'] . "\n";
} else {
    echo "Authentication failed\n";
}
```

## Error handling

### Signing without a private key

Attempting to sign with a public-key-only keypair throws a `TypeError` because the SDK uses `strict_types=1`:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

// This keypair has no private key
$publicKeyOnly = KeyPair::fromAccountId("GABC...");

try {
    // Throws TypeError - no private key available
    $signature = $publicKeyOnly->signMessage("test");
} catch (\TypeError $e) {
    echo "Cannot sign: keypair has no private key\n";
}
```

### Handling null signatures

The `signMessage()` method can return `null` if signing fails due to cryptographic errors. Always check for null:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$keyPair = KeyPair::fromSeed("SXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");
$signature = $keyPair->signMessage("Important message");

if ($signature === null) {
    // Handle signing failure
    throw new RuntimeException("Signing failed");
}

// Safe to use $signature
$base64Signature = base64_encode($signature);
```

### Common verification failures

When verification fails, several causes are possible:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$publicKey = KeyPair::fromAccountId("GABC...");
$signature = base64_decode($receivedSignature);

if (!$publicKey->verifyMessage($message, $signature)) {
    // Possible causes:
    // 1. Message was modified after signing
    // 2. Signature was modified or corrupted in transit
    // 3. Wrong public key used for verification
    // 4. Signature was created for a different message
    
    http_response_code(401);
    echo json_encode(["error" => "Invalid signature"]);
    exit;
}

// Signature is valid, proceed
echo "Signature verified successfully\n";
```

## Protocol details

SEP-53 signing works like this:

```
signature = Ed25519Sign(privateKey, SHA256("Stellar Signed Message:\n" + message))
```

Verification reverses it:

```
valid = Ed25519Verify(publicKey, SHA256("Stellar Signed Message:\n" + message), signature)
```

The `"Stellar Signed Message:\n"` prefix provides domain separation. A signed message can never be confused with a Stellar transaction signature.

## Test vectors

Use these official test vectors from the SEP-53 specification to validate your implementation:

### ASCII message

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$seed = "SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW";
$expectedAccountId = "GBXFXNDLV4LSWA4VB7YIL5GBD7BVNR22SGBTDKMO2SBZZHDXSKZYCP7L";
$message = "Hello, World!";

$keyPair = KeyPair::fromSeed($seed);
assert($keyPair->getAccountId() === $expectedAccountId);

$signature = $keyPair->signMessage($message);
$base64Signature = base64_encode($signature);
$hexSignature = bin2hex($signature);

// Expected signatures:
$expectedBase64 = "fO5dbYhXUhBMhe6kId/cuVq/AfEnHRHEvsP8vXh03M1uLpi5e46yO2Q8rEBzu3feXQewcQE5GArp88u6ePK6BA==";
$expectedHex = "7cee5d6d885752104c85eea421dfdcb95abf01f1271d11c4bec3fcbd7874dccd6e2e98b97b8eb23b643cac4073bb77de5d07b0710139180ae9f3cbba78f2ba04";

assert($base64Signature === $expectedBase64, "Base64 signature mismatch");
assert($hexSignature === $expectedHex, "Hex signature mismatch");

echo "ASCII test vector passed\n";
```

### Japanese (UTF-8) message

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$seed = "SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW";
$message = "こんにちは、世界！";

$keyPair = KeyPair::fromSeed($seed);
$signature = $keyPair->signMessage($message);

$expectedBase64 = "CDU265Xs8y3OWbB/56H9jPgUss5G9A0qFuTqH2zs2YDgTm+++dIfmAEceFqB7bhfN3am59lCtDXrCtwH2k1GBA==";
$expectedHex = "083536eb95ecf32dce59b07fe7a1fd8cf814b2ce46f40d2a16e4ea1f6cecd980e04e6fbef9d21f98011c785a81edb85f3776a6e7d942b435eb0adc07da4d4604";

assert(base64_encode($signature) === $expectedBase64);
assert(bin2hex($signature) === $expectedHex);

echo "Japanese test vector passed\n";
```

### Binary data message

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

$seed = "SAKICEVQLYWGSOJS4WW7HZJWAHZVEEBS527LHK5V4MLJALYKICQCJXMW";

// Binary data (base64-decoded)
$message = base64_decode("2zZDP1sa1BVBfLP7TeeMk3sUbaxAkUhBhDiNdrksaFo=");

$keyPair = KeyPair::fromSeed($seed);
$signature = $keyPair->signMessage($message);

$expectedBase64 = "VA1+7hefNwv2NKScH6n+Sljj15kLAge+M2wE7fzFOf+L0MMbssA1mwfJZRyyrhBORQRle10X1Dxpx+UOI4EbDQ==";
$expectedHex = "540d7eee179f370bf634a49c1fa9fe4a58e3d7990b0207be336c04edfcc539ff8bd0c31bb2c0359b07c9651cb2ae104e4504657b5d17d43c69c7e50e23811b0d";

assert(base64_encode($signature) === $expectedBase64);
assert(bin2hex($signature) === $expectedHex);

echo "Binary test vector passed\n";
```

## Security notes

### Display messages before signing

Always show users the full message before signing. Never auto-sign without user review. This prevents phishing where users sign malicious content.

### Key ownership vs account control

A valid signature proves the signer has the private key. It doesn't prove they control the account:

- **Multi-sig accounts**: One signature doesn't mean transaction authority
- **Revoked signers**: A key may have been removed from the account
- **Weight thresholds**: The key may lack sufficient weight

For critical operations, check the account's current state on-chain.

### Signature encoding

SEP-53 doesn't specify an encoding format. Common choices:

| Encoding | Pros | Cons |
|----------|------|------|
| Base64 | Compact, URL-safe variant available | Needs decode |
| Hex | Human-readable, simple | 2x larger |

Pick one and document it. The raw signature is always 64 bytes.

## Cross-SDK compatibility

SEP-53 signatures work across all Stellar SDKs. A signature created in Java, Python, or Flutter can be verified in PHP, and vice versa.

**Compatible SDKs:** Java, Python, Flutter, JavaScript, Kotlin (KMP), and this PHP SDK.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;

// Signature from Java/Python/Flutter SDK
$base64Signature = "...";
$message = "Cross-platform message";

$publicKey = KeyPair::fromAccountId("GABC...");
$signature = base64_decode($base64Signature);

if ($publicKey->verifyMessage($message, $signature)) {
    echo "Verified across SDKs\n";
}
```

## Related SEPs

- [SEP-10](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0010.md) - Web authentication for accounts
- [SEP-45](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md) - Web authentication for contract accounts

## Reference

- [SEP-53 Specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0053.md)
- [KeyPair Source Code](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDK/Crypto/KeyPair.php)

---

[Back to SEP Overview](README.md)
