# Soroban RPC API Reference

The `SorobanServer` class (`Soneso\StellarSDK\Soroban\SorobanServer`) is a JSON-RPC 2.0 client for interacting with Soroban RPC servers. It provides typed methods for all 12 RPC endpoints plus convenience helpers for account lookups and contract data retrieval.

## Creating a SorobanServer

```php
<?php

declare(strict_types=1);

use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

// Optional: enable PSR-3 request/response logging for debugging
// $server->setLogger($yourPsr3Logger);
```

## Health & Network Info

### getHealth

Check if the RPC server is healthy and ready.

```php
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$healthResponse = $server->getHealth();

printf("Status: %s\n", $healthResponse->status);              // "healthy"
printf("Oldest ledger: %d\n", $healthResponse->oldestLedger);
printf("Latest ledger: %d\n", $healthResponse->latestLedger);
printf("Ledger retention: %d\n", $healthResponse->ledgerRetentionWindow);
```

### getNetwork

Get network configuration including passphrase and protocol version.

```php
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$networkResponse = $server->getNetwork();

printf("Passphrase: %s\n", $networkResponse->passphrase);
printf("Protocol: %d\n", $networkResponse->protocolVersion);
printf("Friendbot: %s\n", $networkResponse->friendbotUrl ?? 'N/A');
```

### getFeeStats

Get inclusion fee statistics for both Soroban and classic transactions.

```php
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$feeStats = $server->getFeeStats();

// Soroban inclusion fees
printf("Soroban fee (max): %s\n", $feeStats->sorobanInclusionFee->max);
printf("Soroban fee (p50): %s\n", $feeStats->sorobanInclusionFee->p50);
printf("Soroban fee (p90): %s\n", $feeStats->sorobanInclusionFee->p90);
```

### getVersionInfo

Get version information about the RPC server and its embedded Captive Core.

```php
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$versionInfo = $server->getVersionInfo();

printf("RPC version: %s\n", $versionInfo->version);
printf("Protocol: %d\n", $versionInfo->protocolVersion);
printf("Commit hash: %s\n", $versionInfo->commitHash);
printf("Core version: %s\n", $versionInfo->captiveCoreVersion);
```

## Ledger Methods

### getLatestLedger

Get the sequence number and hash of the latest ledger known to the server.

```php
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$latestLedger = $server->getLatestLedger();

printf("Sequence: %d\n", $latestLedger->sequence);
printf("Hash: %s\n", $latestLedger->id);
printf("Protocol: %d\n", $latestLedger->protocolVersion);
```

### getLedgerEntries

Read ledger entries directly by key. Useful for inspecting contract state, account data, or contract code.

```php
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryType;
use Soneso\StellarSDK\Xdr\XdrLedgerKeyContractData;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

// Build a ledger key for contract data
$contractId = 'CABC...'; // Your contract ID
$ledgerKey = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_DATA());
$ledgerKey->contractData = new XdrLedgerKeyContractData(
    Address::fromContractId($contractId)->toXdr(),
    XdrSCVal::forSymbol('counter'),
    XdrContractDataDurability::PERSISTENT()
);

$response = $server->getLedgerEntries([$ledgerKey->toBase64Xdr()]);

printf("Latest ledger: %d\n", $response->latestLedger);
if ($response->entries !== null) {
    foreach ($response->entries as $entry) {
        printf("Key: %s\n", $entry->key);
        printf("XDR: %s\n", $entry->xdr);
        printf("Last modified: %d\n", $entry->lastModifiedLedgerSeq);
    }
}
```

## Account & Contract Data Helpers

### getAccount

Retrieve account information (primarily the sequence number) via RPC instead of Horizon. Returns an `Account` object ready for transaction building.

```php
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$accountId = 'GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54';

$account = $server->getAccount($accountId);
if ($account !== null) {
    printf("Account: %s\n", $account->getAccountId());
    printf("Sequence: %s\n", $account->getSequenceNumber());
    // $account can be used directly as TransactionBuilderAccount
}
```

### getContractData

Read a specific contract data entry by key and durability.

