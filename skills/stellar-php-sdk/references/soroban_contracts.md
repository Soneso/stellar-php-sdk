# Soroban Smart Contracts

## Contract Deployment

### Install WASM Code

Upload compiled contract bytecode to the network. Returns a WASM hash used for deployment.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Contract\InstallRequest;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;

$keyPair = KeyPair::random(); // or KeyPair::fromSeed($yourSecret)
$wasmBytes = file_get_contents('/path/to/contract.wasm');

$wasmHash = SorobanClient::install(new InstallRequest(
    wasmBytes: $wasmBytes,
    rpcUrl: 'https://soroban-testnet.stellar.org',
    network: Network::testnet(),
    sourceAccountKeyPair: $keyPair,
));
// $wasmHash is a hex string identifying the installed code
```

### Deploy Contract Instance

Create a contract instance from installed WASM. Returns a `SorobanClient` for the new contract.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Contract\DeployRequest;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$keyPair = KeyPair::random(); // or KeyPair::fromSeed($yourSecret)
$wasmHash = 'a1b2c3...'; // hex hash from install step

// constructorArgs must match the __constructor spec types exactly.
// Manual approach — use when you don't have the contract spec:
$client = SorobanClient::deploy(new DeployRequest(
    rpcUrl: 'https://soroban-testnet.stellar.org',
    network: Network::testnet(),
    sourceAccountKeyPair: $keyPair,
    wasmHash: $wasmHash,
    constructorArgs: [XdrSCVal::forSymbol('MyToken'), XdrSCVal::forU32(18)],
    // ^ see "XdrSCSpecType Constants to XdrSCVal Factories" mapping table below
));

$contractId = $client->getContractId(); // C-prefixed contract address
```

**Preferred: use `funcArgsToXdrSCValues` when you have the contract spec.** It auto-converts native PHP values to the correct `XdrSCVal` types based on the spec — no manual type mapping needed:

```php
use Soneso\StellarSDK\Soroban\Contract\ContractSpec;
use Soneso\StellarSDK\Soroban\SorobanServer;

// Load spec from installed WASM
$server = new SorobanServer('https://soroban-testnet.stellar.org');
$info = $server->loadContractInfoForWasmId($wasmHash);
$spec = new ContractSpec($info->specEntries);

// Auto-convert named args to XdrSCVal based on __constructor spec types
$constructorArgs = $spec->funcArgsToXdrSCValues('__constructor', [
    'admin' => $keyPair->getAccountId(),  // String → Address (automatic)
    'decimal' => 7,                        // int → U32 (automatic)
    'name' => 'MyToken',                   // string → String (automatic)
    'symbol' => 'MTK',                     // string → Symbol (automatic)
]);

$client = SorobanClient::deploy(new DeployRequest(
    rpcUrl: 'https://soroban-testnet.stellar.org',
    network: Network::testnet(),
    sourceAccountKeyPair: $keyPair,
    wasmHash: $wasmHash,
    constructorArgs: $constructorArgs,
));
```

## Contract Invocation

### High-Level: SorobanClient

The `SorobanClient` auto-detects read vs write calls, handles simulation, signing, and submission.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\MethodOptions;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$keyPair = KeyPair::random(); // or KeyPair::fromSeed($yourSecret)

$client = SorobanClient::forClientOptions(new ClientOptions(
    sourceAccountKeyPair: $keyPair,
    contractId: 'CABC123...', // C-prefixed contract address
    network: Network::testnet(),
    rpcUrl: 'https://soroban-testnet.stellar.org',
));

// Method args must match the function's spec types exactly.
// Manual approach — use when you don't have the contract spec:
// See "XdrSCSpecType Constants to XdrSCVal Factories" mapping table below.

// Read call -- auto-detected, simulation only, no signing needed
$result = $client->invokeMethod('get_balance', [
    Address::fromAccountId($keyPair->getAccountId())->toXdrSCVal(),
]);
$balance = gmp_strval($result->toBigInt());

