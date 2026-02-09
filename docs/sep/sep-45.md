# SEP-45: Web Authentication for Contract Accounts

Authenticate Soroban smart contract accounts (C... addresses) with anchor services.

## Overview

SEP-45 enables wallets and clients to prove control of a Soroban contract account by signing authorization entries provided by an anchor's authentication server. Upon successful verification, the server returns a JWT token for accessing protected SEP services.

Use SEP-45 when:

- Authenticating a Soroban contract with an anchor
- Accessing SEP-24 deposits/withdrawals from a contract account
- Using SEP-12 KYC or SEP-38 quotes with contract accounts

**SEP-45 vs SEP-10:**
- SEP-45: For contract accounts (C... addresses)
- SEP-10: For traditional accounts (G... and M... addresses)

Services supporting all account types should implement both protocols.

### How it works

1. Client requests a challenge from the server
2. Server returns authorization entries calling `web_auth_verify` on its web-auth contract
3. Client validates and signs the entries with keypairs registered in the contract
4. Client submits signed entries to server
5. Server simulates the transaction — this invokes the client contract's `__check_auth`
6. If `__check_auth` succeeds, server returns a JWT token

## Quick example

The `jwtToken()` method handles the entire flow automatically. This example loads configuration from the anchor's stellar.toml file.

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;

// Your contract account (must implement __check_auth)
$contractId = "CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ";

// Signer registered in your contract's __check_auth implementation
$signer = KeyPair::fromSeed("SXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");

// Create instance from domain and authenticate in one step
$webAuth = WebAuthForContracts::fromDomain("anchor.example.com", Network::testnet());
$jwtToken = $webAuth->jwtToken($contractId, [$signer]);

echo "Authenticated! Token: " . substr($jwtToken, 0, 50) . "...\n";
```

## Prerequisites

Before using SEP-45, ensure:

1. **Server Configuration**: The service must have a stellar.toml with:
   - `WEB_AUTH_FOR_CONTRACTS_ENDPOINT`: URL for the authentication endpoint
   - `WEB_AUTH_CONTRACT_ID`: The server's web-auth contract address (C...)
   - `SIGNING_KEY`: The server's signing key (G...)

2. **Client Contract Requirements**: Your contract account must:
   - Be deployed on the Stellar network (testnet or pubnet)
   - Implement `__check_auth` to define authorization rules
   - Have the signer's public key registered in its contract storage

3. **Signer Keypairs**: You need the secret keys for the signers registered in your contract's `__check_auth` implementation

## Creating the service

### From stellar.toml

The `fromDomain()` factory method loads configuration from the anchor's stellar.toml file. This is the typical approach since it pulls the correct endpoint and contract information automatically.

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Network;

$webAuth = WebAuthForContracts::fromDomain("anchor.example.com", Network::testnet());
```

### Manual configuration

You can also provide all configuration values directly, which works well for testing or when you have the configuration cached.

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Network;

$webAuth = new WebAuthForContracts(
    authEndpoint: "https://anchor.example.com/auth/sep45",
    webAuthContractId: "CCALHRGH5RXIDJDRLPPG4ZX2S563TB2QKKJR4STWKVQCYB6JVPYQXHRG",
    serverSigningKey: "GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP",
    serverHomeDomain: "anchor.example.com",
    network: Network::testnet()
);
```

### Custom Soroban RPC URL

By default, the SDK uses `soroban-testnet.stellar.org` for testnet and `soroban.stellar.org` for pubnet. Specify a custom URL if you run a private RPC server.

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Network;

$webAuth = new WebAuthForContracts(
    authEndpoint: "https://anchor.example.com/auth/sep45",
    webAuthContractId: "CCALHRGH5RXIDJDRLPPG4ZX2S563TB2QKKJR4STWKVQCYB6JVPYQXHRG",
    serverSigningKey: "GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP",
    serverHomeDomain: "anchor.example.com",
    network: Network::testnet(),
    httpClient: null,
    sorobanRpcUrl: "https://your-custom-rpc.example.com"
);
```

## Basic authentication

The `jwtToken()` method executes the complete SEP-45 flow: requesting the challenge, validating entries, signing with your keypairs, and submitting for a JWT.

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;

$contractId = "CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ";
$signer = KeyPair::fromSeed("SXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");

