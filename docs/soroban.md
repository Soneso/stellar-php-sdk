# Soroban Smart Contracts

Deploy and interact with Soroban smart contracts using the Stellar PHP SDK.

**Protocol details**: [Soroban Documentation](https://developers.stellar.org/docs/smart-contracts)

## Quick Start

Install WASM, deploy a contract, and call a method in one go.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\DeployRequest;
use Soneso\StellarSDK\Soroban\Contract\InstallRequest;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$keyPair = KeyPair::fromSeed('SXXX...');
$rpcUrl = 'https://soroban-testnet.stellar.org';

// 1. Install WASM
$wasmHash = SorobanClient::install(new InstallRequest(
    wasmBytes: file_get_contents('hello.wasm'),
    rpcUrl: $rpcUrl,
    network: Network::testnet(),
    sourceAccountKeyPair: $keyPair
));

// 2. Deploy
$client = SorobanClient::deploy(new DeployRequest(
    rpcUrl: $rpcUrl,
    network: Network::testnet(),
    sourceAccountKeyPair: $keyPair,
    wasmHash: $wasmHash
));

// 3. Invoke
$result = $client->invokeMethod('hello', [XdrSCVal::forSymbol('World')]);
echo $result->vec[0]->sym . ', ' . $result->vec[1]->sym; // Hello, World
```

## SorobanServer

Direct communication with Soroban RPC nodes for low-level operations.

### Connecting to RPC

Connect to a Soroban RPC node to send requests and receive responses.

```php
<?php
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$server->enableLogging = true; // Enable debug logging
```

### Health Check

Verify the RPC node is operational before making requests.

```php
<?php
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\Responses\GetHealthResponse;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

$health = $server->getHealth();
if ($health->status === GetHealthResponse::HEALTHY) {
    echo "Node healthy\n";
}
```

### Network Information

Get network passphrase and protocol version.

```php
<?php
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

$network = $server->getNetwork();
echo "Passphrase: {$network->passphrase}\n";
echo "Protocol version: {$network->protocolVersion}\n";
```

### Latest Ledger

Get the current ledger sequence for transaction timing.

```php
<?php
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

$ledger = $server->getLatestLedger();
echo "Sequence: {$ledger->sequence}\n";
```

### Account Data

Load account information (needed for transaction building).

```php
<?php
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

$account = $server->getAccount('GABC...');
echo "Sequence: {$account->getSequenceNumber()}\n";
```

### Contract Data

Read persistent or temporary data stored by a contract.

```php
<?php
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

$entry = $server->getContractData(
    contractId: 'CCXYZ...',
    key: XdrSCVal::forSymbol('counter'),
    durability: XdrContractDataDurability::PERSISTENT()
);

if ($entry !== null) {
    echo "Value: " . $entry->getLedgerEntryDataXdr()->contractData->val->u32 . "\n";
}
```

### Contract Info

Load contract specification and metadata.

```php
<?php
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

// By contract ID
$info = $server->loadContractInfoForContractId('CCXYZ...');
if ($info !== null) {
    echo "Spec entries: " . count($info->specEntries) . "\n";
}

// By WASM ID (hash of uploaded code)
$info = $server->loadContractInfoForWasmId($wasmId);
```

### Get Ledger Entries

Query raw ledger entries by their keys. Use when you need direct access to ledger state data.

```php
<?php
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyContractCode;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

// Build ledger key for contract code
$ledgerKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_CODE());
$ledgerKey->contractCode = new XdrLedgerKeyContractCode(hex2bin($wasmId));

// Request ledger entries
$response = $server->getLedgerEntries([$ledgerKey->toBase64Xdr()]);

foreach ($response->entries as $entry) {
    echo "Ledger: " . $entry->lastModifiedLedgerSeq . "\n";
}
```

### Load Contract Code

Helper methods to load contract bytecode from the network.

```php
<?php
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