// Write call -- auto-detected, simulates + signs + sends
$result = $client->invokeMethod('transfer', [
    Address::fromAccountId($keyPair->getAccountId())->toXdrSCVal(),
    Address::fromAccountId('GDEST...')->toXdrSCVal(),
    XdrSCVal::forI128(new XdrInt128Parts(0, 1000)),
]);

// With custom options (higher fee, shorter timeout)
$result = $client->invokeMethod('expensive_op', null, methodOptions: new MethodOptions(
    fee: 10000,
    timeoutInSeconds: 60,
    restore: true, // auto-restore archived state (default)
));

// Discover available methods
$methodNames = $client->getMethodNames(); // ['transfer', 'balance', ...]
```

**Preferred: use `funcArgsToXdrSCValues` when you have the contract spec.** Pass native PHP values and let the spec handle type conversion:

```php
// SorobanClient already has the spec loaded — access it via getContractSpec()
$spec = $client->getContractSpec();

// Read call with auto-converted args
$args = $spec->funcArgsToXdrSCValues('balance', [
    'id' => $keyPair->getAccountId(),  // String → Address (automatic)
]);
$result = $client->invokeMethod('balance', $args);
$balance = gmp_strval($result->toBigInt());

// Write call with auto-converted args
$args = $spec->funcArgsToXdrSCValues('transfer', [
    'from' => $keyPair->getAccountId(),  // String → Address
    'to' => 'GDEST...',                   // String → Address
    'amount' => 1000,                     // int → I128
]);
$result = $client->invokeMethod('transfer', $args);
```

### Low-Level: SorobanServer

Full control over simulation, signing, and submission.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$keyPair = KeyPair::random(); // or KeyPair::fromSeed($yourSecret)
$contractId = 'CABC123...';
$server = new SorobanServer('https://soroban-testnet.stellar.org');
$network = Network::testnet();

// 1. Build the transaction
$account = $server->getAccount($keyPair->getAccountId());
$hostFunction = new InvokeContractHostFunction($contractId, 'transfer', [
    Address::fromAccountId($keyPair->getAccountId())->toXdrSCVal(),
    Address::fromAccountId('GDEST...')->toXdrSCVal(),
    XdrSCVal::forI128(new XdrInt128Parts(0, 500)),
]);
$op = (new InvokeHostFunctionOperationBuilder($hostFunction))->build();
$tx = (new TransactionBuilder($account))
    ->addOperation($op)
    ->setMaxOperationFee(1000)
    ->build();

// 2. Simulate to get resource fees and footprint
$simResponse = $server->simulateTransaction(
    new SimulateTransactionRequest(transaction: $tx),
);
$tx->setSorobanTransactionData($simResponse->transactionData);
$tx->addResourceFee($simResponse->minResourceFee);
$tx->setSorobanAuth($simResponse->getSorobanAuth());

// 3. Sign and send
$tx->sign($keyPair, $network);
$sendResponse = $server->sendTransaction($tx);
if ($sendResponse->status === SendTransactionResponse::STATUS_ERROR) {
    throw new \RuntimeException("Send failed: {$sendResponse->errorResultXdr}");
}

// 4. Poll for result
$txResponse = $server->getTransaction($sendResponse->hash);
while ($txResponse->getStatus() === GetTransactionResponse::STATUS_NOT_FOUND) {
    sleep(3);
    $txResponse = $server->getTransaction($sendResponse->hash);
}
if ($txResponse->getStatus() === GetTransactionResponse::STATUS_SUCCESS) {
    $resultValue = $txResponse->getResultValue(); // XdrSCVal
}
```

## Argument Encoding

Build contract arguments using `XdrSCVal` factory methods.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;

// Primitives
$boolVal   = XdrSCVal::forBool(true);
$u32Val    = XdrSCVal::forU32(42);
$i32Val    = XdrSCVal::forI32(-10);
$u64Val    = XdrSCVal::forU64(1_000_000);
$i64Val    = XdrSCVal::forI64(-500);
$symbolVal = XdrSCVal::forSymbol('transfer');
$stringVal = XdrSCVal::forString('hello world');
$bytesVal  = XdrSCVal::forBytes(hex2bin('deadbeef'));
$voidVal   = XdrSCVal::forVoid();

