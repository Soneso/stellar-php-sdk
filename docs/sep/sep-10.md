# SEP-10: Stellar Web Authentication

SEP-10 defines how wallets prove account ownership to anchors and other services. When a service needs to verify you control a Stellar account, SEP-10 handles the challenge-response flow and returns a JWT token you can use for authenticated requests.

**Use SEP-10 when:**
- Authenticating with anchors before deposits/withdrawals (SEP-6, SEP-24)
- Submitting KYC information (SEP-12)
- Accessing any service that requires proof of account ownership

**Spec:** [SEP-0010](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0010.md)

## Quick Example

This example demonstrates the simplest SEP-10 authentication flow: creating a WebAuth instance from the anchor's domain and obtaining a JWT token in a single call.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

// Create WebAuth from the anchor's domain - this automatically loads
// the stellar.toml and extracts the WEB_AUTH_ENDPOINT and SIGNING_KEY
$webAuth = WebAuth::fromDomain("testanchor.stellar.org", Network::testnet());

// Get JWT token - handles challenge request, signing, and submission
$userKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");
$jwtToken = $webAuth->jwtToken($userKeyPair->getAccountId(), [$userKeyPair]);

// Use the token for authenticated requests to SEP-6, SEP-12, SEP-24, etc.
echo "Authenticated! Token: " . substr($jwtToken, 0, 50) . "...";
```

## Detailed Usage

### Creating WebAuth

#### From domain (recommended)

This method loads configuration automatically from the anchor's stellar.toml file, so you always have the correct endpoint and signing key.

```php
<?php

use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

// Loads stellar.toml and extracts WEB_AUTH_ENDPOINT and SIGNING_KEY
$webAuth = WebAuth::fromDomain("testanchor.stellar.org", Network::testnet());
```

#### Manual construction

Use this when you already have the endpoint and signing key, or when testing with custom configurations.

```php
<?php

use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = new WebAuth(
    authEndpoint: "https://testanchor.stellar.org/auth",
    serverSigningKey: "GCUZ6YLL5RQBTYLTTQLPCM73C5XAIUGK2TIMWQH7HPSGWVS2KJ2F3CHS",
    serverHomeDomain: "testanchor.stellar.org",
    network: Network::testnet()
);
```

### Standard authentication

For most use cases, `jwtToken()` handles the entire SEP-10 flow: requesting a challenge, validating it, signing with your keypair(s), and getting the JWT token.

> **Note:** Accounts don't need to exist on the Stellar network to authenticate. SEP-10 only proves you control the signing key for an account address. The server handles non-existent accounts by assuming default signature requirements.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = WebAuth::fromDomain("testanchor.stellar.org", Network::testnet());
$userKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");

$jwtToken = $webAuth->jwtToken(
    clientAccountId: $userKeyPair->getAccountId(),
    signers: [$userKeyPair]
);
```

The method performs these steps internally:
1. Requests a challenge transaction from the server
2. Validates the challenge (sequence number = 0, valid signatures, time bounds, operations)
3. Signs with your keypair(s)
4. Submits the signed transaction to the server
5. Returns the JWT token

### Multi-signature accounts

For accounts requiring multiple signatures to meet the authentication threshold, provide all required signers. The combined signature weight must meet the server's requirements.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = WebAuth::fromDomain("testanchor.stellar.org", Network::testnet());

// Provide all signers needed to meet the account's threshold
$signer1 = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");
$signer2 = KeyPair::fromSeed("SBGWSG6BTNCKCOB3DIFBGCVMUPQFYPA2HIF74DBGCZ6V5CSBRROPGKVZ");

$jwtToken = $webAuth->jwtToken(
    clientAccountId: $signer1->getAccountId(),
    signers: [$signer1, $signer2]
);
```

### Muxed accounts

Muxed accounts (M... addresses) bundle a user ID with a G... account. This lets services distinguish between multiple users sharing the same Stellar account.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = WebAuth::fromDomain("testanchor.stellar.org", Network::testnet());
$userKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");

// Create muxed account with user ID embedded in the address
$muxedAccount = new MuxedAccount($userKeyPair->getAccountId(), 1234567890);

$jwtToken = $webAuth->jwtToken(
    clientAccountId: $muxedAccount->getAccountId(), // Returns M... address
    signers: [$userKeyPair]
);
```

#### Memo-based user separation

For services that use memos instead of muxed accounts to identify users sharing a single Stellar account, pass the memo as a separate parameter.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = WebAuth::fromDomain("testanchor.stellar.org", Network::testnet());
$userKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");

