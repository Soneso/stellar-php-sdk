# Quick Start Guide

Get your first Stellar transaction running in 15 minutes. This guide covers the essentials to start using the PHP SDK.

## What You'll Build

By the end of this guide, you'll:
- Generate a Stellar keypair (wallet)
- Fund an account on testnet
- Send your first payment transaction

## Installation

Install the SDK via Composer:

```bash
composer require soneso/stellar-php-sdk
```

**Requirements:** PHP 8.0 or higher (with `bcmath` and `gmp` extensions). See [Getting Started](getting-started.md) for full requirements.

## Your First KeyPair

Generate a random Stellar wallet:

```php
<?php
require 'vendor/autoload.php';

use Soneso\StellarSDK\Crypto\KeyPair;

// Generate a new random keypair
$keyPair = KeyPair::random();

echo "Account ID: " . $keyPair->getAccountId() . PHP_EOL;
echo "Secret Seed: " . $keyPair->getSecretSeed() . PHP_EOL;

// Example output:
// Account ID: GCFXHS4GXL6BVUCXBWXGTITROWLVYXQKQLF4YH5O5JT3YZXCYPAFBJZB
// Secret Seed: SAV76USXIJOBMEQXPANUOQM6F5LIOTLPDIDVRJBFFE2MDJXG24TAPUU7
```

**Keep the secret seed safe** — it controls your account!

## Creating Accounts

New Stellar accounts need at least 1 XLM to exist. On testnet, FriendBot gives you 10,000 free test XLM:

```php
<?php
require 'vendor/autoload.php';

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Util\FriendBot;

// Generate a new keypair
$keyPair = KeyPair::random();

// Fund on testnet (10,000 test XLM)
$funded = FriendBot::fundTestAccount($keyPair->getAccountId());

if ($funded) {
    echo "Account funded: " . $keyPair->getAccountId() . PHP_EOL;
}
```

> **Public network:** FriendBot only works on testnet. On the public network, you need an existing funded account to create new accounts using a `CreateAccountOperation`. See [Getting Started](getting-started.md#create-account-on-public-network) for details.

## Your First Transaction

Send a payment on the Stellar testnet:

```php
<?php
require 'vendor/autoload.php';

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

// Connect to testnet
$sdk = StellarSDK::getTestNetInstance();

// Your funded account (replace with your secret seed)
$senderKeyPair = KeyPair::fromSeed("SXXX...");
$destinationId = "GYYY..."; // Recipient address

// Load current account state from network
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

// Build payment operation
$paymentOp = (new PaymentOperationBuilder(
    $destinationId,
    Asset::native(),
    "10" // Amount in XLM
))->build();

// Build and sign transaction
$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->build();

$transaction->sign($senderKeyPair, Network::testnet());

// Submit to network
$response = $sdk->submitTransaction($transaction);

if ($response->isSuccessful()) {
    echo "Payment sent! Hash: " . $response->getHash() . PHP_EOL;
}
```

## Complete Example

Here's everything together — two accounts, one payment:

```php
<?php
require 'vendor/autoload.php';

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;

// 1. Generate two keypairs
$alice = KeyPair::random();
$bob = KeyPair::random();

echo "Alice: " . $alice->getAccountId() . PHP_EOL;
echo "Bob: " . $bob->getAccountId() . PHP_EOL;

// 2. Fund both accounts on testnet
FriendBot::fundTestAccount($alice->getAccountId());
FriendBot::fundTestAccount($bob->getAccountId());

echo "Accounts funded!" . PHP_EOL;

// 3. Connect to testnet
$sdk = StellarSDK::getTestNetInstance();

// 4. Load Alice's account
$aliceAccount = $sdk->requestAccount($alice->getAccountId());

// 5. Build payment: Alice sends 100 XLM to Bob
$paymentOp = (new PaymentOperationBuilder(
    $bob->getAccountId(),
    Asset::native(),
    "100"
))->build();

$transaction = (new TransactionBuilder($aliceAccount))
    ->addOperation($paymentOp)
    ->build();

// 6. Sign with Alice's key
$transaction->sign($alice, Network::testnet());

// 7. Submit to network
$response = $sdk->submitTransaction($transaction);

if ($response->isSuccessful()) {
    echo "Payment successful! Transaction: " . $response->getHash() . PHP_EOL;
} else {
    echo "Payment failed." . PHP_EOL;
}

// 8. Check Bob's new balance
$bobAccount = $sdk->requestAccount($bob->getAccountId());
foreach ($bobAccount->getBalances() as $balance) {
    if ($balance->getAssetType() === Asset::TYPE_NATIVE) {
        echo "Bob's balance: " . $balance->getBalance() . " XLM" . PHP_EOL;
    }
}
```

Run this script and you'll see Bob receive 100 XLM from Alice.

## Next Steps

You've created wallets and sent your first Stellar payment.

**Learn more:**
- **[Getting Started Guide](getting-started.md)** — Installation details, error handling, best practices
- **[SDK Usage](sdk-usage.md)** — All SDK features organized by use case
- **[Soroban Guide](soroban.md)** — Smart contract development
- **[SEP Protocols](sep/README.md)** — Stellar Ecosystem Proposals (authentication, deposits, KYC)

**Testnet vs Public Net:**
This guide uses testnet. For production, replace:
- `StellarSDK::getTestNetInstance()` → `StellarSDK::getPublicNetInstance()`
- `Network::testnet()` → `Network::public()`

---

**Navigation:** [← Documentation Home](README.md) | [Getting Started →](getting-started.md)