// By contract ID
$contractCodeEntry = $server->loadContractCodeForContractId('CCXYZ...');
if ($contractCodeEntry !== null) {
    $bytecode = $contractCodeEntry->code->value;
    echo "Code size: " . strlen($bytecode) . " bytes\n";
}

// By WASM ID
$contractCodeEntry = $server->loadContractCodeForWasmId($wasmId);
```

## SorobanClient

High-level API for contract interaction.

### Creating a Client

Set up a SorobanClient instance for interacting with a specific contract.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;

$client = SorobanClient::forClientOptions(new ClientOptions(
    sourceAccountKeyPair: KeyPair::fromSeed('SXXX...'),
    contractId: 'CCXYZ...',
    network: Network::testnet(),
    rpcUrl: 'https://soroban-testnet.stellar.org'
));

$methodNames = $client->getMethodNames();
$spec = $client->getContractSpec();
```

### Invoking Methods

Call contract functions to read data or submit state changes.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\MethodOptions;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$client = SorobanClient::forClientOptions(new ClientOptions(
    sourceAccountKeyPair: KeyPair::fromSeed('SXXX...'),
    contractId: 'CCXYZ...',
    network: Network::testnet(),
    rpcUrl: 'https://soroban-testnet.stellar.org'
));

// Read-only (returns simulation result)
$balance = $client->invokeMethod('balance', [
    Address::fromAccountId('GABC...')->toXdrSCVal()
]);

// Write (auto-signs and submits)
$result = $client->invokeMethod('transfer', [
    Address::fromAccountId('GFROM...')->toXdrSCVal(),
    Address::fromAccountId('GTO...')->toXdrSCVal(),
    XdrSCVal::forI128BigInt(1000)
]);

// Custom options
$methodOptions = new MethodOptions(
    fee: 10000,
    timeoutInSeconds: 30,
    restore: true  // Auto-restore expired state
);
$result = $client->invokeMethod('expensive_op', [], methodOptions: $methodOptions);
```

## Installing and Deploying

Put your contract on the network. Install uploads the WASM bytecode once; deploy creates contract instances from that code.

### Installation

Upload WASM bytecode (do once per contract version):

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Contract\InstallRequest;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;

$wasmHash = SorobanClient::install(new InstallRequest(
    wasmBytes: file_get_contents('contract.wasm'),
    rpcUrl: 'https://soroban-testnet.stellar.org',
    network: Network::testnet(),
    sourceAccountKeyPair: KeyPair::fromSeed('SXXX...')
));

// Returns existing hash if already installed (use force: true to re-install)
```

### Deployment

Create contract instance from installed WASM:

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Contract\DeployRequest;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Xdr\XdrSCVal;

// Basic deployment
$client = SorobanClient::deploy(new DeployRequest(
    rpcUrl: 'https://soroban-testnet.stellar.org',
    network: Network::testnet(),
    sourceAccountKeyPair: KeyPair::fromSeed('SXXX...'),
    wasmHash: $wasmHash
));

// With constructor (protocol 22+)
$client = SorobanClient::deploy(new DeployRequest(
    rpcUrl: 'https://soroban-testnet.stellar.org',
    network: Network::testnet(),
    sourceAccountKeyPair: KeyPair::fromSeed('SXXX...'),
    wasmHash: $wasmHash,
    constructorArgs: [XdrSCVal::forSymbol('MyToken'), XdrSCVal::forU32(8)]
));
```

## AssembledTransaction

Fine-grained control over the transaction lifecycle. Use `buildInvokeMethodTx()` instead of `invokeMethod()` when you need to inspect simulation results, add memos, or handle multi-signature workflows.

### Building Without Submitting

Build a transaction to inspect it before submission.

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$client = SorobanClient::forClientOptions(new ClientOptions(
    sourceAccountKeyPair: KeyPair::fromSeed('SXXX...'),
    contractId: 'CCXYZ...',
    network: Network::testnet(),
    rpcUrl: 'https://soroban-testnet.stellar.org'
));

// Build without submitting
$tx = $client->buildInvokeMethodTx('transfer', [XdrSCVal::forSymbol('test')]);
```