$jwtToken = $webAuth->jwtToken(
    clientAccountId: $userKeyPair->getAccountId(),
    signers: [$userKeyPair],
    memo: 1234567890  // User ID memo (must be integer)
);
```

> **Note:** You cannot use both a muxed account (M...) and a memo simultaneously. The SDK will throw an `InvalidArgumentException` if you attempt this.

### Client attribution (non-custodial wallets)

Client domain verification lets wallets prove their identity to anchors. Anchors can then provide different experiences for users coming from known, trusted wallets.

#### Local signing

When the wallet has direct access to its signing key, provide the keypair directly. The wallet's stellar.toml must include a `SIGNING_KEY` that matches the provided keypair.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = WebAuth::fromDomain("testanchor.stellar.org", Network::testnet());

$userKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");
$clientDomainKeyPair = KeyPair::fromSeed("SBGWSG6BTNCKCOB3DIFBGCVMUPQFYPA2HIF74DBGCZ6V5CSBRROPGKVZ");

$jwtToken = $webAuth->jwtToken(
    clientAccountId: $userKeyPair->getAccountId(),
    signers: [$userKeyPair],
    clientDomain: "mywallet.com",
    clientDomainKeyPair: $clientDomainKeyPair
);
```

#### Remote signing callback

When the client domain signing key is stored on a separate server (recommended for security), use a callback to delegate signing. This is the recommended approach for production.

```php
<?php

use Exception;
use GuzzleHttp\Client;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = WebAuth::fromDomain("testanchor.stellar.org", Network::testnet());
$userKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");

// Callback receives base64-encoded transaction XDR and must return signed XDR
$signingCallback = function(string $transactionXdr): string {
    $httpClient = new Client();
    $response = $httpClient->post('https://signing-server.mywallet.com/sign', [
        'json' => [
            'transaction' => $transactionXdr,
            'network_passphrase' => 'Test SDF Network ; September 2015'
        ],
        'headers' => ['Authorization' => 'Bearer YOUR_API_TOKEN']
    ]);
    
    $data = json_decode($response->getBody()->getContents(), true);
    if (!isset($data['transaction'])) {
        throw new Exception("Invalid signing server response");
    }
    return $data['transaction'];
};

$jwtToken = $webAuth->jwtToken(
    clientAccountId: $userKeyPair->getAccountId(),
    signers: [$userKeyPair],
    clientDomain: "mywallet.com",
    clientDomainSigningCallback: $signingCallback
);
```

### Multiple home domains

When an anchor serves multiple domains from the same authentication server, specify which domain the challenge should be issued for.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$webAuth = WebAuth::fromDomain("testanchor.stellar.org", Network::testnet());
$userKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");

