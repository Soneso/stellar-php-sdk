# SDK Usage Guide

This guide covers SDK features organized by use case. For detailed method signatures, see the [PHPDoc API Reference](https://soneso.github.io/stellar-php-sdk/).

## Table of Contents

- [Keypairs & Accounts](#keypairs--accounts)
- [Building Transactions](#building-transactions)
- [Operations](#operations)
- [Querying Horizon Data](#querying-horizon-data)
- [Streaming (SSE)](#streaming-sse)
- [Network Communication](#network-communication)
- [Assets](#assets)
- [Soroban (Smart Contracts)](#soroban-smart-contracts)

---

## Keypairs & Accounts

### Creating Keypairs

Every Stellar account has a keypair: a public key (the account ID, starts with G) and a secret seed (starts with S). The secret seed signs transactions; keep it secure and never share it.

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;

// Generate new random keypair
$keyPair = KeyPair::random();
echo $keyPair->getAccountId();   // G... public key
echo $keyPair->getSecretSeed();  // S... secret seed

// Create from existing secret seed
$keyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34JFD6XVEAEPTBED53FETV");

// Create public-key-only keypair (cannot sign)
$publicOnly = KeyPair::fromAccountId("GABC123...");
```

### Loading an Account

Load an account from the network to check its balances, sequence number, and other data. The sequence number is required when building transactions.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

// Load account data from network
$account = $sdk->requestAccount("GABC123...");
echo "Sequence: " . $account->getSequenceNumber();

// Check balances
foreach ($account->getBalances() as $balance) {
    if ($balance->getAssetType() === 'native') {
        echo "XLM: " . $balance->getBalance();
    } else {
        echo $balance->getAssetCode() . ": " . $balance->getBalance();
    }
}

// Check if account exists
if ($sdk->accountExists("GABC123...")) {
    echo "Account exists";
}
```

### Funding Testnet Accounts

FriendBot is a testnet service that funds new accounts with 10,000 test XLM. Only works on testnet; on mainnet you need an existing funded account to create new ones.

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Util\FriendBot;

$keyPair = KeyPair::random();
FriendBot::fundTestAccount($keyPair->getAccountId());
```

### HD Wallets (SEP-5)

Derive multiple Stellar accounts from a single mnemonic phrase. Follows BIP-39 and SLIP-0010 standards, so the same phrase always produces the same accounts.

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

// Generate 24-word mnemonic
$mnemonic = Mnemonic::generate24WordsMnemonic();
echo implode(" ", $mnemonic->words);

// Restore from existing words
$mnemonic = Mnemonic::mnemonicFromWords("cable spray genius state float ...");

// Derive keypairs: m/44'/148'/{index}'
$account0 = KeyPair::fromMnemonic($mnemonic, 0);
$account1 = KeyPair::fromMnemonic($mnemonic, 1);
```

With an optional BIP-39 passphrase, the same mnemonic produces completely different accounts. The passphrase acts as a second factor: someone with only the mnemonic words can't access these accounts.

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\Derivation\Mnemonic;

// Restore mnemonic from existing words
$mnemonic = Mnemonic::mnemonicFromWords("cable spray genius state float ...");

// Derive with passphrase - produces completely different accounts than without
$account0 = KeyPair::fromMnemonic($mnemonic, 0, "my-secret-passphrase");
$account1 = KeyPair::fromMnemonic($mnemonic, 1, "my-secret-passphrase");

// Without the exact passphrase, you get different (wrong) accounts
// Keep both the mnemonic AND the passphrase safe
```

### Muxed Accounts

Muxed accounts let multiple virtual users share one Stellar account. Useful for exchanges and payment processors that need to track many users without creating separate accounts for each. The muxed address (M...) encodes both the base account and a 64-bit user ID.

```php
<?php
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\MuxedAccount;
use Soneso\StellarSDK\PaymentOperationBuilder;

// Create muxed account from base account + ID
$muxedAccount = new MuxedAccount("GABC...", 123456789);

echo $muxedAccount->getAccountId();       // M... address
echo $muxedAccount->getId();              // 123456789
echo $muxedAccount->getEd25519AccountId(); // GABC... (base account)

// Parse existing muxed address
$muxed = MuxedAccount::fromAccountId("MABC...");
echo $muxed->getAccountId();         // M... address
echo $muxed->getEd25519AccountId();  // Underlying G... address
echo $muxed->getId();                // The 64-bit ID

// Use in payments
$paymentOp = PaymentOperationBuilder::forMuxedDestinationAccount(
    $muxedAccount,
    Asset::native(),
    "100"
)->build();
```

### Connecting to Networks

Stellar has multiple networks, each with its own Horizon server and network passphrase. Use testnet for development, public for production. The network passphrase is used when signing transactions.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Network;

// Testnet (development and testing)
$sdk = StellarSDK::getTestNetInstance();
$network = Network::testnet();

// Public network (production)
$sdk = StellarSDK::getPublicNetInstance();
$network = Network::public();

// Futurenet (preview upcoming features)
$sdk = StellarSDK::getFutureNetInstance();
$network = Network::futurenet();

// Custom Horizon server
$sdk = new StellarSDK("https://my-horizon-server.example.com");
$network = new Network("Custom Network Passphrase");
```

---

## Building Transactions

Transactions group one or more operations together. All operations in a transaction execute atomically: either all succeed or all fail. Every transaction needs a source account (which pays the fee) and must be signed before submission.

### Simple Payments

The most common transaction: send XLM or another asset from one account to another.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();

$senderKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34JFD6XVEAEPTBED53FETV");
$sender = $sdk->requestAccount($senderKeyPair->getAccountId());

// Build payment
$paymentOp = (new PaymentOperationBuilder(
    "GDEST...",      // destination account
    Asset::native(), // asset (XLM)
    "100.50"         // amount
))->build();

// Build, sign, submit
$transaction = (new TransactionBuilder($sender))
    ->addOperation($paymentOp)
    ->build();

$transaction->sign($senderKeyPair, Network::testnet());
$response = $sdk->submitTransaction($transaction);

if ($response->isSuccessful()) {
    echo "Payment sent! Hash: " . $response->getHash();
}
```

### Multi-Operation Transactions

Bundle multiple operations into one transaction. This example creates an account, sets up a trustline, and sends an initial payment, all in one atomic transaction. If any operation fails, the entire transaction is rolled back.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;

$sdk = StellarSDK::getTestNetInstance();

$funderKeyPair = KeyPair::fromSeed("SFUNDER...");
$newAccountKeyPair = KeyPair::random();
$newAccountId = $newAccountKeyPair->getAccountId();

$funder = $sdk->requestAccount($funderKeyPair->getAccountId());

$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

// 1. Create the new account
$createAccountOp = (new CreateAccountOperationBuilder(
    $newAccountId, // destination
    "5"            // starting balance in XLM
))->build();

// 2. Establish trustline for USD
// The new account must be the source (not the funder) because trustlines
// are created by the account that wants to hold the asset
$trustlineOp = (new ChangeTrustOperationBuilder(
    $usdAsset, // asset to trust
    "10000"    // limit
))
    ->setSourceAccount($newAccountId)
    ->build();

// 3. Send initial USD to new account
$paymentOp = (new PaymentOperationBuilder(
    $newAccountId, // destination
    $usdAsset,     // asset
    "100"          // amount
))->build();

// Build transaction with all operations
$transaction = (new TransactionBuilder($funder))
    ->addOperation($createAccountOp)
    ->addOperation($trustlineOp)
    ->addOperation($paymentOp)
    ->build();

// Both accounts must sign:
// - Funder: transaction source (pays fees) + creates account + sends payment
// - New account: source of the trustline operation
$transaction->sign($funderKeyPair, Network::testnet());
$transaction->sign($newAccountKeyPair, Network::testnet());

// Submit to network
$sdk->submitTransaction($transaction);
```

### Memos, Time Bounds, and Fees

Memos attach data to transactions (payment references, user IDs). Time bounds limit when a transaction is valid, preventing old signed transactions from being submitted later. Fees are paid in stroops (1 XLM = 10,000,000 stroops).

```php
<?php
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\TransactionBuilder;

// Add memo
$transaction = (new TransactionBuilder($account))
    ->addOperation($operation)
    ->addMemo(Memo::text("Payment for invoice #1234"))
    ->build();

// Memo types: Memo::text(), Memo::id(), Memo::hash(), Memo::return()

// Time bounds (valid for next 5 minutes)
$timeBounds = new TimeBounds(new DateTime(), (new DateTime())->modify('+5 minutes'));
$transaction = (new TransactionBuilder($account))
    ->addOperation($operation)
    ->setTimeBounds($timeBounds)
    ->build();

// Custom fee (stroops per operation, default 100)
$transaction = (new TransactionBuilder($account))
    ->addOperation($operation)
    ->setMaxOperationFee(200)
    ->build();
```

### Fee Bump Transactions

Fee bump transactions let a different account pay the fee for an existing transaction. Useful when the source account of the inner transaction doesn't have enough XLM to cover fees, or when a service wants to pay fees on behalf of users.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\FeeBumpTransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();

// The user wants to send a payment but has no XLM for fees
$userKeyPair = KeyPair::fromSeed("SUSER...");
$userAccount = $sdk->requestAccount($userKeyPair->getAccountId());

// Build and sign the inner transaction (user signs their own transaction)
$innerTransaction = (new TransactionBuilder($userAccount))
    ->addOperation((new PaymentOperationBuilder(
        "GDEST1...",
        Asset::native(),
        "10"
    ))->build())
    ->addOperation((new PaymentOperationBuilder(
        "GDEST2...",
        Asset::native(),
        "20"
    ))->build())
    ->build();

$innerTransaction->sign($userKeyPair, Network::testnet());

// A service (fee payer) wraps the transaction and pays the fee
$feePayerKeyPair = KeyPair::fromSeed("SFEEPAYER...");

// Build fee bump transaction
// Base fee must be >= (inner tx base fee * number of operations) + 100
// Inner tx: 100 * 2 ops = 200, plus 100 for fee bump = 300 minimum
$feeBumpTx = (new FeeBumpTransactionBuilder($innerTransaction))
    ->setBaseFee(300)
    ->setFeeAccount($feePayerKeyPair->getAccountId())
    ->build();

// Only the fee payer signs the fee bump
$feeBumpTx->sign($feePayerKeyPair, Network::testnet());

// Submit the fee bump transaction
$sdk->submitTransaction($feeBumpTx);
```

---

## Operations

Operations are the individual actions within a transaction. Each operation type has its own builder class. Build the operation, then add it to a transaction.

### Payment Operations

Transfer XLM or custom assets between accounts.

```php
<?php
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;

// Native XLM payment
$paymentOp = (new PaymentOperationBuilder(
    "GDEST...",      // destination
    Asset::native(), // asset (XLM)
    "100"            // amount
))->build();

// Custom asset payment
$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");
$paymentOp = (new PaymentOperationBuilder(
    "GDEST...", // destination
    $usdAsset,  // asset
    "50.25"     // amount
))->build();
```

### Path Payment Operations

Path payments convert assets through the DEX during transfer. You send one asset and the recipient receives a different asset. Query Horizon for available paths, then choose the best one for your transaction.

First, query available paths to get the exchange route and expected amounts:

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\PathPaymentStrictSendOperationBuilder;
use Soneso\StellarSDK\PathPaymentStrictReceiveOperationBuilder;

$sdk = StellarSDK::getTestNetInstance();

$xlm = Asset::native();
$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

// Find paths: "If I send 100 XLM, how much USD will the recipient get?"
$pathsResponse = $sdk->findStrictSendPaths()
    ->forSourceAsset($xlm)
    ->forSourceAmount("100")
    ->forDestinationAssets([$usdAsset])
    ->execute();

// Find the path with the best destination amount
$paths = $pathsResponse->getPaths()->toArray();
if (!empty($paths)) {
    $bestPath = $paths[0];
    foreach ($paths as $p) {
        if ($p->getDestinationAmount() > $bestPath->getDestinationAmount()) {
            $bestPath = $p;
        }
    }
    $destMin = $bestPath->getDestinationAmount(); // expected USD amount
    $path = $bestPath->getPath();                 // intermediate assets
}
```

Then build the path payment operation:

```php
// Strict send: send exactly 100 XLM, receive at least $destMin USD
$pathPaymentOp = (new PathPaymentStrictSendOperationBuilder(
    $xlm,       // send asset
    "100",      // send amount (exact)
    "GDEST...", // destination
    $usdAsset,  // destination asset
    $destMin    // minimum amount to receive
))
    ->setPath($path) // intermediate assets from path query
    ->build();
```

For strict receive (recipient gets exact amount, you pay variable):

```php
// Find paths: "If recipient needs exactly 100 USD, how much XLM do I send?"
$pathsResponse = $sdk->findStrictReceivePaths()
    ->forSourceAccount("GSENDER...")
    ->forDestinationAsset($usdAsset)
    ->forDestinationAmount("100")
    ->execute();

// Find the path with the lowest source amount (least XLM to send)
$paths = $pathsResponse->getPaths()->toArray();
if (!empty($paths)) {
    $bestPath = $paths[0];
    foreach ($paths as $p) {
        if ($p->getSourceAmount() < $bestPath->getSourceAmount()) {
            $bestPath = $p;
        }
    }
    $sendMax = $bestPath->getSourceAmount(); // max XLM needed
    $path = $bestPath->getPath();
}

// Strict receive: receive exactly 100 USD, send at most $sendMax XLM
$pathPaymentOp = (new PathPaymentStrictReceiveOperationBuilder(
    $xlm,       // send asset
    $sendMax,   // maximum amount to send
    "GDEST...", // destination
    $usdAsset,  // destination asset
    "100"       // destination amount (exact)
))
    ->setPath($path)
    ->build();
```

### Account Operations

#### Create Account

Create a new account on the network. The source account funds the new account with a starting balance.

```php
<?php
use Soneso\StellarSDK\CreateAccountOperationBuilder;

$createOp = (new CreateAccountOperationBuilder(
    "GNEWACCOUNT...", // new account ID
    "10"              // starting balance in XLM (minimum ~1 XLM for base reserve)
))->build();
```

#### Merge Account

Close an account and transfer all its assets to another account. The merged account is removed from the ledger.

The account being merged is the operation's source account. If not set, it defaults to the transaction's source account.

The destination account must have trustlines for all non-XLM assets the account to be merged holds, otherwise the operation fails.

```php
<?php
use Soneso\StellarSDK\AccountMergeOperationBuilder;

// Merge the transaction's source account into destination
$mergeOp = (new AccountMergeOperationBuilder(
    "GDEST..." // destination receives all XLM and other assets
))->build();

// Or merge a different account (must also sign the transaction)
$mergeOp = (new AccountMergeOperationBuilder("GDEST..."))
    ->setSourceAccount("GACCOUNT_TO_MERGE...")
    ->build();
```

#### Manage Data

Store key-value data on your account (max 64 bytes per entry). Useful for app-specific metadata.

```php
<?php
use Soneso\StellarSDK\ManageDataOperationBuilder;

// Store a string value
$setDataOp = (new ManageDataOperationBuilder(
    "config",     // key (string)
    "production"  // value (max 64 bytes)
))->build();

// Store binary data (e.g., a hash)
$hash = hash('sha256', 'some data', true); // binary output
$setHashOp = (new ManageDataOperationBuilder(
    "data_hash",
    $hash
))->build();

// Delete an entry (set value to null)
$deleteDataOp = (new ManageDataOperationBuilder(
    "temp_key", // key to delete
    null        // null removes the entry
))->build();
```

#### Set Options

Configure account settings: home domain, thresholds, signers, and flags.

**Set Home Domain**

The home domain is used for SEP protocols like federation (SEP-2) and stellar.toml discovery.

```php
<?php
use Soneso\StellarSDK\SetOptionsOperationBuilder;

$setDomainOp = (new SetOptionsOperationBuilder())
    ->setHomeDomain("example.com")
    ->build();
```

**Configure Multi-Sig Thresholds**

Operations require signatures with combined weight >= the operation's threshold. Each operation type has a threshold level:

- **Low:** Allow Trust, Set Trustline Flags, Bump Sequence
- **Medium:** Payments, Create Account, Path Payments, Manage Offers, most other operations
- **High:** Account Merge, Set Options (when changing signers or thresholds)

```php
<?php
use Soneso\StellarSDK\SetOptionsOperationBuilder;

$setThresholdsOp = (new SetOptionsOperationBuilder())
    ->setMasterKeyWeight(10) // weight of the master key
    ->setLowThreshold(10)    // e.g., bump sequence
    ->setMediumThreshold(20) // e.g., payments
    ->setHighThreshold(30)   // e.g., account merge, adding signers
    ->build();
```

**Add or Remove Signers**

Add additional signers to create a multi-sig account. Each signer has a weight that contributes to meeting thresholds.

```php
<?php
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\Signer;
use Soneso\StellarSDK\Crypto\KeyPair;

// Add a signer with weight 10
$signerKey = Signer::ed25519PublicKey(KeyPair::fromAccountId("GSIGNER..."));
$addSignerOp = (new SetOptionsOperationBuilder())
    ->setSigner($signerKey, 10)
    ->build();

// Remove a signer (set weight to 0)
$removeSignerOp = (new SetOptionsOperationBuilder())
    ->setSigner($signerKey, 0)
    ->build();
```

**Set Account Flags**

Flags control asset issuance behavior. Typically set by asset issuers.

```php
<?php
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\AccountFlag;

// Enable authorization required and revocable (for regulated assets)
$setFlagsOp = (new SetOptionsOperationBuilder())
    ->setSetFlags(AccountFlag::AUTH_REQUIRED_FLAG | AccountFlag::AUTH_REVOCABLE_FLAG)
    ->build();

// Clear a flag
$clearFlagsOp = (new SetOptionsOperationBuilder())
    ->setClearFlags(AccountFlag::AUTH_REVOCABLE_FLAG)
    ->build();

// Available flags:
// AUTH_REQUIRED_FLAG (1)        - Trustlines must be authorized by issuer
// AUTH_REVOCABLE_FLAG (2)       - Issuer can revoke authorization
// AUTH_IMMUTABLE_FLAG (4)       - Flags can never be changed (irreversible!)
// AUTH_CLAWBACK_ENABLED_FLAG (8) - Issuer can clawback assets
```

#### Bump Sequence

Manually set the account's sequence number. Useful for invalidating pre-signed transactions that use older sequence numbers.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\BumpSequenceOperationBuilder;
use phpseclib3\Math\BigInteger;

$sdk = StellarSDK::getTestNetInstance();

// Get the current sequence number
$account = $sdk->requestAccount("GABC...");
$currentSequence = $account->getSequenceNumber();

// Bump to current + 100 (invalidates any pre-signed tx with sequence <= current + 100)
$bumpOp = (new BumpSequenceOperationBuilder(
    $currentSequence->add(new BigInteger(100))
))->build();
```

### Asset Operations

Before receiving a custom asset, an account must create a trustline for it. Trustlines specify which assets the account accepts and set optional limits.

#### Create Trustline

Create a trustline to allow your account to hold a custom asset. The limit specifies the maximum amount you're willing to hold. If omitted, the limit defaults to the maximum possible value (unlimited).

```php
<?php
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;

$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

// With a specific limit
$trustOp = (new ChangeTrustOperationBuilder(
    $usdAsset, // asset to trust
    "10000"    // limit (max amount you can hold)
))->build();

// Without limit (defaults to maximum possible value)
$trustOpUnlimited = (new ChangeTrustOperationBuilder($usdAsset))->build();
```

#### Modify Trustline Limit

Change the maximum amount of an asset your account can hold.

```php
<?php
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;

$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

// Increase or decrease the limit
$modifyTrustOp = (new ChangeTrustOperationBuilder(
    $usdAsset,
    "50000" // new limit
))->build();
```

#### Remove Trustline

Remove a trustline by setting the limit to zero. Your balance must be zero first.

```php
<?php
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;

$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

// Balance must be zero before removing
$removeTrustOp = (new ChangeTrustOperationBuilder(
    $usdAsset,
    "0" // zero limit removes the trustline
))->build();
```

#### Authorize Trustline (Issuer Only)

If an asset has the AUTH_REQUIRED flag, the issuer must authorize trustlines before holders can receive the asset. Use `SetTrustLineFlagsOperationBuilder` to authorize or revoke.

```php
<?php
use Soneso\StellarSDK\SetTrustLineFlagsOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\Xdr\XdrTrustLineFlags;

$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

// Authorize a trustline (allow holder to receive the asset)
$authorizeOp = (new SetTrustLineFlagsOperationBuilder(
    "GTRUSTOR...",                      // account to authorize
    $usdAsset,                          // asset
    0,                                  // flags to clear
    XdrTrustLineFlags::AUTHORIZED_FLAG  // flags to set
))->build();

// Revoke authorization (holder can no longer receive, but can send)
$revokeOp = (new SetTrustLineFlagsOperationBuilder(
    "GTRUSTOR...",
    $usdAsset,
    XdrTrustLineFlags::AUTHORIZED_FLAG, // flags to clear
    0                                   // flags to set
))->build();
```

### Trading Operations

Place, update, or cancel offers on Stellar's built-in decentralized exchange (DEX).

#### Create Sell Offer

Sell a specific amount of an asset at a given price. You specify how much you want to sell.

```php
<?php
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\ManageSellOfferOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;

$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

// Sell 100 XLM at 0.20 USD per XLM (receive 20 USD total)
$sellOp = (new ManageSellOfferOperationBuilder(
    Asset::native(), // selling asset
    $usdAsset,       // buying asset
    "100",           // amount to sell
    "0.20"           // price (buying asset per selling asset)
))->build();
```

#### Create Buy Offer

Buy a specific amount of an asset at a given price. You specify how much you want to receive.

```php
<?php
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\ManageBuyOfferOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;

$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

// Buy 50 USD at 0.20 USD per XLM (spend 250 XLM total)
$buyOp = (new ManageBuyOfferOperationBuilder(
    Asset::native(), // selling asset (what you pay with)
    $usdAsset,       // buying asset (what you receive)
    "50",            // amount to buy
    "0.20"           // price (buying asset per selling asset)
))->build();
```

#### Update Offer

Modify an existing offer by providing its offer ID. You can change the amount or price.

```php
<?php
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\ManageSellOfferOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;

$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

// Update offer 12345: change amount to 150 XLM at new price 0.22 USD
$updateOp = (new ManageSellOfferOperationBuilder(
    Asset::native(),
    $usdAsset,
    "150",  // new amount
    "0.22"  // new price
))
    ->setOfferId(12345) // existing offer to update
    ->build();
```

**How to get the offer ID**

You can get the offer ID from the transaction response after creating an offer:

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Operations\ManageSellOfferOperationResponse;

$sdk = StellarSDK::getTestNetInstance();

// After submitting a transaction that creates an offer
$response = $sdk->submitTransaction($transaction);

// Query the operations from the transaction to get the offer ID
$operationsPage = $sdk->operations()->forTransaction($response->getHash())->execute();
foreach ($operationsPage->getOperations()->toArray() as $op) {
    if ($op instanceof ManageSellOfferOperationResponse) {
        $offerId = $op->getOfferId();
        echo "Created offer ID: " . $offerId . "\n";
    }
}
```

Or query your account's existing offers:

```php
<?php
// Get all offers for an account
$offersPage = $sdk->offers()->forAccount("GABC...")->execute();
foreach ($offersPage->getOffers()->toArray() as $offer) {
    echo "Offer ID: " . $offer->getId() . "\n";
    echo "Selling: " . $offer->getAmount() . " " . $offer->getSellingAsset() . "\n";
    echo "Price: " . $offer->getPrice() . "\n";
}
```

#### Cancel Offer

Cancel an existing offer by setting the amount to zero.

```php
<?php
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\ManageSellOfferOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;

$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

// Cancel offer 12345
$cancelOp = (new ManageSellOfferOperationBuilder(
    Asset::native(),
    $usdAsset,
    "0",    // zero amount cancels the offer
    "0.20"  // price doesn't matter when canceling
))
    ->setOfferId(12345)
    ->build();
```

#### Passive Sell Offer

A passive offer doesn't immediately match existing offers at the same price. Use it for market making when you want to provide liquidity without taking from the order book.

```php
<?php
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\CreatePassiveSellOfferOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\Price;

$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

// Passive offer: sell 100 XLM at 0.20 USD per XLM
// Won't match existing offers, waits for a counterparty
$passiveOp = (new CreatePassiveSellOfferOperationBuilder(
    Asset::native(),          // selling asset
    $usdAsset,                // buying asset
    "100",                    // amount to sell
    Price::fromString("0.20") // price
))->build();
```

### Claimable Balance Operations

Send funds that recipients claim later, with optional time-based conditions. Useful for escrow, scheduled payments, or sending to accounts that don't exist yet.

#### Create Claimable Balance

Lock funds that one or more claimants can claim. Each claimant has a predicate that defines when they can claim.

```php
<?php
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Claimant;
use Soneso\StellarSDK\CreateClaimableBalanceOperationBuilder;

// Create claimants (who can claim and under what conditions)
$claimant1 = new Claimant(
    "GCLAIMER1...",                    // claimant account
    Claimant::predicateUnconditional() // can claim anytime
);

$claimant2 = new Claimant(
    "GCLAIMER2...",
    Claimant::predicateBeforeAbsoluteTime(strtotime("+30 days")) // must claim within 30 days
);

// Create the claimable balance
$createOp = (new CreateClaimableBalanceOperationBuilder(
    [$claimant1, $claimant2], // list of claimants
    Asset::native(),          // asset
    "100"                     // amount
))->build();
```

#### Predicates

Predicates control when a claimant can claim. You can combine them for complex conditions.

```php
<?php
use Soneso\StellarSDK\Claimant;

// Unconditional: can claim anytime
$anytime = Claimant::predicateUnconditional();

// Before absolute time: must claim before this Unix timestamp
$before = Claimant::predicateBeforeAbsoluteTime(strtotime("+30 days"));

// Before relative time: must claim within X seconds of balance creation
$withinOneHour = Claimant::predicateBeforeRelativeTime(3600);

// NOT: inverts a predicate (e.g., can claim AFTER a time)
$afterOneDay = Claimant::predicateNot(
    Claimant::predicateBeforeRelativeTime(86400) // NOT "before 1 day" = "after 1 day"
);

// AND: both conditions must be true
// Example: can claim after 1 day AND before 30 days (a time window)
$timeWindow = Claimant::predicateAnd(
    Claimant::predicateNot(Claimant::predicateBeforeRelativeTime(86400)),     // after 1 day
    Claimant::predicateBeforeRelativeTime(86400 * 30)                          // before 30 days
);

// OR: either condition can be true
$eitherCondition = Claimant::predicateOr($anytime, $before);
```

#### Claim Balance

To claim a balance, you need its balance ID. Get it from the transaction response when created, or query claimable balances for your account.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\ClaimClaimableBalanceOperationBuilder;

$sdk = StellarSDK::getTestNetInstance();

// Find claimable balances you can claim
$balancesPage = $sdk->claimableBalances()
    ->forClaimant("GCLAIMER1...")
    ->execute();

foreach ($balancesPage->getClaimableBalances()->toArray() as $balance) {
    echo "Balance ID: " . $balance->getBalanceId() . "\n"; // hex string
    echo "Amount: " . $balance->getAmount() . "\n";
    echo "Asset: " . $balance->getAsset() . "\n";
}
```

Then claim it:

```php
<?php
use Soneso\StellarSDK\ClaimClaimableBalanceOperationBuilder;

// Claim the balance
// Accepts both hex format and strkey format (starts with "B")
// See sep/sep-23.md for more on strkey encoding
$balanceId = "00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072";
$claimOp = (new ClaimClaimableBalanceOperationBuilder($balanceId))->build();
```

### Liquidity Pool Operations

Provide liquidity to Stellar's automated market maker (AMM) pools and earn trading fees.

#### Pool Share Trustline

Before depositing to a liquidity pool, you need a trustline for the pool shares. Create a pool share asset from the two assets in the pool.

```php
<?php
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\AssetTypePoolShare;

$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

// Create pool share asset (assets must be in lexicographic order)
$poolShareAsset = new AssetTypePoolShare(Asset::native(), $usdAsset);

// Establish trustline for pool shares
$trustPoolOp = (new ChangeTrustOperationBuilder($poolShareAsset))->build();
```

#### Get Pool ID

Query the pool ID by the reserve assets, or find pools your account participates in.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;

$sdk = StellarSDK::getTestNetInstance();

$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

// Find pool by reserve assets
$poolsPage = $sdk->liquidityPools()
    ->forReserves("native", "USD:GISSUER...")
    ->execute();

foreach ($poolsPage->getLiquidityPools()->toArray() as $pool) {
    echo "Pool ID: " . $pool->getPoolId() . "\n";
    echo "Total shares: " . $pool->getTotalShares() . "\n";
}

// Or find pools your account participates in
$poolsPage = $sdk->liquidityPools()
    ->forAccount("GABC...")
    ->execute();
```

#### Deposit Liquidity

Add liquidity to a pool. You specify the maximum amounts of each asset to deposit and price bounds to protect against slippage.

```php
<?php
use Soneso\StellarSDK\Price;
use Soneso\StellarSDK\LiquidityPoolDepositOperationBuilder;

$depositOp = (new LiquidityPoolDepositOperationBuilder(
    "poolid123abc...",        // pool ID from query above
    "1000",                   // max amount of asset A (XLM)
    "500",                    // max amount of asset B (USD)
    Price::fromString("1.9"), // min price (A per B) - slippage protection
    Price::fromString("2.1")  // max price (A per B) - slippage protection
))->build();

// The actual amounts deposited depend on the current pool ratio
// Price bounds reject the transaction if the pool price moves outside your range
```

#### Withdraw Liquidity

Remove liquidity by burning pool shares. You receive both assets back proportionally.

```php
<?php
use Soneso\StellarSDK\LiquidityPoolWithdrawOperationBuilder;

$withdrawOp = (new LiquidityPoolWithdrawOperationBuilder(
    "poolid123abc...", // pool ID
    "100",             // amount of pool shares to burn
    "180",             // min amount of asset A to receive (slippage protection)
    "90"               // min amount of asset B to receive (slippage protection)
))->build();

// If you would receive less than the minimums, the transaction fails
```

### Sponsorship Operations

Sponsorship lets one account pay base reserves for another account's ledger entries. This enables user onboarding without requiring new users to hold XLM for reserves.

#### Sponsor Account Creation

Create a new account where the sponsor pays the base reserve. The new account can start with 0 XLM.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\BeginSponsoringFutureReservesOperationBuilder;
use Soneso\StellarSDK\EndSponsoringFutureReservesOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;

$sdk = StellarSDK::getTestNetInstance();

// Sponsor: existing funded account that will pay reserves
$sponsorKeyPair = KeyPair::fromSeed("SSPONSOR...");
$sponsorAccount = $sdk->requestAccount($sponsorKeyPair->getAccountId());

// New account to be sponsored
$newAccountKeyPair = KeyPair::random();
$newAccountId = $newAccountKeyPair->getAccountId();

$transaction = (new TransactionBuilder($sponsorAccount))
    // 1. Begin sponsoring - sponsor declares intent to pay reserves
    ->addOperation((new BeginSponsoringFutureReservesOperationBuilder($newAccountId))->build())
    // 2. Create account with 0 XLM (sponsor pays the reserve)
    ->addOperation((new CreateAccountOperationBuilder($newAccountId, "0"))->build())
    // 3. End sponsoring - new account must confirm (source = new account)
    ->addOperation(
        (new EndSponsoringFutureReservesOperationBuilder())
            ->setSourceAccount($newAccountId)
            ->build()
    )
    ->build();

// Both must sign:
// - Sponsor: authorizes paying reserves and funds the transaction
// - New account: confirms acceptance of sponsorship (required for EndSponsoring)
$transaction->sign($sponsorKeyPair, Network::testnet());
$transaction->sign($newAccountKeyPair, Network::testnet());

$sdk->submitTransaction($transaction);
```

#### Sponsor Trustline

Sponsor a trustline for an existing account. Useful when users want to hold an asset but don't have XLM for the trustline reserve.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\BeginSponsoringFutureReservesOperationBuilder;
use Soneso\StellarSDK\EndSponsoringFutureReservesOperationBuilder;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;

$sdk = StellarSDK::getTestNetInstance();

$sponsorKeyPair = KeyPair::fromSeed("SSPONSOR...");
$sponsorAccount = $sdk->requestAccount($sponsorKeyPair->getAccountId());

$userKeyPair = KeyPair::fromSeed("SUSER...");
$userId = $userKeyPair->getAccountId();

$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

$transaction = (new TransactionBuilder($sponsorAccount))
    ->addOperation((new BeginSponsoringFutureReservesOperationBuilder($userId))->build())
    ->addOperation(
        (new ChangeTrustOperationBuilder($usdAsset))
            ->setSourceAccount($userId) // user creates the trustline
            ->build()
    )
    ->addOperation(
        (new EndSponsoringFutureReservesOperationBuilder())
            ->setSourceAccount($userId)
            ->build()
    )
    ->build();

// Both sign
$transaction->sign($sponsorKeyPair, Network::testnet());
$transaction->sign($userKeyPair, Network::testnet());

$sdk->submitTransaction($transaction);
```

#### Revoke Sponsorship

Transfer the reserve responsibility back to the sponsored account. The operation fails if the account doesn't have enough XLM to cover its own reserves after revoking.

```php
<?php
use Soneso\StellarSDK\RevokeSponsorshipOperationBuilder;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;

// Revoke account sponsorship
$revokeAccountOp = (new RevokeSponsorshipOperationBuilder())
    ->revokeAccountSponsorship("GSPONSORED...")
    ->build();

// Revoke trustline sponsorship
$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");
$revokeTrustlineOp = (new RevokeSponsorshipOperationBuilder())
    ->revokeTrustlineSponsorship("GSPONSORED...", $usdAsset)
    ->build();

// Revoke data entry sponsorship
$revokeDataOp = (new RevokeSponsorshipOperationBuilder())
    ->revokeDataSponsorship("GSPONSORED...", "data_key")
    ->build();
```

---

## Querying Horizon Data

Horizon is the API server for Stellar. Query it for accounts, transactions, operations, and other network data. All query builders support `limit()`, `order()`, and `cursor()` for pagination (see [Pagination](#pagination) at the end of this section).

### Account Queries

Look up accounts by ID, signer, asset holdings, or sponsor.

#### Get Single Account

Fetch a specific account by its public key.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$account = $sdk->accounts()->account("GABC...");
echo "Sequence: " . $account->getSequenceNumber() . "\n";
echo "Subentry count: " . $account->getSubentryCount() . "\n";
```

#### Check if Account Exists

Check whether an account exists on the network before attempting operations. Useful for deciding between `CreateAccountOperation` (new account) vs `PaymentOperation` (existing account).

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

if ($sdk->accountExists("GABC...")) {
    echo "Account exists - use PaymentOperation\n";
} else {
    echo "Account does not exist - use CreateAccountOperation\n";
}
```

#### Query by Signer

Find all accounts that have a specific key as a signer. Useful for discovering accounts controlled by a key.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$accountsPage = $sdk->accounts()
    ->forSigner("GSIGNER...")
    ->limit(50)
    ->order("desc")
    ->execute();

foreach ($accountsPage->getAccounts()->toArray() as $account) {
    echo $account->getAccountId() . "\n";
}
```

#### Query by Asset

Find all accounts holding a specific asset. Useful for asset issuers to find their token holders.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;

$sdk = StellarSDK::getTestNetInstance();

$usdAsset = Asset::createNonNativeAsset("USD", "GISSUER...");
$accountsPage = $sdk->accounts()
    ->forAsset($usdAsset)
    ->execute();

foreach ($accountsPage->getAccounts()->toArray() as $account) {
    echo $account->getAccountId() . "\n";
}
```

#### Query by Sponsor

Find all accounts sponsored by a specific account.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$accountsPage = $sdk->accounts()
    ->forSponsor("GSPONSOR...")
    ->execute();

foreach ($accountsPage->getAccounts()->toArray() as $account) {
    echo $account->getAccountId() . "\n";
}
```

#### Get Account Data Entry

Retrieve a specific data entry stored on an account.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$dataValue = $sdk->accounts()->accountData("GABC...", "config");
echo "Value: " . $dataValue->getValue() . "\n";
```

### Transaction Queries

Fetch transactions by hash, account, ledger, or related resources.

#### Get Single Transaction

Fetch a specific transaction by its hash.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$tx = $sdk->transactions()->transaction("abc123hash...");
echo "Ledger: " . $tx->getLedger() . "\n";
echo "Fee paid: " . $tx->getFeeCharged() . "\n";
echo "Operation count: " . $tx->getOperationCount() . "\n";
```

#### Transactions for Account

Get all transactions involving a specific account (as source or in any operation).

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$txPage = $sdk->transactions()
    ->forAccount("GABC...")
    ->limit(20)
    ->order("desc")
    ->execute();

foreach ($txPage->getTransactions()->toArray() as $tx) {
    echo $tx->getHash() . "\n";
}
```

#### Include Failed Transactions

By default, only successful transactions are returned. Use `includeFailed(true)` to also get failed ones.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$txPage = $sdk->transactions()
    ->forAccount("GABC...")
    ->includeFailed(true)
    ->execute();

foreach ($txPage->getTransactions()->toArray() as $tx) {
    echo $tx->getHash() . " - " . ($tx->isSuccessful() ? "success" : "failed") . "\n";
}
```

#### Transactions by Related Resource

Find transactions related to a ledger, claimable balance, or liquidity pool.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

// Transactions in a specific ledger
$txPage = $sdk->transactions()
    ->forLedger("12345678")
    ->execute();

// Transactions affecting a claimable balance
$txPage = $sdk->transactions()
    ->forClaimableBalance("00000000abc...")
    ->execute();

// Transactions affecting a liquidity pool
$txPage = $sdk->transactions()
    ->forLiquidityPool("poolid...")
    ->execute();
```

### Operation Queries

Query operations by ID, account, transaction, or ledger.

#### Get Single Operation

Fetch a specific operation by its ID.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$op = $sdk->operations()->operation("123456789");
echo "Type: " . $op->getHumanReadableOperationType() . "\n";
echo "Transaction: " . $op->getTransactionHash() . "\n";
```

#### Operations for Account

Get all operations involving a specific account.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$opsPage = $sdk->operations()
    ->forAccount("GABC...")
    ->limit(50)
    ->order("desc")
    ->execute();

foreach ($opsPage->getOperations()->toArray() as $op) {
    echo $op->getOperationId() . ": " . $op->getHumanReadableOperationType() . "\n";
}
```

#### Operations in Transaction

Get all operations within a specific transaction.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$opsPage = $sdk->operations()
    ->forTransaction("txhash...")
    ->execute();

foreach ($opsPage->getOperations()->toArray() as $op) {
    echo $op->getHumanReadableOperationType() . "\n";
}
```

#### Handling Operation Types

Operations are returned as specific response types based on their kind. Use `instanceof` to handle each type appropriately.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\Responses\Operations\CreateAccountOperationResponse;
use Soneso\StellarSDK\Responses\Operations\ChangeTrustOperationResponse;
use Soneso\StellarSDK\Responses\Operations\ManageSellOfferOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PathPaymentStrictReceiveOperationResponse;

$sdk = StellarSDK::getTestNetInstance();

$opsPage = $sdk->operations()->forAccount("GABC...")->execute();

foreach ($opsPage->getOperations()->toArray() as $op) {
    if ($op instanceof PaymentOperationResponse) {
        echo "Payment: " . $op->getAmount() . " to " . $op->getTo() . "\n";
    } elseif ($op instanceof CreateAccountOperationResponse) {
        echo "Account created: " . $op->getAccount() . "\n";
    } elseif ($op instanceof ChangeTrustOperationResponse) {
        echo "Trustline changed for: " . $op->getAssetCode() . "\n";
    } elseif ($op instanceof ManageSellOfferOperationResponse) {
        echo "Offer: " . $op->getAmount() . " at " . $op->getPrice() . "\n";
    } elseif ($op instanceof PathPaymentStrictReceiveOperationResponse) {
        echo "Path payment: " . $op->getSourceAmount() . " -> " . $op->getAmount() . "\n";
    }
    // Many other operation types available
}
```

### Effect Queries

Effects are the results of operations (account credited, trustline created, etc.).

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

// Effects for an account
$effectsPage = $sdk->effects()
    ->forAccount("GABC...")
    ->limit(50)
    ->execute();

// Effects for a specific operation
$effectsPage = $sdk->effects()
    ->forOperation("123456789")
    ->execute();

foreach ($effectsPage->getEffects()->toArray() as $effect) {
    echo $effect->getType() . "\n";
}
```

### Ledger & Payment Queries

Ledgers are blocks of transactions. The payments endpoint filters for payment-type operations only.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

// Ledgers
$ledger = $sdk->requestLedger("12345678");
$ledgersPage = $sdk->ledgers()->limit(10)->order("desc")->execute();

// Payments (Payment, PathPayment, CreateAccount, AccountMerge)
$paymentsPage = $sdk->payments()->forAccount("GABC...")->execute();
```

### Offer Queries

Query open offers on the DEX by account, asset, or sponsor.

#### Get Single Offer

Fetch a specific offer by its ID.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;

$sdk = StellarSDK::getTestNetInstance();

$offer = $sdk->requestOffer("12345");
echo "Selling: " . $offer->getAmount() . " " . Asset::canonicalForm($offer->getSelling()) . "\n";
echo "Buying: " . Asset::canonicalForm($offer->getBuying()) . "\n";
echo "Price: " . $offer->getPrice() . "\n";
```

#### Offers by Account

Get all open offers for a specific account.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$offersPage = $sdk->offers()
    ->forAccount("GABC...")
    ->limit(50)
    ->execute();

foreach ($offersPage->getOffers()->toArray() as $offer) {
    echo $offer->getOfferId() . ": " . $offer->getAmount() . " at " . $offer->getPrice() . "\n";
}
```

#### Offers by Asset

Find all offers selling or buying a specific asset.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;

$sdk = StellarSDK::getTestNetInstance();
$usdAsset = Asset::createNonNativeAsset("USD", "GISSUER...");

// Find offers selling XLM
$offersPage = $sdk->offers()
    ->forSellingAsset(Asset::native())
    ->execute();

// Find offers buying USD
$offersPage = $sdk->offers()
    ->forBuyingAsset($usdAsset)
    ->execute();

foreach ($offersPage->getOffers()->toArray() as $offer) {
    echo $offer->getOfferId() . ": " . $offer->getAmount() . " at " . $offer->getPrice() . "\n";
}
```

#### Offers by Sponsor

Find all offers sponsored by a specific account.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$offersPage = $sdk->offers()
    ->forSponsor("GSPONSOR...")
    ->execute();

foreach ($offersPage->getOffers()->toArray() as $offer) {
    echo $offer->getOfferId() . "\n";
}
```

### Trade Queries

Query executed trades by account, asset pair, or offer.

#### Trades by Account

Get all trades involving a specific account.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$tradesPage = $sdk->trades()
    ->forAccount("GABC...")
    ->limit(50)
    ->order("desc")
    ->execute();

foreach ($tradesPage->getTrades()->toArray() as $trade) {
    echo $trade->getBaseAmount() . " " . $trade->getBaseAssetCode();
    echo " for " . $trade->getCounterAmount() . " " . $trade->getCounterAssetCode() . "\n";
}
```

#### Trades by Asset Pair

Get all trades between two specific assets. Useful for analyzing market activity.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;

$sdk = StellarSDK::getTestNetInstance();
$usdAsset = Asset::createNonNativeAsset("USD", "GISSUER...");

$tradesPage = $sdk->trades()
    ->forBaseAsset(Asset::native())
    ->forCounterAsset($usdAsset)
    ->limit(50)
    ->order("desc")
    ->execute();

foreach ($tradesPage->getTrades()->toArray() as $trade) {
    echo $trade->getBaseAmount() . " XLM for " . $trade->getCounterAmount() . " USD\n";
}
```

#### Trades by Offer

Get all trades that filled a specific offer.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$tradesPage = $sdk->trades()
    ->forOffer("12345")
    ->execute();

foreach ($tradesPage->getTrades()->toArray() as $trade) {
    $price = $trade->getPrice();
    echo $trade->getBaseAmount() . " at " . ($price->getN() / $price->getD()) . "\n";
}
```

#### Trade Aggregations (OHLCV)

Get OHLCV (Open, High, Low, Close, Volume) candles for charting. Useful for building price charts and analyzing market trends.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;

$sdk = StellarSDK::getTestNetInstance();
$usdAsset = Asset::createNonNativeAsset("USD", "GISSUER...");

// Get hourly candles for the last 24 hours
$aggregations = $sdk->tradeAggregations()
    ->forBaseAsset(Asset::native())
    ->forCounterAsset($usdAsset)
    ->forResolution("3600000")  // 1 hour in milliseconds
    ->limit(24)
    ->execute();

foreach ($aggregations->getTradeAggregations()->toArray() as $candle) {
    echo "Open: " . $candle->getOpenPrice() . "\n";
    echo "High: " . $candle->getHighPrice() . "\n";
    echo "Low: " . $candle->getLowPrice() . "\n";
    echo "Close: " . $candle->getClosePrice() . "\n";
    echo "Volume: " . $candle->getBaseVolume() . "\n";
}

// Common resolutions (in milliseconds):
// 60000 (1 min), 300000 (5 min), 900000 (15 min),
// 3600000 (1 hour), 86400000 (1 day), 604800000 (1 week)
```

### Asset Queries

Look up assets by code or issuer. Useful for discovering all issuers of a token or all assets from an issuer.

#### Find by Code

Find all assets with a specific code. Different issuers can have the same asset code.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

// Find all USD assets (from different issuers)
$assetsPage = $sdk->assets()
    ->forAssetCode("USD")
    ->limit(20)
    ->execute();

foreach ($assetsPage->getAssets()->toArray() as $asset) {
    echo $asset->getAssetCode() . " by " . $asset->getAssetIssuer() . "\n";
    
    // Account statistics by authorization status
    $accounts = $asset->getAccounts();
    echo "Authorized holders: " . $accounts->getAuthorized() . "\n";
    
    // Balance totals by authorization status
    $balances = $asset->getBalances();
    echo "Authorized supply: " . $balances->getAuthorized() . "\n";
}
```

#### Find by Issuer

Find all assets issued by a specific account.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$assetsPage = $sdk->assets()
    ->forAssetIssuer("GISSUER...")
    ->execute();

foreach ($assetsPage->getAssets()->toArray() as $asset) {
    $totalSupply = $asset->getBalances()->getAuthorized();
    echo $asset->getAssetCode() . ": " . $totalSupply . " total\n";
}
```

### Order Book Queries

Get the current order book for an asset pair. Returns bids (buy orders) and asks (sell orders) sorted by price.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;

$sdk = StellarSDK::getTestNetInstance();
$usdAsset = Asset::createNonNativeAsset("USD", "GISSUER...");

// Get order book: people selling XLM for USD
$orderBook = $sdk->orderBook()
    ->forSellingAsset(Asset::native())
    ->forBuyingAsset($usdAsset)
    ->execute();

// Bids: offers to buy the base asset (XLM)
foreach ($orderBook->getBids() as $bid) {
    echo "Bid: " . $bid->getAmount() . " XLM at " . $bid->getPrice() . " USD\n";
}

// Asks: offers to sell the base asset (XLM)
foreach ($orderBook->getAsks() as $ask) {
    echo "Ask: " . $ask->getAmount() . " XLM at " . $ask->getPrice() . " USD\n";
}
```

### Payment Path Queries

Find payment paths for cross-asset transfers. Used with path payment operations.

#### Strict Send Paths

Find paths when you know how much you want to send. Returns what the recipient can receive.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;

$sdk = StellarSDK::getTestNetInstance();
$usdAsset = Asset::createNonNativeAsset("USD", "GISSUER...");

// "If I send 100 XLM, how much USD can the recipient get?"
$pathsPage = $sdk->findStrictSendPaths()
    ->forSourceAsset(Asset::native())
    ->forSourceAmount("100")
    ->forDestinationAssets([$usdAsset])
    ->execute();

foreach ($pathsPage->getPaths()->toArray() as $path) {
    echo "Send 100 XLM, receive " . $path->getDestinationAmount() . " USD\n";
}
```

#### Strict Receive Paths

Find paths when you know how much the recipient needs. Returns what you need to send.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;

$sdk = StellarSDK::getTestNetInstance();
$usdAsset = Asset::createNonNativeAsset("USD", "GISSUER...");

// "If recipient needs 100 USD, how much XLM do I send?"
$pathsPage = $sdk->findStrictReceivePaths()
    ->forSourceAccount("GSENDER...")
    ->forDestinationAsset($usdAsset)
    ->forDestinationAmount("100")
    ->execute();

foreach ($pathsPage->getPaths()->toArray() as $path) {
    echo "Send " . $path->getSourceAmount() . " XLM to receive 100 USD\n";
}

// See "Path Payment Operations" section for how to use these paths
```

### Claimable Balance Queries

Find claimable balances you can claim, or look up a specific balance by ID.

#### Get Single Balance

Fetch a specific claimable balance by its ID. Accepts both hex format and strkey format (starts with "B"). See [SEP-23](sep/sep-23.md) for more on strkey encoding.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;

$sdk = StellarSDK::getTestNetInstance();

// Using hex format
$balance = $sdk->requestClaimableBalance("00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072");
echo "Amount: " . $balance->getAmount() . "\n";
echo "Asset: " . Asset::canonicalForm($balance->getAsset()) . "\n";

// Strkey format also works (starts with "B")
$balance = $sdk->requestClaimableBalance("BAEKKL...");
```

#### Find by Claimant

Find all claimable balances that a specific account can claim.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$balancesPage = $sdk->claimableBalances()
    ->forClaimant("GCLAIMER...")
    ->execute();

foreach ($balancesPage->getClaimableBalances()->toArray() as $balance) {
    echo $balance->getBalanceId() . ": " . $balance->getAmount() . "\n";
}
```

#### Find by Sponsor

Find all claimable balances sponsored by a specific account.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$balancesPage = $sdk->claimableBalances()
    ->forSponsor("GSPONSOR...")
    ->execute();

foreach ($balancesPage->getClaimableBalances()->toArray() as $balance) {
    echo $balance->getBalanceId() . "\n";
}
```

#### Find by Asset

Find all claimable balances for a specific asset.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;

$sdk = StellarSDK::getTestNetInstance();

$usdAsset = Asset::createNonNativeAsset("USD", "GISSUER...");
$balancesPage = $sdk->claimableBalances()
    ->forAsset($usdAsset)
    ->execute();

foreach ($balancesPage->getClaimableBalances()->toArray() as $balance) {
    echo $balance->getAmount() . " " . Asset::canonicalForm($balance->getAsset()) . "\n";
}
```

### Liquidity Pool Queries

Find liquidity pools by reserve assets or by account participation.

#### Get Single Pool

Fetch a specific liquidity pool by its ID.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$pool = $sdk->requestLiquidityPool("poolid123...");
echo "Total shares: " . $pool->getTotalShares() . "\n";
echo "Total trustlines: " . $pool->getTotalTrustlines() . "\n";
```

#### Find by Reserve Assets

Find pools containing specific reserve assets.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$poolsPage = $sdk->liquidityPools()
    ->forReserves("native", "USD:GISSUER...")
    ->execute();

foreach ($poolsPage->getLiquidityPools()->toArray() as $pool) {
    echo "Pool ID: " . $pool->getPoolId() . "\n";
    echo "Total shares: " . $pool->getTotalShares() . "\n";
}
```

#### Find by Account

Find all pools an account participates in (has pool share trustlines).

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$poolsPage = $sdk->liquidityPools()
    ->forAccount("GABC...")
    ->execute();

foreach ($poolsPage->getLiquidityPools()->toArray() as $pool) {
    echo "Pool ID: " . $pool->getPoolId() . "\n";
}
```

### Pagination

Navigate through large result sets using cursors. Each record has a paging token you can use to fetch the next page.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

// First page
$page = $sdk->transactions()
    ->forAccount("GABC...")
    ->limit(20)
    ->order("desc")
    ->execute();

// Process results
foreach ($page->getTransactions()->toArray() as $tx) {
    echo $tx->getHash();
}

// Get next page using cursor from last record
$transactions = $page->getTransactions()->toArray();
if (!empty($transactions)) {
    $lastTx = end($transactions);
    $nextPage = $sdk->transactions()
        ->forAccount("GABC...")
        ->limit(20)
        ->order("desc")
        ->cursor($lastTx->getPagingToken())
        ->execute();
}
```

---

## Streaming (SSE)

Get real-time updates via Server-Sent Events. The connection stays open and calls your callback whenever new data arrives. Use `cursor("now")` to start from the current position rather than replaying historical data.

Streaming runs indefinitely until the process is interrupted. To stop streaming, you'll need to terminate the process or throw an exception from within the callback.

### Stream Payments

Stream payment-type operations (payments, path payments, create account, account merge) for an account.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Operations\OperationResponse;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PathPaymentStrictReceiveOperationResponse;

$sdk = StellarSDK::getTestNetInstance();

$sdk->payments()->forAccount("GABC...")->cursor("now")->stream(function(OperationResponse $payment) {
    if ($payment instanceof PaymentOperationResponse) {
        echo "Payment: " . $payment->getAmount() . " from " . $payment->getFrom() . "\n";
    } elseif ($payment instanceof PathPaymentStrictReceiveOperationResponse) {
        echo "Path payment: " . $payment->getAmount() . "\n";
    }
});
```

### Stream Transactions

Stream transactions for an account or all transactions on the network.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;

$sdk = StellarSDK::getTestNetInstance();

// Stream transactions for a specific account
$sdk->transactions()->forAccount("GABC...")->cursor("now")->stream(function(TransactionResponse $tx) {
    echo "Transaction: " . $tx->getHash() . "\n";
    echo "Operations: " . $tx->getOperationCount() . "\n";
});

// Stream all transactions on the network
$sdk->transactions()->cursor("now")->stream(function(TransactionResponse $tx) {
    echo "New transaction in ledger " . $tx->getLedger() . "\n";
});
```

### Stream Ledgers

Stream ledger closes to track network progress.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Ledger\LedgerResponse;

$sdk = StellarSDK::getTestNetInstance();

$sdk->ledgers()->cursor("now")->stream(function(LedgerResponse $ledger) {
    echo "Ledger " . $ledger->getSequence() . " closed\n";
    echo "Transactions: " . $ledger->getSuccessfulTransactionCount() . "\n";
});
```

### Stream Operations

Stream all operations for an account.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Operations\OperationResponse;

$sdk = StellarSDK::getTestNetInstance();

$sdk->operations()->forAccount("GABC...")->cursor("now")->stream(function(OperationResponse $op) {
    echo "Operation: " . $op->getHumanReadableOperationType() . "\n";
});
```

### Stream Effects

Stream effects (account credited, trustline created, etc.) for an account.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Effects\EffectResponse;

$sdk = StellarSDK::getTestNetInstance();

$sdk->effects()->forAccount("GABC...")->cursor("now")->stream(function(EffectResponse $effect) {
    echo "Effect: " . $effect->getHumanReadableEffectType() . "\n";
});
```

### Stream Trades

Stream trades for an account or trading pair.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Trades\TradeResponse;

$sdk = StellarSDK::getTestNetInstance();

// Stream trades for an account
$sdk->trades()->forAccount("GABC...")->cursor("now")->stream(function(TradeResponse $trade) {
    echo "Trade: " . $trade->getBaseAmount() . " for " . $trade->getCounterAmount() . "\n";
});

// Stream trades for an asset pair
$usdAsset = Asset::createNonNativeAsset("USD", "GISSUER...");
$sdk->trades()->forBaseAsset(Asset::native())->forCounterAsset($usdAsset)->cursor("now")->stream(function(TradeResponse $trade) {
    echo "XLM/USD trade at " . ($trade->getPrice()->getN() / $trade->getPrice()->getD()) . "\n";
});
```

### Stream Order Book

Stream order book updates for an asset pair.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\OrderBook\OrderBookResponse;

$sdk = StellarSDK::getTestNetInstance();
$usdAsset = Asset::createNonNativeAsset("USD", "GISSUER...");

$sdk->orderBook()
    ->forSellingAsset(Asset::native())
    ->forBuyingAsset($usdAsset)
    ->cursor("now")
    ->stream(function(OrderBookResponse $orderBook) {
        echo "Bids: " . $orderBook->getBids()->count() . "\n";
        echo "Asks: " . $orderBook->getAsks()->count() . "\n";
    });
```

### Stream Offers

Stream offer updates for an account.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Offers\OfferResponse;

$sdk = StellarSDK::getTestNetInstance();

$sdk->offers()->forAccount("GABC...")->cursor("now")->stream(function(OfferResponse $offer) {
    echo "Offer " . $offer->getOfferId() . ": " . $offer->getAmount() . " at " . $offer->getPrice() . "\n";
});
```

### Stream Accounts

Stream account updates (balance changes, data changes, etc.).

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Account\AccountResponse;

$sdk = StellarSDK::getTestNetInstance();

$sdk->accounts()->streamAccount("GABC...", function(AccountResponse $account) {
    echo "Account updated. Sequence: " . $account->getSequenceNumber() . "\n";
});
```

---

## Network Communication

Submit transactions, check fees, and handle network responses.

### Transaction Submission

Submit signed transactions to the network. The response includes the transaction hash and ledger number on success.

#### Synchronous Submission

The standard submission method waits for the transaction to be validated and included in a ledger before returning.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

$sdk = StellarSDK::getTestNetInstance();

try {
    $response = $sdk->submitTransaction($transaction);
    
    if ($response->isSuccessful()) {
        echo "Hash: " . $response->getHash() . "\n";
        echo "Ledger: " . $response->getLedger() . "\n";
    }
} catch (HorizonRequestException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

#### Asynchronous Submission

Submit without waiting for ledger inclusion. Returns immediately after Stellar Core accepts the transaction. Useful for high-throughput applications.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Transaction\SubmitAsyncTransactionResponse;

$sdk = StellarSDK::getTestNetInstance();

$response = $sdk->submitAsyncTransaction($transaction);

// Status: PENDING, DUPLICATE, TRY_AGAIN_LATER, or ERROR
echo "Status: " . $response->txStatus . "\n";
echo "Hash: " . $response->hash . "\n";

if ($response->txStatus === SubmitAsyncTransactionResponse::TX_STATUS_PENDING) {
    // Transaction accepted - poll for result later
    sleep(5);
    $txResponse = $sdk->requestTransaction($response->hash);
    if ($txResponse->isSuccessful()) {
        echo "Transaction confirmed in ledger " . $txResponse->getLedger() . "\n";
    }
}
```

#### Check Memo Requirements (SEP-29)

Before submitting, check if any destination accounts require a memo. Some exchanges and services reject transactions without memos.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$memoRequired = $sdk->checkMemoRequired($transaction);
if ($memoRequired !== false) {
    echo "Account $memoRequired requires a memo\n";
    // Add a memo before submitting
}
```

### Fee Statistics

Query current network fee levels to set appropriate fees for your transactions. All values are in stroops (1 XLM = 10,000,000 stroops).

#### Fee Charged Statistics

Get statistics on fees actually charged in recent ledgers.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$feeStats = $sdk->requestFeeStats();

// Fees actually charged in recent transactions
$feeCharged = $feeStats->getFeeCharged();
echo "Min fee charged: " . $feeCharged->getMin() . " stroops\n";
echo "Mode fee charged: " . $feeCharged->getMode() . " stroops\n";
echo "P90 fee charged: " . $feeCharged->getP90() . " stroops\n";
```

#### Max Fee Statistics

Get statistics on maximum fees users were willing to pay.

```php
<?php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

$feeStats = $sdk->requestFeeStats();

// Max fees users set (what they were willing to pay)
$maxFee = $feeStats->getMaxFee();
echo "Min max fee: " . $maxFee->getMin() . " stroops\n";
echo "Mode max fee: " . $maxFee->getMode() . " stroops\n";
echo "P90 max fee: " . $maxFee->getP90() . " stroops\n";

// Network capacity and base fee
echo "Last ledger: " . $feeStats->getLastLedger() . "\n";
echo "Base fee: " . $feeStats->getLastLedgerBaseFee() . " stroops\n";
echo "Capacity usage: " . $feeStats->getLedgerCapacityUsage() . "\n";
```

### Error Handling

When transactions fail, Horizon returns detailed error information including result codes for the transaction and each operation.

#### Handling Submission Errors

Catch exceptions and inspect result codes when a transaction fails.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

$sdk = StellarSDK::getTestNetInstance();

try {
    $response = $sdk->submitTransaction($transaction);
    
    if ($response->isSuccessful()) {
        echo "Success! Hash: " . $response->getHash() . "\n";
    }
} catch (HorizonRequestException $e) {
    // HTTP status and message
    echo "Status: " . $e->getStatusCode() . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    
    // Detailed Horizon error response
    $horizonError = $e->getHorizonErrorResponse();
    if ($horizonError) {
        echo "Type: " . $horizonError->getType() . "\n";
        echo "Title: " . $horizonError->getTitle() . "\n";
        echo "Detail: " . $horizonError->getDetail() . "\n";
    }
}
```

#### Transaction Result Codes

Extract specific result codes to understand why a transaction failed.

```php
<?php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

$sdk = StellarSDK::getTestNetInstance();

try {
    $sdk->submitTransaction($transaction);
} catch (HorizonRequestException $e) {
    $horizonError = $e->getHorizonErrorResponse();
    if ($horizonError) {
        $extras = $horizonError->getExtras();
        if ($extras) {
            // Transaction-level result code
            $txResult = $extras->getResultCodesTransaction();
            echo "Transaction result: " . $txResult . "\n";
            
            // Per-operation result codes
            $opResults = $extras->getResultCodesOperation() ?? [];
            foreach ($opResults as $index => $opResult) {
                echo "Operation $index: " . $opResult . "\n";
            }
        }
    }
}
```

#### Common Result Codes

**Transaction-level codes:**
- `tx_success` — Transaction succeeded
- `tx_failed` — One or more operations failed
- `tx_bad_seq` — Sequence number mismatch (reload account and retry)
- `tx_insufficient_fee` — Fee too low for current network load
- `tx_insufficient_balance` — Not enough XLM to cover fee + reserves

**Operation-level codes:**
- `op_success` — Operation succeeded
- `op_underfunded` — Not enough balance for payment
- `op_no_trust` — Destination missing trustline for asset
- `op_line_full` — Destination trustline limit exceeded
- `op_low_reserve` — Would leave account below minimum reserve

### Message Signing (SEP-53)

Sign and verify arbitrary messages with Stellar keypairs following the [SEP-53](sep/sep-53.md) specification. Useful for authentication and proving ownership of an account without creating a transaction.

#### Sign a Message

Create a cryptographic signature for any text using your secret key.

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;

$keyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34JFD6XVEAEPTBED53FETV");

// Sign a message
$message = "Please sign this message to verify your identity";
$signature = $keyPair->signMessage($message);

// Encode signature for transmission (e.g., in HTTP header or JSON)
$signatureBase64 = base64_encode($signature);
echo "Signature: " . $signatureBase64 . "\n";
```

#### Verify a Message

Confirm a signature matches the message and was created by a specific account.

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;

// Verify with the signing keypair
$keyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34JFD6XVEAEPTBED53FETV");

$message = "Please sign this message to verify your identity";
$signature = $keyPair->signMessage($message);

$isValid = $keyPair->verifyMessage($message, $signature);
if ($isValid) {
    echo "Signature is valid\n";
}
```

#### Verify with Public Key Only

When verifying, you only need the public key (account ID). This is typical for server-side verification.

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;

// Only have the public key (account ID)
$publicKey = KeyPair::fromAccountId("GABC...");

// Signature received from client (base64 encoded)
$signatureBase64 = "...";
$signature = base64_decode($signatureBase64);

$message = "Please sign this message to verify your identity";
$isValid = $publicKey->verifyMessage($message, $signature);

if ($isValid) {
    echo "User owns this account\n";
}
```

---

## Assets

Stellar supports native XLM and custom assets issued by accounts. Asset codes are 1-4 characters (alphanumeric4) or 5-12 characters (alphanumeric12). Every custom asset is uniquely identified by its code plus issuer account.

### Native XLM

The native asset (XLM) has no issuer and doesn't require a trustline.

```php
<?php
use Soneso\StellarSDK\Asset;

$xlm = Asset::native();
```

### Credit Assets

Custom assets issued by Stellar accounts. Use `AssetTypeCreditAlphanum4` for 1-4 character codes or `AssetTypeCreditAlphanum12` for 5-12 character codes.

```php
<?php
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\AssetTypeCreditAlphanum12;

// 1-4 character code
$usd = new AssetTypeCreditAlphanum4("USD", "GISSUER...");
$btc = new AssetTypeCreditAlphanum4("BTC", "GISSUER...");

// 5-12 character code
$myToken = new AssetTypeCreditAlphanum12("MYTOKEN", "GISSUER...");
```

### Auto-Detect Code Length

Use `createNonNativeAsset()` to automatically choose the correct type based on code length.

```php
<?php
use Soneso\StellarSDK\Asset;

// Automatically creates AssetTypeCreditAlphanum4
$usd = Asset::createNonNativeAsset("USD", "GISSUER...");

// Automatically creates AssetTypeCreditAlphanum12
$myToken = Asset::createNonNativeAsset("MYTOKEN", "GISSUER...");
```

### Canonical Form

Convert assets to/from canonical string format (`CODE:ISSUER`). Useful for storage, display, configuration, and SEP protocols like [SEP-38](sep/sep-38.md) (Anchor RFQ API).

```php
<?php
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;

$usd = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

// Convert to canonical string
$canonical = Asset::canonicalForm($usd);  // "USD:GISSUER..."

// Parse from canonical string
$asset = Asset::createFromCanonicalForm("USD:GISSUER...");

// Native asset canonical form
$xlmCanonical = Asset::canonicalForm(Asset::native());  // "native"
```

### Pool Share Assets

Liquidity pool share assets represent ownership in an AMM pool. Created from the two reserve assets.

```php
<?php
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\AssetTypePoolShare;

$usdAsset = new AssetTypeCreditAlphanum4("USD", "GISSUER...");

// Create pool share asset (assets must be in lexicographic order)
$poolShareAsset = new AssetTypePoolShare(Asset::native(), $usdAsset);
```

### Trustlines

Before receiving a custom asset, an account must create a trustline for it. Trustlines specify which assets the account accepts and set optional limits.

For detailed trustline operations (create, modify, remove, authorize), see [Asset Operations](#asset-operations) in the Operations chapter.

---

## Soroban (Smart Contracts)

Soroban is Stellar's smart contract platform. Smart contract transactions differ from classic transactions: they require a simulation step to determine resource requirements and fees before submission.

For complete documentation, see the dedicated [Soroban Guide](soroban.md).

### Quick Example

Deploy a contract and call a method with minimal setup.

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Contract\DeployRequest;
use Soneso\StellarSDK\Soroban\Contract\InstallRequest;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$keyPair = KeyPair::fromSeed('SXXX...');
$rpcUrl = 'https://soroban-testnet.stellar.org';

// Install WASM and deploy contract
$wasmHash = SorobanClient::install(new InstallRequest(
    wasmBytes: file_get_contents('hello.wasm'),
    rpcUrl: $rpcUrl,
    network: Network::testnet(),
    sourceAccountKeyPair: $keyPair
));

$client = SorobanClient::deploy(new DeployRequest(
    rpcUrl: $rpcUrl,
    network: Network::testnet(),
    sourceAccountKeyPair: $keyPair,
    wasmHash: $wasmHash
));

// Invoke contract method
$result = $client->invokeMethod('hello', [XdrSCVal::forSymbol('World')]);
echo $result->vec[0]->sym . ', ' . $result->vec[1]->sym; // Hello, World
```

### Soroban RPC Server

Direct communication with Soroban RPC nodes for low-level operations.

```php
<?php
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\Responses\GetHealthResponse;

$server = new SorobanServer("https://soroban-testnet.stellar.org");

// Check server health
$health = $server->getHealth();
if ($health->getStatus() === GetHealthResponse::HEALTHY) {
    echo "Soroban RPC is healthy\n";
}

// Get latest ledger
$ledger = $server->getLatestLedger();
echo "Latest ledger: " . $ledger->sequence . "\n";

// Get account (for sequence number)
$account = $server->getAccount("GABC...");
echo "Sequence: " . $account->getSequenceNumber() . "\n";
```

### What's Covered in the Soroban Guide

The [Soroban Guide](soroban.md) covers:

- **SorobanServer** — Direct RPC communication, contract data queries
- **SorobanClient** — High-level contract interaction API
- **Installing & Deploying** — WASM installation and contract deployment
- **AssembledTransaction** — Transaction lifecycle with simulation
- **Authorization** — Signing auth entries for contract calls
- **Type Conversions** — XdrSCVal creation and parsing
- **Events** — Reading contract events
- **Error Handling** — Simulation and submission errors

---

## Further Reading

- [Quick Start Guide](quick-start.md) — First transaction in 15 minutes
- [Getting Started](getting-started.md) — Installation and fundamentals
- [Soroban Guide](soroban.md) — Smart contract development
- [SEP Protocols](sep/README.md) — Stellar Ecosystem Proposals
- [PHPDoc Reference](https://soneso.github.io/stellar-php-sdk/) — Full API documentation

---

**Navigation:** [← Getting Started](getting-started.md) | [Soroban Guide →](soroban.md)