### Accessing Simulation Results

Get simulation data including return values and resource estimates.

```php
<?php
// Access simulation results
$simData = $tx->getSimulationData();
$returnValue = $simData->returnedValue;
$minResourceFee = $tx->simulationResponse->minResourceFee;
```

### Read-Only vs Write Calls

Check if a call is read-only (simulation only) or requires submission.

```php
<?php
if ($tx->isReadCall()) {
    // Read-only: result available from simulation
    $result = $tx->getSimulationData()->returnedValue;
} else {
    // Write: must sign and submit
    $response = $tx->signAndSend();
    $result = $response->getResultValue();
}
```

### Modifying Before Submission

Skip automatic simulation to modify the transaction (e.g., add memo) before simulating.

```php
<?php
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Soroban\Contract\MethodOptions;

// Build without auto-simulation
$tx = $client->buildInvokeMethodTx(
    'my_method', 
    [], 
    methodOptions: new MethodOptions(simulate: false)
);

// Modify the raw transaction
$tx->raw->addMemo(Memo::text('My memo'));

// Now simulate and submit
$tx->simulate();
$response = $tx->signAndSend();
```

## Authorization

Handle multi-party signing for operations like swaps, escrow, and transfers that require consent from multiple accounts.

### Check Who Needs to Sign

Before submission, check which accounts need to authorize the transaction.

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$alice = KeyPair::fromSeed('SALICE...');
$bob = KeyPair::fromSeed('SBOB...');

$client = SorobanClient::forClientOptions(new ClientOptions(
    sourceAccountKeyPair: $alice,
    contractId: 'CSWAP...',
    network: Network::testnet(),
    rpcUrl: 'https://soroban-testnet.stellar.org'
));

$tx = $client->buildInvokeMethodTx('swap', [
    Address::fromAccountId($alice->getAccountId())->toXdrSCVal(),
    Address::fromAccountId($bob->getAccountId())->toXdrSCVal(),
    XdrSCVal::forI128BigInt(1000),
    XdrSCVal::forI128BigInt(500)
]);

// Check who needs to sign (returns array of account IDs)
$neededSigners = $tx->needsNonInvokerSigningBy();
// e.g., ['GBOB...'] - Bob needs to authorize
```

### Local Signing

Sign auth entries when you have the private key locally.

```php
<?php
// Sign Bob's auth entries (Bob's keypair available locally)
$tx->signAuthEntries(signerKeyPair: $bob);

// Submit (Alice signs the transaction envelope)
$response = $tx->signAndSend();
```

### Remote Signing

Sign auth entries when the private key is on another server (e.g., custody service).

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;

// Only have Bob's public key locally
$bobPublicKey = KeyPair::fromAccountId('GBOB...');

$tx->signAuthEntries(
    signerKeyPair: $bobPublicKey,
    authorizeEntryCallback: function (
        SorobanAuthorizationEntry $entry,
        Network $network
    ): SorobanAuthorizationEntry {
        // Send to remote server for signing
        $base64Entry = $entry->toBase64Xdr();
        $signedBase64 = sendToRemoteServer($base64Entry); // Your implementation
        return SorobanAuthorizationEntry::fromBase64Xdr($signedBase64);
    }
);

// Submit after all auth entries are signed
$response = $tx->signAndSend();
```

## Type Conversions

Convert between PHP native types and Soroban XDR values.

### Creating XdrSCVal

Create XDR values manually for contract arguments.

#### Primitives

Basic data types like numbers, booleans, and strings.

```php
<?php
use Soneso\StellarSDK\Xdr\XdrSCVal;

$bool = XdrSCVal::forBool(true);
$u32 = XdrSCVal::forU32(42);
$i32 = XdrSCVal::forI32(-42);
$u64 = XdrSCVal::forU64(1000000);
$i64 = XdrSCVal::forI64(-1000000);
$string = XdrSCVal::forString('Hello');
$symbol = XdrSCVal::forSymbol('transfer');
$bytes = XdrSCVal::forBytes(hex2bin('deadbeef'));
$void = XdrSCVal::forVoid();
```