```php
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$contractId = 'CABC...'; // Your contract ID

$entry = $server->getContractData(
    $contractId,
    XdrSCVal::forSymbol('balance'),
    XdrContractDataDurability::PERSISTENT()
);

if ($entry !== null) {
    $dataXdr = $entry->getLedgerEntryDataXdr();
    $val = $dataXdr->contractData->val;
    printf("Value: %s\n", $val->toBase64Xdr());
}
```

## Transaction Methods

### simulateTransaction

Simulate a transaction to get estimated resources, fees, and return values without submitting it to the network. Required before submitting any Soroban transaction.

```php
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\Requests\ResourceConfig;
use Soneso\StellarSDK\Transaction;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

// $tx is a Transaction containing an InvokeHostFunctionOperation
/** @var Transaction $tx */
$simRequest = new SimulateTransactionRequest(
    transaction: $tx,
    resourceConfig: new ResourceConfig(instructionLeeway: 200000)
);

$simResponse = $server->simulateTransaction($simRequest);

if ($simResponse->error === null) {
    printf("Min resource fee: %d\n", $simResponse->minResourceFee);
    printf("Transaction data: %s\n", $simResponse->transactionData?->toBase64Xdr());

    // Apply simulation results to the transaction before sending
    $tx->setSorobanTransactionData($simResponse->transactionData);
    $tx->addResourceFee($simResponse->minResourceFee);
    $tx->setSorobanAuth($simResponse->getSorobanAuth());
} else {
    printf("Simulation error: %s\n", $simResponse->error);
}
```

### sendTransaction

Submit a signed transaction to the network. This method returns immediately after validation -- it does not wait for ledger inclusion. Poll with `getTransaction()` to check the result.

```php
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Transaction;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

// $tx is a signed Transaction with simulation data applied
/** @var Transaction $tx */
$sendResponse = $server->sendTransaction($tx);

printf("Hash: %s\n", $sendResponse->hash);
printf("Status: %s\n", $sendResponse->status); // "PENDING", "DUPLICATE", "ERROR"

if ($sendResponse->status === 'ERROR') {
    printf("Error: %s\n", $sendResponse->errorResultXdr);
}
```

### getTransaction

Check the status of a previously submitted transaction by its hash. Poll this method until the status is no longer `"NOT_FOUND"`.

```php
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$txHash = 'abc123...';

// Poll for transaction result
$maxAttempts = 30;
for ($i = 0; $i < $maxAttempts; $i++) {
    $txResponse = $server->getTransaction($txHash);

    if ($txResponse->status === 'SUCCESS') {
        printf("Confirmed in ledger: %d\n", $txResponse->ledger);
        printf("Result XDR: %s\n", $txResponse->resultXdr);
        break;
    }

    if ($txResponse->status === 'FAILED') {
        printf("Transaction failed: %s\n", $txResponse->resultXdr);
        break;
    }

    // Status is "NOT_FOUND" -- still pending
    sleep(1);
}
```

### getTransactions

Retrieve a paginated list of transactions starting from a given ledger.

```php
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\Requests\GetTransactionsRequest;
use Soneso\StellarSDK\Soroban\Requests\PaginationOptions;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

$request = new GetTransactionsRequest(
    startLedger: 500000,
    paginationOptions: new PaginationOptions(limit: 10)
);

$response = $server->getTransactions($request);

printf("Latest ledger: %d\n", $response->latestLedger);
if ($response->transactions !== null) {
    foreach ($response->transactions as $txInfo) {
        printf("Tx: %s | Status: %s | Ledger: %d\n",
            $txInfo->hash,
            $txInfo->status,
            $txInfo->ledger
        );
    }
}

// Paginate with cursor from response
if ($response->cursor !== null) {
    $nextRequest = new GetTransactionsRequest(
        paginationOptions: new PaginationOptions(
            cursor: $response->cursor,
            limit: 10
        )
    );
    $nextPage = $server->getTransactions($nextRequest);
}
```

## Events

### getEvents

