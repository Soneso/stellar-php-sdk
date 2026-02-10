# Getting Started Guide

**Looking for a quick start? See [Quick Start](quick-start.md) to get running in 15 minutes.**

This guide covers the fundamentals of the Stellar PHP SDK.

## Table of Contents

- [Installation](#installation)
- [Basic Concepts](#basic-concepts)
- [KeyPair Management](#keypair-management)
- [Account Operations](#account-operations)
- [Transaction Building](#transaction-building)
- [Connecting to Networks](#connecting-to-networks)
- [Soroban RPC](#soroban-rpc)
- [Error Handling](#error-handling)
- [Best Practices](#best-practices)
- [Next Steps](#next-steps)

## Installation

Install via Composer:

```bash
composer require soneso/stellar-php-sdk
```

**Requirements:** PHP 8.0+, ext-bcmath, ext-gmp. **Optional:** ext-pcntl (Unix only, used for process forking in integration tests).

## Basic Concepts

### Networks

Stellar has multiple networks with unique passphrases:

```php
<?php

use Soneso\StellarSDK\Network;

$network = Network::testnet();   // Development (free test XLM via Friendbot)
$network = Network::public();    // Production (real assets)
$network = Network::futurenet(); // Upcoming protocol features
```

### Accounts

Every Stellar account has:
- **Account ID** (public key): Starts with `G`. Safe to share.
- **Secret Seed** (private key): Starts with `S`. Keep secret!

An account must hold at least 1 XLM to exist (the base reserve).

### Assets

Stellar supports two types of assets:
- **Native (XLM):** The built-in currency used for fees and account reserves.
- **Issued assets:** Tokens created by any account (the "issuer"). To hold an issued asset, you must first establish a trustline to the issuer.

```php
<?php

use Soneso\StellarSDK\Asset;

// Native XLM
$xlm = Asset::native();

// Issued asset (code + issuer account)
$usdc = Asset::createNonNativeAsset("USDC", "GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN");
```

### Operations and Transactions

A **transaction** groups one or more **operations** that execute atomically. Common operations:

- `CreateAccountOperation` — Create a new account
- `PaymentOperation` — Send assets
- `ChangeTrustOperation` — Establish a trustline
- `ManageSellOfferOperation` — Place a DEX order

## KeyPair Management

Manage cryptographic keys for signing transactions and identifying accounts.

### Generate a Random KeyPair

Create a new wallet with a random keypair. The account ID is your public address; the secret seed is your private key for signing transactions.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;

$keyPair = KeyPair::random();

$accountId = $keyPair->getAccountId();   // GCFXHS4GXL6B... (public)
$secretSeed = $keyPair->getSecretSeed(); // SAV76USXIJOB... (private)
```

### Import from Secret Seed

If you already have a secret seed (from a backup or another wallet), you can restore the full keypair. This lets you sign transactions.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;

// Restore keypair from seed (can sign transactions)
$keyPair = KeyPair::fromSeed("SDJHRQF4GCMIIKAAAQ6IHY42X73FQFLHUULAPSKKD4DFDM7UXWWCRHBE");
```

### Import from Account ID

You can create a keypair from just an account ID (public key). This is useful for verifying signatures or specifying destinations, but you can't sign transactions without the secret seed.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;

// Public key only (cannot sign)
$keyPair = KeyPair::fromAccountId("GCZHXL5HXQX5ABDM26LHYRCQZ5OJFHLOPLZX47WEBP3V2PF5AVFK2A5D");
```

### Mnemonic Phrases (SEP-5)

For wallet backup and recovery. The SDK supports 12, 15, 18, 21, or 24 word phrases:

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

// Generate mnemonic — choose your preferred length:
$mnemonic = Mnemonic::generate24WordsMnemonic();  // 24 words (recommended)
// or: $mnemonic = Mnemonic::generate12WordsMnemonic();  // 12 words

$words = implode(" ", $mnemonic->words);
// Store these words securely — they control all derived accounts

// Derive multiple accounts from one mnemonic
$keyPair0 = KeyPair::fromMnemonic($mnemonic, 0); // First account
$keyPair1 = KeyPair::fromMnemonic($mnemonic, 1); // Second account

// Restore from existing words
$words = "your twelve or twenty four word phrase goes here ...";
$mnemonic = Mnemonic::mnemonicFromWords($words);
$keyPair = KeyPair::fromMnemonic($mnemonic, 0);
```

## Account Operations

Create accounts, fund them, and query their data from the network.

### Fund on Testnet

On testnet, FriendBot gives you 10,000 free test XLM to experiment with. This is the easiest way to get started.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Util\FriendBot;

$keyPair = KeyPair::random();
$funded = FriendBot::fundTestAccount($keyPair->getAccountId());
```

### Create Account on Public Network

On the public network, there's no FriendBot. You need an existing funded account to create new accounts using the `CreateAccountOperation`. The new account receives a starting balance from the source account.

```php
<?php

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;

$sdk = StellarSDK::getPublicNetInstance();

$sourceKeyPair = KeyPair::fromSeed("SAPS66IJDXUSFDSDKIHR4LN6YPXIGCM5FBZ7GE66FDKFJRYJGFW7ZHYF");
$newKeyPair = KeyPair::random();

// Source account must already exist and have enough XLM for the new account's starting balance + fees
$sourceAccount = $sdk->requestAccount($sourceKeyPair->getAccountId());

$createOp = (new CreateAccountOperationBuilder(
    $newKeyPair->getAccountId(),
    "10" // Starting balance in XLM
))->build();

$transaction = (new TransactionBuilder($sourceAccount))
    ->addOperation($createOp)
    ->build();

$transaction->sign($sourceKeyPair, Network::public());
$response = $sdk->submitTransaction($transaction);

if ($response->isSuccessful()) {
    echo "Account created: " . $newKeyPair->getAccountId() . "\n";
}
```

### Query Account Data

Load an account from the network to check its balances, sequence number, and signers. Always verify an account exists before sending payments to it.

```php
<?php

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;

$sdk = StellarSDK::getTestNetInstance();
$accountId = "GCQHNQR2VM5OPXSTWZSF7ISDLE5XZRF73LNU6EOZXFQG2IJFU4WB7VFY";

// Check if account exists
if (!$sdk->accountExists($accountId)) {
    echo "Account not found\n";
    return;
}

$account = $sdk->requestAccount($accountId);

echo "Sequence: " . $account->getSequenceNumber() . "\n";

// List balances
foreach ($account->getBalances() as $balance) {
    if ($balance->getAssetType() === Asset::TYPE_NATIVE) {
        echo "XLM: " . $balance->getBalance() . "\n";
    } else {
        echo $balance->getAssetCode() . ": " . $balance->getBalance() . "\n";
    }
}

// List signers
foreach ($account->getSigners() as $signer) {
    echo "Signer: " . $signer->getKey() . " (weight: " . $signer->getWeight() . ")\n";
}
```

## Transaction Building

Construct transactions by adding operations, setting fees, and preparing for submission.

### Builder Pattern

Transactions are built using a fluent builder pattern:

```php
<?php

use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Memo;

// $sourceAccount loaded via $sdk->requestAccount(...)
// $operation1, $operation2 built via operation builders (see below)

$transaction = (new TransactionBuilder($sourceAccount))
    ->addOperation($operation1)
    ->addOperation($operation2)
    ->addMemo(Memo::text("Payment reference"))
    ->setMaxOperationFee(200) // 200 stroops per operation
    ->build();
```

### Building Operations

Each operation type has its own builder class. Build the operations first, then add them to the transaction. Operations execute in order.

```php
<?php

use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\Asset;

// Build operations
$paymentOp = (new PaymentOperationBuilder(
    "GDESTINATION...",
    Asset::native(),
    "100.50"
))->build();

$trustOp = (new ChangeTrustOperationBuilder(
    Asset::createNonNativeAsset("USD", "GISSUER..."),
    "10000"
))->build();

// Add operations to transaction
$transaction = (new TransactionBuilder($sourceAccount))
    ->addOperation($trustOp)    // First: establish trustline
    ->addOperation($paymentOp)  // Then: send payment
    ->build();
```

### Signing and Submitting

Transactions need a valid signature before the network accepts them. The signature proves the source account authorized the transaction. Use the correct network passphrase when signing—testnet and public have different passphrases, and a mismatch causes the transaction to fail.

```php
<?php

use Soneso\StellarSDK\Network;

// After building a transaction, sign it with the source account's keypair
// Use the correct network — testnet and public have different passphrases!
$transaction->sign($sourceKeyPair, Network::testnet());

// Multi-sig accounts: add signatures from all required signers
// $transaction->sign($keyPairA, Network::testnet());
// $transaction->sign($keyPairB, Network::testnet());

// Submit to the network
$response = $sdk->submitTransaction($transaction);

if ($response->isSuccessful()) {
    echo "Hash: " . $response->getHash() . "\n";
    echo "Ledger: " . $response->getLedger() . "\n";
}
```

### Complete Payment Example

Here's a full example that sends 100 XLM on testnet. It loads the sender's account, builds a payment, signs it, and submits to the network.

```php
<?php

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Memo;

$sdk = StellarSDK::getTestNetInstance();

$senderKeyPair = KeyPair::fromSeed("SA52PD5FN425CUONRMMX2CY5HB6I473A5OYNIVU67INROUZ6W4SPHXZB");
$destination = "GCRFFUKMUWWBRIA6ABRDFL5NKO6CKDB2IOX7MOS2TRLXNXQD255Z2MYG";

$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

$paymentOp = (new PaymentOperationBuilder($destination, Asset::native(), "100"))->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->addMemo(Memo::text("Coffee payment"))
    ->build();

$transaction->sign($senderKeyPair, Network::testnet());
$response = $sdk->submitTransaction($transaction);

if ($response->isSuccessful()) {
    echo "Payment sent! Hash: " . $response->getHash() . "\n";
}
```

## Connecting to Networks

The SDK connects to Horizon servers to query account data and submit transactions. Use testnet for development, public network for production.

```php
<?php

use Soneso\StellarSDK\StellarSDK;

// Testnet (https://horizon-testnet.stellar.org)
$sdk = StellarSDK::getTestNetInstance();

// Public network (https://horizon.stellar.org)
$sdk = StellarSDK::getPublicNetInstance();

// Custom Horizon server
$sdk = new StellarSDK("https://horizon.your-company.com");
```

## Soroban RPC

Soroban is Stellar's smart contract platform. To interact with smart contracts, you connect to a Soroban RPC server instead of Horizon.

### Connecting to Soroban RPC

Create a `SorobanServer` instance to interact with the Soroban RPC endpoint.

```php
<?php

use Soneso\StellarSDK\Soroban\SorobanServer;

// Testnet
$server = new SorobanServer("https://soroban-testnet.stellar.org");

// Mainnet
$server = new SorobanServer("https://soroban.stellar.org");
```

### Health Check

Check if the Soroban RPC server is running and see which ledger range it has available.

```php
<?php

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\Responses\GetHealthResponse;

$server = new SorobanServer("https://soroban-testnet.stellar.org");

$health = $server->getHealth();

if ($health->getStatus() === GetHealthResponse::HEALTHY) {
    echo "Server is healthy\n";
    echo "Latest ledger: " . $health->getLatestLedger() . "\n";
    echo "Oldest ledger: " . $health->getOldestLedger() . "\n";
}
```

### Latest Ledger Info

Get the current ledger sequence and protocol version. Useful for checking network status.

```php
<?php

use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer("https://soroban-testnet.stellar.org");

$ledger = $server->getLatestLedger();

echo "Ledger sequence: " . $ledger->getSequence() . "\n";
echo "Protocol version: " . $ledger->getProtocolVersion() . "\n";
```

### Smart Contract Interaction

For deploying contracts, invoking functions, and handling Soroban transactions, see the [Soroban Guide](soroban.md).

## Error Handling

### Horizon Request Exceptions

Network requests can fail for many reasons — invalid account IDs, network issues, or server errors. Catch `HorizonRequestException` to handle these gracefully.

```php
<?php

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

$sdk = StellarSDK::getTestNetInstance();

try {
    $account = $sdk->requestAccount("GINVALIDACCOUNTID");
} catch (HorizonRequestException $e) {
    echo "HTTP Status: " . $e->getStatusCode() . "\n";
    echo "Error: " . $e->getMessage() . "\n";
    
    $errorResponse = $e->getHorizonErrorResponse();
    if ($errorResponse) {
        echo "Detail: " . $errorResponse->getDetail() . "\n";
    }
}
```

### Transaction Failures

When a transaction fails, the error response contains result codes explaining what went wrong — both at the transaction level and for each operation.

```php
<?php

use Soneso\StellarSDK\Exceptions\HorizonRequestException;

try {
    $response = $sdk->submitTransaction($transaction);
    if ($response->isSuccessful()) {
        echo "Success!\n";
    }
} catch (HorizonRequestException $e) {
    $errorResponse = $e->getHorizonErrorResponse();
    if ($errorResponse) {
        $extras = $errorResponse->getExtras();
        if ($extras) {
            echo "Transaction: " . ($extras->getResultCodesTransaction() ?? 'unknown') . "\n";
            foreach ($extras->getResultCodesOperation() ?? [] as $i => $opCode) {
                echo "Operation $i: $opCode\n";
            }
        }
    }
}
```

### Common Error Codes

| Code | Meaning |
|------|---------|
| `tx_bad_seq` | Wrong sequence number. Reload account and retry. |
| `tx_insufficient_fee` | Fee too low. Increase `setMaxOperationFee()`. |
| `tx_insufficient_balance` | Not enough XLM for operation + fees + reserves. |
| `op_underfunded` | Source lacks funds for payment amount. |
| `op_no_trust` | Destination lacks trustline for asset. |
| `op_line_full` | Destination trustline limit exceeded. |
| `op_no_destination` | Destination account doesn't exist. |

## Best Practices

**1. Never expose secret seeds**
```php
// Bad
echo "Error with account: " . $keyPair->getSecretSeed();

// Good  
echo "Error with account: " . $keyPair->getAccountId();
```

**2. Use testnet for development** — Always test against testnet first.

**3. Set appropriate fees**
```php
<?php

$feeStats = $sdk->requestFeeStats();
$recommendedFee = $feeStats->getLastLedgerBaseFee();
```

**4. Handle errors gracefully** — Wrap network operations in try-catch.

**5. Verify destination exists** — Before payments, check if account exists. If not, use `CreateAccountOperation`.

**6. Use memos for exchanges** — Many exchanges require a memo to credit your account.

## Next Steps

- **[Quick Start](quick-start.md)** — First transaction in 15 minutes
- **[SDK Usage](sdk-usage.md)** — All operations, queries, and patterns
- **[SEP Protocols](sep/README.md)** — Authentication, deposits, cross-border payments
- **[Soroban Guide](soroban.md)** — Smart contract interaction

---

**Navigation**: [← Quick Start](quick-start.md) | [SDK Usage →](sdk-usage.md)
