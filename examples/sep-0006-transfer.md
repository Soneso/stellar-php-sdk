
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
// uses the jwt token received from stellar web auth - sep-0010
$response = $transferService->info($jwtToken, "en");
print($response->getFeeInfo()->isEnabled());
```



### Deposit

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#deposit)) is used when a user sends an external token (BTC via Bitcoin, USD via bank transfer, etc...) to an address held by an anchor. With the php sdk you can use the ```deposit``` method of your ```TransferServerService``` instance to get the deposit information:

```php
$request = new DepositRequest();
$request->jwt = $jwtToken; // jwt token received from stellar web auth - sep-0010
$request->assetCode = "BTC";
$request->account = $accountId; // The stellar account ID of the user that wants to deposit.
// ...

try {
    $response = $transferService->deposit($request);
    print($response->getHow() . PHP_EOL);
    print($response->getFeeFixed() . PHP_EOL);
} catch (CustomerInformationNeededException $e) {
    print_r($e->getResponse()->getFields());
} catch (CustomerInformationStatusException $e) {
    print($e->getResponse()->getStatus() . PHP_EOL);
}
// ...
```



### Withdraw

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#withdraw)) is used when a user redeems an asset currently on the Stellar network for it's equivalent off-chain asset via the Anchor. For instance, a user redeeming their NGNT in exchange for fiat NGN. With the php sdk you can use the ```withdraw``` method of your ```TransferServerService``` instance to get the withdrawal information:

```php
$request = new WithdrawRequest();
$request->jwt = $jwtToken; // jwt token received from stellar web auth - sep-0010
$request->assetCode = "NGNT";
$request->amount = "129.01";
// ...
try {
    $response = $transferService->withdraw($request);
    print($response->getAccountId() . PHP_EOL);
    print($response->getFeeFixed() . PHP_EOL);
} catch (CustomerInformationNeededException $e) {
    print_r($e->getResponse()->getFields());
} catch (CustomerInformationStatusException $e) {
    print($e->getResponse()->getStatus() . PHP_EOL);
}
// ...
```



### Fee

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#fee)) allows an anchor to report the fee that would be charged for a given deposit or withdraw operation. With the php sdk you can use the ```fee``` method of your ```TransferServerService``` instance to get the info if supported by the anchor:

```php
$request = new FeeRequest();
$request->jwt = $jwtToken; // jwt token received from stellar web auth - sep-0010
$request->operation = "deposit";
$request->assetCode = "NGN";
$request->amount = 123.09;
// ...

$response = $transferService->fee($request);
print($response->getFee());
```



### Transaction History

From this endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#transaction-history)) wallets can receive the status of deposits and withdrawals while they process and a history of past transactions with the anchor. With the php sdk you can use the ```transactions``` method of your ```TransferServerService``` instance to get the transactions:

```php
$request = new AnchorTransactionsRequest();
$request->jwt = $jwtToken; // jwt token received from stellar web auth - sep-0010
$request->account = "GCTTGO5ABSTHABXWL2FMHPZ2XFOZDXJYJN5CKFRKXMPAAWZW3Y3JZ3JK";
$request->assetCode = "XLM";

$response = $transferService->transactions($request);
print(count($response->getTransactions()) . PHP_EOL);
print($response->getTransactions()[0]->getId() . PHP_EOL);
// ...
```



### Single Historical Transaction

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#single-historical-transaction)) enables clients to query/validate a specific transaction at an anchor. With the php sdk you can use the ```transaction``` method of your ```TransferServerService``` instance to get the data:

```php
$request = new AnchorTransactionRequest();
$request->jwt = $jwtToken; // jwt token received from stellar web auth - sep-0010
$request->id = "82fhs729f63dh0v4";

$response = $transferService->transaction($request);
print($response->getTransaction()->getKind() .  PHP_EOL);
print($response->getTransaction()->getStatus() .  PHP_EOL);
// ...
```



### Update Transaction

This endpoint (described [here](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md#update)) is used when the anchor requests more info via the pending_transaction_info_update status. With the php sdk you can use the ```patchTransaction``` method of your ```TransferServerService``` instance to update the data:

```php
$request = new PatchTransactionRequest();
$request->jwt = $jwtToken; // jwt token received from stellar web auth - sep-0010
$request->id = "82fhs729f63dh0v4";
$request->fields = array();
$request->fields["dest"] = "12345678901234";
$request->fields["dest_extra"] = "021000021";

$response = $transferService->patchTransaction($request);
print($response->getStatusCode());
// ...
```

### Further readings

For more info, see also the sdk's sep-0006 test cases.