// 128-bit integers -- two approaches
$i128Parts  = XdrSCVal::forI128(new XdrInt128Parts(0, 1_000_000)); // hi, lo
$i128BigInt = XdrSCVal::forI128BigInt('999999999999999999');        // GMP string

// Addresses (account or contract)
$accountAddr  = Address::fromAccountId('GABC...')->toXdrSCVal();
$contractAddr = Address::fromContractId('CABC...')->toXdrSCVal();

// Vec (ordered array of values)
$vecVal = XdrSCVal::forVec([
    XdrSCVal::forU32(1),
    XdrSCVal::forU32(2),
    XdrSCVal::forU32(3),
]);

// Map (key-value pairs)
$mapVal = XdrSCVal::forMap([
    new XdrSCMapEntry(XdrSCVal::forSymbol('name'), XdrSCVal::forString('Alice')),
    new XdrSCMapEntry(XdrSCVal::forSymbol('age'), XdrSCVal::forU32(30)),
]);
```

## Result Parsing

Extract typed values from contract return values.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;

/** @var XdrSCVal $result */

switch ($result->type->value) {
    case XdrSCValType::SCV_BOOL:
        $value = $result->b; // bool
        break;
    case XdrSCValType::SCV_U32:
        $value = $result->u32; // int
        break;
    case XdrSCValType::SCV_I128:
        $gmpValue = $result->toBigInt(); // GMP object
        $readable = gmp_strval($gmpValue);
        break;
    case XdrSCValType::SCV_SYMBOL:
        $value = $result->sym; // string
        break;
    case XdrSCValType::SCV_ADDRESS:
        $strKey = $result->address->toStrKey(); // G... or C... address
        break;
    case XdrSCValType::SCV_MAP:
        foreach ($result->map ?? [] as $entry) {
            $key = $entry->key;  // XdrSCVal
            $val = $entry->val;  // XdrSCVal
        }
        break;
    case XdrSCValType::SCV_VEC:
        foreach ($result->vec ?? [] as $item) {
            // Each $item is XdrSCVal
        }
        break;
}
```

## Multi-Auth Workflows

When a contract requires authorization from multiple parties (e.g., atomic swap).

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\ClientOptions;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Xdr\XdrInt128Parts;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$aliceKeyPair = KeyPair::fromSeed('SALICE...');
$bobKeyPair   = KeyPair::fromSeed('SBOB...');

$client = SorobanClient::forClientOptions(new ClientOptions(
    sourceAccountKeyPair: $aliceKeyPair,
    contractId: 'CSWAP...',
    network: Network::testnet(),
    rpcUrl: 'https://soroban-testnet.stellar.org',
));

$args = [
    Address::fromAccountId($aliceKeyPair->getAccountId())->toXdrSCVal(),
    Address::fromAccountId($bobKeyPair->getAccountId())->toXdrSCVal(),
    Address::fromContractId('CTOKENA...')->toXdrSCVal(),
    Address::fromContractId('CTOKENB...')->toXdrSCVal(),
    XdrSCVal::forI128(new XdrInt128Parts(0, 1000)),
    XdrSCVal::forI128(new XdrInt128Parts(0, 4500)),
    XdrSCVal::forI128(new XdrInt128Parts(0, 5000)),
    XdrSCVal::forI128(new XdrInt128Parts(0, 950)),
];

// Build and simulate (populates auth entries)
$tx = $client->buildInvokeMethodTx('swap', $args);

// Check who else needs to sign besides the invoker (Alice)
$signers = $tx->needsNonInvokerSigningBy(); // Returns account IDs

// Sign Bob's auth entries with his local key
$tx->signAuthEntries(signerKeyPair: $bobKeyPair);

// Or sign via remote callback (when Bob's key is on another server)
$bobPublicKeyPair = KeyPair::fromAccountId($bobKeyPair->getAccountId());
$tx->signAuthEntries(
    signerKeyPair: $bobPublicKeyPair,
    authorizeEntryCallback: function (
        SorobanAuthorizationEntry $entry,
        Network $network,
    ): SorobanAuthorizationEntry {
        $base64Entry = $entry->toBase64Xdr();
        // Send to remote signer, receive signed entry back
        $signedBase64 = sendToRemoteSigner($base64Entry);
        return SorobanAuthorizationEntry::fromBase64Xdr($signedBase64);
    },
);

