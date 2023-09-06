
# [Stellar SDK for PHP](https://github.com/Soneso/stellar-php-sdk)

![v1.2.4](https://img.shields.io/badge/v1.2.4-green.svg)

The Soneso open source Stellar SDK for PHP provides APIs to build and sign transactions, connect and query [Horizon](https://github.com/stellar/horizon).

## Installation

```composer require soneso/stellar-php-sdk```

## Quick Start

### 1. Create a Stellar key pair

#### Random generation
```php
// create a completely new and unique pair of keys.
$keyPair = KeyPair::random();

print($keyPair->getAccountId());
// GCFXHS4GXL6BVUCXBWXGTITROWLVYXQKQLF4YH5O5JT3YZXCYPAFBJZB

print($keyPair->getSecretSeed());
// SAV76USXIJOBMEQXPANUOQM6F5LIOTLPDIDVRJBFFE2MDJXG24TAPUU7
```

### 2. Create an account
After the key pair generation, you have already got the address, but it is not activated until someone transfers at least 1 lumen into it.

#### 2.1 Testnet
If you want to play in the Stellar test network, the SDK can ask Friendbot to create an account for you as shown below:
```php
$funded = FriendBot::fundTestAccount($keyPair->getAccountId());
print ($funded ? "account funded" : "account not funded");
```

#### 2.2 Public net

On the other hand, if you would like to create an account in the public net, you should buy some Stellar Lumens (XLM) from an exchange. When you withdraw the Lumens into your new account, the exchange will automatically create the account for you. However, if you want to create an account from another account of your own, you may run the following code:

```php
/// Init sdk for public net
$sdk = StellarSDK::getPublicNetInstance();
 
/// Create a key pair for your existing account.
$keyA = KeyPair::fromSeed("SAPS66IJDXUSFDSDKIHR4LN6YPXIGCM5FBZ7GE66FDKFJRYJGFW7ZHYF");

/// Load the data of your account from the stellar network.
$accA = $sdk->requestAccount($keyA->getAccountId());

/// Create a keypair for a new account.
$keyB = KeyPair::random();

/// Create the operation builder.
$createAccBuilder = new CreateAccountOperationBuilder($keyB->getAccountId(), "3"); // send 3 XLM (lumen)

// Create the transaction.
$transaction = (new TransactionBuilder($accA))
    ->addOperation($createAccBuilder->build())
    ->build();

/// Sign the transaction with the key pair of your existing account.
$transaction->sign($keyA, Network::public());

/// Submit the transaction to the stellar network.
$response = $sdk->submitTransaction($transaction);

if ($response->isSuccessful()) {
    printf (PHP_EOL."account %s created", $keyB->getAccountId());
}
```

### 3. Check account
#### 3.1 Basic info

After creating the account, we may check the basic information of the account.

```php
$accountId = "GCQHNQR2VM5OPXSTWZSF7ISDLE5XZRF73LNU6EOZXFQG2IJFU4WB7VFY";

// Request the account data.
$account = $sdk->requestAccount($accountId);

// You can check the `balance`, `sequence`, `flags`, `signers`, `data` etc.
foreach ($account->getBalances() as $balance) {
    switch ($balance->getAssetType()) {
        case Asset::TYPE_NATIVE:
            printf (PHP_EOL."Balance: %s XLM", $balance->getBalance() );
            break;
        default:
            printf(PHP_EOL."Balance: %s %s Issuer: %s",
                $balance->getBalance(), $balance->getAssetCode(),
                $balance->getAssetIssuer());
    }
}

print(PHP_EOL."Sequence number: ".$account->getSequenceNumber());

foreach ($account->getSigners() as $signer) {
    print(PHP_EOL."Signer public key: ".$signer->getKey());
}
```

#### 3.2 Check payments

You can check the payments connected to an account:

```php
$accountId = $account->getAccountId();

$operationsPage = $sdk->payments()->forAccount($accountId)->order("desc")->execute();

foreach ($operationsPage->getOperations() as $payment) {
    if ($payment->isTransactionSuccessful()) {
        print(PHP_EOL."Transaction hash: ".$payment->getTransactionHash());
    }
}
```
You can use:`limit`, `order`, and `cursor` to customize the query. Get the most recent payments for accounts, ledgers and transactions.

Horizon has SSE support for push data. You can use it like this:
```php
$accountId = "GCDBA6GFGEHAMVAMRL6R2733EXUENJ35EMYNA2LE7WWJPVANORVC4UNA";

$sdk->payments()->forAccount($accountId)->cursor("now")->stream(function(OperationResponse $response) {
    if ($response instanceof PaymentOperationResponse) {
        switch ($response->getAsset()->getType()) {
            case Asset::TYPE_NATIVE:
                printf("Payment of %s XLM from %s received.", $response->getAmount(), $response->getSourceAccount());
                break;
            default:
                printf("Payment of %s %s from %s received.", $response->getAmount(),  $response->getAsset()->getCode(), $response->getSourceAccount());
        }
        if (floatval($response->getAmount()) > 0.5) {
            exit;
        }
    }
});
```
see also [stream payments example](examples/stream_payments.md)

#### 3.3 Check others

Just like payments, you can check `assets`, `transactions`, `effects`, `offers`, `operations`, `ledgers` etc. 

```php
$sdk->assets()
$sdk->transactions()
$sdk->effects()
$sdk->offers()
$sdk->operations()
$sdk->orderBook()
$sdk->trades()
// add so on ...
```

### 4. Building and submitting transactions

Example "send native payment":

```php
$senderKeyPair = KeyPair::fromSeed("SA52PD5FN425CUONRMMX2CY5HB6I473A5OYNIVU67INROUZ6W4SPHXZB");
$destination = "GCRFFUKMUWWBRIA6ABRDFL5NKO6CKDB2IOX7MOS2TRLXNXQD255Z2MYG";

// Load sender account data from the stellar network.
$sender = $sdk->requestAccount($senderKeyPair->getAccountId());

// Build the transaction to send 100 XLM native payment from sender to destination
$paymentOperation = (new PaymentOperationBuilder($destination,Asset::native(), "100"))->build();
$transaction = (new TransactionBuilder($sender))->addOperation($paymentOperation)->build();

// Sign the transaction with the sender's key pair.
$transaction->sign($senderKeyPair, Network::testnet());

// Submit the transaction to the stellar network.
$response = $sdk->submitTransaction($transaction);
if ($response->isSuccessful()) {
    print(PHP_EOL."Payment sent");
}
```

## Documentation and Examples

### Examples

| Example | Description | Documentation |
| :--- | :--- | :--- |
| [Create a new account](examples/create_account.md)| A new account is created by another account. In the testnet we can also use Freindbot.|[Create account](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#create-account) |
| [Send native payment](examples/send_native_payment.md)| A sender sends 100 XLM (Stellar Lumens) native payment to a receiver. |[Payments](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#payment) |
| [Trustlines](examples/change_trust.md) | Creates, updates, and deletes a trustline. | [Change Trust](https://www.stellar.org/developers/learn/concepts/list-of-operations.html#change-trust) and [Assets documentation](https://www.stellar.org/developers/learn/concepts/assets.html) |
| [Send tokens - non native payment](examples/send_non_native_payment.md) | Two accounts trust the same issuer account and custom token. They can now send this custom tokens to each other. | [Assets & Trustlines](https://www.stellar.org/developers/guides/concepts/assets.html) and [Change trust](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#change-trust) and [Payments](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#payment)|
| [Path payments](examples/path_payments.md) | Two accounts trust different custom tokens. The sender wants to send token "IOM" but the receiver wants to receive token "ECO".| [Path payment strict send](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#path-payment-strict-send) and [Path payment strict receive](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#path-payment-strict-receive)|
| [Merge accounts](examples/merge_account.md) | Merge one account into another. The first account is removed, the second receives the funds. | [Account merge](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#account-merge) |
| [Bump sequence number](examples/bump_sequence.md) | In this example we will bump the sequence number of an account to a higher number. | [Bump sequence number](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#bump-sequence) |
| [Manage data](examples/manage_data.md) | Sets, modifies, or deletes a data entry (name/value pair) that is attached to a particular account. | [Manage data](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#manage-data) |
| [Manage buy offer](examples/manage_buy_offer.md) | Creates, updates, or deletes an offer to buy one asset for another, otherwise known as a "bid" order on a traditional orderbook. | [Manage buy offer](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#manage-buy-offer) |
| [Manage sell offer](examples/manage_sell_offer.md) | Creates, updates, or deletes an offer to sell one asset for another, otherwise known as a "ask" order or “offer” on a traditional orderbook. | [Manage sell offer](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#manage-sell-offer) |
| [Create passive sell offer](examples/create_passive_sell_offer.md) | Creates, updates and deletes an offer to sell one asset for another, otherwise known as a "ask" order or “offer” on a traditional orderbook, _without taking a reverse offer of equal price_. | [Create passive sell offer](https://www.stellar.org/developers/learn/concepts/list-of-operations.html#create-passive-sell-offer) |
| [Allow trust](examples/allow_trust.md) | Updates the authorized flag of an existing trustline. | [Allow trust](https://www.stellar.org/developers/learn/concepts/list-of-operations.html#allow-trust) and [Assets documentation](https://www.stellar.org/developers/learn/concepts/assets.html) |
| [Fee bump transaction](examples/fee_bump.md) | Fee bump transactions allow an arbitrary account to pay the fee for a transaction.| [Fee bump transactions](https://github.com/stellar/stellar-protocol/blob/master/core/cap-0015.md)|
| [Muxed accounts](examples/muxed_account_payment.md) | In this example we will see how to use a muxed account in a payment operation.| [First-class multiplexed accounts](https://github.com/stellar/stellar-protocol/blob/master/core/cap-0027.md)|
| [Stream payments](examples/stream_payments.md) | Listens for payments received by a given account.| [Streaming](https://developers.stellar.org/api/introduction/streaming/) |
| [SEP-0001: stellar.toml](examples/sep-0001-toml.md) | In this example you can find out how to obtain data about an organization’s Stellar integration.| [SEP-0001](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md)|
| [SEP-0002: Federation](examples/sep-0002-federation.md) | This example shows how to resolve a stellar address, a stellar account id, a transaction id and a forward by using the federation protocol. | [SEP-0002](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0002.md)|
| [SEP-0005: Key derivation](examples/sep-0005-key-derivation.md) | In this examples you can see how to generate 12 or 24 words mnemonics for different languages using the PHP SDK, how to generate key pairs from a mnemonic (with and without BIP 39 passphrase) and how to generate key pairs from a BIP 39 seed. | [SEP-0005](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md)|
| [SEP-0006: Deposit and Withdrawal API](examples/sep-0006-transfer.md) | In this examples you can see how to use the sdk to communicate with anchors.| [SEP-0006](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md)|
| [SEP-0007: URI Scheme to facilitate delegated signing](examples/sep-0007-urischeme.md) | In this examples you can see how to use the sdk to support SEP-0007 in your wallet or server.| [SEP-0007](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0007.md)|
| [SEP-0010: Stellar Web Authentication](examples/sep-0010-webauth.md) | This example shows how to authenticate with any web service which requires a Stellar account ownership verification. | [SEP-0010](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0010.md)|
| [SEP-0011: Txrep](examples/sep-0011-txrep.md) | This example shows how to  to generate Txrep (human-readable low-level representation of Stellar transactions) from a transaction and how to create a transaction object from a Txrep string. | [SEP-0011](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0011.md)|
| [SEP-0012: KYC API](examples/sep-0012-kyc.md) | In this examples you can see how to use the sdk to send KYC data to anchors and other services. | [SEP-0012](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md)|

More examples can be found in the [tests](https://github.com/Soneso/stellar-php-sdk/tree/main/Soneso/StellarSDKTests).

### SEPs implemented

- [SEP-0001: stellar.toml](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md)
- [SEP-0002: Federation](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0002.md)
- [SEP-0005: Key derivation](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0005.md)
- [SEP-0006: Deposit and Withdrawal API](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md)
- [SEP-0007: URI Scheme to facilitate delegated signing](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0007.md)
- [SEP-0009: Standard KYC Fields](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0009.md)
- [SEP-0010: Stellar Web Authentication](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0010.md)
- [SEP-0011: Txrep](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0011.md)
- [SEP-0012: KYC API](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0012.md)
- [SEP-0023: Strkeys](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0023.md)
- [SEP-0029: Account Memo Requirements](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0029.md)

### Soroban support

This SDK provides experimental [support for Soroban](https://github.com/Soneso/stellar-php-sdk/blob/main/soroban.md). 