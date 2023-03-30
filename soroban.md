
## [Stellar SDK for PHP](https://github.com/Soneso/stellar-php-sdk) 
## Soroban support

The following shows you how to use the PHP SDK to start experimenting with Soroban smart contracts. 

**Please note, that both, Soroban itself and the PHP SDK support for Soroban are still under development, so breaking changes may occur.**


### Quick Start

PHP SDK Soroban support allows you to deploy and to invoke smart contracts on Futurenet. Futurenet is a special test network provided by Stellar.

To deploy and/or invoke smart contracts with the PHP SDK use the ```SorobanServer``` class. It connects to a given local or remote Soroban-RPC Server.

Soroban-RPC can be simply described as a “live network gateway for Soroban”. It provides information that the network currently has in its view (i.e. current state). It also has the ability to send a transaction to the network and query the network for the status of previously sent transactions.

You can install your own instance of a Soroban-RPC Server as described [here](https://soroban.stellar.org/docs/tutorials/deploy-to-futurenet). Alternatively, you can use a public remote instance for testing.

The Soroban-RPC API is described in this early stage [design document](https://docs.google.com/document/d/1TZUDgo_3zPz7TiPMMHVW_mtogjLyPL0plvzGMsxSz6A).

#### Initialize SorobanServer 

Provide the url to the endpoint of the Soroban-RPC server to connect to:

```php
$server = new SorobanServer("https://horizon-futurenet.stellar.cash/soroban/rpc");
```

Set the experimental flag to true. Otherwise it will not work.

```php
$server->acknowledgeExperimental = true;
```

#### General node health check
```php
$healthResponse = $server->getHealth();

if (GetHealthResponse::HEALTHY == $healthResponse->status) {
   //...
}
```

#### Get account data

You first need an account on Futurenet. For this one can use ```FuturenetFriendBot``` to fund it:

```php
$accountKeyPair = KeyPair::random();
$accountId = $accountKeyPair->getAccountId();

FuturenetFriendBot::fundTestAccount($accountId);
```

Next you can fetch current information about your Stellar account using the ```SorobanServer```:

```php
$accountResponse = $server->getAccount($accountId);
print("Sequence: ".$accountResponse->sequence);
```


#### Deploy your contract

If you want to create a smart contract for testing, you can easily build one with our [AssemblyScript Soroban SDK](https://github.com/Soneso/as-soroban-sdk) or with the [official Stellar Rust SDK](https://soroban.stellar.org/docs/examples/hello-world). Here you can find [examples](https://github.com/Soneso/as-soroban-examples) to be build with the AssemblyScript SDK.

There are two main steps involved in the process of deploying a contract. First you need to **install** the **contract code** and then to **create** the **contract**.

To **install** the **contract code**, first build a transaction containing the corresponding operation:

```php
// Create the operation for installing the contract code (*.wasm file content)
$operation = InvokeHostFunctionOperationBuilder::
    forInstallingContractCode($contractCode)->build();

// Build the transaction
$transaction = (new TransactionBuilder($account))
    ->addOperation($operation)->build();
```

Next we need to **simulate** the transaction to obtain the **footprint** needed for final submission:

```php
// Simulate first to obtain the footprint
$simulateResponse = $server->simulateTransaction($transaction);

$footprint = $simulateResponse->footprint;
```
On success, one can find the **footprint** in the response. The response also contains other information such as information about the fees expected:

```php
print("cpuInsns: ".$simulateResponse->cost->cpuInsns);
print("memBytes: ".$simulateResponse->cost->memBytes);
```

Next we need to set the **footprint** to our transaction, **sign** the transaction and send it to the network using the ```SorobanServer```:

```php
$transaction->setFootprint($simulateResponse->footprint);
$transaction->sign($accountKeyPair, Network::futurenet());

// send transaction to soroban rpc server
$sendResponse = $server->sendTransaction($transaction);
```

On success, the response contains the id and status of the transaction:

```php
if ($sendResponse->error == null) {
    print("Transaction Id: ".$sendResponse->transactionId);
    print("Status: ".$sendResponse->status); // pending
}
```

The status is ```pending``` because the transaction needs to be processed by the Soroban-RPC Server first. Therefore we need to wait a bit and poll for the current transaction status by using the ```getTransactionStatus``` request:

```php
// Fetch transaction status
$statusResponse = $server->getTransactionStatus($transactionId);

$status = $statusResponse->status;

if (GetTransactionStatusResponse::STATUS_PENDING == $status) {
    // try again later ...
} else if (GetTransactionStatusResponse::STATUS_SUCCESS == $status) {
    // continue with creating the contract ...
    $contractWasmId = $statusResponse->getWasmId();
    // ...
} else if (GetTransactionStatusResponse::STATUS_ERROR == $status) {
    // handle error ...
}
```

If the transaction was successful, the status response contains the ```wasmId``` of the installed contract code. We need the ```wasmId``` in our next step to **create** the contract:

```php
// Build the operation for creating the contract
$operation = InvokeHostFunctionOperationBuilder::
    forCreatingContract($contractWasmId)->build();

// Build the transaction for creating the contract
$transaction = (new TransactionBuilder($account))
    ->addOperation($operation)->build();

// First simulate to obtain the footprint
$simulateResponse = $server->simulateTransaction($transaction);

$footprint = $simulateResponse->footprint;

// Set footprint & sign
$transaction->setFootprint($footprint);
$transaction->sign($accountKeyPair, Network::futurenet());

// Send the transaction to the network.
$sendResponse = $server->sendTransaction($transaction);

if ($sendResponse->error == null) {
    print("Transaction Id :".$sendResponse->transactionId);
    print("Status: ".$sendResponse->status); // pending
}
```

As you can see, we use the ```wasmId``` to create the operation and the transaction for creating the contract. After simulating, we obtain the footprint to be set in the transaction. Next, sign the transaction and send it to the Soroban-RPC Server. The transaction status will be "pending", so we need to wait a bit and poll for the current status:

```php
// Fetch transaction status
$statusResponse = $server->getTransactionStatus($transactionId);

$status = $statusResponse->status;

if (GetTransactionStatusResponse::STATUS_SUCCESS == $status) {
  // contract successfully deployed!
  $contractId = $statusResponse->getContractId();
}
```

Success!

#### Get Ledger Entry

The Soroban-RPC server also provides the possibility to request values of ledger entries directly. It will allow you to directly inspect the current state of a contract, a contract’s code, or any other ledger entry. 

For example, to fetch contract wasm byte-code, use the ContractCode ledger entry key:

```php
$contractCodeKey = $footprint->getContractCodeLedgerKey();

$contractCodeEntryResponse = $server->getLedgerEntry($contractCodeKey);
```

#### Invoking a contract

Now, that we successfully deployed our contract, we are going to invoke it using the PHP SDK.

First let's have a look to a simple (hello word) contract created with the [AssemblyScript Soroban SDK](https://github.com/Soneso/as-soroban-sdk). The code and instructions on how to build it, can be found in this [example](https://github.com/Soneso/as-soroban-examples/tree/main/hello_word).

*Hello Word contract AssemblyScript code:*

```typescript
import {SymbolVal, VectorObject, fromSymbolStr} from 'as-soroban-sdk/lib/value';
import {Vec} from 'as-soroban-sdk/lib/vec';

export function hello(to: SymbolVal): VectorObject {

  let vec = new Vec();
  vec.pushFront(fromSymbolStr("Hello"));
  vec.pushBack(to);
  
  return vec.getHostObject();
}
```

It's only function is called ```hello``` and it accepts a ```symbol``` as an argument. It returns a ```vector``` containing two symbols.

To invoke the contract with the PHP SDK, we first need to build the corresponding operation and transaction:


```php
// Name of the method to be invoked
$method = "hello";

// Prepare the argument (Symbol)
$argVal = XdrSCVal::fromSymbol("friend");

// Prepare the "invoke" operation
$operation = InvokeHostFunctionOperationBuilder::
    forInvokingContract($contractId, $method, [$argVal])->build();

// Build the transaction
$transaction = (new TransactionBuilder($account))
    ->addOperation($operation)->build();
```

Next we need to **simulate** the transaction to obtain the **footprint** needed for final submission:

```php
// Simulate first to obtain the footprint
$simulateResponse = $server->simulateTransaction($transaction);

$footprint = $simulateResponse->footprint;
```
On success, one can find the **footprint** in the response. The response also contains other information such as information about the fees expected:

```php
print("cpuInsns: ".$simulateResponse->cost->cpuInsns);
print("memBytes: ".$simulateResponse->cost->memBytes);
```

Next we need to set the **footprint** to our transaction, **sign** the transaction and send it to the network using the ```SorobanServer```:

```php
// Set footprint & sign
$transaction->setFootprint($footprint);
$transaction->sign($accountKeyPair, Network::futurenet());

// Send the transaction to the network.
$sendResponse = $server->sendTransaction($transaction);
```

On success, the response contains the id and status of the transaction:

```php
if ($sendResponse->error == null) {
    print("Transaction Id :".$sendResponse->transactionId);
    print("Status: ".$sendResponse->status); // pending
}
```

The status is ```pending``` because the transaction needs to be processed by the Soroban-RPC Server first. Therefore we need to wait a bit and poll for the current transaction status by using the ```getTransactionStatus``` request:

```php
// Fetch transaction status
$statusResponse = $server->getTransactionStatus($transactionId);

$status = $statusResponse->status;

if (GetTransactionStatusResponse::STATUS_PENDING == $status) {
    // try again later ...
} else if (GetTransactionStatusResponse::STATUS_SUCCESS == $status) {
    // success ...
} else if (GetTransactionStatusResponse::STATUS_ERROR == $status) {
    // handle error ...
}
```

If the transaction was successful, the status response contains the result:

```php
$resVal = $statusResponse->getResultValue();

// Extract the Vector
$vec = $resVal->getVec();

// Print result
if ($vec != null && count($vec) > 1) {
  print("[".$vec[0]->sym.", ".$vec[1]->sym."]");
  // [Hello, friend]
}
```

Success!

#### Deploying Stellar Asset Contract (SAC)

The PHP SDK also provides support for deploying the build-in [Stellar Asset Contract](https://soroban.stellar.org/docs/built-in-contracts/stellar-asset-contract) (SAC). The following operations are available for this purpose:

1. Deploy SAC with source account:

```php
$operation = InvokeHostFunctionOperationBuilder::
    forDeploySACWithSourceAccount()->build();
```

2. Deploy SAC with asset:

```php
$operation = InvokeHostFunctionOperationBuilder::
    forDeploySACWithAsset($asset)->build();
```

#### Soroban Authorization

The PHP SDK provides support for the [Soroban Authorization Framework](https://soroban.stellar.org/docs/learn/authorization).

For this purpose, it offers the `Address`, `AuthorizedInvocation` and `ContractAuth` classes as well as helper functions like `getNonce(...)`.

Here is a code fragment showing how they can be used:

```php
$invokerAddress = Address::fromAccountId($invokerId);
$nonce = $server->getNonce($invokerId, $contractId);

$functionName = "auth";
$args = [$invokerAddress->toXdrSCVal(), XdrSCVal::fromU32(3)];

$authInvocation = new AuthorizedInvocation($contractId, $functionName, args: $args);

$contractAuth = new ContractAuth($authInvocation, address: $invokerAddress, nonce: $nonce);
$contractAuth->sign($invokerKeyPair, Network::futurenet());

$invokeOp = InvokeHostFunctionOperationBuilder::forInvokingContract($contractId,
    $functionName, $args, auth: [$contractAuth])->build();

// simulate first to obtain the footprint
$submitterAccount = $server->getAccount($submitterId);
$transaction = (new TransactionBuilder($submitterAccount))
    ->addOperation($invokeOp)->build();

$simulateResponse = $server->simulateTransaction($transaction);
```

The example above invokes this assembly script [auth contract](https://github.com/Soneso/as-soroban-examples/tree/main/auth#code).

Other auth examples can be found in the [Soroban Auth Test Cases](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanAuthTest.php) of the SDK.

An advanced auth example can be found in the [atomic swap](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanAtomicSwapTest.php) test.

#### Get Events

The Soroban-RPC server provides the possibility to request contract events. 

You can use the PHP SDK to request events like this:

```php
$eventFilter = new EventFilter("contract", [$contractId]);
$eventFilters = new EventFilters();
$eventFilters->add($eventFilter);

$request = new GetEventsRequest($startLedger, $endLedger, $eventFilters);
$response = $server->getEvents($request);
```
Find the complete code [here](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanTest.php#L579).

#### Hints and Tips

You can find the working code and more in the [Soroban Test Cases](https://github.com/Soneso/stellar-php-sdk/tree/main/Soneso/StellarSDKTests/SorobanTest.php) and [Soroban Auth Test Cases](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanAuthTest.php#L30) of the PHP SDK. The wasm byte-code files can be found in the [test/wasm](https://github.com/Soneso/stellar-php-sdk/tree/main/Soneso/StellarSDKTests/wasm/) folder.

Because Soroban and the PHP SDK support for Soroban are in development, errors may occur. For a better understanding of an error you can enable the ```SorobanServer``` logging:

```php
$server->enableLogging = true;
```
This will log the responses received from the Soroban-RPC server.

If you find any issues please report them [here](https://github.com/Soneso/stellar-php-sdk/issues). It will help us to improve the SDK.

