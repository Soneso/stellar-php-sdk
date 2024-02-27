
# SEP-0031 - Cross-Border Payments API

The [SEP-31](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0031.md) 
defines a protocol for enabling payments between two financial accounts that exist outside the Stellar network.

The PHP SDK offers SEP-31 support for the Sending Anchor.

## Create a `CrossBorderPaymentsService` instance

Let's start with creating a `CrossBorderPaymentsService` object, which we'll use for all SEP-31 interactions.

**By providing the direct payment server url or the receiving anchor directly via the constructor:**

```php
$service = new CrossBorderPaymentsService("http://api.stellar.org/direct");
```

**By providing the receiving anchor's domain hosting the stellar.toml file**

```php
$service = CrossBorderPaymentsService::fromDomain("place.domain.com");
```

This will automatically load and parse the `stellar.toml` file. It will then create the `CrossBorderPaymentsService` instance by using the direct payment server url provided in the `stellar.toml` file.

## Authentication

Authentication is done using the [Sep-10 WebAuth Service](https://github.com/Soneso/stellar-php-sdk/blob/main/examples/sep-0010-webauth.md), and we will use the authentication token in the SEP-31 requests.

## Get Information

Receiving anchors communicate basic info about what currencies their direct payment server supports receiving from partner anchors (Sending Anchors).

To request this information as a sending anchor from the receiver anchor we can use the info endpoint.

```php
$response = $service->info(jwt: $jwtToken);
$assets = $response->receiveAssets;
```

## POST Transactions

This request initiates a payment. The Sending and Receiving Client must be registered via SEP-12 if required by the Receiving Anchor.

```php
$request = new SEP31PostTransactionsRequest(
    amount: 100,
    assetCode: "USDC",
    assetIssuer: "GDRHDSTZ4PK6VI3WL224XBJFEB6CUXQESTQPXYIB3KGITRLL7XVE4NWV",
    destinationAsset: "iso4217:BRL",
    senderId: "d2bd1412-e2f6-4047-ad70-a1a2f133b25c",
    receiverId: "137938d4-43a7-4252-a452-842adcee474c",
);
$response = $service->postTransactions($request, $jwtToken);
$transactionId = $response->id; 
```

## Get Transaction

The transaction endpoint enables Sending Anchors to fetch information on a specific transaction with the Receiving Anchor.

```php
$response = $service->getTransaction($transactionId, jwtToken);
$status = $response->status;
```

## PUT Transaction Callback

This endpoint can be used by the Sending Anchor to register a callback URL that the Receiving Anchor will make application/json POST requests to containing the transaction object whenever the transaction's status value has changed. 
Note that a callback does not need to be made for the initial status of the transaction, which in most cases is pending_sender.

```php
$service->putTransactionCallback($transactionId, $callbackUrl, $jwtToken);
```

### Further readings

SDK's [SEP-31 test cases](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SEP031Test.php).
