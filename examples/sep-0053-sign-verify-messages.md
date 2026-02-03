### SEP-0053 - Sign and Verify Messages

Stellar message signing is described in [SEP-0053](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0053.md). The SEP defines the standard way for Stellar account holders to sign and verify arbitrary messages, proving ownership of a private key without exposing it. This is useful for authentication, attestations, and verifying key ownership in contexts outside of transaction signing.

## Quick Start

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;

// Generate a keypair
$keyPair = KeyPair::random();

// Sign a message
$message = "Hello, Stellar!";
$signature = $keyPair->signMessage($message);

// Verify the signature
$isValid = $keyPair->verifyMessage($message, $signature);
echo $isValid ? "Valid signature" : "Invalid signature";
```

## API Reference

### signMessage

```php
public function signMessage(string $message): ?string
```

Signs a message according to SEP-53 specification and returns the raw signature bytes.

**Parameters:**
- `$message` - The message to sign (arbitrary text or binary data)

**Returns:**
- Raw signature bytes (64 bytes), or `null` if signing fails

**Throws:**
- `TypeError` - If the keypair has no private key (under strict_types=1)

**Encoding for transmission:**
SEP-53 does not mandate a specific string encoding format. Use `base64_encode()` or `bin2hex()` to encode the signature for transmission or storage:

```php
$signature = $keyPair->signMessage($message);
$base64Signature = base64_encode($signature);
$hexSignature = bin2hex($signature);
```

### verifyMessage

```php
public function verifyMessage(string $message, string $signature): bool
```

Verifies a SEP-53 message signature using this keypair's public key.

**Parameters:**
- `$message` - The original message that was signed
- `$signature` - The raw signature bytes to verify

**Returns:**
- `true` if the signature is valid for this message and public key, `false` otherwise

**Decoding received signatures:**
If you receive a signature as a base64 or hex string, decode it before passing to `verifyMessage()`:

```php
$signature = base64_decode($base64Signature);
$isValid = $keyPair->verifyMessage($message, $signature);
```

## Usage Examples

### Signing and encoding a message

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;

$keyPair = KeyPair::fromSeed("SBXXX..."); // Your secret seed

$message = "I authorize payment #12345";
$signature = $keyPair->signMessage($message);

// Encode for transmission
$base64Signature = base64_encode($signature);
echo "Signature (base64): " . $base64Signature . PHP_EOL;

// Or encode as hex
$hexSignature = bin2hex($signature);
echo "Signature (hex): " . $hexSignature . PHP_EOL;
```

### Signing binary data

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;

$keyPair = KeyPair::fromSeed("SBXXX...");

// Sign binary data (e.g., file contents or serialized data)
$binaryData = file_get_contents("document.pdf");
$signature = $keyPair->signMessage($binaryData);

$base64Signature = base64_encode($signature);
```

### Verifying a message with base64 signature

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;

// Public key of the signer
$publicKeyPair = KeyPair::fromAccountId("GXXX...");

$message = "I authorize payment #12345";
$base64Signature = "YWJjZGVm..."; // Received from client

// Decode and verify
$signature = base64_decode($base64Signature);
$isValid = $publicKeyPair->verifyMessage($message, $signature);

if ($isValid) {
    echo "Signature verified: message was signed by this account" . PHP_EOL;
} else {
    echo "Invalid signature: message was not signed by this account" . PHP_EOL;
}
```

### Verifying a message with hex signature

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;

$publicKeyPair = KeyPair::fromAccountId("GXXX...");

$message = "User consent granted at 2025-10-05T12:34:56Z";
$hexSignature = "a1b2c3d4..."; // Received as hex string

// Decode from hex and verify
$signature = hex2bin($hexSignature);
$isValid = $publicKeyPair->verifyMessage($message, $signature);
```

### Handling verification failure

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;

$publicKeyPair = KeyPair::fromAccountId("GXXX...");

$message = "Expected message";
$signature = base64_decode($receivedSignature);

if (!$publicKeyPair->verifyMessage($message, $signature)) {
    // Verification failed - signature does not match
    // Possible causes:
    // - Message was modified
    // - Signature was modified
    // - Wrong public key used for verification
    // - Signature was created for a different message

    http_response_code(401);
    echo json_encode(["error" => "Invalid signature"]);
    exit;
}

// Signature is valid, proceed with request
```

## Protocol Details

SEP-53 message signing follows this process:

1. **Prefix the message**: Prepend `"Stellar Signed Message:\n"` to the message
2. **Hash**: Calculate SHA-256 hash of the prefixed message
3. **Sign**: Sign the hash using Ed25519 with the private key

The formula is:
```
signature = Ed25519Sign(privkey, SHA256("Stellar Signed Message:\n" || message))
```

Verification follows the inverse process:
```
valid = Ed25519Verify(pubkey, SHA256("Stellar Signed Message:\n" || message), signature)
```

The prefix provides domain separation, ensuring that a signed message cannot be interpreted as a valid Stellar transaction.

## Security Notes

### Domain Separation

The `"Stellar Signed Message:\n"` prefix prevents signed messages from being confused with transaction signatures. Without this prefix, a malicious party could potentially trick a user into signing what appears to be a message but is actually a transaction hash.

### Key Ownership vs Account Control

SEP-53 signatures prove ownership of a private key, not necessarily control of a Stellar account. Consider these scenarios:

- **Multi-signature accounts**: A signature from one key proves ownership of that key, but the account may require multiple signatures for transactions
- **Revoked signers**: A key may have been removed from an account's signer list after the signature was created
- **Weight thresholds**: The signing key may not have sufficient weight to authorize account operations

For critical operations, verify the current account state on the Stellar ledger, not just the message signature.

### User Confirmation

Applications MUST display the full message content to users before signing. This prevents phishing attacks where users unknowingly sign malicious content. Never auto-sign messages without explicit user review.

### Missing Private Key

Calling `signMessage()` on a keypair without a private key throws a `TypeError` because the SDK uses `strict_types=1`. Always ensure the keypair has a private key before signing:

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;

$keyPair = KeyPair::fromAccountId("GXXX..."); // Public key only

$keyPair->signMessage("test"); // Throws TypeError - no private key
```

## Interoperability

SEP-53 implementations are compatible across Stellar SDKs. A signature created with one SDK can be verified by another.

### Verifying a signature from another SDK

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;

// Signature created by Java SDK, Python SDK, or Flutter SDK
$base64Signature = "hZ3+..."; // Received from another SDK
$message = "Cross-platform message";

$publicKeyPair = KeyPair::fromAccountId("GXXX...");
$signature = base64_decode($base64Signature);

if ($publicKeyPair->verifyMessage($message, $signature)) {
    echo "Signature verified across SDKs" . PHP_EOL;
}
```

**Compatible SDKs:**
- Java Stellar SDK
- Python Stellar SDK
- Flutter Stellar SDK

All implementations follow the same SEP-53 specification with identical prefix, hashing, and signing algorithms.

## References

- [SEP-0053 Specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0053.md)
- [Stellar Developer Documentation](https://developers.stellar.org/)
- [KeyPair Source Code](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDK/Crypto/KeyPair.php)
