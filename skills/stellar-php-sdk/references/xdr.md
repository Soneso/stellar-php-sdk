# XDR Encoding & Decoding

## Overview

XDR (External Data Representation) is the binary serialization format used by Stellar for all on-chain data: transactions, operations, ledger entries, and smart contract values. The PHP SDK provides 309 XDR classes in `Soneso\StellarSDK\Xdr\` for encoding and decoding.

## Transaction Envelope Encoding

Encode a transaction to base64 XDR for storage, sharing, or submission.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\AssetTypeNative;
use Soneso\StellarSDK\TransactionBuilder;

$sourceKeyPair = KeyPair::random(); // or KeyPair::fromSeed($yourSecret)
$destinationId = 'GDEST...';
$network = Network::testnet();

// Assume $account is loaded from the network
$account = new Account($sourceKeyPair->getAccountId(), new \phpseclib3\Math\BigInteger(12345));

$tx = (new TransactionBuilder($account))
    ->addOperation(
        (new PaymentOperationBuilder($destinationId, new AssetTypeNative(), '100.50'))->build()
    )
    ->setMaxOperationFee(200)
    ->build();

$tx->sign($sourceKeyPair, $network);

// Encode to base64 XDR (most common format for sharing/submission)
$base64Envelope = $tx->toEnvelopeXdrBase64();

// Encode to raw XDR bytes
$xdrEnvelope = $tx->toEnvelopeXdr(); // XdrTransactionEnvelope object
$rawBytes = $xdrEnvelope->encode();  // binary string

// Transaction XDR (without signatures)
$txXdrBase64 = $tx->toXdrBase64();
```

## Transaction Envelope Decoding

Decode a base64 XDR string back into a transaction object.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\FeeBumpTransaction;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;

$base64Xdr = 'AAAAAgAAAAD...'; // base64-encoded transaction envelope

// Method 1: Parse into typed transaction (Transaction or FeeBumpTransaction)
$transaction = AbstractTransaction::fromEnvelopeBase64XdrString($base64Xdr);

if ($transaction instanceof Transaction) {
    $sourceAccount = $transaction->getSourceAccount();
    $fee = $transaction->getFee();
    $operations = $transaction->getOperations();
    $memo = $transaction->getMemo();
    $signatures = $transaction->getSignatures();
    $sequenceNumber = $transaction->getSequenceNumber();
} elseif ($transaction instanceof FeeBumpTransaction) {
    $innerTx = $transaction->getInnerTx();
    $feeSource = $transaction->getFeeAccount();
}

// Method 2: Decode to XDR envelope for low-level inspection
$xdrEnvelope = XdrTransactionEnvelope::fromEnvelopeBase64XdrString($base64Xdr);
$envelopeType = $xdrEnvelope->getType()->getValue(); // ENVELOPE_TYPE_TX, etc.

if ($xdrEnvelope->getV1() !== null) {
    $xdrTx = $xdrEnvelope->getV1()->tx;
    $xdrOps = $xdrTx->operations;
    $xdrSignatures = $xdrEnvelope->getV1()->signatures;
}
```

## Inspecting Transactions Before Signing

Security best practice: always review transaction contents before signing.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\Xdr\XdrOperationType;

$base64Xdr = 'AAAAAgAAAAD...'; // received from external source
$transaction = AbstractTransaction::fromEnvelopeBase64XdrString($base64Xdr);

if ($transaction instanceof Transaction) {
    echo "Source: {$transaction->getSourceAccount()->getAccountId()}\n";
    echo "Fee: {$transaction->getFee()} stroops\n";
    echo "Sequence: {$transaction->getSequenceNumber()}\n";

    foreach ($transaction->getOperations() as $index => $op) {
        $xdrBody = $op->toXdr()->getBody();
        $opType = $xdrBody->getType()->getValue();

        echo "Operation {$index}: ";
        echo match ($opType) {
            XdrOperationType::PAYMENT => "Payment",
            XdrOperationType::CREATE_ACCOUNT => "Create Account",
            XdrOperationType::INVOKE_HOST_FUNCTION => "Invoke Host Function (Soroban)",
            XdrOperationType::EXTEND_FOOTPRINT_TTL => "Extend Footprint TTL",
            XdrOperationType::RESTORE_FOOTPRINT => "Restore Footprint",
            default => "Type {$opType}",
        };
        echo "\n";
    }

    // Check Soroban transaction data (resource footprint)
    $sorobanData = $transaction->getSorobanTransactionData();
    if ($sorobanData !== null) {
        $readOnly = $sorobanData->resources->footprint->readOnly;
        $readWrite = $sorobanData->resources->footprint->readWrite;
        echo "Footprint: " . count($readOnly) . " read, " . count($readWrite) . " write\n";
    }

    // Verify existing signatures count
    echo "Signatures: " . count($transaction->getSignatures()) . "\n";
}
```

## XDR Type Construction for Contract Arguments

The `XdrSCVal` class is the central type for Soroban smart contract values. See [soroban_contracts.md](./soroban_contracts.md) for argument encoding examples.

### Key XdrSCVal Factory Methods

