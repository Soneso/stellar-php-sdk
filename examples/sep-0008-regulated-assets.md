
# SEP-0008 - Regulated Assets

Regulated Assets are assets that require an issuer’s approval (or a delegated third party’s approval, such as a licensed securities exchange)
on a per-transaction basis. [SEP-08](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0008.md)
standardizes the identification of such assets as well as defines the protocol for performing compliance checks and requesting issuer approval.

## Create a `RegulatedAssetsService` instance

Let's start with creating a `RegulatedAssetsService` object, which we'll use for all SEP-08 interactions.

**Via it's constructor:**

```php
$service = new RegulatedAssetsService(tomlData: $tomlData);
```

The parameter `tomlData` of type `StellarToml` represents stellar.toml data provided by the Server (e.g. Anchor) as described in the [SEP-01 Example](https://github.com/Soneso/stellar-php-sdk/blob/main/examples/sep-0001-toml.md)

**Or by providing the domain hosting the stellar.toml file**

```php
$service = RegulatedAssetsService::fromDomain(domain: 'place.domain.com');
```

This will automatically load and parse the `stellar.toml` file. It will then create the `RegulatedAssetsService` instance by using the needed data provided in the `stellar.toml` file by the Server.

## Get regulated assets

During initialization, the service extracts the relevant assets from the provided toml data.
It considers only those currencies that are regulated and have an approval server set.

You can access them as follows:

```php
$regulatedAssets = $service->regulatedAssets;
```

## Authorization required

By using the service, you can check if a given asset needs authorization.

```php
$needsAuthorization = $service->authorizationRequired($regulatedAsset);
```

This loads the issuer account data from the Stellar Network and checks if the both
flags `authRequired` and `authRevocable` are set.


## Send transaction to approval server

First let's create the transaction:

```php
$xAsset = $regulatedAssets[0];

// Operation 1: AllowTrust op where issuer fully authorizes account A, asset X
$op1 = (new SetTrustLineFlagsOperationBuilder(
            trustorId: $accountAId,
            asset: $xAsset,
            clearFlags: 0,
            setFlags: XdrTrustLineFlags::AUTHORIZED_FLAG,
        ))->build();

// Operation 2: Account A manages offer to buy asset X
$op2 = (new ManageBuyOfferOperationBuilder(
            selling: Asset::native(),
            buying: $xAsset,
            amount: '10',
            price: '0.1',
        ))->build();

// Operation 3: AllowTrust op where issuer sets account A, asset X to AUTHORIZED_TO_MAINTAIN_LIABILITIES_FLAG state
$op3 = (new SetTrustLineFlagsOperationBuilder(
            trustorId: $accountAId,
            asset: $xAsset,
            clearFlags: 0,
            setFlags: XdrTrustLineFlags::AUTHORIZED_TO_MAINTAIN_LIABILITIES_FLAG,
        ))->build();

$tx = (new TransactionBuilder(sourceAccount: $accountA))
    ->addOperation($op1)
    ->addOperation($op2)
    ->addOperation($op3)
    ->build();

$txBase64Xdr = $tx->toEnvelopeXdrBase64();
```

Next let's send it to the approval server using our service:

```php
$postResponse = $service->postTransaction(
            tx: $txB46Xdr,
            approvalServer: $goatAsset->approvalServer,
);
```

Depending on the `postResponse` type you can now access the corresponding data.

```php
if ($postResponse instanceof SEP08PostTransactionSuccess) {
  // Transaction has been approved and signed by the issuer
  print($postResponse->tx);
  print($postResponse->message);
} else if ($postResponse instanceof SEP08PostTransactionRevised) {
  // Transaction has been revised to be made compliant, and signed by the issuer. 
  print($postResponse->tx);
  print($postResponse->message);
} else if ($postResponse instanceof SEP08PostTransactionPending) {
  // The issuer could not determine whether to approve this transaction at the moment. 
  print($postResponse->timeout);
  print($postResponse->message);
} else if ($postResponse instanceof SEP08PostTransactionActionRequired) {
  // Transaction requires a user action to be completed.
  print($postResponse->actionUrl);
  print($postResponse->actionMethod);
  print($postResponse->actionFields);
  print($postResponse->message);
} else if ($postResponse instanceof SEP08PostTransactionRejected) {
  // Wallet should display the associated error message to the user.
  print($postResponse->error);
}
```


### Following the Action URL

If the approval server response is `SEP08PostTransactionActionRequired` and the `$postResponse->actionMethod` is `POST`
you can use the service to send the values for the requested fields.

```php
$actionResponse = $service->postAction(
                url: $actionUrl,
                actionFields:[
                    'email_address' => 'test@mail.com',
                    'mobile_number' => '+3472829839222',
                ],
);
     
if ($actionResponse instanceof SEP08PostActionNextUrl) {
    print($actionResponse->message);
    print($actionResponse->nextUrl);
    // ...
} else if ($actionResponse instanceof SEP08PostActionDone) {
  // resend tx
}
```

## Further readings

SDK's [SEP-08 test cases](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SEP008Test.php).