$jwtToken = $webAuth->jwtToken(
    clientAccountId: $userKeyPair->getAccountId(),
    signers: [$userKeyPair],
    homeDomain: "other-domain.com"  // Request challenge for specific domain
);
```

## Error handling

The SDK provides specific exception types for different failure scenarios. This lets you handle errors precisely and give users appropriate feedback.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeRequestErrorResponse;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationError;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidHomeDomain;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidMemoType;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidMemoValue;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidOperationType;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidSeqNr;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidSignature;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidSourceAccount;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidTimeBounds;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidWebAuthDomain;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorMemoAndMuxedAccount;
use Soneso\StellarSDK\SEP\WebAuth\SubmitCompletedChallengeErrorResponseException;
use Soneso\StellarSDK\SEP\WebAuth\SubmitCompletedChallengeTimeoutResponseException;
use Soneso\StellarSDK\SEP\WebAuth\SubmitCompletedChallengeUnknownResponseException;
use InvalidArgumentException;

try {
    $webAuth = WebAuth::fromDomain("testanchor.stellar.org", Network::testnet());
    $userKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");
    
    $jwtToken = $webAuth->jwtToken($userKeyPair->getAccountId(), [$userKeyPair]);
    
} catch (InvalidArgumentException $e) {
    // Invalid parameters (e.g., memo with muxed account, missing client domain keypair)
    echo "Invalid parameters: " . $e->getMessage();
    
} catch (ChallengeRequestErrorResponse $e) {
    // Server rejected the challenge request (HTTP error from auth endpoint)
    echo "Challenge request failed: " . $e->getMessage();
    
} catch (ChallengeValidationErrorInvalidSeqNr $e) {
    // CRITICAL SECURITY: Challenge has non-zero sequence number
    // This could indicate a malicious server trying to get you to sign a real transaction
    echo "Security error: Invalid sequence number - DO NOT PROCEED";
    
} catch (ChallengeValidationErrorInvalidSignature $e) {
    // Challenge wasn't properly signed by the server's signing key
    echo "Invalid server signature - check stellar.toml SIGNING_KEY";
    
} catch (ChallengeValidationErrorInvalidTimeBounds $e) {
    // Challenge expired or time bounds invalid - request a new one
    echo "Challenge expired or invalid time bounds";
    
} catch (ChallengeValidationErrorInvalidHomeDomain $e) {
    // First operation's data key doesn't match expected "domain auth" format
    echo "Invalid home domain in challenge";
    
} catch (ChallengeValidationErrorInvalidWebAuthDomain $e) {
    // web_auth_domain operation value doesn't match the auth endpoint host
    echo "Invalid web auth domain";
    
} catch (ChallengeValidationErrorInvalidSourceAccount $e) {
    // Operation source account is incorrect (first op must be client, others must be server)
    echo "Invalid source account in challenge operation";
    
} catch (ChallengeValidationErrorInvalidOperationType $e) {
    // Challenge contains non-ManageData operations (security risk)
    echo "Invalid operation type - all operations must be ManageData";
    
} catch (ChallengeValidationErrorInvalidMemoType $e) {
    // Memo must be MEMO_NONE or MEMO_ID
    echo "Invalid memo type";
    
} catch (ChallengeValidationErrorInvalidMemoValue $e) {
    // Memo value doesn't match the requested memo
    echo "Memo value mismatch";
    
} catch (ChallengeValidationErrorMemoAndMuxedAccount $e) {
    // Challenge has both memo and muxed account (invalid per SEP-10)
    echo "Cannot have both memo and muxed account";
    
} catch (ChallengeValidationError $e) {
    // Generic validation errors (specific errors have their own exception types above)
    echo "Challenge validation failed: " . $e->getMessage();
    
} catch (SubmitCompletedChallengeErrorResponseException $e) {
    // Server rejected the signed challenge (e.g., insufficient signers, invalid signatures)
    echo "Authentication failed: " . $e->getMessage();
    
} catch (SubmitCompletedChallengeTimeoutResponseException $e) {
    // Server returned 504 Gateway Timeout - retry with backoff
    echo "Server timeout - please retry";
    
} catch (SubmitCompletedChallengeUnknownResponseException $e) {
    // Unexpected HTTP response from server
    echo "Unexpected server response: " . $e->getMessage();
}
```

### Exception reference

| Exception | Cause | Solution |
|-----------|-------|----------|
| `InvalidArgumentException` | Invalid method parameters | Check parameters (no memo with M... account) |
| `ChallengeRequestErrorResponse` | Server rejected challenge request | Check account ID format, server status |
| `ChallengeValidationErrorInvalidSeqNr` | Sequence number ≠ 0 | **Security risk** - do not proceed |
| `ChallengeValidationErrorInvalidSignature` | Bad server signature | Verify stellar.toml SIGNING_KEY |
| `ChallengeValidationErrorInvalidTimeBounds` | Challenge expired | Request a new challenge |
| `ChallengeValidationErrorInvalidHomeDomain` | Wrong home domain | Check domain configuration |
| `ChallengeValidationErrorInvalidWebAuthDomain` | Wrong web auth domain | Verify auth endpoint URL |
| `ChallengeValidationErrorInvalidSourceAccount` | Wrong operation source | Server configuration issue |
| `ChallengeValidationErrorInvalidOperationType` | Non-ManageData operation | **Security risk** - server may be malicious |
| `ChallengeValidationErrorInvalidMemoType` | Memo not NONE or ID | Server configuration issue |
| `ChallengeValidationErrorInvalidMemoValue` | Memo mismatch | Check memo parameter matches server |
| `ChallengeValidationErrorMemoAndMuxedAccount` | Both memo and M... address | Use one or the other, not both |
| `SubmitCompletedChallengeErrorResponseException` | Signed challenge rejected | Provide sufficient signers |
| `SubmitCompletedChallengeTimeoutResponseException` | Server timeout (504) | Retry with exponential backoff |
| `SubmitCompletedChallengeUnknownResponseException` | Unexpected HTTP response | Check server logs, contact support |

### Retry logic example

For production applications, implement retry logic with exponential backoff for transient failures.

