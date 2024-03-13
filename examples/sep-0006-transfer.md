
### SEP-0006 - TransferServerService

Helps clients to interact with anchors in a standard way defined by [SEP-0006: Deposit and Withdrawal API](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md).



### Create a TransferServerService instance

**By providing the domain hosting the stellar.toml file**

```php
$transferService = TransferServerService::fromDomain("place.domain.com");
```

This will automatically load and parse the stellar.toml file. It will then create the TransferServerService instance by using the transfer server url provided in the stellar.toml file.

**Or by providing the service url**

Alternatively one can create a TransferServerService instance by providing the transfer server url directly via the constructor:

```php
$transferService = new TransferServerService("http://api.stellar-anchor.org/transfer");
```



### Info

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#info)) allows an anchor to communicate basic info about what their TRANSFER_SERVER supports to wallets and clients. With the php sdk you can use the ```info``` method of your ```TransferServerService``` instance to get the info:

```php
$response = $transferService->info();
print($response->feeInfo->enabled);
```



### Deposit

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#deposit)) is used when a user sends an external token (BTC via Bitcoin, USD via bank transfer, etc...) to an address held by an anchor. With the php sdk you can use the ```deposit``` method of your ```TransferServerService``` instance to get the deposit information:

```php
$request = new DepositRequest(
                assetCode: "USD",
                account: $accountId,
                jwt: $jwtToken);
// ...

try {
    $response = $transferService->deposit($request);
    print($response->how . PHP_EOL);
    print($response->feeFixed . PHP_EOL);
} catch (CustomerInformationNeededException $e) {
    print_r($e->response->fields);
} catch (CustomerInformationStatusException $e) {
    print($e->response->status . PHP_EOL);
}
// ...
```



### Withdraw

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#withdraw)) is used when a user redeems an asset currently on the Stellar network for it's equivalent off-chain asset via the Anchor. For instance, a user redeeming their NGNT in exchange for fiat NGN. With the php sdk you can use the ```withdraw``` method of your ```TransferServerService``` instance to get the withdrawal information:

```php
$request = new WithdrawRequest(
                assetCode: "NGNT",
                type: "bank_account",
                jwt: $jwtToken);
// ...
try {
    $response = $transferService->withdraw($request);
    print($response->accountId . PHP_EOL);
    print($response->feeFixed . PHP_EOL);
} catch (CustomerInformationNeededException $e) {
    print_r($e->response->fields);
} catch (CustomerInformationStatusException $e) {
    print($e->response->status . PHP_EOL);
}
// ...
```


### Deposit-Exchange

If the anchor supports SEP-38 quotes, it can provide a deposit that makes a bridge between non-equivalent tokens by receiving, for instance BRL via bank transfer and in return sending the equivalent value (minus fees) as USDC to the user's Stellar account.

The /deposit-exchange endpoint allows a wallet to get deposit information from an anchor when the user intends to make a conversion between non-equivalent tokens. With this endpoint, described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#deposit-exchange), a user has all the information needed to initiate a deposit and it also lets the anchor specify additional information (if desired) that the user must submit via SEP-12.

```php
$request = new DepositExchangeRequest(
                destinationAsset: 'USDC',
                sourceAsset: 'iso4217:BRA',
                amount: '480.00',
                account: $accountId,
                quoteId: '282837',
                jwt: $jwtToken);

$response = $transferService->depositExchange($request);
$instructions = $response->instructions;
//...
```

### Withdraw-Exchange

If the anchor supports SEP-38 quotes, it can provide a withdraw that makes a bridge between non-equivalent tokens by receiving, for instance USDC from the Stellar network and in return sending the equivalent value (minus fees) as NGN to the user's bank account.

The /withdraw-exchange endpoint allows a wallet to get withdraw information from an anchor when the user intends to make a conversion between non-equivalent tokens. With this endpoint, a user has all the information needed to initiate a withdraw and it also lets the anchor specify additional information (if desired) that the user must submit via SEP-12.

```php
$request = new WithdrawExchangeRequest(
                sourceAsset: 'USDC',
                destinationAsset: 'iso4217:NGN',
                amount: '700',
                type: 'bank_account',
                quoteId: '282837',
                jwt: $jwtToken);

$response = $transferService->withdrawExchange($request);
print($response->accountId);
//...
```


### Fee

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#fee)) allows an anchor to report the fee that would be charged for a given deposit or withdraw operation. With the php sdk you can use the ```fee``` method of your ```TransferServerService``` instance to get the info if supported by the anchor:

```php
$request = new FeeRequest(
            operation: "deposit",
            assetCode: "NGN",
            amount: 123.09);
// ...

$response = $transferService->fee($request);
print($response->fee);
```



### Transaction History

From this endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#transaction-history)) wallets can receive the status of deposits and withdrawals while they process and a history of past transactions with the anchor. With the php sdk you can use the ```transactions``` method of your ```TransferServerService``` instance to get the transactions:

```php
$request = new AnchorTransactionsRequest(
                assetCode: "XLM",
                account: "GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK",
                jwt: $jwtToken);

$response = $transferService->transactions($request);
print(count($response->transactions) . PHP_EOL);
print($response->transactions[0]->id . PHP_EOL);
// ...
```



### Single Historical Transaction

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#single-historical-transaction)) enables clients to query/validate a specific transaction at an anchor. With the php sdk you can use the ```transaction``` method of your ```TransferServerService``` instance to get the data:

```php
$request = new AnchorTransactionRequest();
$request->jwt = $jwtToken; // jwt token received from stellar web auth - sep-0010
$request->id = "82fhs729f63dh0v4";

$response = $transferService->transaction($request);
print($response->transaction->kind .  PHP_EOL);
print($response->transaction->status .  PHP_EOL);
// ...
```



### Update Transaction

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#update)) is used when the anchor requests more info via the pending_transaction_info_update status. With the php sdk you can use the ```patchTransaction``` method of your ```TransferServerService``` instance to update the data:

```php
$fields = array();
$fields["dest"] = "12345678901234";
$fields["dest_extra"] = "021000021";

$request = new PatchTransactionRequest(
    id: "82fhs729f63dh0v4",
    fields: $fields,
    jwt: $jwtToken,
);

$response = $transferService->patchTransaction($request);
print($response->statusCode);
// ...
```

### Further readings

For more info, see also the class documentation of  [`TransferServerService`](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDK/SEP/TransferServerService/TransferServerService.php)  
and the sdk's [SEP-0006 test cases](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SEP006Test.php).


