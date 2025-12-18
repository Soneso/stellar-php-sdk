# SEP-45: Web Authentication for Contract Accounts

This document describes how to use the Stellar PHP SDK's SEP-45 implementation to authenticate Soroban smart contract accounts (C... addresses).

## Overview

SEP-45 enables wallets and clients to prove control of a Soroban contract account by signing authorization entries provided by an anchor's authentication server. Upon successful authentication, the server returns a JWT token that can be used to access other SEP services (SEP-12 KYC, SEP-24 deposits/withdrawals, SEP-38 quotes, etc.).

**SEP-45 vs SEP-10:**
- SEP-45 is for contract accounts (C... addresses) only
- SEP-10 is for traditional Stellar accounts (G... and M... addresses)
- Services supporting all account types should implement both protocols

## How Contract Authentication Works

Understanding the authentication flow requires knowing how contract accounts differ from traditional accounts:

### Traditional Accounts (G...)
- Signers are defined on-chain in the account's signer list
- Authentication verifies signatures against on-chain signers

### Contract Accounts (C...)
- Contracts define their own authorization logic via `__check_auth`
- The contract stores authorized signers (e.g., in persistent storage)
- Authentication invokes `__check_auth` which validates provided signatures

### The SEP-45 Flow

1. **Client** requests a challenge from the server
2. **Server** returns authorization entries calling `web_auth_verify` on its web-auth contract
3. **Client** validates and signs the entries with keypairs registered in the contract
4. **Client** submits signed entries to server
5. **Server** simulates the transaction - this invokes the client contract's `__check_auth`
6. If `__check_auth` succeeds, **Server** returns a JWT token

## Prerequisites

Before using SEP-45:

1. **Server Configuration**: The service must have a stellar.toml with:
   - `WEB_AUTH_FOR_CONTRACTS_ENDPOINT`: URL for the authentication endpoint
   - `WEB_AUTH_CONTRACT_ID`: The server's web-auth contract address (C...)
   - `SIGNING_KEY`: The server's signing key (G...)

2. **Client Contract Requirements**: Your contract account must:
   - Be deployed on the Stellar network (testnet or pubnet)
   - Implement `__check_auth` to define authorization rules
   - Have the signer's public key registered in its storage

3. **Signer Keypairs**: You need the secret keys for the signers registered in your contract's `__check_auth` implementation

## Basic Usage

### Simple Authentication

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;

// Your contract account (must be deployed and implement __check_auth)
$contractAccountId = "CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ";

// The signer keypair - its public key must be registered in your contract's __check_auth
$signerKeyPair = KeyPair::fromSeed("SXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX");

// Create WebAuthForContracts instance from domain (loads stellar.toml automatically)
$webAuth = WebAuthForContracts::fromDomain("anchor.example.com", Network::testnet());

try {
    // Execute complete authentication flow
    $jwtToken = $webAuth->jwtToken(
        $contractAccountId,
        [$signerKeyPair]
    );

    echo "Authentication successful!\n";
    echo "JWT Token: " . $jwtToken . "\n";

} catch (Exception $e) {
    echo "Authentication failed: " . $e->getMessage() . "\n";
}
```

### Manual Configuration

If you prefer not to load from stellar.toml:

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;

$contractAccountId = "CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ";
$signerKeyPair = KeyPair::fromSeed("SXXXXX...");

$webAuth = new WebAuthForContracts(
    authEndpoint: "https://anchor.example.com/auth",
    webAuthContractId: "CCALHRGH5RXIDJDRLPPG4ZX2S563TB2QKKJR4STWKVQCYB6JVPYQXHRG",
    serverSigningKey: "GBWMCCC3NHSKLAOJDBKKYW7SSH2PFTTNVFKWSGLWGDLEBKLOVP5JLBBP",
    serverHomeDomain: "anchor.example.com",
    network: Network::testnet()
);

$jwtToken = $webAuth->jwtToken($contractAccountId, [$signerKeyPair]);
```

## Signature Expiration

Signatures include an expiration ledger for replay protection. Per SEP-45, this should be set to a near-future ledger.

### Automatic Expiration (Default)

When you don't specify an expiration ledger, the SDK automatically:
1. Fetches the current ledger from Soroban RPC
2. Sets expiration to current ledger + 10 (~50-60 seconds)

```php
// Expiration is auto-filled (current ledger + 10)
$jwtToken = $webAuth->jwtToken($contractAccountId, [$signerKeyPair]);
```

### Custom Expiration

For specific requirements, you can set a custom expiration:

```php
$customExpirationLedger = 1000000;

$jwtToken = $webAuth->jwtToken(
    $contractAccountId,
    [$signerKeyPair],
    homeDomain: null,
    clientDomain: null,
    clientDomainKeyPair: null,
    clientDomainSigningCallback: null,
    signatureExpirationLedger: $customExpirationLedger
);
```

## Contracts Without Signature Requirements

Some contracts may implement `__check_auth` without requiring signature verification (e.g., contracts using other authorization mechanisms). Per SEP-45, client signatures are optional in such cases.

For these contracts, pass an empty signers array:

```php
// Empty signers array - no signatures will be added
$jwtToken = $webAuth->jwtToken(
    $contractAccountId,
    []  // No signers needed
);
```

When the signers array is empty, the SDK skips the Soroban RPC call since no signature expiration is needed.

**Note:** This only works if the server's anchor and your contract both support signature-less authentication. Most production contracts require signatures.

## Client Domain Verification

Non-custodial wallets can prove their domain to the anchor. This requires:
1. A stellar.toml on your domain with a `SIGNING_KEY`
2. The keypair for that signing key

### Local Signing