Query contract events emitted within a ledger range. Supports filtering by event type, contract ID, and topic segments. Maximum 24 hours of recent ledger data.

```php
use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\Requests\GetEventsRequest;
use Soneso\StellarSDK\Soroban\Requests\EventFilter;
use Soneso\StellarSDK\Soroban\Requests\EventFilters;
use Soneso\StellarSDK\Soroban\Requests\TopicFilter;
use Soneso\StellarSDK\Soroban\Requests\TopicFilters;
use Soneso\StellarSDK\Soroban\Requests\PaginationOptions;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$contractId = 'CABC...'; // Your contract ID

// Build topic filter: match any first segment, "transfer" as second segment
$topicFilter = new TopicFilter([
    '*',                                              // Wildcard first segment
    XdrSCVal::forSymbol('transfer')->toBase64Xdr()    // Exact match second segment
]);

$eventFilter = new EventFilter(
    type: 'contract',
    contractIds: [$contractId],
    topics: new TopicFilters($topicFilter)
);

$latestLedger = $server->getLatestLedger();
$startLedger = $latestLedger->sequence - 1000; // Look back ~1.4 hours

$request = new GetEventsRequest(
    startLedger: $startLedger,
    filters: new EventFilters($eventFilter),
    paginationOptions: new PaginationOptions(limit: 100)
);

$eventsResponse = $server->getEvents($request);

if ($eventsResponse->events !== null) {
    foreach ($eventsResponse->events as $event) {
        printf("Event ID: %s\n", $event->id);
        printf("  Type: %s\n", $event->type);
        printf("  Ledger: %d\n", $event->ledger);
        printf("  Contract: %s\n", $event->contractId);
        printf("  Topics: %s\n", json_encode($event->topic));
        printf("  Value: %s\n", $event->value);
    }
}
```

### Event Filter Types

The `EventFilter` type field accepts:
- `"contract"` -- events emitted by contract code
- `"system"` -- system-level events (e.g., TTL extensions)
- `"diagnostic"` -- diagnostic events (includes internal host function calls)

Up to 5 filters per request. Up to 5 contract IDs per filter. Topic filters use `"*"` as wildcard for segment matching and base64-encoded `XdrSCVal` for exact segment matching.

## Complete Soroban Transaction Flow

This example shows the full cycle: build, simulate, sign, send, and poll.

```php
<?php

declare(strict_types=1);

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$network = Network::testnet();
$keyPair = KeyPair::fromSeed('SDJHRQF4GCMIIKAAAQ6GR...'); // Use secure key storage
$contractId = 'CABC...';

// 1. Load account from RPC
$account = $server->getAccount($keyPair->getAccountId());

// 2. Build the invocation operation
$invokeFunction = new InvokeContractHostFunction(
    $contractId,
    'increment',
    [XdrSCVal::forU32(5)]
);
$operation = (new InvokeHostFunctionOperationBuilder($invokeFunction))->build();

// 3. Build transaction
$tx = (new TransactionBuilder($account))
    ->addOperation($operation)
    ->build();

// 4. Simulate to get resource estimates
$simRequest = new SimulateTransactionRequest(transaction: $tx);
$simResponse = $server->simulateTransaction($simRequest);

// 5. Apply simulation results
$tx->setSorobanTransactionData($simResponse->transactionData);
$tx->addResourceFee($simResponse->minResourceFee);
$tx->setSorobanAuth($simResponse->getSorobanAuth());

// 6. Sign and send
$tx->sign($keyPair, $network);
$sendResponse = $server->sendTransaction($tx);

// 7. Poll for result
$maxAttempts = 30;
for ($i = 0; $i < $maxAttempts; $i++) {
    $txResponse = $server->getTransaction($sendResponse->hash);
    if ($txResponse->status !== 'NOT_FOUND') {
        printf("Status: %s | Ledger: %d\n", $txResponse->status, $txResponse->ledger);
        break;
    }
    sleep(1);
}
```

## Contract Introspection Helpers

`SorobanServer` includes convenience methods for loading contract bytecode and metadata:

