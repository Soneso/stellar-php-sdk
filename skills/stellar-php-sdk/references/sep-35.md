# SEP-35: Operation IDs

**Purpose:** Compute and decode the operation IDs (TOIDs) Horizon uses for historical ledger data — operations, transactions, and paging cursors.
**Prerequisites:** None
**SDK Namespace:** `Soneso\StellarSDK\SEP\TOID`
**Note:** Consult the [SEP-35 specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0035.md) for the protocol's current status.

## Table of Contents

- [How It Works](#how-it-works)
- [Quick Example](#quick-example)
- [Method Signatures](#method-signatures)
- [Cursor Iteration](#cursor-iteration)
- [Bounding a Query by Ledger](#bounding-a-query-by-ledger)
- [Test Vectors](#test-vectors)
- [Error Handling](#error-handling)
- [Common Pitfalls](#common-pitfalls)

## How It Works

SEP-35 defines the ID scheme Horizon uses for Stellar operations in historical ledger data, also known as a TOID (total order ID). An ID is a single signed 64-bit integer that packs three big-endian bit fields:

```
id = (ledgerSequence << 32) | (transactionOrder << 12) | operationIndex
```

- `ledgerSequence` — 32 bits, the ledger the operation was validated in
- `transactionOrder` — 20 bits, the application order of the transaction within the ledger
- `operationIndex` — 12 bits, the index of the operation within the transaction

The sign bit of the encoded value is never set for valid field values, so IDs are usable directly as SQL bigint primary keys and paging cursors.

`TOID` is represented with PHP's native `int` type, which requires a 64-bit PHP build. On the network, transaction order and operation index are assigned starting at 1, but the class also accepts 0 for both, since 0 is a valid encoded value and is needed to express range boundaries such as `new TOID($ledgerSequence, 0, 0)`.

## Quick Example

The constructor takes the three fields directly. `toInt64()` packs them into the signed 64-bit integer Horizon uses as an operation ID or paging cursor, and `TOID::fromInt64()` reverses that.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

// ledger 100, transaction order 3, operation index 2
$toid = new TOID(100, 3, 2);

echo $toid->getLedgerSequence() . "\n";   // 100
echo $toid->getTransactionOrder() . "\n"; // 3
echo $toid->getOperationIndex() . "\n";   // 2

$operationId = $toid->toInt64();
echo $operationId . "\n"; // 429496741890

$decoded = TOID::fromInt64($operationId);
echo $decoded->getLedgerSequence() . "\n"; // 100
```

## Method Signatures

`TOID` is in `Soneso\StellarSDK\SEP\TOID\TOID`; `TOIDRange` is in `Soneso\StellarSDK\SEP\TOID\TOIDRange`.

```text
class TOID
{
    // Field bounds
    public const MAX_LEDGER_SEQUENCE = 2147483647;
    public const MAX_TRANSACTION_ORDER = 1048575;
    public const MAX_OPERATION_INDEX = 4095;

    // Throws InvalidArgumentException if any field is outside its valid range:
    // ledgerSequence 0..2147483647, transactionOrder 0..1048575, operationIndex 0..4095
    public function __construct(int $ledgerSequence, int $transactionOrder, int $operationIndex)

    public function getLedgerSequence(): int
    public function getTransactionOrder(): int
    public function getOperationIndex(): int

    // Encodes the three fields into a signed 64-bit integer
    public function toInt64(): int

    // Decodes a signed 64-bit integer into a TOID. Throws InvalidArgumentException if $value < 0.
    public static function fromInt64(int $value): TOID

    // Mutates this TOID in place, advancing to the next operation slot.
    // Throws OverflowException only when both ledgerSequence and operationIndex
    // are already at their maximum (2147483647 and 4095).
    public function incrementOperationIndex(): void

    // Returns the TOID ($ledgerSequence, 1048575, 4095) — the largest encodable ID
    // within the given ledger. Use with <= as an inclusive upper bound.
    // Throws InvalidArgumentException if $ledgerSequence is outside 0..2147483647.
    public static function afterLedger(int $ledgerSequence): TOID

    // Returns a TOIDRange covering ledgers $from through $to, inclusive. When $from
    // is 1 the range start is pulled down to 0 rather than (1, 0, 0).
    // Throws InvalidArgumentException if $from > $to, $from < 1, or $to >= 2147483647.
    public static function ledgerRangeInclusive(int $from, int $to): TOIDRange
}

class TOIDRange
{
    public function __construct(int $start, int $end)

    public function getStart(): int // inclusive
    public function getEnd(): int   // exclusive
}
```

## Cursor Iteration

`incrementOperationIndex()` mutates a `TOID` in place, advancing it to the next operation slot — useful for building a paging cursor that walks operations in order. Most of the time it just increments the operation index:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

$cursor = new TOID(100, 0, 0);

$cursor->incrementOperationIndex();
echo $cursor->getOperationIndex() . "\n"; // 1
```

When the operation index is already at its maximum (4095), incrementing rolls it back to 0 and advances the ledger sequence instead; the transaction order is left unchanged:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

$cursor = new TOID(100, 5, 4095);

$cursor->incrementOperationIndex();
echo $cursor->getLedgerSequence() . "\n";   // 101
echo $cursor->getTransactionOrder() . "\n"; // 5 (unchanged)
echo $cursor->getOperationIndex() . "\n";   // 0
```

At the very top of the encodable range, `incrementOperationIndex()` throws `OverflowException`:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

$cursor = TOID::afterLedger(2147483647); // (2147483647, 1048575, 4095)

try {
    $cursor->incrementOperationIndex();
} catch (\OverflowException $e) {
    echo $e->getMessage() . "\n";
    // Cannot increment operation index, the largest encodable ID has already been reached.
}
```

## Bounding a Query by Ledger

`TOID::afterLedger()` returns the largest encodable ID within a given ledger, for use as an inclusive upper bound — compare candidate IDs against it with `<=`. It throws `InvalidArgumentException` for a ledger sequence outside 0 to 2147483647.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

$upperBound = TOID::afterLedger(100)->toInt64();
echo $upperBound . "\n"; // 433791696895

$inLedger = (new TOID(100, 3, 2))->toInt64();
var_dump($inLedger <= $upperBound); // bool(true)

$nextLedger = (new TOID(101, 0, 0))->toInt64();
var_dump($nextLedger <= $upperBound); // bool(false)
```

`TOID::ledgerRangeInclusive()` covers a span of ledgers at once and returns a `TOIDRange`. Its convention is the opposite of `afterLedger()`: the start is inclusive, the end is exclusive, so compare with `<`. One case does not follow the general formula: a range starting at ledger 1 gets a start of 0 rather than the encoding of `(1, 0, 0)`, so that IDs encoded with ledger field 0 fall inside the lowest range.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

$range = TOID::ledgerRangeInclusive(1, 2);
echo $range->getStart() . "\n"; // 0 — pulled down from (1, 0, 0), which encodes to 4294967296
echo $range->getEnd() . "\n";   // 12884901888

$inRange = (new TOID(2, 1, 0))->toInt64();
var_dump($inRange >= $range->getStart() && $inRange < $range->getEnd()); // bool(true)

$pastRange = (new TOID(3, 0, 0))->toInt64();
var_dump($pastRange >= $range->getStart() && $pastRange < $range->getEnd()); // bool(false)
```

## Test Vectors

These value pairs are verified against the class's constructor, `toInt64()`, `afterLedger()`, and `ledgerRangeInclusive()`. Use them to confirm an integration is calling the right method with the right arguments.

**Encode `(ledgerSequence, transactionOrder, operationIndex)` → `toInt64()`:**

| ledgerSequence | transactionOrder | operationIndex | toInt64() |
|---|---|---|---|
| 0 | 0 | 0 | 0 |
| 0 | 0 | 4095 | 4095 |
| 1 | 1 | 1 | 4294971393 |
| 1234567 | 89 | 2000 | 5302424890087376 |
| 2147483647 | 1048575 | 4095 | 9223372036854775807 (`PHP_INT_MAX`) |

**`afterLedger()`:**

| ledgerSequence | afterLedger()->toInt64() |
|---|---|
| 0 | 4294967295 |
| 1 | 8589934591 |
| 100 | 433791696895 |

**`ledgerRangeInclusive()`:**

| from | to | start | end |
|---|---|---|---|
| 1 | 1 | 0 | 8589934592 |
| 1 | 2 | 0 | 12884901888 |
| 2 | 3 | 8589934592 | 17179869184 |

The two `from = 1` rows show the special case: the start is 0, not the 4294967296 that `(1, 0, 0)` encodes to.

## Error Handling

The constructor throws `InvalidArgumentException` for a field outside its encodable range:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

try {
    new TOID(-1, 0, 0);
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage() . "\n";
    // Invalid ledger sequence, it must be between 0 and 2147483647.
}

try {
    new TOID(0, 0, 4096);
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage() . "\n";
    // Invalid operation index, it must be between 0 and 4095.
}
```

`TOID::fromInt64()` throws the same exception for a negative value:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

try {
    TOID::fromInt64(-1);
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage() . "\n";
    // Invalid encoded ID, it must not be negative.
}
```

`TOID::ledgerRangeInclusive()` additionally rejects a range start greater than its end, a start below 1, or an end of 2147483647 or more:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

try {
    TOID::ledgerRangeInclusive(200, 100);
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage() . "\n";
    // Invalid range, from must not be greater than to.
}
```

`incrementOperationIndex()` throws `OverflowException` (not `InvalidArgumentException`) at the true ceiling — see [Cursor Iteration](#cursor-iteration) above.

## Common Pitfalls

**WRONG/CORRECT — operation index is not zero-based on the network:**

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

$ledgerSeq = 100;
$txOrder = 3;

// WRONG: assuming the first operation of a transaction has index 0
$firstOpId = (new TOID($ledgerSeq, $txOrder, 0))->toInt64();

// CORRECT: the network assigns transaction order and operation index starting at 1;
// 0 is only meaningful as a range boundary, not as "the first real value"
$firstOpId = (new TOID($ledgerSeq, $txOrder, 1))->toInt64();
```

**WRONG/CORRECT — comparing against `afterLedger()` and `ledgerRangeInclusive()` with the same operator:**

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

$upperBound = TOID::afterLedger(100)->toInt64();
$range = TOID::ledgerRangeInclusive(1, 100);
$inLedger = (new TOID(100, 3, 2))->toInt64();

// WRONG: afterLedger() is an inclusive bound, ledgerRangeInclusive()->getEnd() is exclusive
$hit = $inLedger <= $range->getEnd();  // off by one — includes IDs from ledger 101
$hit = $inLedger < $upperBound;        // off by one — excludes the last ID of ledger 100

// CORRECT: afterLedger() compares with <=, ledgerRangeInclusive()->getEnd() compares with <
$hit = $inLedger <= $upperBound;
$hit = $inLedger >= $range->getStart() && $inLedger < $range->getEnd();
```

**WRONG/CORRECT — `ledgerRangeInclusive()` requires `$from >= 1`:**

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

// WRONG: 0 is not a valid ledger sequence to start a range from
try {
    TOID::ledgerRangeInclusive(0, 100);
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage() . "\n";
    // Invalid range start, it must be at least 1.
}

// CORRECT: the network's first ledger is 1; use afterLedger() or the TOID
// constructor directly if you need an encoded ID with ledger field 0
TOID::ledgerRangeInclusive(1, 100);
```

## Related SEPs

- SEP-35 has no dependencies on other SEPs.
