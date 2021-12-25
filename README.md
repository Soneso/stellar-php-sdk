# [Stellar SDK for PHP](https://github.com/Soneso/stellar-php-sdk)

![PHP](https://img.shields.io/badge/PHP-blue.svg)

The Soneso open source Stellar SDK for PHP will provide APIs to build and sign transactions, connect and query [Horizon](https://github.com/stellar/horizon).

The SDK is currently in development. The core functionality is already implemented and can be used. Please be careful with it, it is an alpha version.

## Development Roadmap

#### Okt 2021 – March 2022
Implementation of the Core Functionalty including SEP-029

#### April – August 2022
Implementation of SEP-001, SEP-002, SEP-023, SEP-005, SEP-010, SEP-007, SEP-009, SEP-006, SEP-012, SEP-011

#### Sept 2022 – Dec 2022
Maintenance and continuous improvement of the sdk

## Installation

TODO

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
| [Create trustline](examples/trustline.md) | An trustor account trusts an issuer account for a specific custom token. The issuer account can now send tokens to the trustor account. |[Assets & Trustlines](https://www.stellar.org/developers/guides/concepts/assets.html) and [Change trust](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#change-trust)|
| [Send tokens - non native payment](examples/send_non_native_payment.md) | Two accounts trust the same issuer account and custom token. They can now send this custom tokens to each other. | [Assets & Trustlines](https://www.stellar.org/developers/guides/concepts/assets.html) and [Change trust](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#change-trust) and [Payments](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#payment)|
| [Path payments](examples/path_payments.md) | Two accounts trust different custom tokens. The sender wants to send token "IOM" but the receiver wants to receive token "ECO".| [Path payment strict send](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#path-payment-strict-send) and [Path payment strict receive](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#path-payment-strict-receive)|
| [Merge accounts](examples/merge_account.md) | Merge one account into another. The first account is removed, the second receives the funds. | [Account merge](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#account-merge) |
| [Bump sequence number](examples/bump_sequence.md) | In this example we will bump the sequence number of an account to a higher number. | [Bump sequence number](https://www.stellar.org/developers/guides/concepts/list-of-operations.html#bump-sequence) |

More examples can be found in the [tests](https://github.com/Soneso/stellar-php-sdk/tree/main/Soneso/StellarSDKTests).