// Sign the transaction envelope and send
$response = $tx->signAndSend(); // returns GetTransactionResponse
echo $response->getStatus();     // e.g. "SUCCESS"
```

## TTL Extension and Restore

### Extend Footprint TTL

Extend the time-to-live of contract data. When using `SorobanClient`, set `MethodOptions::$restore = true` (the default) for automatic handling.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\ExtendFootprintTTLOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\TransactionBuilder;

$keyPair = KeyPair::random(); // or KeyPair::fromSeed($yourSecret)
$server = new SorobanServer('https://soroban-testnet.stellar.org');
$network = Network::testnet();

$account = $server->getAccount($keyPair->getAccountId());
$extendTo = 535_670; // ledger count (~30 days)

$op = (new ExtendFootprintTTLOperationBuilder($extendTo))->build();
$tx = (new TransactionBuilder($account))
    ->addOperation($op)
    ->setMaxOperationFee(10000)
    ->build();

// Simulate populates the footprint with entries to extend
$simResponse = $server->simulateTransaction(
    new SimulateTransactionRequest(transaction: $tx),
);
$tx->setSorobanTransactionData($simResponse->transactionData);
$tx->addResourceFee($simResponse->minResourceFee);
$tx->sign($keyPair, $network);
$server->sendTransaction($tx);
```

### Restore Archived Data

Restore expired contract data back to the ledger.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\RestoreFootprintOperationBuilder;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\TransactionBuilder;

$keyPair = KeyPair::random(); // or KeyPair::fromSeed($yourSecret)
$server = new SorobanServer('https://soroban-testnet.stellar.org');
$network = Network::testnet();

$account = $server->getAccount($keyPair->getAccountId());
$op = (new RestoreFootprintOperationBuilder())->build();
$tx = (new TransactionBuilder($account))
    ->addOperation($op)
    ->setMaxOperationFee(10000)
    ->build();

// Apply the restore preamble from a prior simulation that detected archived entries
// $restorePreamble comes from SimulateTransactionResponse::$restorePreamble
$tx->setSorobanTransactionData($restorePreamble->transactionData);

$simResponse = $server->simulateTransaction(
    new SimulateTransactionRequest(transaction: $tx),
);
$tx->setSorobanTransactionData($simResponse->transactionData);
$tx->addResourceFee($simResponse->minResourceFee);
$tx->sign($keyPair, $network);
$server->sendTransaction($tx);
```

## Reading Contract Data

Query contract state from the ledger without invoking the contract.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$contractId = 'CABC123...';

// Read a specific contract data entry by key
$entry = $server->getContractData(
    $contractId,
    XdrSCVal::forSymbol('counter'),
    XdrContractDataDurability::PERSISTENT(),
);

if ($entry !== null) {
    $ledgerEntryData = $entry->getLedgerEntryDataXdr();
    $contractDataEntry = $ledgerEntryData->contractData;
    $value = $contractDataEntry->val; // XdrSCVal
    echo "Counter: {$value->u32}\n";
    echo "Live until ledger: {$entry->liveUntilLedgerSeq}\n";
}
```

## Contract Introspection

Examine available methods, types, and events from a contract. Three approaches depending on what you have:

### Loading Contract Info

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Soroban\SorobanContractParser;
use Soneso\StellarSDK\Soroban\SorobanServer;

// From local WASM bytes (no network call -- parse before installing)
$wasmBytes = file_get_contents('/path/to/contract.wasm');
$info = SorobanContractParser::parseContractByteCode($wasmBytes);

// From installed WASM hash (network call -- when you only have the hash)
$server = new SorobanServer('https://soroban-testnet.stellar.org');
$info = $server->loadContractInfoForWasmId('a1b2c3...');