```php
$contractAccountId = "CCIBUCGPOHWMMMFPFTDWBSVHQRT4DIBJ7AD6BZJYDITBK2LCVBYW7HUQ";
$signerKeyPair = KeyPair::fromSeed("SXXXXX...");

// Your wallet's SIGNING_KEY from stellar.toml
$clientDomainKeyPair = KeyPair::fromSeed("SYYYYY...");

$webAuth = WebAuthForContracts::fromDomain("anchor.example.com", Network::testnet());

$jwtToken = $webAuth->jwtToken(
    $contractAccountId,
    [$signerKeyPair],
    homeDomain: "anchor.example.com",
    clientDomain: "wallet.example.com",
    clientDomainKeyPair: $clientDomainKeyPair
);
```

### Remote Signing via Callback

If the client domain signing key is on a remote server:

```php
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use GuzzleHttp\Client;

$clientDomainSigningCallback = function(SorobanAuthorizationEntry $entry): SorobanAuthorizationEntry {
    // The callback receives only the client domain entry that needs to be signed
    // Send to your remote signing service and return the signed entry

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

$jwtToken = $webAuth->jwtToken(
    $contractAccountId,
    [$signerKeyPair],
    homeDomain: "anchor.example.com",
    clientDomain: "wallet.example.com",
    clientDomainKeyPair: null,
    clientDomainSigningCallback: $clientDomainSigningCallback
);
```

## Step-by-Step Authentication

For more control, execute each step individually:

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

    // Step 3: Validate challenge
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

## Error Handling

The SDK provides specific exception types for different failure scenarios:

```php
<?php

use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidContractAddress;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidFunctionName;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorSubInvocationsFound;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorInvalidServerSignature;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorMissingServerEntry;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeValidationErrorMissingClientEntry;
use Soneso\StellarSDK\SEP\WebAuthForContracts\ContractChallengeRequestErrorResponse;
use Soneso\StellarSDK\SEP\WebAuthForContracts\SubmitContractChallengeErrorResponseException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;

$webAuth = WebAuthForContracts::fromDomain("anchor.example.com", Network::testnet());

try {
    $jwtToken = $webAuth->jwtToken($contractAccountId, [$signerKeyPair]);

} catch (ContractChallengeValidationErrorInvalidContractAddress $e) {
    // Server's contract address doesn't match stellar.toml
    echo "Security error - contract address mismatch\n";

} catch (ContractChallengeValidationErrorSubInvocationsFound $e) {
    // Challenge contains unauthorized sub-invocations
    echo "Security error - sub-invocations detected\n";

} catch (ContractChallengeValidationErrorInvalidServerSignature $e) {
    // Server's signature is invalid
    echo "Security error - invalid server signature\n";

} catch (ContractChallengeValidationErrorMissingServerEntry $e) {
    // No authorization entry for server account
    echo "Invalid challenge - missing server entry\n";

} catch (ContractChallengeValidationErrorMissingClientEntry $e) {
    // No authorization entry for client account
    echo "Invalid challenge - missing client entry\n";

} catch (ContractChallengeRequestErrorResponse $e) {
    // Server returned an error for challenge request
    echo "Challenge request failed: " . $e->getMessage() . "\n";

} catch (SubmitContractChallengeErrorResponseException $e) {
    // Server returned an error when submitting signed challenge
    // Common cause: signer not registered in contract's __check_auth
    echo "Authentication failed: " . $e->getMessage() . "\n";

} catch (Exception $e) {
    echo "Unexpected error: " . $e->getMessage() . "\n";
}
```

## Security Considerations

1. **Contract Address Validation**: The SDK verifies that `contract_address` in all authorization entries matches the `WEB_AUTH_CONTRACT_ID` from stellar.toml

2. **No Sub-Invocations**: The SDK rejects challenges containing sub-invocations, which could authorize unintended operations

3. **Server Signature Verification**: The SDK verifies the server has signed the challenge with the key from stellar.toml

4. **Function Name Validation**: Only `web_auth_verify` function is accepted

5. **Signature Expiration**: The SDK automatically sets tight expiration windows (current ledger + 10) to minimize replay attack windows

6. **Nonce Consistency**: The SDK verifies the nonce is consistent across all authorization entries

7. **Network Passphrase**: When provided by the server, the SDK validates the network passphrase matches

8. **JWT Storage**: Store JWT tokens securely. Never expose them in logs, URLs, or insecure storage

## Using JWT Tokens

Once authenticated, use the JWT token with other SEP services:

```php
<?php

use GuzzleHttp\Client;

$jwtToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...";
$client = new Client();

// Example: SEP-12 KYC request
$response = $client->get('https://anchor.example.com/kyc/customer', [
    'headers' => [
        'Authorization' => 'Bearer ' . $jwtToken
    ]
]);

$customerInfo = json_decode($response->getBody(), true);
```

## Network Support

```php
<?php

use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\WebAuthForContracts\WebAuthForContracts;

// Testnet
$webAuth = WebAuthForContracts::fromDomain("testnet.anchor.com", Network::testnet());

// Public network (mainnet)
$webAuth = WebAuthForContracts::fromDomain("anchor.com", Network::public());
```

## Reference Contracts

For SEP-45 authentication, the client contract must implement `__check_auth` to define authorization rules. The Stellar Anchor Platform provides a reference implementation:

- [Account Contract](https://github.com/stellar/anchor-platform/tree/main/soroban/contracts/account) - A sample client contract that implements `__check_auth` with Ed25519 signature verification. This contract stores authorized signers in persistent storage and validates signatures during authentication.

## Reference

- [SEP-45 Specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0045.md)
- [Stellar PHP SDK](https://github.com/Soneso/stellar-php-sdk)
- [SEP-10 (for traditional accounts)](sep-0010-webauth.md)