#### Big Integers (128/256-bit)

Handle integers that exceed PHP's native range using strings or GMP.

```php
<?php
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrSCVal;

// From string (recommended for large values)
$u128 = XdrSCVal::forU128BigInt('340282366920938463463374607431768211455');
$i128 = XdrSCVal::forI128BigInt('-170141183460469231731687303715884105728');

// From GMP for calculations
$u256 = XdrSCVal::forU256BigInt(gmp_pow(2, 200));
$i256 = XdrSCVal::forI256BigInt(gmp_neg(gmp_pow(2, 200)));

// Small integers work directly
$i128 = XdrSCVal::forI128BigInt(42);

// Legacy method (still supported)
$i128Legacy = XdrSCVal::forI128(new XdrInt128Parts(hi: 0, lo: 1000));

// Converting back to BigInt
$bigInt = $u128->toBigInt();  // Returns GMP resource
echo gmp_strval($bigInt);
```

#### Addresses

Account and contract addresses for referencing entities on the network.

```php
<?php
use Soneso\StellarSDK\Soroban\Address;

// Account address (G...)
$account = Address::fromAccountId('GABC...')->toXdrSCVal();

// Contract address (C...) - use fromAnyId for strkey format
$contract = Address::fromAnyId('CABC...')->toXdrSCVal();
```

#### Collections

Arrays (vectors) and key-value pairs (maps) for structured data.

```php
<?php
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;

// Vector (array)
$vec = XdrSCVal::forVec([
    XdrSCVal::forSymbol('a'),
    XdrSCVal::forSymbol('b')
]);

// Map (key-value pairs)
$map = XdrSCVal::forMap([
    new XdrSCMapEntry(XdrSCVal::forSymbol('name'), XdrSCVal::forString('Alice')),
    new XdrSCMapEntry(XdrSCVal::forSymbol('age'), XdrSCVal::forU32(30))
]);
```

### Using ContractSpec

Auto-convert native PHP values based on contract specification. The spec is loaded from the contract and knows the expected types.

```php
<?php
$spec = $client->getContractSpec();

// Convert function arguments (uses spec to determine types)
$args = $spec->funcArgsToXdrSCValues('swap', [
    'a' => 'GALICE...',      // Auto-converts to Address
    'b' => 'GBOB...',
    'token_a' => 'CTOKEN1...',
    'token_b' => 'CTOKEN2...',
    'amount_a' => 1000,       // Auto-converts to i128
    'min_b_for_a' => 950,
    'amount_b' => 500,
    'min_a_for_b' => 450
]);

// Explore contract functions
$functions = $spec->funcs();
$swapFunc = $spec->getFunc('swap');

// Find custom types
$myUnion = $spec->findEntry('myUnion');
```

### Advanced Type Conversions

For low-level control, use `nativeToXdrSCVal()` with explicit type definitions.

#### Void and Option (Nullable)

Empty values and nullable types for optional data.

```php
<?php
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeOption;

// Void
$def = XdrSCSpecTypeDef::VOID();
$val = $spec->nativeToXdrSCVal(null, $def);

// Option (nullable) - returns string or void
$def = XdrSCSpecTypeDef::forOption(
    new XdrSCSpecTypeOption(valueType: XdrSCSpecTypeDef::STRING())
);
$val = $spec->nativeToXdrSCVal("a string", $def);  // String value
$val = $spec->nativeToXdrSCVal(null, $def);        // Void (none)
```

#### Vectors with Element Type

Strongly-typed arrays where all elements share the same type.

```php
<?php
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeVec;

$def = XdrSCSpecTypeDef::forVec(
    new XdrSCSpecTypeVec(elementType: XdrSCSpecTypeDef::SYMBOL())
);
$val = $spec->nativeToXdrSCVal(["a", "b", "c"], $def);
```

#### Maps with Key/Value Types

Strongly-typed key-value mappings with specific types for keys and values.

