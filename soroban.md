
## [Stellar SDK for PHP](https://github.com/Soneso/stellar-php-sdk) 
## Soroban support

The following shows you how to use the PHP SDK to start experimenting with Soroban smart contracts. 

**Please note, that both, Soroban itself and the PHP SDK support for Soroban are still under development, so breaking changes may occur.**


### Quick Start

PHP SDK Soroban support allows you to deploy and to invoke smart contracts on Futurenet. Futurenet is a special test network provided by Stellar.

To deploy and/or invoke smart contracts with the PHP SDK use the ```SorobanServer``` class. It connects to a given local or remote Soroban-RPC Server.

Soroban-RPC can be simply described as a “live network gateway for Soroban”. It provides information that the network currently has in its view (i.e. current state). It also has the ability to send a transaction to the network and query the network for the status of previously sent transactions.

You can install your own instance of a Soroban-RPC Server as described [here](https://soroban.stellar.org/docs/tutorials/deploy-to-futurenet). Alternatively, you can use a public remote instance for testing.

The Soroban-RPC API is described [here](https://soroban.stellar.org/api/methods).

#### Initialize SorobanServer 

Provide the url to the endpoint of the Soroban-RPC server to connect to:

```php
$server = new SorobanServer("https://rpc-futurenet.stellar.org:443");
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

Next you can fetch current information about your Stellar account using the SDK:

```php
$sdk = StellarSDK::getFutureNetInstance();
$accountResponse = $sdk->requestAccount($accountId);
print("Sequence: ".$getAccountResponse->getSequenceNumber());
```


#### Deploy your contract

If you want to create a smart contract for testing, you can easily build one with our [AssemblyScript Soroban SDK](https://github.com/Soneso/as-soroban-sdk) or with the [official Stellar Rust SDK](https://soroban.stellar.org/docs/examples/hello-world). Here you can find [examples](https://github.com/Soneso/as-soroban-examples) to be build with the AssemblyScript SDK.

There are two main steps involved in the process of deploying a contract. First you need to **upload** the **contract code** and then to **create** the **contract**.

To **upload** the **contract code**, first build a transaction containing the corresponding operation:

```php
// Create the operation for uploading the contract code (*.wasm file content)
$builder = new InvokeHostFunctionOperationBuilder();
$uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
$operation = $builder->addFunction($uploadContractHostFunction)->build();

// Build the transaction
$transaction = (new TransactionBuilder($account))
    ->addOperation($operation)->build();
```

Next we need to **simulate** the transaction to obtain the **soroban transaction data** and the **resource fee** needed for final submission:

```php
// Simulate first to obtain the footprint
$simulateResponse = $server->simulateTransaction($transaction);

$transactionData = $simulateResponse->transactionData;
$minResourceFee = $simulateResponse->minRessourceFee;
```
On success, one can find the **soroban transaction data** and the **minimum resource fee** in the response.

Next we need to set the **soroban transaction data** to our transaction, add the **resource fee** and  **sign** the transaction before sending it to the network using the ```SorobanServer```:

```php
$transaction->setSorobanTransactionData($transactionData);
$transaction->addRessourceFee($minResourceFee);
$transaction->sign($accountKeyPair, Network::futurenet());

// send transaction to soroban rpc server
$sendResponse = $server->sendTransaction($transaction);
```

On success, the response contains the id and status of the transaction:

```php
if ($sendResponse->error == null) {
    print("Transaction Id: ".$sendResponse->hash);
    print("Status: ".$sendResponse->status); // PENDING
}
```

The status is ```pending``` because the transaction needs to be processed by the Soroban-RPC Server first. Therefore we need to wait a bit and poll for the current transaction status by using the ```getTransaction``` request:

```php
// Fetch transaction status
$transactionResponse = $server->getTransaction($transactionId);

$status = $transactionResponse->status;

if (GetTransactionResponse::STATUS_NOT_FOUND == $status) {
    // try again later ...
} else if (GetTransactionResponse::STATUS_SUCCESS == $status) {
    // continue with creating the contract ...
    $contractWasmId = $transactionResponse->getWasmId();
    // ...
} else if (GetTransactionResponse::STATUS_FAILED == $status) {
    // handle error ...
}
```

If the transaction was successful, the status response contains the ```wasmId``` of the installed contract code. We need the ```wasmId``` in our next step to **create** the contract:

```php
// Build the operation for creating the contract
$createContractHostFunction = new CreateContractHostFunction($wasmId);
$builder = new InvokeHostFunctionOperationBuilder();
$operation = $builder->addFunction($createContractHostFunction)->build();

// Build the transaction for creating the contract
$transaction = (new TransactionBuilder($account))
    ->addOperation($operation)->build();

// First simulate to obtain the footprint
$simulateResponse = $server->simulateTransaction($transaction);

// set the transaction data, add fee and sign
$transaction->setSorobanTransactionData($simulateResponse->transactionData);
$transaction->addRessourceFee($simulateResponse->minRessourceFee);
$transaction->sign($accountKeyPair, Network::futurenet());

// Send the transaction to the network.
$sendResponse = $server->sendTransaction($transaction);

if ($sendResponse->error == null) {
    print("Transaction Id :".$sendResponse->hash);
    print("Status: ".$sendResponse->status); // pending
}
```

As you can see, we use the ```wasmId``` to create the operation and the transaction for creating the contract. After simulating, we obtain the transaction data and resource fee for the transaction. Next, sign the transaction and send it to the Soroban-RPC Server. The transaction status will be "pending", so we need to wait a bit and poll for the current status:

```php
// Fetch transaction status
$transactionResponse = $server->getTransactionStatus($transactionId);

$status = $transactionResponse->status;

if (GetTransactionResponse::STATUS_SUCCESS == $status) {
  // contract successfully deployed!
  $contractId = $statusResponse->getContractId();
}
```

Success!

#### Get Ledger Entry

The Soroban-RPC server also provides the possibility to request values of ledger entries directly. It will allow you to directly inspect the current state of a contract, a contract’s code, or any other ledger entry. 

For example, to fetch contract wasm byte-code, use the ContractCode ledger entry key:

```php
$footprint = $simulateResponse->footprint;
$contractCodeKey = $footprint->getContractCodeLedgerKey();

$contractCodeEntryResponse = $server->getLedgerEntry($contractCodeKey);
```

#### Invoking a contract

Now, that we successfully deployed our contract, we are going to invoke it using the PHP SDK.

First let's have a look to a simple (hello word) contract created with the [AssemblyScript Soroban SDK](https://github.com/Soneso/as-soroban-sdk). The code and instructions on how to build it, can be found in this [example](https://github.com/Soneso/as-soroban-examples/tree/main/hello_word).

*Hello Word contract AssemblyScript code:*

```typescript
import {Symbol, VecObject, fromSmallSymbolStr} from 'as-soroban-sdk/lib/value';
import {Vec} from 'as-soroban-sdk/lib/vec';

export function hello(to: Symbol): VecObject {

    let vec = new Vec();
    vec.pushFront(fromSmallSymbolStr("Hello"));
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
$argVal = XdrSCVal::forSymbol("friend");

// Prepare the "invoke" operation
$invokeContractHostFunction = new InvokeContractHostFunction($contractId, "hello", [$argVal]);
$builder = new InvokeHostFunctionOperationBuilder();
$operation = $builder->addFunction($invokeContractHostFunction)->build();

// Build the transaction
$transaction = (new TransactionBuilder($account))
    ->addOperation($operation)->build();
```

Next we need to **simulate** the transaction to obtain the **transaction data** and **resource fee** needed for final submission:

```php
// Simulate first to obtain the transaction data and fee
$simulateResponse = $server->simulateTransaction($transaction);

$transactionData = $simulateResponse->transactionData;
$minResourceFee = $simulateResponse->minRessourceFee;
```
On success, one can find the **transaction data** and the **resource fee** in the response. 

Next we need to set the **soroban transaction data** to our transaction, to add the **resource fee** and **sign** the transaction to send it to the network using the ```SorobanServer```:


```php
$transaction->setSorobanTransactionData($transactionData);
$transaction->addRessourceFee($minResourceFee);
$transaction->sign($accountKeyPair, Network::futurenet());

// Send the transaction to the network.
$sendResponse = $server->sendTransaction($transaction);
```

On success, the response contains the id and status of the transaction:

```php
if ($sendResponse->error == null) {
    print("Transaction Id :".$sendResponse->hash);
    print("Status: ".$sendResponse->status); // pending
}
```

The status is ```pending``` because the transaction needs to be processed by the Soroban-RPC Server first. Therefore we need to wait a bit and poll for the current transaction status by using the ```getTransactionStatus``` request:

```php
// Fetch transaction status
$transactionResponse = $server->getTransactionStatus($transactionId);

$status = $transactionResponse->status;

if (GetTransactionResponse::STATUS_NOT_FOUND == $status) {
    // try again later ...
} else if (GetTransactionResponse::STATUS_SUCCESS == $status) {
    // success
    // ...
} else if (GetTransactionResponse::STATUS_FAILED == $status) {
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
$builder = new InvokeHostFunctionOperationBuilder();
$operation = $builder->addFunction(new DeploySACWithSourceAccountHostFunction())->build();
```

2. Deploy SAC with asset:

```php
$builder = new InvokeHostFunctionOperationBuilder();
$operation = $builder->addFunction(new DeploySACWithAssetHostFunction($iomAsset))->build();
```

#### Soroban Authorization

The PHP SDK provides support for the [Soroban Authorization Framework](https://soroban.stellar.org/docs/learn/authorization).

For this purpose, it offers the `Address`, `AuthorizedInvocation` and `ContractAuth` classes as well as helper functions like `getNonce(...)`.

Here is a code fragment showing how they can be used:

```php
$invokerAddress = Address::fromAccountId($invokerId);
$nonce = $server->getNonce($invokerId, $contractId);

$functionName = "auth";
$args = [$invokerAddress->toXdrSCVal(), XdrSCVal::forU32(3)];

$authInvocation = new AuthorizedInvocation($contractId, $functionName, args: $args);

$contractAuth = new ContractAuth($authInvocation, address: $invokerAddress, nonce: $nonce);
$contractAuth->sign($invokerKeyPair, Network::futurenet());

$invokeHostFunction = new InvokeContractHostFunction($contractId, $functionName, $args, auth: [$contractAuth]);

$builder = new InvokeHostFunctionOperationBuilder();
$invokeOp = $builder->addFunction($invokeHostFunction)->build();

// simulate first to obtain the transaction data and resource fee
$submitterAccount = $server->getAccount($submitterId);
$transaction = (new TransactionBuilder($submitterAccount))
    ->addOperation($invokeOp)->build();

$simulateResponse = $server->simulateTransaction($transaction);
```

The example above invokes this assembly script [auth contract](https://github.com/Soneso/as-soroban-examples/tree/main/auth#code). In this example the submitter of the transaction is not the same as the "invoker" of the contract function. 

One can find another example in the [Soroban Auth Test Cases](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanAuthTest.php) of the SDK where the submitter and invoker are the same.

An advanced auth example can be found in the [atomic swap](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanAtomicSwapTest.php) test.

Hint: Resource values and fees have been added in the new soroban preview 9 version. The calculation of the minimum resource values and fee by the simulation (preflight) is not always accurate, because it does not consider signatures. This may result in a failing transaction because of insufficient resources. In this case one can experiment and increase the resources values within the soroban transaction data before signing and submitting the transaction. E.g.:

```php
$transactionData = $simulateResponse->transactionData;
$transactionData->resources->instructions += intval($transactionData->resources->instructions / 4);
$simulateResponse->minRessourceFee += 2800;

// set the transaction data + fee and sign
$transaction->setSorobanTransactionData($transactionData);
$transaction->addRessourceFee($simulateResponse->minRessourceFee);
$transaction->sign($submitterKeyPair, Network::futurenet());
```

See also: https://discord.com/channels/897514728459468821/1112853306881081354

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

