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

### Predicting the Contract Id

`Address::deriveContractId(Address $deployer, string $salt, Network $network): string`
returns the contract id ("C...") a deployment from that deployer and salt creates. It
applies to every deployment issued from an address — `deploy()`, `deployFromExternalRef()`
and the create-operation builders alike — because the id derives from deployer, salt and
network only; the executable does not enter it. Use it when the address is needed before
deploying, for example in another contract's constructor arguments. The salt is 32 raw
bytes, not hex, and anything else throws `InvalidArgumentException`.

### Deploy from an External Reference (Protocol 28)

A CAP-85 external reference names an owner contract and a tag; the owner's persistent
entry under that tag holds the wasm hash the new instance runs. There is no install
step. `SorobanClient::deployFromExternalRef` resolves the reference before the
transaction is built (an unresolvable reference throws naming the owner and the tag),
loads the spec from the resolved wasm, and returns a ready client:

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Soroban\Contract\DeployFromExternalRefRequest;
use Soneso\StellarSDK\Soroban\Contract\SorobanClient;

$keyPair = KeyPair::random(); // or KeyPair::fromSeed($yourSecret)

$client = SorobanClient::deployFromExternalRef(new DeployFromExternalRefRequest(
    rpcUrl: 'https://soroban-testnet.stellar.org',
    network: Network::testnet(),
    sourceAccountKeyPair: $keyPair,
    executableOwner: Address::fromContractId('CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE'),
    tag: 'token-v1', // matched byte for byte
    // constructorArgs and salt work as in DeployRequest
));
```

`deployFromExternalRef` builds the `CREATE_CONTRACT_V2` host function form (empty
constructor-argument vector when no args are given), as `deploy()` does.

Without SorobanClient, build the create operation directly with
`CreateContractFromExternalRefHostFunction(Address $address, Address $executableOwner,
string $tag, ?string $salt = null)` in an `InvokeHostFunctionOperationBuilder` and
submit it like any `InvokeHostFunctionOperation`.
`CreateContractFromExternalRefWithConstructorHostFunction` adds `array $constructorArgs`
after the tag. Both builders reject an executable owner that is not a contract address
(constructor, `setExecutableOwner()`, and `toXdr()`). Envelope parsing returns these
classes for external-ref create operations.

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

### Converting results to native PHP values

`public function toNative(): mixed` on `XdrSCVal` recursively converts a value tree to
native PHP values on a best-effort basis. It never throws: an arm with no faithful
native representation returns that `XdrSCVal` (sub)value unchanged, same instance, not a
copy.

| XDR type | Native result |
|---|---|
| Bool | `bool` |
| Void | `null` |
| U32, I32, I64 | `int` |
| U64, Timepoint, Duration | `int`, or an unsigned decimal `string` when the value exceeds `PHP_INT_MAX` |
| U128, I128, U256, I256 | `GMP` (same as `toBigInt()`) |
| Bytes | `string` (raw binary) |
| String, Symbol | `string` |
| Vec | list `array`, elements converted recursively, order preserved |
| Map | keyed `array` (rules below), or the `XdrSCVal` itself when the map can't convert |
| Address | `Address` |
| Error, Contract Instance, Ledger Key Contract Instance, Ledger Key Nonce, Executable Tag, any other/future type | the `XdrSCVal` itself |

Map key rules (PHP array keys can only be `int` or `string`):

| Key arm | PHP array key |
|---|---|
| Symbol, String | `string` |
| U32, I32, I64 | `int` |
| U64, Timepoint, Duration | `int`, or unsigned decimal `string` per the uint64 rule |
| U128, I128, U256, I256 | decimal `string` |
| Bytes | the raw binary `string` |
| Address | StrKey `string` (`G...`/`C...`/...) — not an `Address` object, unlike the value conversion |
| any other arm, or a required key payload that is `null` | unrepresentable |

The whole map falls back to the `XdrSCVal` itself (values left unconverted) when a key
is unrepresentable, or when two entries collide on the same PHP key after coercion. A
fallback of a nested map is contained: the enclosing vec/map still converts, with the
problematic map left as an `XdrSCVal` element.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;

XdrSCVal::forU32(4294967295)->toNative(); // int(4294967295)

// uint64 above PHP_INT_MAX: the stored bit pattern wraps, so -1 means 2^64-1
XdrSCVal::forU64(-1)->toNative(); // string(20) "18446744073709551615"

// Map with symbol keys, insertion order preserved
$val = XdrSCVal::forMap([
    new XdrSCMapEntry(XdrSCVal::forSymbol('name'), XdrSCVal::forString('Alice')),
    new XdrSCMapEntry(XdrSCVal::forSymbol('age'), XdrSCVal::forU32(30)),
]);
$val->toNative(); // ['name' => 'Alice', 'age' => 30]
```

**Trap: uint64 values above `PHP_INT_MAX` arrive as strings, not int.**

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrSCVal;

// WRONG: assumes toNative() always returns int for U64/Timepoint/Duration
$seconds = XdrSCVal::forU64(-1)->toNative();
$next = $seconds + 1; // silently loses precision: becomes a float, not an error