```php
<?php
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeMap;

$mapType = new XdrSCSpecTypeMap(
    keyType: XdrSCSpecTypeDef::STRING(),
    valueType: XdrSCSpecTypeDef::ADDRESS()
);
$def = XdrSCSpecTypeDef::forMap($mapType);
$val = $spec->nativeToXdrSCVal([
    "alice" => "GALICE...",
    "bob" => "GBOB..."
], $def);
```

> **PHP Limitation**: PHP only accepts `int` or `string` as array keys. If a map key must be a type that cannot be constructed from int/string (e.g., bool, vec, another map), you must construct the `XdrSCVal` manually.

#### Tuples

Fixed-size collections of values where each position has a specific type.

```php
<?php
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeTuple;

$tuple = new XdrSCSpecTypeTuple(valueTypes: [
    XdrSCSpecTypeDef::STRING(),
    XdrSCSpecTypeDef::BOOL(),
    XdrSCSpecTypeDef::U32()
]);
$def = XdrSCSpecTypeDef::forTuple($tuple);
$val = $spec->nativeToXdrSCVal(["hello", true, 42], $def);
```

#### Bytes and BytesN

Binary data of variable or fixed length for hashes, keys, and raw data.

```php
<?php
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeBytesN;

// Variable-length bytes
$def = XdrSCSpecTypeDef::BYTES();
$val = $spec->nativeToXdrSCVal($keyPair->getPublicKey(), $def);

// Fixed-length bytes (e.g., 32 bytes for a hash)
$def = XdrSCSpecTypeDef::forBytesN(new XdrSCSpecTypeBytesN(n: 32));
$val = $spec->nativeToXdrSCVal($keyPair->getPublicKey(), $def);
```

#### User-Defined Types (Enum, Struct, Union)

**Enum** — pass the integer value:

```php
<?php
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeUDT;

$def = XdrSCSpecTypeDef::forUDT(new XdrSCSpecTypeUDT(name: "MyEnum"));
$val = $spec->nativeToXdrSCVal(2, $def);  // Enum case with value 2
```

**Struct** — pass an associative array:

```php
<?php
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeUDT;

$def = XdrSCSpecTypeDef::forUDT(new XdrSCSpecTypeUDT(name: "MyStruct"));
$val = $spec->nativeToXdrSCVal([
    "field1" => 100,
    "field2" => "hello",
    "field3" => true
], $def);
```

> **Note**: If all struct field names are numeric strings, pass a sequential array instead — the result will be `SCV_VEC` rather than `SCV_MAP`.

**Union** — use `NativeUnionVal`:

```php
<?php
use Soneso\StellarSDK\Soroban\Contract\NativeUnionVal;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeDef;
use Soneso\StellarSDK\Xdr\XdrSCSpecTypeUDT;

$def = XdrSCSpecTypeDef::forUDT(new XdrSCSpecTypeUDT(name: "MyUnion"));

// Void case (no values)
$val = $spec->nativeToXdrSCVal(new NativeUnionVal("voidCase"), $def);

// Tuple case (with values)
$val = $spec->nativeToXdrSCVal(
    new NativeUnionVal("tupleCase", values: ["hello", 42]),
    $def
);
```

### Reading Return Values

Access return values by their XDR type.

```php
<?php
$result = $client->invokeMethod('get_data', []);

// Access by type property
$count = $result->u32;
$name = $result->str;
$flag = $result->b;

// BigInt conversion
$bigValue = $result->toBigInt();
echo gmp_strval($bigValue);

// Iterate vector elements
foreach ($result->vec as $item) {
    echo $item->sym . "\n";
}

// Access map entries
foreach ($result->map as $entry) {
    echo $entry->key->sym . ": " . $entry->val->str . "\n";
}
```

## Events

Query contract events emitted during execution. Useful for tracking transfers, state changes, and other contract activity.

### Basic Event Query

Query events starting from a specific ledger.