// From deployed contract ID (network call)
$info = $server->loadContractInfoForContractId('CABC123...');
```

### Pre-Extracted Arrays

`SorobanContractInfo` provides pre-extracted plain PHP arrays from the raw spec entries.
Use `count()`, `foreach`, and standard array functions — these are NOT collection objects.

```php
// Functions (XdrSCSpecFunctionV0 objects)
$info->funcs       // plain array — use count($info->funcs), not $info->funcs->count()

// User-defined types
$info->udtStructs  // XdrSCSpecUDTStructV0 objects
$info->udtUnions   // XdrSCSpecUDTUnionV0 objects
$info->udtEnums    // XdrSCSpecUDTEnumV0 objects

// Events
$info->events      // XdrSCSpecEventV0 objects
```

### Enumerating Functions and Parameters

Each function has a name, inputs (parameters), and outputs (return types):

```php
foreach ($info->funcs as $func) {
    // $func is XdrSCSpecFunctionV0
    echo "Function: {$func->name}\n";

    foreach ($func->inputs as $input) {
        // $input is XdrSCSpecFunctionInputV0
        $typeName = $input->type->type->value; // int constant from XdrSCSpecType
        echo "  param: {$input->name} (type constant: {$typeName})\n";
    }

    foreach ($func->outputs as $output) {
        // $output is XdrSCSpecTypeDef
        echo "  returns type constant: {$output->type->value}\n";
    }
}
```

### XdrSCSpecType Constants to XdrSCVal Factories

Use this mapping to convert discovered parameter types to the **exact** `XdrSCVal` factory:

<!-- WRONG: overriding discovered type based on convention -->
// Spec says symbol: String (type 16) but "token symbols are usually Symbol"
XdrSCVal::forSymbol('TEST') // WRONG — crashes: UnreachableCodeReached

// CORRECT: always use the exact type from introspection
XdrSCVal::forString('TEST') // CORRECT — spec says String, use forString

| Constant | Value | Type Name | XdrSCVal Factory |
|----------|-------|-----------|-----------------|
| SC_SPEC_TYPE_BOOL | 1 | Bool | `XdrSCVal::forBool($val)` |
| SC_SPEC_TYPE_VOID | 2 | Void | `XdrSCVal::forVoid()` |
| SC_SPEC_TYPE_U32 | 4 | U32 | `XdrSCVal::forU32($val)` |
| SC_SPEC_TYPE_I32 | 5 | I32 | `XdrSCVal::forI32($val)` |
| SC_SPEC_TYPE_U64 | 6 | U64 | `XdrSCVal::forU64($val)` |
| SC_SPEC_TYPE_I64 | 7 | I64 | `XdrSCVal::forI64($val)` |
| SC_SPEC_TYPE_U128 | 10 | U128 | `XdrSCVal::forU128(new XdrUInt128Parts($hi, $lo))` |
| SC_SPEC_TYPE_I128 | 11 | I128 | `XdrSCVal::forI128(new XdrInt128Parts($hi, $lo))` |
| SC_SPEC_TYPE_U256 | 12 | U256 | `XdrSCVal::forU256(...)` |
| SC_SPEC_TYPE_I256 | 13 | I256 | `XdrSCVal::forI256(...)` |
| SC_SPEC_TYPE_BYTES | 14 | Bytes | `XdrSCVal::forBytes($val)` |
| SC_SPEC_TYPE_STRING | 16 | String | `XdrSCVal::forString($val)` |
| SC_SPEC_TYPE_SYMBOL | 17 | Symbol | `XdrSCVal::forSymbol($val)` |
| SC_SPEC_TYPE_ADDRESS | 19 | Address | `Address::fromAccountId($id)->toXdrSCVal()` |

### Enumerating User-Defined Types and Events

```php
foreach ($info->udtStructs as $struct) {
    echo "Struct: {$struct->name}\n";
    foreach ($struct->fields as $field) {
        echo "  field: {$field->name} (type: {$field->type->type->value})\n";
    }
}

foreach ($info->udtEnums as $enum) {
    echo "Enum: {$enum->name}\n";
    foreach ($enum->cases as $case) {
        echo "  case: {$case->name} = {$case->value}\n";
    }
}

foreach ($info->events as $event) {
    echo "Event: {$event->name}\n";
}
```