// CORRECT: a uint64 whose true value exceeds PHP_INT_MAX comes back as a string
$seconds = XdrSCVal::forU64(-1)->toNative();
$next = is_int($seconds) ? $seconds + 1 : gmp_add($seconds, 1);
```

**Trap: detecting a map fallback with `instanceof XdrSCVal`.**

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$mapWithBoolKey = XdrSCVal::forMap([
    new XdrSCMapEntry(XdrSCVal::forBool(true), XdrSCVal::forU32(1)),
]);

// WRONG: assumes toNative() on a map always returns an array
$native = $mapWithBoolKey->toNative();
foreach ($native as $key => $value) { // no exception: silently iterates the object's public properties (type, b, u32, ...) instead of map entries — wrong data, not a crash
    // ...
}

// CORRECT: check for the fallback before treating the result as an array
$native = $mapWithBoolKey->toNative();
if ($native instanceof XdrSCVal) {
    // Same instance as $mapWithBoolKey; inspect $native->map manually.
    foreach ($native->map ?? [] as $entry) {
        // $entry->key and $entry->val are XdrSCVal
    }
} else {
    // $native is a keyed array
}
```

**Trap: address value/key asymmetry — a map key never becomes an `Address` object.**

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCMapEntry;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$accountId = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
$mapVal = XdrSCVal::forMap([
    new XdrSCMapEntry(XdrSCVal::forAddress(XdrSCAddress::forAccountId($accountId)), XdrSCVal::forU32(1)),
]);

// WRONG: expects an Address object as the map key, like an address VALUE converts to
$native = $mapVal->toNative();
$key = array_key_first($native);
$key->toStrKey(); // Error: $key is a string ('G...'), not an Address

// CORRECT: an address used as a map key converts to its StrKey string directly
$native = $mapVal->toNative();
$key = array_key_first($native); // already 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H'

// An address VALUE (not a key) still converts to an Address object:
$addressValue = XdrSCVal::forAddress(XdrSCAddress::forAccountId($accountId))->toNative();
$addressValue->toStrKey(); // works: $addressValue is an Address
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

## Protocol 27 Credentials (CAP-71)

`SorobanCredentials` has four arms: source-account, legacy `ADDRESS` (valid on all protocols), `ADDRESS_V2` (the default arm), and `ADDRESS_WITH_DELEGATES` (the latter two protocol 27+; invalid below 27). `forAddress()`/`forAddressCredentials()` build `ADDRESS_V2`; `forAddressLegacy()`/`forAddressCredentialsLegacy()` build legacy `ADDRESS`. All signing APIs handle every arm; `getAddressCredentials()` returns the inner `SorobanAddressCredentials` for any address arm (null only for source-account); `getCredentialType()` / `isSourceAccount()` inspect the arm.

Simulation requests V2 entries by default (`useUpgradedAuth` is `true` on `MethodOptions` and `SimulateTransactionRequest`, always sent on the wire); pass `false` for legacy `ADDRESS` entries. RPCs without protocol 27 support silently ignore the flag and return legacy `ADDRESS` entries — detect support by checking the returned credential arm, not by expecting an error.

`ADDRESS_WITH_DELEGATES` lets delegate addresses co-sign one entry. Simulation never returns this arm; build it from an `ADDRESS`/`ADDRESS_V2` entry via `SorobanAuthorizationEntry::withDelegates($source, $expirationLedger, $delegates)`, passing `SorobanDelegateDescriptor` objects. The builder sorts the delegate array and rejects duplicates. All nodes (top-level + delegates at any depth) sign the same payload bound to the top-level address; delegates carry no nonce/expiration. `sign($kp, $network, forAddress: $strkey)` routes a signature to matching nodes depth-first; `null` signs top-level. A void top-level with all delegates signed is valid (delegates-only). After attaching the signed entries, re-simulate in enforcing mode (`new SimulateTransactionRequest($tx, authMode: 'enforce')`) and apply the returned `transactionData` / `minResourceFee` before submitting: the recording simulation does not run `__check_auth`, so for a custom (contract) account it omits the footprint its authorization reads (and understates the delegate fee). For multiple classical signatures on one node, call `sign()` in ascending public-key order (the SDK appends in call order and does not sort).

```php
<?php
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
use Soneso\StellarSDK\Soroban\SorobanDelegateDescriptor;

// $sourceEntry is an ADDRESS/ADDRESS_V2 entry; $expirationLedger from $server->getLatestLedger()->getSequence()
$delegated = SorobanAuthorizationEntry::withDelegates(
    $sourceEntry,
    $expirationLedger,
    [new SorobanDelegateDescriptor($delegateKeyPair->getAccountId())],
);
$delegated->sign($delegateKeyPair, Network::testnet(), forAddress: $delegateKeyPair->getAccountId());
```

The constructor is `new SorobanCredentials(int|SorobanAddressCredentials $credentialType = SOROBAN_CREDENTIALS_SOURCE_ACCOUNT, ?SorobanAddressCredentials $addressCredentials = null, ?SorobanAddressCredentialsWithDelegates $addressWithDelegates = null)`. A `SorobanAddressCredentials` in the first position selects the ADDRESS arm, so passing one positionally works; the named argument for that object is `credentialType`, or use `forAddressCredentialsLegacy(...)`. `XdrSorobanCredentialsType`, `XdrEnvelopeType` and `XdrHashIDPreimage` carry cases for the V2 and delegated arms, so an exhaustive `match`/`switch` over them needs a `default` arm.

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

A contract created from a CAP-85 external reference (Protocol 28) resolves automatically.
See `rpc.md` > Contract Introspection Helpers for `loadWasmIdForExternalRef()`.

### Pre-Extracted Arrays

`SorobanContractInfo` provides pre-extracted plain PHP arrays from the raw spec entries.
Use `count()`, `foreach`, and standard array functions — these are NOT collection objects.

```text
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

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrSCVal;

// WRONG: overriding the discovered type based on convention.
// Spec says symbol: String (type 16) but "token symbols are usually Symbol"
XdrSCVal::forSymbol('TEST'); // crashes: UnreachableCodeReached

// CORRECT: always use the exact type from introspection
XdrSCVal::forString('TEST'); // spec says String, so use forString
```

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