```php
<?php
use Soneso\StellarSDK\Soroban\Requests\GetEventsRequest;
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

// Get events starting from ledger 12345
$response = $server->getEvents(new GetEventsRequest(startLedger: 12345));

foreach ($response->events as $event) {
    echo "Ledger: {$event->ledger}\n";
    echo "Contract: {$event->contractId}\n";
    echo "Topics: " . json_encode($event->topic) . "\n";
}
```

### Filtering by Contract and Topic

Filter events by contract ID and topic values.

```php
<?php
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Soroban\Requests\EventFilter;
use Soneso\StellarSDK\Soroban\Requests\EventFilters;
use Soneso\StellarSDK\Soroban\Requests\GetEventsRequest;
use Soneso\StellarSDK\Soroban\Requests\TopicFilter;
use Soneso\StellarSDK\Soroban\Requests\TopicFilters;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

// Contract ID must be C-prefixed strkey
$contractId = 'CCXYZ...';

// Filter: any first topic, "transfer" as second topic
$topicFilter = new TopicFilter([
    '*',  // Wildcard for first topic
    XdrSCVal::forSymbol('transfer')->toBase64Xdr()
]);

$eventFilter = new EventFilter(
    type: 'contract',
    contractIds: [$contractId],
    topics: new TopicFilters($topicFilter)
);

$filters = new EventFilters();
$filters->add($eventFilter);

$response = $server->getEvents(new GetEventsRequest(
    startLedger: 12345,
    filters: $filters
));

foreach ($response->events as $event) {
    echo "Ledger: {$event->ledger}, Value: {$event->value}\n";
}
```

## Error Handling

Handle errors at different stages: client creation, simulation, and transaction submission.

### Debug Logging

Enable logging to diagnose issues.

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;

$client = SorobanClient::forClientOptions(new ClientOptions(
    sourceAccountKeyPair: KeyPair::fromSeed('SXXX...'),
    contractId: 'CCXYZ...',
    network: Network::testnet(),
    rpcUrl: 'https://soroban-testnet.stellar.org',
    enableServerLogging: true  // Enable RPC logging
));
```

### Method Not Found

Handle invalid method names or arguments.

```php
<?php
try {
    $tx = $client->buildInvokeMethodTx('nonexistent', []);
} catch (\Exception $e) {
    echo "Error: {$e->getMessage()}\n";
}
```

### Simulation Errors

Check simulation response for errors before submission.

```php
<?php
$tx = $client->buildInvokeMethodTx('my_method', []);

if ($tx->simulationResponse->error !== null) {
    echo "Simulation failed: {$tx->simulationResponse->error->message}\n";
    // Don't submit - fix the issue first
}
```

### Transaction Failures

Handle failures after submission.

```php
<?php
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;

try {
    $response = $tx->signAndSend();
    
    if ($response->status === GetTransactionResponse::STATUS_FAILED) {
        echo "Transaction failed: {$response->resultXdr}\n";
    } elseif ($response->status === GetTransactionResponse::STATUS_SUCCESS) {
        echo "Success!\n";
    }
} catch (\Exception $e) {
    echo "Submission error: {$e->getMessage()}\n";
}
```

### Auto-Restore Expired State

Automatically restore expired contract state before invocation.

```php
<?php
use Soneso\StellarSDK\Soroban\Contract\MethodOptions;

// If contract state has expired, restore it automatically
$result = $client->invokeMethod(
    'my_method',
    [],
    methodOptions: new MethodOptions(restore: true)
);
```

## Contract Bindings

Generate type-safe PHP classes from contract specifications. This provides IDE autocompletion and compile-time type checking.

### Generate Bindings

Use [stellar-contract-bindings](https://github.com/lightsail-network/stellar-contract-bindings) to generate PHP classes:

```bash
pip install stellar-contract-bindings

stellar-contract-bindings php \
  --contract-id YOUR_CONTRACT_ID \
  --rpc-url https://soroban-testnet.stellar.org \
  --output ./generated \
  --namespace MyApp\\Contracts \
  --class-name TokenClient
