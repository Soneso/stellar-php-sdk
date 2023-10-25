
# SEP-0024 - InteractiveService

Helps clients to interact with anchors in a standard way defined by [SEP-0024: Hosted Deposit and Withdrawal](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md).

## Create an InteractiveService instance

**By providing the domain hosting the stellar.toml file**

```php
$interactiveService = InteractiveService::fromDomain("place.domain.com");
```

This will automatically load and parse the stellar.toml file. It will then create the InteractiveService instance by using the transfer server url provided in the stellar.toml file.

**Or by providing the service url**

Alternatively one can create a InteractiveService instance by providing the transfer server url directly via the constructor:

```php
$interactiveService = new InteractiveService("http://api.stellar-anchor.org/interactive");
```

## Get Anchor Information

First, let's get the information about the anchor's support for [SEP-24](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md). This request doesn't require authentication, and will return generic info, such as supported currencies, and features supported by the anchor. You can get a full list of returned fields in the [SEP-24 specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#info).

```php
$infoResponse = $interactiveService->info("en");
```

## Fee

If there is a fee and the fee schedule is not complex, the info response already contains the fee data for a given asset.

```php
$depositAssetUSD = $infoResponse->getDepositAssets()["USD"];
$feeFixed = $depositAssetUSD->feeFixed;
$feePercent = $depositAssetUSD->feePercent;
$feeMinimum = $depositAssetUSD->feeMinimum;
```

Otherwise, one can check if the fee endpoint of the anchor is enabled and if so, request the fee from there.

```php
if ($infoResponse->feeEndpointInfo->enabled) {
    $request = new SEP24FeeRequest("deposit", "ETH", 2034.09, "SEPA", $jwtToken);
    $response = $interactiveService->fee($request);
    $fee = $response->fee;
}
```

## Interactive Flows

Before getting started, make sure you have connected to the anchor and received an authentication token, by using the SDKs [WebAuthService](sep-0010-webauth.md).
We will use the jwt token in the examples below as the SEP-10 authentication token, obtained earlier.

### Deposit
To initiate an operation, we need to know the asset code.

```php
$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$response = $interactiveService->deposit($request);
```

As a result, you will get an interactive response from the anchor.
Open the received URL in an iframe and deposit the transaction ID for future reference:

```php
$url = $response->url;
$id = $response->id;
```

### Withdraw

Similarly to the deposit flow, a basic withdrawal flow has the same method signature and response type:

```php
$request = new SEP24WithdrawRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$response = $interactiveService->withdraw($request);
```

As a result, you will get an interactive response from the anchor.
Open the received URL in an iframe and deposit the transaction ID for future reference:

```php
$url = $response->url;
$id = $response->id;
```

### Providing KYC Info
To improve the user experience, the SEP-24 standard supports passing user KYC to the anchor via [SEP-9](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0009.md).
In turn, the anchor will pre-fill this information in the interactive popup.

```php
$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";

$naturalPersonFields = new NaturalPersonKYCFields();
$naturalPersonFields->firstName = "John";
$naturalPersonFields->lastName = "Doe";
$naturalPersonFields->mobileNumber = "(718) 454-7453";
$naturalPersonFields->photoIdBack = $idBackImgData;

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $naturalPersonFields;

$request->kycFields = $kycFields;

$response = $interactiveService->deposit($request);
```

### Changing Stellar Transfer Account

By default, the Stellar transfer will be sent to the authenticated account (with a memo) that initiated the deposit.

While in most cases it's acceptable, some wallets may split their accounts. To do so, pass additional account (and optionally a memo):

```php
$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->account = "G...";
$request->memo = "my memo";
$request->memoType = "text";
$response = $interactiveService->deposit($request);
```
Similarly, for a withdrawal, the origin account of the Stellar transaction could be changed.


## Getting Transaction Info

On the typical flow, the wallet would get transaction data to notify users about status updates. This is done via the SEP-24 GET /transaction and GET /transactions endpoint.

```php
$request = new SEP24TransactionsRequest();
$request->jwt = $this->jwtToken;
$request->assetCode = "ETH";
$response = $interactiveService->transactions($request);
$transactions = $response->transactions;
```

Single Transaction:

```php
$request = new SEP24TransactionRequest();
$request->jwt = $this->jwtToken;
$request->stellarTransactionId = "17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a";
$response = $interactiveService->transaction($request);
$transaction = $response->transaction;
```

### Further readings

For more info, see also the class documentation of  [InteractiveService](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDK/SEP/Interactive/InteractiveService.php)  and the SDK's [SEP-24 test cases](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SEP024Test.php).