```php
use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$contractId = 'CABC...';

// Load contract code entry (WASM bytecode) by contract ID
$codeEntry = $server->loadContractCodeForContractId($contractId);
if ($codeEntry !== null) {
    printf("Code size: %d bytes\n", strlen($codeEntry->code->value));
}

// Or load by WASM hash directly (works before deployment)
$wasmHash = 'abc123...'; // Hex-encoded WASM hash
$codeEntry = $server->loadContractCodeForWasmId($wasmHash);
```

### Introspecting Contract Interface

Load parsed contract info to discover functions, types, and events.
`SorobanContractInfo` provides pre-extracted arrays: `funcs`, `udtStructs`, `udtUnions`, `udtEnums`, `events`.

```php
// Load by deployed contract ID or by WASM hash
$info = $server->loadContractInfoForContractId($contractId);
// or: $info = $server->loadContractInfoForWasmId($wasmHash);

if ($info !== null) {
    // Enumerate all functions and their parameter types
    foreach ($info->funcs as $func) {
        echo "Function: {$func->name}\n";
        foreach ($func->inputs as $input) {
            // $input->type->type->value is an int constant from XdrSCSpecType
            echo "  param: {$input->name} type={$input->type->type->value}\n";
        }
        foreach ($func->outputs as $output) {
            echo "  returns: {$output->type->value}\n";
        }
    }

    // User-defined types
    foreach ($info->udtStructs as $struct) {
        echo "Struct: {$struct->name}\n";
    }
    foreach ($info->udtEnums as $enum) {
        echo "Enum: {$enum->name}\n";
    }

    // Events
    foreach ($info->events as $event) {
        echo "Event: {$event->name}\n";
    }
}
```

See `soroban_contracts.md` > Contract Introspection for the full XdrSCSpecType-to-XdrSCVal mapping table.

## Method Summary

| RPC Method | SDK Method | Request Class | Response Class |
|------------|-----------|---------------|----------------|
| `getHealth` | `getHealth()` | -- | `GetHealthResponse` |
| `getNetwork` | `getNetwork()` | -- | `GetNetworkResponse` |
| `getFeeStats` | `getFeeStats()` | -- | `GetFeeStatsResponse` |
| `getVersionInfo` | `getVersionInfo()` | -- | `GetVersionInfoResponse` |
| `getLatestLedger` | `getLatestLedger()` | -- | `GetLatestLedgerResponse` |
| `getLedgerEntries` | `getLedgerEntries(array $keys)` | -- | `GetLedgerEntriesResponse` |
| `getLedgers` | `getLedgers(GetLedgersRequest)` | `GetLedgersRequest` | `GetLedgersResponse` |
| `getTransaction` | `getTransaction(string $hash)` | -- | `GetTransactionResponse` |
| `getTransactions` | `getTransactions(GetTransactionsRequest)` | `GetTransactionsRequest` | `GetTransactionsResponse` |
| `getEvents` | `getEvents(GetEventsRequest)` | `GetEventsRequest` | `GetEventsResponse` |
| `simulateTransaction` | `simulateTransaction(SimulateTransactionRequest)` | `SimulateTransactionRequest` | `SimulateTransactionResponse` |
| `sendTransaction` | `sendTransaction(Transaction)` | -- | `SendTransactionResponse` |

Helper methods (not direct RPC calls):
- `getAccount(string $accountId): ?Account` -- fetches account via `getLedgerEntries`
- `getContractData(string $contractId, XdrSCVal $key, XdrContractDataDurability $durability): ?LedgerEntry`
- `loadContractCodeForContractId(string $contractId): ?XdrContractCodeEntry`
- `loadContractCodeForWasmId(string $wasmId): ?XdrContractCodeEntry`
- `loadContractInfoForContractId(string $contractId): ?SorobanContractInfo`
- `loadContractInfoForWasmId(string $wasmId): ?SorobanContractInfo`

All RPC methods throw `GuzzleException` on network errors. Non-200 HTTP responses throw `\RuntimeException`. JSON parse failures throw `\InvalidArgumentException`. RPC-level errors are captured in the response object's `error` property.