```

Or use the [web interface](https://stellar-contract-bindings.fly.dev/).

### Use Generated Client

The generated client provides type-safe method calls with native PHP types.

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use MyApp\Contracts\TokenClient;

$client = TokenClient::forClientOptions(new ClientOptions(
    sourceAccountKeyPair: KeyPair::fromSeed('SXXX...'),
    contractId: 'CTOKEN...',
    network: Network::testnet(),
    rpcUrl: 'https://soroban-testnet.stellar.org'
));

// Type-safe calls with native PHP types
$balance = $client->balance('GABC...');  // Returns BigInteger
$client->transfer('GFROM...', 'GTO...', 1000);  // Amount as int
```

## Low-Level Operations

Manual operations for custom workflows requiring full control over the transaction process.

### Upload WASM

Upload contract bytecode to the network. Returns a WASM hash for deployment.

```php
<?php
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\UploadContractWasmHostFunction;

$keyPair = KeyPair::fromSeed('SXXX...');
$server = new SorobanServer('https://soroban-testnet.stellar.org');

// Build upload operation
$uploadOp = (new InvokeHostFunctionOperationBuilder(
    new UploadContractWasmHostFunction(file_get_contents('contract.wasm'))
))->build();

// Build and simulate transaction
$account = $server->getAccount($keyPair->getAccountId());
$tx = (new TransactionBuilder($account))->addOperation($uploadOp)->build();

$sim = $server->simulateTransaction(new SimulateTransactionRequest($tx));
$tx->setSorobanTransactionData($sim->transactionData);
$tx->addResourceFee($sim->minResourceFee);
$tx->sign($keyPair, Network::testnet());

// Submit
$sendResponse = $server->sendTransaction($tx);

// Poll for result
$txResponse = $server->getTransaction($sendResponse->hash);
if ($txResponse->status === GetTransactionResponse::STATUS_SUCCESS) {
    $wasmHash = $txResponse->getWasmId();
}
```

### Create Contract Instance

Deploy a contract instance from an uploaded WASM hash.

```php
<?php
use Soneso\StellarSDK\CreateContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Soroban\Address;

$createOp = (new InvokeHostFunctionOperationBuilder(
    new CreateContractHostFunction(
        Address::fromAccountId($keyPair->getAccountId()),
        $wasmHash
    )
))->build();

// Build, simulate, set auth, sign, and send
$tx = (new TransactionBuilder($account))->addOperation($createOp)->build();
$sim = $server->simulateTransaction(new SimulateTransactionRequest($tx));

$tx->setSorobanTransactionData($sim->transactionData);
$tx->setSorobanAuth($sim->getSorobanAuth());
$tx->addResourceFee($sim->minResourceFee);
$tx->sign($keyPair, Network::testnet());

$sendResponse = $server->sendTransaction($tx);
```

### Create Contract with Constructor (Protocol 22+)

Deploy contracts that have constructors.

```php
<?php
use Soneso\StellarSDK\CreateContractWithConstructorHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$createOp = (new InvokeHostFunctionOperationBuilder(
    new CreateContractWithConstructorHostFunction(
        Address::fromAccountId($keyPair->getAccountId()),
        $wasmHash,
        [XdrSCVal::forSymbol('MyToken'), XdrSCVal::forU32(8)]  // Constructor args
    )
))->build();

// Build, simulate, sign, and send (same pattern)
```

### Invoke Contract (Low-Level)

Invoke a contract method without using SorobanClient.

```php
<?php
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$invokeOp = (new InvokeHostFunctionOperationBuilder(
    new InvokeContractHostFunction(
        $contractId,
        'hello',
        [XdrSCVal::forSymbol('World')]
    )
))->build();

// Build transaction
$tx = (new TransactionBuilder($account))->addOperation($invokeOp)->build();

// Simulate to get resource requirements
$sim = $server->simulateTransaction(new SimulateTransactionRequest($tx));
$tx->setSorobanTransactionData($sim->transactionData);
$tx->addResourceFee($sim->minResourceFee);
$tx->sign($keyPair, Network::testnet());

// Submit and get result
$sendResponse = $server->sendTransaction($tx);
// Poll getTransaction until success, then get result:
$result = $txResponse->getResultValue();
```