```php
<?php

use Exception;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;
use Soneso\StellarSDK\SEP\WebAuth\ChallengeValidationErrorInvalidTimeBounds;
use Soneso\StellarSDK\SEP\WebAuth\SubmitCompletedChallengeTimeoutResponseException;

/**
 * Authenticates with automatic retry for transient failures.
 *
 * @param WebAuth $webAuth The WebAuth instance
 * @param string $accountId The account to authenticate
 * @param array<KeyPair> $signers The keypairs to sign with
 * @param int $maxRetries Maximum retry attempts (default: 3)
 * @return string JWT token on success
 * @throws Exception When all retries are exhausted
 */
function authenticateWithRetry(
    WebAuth $webAuth,
    string $accountId,
    array $signers,
    int $maxRetries = 3
): string {
    $attempt = 0;
    $lastException = null;
    
    while ($attempt < $maxRetries) {
        try {
            return $webAuth->jwtToken($accountId, $signers);
            
        } catch (ChallengeValidationErrorInvalidTimeBounds $e) {
            // Challenge expired - retry immediately with fresh challenge
            $attempt++;
            $lastException = $e;
            
        } catch (SubmitCompletedChallengeTimeoutResponseException $e) {
            // Server timeout - retry with exponential backoff
            $attempt++;
            $lastException = $e;
            sleep(pow(2, $attempt)); // 2, 4, 8 seconds
        }
    }
    
    throw $lastException ?? new Exception("Authentication failed after $maxRetries attempts");
}

// Usage
$webAuth = WebAuth::fromDomain("testanchor.stellar.org", Network::testnet());
$userKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");

$jwtToken = authenticateWithRetry($webAuth, $userKeyPair->getAccountId(), [$userKeyPair]);
```

## Security notes

- **Store tokens securely.** JWT tokens grant access to protected services. Don't log them or expose them in URLs.
- **Use the correct network.** Ensure you pass `Network::testnet()` or `Network::public()` matching the server's network.

The SDK automatically validates challenges (sequence number, signatures, time bounds, operations) and throws specific exceptions if anything looks wrong.

> **Note:** The SDK does not currently support Authorization headers when requesting challenges (SEP-10 v3.4.0 feature). Most servers don't require this, as it's an optional feature that servers may implement to restrict or rate-limit challenge generation.

## Testing

The SDK provides mock handler support for testing without making real network requests. You can simulate various server responses in your unit tests.

```php
<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

// Create WebAuth instance with manual configuration for testing
$webAuth = new WebAuth(
    authEndpoint: "https://test.example.com/auth",
    serverSigningKey: "GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP",
    serverHomeDomain: "test.example.com",
    network: Network::testnet()
);

// Create mock responses
// In a real test, you would construct a valid challenge transaction XDR
// The SDK's unit tests show how to build these - see WebAuthTest.php
$challengeResponseJson = json_encode(['transaction' => 'YOUR_VALID_CHALLENGE_XDR']);
$tokenResponseJson = json_encode(['token' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...']);

$mock = new MockHandler([
    new Response(200, [], $challengeResponseJson),  // Challenge response
    new Response(200, [], $tokenResponseJson)       // Token response
]);

// Inject mock handler
$webAuth->setMockHandler($mock);

// Now jwtToken() will use mock responses instead of real HTTP requests
$userKeyPair = KeyPair::random();
$jwtToken = $webAuth->jwtToken($userKeyPair->getAccountId(), [$userKeyPair]);
```

For complete testing examples including valid challenge transaction construction, refer to the SDK's test suite in `Soneso/StellarSDKTests/Unit/SEP/WebAuth/WebAuthTest.php`.

## JWT token structure

The JWT token returned by SEP-10 authentication contains standard claims. The SDK doesn't include a JWT decoder, but understanding the token structure helps with debugging and validation.

**Standard JWT claims:**
- `sub` - The authenticated account (G... or M... address, or G...:memo format for memo-based auth)
- `iss` - The token issuer (authentication server URL)
- `iat` - Token issued at timestamp (Unix epoch)
- `exp` - Token expiration timestamp (Unix epoch)
- `client_domain` - (optional) Present when client domain verification was performed

To decode and inspect a JWT token, you can use any JWT library or the [jwt.io](https://jwt.io) debugger.

## Related SEPs

- [SEP-01](sep-01.md) - stellar.toml discovery (provides auth endpoint)
- [SEP-06](sep-06.md) - Deposit/withdrawal (uses SEP-10 auth)
- [SEP-12](sep-12.md) - KYC API (uses SEP-10 auth)
- [SEP-24](sep-24.md) - Interactive deposit/withdrawal (uses SEP-10 auth)
- [SEP-45](sep-45.md) - Web Authentication for Contract Accounts (Soroban alternative)

---

[Back to SEP Overview](README.md)
