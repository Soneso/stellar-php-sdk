# SEP-35: Operation IDs

Compute and decode the operation IDs Horizon uses for historical ledger data.

## Overview

> **Note:** SEP-35 is currently in Draft status. The specification may evolve before reaching final status.

SEP-35 defines the ID scheme for Stellar operations in historical ledger data — the same scheme Horizon uses for operation IDs and paging cursors, often called a TOID (total order ID). An ID is a single signed 64-bit integer that packs three fields: the ledger sequence an operation was validated in, the application order of its transaction within that ledger, and the operation's index within that transaction.

Use it when you need to:

- Build an operation ID that matches what Horizon returns, without a round trip to fetch it
- Decode an operation ID or paging cursor back into its ledger, transaction, and operation components
- Advance a paging cursor through operation slots one at a time
- Filter operation IDs down to a specific ledger or range of ledgers

The scheme only orders data that already exists in closed ledgers. It cannot be used to predict the ID an operation will receive before its transaction is applied, and failed transactions still consume an application order number, so application order is not the same as a count of successful transactions.

## Quick example

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

// The second operation (index 2) of the third transaction (order 3) in ledger 100
$toid = new TOID(100, 3, 2);

// This matches the operation ID Horizon would report for that same operation
$operationId = $toid->toInt64();
echo $operationId . "\n"; // 429496741890
```

## Detailed usage

### Constructing a TOID

The constructor takes the three fields directly: ledger sequence, transaction application order, and operation index. On the network, the latter two are assigned starting at 1, but the class also accepts 0 for both, since 0 is needed to express range boundaries.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

$toid = new TOID(12345, 2, 0);

echo $toid->getLedgerSequence() . "\n";   // 12345
echo $toid->getTransactionOrder() . "\n"; // 2
echo $toid->getOperationIndex() . "\n";   // 0
```

### Encoding and decoding

`toInt64()` packs the three fields into the signed 64-bit integer Horizon uses as an operation ID. `TOID::fromInt64()` reverses that, splitting an ID you received from Horizon back into its fields.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

$toid = new TOID(12345, 2, 0);
$operationId = $toid->toInt64();
echo $operationId . "\n"; // 53021371277312

// Decode an operation ID (or paging cursor) received from Horizon
$decoded = TOID::fromInt64($operationId);
echo $decoded->getLedgerSequence() . "\n";   // 12345
echo $decoded->getTransactionOrder() . "\n"; // 2
echo $decoded->getOperationIndex() . "\n";   // 0
```

Since a PHP `int` requires a 64-bit build to represent the full range of encoded IDs, this only works correctly on a 64-bit PHP installation.

### Cursor iteration

`incrementOperationIndex()` mutates a `TOID` in place, advancing it to the next operation slot. This is useful for building a paging cursor that walks operations in order. Most of the time it just increments the operation index:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

$cursor = new TOID(100, 0, 0);

$cursor->incrementOperationIndex();
echo $cursor->getOperationIndex() . "\n"; // 1
```

When the operation index is already at its maximum (4095), incrementing rolls it back to 0 and advances the ledger sequence instead, since the operation index alone cannot address the next slot once a ledger's own range is exhausted:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

$cursor = new TOID(100, 5, 4095);

$cursor->incrementOperationIndex();
echo $cursor->getLedgerSequence() . "\n";   // 101
echo $cursor->getTransactionOrder() . "\n"; // 5 (unchanged)
echo $cursor->getOperationIndex() . "\n";   // 0
```

At the very top of the encodable range — ledger sequence and operation index both at their maximum — there is no next slot left to advance to, so `incrementOperationIndex()` throws instead of silently wrapping:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;
use OverflowException;

$cursor = TOID::afterLedger(2147483647);

try {
    $cursor->incrementOperationIndex();
} catch (OverflowException $e) {
    echo $e->getMessage() . "\n";
    // Cannot increment operation index, the largest encodable ID has already been reached.
}
```

### Bounding a query by ledger

`TOID::afterLedger()` returns the largest encodable ID within a given ledger, for use as an inclusive upper bound: compare candidate IDs against it with `<=`.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

// Every operation ID up to and including ledger 100
$upperBound = TOID::afterLedger(100)->toInt64();

$inLedger = (new TOID(100, 3, 2))->toInt64();
var_dump($inLedger <= $upperBound); // true

$nextLedger = (new TOID(101, 0, 0))->toInt64();
var_dump($nextLedger <= $upperBound); // false
```

`TOID::ledgerRangeInclusive()` covers a span of ledgers at once and returns a `TOIDRange`. Its convention is the opposite of `afterLedger()`: the start is inclusive, but the end is exclusive, so compare with `<`. `TOIDRange` also has a public constructor, so you can build one directly with `new TOIDRange($start, $end)` when the bounds come from encoded IDs computed elsewhere rather than from `ledgerRangeInclusive()`.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;

// Every operation ID for ledgers 100 through 200, inclusive
$range = TOID::ledgerRangeInclusive(100, 200);

$inRange = (new TOID(150, 1, 0))->toInt64();
var_dump($inRange >= $range->getStart() && $inRange < $range->getEnd()); // true

$pastRange = (new TOID(201, 0, 0))->toInt64();
var_dump($pastRange >= $range->getStart() && $pastRange < $range->getEnd()); // false
```

## Error handling

The constructor rejects field values that fall outside their encodable ranges (ledger sequence 0 to 2147483647, transaction order 0 to 1048575, operation index 0 to 4095) with `InvalidArgumentException`. `TOID::fromInt64()` throws the same exception for a negative value, since a negative encoded ID would have its sign bit set, which never happens for a valid ID. `TOID::ledgerRangeInclusive()` additionally rejects a range start greater than its end, a start below 1, or an end of 2147483647 or more.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TOID\TOID;
use InvalidArgumentException;

try {
    new TOID(-1, 0, 0);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage() . "\n";
    // Invalid ledger sequence, it must be between 0 and 2147483647.
}

try {
    TOID::ledgerRangeInclusive(200, 100);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage() . "\n";
    // Invalid range, from must not be greater than to.
}
```

## Reference

- [SEP-35 Specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0035.md)
- [TOID Source Code](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDK/SEP/TOID/TOID.php)

---

[Back to SEP Overview](README.md)
