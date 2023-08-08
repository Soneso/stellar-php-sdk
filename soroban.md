
## [Stellar SDK for PHP](https://github.com/Soneso/stellar-php-sdk) 
## Soroban support

The following shows you how to use the PHP SDK to start experimenting with Soroban smart contracts. 

**Please note, that both, Soroban itself and the PHP SDK support for Soroban are still under development, so breaking changes may occur.**

**Soroban version supported: Preview 10**

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

If you want to create a smart contract for testing, you can find the official examples [here](https://github.com/stellar/soroban-examples).

There are two main steps involved in the process of deploying a contract. First you need to **upload** the **contract code** and then to **create** the **contract**.

To **upload** the **contract code**, first build a transaction containing the corresponding operation:

```php
// Create the operation for uploading the contract code (*.wasm file content)
$uploadContractHostFunction = new UploadContractWasmHostFunction($contractCode);
$builder = new InvokeHostFunctionOperationBuilder($uploadContractHostFunction);
$operation = $builder->build();

// Build the transaction
$transaction = (new TransactionBuilder($account))
    ->addOperation($operation)->build();
```

Next we need to **simulate** the transaction to obtain the **soroban transaction data** and the **resource fee** needed for final submission:

```php
// Simulate first to obtain the footprint
$simulateResponse = $server->simulateTransaction($transaction);

$transactionData = $simulateResponse->transactionData;
$minResourceFee = $simulateResponse->minResourceFee;
```
On success, one can find the **soroban transaction data** and the **minimum resource fee** in the response.

Next we need to set the **soroban transaction data** to our transaction, add the **resource fee** and  **sign** the transaction before sending it to the network using the ```SorobanServer```:

```php
$transaction->setSorobanTransactionData($transactionData);
$transaction->addResourceFee($minResourceFee);
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

Hint: If you experience an error with the transaction result ```txInternalError``` it is most likely that a ledger entry used in the transaction has expired. This is an issue specific to soroban prev. 10 (see [here](https://discord.com/channels/897514728459468821/1130347673627664515)). You can fix it by restoring the footprint (see this [example](https://github.com/Soneso/stellar-php-sdk/tree/main/Soneso/StellarSDKTests/SorobanTest.php) in the soroban test of the SDK).

If the transaction was successful, the status response contains the ```wasmId``` of the installed contract code. We need the ```wasmId``` in our next step to **create** the contract:

```php
// Build the operation for creating the contract
$createContractHostFunction = new CreateContractHostFunction(Address::fromAccountId($invokerAccountId), $wasmId);
$builder = new InvokeHostFunctionOperationBuilder($createContractHostFunction);
$operation = $builder->build();

// Build the transaction for creating the contract
$transaction = (new TransactionBuilder($account))
    ->addOperation($operation)->build();

// First simulate to obtain the footprint
$simulateResponse = $server->simulateTransaction($transaction);

// set the transaction data & auth, add fee and sign
$transaction->setSorobanTransactionData($simulateResponse->transactionData);
$transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
$transaction->addResourceFee($simulateResponse->minResourceFee);
$transaction->sign($invokerKeyPair, Network::futurenet());

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

```php
$ledgerKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_CODE());
$ledgerKey->contractCode = new XdrLedgerKeyContractCode(hex2bin($wasmId), XdrContractEntryBodyType::DATA_ENTRY());
$ledgerEntry = $this->getLedgerEntry($ledgerKey->toBase64Xdr());
```

If you already have a contractId you can load the code as follows:
```php
$contractCodeEntry = $server->loadContractCodeForContractId($contractId);
if ($contractCodeEntry != null) {
    $loadedSourceCode = $contractCodeEntry->body->code->value;
    $expirationLedgerSeq = $contractCodeEntry->expirationLedgerSeq;
}
```

If you have a wasmId:

```php
$contractCodeEntry = $server->loadContractCodeForWasmId($wasmId);
if ($contractCodeEntry != null) {
    $loadedSourceCode = $contractCodeEntry->body->code->value;
    $expirationLedgerSeq = $contractCodeEntry->expirationLedgerSeq;
}
```


#### Invoking a contract

Now, that we successfully deployed our contract, we are going to invoke it using the PHP SDK.

First let's have a look to a simple (hello word) contract created with the Rust Soroban SDK. The code and instructions on how to build it, can be found in the official [soroban docs](https://soroban.stellar.org/docs/getting-started/hello-world).
*Hello Word contract code:*

```rust
impl HelloContract {
    pub fn hello(env: Env, to: Symbol) -> Vec<Symbol> {
        vec![&env, symbol_short!("Hello"), to]
    }
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
$builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
$operation = $builder->build();

// Build the transaction
$transaction = (new TransactionBuilder($account))
    ->addOperation($operation)->build();
```

Next we need to **simulate** the transaction to obtain the **transaction data** and **resource fee** needed for final submission:

```php
// Simulate first to obtain the transaction data and fee
$simulateResponse = $server->simulateTransaction($transaction);

$transactionData = $simulateResponse->transactionData;
$minResourceFee = $simulateResponse->minResourceFee;
```
On success, one can find the **transaction data** and the **resource fee** in the response. 

Next we need to set the **soroban transaction data** to our transaction, to add the **resource fee** and **sign** the transaction to send it to the network using the ```SorobanServer```:


```php
$transaction->setSorobanTransactionData($transactionData);
$transaction->addResourceFee($minResourceFee);
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

The PHP SDK also provides support for deploying the build-in [Stellar Asset Contract](https://soroban.stellar.org/docs/advanced-tutorials/stellar-asset-contract) (SAC). The following operations are available for this purpose:

1. Deploy SAC with source account:

```php
$hostFunction = new DeploySACWithSourceAccountHostFunction(Address::fromAccountId($invokerAccountId));
$builder = new InvokeHostFunctionOperationBuilder($hostFunction);
$operation = $builder->build();

//...
// set the transaction data & auth, add fee and sign
$transaction->setSorobanTransactionData($simulateResponse->transactionData);
$transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
$transaction->addResourceFee($simulateResponse->minResourceFee);
$transaction->sign($invokerKeyPair, Network::futurenet());
```

2. Deploy SAC with asset:

```php
$hostFunction = new DeploySACWithAssetHostFunction($iomAsset);
$builder = new InvokeHostFunctionOperationBuilder($hostFunction);
$operation = $builder->build();
```

#### Soroban Authorization

The PHP SDK provides support for the [Soroban Authorization Framework](https://soroban.stellar.org/docs/fundamentals-and-concepts/authorization).

To provide authorization you can add an array of `SorobanAuthorizationEntry` to the transaction before sending it.

```php
$transaction->setSorobanAuth($myAuthArray);
```

The easiest way to do this is to use the auth data generated by the simulation.

```php
$transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
```
But you can also compose the authorization entries by yourself.

If the entries need to be signed you can do it as follows:
```php
$auth = $simulateResponse->getSorobanAuth();
$this->assertNotNull($auth);

$latestLedgerResponse = $server->getLatestLedger();
foreach ($auth as $a) {
    if ($a instanceof  SorobanAuthorizationEntry) {
        $this->assertNotNull($a->credentials->addressCredentials);
        // increase signature expiration ledger
        $a->credentials->addressCredentials->signatureExpirationLedger = $latestLedgerResponse->sequence + 10;
        // sign
        $a->sign($invokerKeyPair, Network::futurenet());
    } else {
        self::fail("invalid auth");
    }
}
$transaction->setSorobanAuth($auth);
```

You can find multiple examples in the [Soroban Auth Test Cases](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanAuthTest.php) and in the [Atomic Swap Test](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanAtomicSwapTest.php) of the SDK.

Hint: Resource values and fees have been added in the new soroban preview 9 version. The calculation of the minimum resource values and fee by the simulation (preflight) is not always accurate, because it does not consider signatures. This may result in a failing transaction because of insufficient resources. In this case one can experiment and increase the resources values within the soroban transaction data before signing and submitting the transaction. E.g.:

```php
$transactionData = $simulateResponse->transactionData;
$transactionData->resources->instructions += intval($transactionData->resources->instructions / 4);
$simulateResponse->minResourceFee += 2800;

// set the transaction data + fee and sign
$transaction->setSorobanTransactionData($transactionData);
$transaction->addResourceFee($simulateResponse->minResourceFee);
$transaction->sign($submitterKeyPair, Network::futurenet());
```

See also: https://discord.com/channels/897514728459468821/1112853306881081354

#### Get Events

The Soroban-RPC server provides the possibility to request contract events. 

You can use the PHP SDK to request events like this:

```php
$topicFilter = new TopicFilter(["*", XdrSCVal::forSymbol("increment")->toBase64Xdr()]);
$topicFilters = new TopicFilters($topicFilter);

$eventFilter = new EventFilter("contract", [$contractId], $topicFilters);
$eventFilters = new EventFilters();
$eventFilters->add($eventFilter);

$request = new GetEventsRequest($startLedger, $eventFilters);
$response = $server->getEvents($request);
```
Find the complete code [here](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanTest.php).

#### Hints and Tips

You can find the working code and more in the [Soroban Test](https://github.com/Soneso/stellar-php-sdk/tree/main/Soneso/StellarSDKTests/SorobanTest.php), [Soroban Auth Test](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanAuthTest.php) and [Atomic Swap Test](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanAtomicSwapTest.php) of the PHP SDK. The wasm byte-code files can be found in the [test/wasm](https://github.com/Soneso/stellar-php-sdk/tree/main/Soneso/StellarSDKTests/wasm/) folder.

Because Soroban and the PHP SDK support for Soroban are in development, errors may occur. For a better understanding of an error you can enable the ```SorobanServer``` logging:

```php
$server->enableLogging = true;
```
This will log the responses received from the Soroban-RPC server.

If you find any issues please report them [here](https://github.com/Soneso/stellar-php-sdk/issues). It will help us to improve the SDK.

