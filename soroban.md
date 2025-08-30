
## [Stellar SDK for PHP](https://github.com/Soneso/stellar-php-sdk) 
# Soroban support

The following shows you how to use the PHP SDK to interact with Soroban. 

## Quick Start

PHP SDK Soroban support allows you to deploy and to invoke Soroban smart contracts.

To interact with a Soroban RPC Server, you can use the [`SorobanServer`](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDK/Soroban/SorobanServer.php) class. It connects to a given local or remote Soroban-RPC Server.

Soroban-RPC can be simply described as a “live network gateway for Soroban”. It provides information that the network currently has in its view (i.e. current state). It also has the ability to send a transaction to the network and query the network for the status of previously sent transactions.

You can install your own instance of a Soroban-RPC Server as described [here](https://soroban.stellar.org/docs/tutorials/deploy-to-futurenet). Alternatively, you can use a public remote instance for testing. The Soroban-RPC API is described [here](https://developers.stellar.org/docs/data/rpc/api-reference).

The easiest way to interact with Soroban smart contract is by using the class [`SorobanClient`](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDK/Soroban/Contract/SorobanClient.php). It helps you to install and deploy smart contracts and to invoke their methods. You can find a more detailed description below. 

## SorobanServer 

Provide the url to the endpoint of the Soroban-RPC server to connect to:

```php
$server = new SorobanServer("https://soroban-testnet.stellar.org");
```

Now you can use your `SorobanServer` instance to access the [API endpoints](https://developers.stellar.org/docs/data/rpc/api-reference/methods) provided by the Soroban RPC server.

### Examples 

General node health check:
```php
$healthResponse = $server->getHealth();

if (GetHealthResponse::HEALTHY == $healthResponse->status) {
   //...
}
```

Fetch current information about your account:

```php
$account = $server->getAccount($accountId);
print("Sequence: " . $account->getSequenceNumber());
```

Fetch the latest ledger sequence:

```php
$response = server->getLatestLedger();
print("latest ledger sequence: " . $response->getSequence());
```

## SorobanClient

The easiest way to interact with Soroban smart contracts is by using the class [`SorobanClient`](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDK/Soroban/Contract/SorobanClient.php). It helps you to install and deploy smart contracts and to invoke their methods.

If you want to create a smart contract for testing, you can find the official examples [here](https://github.com/stellar/soroban-examples).
You can also create smart contracts with our AssemblyScript Soroban SDK. Examples can be found [here](https://github.com/Soneso/as-soroban-examples).

The following chapters show examples of interaction with Soroban smart contracts. Please also take a look at the [`SorobanClientTest`](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanClientTest.php), where you can try out this functionality right away.

### Install a contract

As soon as you have the wasm byte code of a compiled contract you can install it as follows:

```php
$contractCode = file_get_contents($path, false);

$installRequest = new InstallRequest(
    wasmBytes: $contractCode,
    rpcUrl: 'https://...',
    network: Network::testnet(),
    sourceAccountKeyPair: $sourceAccountKeyPair
);

$wasmHash = SorobanClient::install($installRequest);
```

It will return the wasm hash of the installed contract that you can now use to deploy the contract.

### Deploy a contract

As soon as you have the wasm hash of an installed contract, you can deploy an instance of the contract.

Deployment works as follows:

```php
$deployRequest = new DeployRequest(
    rpcUrl: 'https://...',
    network: Network::testnet(),
    sourceAccountKeyPair: $sourceAccountKeyPair,
    wasmHash: $wasmHash,
);

$client = SorobanClient::deploy($deployRequest);
```

It returns an instance of `SorobanClient`, that you can now use to interact with the contract.

### Instance for contract

To create a new instance of `SorobanClient` for an existing contract, you must provide the contract id:

```php
$client = SorobanClient::forClientOptions(new ClientOptions(
            sourceAccountKeyPair: $sourceAccountKeyPair,
            contractId: 'C...'
            network: Network::testnet(),
            rpcUrl: 'https://...')
        );
```

Now you can use the new instance to interact with the contract.

### Invoking a method

As soon as a new instance is created, you can invoke the contract's methods:

```php
$result = $client->invokeMethod(name: "hello", args: [XdrSCVal::forSymbol("friend")]);
```

It will return the result of the method invocation as a `XdrSCVal` object.

For more advanced use cases where you need to manipulate the transaction (e.g. add memo, additional signers, etc.) you can
obtain the [`AssembledTransaction`](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDK/Soroban/Contract/AssembledTransaction.php) before sending it to the Soroban RPC Server
as follows:

```php
$tx = $client->buildInvokeMethodTx(name: $methodName, args: $args);
```

In the following chapter we will discuss how you can use the obtained `AssembledTransaction`.

## AssembledTransaction

The main workhorse of `SorobanClient`. This class is used to wrap a 
transaction-under-construction and provide high-level interfaces to the most 
common workflows, while still providing access to low-level SDK transaction manipulation.

Most of the time, you will not construct an `AssembledTransaction` directly,
but instead receive one as the return value of the `SorobanClient->buildInvokeMethodTx()` method.

Let's look at examples of how to use `AssembledTransaction` for a variety of
use-cases:

### 1. Simple read call

Since these only require simulation, you can get the `result` of the call
right after constructing your `AssembledTransaction`:

```php
$clientOptions = new ClientOptions(
      sourceAccountKeyPair: $sourceAccountKeyPair,
      contractId: 'C123…',
      network: Network::testnet(),
      rpcUrl: 'https://…',
    );

    $txOptions = new AssembledTransactionOptions(
                   clientOptions: $clientOptions,
                   methodOptions: new MethodOptions(),
                   method: 'myReadMethod',
                   arguments: $args);

    $tx = AssembledTransaction::build($options);
    
    $result = $tx->getSimulationData()->returnedValue;
```

While that looks pretty complicated, most of the time you will use this in
conjunction with `SorobanClient`, which simplifies it to:

```php
$result = $client->invokeMethod(name: 'myReadMethod', args: $args);
```

### 2. Simple write call

For write calls that will be simulated and then sent to the network without 
further manipulation, only one more step is needed:

```php
$tx = AssembledTransaction::build($options);
$response = $tx->signAndSend();

if ($response->getStatus() === GetTransactionResponse::STATUS_SUCCESS) {
    $result = $response->getResultValue();
}
```

If you are using it in conjunction with `SorobanClient`:

```php
$result = $client->invokeMethod(name: 'myWriteMethod', args: $args);
```

### 3. More fine-grained control over transaction construction

If you need more control over the transaction before simulating it, you can
set various [`MethodOptions`](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDK/Soroban/Contract/MethodOptions.php) 
when constructing your `AssembledTransaction`. With a `SorobanClient`, this can be passed as an
argument when calling `invokeMethod` or `buildInvokeMethodTx` :

```php
$methodOptions = new MethodOptions(
            fee: 10000,
            timeoutInSeconds: 20,
            simulate: false,
);

$tx = $client->buildInvokeMethodTx(name: 'myWriteMethod', 
                                   args: $args, 
                                   methodOptions: $methodOptions);
```

Since we've skipped simulation, we can now edit the `raw` transaction builder and then manually call `simulate`:

```php
$tx->raw->addMemo(Memo::text("Hello!"));
$tx->simulate();
```

If you need to inspect the simulation later, you can access it with `$tx->getSimulationData()`.


### 4. Multi-auth workflows

Soroban, and Stellar in general, allows multiple parties to sign a transaction.

Let's consider an Atomic Swap contract. Alice wants to give some of her Token A tokens to Bob for some of his Token B tokens.

 ```php
$swapMethodName = "swap";

// Using the new BigInt support - much simpler!
$amountA = XdrSCVal::forI128BigInt(1000);
$minBForA = XdrSCVal::forI128BigInt(4500);

$amountB = XdrSCVal::forI128BigInt(5000);
$minAForB = XdrSCVal::forI128BigInt(950);

// Or using the legacy method (still supported)
// $amountA = XdrSCVal::forI128(new XdrInt128Parts(0,1000));
// $minBForA = XdrSCVal::forI128(new XdrInt128Parts(0,4500));

$args = [
          Address::fromAccountId($aliceAccountId)->toXdrSCVal(),
          Address::fromAccountId($bobAccountId)->toXdrSCVal(),
          Address::fromContractId($tokenAContractId)->toXdrSCVal(),
          Address::fromContractId($tokenBContractId)->toXdrSCVal(),
          $amountA,
          $minBForA,
          $amountB,
          $minAForB,
        ];
```

Let's say Alice is also going to be the one signing the final transaction envelope, meaning she is the invoker. 
So your app, she simulates the `swap` call:

```php
$tx = $atomicSwapClient->buildInvokeMethodTx(name: $swapMethodName, args: $args);
```

But your app can't `signAndSend` this right away, because Bob needs to sign it first. You can check this:

```php
$whoElseNeedsToSign = tx->needsNonInvokerSigningBy()
```

You can verify that `$whoElseNeedsToSign` is an array of length `1`, containing only Bob's public key.

If you have Bob's secret key, you can sign it right away with:

```php
$bobsKeyPair = $bobsKeypair = KeyPair::fromSeed('S...')
$tx->signAuthEntries(signerKeyPair: $bobsKeyPair);
```

But if you don't have Bob's private key, and e.g. need to send it to another server for signing,
you can provide a callback function for signing the auth entry:

```php
$bobsPublicKeyPair = KeyPair::fromAccountId($bobsAccountId);

$tx->signAuthEntries(signerKeyPair: $bobPublicKeyPair,
                     authorizeEntryCallback: 
    function (SorobanAuthorizationEntry $entry, Network $network) : SorobanAuthorizationEntry  {

        // You can send it to some other server for signing by encoding it as a base64xdr string
        $base64Entry = $entry->toBase64Xdr();
        // send for signing ...
        // and on the other server you can decode it:
        $entryToSign = SorobanAuthorizationEntry::fromBase64Xdr($base64Entry);
        // sign it (the other server)
        $entryToSign->sign($bobsSecretKeyPair, $network);
         // encode as a base64xdr string and send it back
        $signedBase64Entry = $entryToSign->toBase64Xdr();
        // here you can now decode it and return it
        return SorobanAuthorizationEntry::fromBase64Xdr($signedBase64Entry);
  },
);
```

To see an even more complicated example, where Alice swaps with Bob but the
transaction is invoked by yet another party, check out the [`SorobanClientTest.testAtomicSwap`](https://github.com/Soneso/stellar-php-sdk/blob/7d2d3e30258035db04a73de3a3c5e74ea6fad264/Soneso/StellarSDKTests/SorobanClientTest.php#L123) test case.

## Contract Spec

The `ContractSpec` class offers a range of useful functions based on the contract spec entries of a contract.
It can be used to find specific entries from the contract specification and, more importantly,
to easily prepare the arguments to invoke the contract functions.

The class is initialized with the contract spec entries from the soroban client:

```php
$spec = new ContractSpec($atomicSwapClient->getSpecEntries());
```

After initialization, certain spec entries or functions can be found, for example:

```php
$functions = $spec->funcs();
$func = $spec->getFunc("swap");
$unionEntry = $spec->findEntry("myUnion");
```

More important, however, is the ability to easily prepare the arguments for invoking contract functions.
The arguments for invoking the functions must be of type `XdrSCVal`. 

These can either be constructed manually or with the support of the `ContractSpec` class.

Example of manual construction:

 ```php
$swapMethodName = "swap";

// Using the new BigInt support - much simpler!
$amountA = XdrSCVal::forI128BigInt(1000);
$minBForA = XdrSCVal::forI128BigInt(4500);

$amountB = XdrSCVal::forI128BigInt(5000);
$minAForB = XdrSCVal::forI128BigInt(950);

// Or using the legacy method (still supported)
// $amountA = XdrSCVal::forI128(new XdrInt128Parts(0,1000));
// $minBForA = XdrSCVal::forI128(new XdrInt128Parts(0,4500));

$args = [
          Address::fromAccountId($aliceAccountId)->toXdrSCVal(),
          Address::fromAccountId($bobAccountId)->toXdrSCVal(),
          Address::fromContractId($tokenAContractId)->toXdrSCVal(),
          Address::fromContractId($tokenBContractId)->toXdrSCVal(),
          $amountA,
          $minBForA,
          $amountB,
          $minAForB,
        ];
```

Example using the `ContractSpec` class:

```php
$args = $spec->funcArgsToXdrSCValues(name: $swapMethodName, args: [
            "a" => $aliceId,
            "b" => $bobId,
            "token_a" => $tokenAContractId,
            "token_b" => $tokenBContractId,
            "amount_a" => 1000,
            "min_b_for_a" => 4500,
            "amount_b" => 5000,
            "min_a_for_b" => 950
        ]);
```

The conversion of native values to `XdrSCVal` is based on the contract spec entries of the contract
and can be done via the method `funcArgsToXdrSCValues` or individually via the method `nativeToXdrSCVal(mixed $val, XdrSCSpecTypeDef $ty)`:

```php
// examples for nativeToXdrSCVal:

$def = XdrSCSpecTypeDef::ADDRESS(); // self defined
$val = $spec->nativeToXdrSCVal("CCCZVCWISWKWZ3NNH737WGOVCDUI3P776QE3ZM7AUWMJKQBHCPW7NW3D", $def);

// or
$def = XdrSCSpecTypeDef::forUDT(new XdrSCSpecTypeUDT(name: "myStruct")); // myStruct is in the spec entries of the contract.
$val = $spec->nativeToXdrSCVal(["field1" => 1,"field2" => 2,"field3" => 3], $def);

// example for funcArgsToXdrSCValues:
$args = $spec->funcArgsToXdrSCValues("myFunc", [
    "admin" => "CCCZVCWISWKWZ3NNH737WGOVCDUI3P776QE3ZM7AUWMJKQBHCPW7NW3D" 
]);
```

### Supported values

Next, we will go through the individual supported value types using examples. 
We will use the `nativeToXdrSCVal` method for a better understanding. 
Of course, these also apply to the method `funcArgsToXdrSCValues` where the type definitions are 
already included in the spec of the contract function. 

For our examples, we will create the type definitions ourselves in order to better explain the context.

#### Void

To obtain an `XdrScVal` of type void, the native value `null` can be passed:

```php
// prepare def (this is not needed for funcArgsToXdrSCValues)
$def = XdrSCSpecTypeDef::VOID();

// convert null to XdrScVal of type void
$val = $spec->nativeToXdrSCVal(null, $def);
$this->assertEquals(XdrSCValType::SCV_VOID, $val->type->value);
```

#### Addresses 

To obtain an `XDRSCVal` object of type address, either a string or an `Address` object can be passed.
Both account ids and contract ids are supported.

```php
// prepare def (this is not needed for funcArgsToXdrSCValues)
$def = XdrSCSpecTypeDef::ADDRESS();

// convert
$accountId = "GB6AXVJOIWOEOH4EA6ZT24ZJ5XNVOQUJK4PBAEOFNG44VKROWLDA65DB"
$val = $spec->nativeToXdrSCVal($accountId, $def);

$contractId = "CCCZVCWISWKWZ3NNH737WGOVCDUI3P776QE3ZM7AUWMJKQBHCPW7NW3D";
$val = $spec->nativeToXdrSCVal($accountId, $def);

// or
$address = Address::fromAccountId($accountId);
$val = $spec->nativeToXdrSCVal($address, $def);

$address = Address::fromAccountId($contractId);
$val = $spec->nativeToXdrSCVal($address, $def);
```

#### Vectors

To obtain an `XDRSCVal` object of type vec, an array of native values must be passed:

```php
// prepare def (this is not needed for funcArgsToXdrSCValues)
$def = XdrSCSpecTypeDef::forVec(new XdrSCSpecTypeVec(elementType: XdrSCSpecTypeDef::SYMBOL()));

// convert
$val = $spec->nativeToXdrSCVal(["a", "b"], $def);
$this->assertEquals(XdrSCValType::SCV_VEC, $val->type->value);
$this->assertCount(2, $val->vec);
```

#### Maps

To obtain an `XDRSCVal` object of type map, an array of native key value pairs must be passed:

```php
// prepare def (this is not needed for funcArgsToXdrSCValues)
$map = new XdrSCSpecTypeMap(keyType: XdrSCSpecTypeDef::STRING(), valueType: XdrSCSpecTypeDef::ADDRESS());
$def = XdrSCSpecTypeDef::forMap($map);

// convert
$val = $spec->nativeToXdrSCVal(["a" => $accountId, "b" => $contractId], $def);
$this->assertEquals(XdrSCValType::SCV_MAP, $val->type->value);
```

Since PHP only accepts `int` or `string` as key values, only `int` or `string` can be accepted as native input keys here.
If a resulting key must be an `XdrSCVal` object that cannot be constructed from an `int` or `string`, 
the `XdrSCVal` object of type map must be constructed manually (or separately) and cannot be obtained 
via the `nativeToXdrSCVal` function (e.g. key of type bool, union, other map or vec).

For the function `funcArgsToXdrSCValues`, the manually (or separately) constructed `XdrSCVal` object of 
type map must be passed in the list of arguments if its kay can not be constructed from int or string.

#### Tuple

To obtain an `XDRSCVal` object of type tuple, an array of native values must be passed:

```php
// prepare def (this is not needed for funcArgsToXdrSCValues)
$tuple = new XdrSCSpecTypeTuple(valueTypes: [XdrSCSpecTypeDef::STRING(), XdrSCSpecTypeDef::BOOL()]);
$def = XdrSCSpecTypeDef::forTuple($tuple);

// convert
$val = $spec->nativeToXdrSCVal(["a", true], $def);
$this->assertEquals(XdrSCValType::SCV_VEC, $val->type->value);
```

#### Numbers

To obtain an `XDRSCVal` object of type u32, i32, u64, i64, a native int values must be passed:

```php
// prepare def (this is not needed for funcArgsToXdrSCValues)
$def = XdrSCSpecTypeDef::U32();

// convert
$val = $spec->nativeToXdrSCVal(12, $def);
$this->assertEquals(XdrSCValType::SCV_U32, $val->type->value);

$def = XdrSCSpecTypeDef::I32();
$val = $spec->nativeToXdrSCVal(-12, $def);
$this->assertEquals(XdrSCValType::SCV_I32, $val->type->value);

$def = XdrSCSpecTypeDef::U64();
$val = $spec->nativeToXdrSCVal(112, $def);
$this->assertEquals(XdrSCValType::SCV_U64, $val->type->value);

$def = XdrSCSpecTypeDef::I64();
$val = $spec->nativeToXdrSCVal(-112, $def);
$this->assertEquals(XdrSCValType::SCV_I64, $val->type->value);
```

##### BigInt Support for 128-bit and 256-bit Integers

The SDK now provides full BigInt support for handling 128-bit and 256-bit integers (u128, i128, u256, i256) that exceed PHP's native integer range. You can use GMP resources, strings, or small integers:

```php
// Using string representation for large numbers
$def = XdrSCSpecTypeDef::U128();
$val = $spec->nativeToXdrSCVal("123456789012345678901234567890", $def);
$this->assertEquals(XdrSCValType::SCV_U128, $val->type->value);

// Using GMP for calculations
$bigValue = gmp_pow(2, 100);
$def = XdrSCSpecTypeDef::U256();
$val = $spec->nativeToXdrSCVal($bigValue, $def);
$this->assertEquals(XdrSCValType::SCV_U256, $val->type->value);

// Small integers work as before
$def = XdrSCSpecTypeDef::I128();
$val = $spec->nativeToXdrSCVal(42, $def);
$this->assertEquals(XdrSCValType::SCV_I128, $val->type->value);

// Negative values for signed types
$def = XdrSCSpecTypeDef::I256();
$val = $spec->nativeToXdrSCVal("-999999999999999999999999999999", $def);
$this->assertEquals(XdrSCValType::SCV_I256, $val->type->value);

// Direct BigInt methods on XdrSCVal
$u128Val = XdrSCVal::forU128BigInt("340282366920938463463374607431768211455"); // Max U128
$i128Val = XdrSCVal::forI128BigInt("-170141183460469231731687303715884105728"); // Min I128
$u256Val = XdrSCVal::forU256BigInt(gmp_pow(2, 200));
$i256Val = XdrSCVal::forI256BigInt(gmp_neg(gmp_pow(2, 200)));

// Converting back to BigInt
$bigInt = $u128Val->toBigInt(); // Returns GMP resource
echo gmp_strval($bigInt); // Print as string

// Using with ContractSpec function calls
$args = $spec->funcArgsToXdrSCValues("myFunc", [
    "bob" => $accountId,
    "amount" => "123456789012345678901234567890" // Automatically converted to I128
]);

// Legacy method still supported for backward compatibility
$args = $spec->funcArgsToXdrSCValues("myFunc", [
    "bob" => $accountId, 
    "amount" => XdrSCVal::forI128(new XdrInt128Parts(hi: -1230, lo:81881))
]);
```

#### Bytes and BytesN

`XDRSCVal` objects of type bytes or bytesN, are constructed from native strings (containing the bytes):

```php
// prepare def (this is not needed for funcArgsToXdrSCValues)
$def = XdrSCSpecTypeDef::BYTES();

// convert
$val = $spec->nativeToXdrSCVal($keyPair->getPublicKey(), $def);
$this->assertEquals(XdrSCValType::SCV_BYTES, $val->type->value);

$def = XdrSCSpecTypeDef::forBytesN(new XdrSCSpecTypeBytesN(n:32));
$val = $spec->nativeToXdrSCVal($keyPair->getPublicKey(), $def);
$this->assertEquals(XdrSCValType::SCV_BYTES, $val->type->value);
```

#### String

`XDRSCVal` objects of type string, are constructed from native strings:

```php
// prepare def (this is not needed for funcArgsToXdrSCValues)
$def = XdrSCSpecTypeDef::STRING();

// convert
$val = $spec->nativeToXdrSCVal("hello this is a text", $def);
$this->assertEquals(XdrSCValType::SCV_STRING, $val->type->value);
```

#### Symbol

`XDRSCVal` objects of type symbol, are constructed from native strings:

```php
// prepare def (this is not needed for funcArgsToXdrSCValues)
$def = XdrSCSpecTypeDef::SYMBOL();

// convert
$val = $spec->nativeToXdrSCVal("XLM", $def);
$this->assertEquals(XdrSCValType::SCV_SYMBOL, $val->type->value);
```

#### Bool

`XDRSCVal` objects of type bool, are constructed from native bool:

```php
// prepare def (this is not needed for funcArgsToXdrSCValues)
$def = XdrSCSpecTypeDef::BOOL();

// convert
$val = $spec->nativeToXdrSCVal(false, $def);
$this->assertEquals(XdrSCValType::SCV_BOOL, $val->type->value);
```

#### Option

optional `XDRSCVal` objects:

```php
// prepare def (this is not needed for funcArgsToXdrSCValues)
$def = XdrSCSpecTypeDef::forOption(new XdrSCSpecTypeOption(valueType: XdrSCSpecTypeDef::STRING()));

// convert
$val = $spec->nativeToXdrSCVal("a string", $def);
$this->assertEquals(XdrSCValType::SCV_STRING, $val->type->value);
$val = $spec->nativeToXdrSCVal(null, $def);
$this->assertEquals(XdrSCValType::SCV_VOID, $val->type->value);
```

#### User defined types (enum, struct union)

***Enum:***
```php
// prepare (this is not needed for funcArgsToXdrSCValues)
$cases = [
    new XdrSCSpecUDTEnumCaseV0(doc:"", name:"a", value:1),
    new XdrSCSpecUDTEnumCaseV0(doc:"", name:"b", value:2),
    new XdrSCSpecUDTEnumCaseV0(doc:"", name:"c", value:3),
];
$enum = new XdrSCSpecUDTEnumV0(doc: "", lib: "", name: "myEnum", cases: $cases);
$entry = XdrSCSpecEntry::forUDTEnumV0($enum);
$spec = new ContractSpec(entries:[$entry]);
$def = XdrSCSpecTypeDef::forUDT(new XdrSCSpecTypeUDT(name: "myEnum"));

// convert
$val = $spec->nativeToXdrSCVal(2, $def);
$this->assertEquals(XdrSCValType::SCV_U32, $val->type->value);
$this->assertEquals(2, $val->u32);
```

***Struct (non-numeric fields):***
```php
// prepare (this is not needed for funcArgsToXdrSCValues)
$fields = [
    new XdrSCSpecUDTStructFieldV0(doc:"", name:"field1", type: XdrSCSpecTypeDef::U32()),
    new XdrSCSpecUDTStructFieldV0(doc:"", name:"field2", type: XdrSCSpecTypeDef::U32()),
    new XdrSCSpecUDTStructFieldV0(doc:"", name:"field3", type: XdrSCSpecTypeDef::U32()),
];
$struct = new XdrSCSpecUDTStructV0(doc:"", lib:"", name:"myStruct", fields: $fields);
$entry = XdrSCSpecEntry::forUDTStructV0($struct);
$spec = new ContractSpec(entries:[$entry]);
$def = XdrSCSpecTypeDef::forUDT(new XdrSCSpecTypeUDT(name: "myStruct"));

// convert
$val = $spec->nativeToXdrSCVal(["field1" => 1,"field2" => 2,"field3" => 3], $def);
$this->assertEquals(XdrSCValType::SCV_MAP, $val->type->value);
$this->assertCount(3, $val->map);
```

***Struct (all fields are numeric):***
```php
// prepare (this is not needed for funcArgsToXdrSCValues)
$fields = [
    new XdrSCSpecUDTStructFieldV0(doc:"", name:"1", type: XdrSCSpecTypeDef::STRING()),
    new XdrSCSpecUDTStructFieldV0(doc:"", name:"2", type: XdrSCSpecTypeDef::STRING()),
    new XdrSCSpecUDTStructFieldV0(doc:"", name:"3", type: XdrSCSpecTypeDef::STRING()),
];
$numericStruct = new XdrSCSpecUDTStructV0(doc:"", lib:"", name:"myNumericStruct", fields: $fields);
$entry = XdrSCSpecEntry::forUDTStructV0($numericStruct);
$spec = new ContractSpec(entries:[$entry]);
$def = XdrSCSpecTypeDef::forUDT(new XdrSCSpecTypeUDT(name: "myNumericStruct"));

// convert
$val = $spec->nativeToXdrSCVal(["one","two","three"], $def);
$this->assertEquals(XdrSCValType::SCV_VEC, $val->type->value);
$this->assertCount(3, $val->vec);
```

***Union:***
```php
// prepare (this is not needed for funcArgsToXdrSCValues)
$unionCases = [
    XdrSCSpecUDTUnionCaseV0::forVoidCase(new XdrSCSpecUDTUnionCaseVoidV0(doc:"", name:"voidCase")),
    XdrSCSpecUDTUnionCaseV0::forTupleCase(new XdrSCSpecUDTUnionCaseTupleV0(doc:"", name:"tupleCase",
        type:[XdrSCSpecTypeDef::STRING(), XdrSCSpecTypeDef::U32()]))
];
$union = new XdrSCSpecUDTUnionV0(doc:"", lib:"", name:"myUnion", cases:$unionCases);
$entry = XdrSCSpecEntry::forUDTUnionV0($union);
$spec = new ContractSpec(entries:[$entry]);
$def = XdrSCSpecTypeDef::forUDT(new XdrSCSpecTypeUDT(name: "myUnion"));

// convert
$val = $spec->nativeToXdrSCVal(new NativeUnionVal("voidCase"), $def);
$this->assertEquals(XdrSCValType::SCV_VEC, $val->type->value);
$this->assertCount(1, $val->vec); // only key

$val = $spec->nativeToXdrSCVal(new NativeUnionVal("tupleCase", values: ["a", 4]), $def);
$this->assertEquals(XdrSCValType::SCV_VEC, $val->type->value);
$this->assertCount(3, $val->vec); // key + 2 values (a,4)
```

The above examples can be found in the `SorobanClientTest.php` of the SDK.

## Contract Bindings

For an even more streamlined development experience, you can generate type-safe PHP contract bindings using the [stellar-contract-bindings](https://github.com/lightsail-network/stellar-contract-bindings) tool. This tool generates PHP classes from your contract specifications that provide:

- **Type-safe method calls** with proper PHP types for all parameters
- **Automatic type conversion** between PHP and Soroban types
- **Simplified API** that feels natural

### Generating Contract Bindings

To generate PHP bindings for a deployed contract, you can use the `stellar-contract-bindings` tool:

```bash
# Install the tool
pip install stellar-contract-bindings

# Generate bindings for a deployed contract
stellar-contract-bindings php \
  --contract-id YOUR_CONTRACT_ID \
  --rpc-url https://soroban-testnet.stellar.org \
  --output ./generated  \
  --namespace MyApp\\Contracts \
  --class-name MyContractClient
```

This will generate a PHP file with:
- A main contract client class
- Type definitions for all structs, enums, and unions defined in the contract
- Methods for each contract function with proper type hints

Hint: You can also use the Contract Bindings [Web Interface](https://stellar-contract-bindings.fly.dev/) to generate the bindings.

### Using Generated Bindings

```php
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\MethodOptions;
use MyApp\Contracts\MyContractClient; // Import the generated bindings

// Initialize
$sourceKeyPair = KeyPair::fromAccountId("GD5KKP3LHUDXLDCGKP55NLEOEHMS3Z4BS6IDDZFCYU3BDXUZTBWL7JNF");
// or: $sourceKeyPair = KeyPair::fromSeed("S...")

// Create client instance
$options = new ClientOptions(
    sourceAccountKeyPair: $sourceKeyPair,
    contractId: "CDOAW6D7NXAPOCO7TFAWZNJHK62E3IYRGNRVX3VOXNKNVOXCLLPJXQCF",
    network: Network::public(),
    rpcUrl: "https://mainnet.sorobanrpc.com"
);

$client = MyContractClient::forClientOptions($options);

// Call contract method directly
try {
    $result = $client->hello("World");
    echo "Contract response: " . $result . "\n";
} catch (Exception $e) {
    echo "Error calling contract: " . $e->getMessage() . "\n";
}

// Or build an assembled transaction for more control
$methodOptions = new MethodOptions();
$assembledTx = $client->buildHelloTx("World", $methodOptions);
```

### Generated Bindings examples

For examples of using generated bindings, see [SorobanClientTest.php](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanClientTest.php), 
particularly the binding test functions:
- `testHelloContractWithBinding()` - Simple contract interaction
- `testAuthContractWithBinding()` - Authorization handling
- `testAtomicSwapContractWithBinding()` - Complex multi-contract interaction

## Interacting with Soroban without using the SorobanClient

The [`SorobanClient`](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDK/Soroban/Contract/SorobanClient.php) was introduced as a usability improvement, that allows you to easily 
install and deploy smart contracts and to invoke their methods. It uses the underlying SDK functionality to facilitate this. If you want to learn more about the underlying functionality or need it, the following chapters are for you.

### Deploy your contract

If you want to create a smart contract for testing, you can find the official examples [here](https://github.com/stellar/soroban-examples).
You can also create smart contracts with our AssemblyScript Soroban SDK. Examples can be found [here](https://github.com/Soneso/as-soroban-examples).

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
$request = new SimulateTransactionRequest($transaction);
$simulateResponse = $server->simulateTransaction($request);

$transactionData = $simulateResponse->transactionData;
$minResourceFee = $simulateResponse->minResourceFee;
```
On success, one can find the **soroban transaction data** and the **minimum resource fee** in the response.

Next we need to set the **soroban transaction data** to our transaction, add the **resource fee** and  **sign** the transaction before sending it to the network using the ```SorobanServer```:

```php
$transaction->setSorobanTransactionData($transactionData);
$transaction->addResourceFee($minResourceFee);
$transaction->sign($accountKeyPair, Network::testnet());

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
$createContractHostFunction = new CreateContractHostFunction(Address::fromAccountId($invokerAccountId), $wasmId);
$builder = new InvokeHostFunctionOperationBuilder($createContractHostFunction);
$operation = $builder->build();

// Build the transaction for creating the contract
$transaction = (new TransactionBuilder($account))
    ->addOperation($operation)->build();

// First simulate to obtain the needed soroban data
$request = new SimulateTransactionRequest($transaction);
$simulateResponse = $server->simulateTransaction($request);

// set the transaction data & auth, add fee and sign
$transaction->setSorobanTransactionData($simulateResponse->transactionData);
$transaction->setSorobanAuth($simulateResponse->getSorobanAuth());
$transaction->addResourceFee($simulateResponse->minResourceFee);
$transaction->sign($invokerKeyPair, Network::testnet());

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

With the introduction of protocol 22, contracts with constructor can also be created. The `CreateContractWithConstructorHostFunction` object is used to create the operation.

### Get Ledger Entries

The Soroban-RPC server also provides the possibility to request values of ledger entries directly. It will allow you to directly inspect the current state of a contract, a contract’s code, or any other ledger entry. 

```php
$ledgerKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_CODE());
$ledgerKey->contractCode = new XdrLedgerKeyContractCode(hex2bin($wasmId), XdrContractEntryBodyType::DATA_ENTRY());
$ledgerEntriesResponse = $server->getLedgerEntries([$ledgerKey->toBase64Xdr()]);
```

If you already have a contractId you can load the code as follows:
```php
$contractCodeEntry = $server->loadContractCodeForContractId($contractId);
if ($contractCodeEntry != null) {
    $loadedSourceCode = $contractCodeEntry->body->code->value;
}
```

If you have a wasmId:

```php
$contractCodeEntry = $server->loadContractCodeForWasmId($wasmId);
if ($contractCodeEntry != null) {
    $loadedSourceCode = $contractCodeEntry->body->code->value;
}
```


### Invoking a contract

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
$request = new SimulateTransactionRequest($transaction);
$simulateResponse = $server->simulateTransaction($request);

$transactionData = $simulateResponse->transactionData;
$minResourceFee = $simulateResponse->minResourceFee;
```
On success, one can find the **transaction data** and the **resource fee** in the response. 

Next we need to set the **soroban transaction data** to our transaction, to add the **resource fee** and **sign** the transaction to send it to the network using the ```SorobanServer```:


```php
$transaction->setSorobanTransactionData($transactionData);
$transaction->addResourceFee($minResourceFee);
$transaction->sign($accountKeyPair, Network::testnet());

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

### Deploying Stellar Asset Contract (SAC)

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
$transaction->sign($invokerKeyPair, Network::testnet());
```

2. Deploy SAC with asset:

```php
$hostFunction = new DeploySACWithAssetHostFunction($iomAsset);
$builder = new InvokeHostFunctionOperationBuilder($hostFunction);
$operation = $builder->build();
```

### Soroban Authorization

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
        $a->sign($invokerKeyPair, Network::testnet());
    } else {
        self::fail("invalid auth");
    }
}
$transaction->setSorobanAuth($auth);
```

You can find multiple examples in the [Soroban Auth Test Cases](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanAuthTest.php) and in the [Atomic Swap Test](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanAtomicSwapTest.php) of the SDK.

### Get Events

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

contractId must currently start with "C...". If you only have the hex value you can encode it with: `StrKey::encodeContractIdHex($contractId)`

Find the complete code [here](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanTest.php).

### Hints and Tips

You can find the working code and more in the [SorobanClient Test](https://github.com/Soneso/stellar-php-sdk/tree/main/Soneso/StellarSDKTests/SorobanClientTest.php), [Soroban Test](https://github.com/Soneso/stellar-php-sdk/tree/main/Soneso/StellarSDKTests/SorobanTest.php), [Soroban Auth Test](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanAuthTest.php) and [Atomic Swap Test](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanAtomicSwapTest.php) of the PHP SDK. The wasm byte-code files can be found in the [test/wasm](https://github.com/Soneso/stellar-php-sdk/tree/main/Soneso/StellarSDKTests/wasm/) folder.

For a better understanding of an error you can enable the ```SorobanServer``` logging:

```php
$server->enableLogging = true;
```
This will log the responses received from the Soroban-RPC server.

If you find any issues please report them [here](https://github.com/Soneso/stellar-php-sdk/issues). It will help us to improve the SDK.

## Soroban contract parser

The soroban contract parser allows you to access the contract info stored in the contract bytecode.
You can access the environment metadata, contract spec and contract meta.

The environment metadata holds the interface version that should match the version of the soroban environment host functions supported.

The contract spec contains a `XdrSCSpecEntry` for every function, struct, and union exported by the contract.

In the contract meta, contracts may store any metadata in the entries that can be used by applications and tooling off-network.

You can access the parser directly if you have the contract bytecode:

```php
$contractByteCode = file_get_contents("path to .wasm file");
$contractInfo = SorobanContractParser::parseContractByteCode($contractByteCode);
```

Or you can use `SorobanServer` methods to load the contract code form the network and parse it.

By contract id:
```php
 $contractInfo = $server->loadContractInfoForContractId($contractId);
```

By wasm id:
```php
$contractInfo = $server->loadContractInfoForWasmId($contractWasmId);
```

The parser returns a `SorobanContractInfo` object containing the parsed data.
In [SorobanParserTest.php](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/SorobanParserTest.php) you can find a detailed example of how you can access the parsed data.