$webAuth = WebAuthForContracts::fromDomain("anchor.example.com", Network::testnet());
$jwtToken = $webAuth->jwtToken($contractId, [$signer]);
```

## Signature expiration

Signatures include an expiration ledger for replay protection. Per SEP-45, this should be set to a near-future ledger to limit the replay window.

### Automatic expiration (default)

When you don't specify an expiration ledger, the SDK automatically fetches the current ledger from Soroban RPC and sets expiration to current ledger + 10 (~50-60 seconds).

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;

$webAuth = WebAuthForContracts::fromDomain("anchor.example.com", Network::testnet());

// Expiration is auto-filled (current ledger + 10)
$jwtToken = $webAuth->jwtToken($contractId, [$signer]);
```

### Custom expiration

You can also set a custom expiration ledger when you need more control over the signature validity window.

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;

$webAuth = WebAuthForContracts::fromDomain("anchor.example.com", Network::testnet());

$jwtToken = $webAuth->jwtToken(
    $contractId,
    [$signer],
    homeDomain: null,
    clientDomain: null,
    clientDomainKeyPair: null,
    clientDomainSigningCallback: null,
    signatureExpirationLedger: 1500000
);
```

## Contracts without signature requirements

Some contracts implement `__check_auth` without requiring signature verification (e.g., contracts using other authorization mechanisms). Per SEP-45, client signatures are optional in such cases.

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Network;

$webAuth = WebAuthForContracts::fromDomain("anchor.example.com", Network::testnet());

// Empty signers array - no signatures will be added
$jwtToken = $webAuth->jwtToken($contractId, []);
```

**Note:** When the signers array is empty, the SDK skips the Soroban RPC call since no signature expiration is needed. This only works if both the anchor and your contract support signature-less authentication.

## Client domain verification

Non-custodial wallets can prove their domain to the anchor, letting the anchor attribute requests to a specific wallet application. Your domain needs a stellar.toml with a `SIGNING_KEY`.

### Local signing

When you have direct access to the client domain's signing key, you can sign locally.

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;

$contractId = "CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ";
$signer = KeyPair::fromSeed("SXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");

// Your wallet's SIGNING_KEY from stellar.toml
$clientDomainKeyPair = KeyPair::fromSeed("SYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY");

$webAuth = WebAuthForContracts::fromDomain("anchor.example.com", Network::testnet());

$jwtToken = $webAuth->jwtToken(
    $contractId,
    [$signer],
    homeDomain: "anchor.example.com",
    clientDomain: "wallet.example.com",
    clientDomainKeyPair: $clientDomainKeyPair
);
```

### Remote signing via callback

If the client domain signing key is on a remote server, use a callback function. The callback receives a `SorobanAuthorizationEntry` and returns the signed entry.

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use GuzzleHttp\Client;

$contractId = "CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ";
$signer = KeyPair::fromSeed("SXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");

$clientDomainSigningCallback = function(SorobanAuthorizationEntry $entry): SorobanAuthorizationEntry {
    // Send the entry to your remote signing service
    $httpClient = new Client();
    $response = $httpClient->post('https://your-signing-server.com/sign-sep-45', [
        'json' => [
            'authorization_entry' => $entry->toBase64Xdr(),
            'network_passphrase' => 'Test SDF Network ; September 2015'
        ],
        'headers' => ['Authorization' => 'Bearer YOUR_TOKEN']
    ]);

    $jsonData = json_decode($response->getBody()->__toString(), true);
    return SorobanAuthorizationEntry::fromBase64Xdr($jsonData['authorization_entry']);
};

$webAuth = WebAuthForContracts::fromDomain("anchor.example.com", Network::testnet());

$jwtToken = $webAuth->jwtToken(
    $contractId,
    [$signer],
    homeDomain: "anchor.example.com",
    clientDomain: "wallet.example.com",
    clientDomainKeyPair: null,
    clientDomainSigningCallback: $clientDomainSigningCallback
);
```

## Step-by-step authentication

For more control, you can execute each step individually. Helpful for debugging or when you need to customize the flow.

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;

$contractAccountId = "CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ";
$signerKeyPair = KeyPair::fromSeed("SXXXXX...");
$homeDomain = "anchor.example.com";

$webAuth = WebAuthForContracts::fromDomain($homeDomain, Network::testnet());