### Deploy Stellar Asset Contract (SAC)

Wrap a classic Stellar asset as a Soroban token contract. The protocol requires a `FROM_ASSET` contract ID preimage, so SAC deployment uses `DeploySACWithAssetHostFunction` with the asset to wrap.

```php
<?php
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\DeploySACWithAssetHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;

$asset = Asset::createNonNativeAsset('USDC', 'GISSUER...');

$sacOp = (new InvokeHostFunctionOperationBuilder(
    new DeploySACWithAssetHostFunction($asset)
))->build();

// Build, simulate, sign, and send
```

### Direct Authorization Signing

For advanced auth workflows, sign authorization entries directly.

```php
<?php
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;

// Get auth entries from simulation
$auth = $simulateResponse->getSorobanAuth();
$latestLedger = $server->getLatestLedger();

foreach ($auth as $entry) {
    if ($entry instanceof SorobanAuthorizationEntry) {
        // Set signature expiration (~50 seconds at 5s/ledger)
        $entry->credentials->addressCredentials->signatureExpirationLedger = 
            $latestLedger->sequence + 10;
        
        // Sign the entry
        $entry->sign($signerKeyPair, Network::testnet());
    }
}

// Set signed auth on transaction
$transaction->setSorobanAuth($auth);
```

> **Tip**: Contract IDs must be C-prefixed strkey format. To convert from hex: `StrKey::encodeContractIdHex($hexContractId)`

## Contract Parser

Parse contract bytecode to access specifications, metadata, and environment information without deploying.

### Parse from Bytecode

Parse a local WASM file directly.

```php
<?php
use Soneso\StellarSDK\Soroban\SorobanContractParser;

$bytecode = file_get_contents('contract.wasm');
$contractInfo = SorobanContractParser::parseContractByteCode($bytecode);

// Environment metadata (interface version)
$envVersion = $contractInfo->envInterfaceVersion;

// Contract spec (functions, structs, unions)
foreach ($contractInfo->specEntries as $entry) {
    // Each entry is an XdrSCSpecEntry
    echo $entry->type->value . "\n";
}

// Contract meta (arbitrary metadata as key-value pairs)
$meta = $contractInfo->metaEntries;
```

### Parse from Network

Load and parse contract info from a deployed contract.

```php
<?php
use Soneso\StellarSDK\Soroban\Contract\ContractSpec;
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

// By contract ID
$contractInfo = $server->loadContractInfoForContractId('CCXYZ...');

// By WASM ID
$contractInfo = $server->loadContractInfoForWasmId($wasmId);

if ($contractInfo !== null) {
    // Use ContractSpec for type conversions
    $spec = new ContractSpec($contractInfo->specEntries);
    $functions = $spec->funcs();
    
    foreach ($functions as $func) {
        echo "Function: " . $func->name . "\n";
    }
}
```

## Further Reading

- [SorobanClientTest.php](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/Integration/SorobanClientTest.php) — High-level API tests
- [SorobanAuthTest.php](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/Integration/SorobanAuthTest.php) — Authorization tests
- [SorobanAtomicSwapTest.php](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/Integration/SorobanAtomicSwapTest.php) — Multi-party signing
- [SorobanParserTest.php](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/Unit/Soroban/SorobanParserTest.php) — Contract parsing
- [Soroban Docs](https://developers.stellar.org/docs/smart-contracts) — Protocol details
- [Soroban Examples](https://github.com/stellar/soroban-examples) — Official example contracts
- [RPC API Reference](https://developers.stellar.org/docs/data/rpc/api-reference) — Soroban RPC methods
- [SEP Protocols](sep/README.md) — Stellar Ecosystem Proposals

---

**Navigation:** [← SDK Usage](sdk-usage.md) | [SEP Protocols →](sep/README.md)