| Method | Type | PHP Input |
|--------|------|-----------|
| `XdrSCVal::forBool($b)` | Boolean | `bool` |
| `XdrSCVal::forVoid()` | Void | -- |
| `XdrSCVal::forU32($n)` | Unsigned 32-bit | `int` |
| `XdrSCVal::forI32($n)` | Signed 32-bit | `int` |
| `XdrSCVal::forU64($n)` | Unsigned 64-bit | `int` |
| `XdrSCVal::forI64($n)` | Signed 64-bit | `int` |
| `XdrSCVal::forI128(XdrInt128Parts)` | Signed 128-bit | `XdrInt128Parts($hi, $lo)` |
| `XdrSCVal::forI128BigInt($v)` | Signed 128-bit | `GMP\|string\|int` |
| `XdrSCVal::forU128BigInt($v)` | Unsigned 128-bit | `GMP\|string\|int` |
| `XdrSCVal::forSymbol($s)` | Symbol | `string` |
| `XdrSCVal::forString($s)` | String | `string` |
| `XdrSCVal::forBytes($b)` | Bytes | `string` (raw) |
| `XdrSCVal::forAddress(XdrSCAddress)` | Address | `XdrSCAddress` |
| `XdrSCVal::forVec(array)` | Vec | `array<XdrSCVal>` |
| `XdrSCVal::forMap(array)` | Map | `array<XdrSCMapEntry>` |

### Base64 Serialization of XdrSCVal

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrSCVal;

// Encode an XdrSCVal to base64 XDR
$val = XdrSCVal::forSymbol('hello');
$base64 = $val->toBase64Xdr(); // base64 string

// Decode an XdrSCVal from base64 XDR
$decoded = XdrSCVal::fromBase64Xdr($base64);
echo $decoded->sym; // 'hello'
```

## Ledger Key Construction

Build ledger keys for querying contract state via `getLedgerEntries`.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Xdr\XdrContractDataDurability;
use Soneso\StellarSDK\Xdr\XdrLedgerKey;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCVal;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$contractId = 'CABC123...';

// Contract data key (persistent storage)
$contractAddress = XdrSCAddress::forContractId($contractId);
$dataKey = XdrLedgerKey::forContractData(
    $contractAddress,
    XdrSCVal::forSymbol('counter'),
    XdrContractDataDurability::PERSISTENT(),
);

// Contract instance key
$instanceKey = XdrLedgerKey::forContractData(
    $contractAddress,
    XdrSCVal::forLedgerKeyContractInstance(),
    XdrContractDataDurability::PERSISTENT(),
);

// Contract code key (WASM bytecode)
$codeKey = XdrLedgerKey::forContractCode('a1b2c3...'); // hex wasm hash

// Account key
$accountKey = XdrLedgerKey::forAccountId('GABC...');

// Encode to base64 for getLedgerEntries RPC call
$base64Keys = [
    $dataKey->toBase64Xdr(),
    $instanceKey->toBase64Xdr(),
];

$response = $server->getLedgerEntries($base64Keys);
if ($response->entries !== null) {
    foreach ($response->entries as $entry) {
        $ledgerEntryData = $entry->getLedgerEntryDataXdr();
        // Process XdrLedgerEntryData based on its type
        echo "Last modified: ledger {$entry->lastModifiedLedgerSeq}\n";
    }
}
```

## XdrSCAddress Construction

Build Soroban addresses for contract arguments and ledger keys.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCVal;

// From account ID (G... address)
$accountAddress = XdrSCAddress::forAccountId('GABC...');

// From contract ID (C... or hex)
$contractAddress = XdrSCAddress::forContractId('CABC...');

// Wrap in XdrSCVal for use as contract argument
$addressVal = XdrSCVal::forAddress($accountAddress);

// Convert back to StrKey
$strKey = $accountAddress->toStrKey(); // 'GABC...'
```

## XdrTransactionEnvelope Base64 Round-Trip

Encode and decode transaction envelopes for RPC submission or multi-sig coordination.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;

// From XdrTransactionEnvelope to base64
/** @var XdrTransactionEnvelope $envelope */
$base64 = $envelope->toBase64Xdr();

// From base64 back to XdrTransactionEnvelope
$decoded = XdrTransactionEnvelope::fromEnvelopeBase64XdrString($base64);

// Access typed transaction data
$v1Envelope = $decoded->getV1();
if ($v1Envelope !== null) {
    $xdrTx = $v1Envelope->tx;
    echo "Fee: {$xdrTx->fee}\n";
    echo "Operations: " . count($xdrTx->operations) . "\n";
    echo "Signatures: " . count($v1Envelope->signatures) . "\n";
}
```

## Soroban Transaction Data (Resource Footprint)

Inspect and manipulate the Soroban-specific transaction data that defines resource usage.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;

// Decode from base64 (e.g., from simulation response)
$base64SorobanData = 'AAAA...'; // from RPC response
$txData = XdrSorobanTransactionData::fromBase64Xdr($base64SorobanData);

// Inspect resources
$footprint = $txData->resources->footprint;
echo "Read-only entries: " . count($footprint->readOnly) . "\n";
echo "Read-write entries: " . count($footprint->readWrite) . "\n";
echo "Resource fee: {$txData->resourceFee} stroops\n";

// Encode back to base64
$reEncoded = $txData->toBase64Xdr();
```

## SorobanAuthorizationEntry XDR

Serialize and deserialize authorization entries for multi-sig workflows.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;

// Encode authorization entry for transport to remote signer
/** @var SorobanAuthorizationEntry $authEntry */
$base64 = $authEntry->toBase64Xdr();

// Decode on the remote server
$decoded = SorobanAuthorizationEntry::fromBase64Xdr($base64);

// Sign and encode back
// $decoded->sign($signerKeyPair, $network);
$signedBase64 = $decoded->toBase64Xdr();
```

## XDR Ledger Key Base64

Encode ledger keys for use with the `getLedgerEntries` RPC method.

```php
<?php
declare(strict_types=1);

use Soneso\StellarSDK\Xdr\XdrLedgerKey;

// Encode to base64
/** @var XdrLedgerKey $ledgerKey */
$base64Key = $ledgerKey->toBase64Xdr();

// Decode from base64
$decoded = XdrLedgerKey::fromBase64Xdr($base64Key);
$type = $decoded->type->getValue(); // XdrLedgerEntryType constant
```