try {
    // Step 1: Get challenge from server
    $challengeResponse = $webAuth->getChallenge($contractAccountId, $homeDomain);

    // Step 2: Decode authorization entries from base64 XDR
    $authEntries = $webAuth->decodeAuthorizationEntries(
        $challengeResponse->getAuthorizationEntries()
    );

    // Step 3: Validate challenge (security checks)
    $webAuth->validateChallenge($authEntries, $contractAccountId, $homeDomain);

    // Step 4: Get current ledger for signature expiration
    $sorobanServer = new SorobanServer("https://soroban-testnet.stellar.org");
    $latestLedgerResponse = $sorobanServer->getLatestLedger();
    $signatureExpirationLedger = $latestLedgerResponse->sequence + 10;

    // Step 5: Sign authorization entries
    $signedEntries = $webAuth->signAuthorizationEntries(
        $authEntries,
        $contractAccountId,
        [$signerKeyPair],
        $signatureExpirationLedger
    );

    // Step 6: Submit signed entries for JWT token
    $jwtToken = $webAuth->sendSignedChallenge($signedEntries);

    echo "JWT Token: " . $jwtToken . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

## Request format configuration

The SDK supports both `application/x-www-form-urlencoded` and `application/json` when submitting signed challenges. Form URL encoding is used by default.

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Network;

$webAuth = WebAuthForContracts::fromDomain("anchor.example.com", Network::testnet());

// Use JSON format instead of form-urlencoded
$webAuth->setUseFormUrlEncoded(false);

$jwtToken = $webAuth->jwtToken($contractId, [$signer]);
```

## Error handling

The SDK throws specific exception types for different failure scenarios:

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidContractAddress;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidFunctionName;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorSubInvocationsFound;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidServerSignature;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidNetworkPassphrase;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorMissingServerEntry;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorMissingClientEntry;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeRequestErrorResponse;
use Soneso\StellarSDK\SEP\WebAuthForContracts\SubmitContractChallengeErrorResponseException;
use Soneso\StellarSDK\SEP\WebAuthForContracts\SubmitContractChallengeTimeoutResponseException;
use Soneso\StellarSDK\SEP\WebAuthForContracts\SubmitContractChallengeUnknownResponseException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;

$webAuth = WebAuthForContracts::fromDomain("anchor.example.com", Network::testnet());

try {
    $jwtToken = $webAuth->jwtToken($contractId, [$signer]);
    
} catch (ContractChallengeValidationErrorInvalidContractAddress $e) {
    // Server's contract address doesn't match stellar.toml - potential security issue
    echo "Security error: contract address mismatch\n";
    
} catch (ContractChallengeValidationErrorSubInvocationsFound $e) {
    // Challenge contains unauthorized sub-invocations - do NOT sign
    echo "Security error: sub-invocations detected. Report to anchor.\n";
    
} catch (ContractChallengeValidationErrorInvalidServerSignature $e) {
    // Server's signature is invalid - potential man-in-the-middle attack
    echo "Security error: invalid server signature\n";
    
} catch (ContractChallengeValidationErrorInvalidNetworkPassphrase $e) {
    // Network passphrase mismatch - wrong network configuration
    echo "Configuration error: network passphrase mismatch\n";
    
} catch (ContractChallengeValidationErrorInvalidFunctionName $e) {
    // Function name is not 'web_auth_verify' - invalid challenge
    echo "Invalid challenge: wrong function name\n";
    
} catch (ContractChallengeValidationErrorMissingServerEntry $e) {
    // No authorization entry for server account
    echo "Invalid challenge: missing server entry\n";
    
} catch (ContractChallengeValidationErrorMissingClientEntry $e) {
    // No authorization entry for client account
    echo "Invalid challenge: missing client entry\n";
    
} catch (ContractChallengeRequestErrorResponse $e) {
    // Server returned an error for challenge request
    echo "Challenge request failed: " . $e->getMessage() . "\n";
    
} catch (SubmitContractChallengeErrorResponseException $e) {
    // Server rejected the signed challenge
    // Common cause: signer not registered in contract's __check_auth
    echo "Authentication failed: " . $e->getMessage() . "\n";

} catch (SubmitContractChallengeTimeoutResponseException $e) {
    // Server timed out processing the challenge
    echo "Server timeout - please try again\n";

} catch (SubmitContractChallengeUnknownResponseException $e) {
    // Unexpected server response
    echo "Unexpected error (HTTP " . $e->getCode() . "): " . $e->getMessage() . "\n";

} catch (Exception $e) {
    echo "Unexpected error: " . $e->getMessage() . "\n";
}
```

### Common issues

| Error | Cause | Solution |
|-------|-------|----------|
| `SubmitContractChallengeErrorResponseException` | Signer not in contract's `__check_auth` | Verify signer is registered in contract storage |
| `ContractChallengeValidationErrorInvalidContractAddress` | Contract address mismatch | Check stellar.toml `WEB_AUTH_CONTRACT_ID` |
| `ContractChallengeValidationErrorSubInvocationsFound` | Malicious challenge | Don't sign; report to anchor |
| `ContractChallengeValidationErrorInvalidNetworkPassphrase` | Wrong network | Check you're using testnet vs pubnet correctly |
| `ContractChallengeValidationErrorInvalidServerSignature` | Invalid server signature | Server may be compromised or misconfigured |

## Security notes

- **Store JWT tokens securely** — Never expose them in logs, URLs, or insecure storage. Use HTTPS for all requests.
- **Report suspicious challenges** — If authentication fails with `ContractChallengeValidationErrorSubInvocationsFound`, the anchor may be compromised. Do not sign and report the issue.
- **Nonce validation** — The SDK automatically validates nonce consistency across all authorization entries for replay protection.
- **Network passphrase validation** — The SDK verifies that the network passphrase in the challenge matches your configured network, preventing cross-network replay attacks.

The SDK automatically validates challenges (contract address, server signature, function name, network passphrase, nonce consistency) and throws specific exceptions if anything looks wrong.

## Using the JWT token

Once authenticated, include the JWT token in the `Authorization` header when making requests to protected SEP services.

```php
<?php

use GuzzleHttp\Client;

$jwtToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...";
$httpClient = new Client();

// Use token with SEP-24 deposit
$response = $httpClient->post('https://anchor.example.com/sep24/transactions/deposit/interactive', [
    'headers' => ['Authorization' => 'Bearer ' . $jwtToken],
    'json' => ['asset_code' => 'USDC']
]);

// Use token with SEP-12 KYC
$response = $httpClient->get('https://anchor.example.com/kyc/customer', [
    'headers' => ['Authorization' => 'Bearer ' . $jwtToken]
]);
```

## Network support

The SDK supports both testnet and public (mainnet) networks. Use the appropriate network constant when creating the service.

```php
<?php

use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;

// Testnet
$webAuth = WebAuthForContracts::fromDomain("testnet.anchor.com", Network::testnet());

// Public network (mainnet)
$webAuth = WebAuthForContracts::fromDomain("anchor.com", Network::public());
```

## Reference contracts

Your contract account must implement `__check_auth` to define authorization rules. The Stellar Anchor Platform provides a reference implementation:

- [Account Contract](https://github.com/stellar/anchor-platform/tree/main/soroban/contracts/account) - Sample contract with Ed25519 signature verification in `__check_auth`

**Server-side web auth contract:** Anchors deploy a web auth contract at `WEB_AUTH_CONTRACT_ID`. The reference implementation is deployed on pubnet at `CALI6JC3MSNDGFRP7Z2OKUEPREHOJRRXKMJEWQDEFZPFGXALA45RAUTH`.

## Extended documentation

For more detailed examples including step-by-step authentication flows and advanced usage patterns, see the [SEP-45 examples documentation](https://github.com/Soneso/stellar-php-sdk/blob/main/examples/sep-0045-webauth-contracts.md).

For implementation details and edge cases, see the [SDK test cases](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/Integration/SEP045Test.php).

## Related SEPs

- [SEP-10](sep-10.md) - Authentication for traditional accounts (G... addresses)
- [SEP-24](sep-24.md) - Interactive deposit/withdrawal
- [SEP-12](sep-12.md) - KYC API
- [SEP-38](sep-38.md) - Quotes API

## Reference

- [SEP-45 Specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md)
- [Stellar PHP SDK](https://github.com/Soneso/stellar-php-sdk)

---

[Back to SEP Overview](README.md)
