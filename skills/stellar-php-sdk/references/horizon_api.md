# Horizon API - Fetching Data

Complete reference for querying the Stellar Horizon REST API using the PHP SDK's request builders.

For method signatures on response objects, see [API Reference](./api_reference.md).

## Server Initialization

```php
use Soneso\StellarSDK\StellarSDK;

// Singleton instances (recommended)
$sdk = StellarSDK::getTestNetInstance();
$sdk = StellarSDK::getPublicNetInstance();

// Custom Horizon URL
$sdk = new StellarSDK('https://my-horizon.example.com');
```

All query methods are accessed via the `StellarSDK` instance, which returns typed request builders for method chaining.

---

## Account Endpoints

### Get Account Details

```php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

// Convenience method (returns AccountResponse directly)
$account = $sdk->requestAccount($accountId);
$sequenceNumber = $account->getSequenceNumber();
$balances = $account->getBalances();

foreach ($balances as $balance) {
    if ($balance->getAssetType() === Asset::TYPE_NATIVE) {
        echo 'XLM: ' . $balance->getBalance() . PHP_EOL;
    } else {
        echo $balance->getAssetCode() . ' (' . $balance->getAssetIssuer() . '): ' . $balance->getBalance() . PHP_EOL;
    }
}

// Check if account exists
$exists = $sdk->accountExists($accountId);
```

### Query Accounts by Filter

Only one filter can be active at a time: signer, asset, sponsor, or liquidity pool.

```php
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;

// By signer
$accounts = $sdk->accounts()
    ->forSigner($signerAccountId)
    ->limit(10)
    ->order('desc')
    ->execute();

// By asset (requires AssetTypeCreditAlphanum, not base Asset)
$asset = new AssetTypeCreditAlphanum4('USD', $issuerAccountId);
$accounts = $sdk->accounts()
    ->forAsset($asset)
    ->limit(20)
    ->execute();

// By sponsor
$accounts = $sdk->accounts()
    ->forSponsor($sponsorAccountId)
    ->execute();

// By liquidity pool
$accounts = $sdk->accounts()
    ->forLiquidityPool($liquidityPoolId)
    ->execute();
```

### Get Account Data

```php
$dataValue = $sdk->accounts()->accountData($accountId, 'config_key');
```

### Get Account Sub-Resources

Account-specific transactions, operations, payments, effects, offers, and trades are accessed through their respective request builders with `forAccount()`.

**IMPORTANT:** `execute()` returns a Page Response object, NOT an array. Use the getter method to extract the iterable collection. The collection implements `IteratorIterator` (works with `foreach`) but does NOT implement PHP's `Countable` interface. **NEVER use PHP's `count()` function on these collections** -- it will throw a `TypeError`. Instead, use the collection's own `->count()` method:

```php
// WRONG — throws TypeError: count() expects Countable|array
$total = count($page->getTransactions());

// CORRECT — use the collection's count() method
$total = $page->getTransactions()->count();
// or convert to array first
$total = count($page->getTransactions()->toArray());
```

This applies to ALL iterable response collections: `TransactionsResponse`, `OperationsResponse`, `OffersResponse`, `TradesResponse`, `EffectsResponse`, `LedgersResponse`, `ClaimableBalancesResponse`, and `AccountSignersResponse`.

```php
// Account transactions — execute() returns TransactionsPageResponse
$txPage = $sdk->transactions()
    ->forAccount($accountId)
    ->limit(25)
    ->order('desc')
    ->execute();
foreach ($txPage->getTransactions() as $tx) {
    echo $tx->getHash() . PHP_EOL;
}

// Account operations — execute() returns OperationsPageResponse
// Each operation is a typed subclass of OperationResponse (e.g. PaymentOperationResponse)
$opsPage = $sdk->operations()
    ->forAccount($accountId)
    ->limit(50)
    ->execute();
foreach ($opsPage->getOperations() as $op) {
    // WRONG: $op->getId() — OperationResponse does NOT have getId()
    // CORRECT: $op->getOperationId() — returns the operation ID string
    echo $op->getOperationId() . ': ' . $op->getHumanReadableOperationType() . PHP_EOL;
    if ($op instanceof PaymentOperationResponse) {
        echo '  ' . $op->getFrom() . ' -> ' . $op->getTo() . ': ' . $op->getAmount() . PHP_EOL;
    } elseif ($op instanceof CreateAccountOperationResponse) {
        echo '  Created: ' . $op->getAccount() . ' with ' . $op->getStartingBalance() . ' XLM' . PHP_EOL;
    } elseif ($op instanceof ChangeTrustOperationResponse) {
        echo '  Trustline: ' . $op->getAssetCode() . ' issuer ' . $op->getAssetIssuer() . PHP_EOL;
    }
}

// Account payments — execute() returns OperationsPageResponse
// Payment types: PaymentOperationResponse, PathPaymentStrictReceiveOperationResponse,
//   PathPaymentStrictSendOperationResponse, CreateAccountOperationResponse, AccountMergeOperationResponse
$pmtPage = $sdk->payments()
    ->forAccount($accountId)
    ->order('desc')
    ->execute();
foreach ($pmtPage->getOperations() as $payment) {
    if ($payment instanceof PaymentOperationResponse) {
        echo $payment->getFrom() . ' -> ' . $payment->getTo() . ': ' . $payment->getAmount() . PHP_EOL;
    } elseif ($payment instanceof PathPaymentStrictReceiveOperationResponse) {
        echo $payment->getFrom() . ' -> ' . $payment->getTo() . ': ' . $payment->getAmount() . ' (source max: ' . $payment->getSourceMax() . ')' . PHP_EOL;
    }
}

// Account effects — execute() returns EffectsPageResponse
// Each effect is a typed subclass of EffectResponse (e.g. AccountCreditedEffectResponse)
$fxPage = $sdk->effects()
    ->forAccount($accountId)
    ->execute();
foreach ($fxPage->getEffects() as $effect) {
    // WRONG: $effect->getType() -- EffectResponse does NOT have getType()
    // CORRECT: $effect->getHumanReadableEffectType() -- e.g. "account_created", "account_credited"
    echo $effect->getHumanReadableEffectType() . PHP_EOL;
    if ($effect instanceof AccountCreditedEffectResponse) {
        echo '  Credited: ' . $effect->getAmount() . ' (' . $effect->getAsset()->getType() . ')' . PHP_EOL;
    } elseif ($effect instanceof AccountDebitedEffectResponse) {
        echo '  Debited: ' . $effect->getAmount() . ' (' . $effect->getAsset()->getType() . ')' . PHP_EOL;
    }
}

// Account offers — execute() returns OffersPageResponse
$offPage = $sdk->offers()
    ->forAccount($accountId)
    ->execute();
foreach ($offPage->getOffers() as $offer) {
    echo $offer->getId() . PHP_EOL;
}

// Account trades — execute() returns TradesPageResponse
$trdPage = $sdk->trades()
    ->forAccount($accountId)
    ->execute();
foreach ($trdPage->getTrades() as $trade) {
    echo $trade->getId() . PHP_EOL;
}
```

**Response object getter reference:**

| Builder | `execute()` returns | Getter method | Returns |
|---------|-------------------|---------------|---------|
| `transactions()` | `TransactionsPageResponse` | `getTransactions()` | `TransactionsResponse` (iterable) |
| `operations()` | `OperationsPageResponse` | `getOperations()` | `OperationsResponse` (iterable) |
| `payments()` | `OperationsPageResponse` | `getOperations()` | `OperationsResponse` (iterable) |
| `effects()` | `EffectsPageResponse` | `getEffects()` | `EffectsResponse` (iterable) |
| `offers()` | `OffersPageResponse` | `getOffers()` | `OffersResponse` (iterable) |
| `trades()` | `TradesPageResponse` | `getTrades()` | `TradesResponse` (iterable) |
| `ledgers()` | `LedgersPageResponse` | `getLedgers()` | `LedgersResponse` (iterable) |
| `claimableBalances()` | `ClaimableBalancesPageResponse` | `getClaimableBalances()` | `ClaimableBalancesResponse` (iterable) |

**AccountResponse sub-collections (accessed directly, not via page response):**

| Method on AccountResponse | Returns | Has `->count()` | Iterate with `foreach` |
|---------------------------|---------|------------------|----------------------|
| `getSigners()` | `AccountSignersResponse` | Yes | Yes |
| `getBalances()` | `AccountBalancesResponse` | Yes | Yes |

**AccountResponse sponsoring methods:**

```php
$account = $sdk->requestAccount($accountId);
$account->getNumSponsoring();  // int — number of entries this account sponsors for others
$account->getNumSponsored();   // int — number of entries sponsored by other accounts for this account
```

---

## Transaction Endpoints

### Get Transaction Details

```php
// Convenience method
$transaction = $sdk->requestTransaction($transactionHash);
$hash = $transaction->getHash();
$ledger = $transaction->getLedger();
$successful = $transaction->isSuccessful();
$envelopeXdr = $transaction->getEnvelopeXdr();
```

### Inspecting Transaction Memos

Transaction responses include a `getMemo()` method that returns a `Memo` object. Check the type before accessing the value:

```php
use Soneso\StellarSDK\Memo;

$transaction = $sdk->requestTransaction($transactionHash);
$memo = $transaction->getMemo();

// Check if memo exists
if ($memo->getType() !== Memo::MEMO_TYPE_NONE) {
    echo 'Memo type: ' . $memo->typeAsString() . PHP_EOL;
    
    // Manual inspection by type
    switch ($memo->getType()) {
        case Memo::MEMO_TYPE_TEXT:
            echo '  Value (text): ' . $memo->getValue() . PHP_EOL;
            break;
        case Memo::MEMO_TYPE_ID:
            echo '  Value (id): ' . $memo->getValue() . PHP_EOL;
            break;
        case Memo::MEMO_TYPE_HASH:
            echo '  Value (hash): ' . base64_encode($memo->getValue()) . PHP_EOL;
            break;
        case Memo::MEMO_TYPE_RETURN:
            echo '  Value (return): ' . base64_encode($memo->getValue()) . PHP_EOL;
            break;
    }
    
    // Or use the convenience method (handles base64 encoding automatically)
    echo 'Memo: ' . $memo->typeAsString() . ' = ' . $memo->valueAsString() . PHP_EOL;
}
```

The `valueAsString()` method automatically converts:
- TEXT memos → raw string value
- ID memos → string representation of the integer
- HASH/RETURN memos → base64-encoded string
- NONE memos → `null`

### Query Transactions

All query builders return Page Response objects. Use `getTransactions()` to extract the iterable collection:

```php
// All transactions with pagination — returns TransactionsPageResponse
$txPage = $sdk->transactions()
    ->limit(50)
    ->order('desc')
    ->execute();
foreach ($txPage->getTransactions() as $tx) {
    echo $tx->getHash() . PHP_EOL;
}

// By account
$txPage = $sdk->transactions()
    ->forAccount($accountId)
    ->includeFailed(true) // include failed transactions
    ->execute();
foreach ($txPage->getTransactions() as $tx) { /* ... */ }

// By ledger
$txPage = $sdk->transactions()
    ->forLedger($ledgerSequence)
    ->execute();

// By claimable balance
$txPage = $sdk->transactions()
    ->forClaimableBalance($balanceId)
    ->execute();

// By liquidity pool
$txPage = $sdk->transactions()
    ->forLiquidityPool($liquidityPoolId)
    ->execute();
```

---

## Ledger Endpoints

```php
// Single ledger by sequence number
$ledger = $sdk->requestLedger($ledgerSequence);
$closedAt = $ledger->getClosedAt();
$txCount = $ledger->getSuccessfulTransactionCount();

// All ledgers — returns LedgersPageResponse
$ledgersPage = $sdk->ledgers()
    ->limit(10)
    ->order('desc')
    ->execute();
foreach ($ledgersPage->getLedgers() as $l) {
    echo $l->getSequence() . ': ' . $l->getClosedAt() . PHP_EOL;
}
```

---

## Operation Endpoints

```php
// Single operation by ID
$operation = $sdk->requestOperation($operationId);

// Query operations — returns OperationsPageResponse (see Account Sub-Resources for iteration and instanceof pattern)
$sdk->operations()->forAccount($accountId)->execute();
$sdk->operations()->forLedger($ledgerSequence)->execute();
$sdk->operations()->forTransaction($transactionHash)->execute();
$sdk->operations()->forLiquidityPool($liquidityPoolId)->execute();
$sdk->operations()->limit(100)->order('desc')->includeFailed(true)->execute();
```

---

## Payment Endpoints

```php
// Query payments — returns OperationsPageResponse (see Account Sub-Resources for iteration and instanceof pattern)
$sdk->payments()->forAccount($accountId)->limit(50)->order('desc')->includeFailed(false)->execute();
$sdk->payments()->forLedger($ledgerSequence)->execute();
$sdk->payments()->forTransaction($transactionHash)->execute();
```

---

## Asset Endpoints

```php
// Query assets by code
$assets = $sdk->assets()
    ->forAssetCode('USD')
    ->execute();

// Query assets by issuer
$assets = $sdk->assets()
    ->forAssetIssuer($issuerAccountId)
    ->execute();

// Both code and issuer
$assets = $sdk->assets()
    ->forAssetCode('USD')
    ->forAssetIssuer($issuerAccountId)
    ->execute();
```

---

## Effect Endpoints

```php
// Query effects — returns EffectsPageResponse (see Account Sub-Resources for iteration and instanceof pattern)
$sdk->effects()->forAccount($accountId)->limit(100)->execute();
$sdk->effects()->forLedger($ledgerSequence)->execute();
$sdk->effects()->forTransaction($transactionHash)->execute();
$sdk->effects()->forOperation($operationId)->execute();
$sdk->effects()->forLiquidityPool($liquidityPoolId)->execute();
```

---

## Offer Endpoints

`execute()` returns `OffersPageResponse`. Use `getOffers()` to extract the iterable `OffersResponse` collection.

```php
use Soneso\StellarSDK\Asset;

// Single offer by ID
$offer = $sdk->requestOffer($offerId);
echo 'Offer ID: ' . $offer->getOfferId() . PHP_EOL;
echo 'Amount: ' . $offer->getAmount() . PHP_EOL;
echo 'Price: ' . $offer->getPrice() . PHP_EOL;

// Offers by account — returns OffersPageResponse
$offPage = $sdk->offers()
    ->forAccount($accountId)
    ->execute();
foreach ($offPage->getOffers() as $offer) {
    echo 'Offer ' . $offer->getOfferId() . ': ' . $offer->getAmount() . ' @ ' . $offer->getPrice() . PHP_EOL;
}
// Count: use ->count() method, NOT count()
$numOffers = $offPage->getOffers()->count();

// Offers by seller
$offPage = $sdk->offers()
    ->forSeller($sellerAccountId)
    ->execute();

// Offers by selling/buying asset
$sellingAsset = Asset::native();
$buyingAsset = Asset::createNonNativeAsset('USD', $issuerAccountId);

$offPage = $sdk->offers()
    ->forSellingAsset($sellingAsset)
    ->forBuyingAsset($buyingAsset)
    ->execute();

// Offers by sponsor
$offPage = $sdk->offers()
    ->forSponsor($sponsorAccountId)
    ->execute();
```

---

## Trade Endpoints

```php
// Trades for an account
$trades = $sdk->trades()
    ->forAccount($accountId)
    ->limit(50)
    ->execute();

// Trades for an offer
$trades = $sdk->trades()
    ->forOffer($offerId)
    ->execute();

// Trades for a liquidity pool
$trades = $sdk->trades()
    ->forLiquidityPool($liquidityPoolId)
    ->execute();
```

### Trade Aggregations

```php
use Soneso\StellarSDK\Asset;

$aggregations = $sdk->tradeAggregations()
    ->forBaseAsset(Asset::native())
    ->forCounterAsset(Asset::createNonNativeAsset('USD', $issuerAccountId))
    ->forResolution('3600000')  // 1 hour in milliseconds
    ->order('desc')
    ->limit(24)
    ->execute();
```

---

## Order Book

`execute()` returns an `OrderBookResponse` with `getAsks()` and `getBids()`, each returning an `OrderBookRowsResponse` (iterable). Each row has `getPrice()`, `getAmount()`, and `getPriceR()`.

```php
use Soneso\StellarSDK\Asset;

$sellingAsset = Asset::native();
$buyingAsset = Asset::createNonNativeAsset('USD', $issuerAccountId);

$orderBook = $sdk->orderBook()
    ->forSellingAsset($sellingAsset)
    ->forBuyingAsset($buyingAsset)
    ->execute();

// Sell offers (asks) — sorted by ascending price
foreach ($orderBook->getAsks() as $ask) {
    echo 'Ask: ' . $ask->getAmount() . ' @ ' . $ask->getPrice() . PHP_EOL;
}

// Buy offers (bids) — sorted by descending price
foreach ($orderBook->getBids() as $bid) {
    echo 'Bid: ' . $bid->getAmount() . ' @ ' . $bid->getPrice() . PHP_EOL;
}

// Check if asks exist (use ->count() method, not count())
$asksCount = $orderBook->getAsks()->count();
echo 'Number of ask price levels: ' . $asksCount . PHP_EOL;
```

---

## Claimable Balance Endpoints

`execute()` returns `ClaimableBalancesPageResponse`. Use `getClaimableBalances()` to extract the iterable collection.

```php
// Single balance by ID
$balance = $sdk->requestClaimableBalance($balanceId);
echo 'Amount: ' . $balance->getAmount() . PHP_EOL;
echo 'Asset: ' . $balance->getAsset() . PHP_EOL;
echo 'Balance ID: ' . $balance->getBalanceId() . PHP_EOL;

// By claimant — returns ClaimableBalancesPageResponse
$cbPage = $sdk->claimableBalances()
    ->forClaimant($claimantAccountId)
    ->execute();
foreach ($cbPage->getClaimableBalances() as $cb) {
    echo 'Balance ID: ' . $cb->getBalanceId() . PHP_EOL;
    echo 'Amount: ' . $cb->getAmount() . PHP_EOL;
}
// Count: use ->count() method, NOT count()
$numBalances = $cbPage->getClaimableBalances()->count();

// By sponsor
$cbPage = $sdk->claimableBalances()
    ->forSponsor($sponsorAccountId)
    ->execute();

// By asset
$asset = Asset::createNonNativeAsset('USD', $issuerAccountId);
$cbPage = $sdk->claimableBalances()
    ->forAsset($asset)
    ->execute();
```

---

## Liquidity Pool Endpoints

```php
use Soneso\StellarSDK\Asset;

// Single pool by ID
$pool = $sdk->requestLiquidityPool($poolId);

// Pools for an account
$pools = $sdk->liquidityPools()
    ->forAccount($accountId)
    ->execute();

// Pools by reserve assets (pass canonical asset strings)
$pools = $sdk->liquidityPools()
    ->forReserves('native', 'USD:' . $issuerAccountId)
    ->execute();
```

---

## Path Finding Endpoints

### Find Payment Paths

```php
use Soneso\StellarSDK\Asset;

// Strict receive paths (find paths for exact destination amount)
$paths = $sdk->findStrictReceivePaths()
    ->forDestinationAsset(Asset::createNonNativeAsset('EUR', $issuerAccountId))
    ->forDestinationAmount('100.0')
    ->forSourceAssets([Asset::native()])
    ->execute();

// Strict send paths (find paths for exact source amount)
$paths = $sdk->findStrictSendPaths()
    ->forSourceAsset(Asset::native())
    ->forSourceAmount('100.0')
    ->forDestinationAssets([Asset::createNonNativeAsset('EUR', $issuerAccountId)])
    ->execute();
```

---

## Fee Stats and Network Info

```php
// Fee statistics — returns FeeStatsResponse
$feeStats = $sdk->requestFeeStats();
echo 'Last ledger: ' . $feeStats->getLastLedger() . PHP_EOL;
echo 'Base fee: ' . $feeStats->getLastLedgerBaseFee() . PHP_EOL;
echo 'Capacity: ' . $feeStats->getLedgerCapacityUsage() . PHP_EOL;

// Fee charged and max fee are nested objects
$feeCharged = $feeStats->getFeeCharged();  // FeeChargedResponse
echo 'Fee charged (min): ' . $feeCharged->getMin() . PHP_EOL;
echo 'Fee charged (mode): ' . $feeCharged->getMode() . PHP_EOL;
echo 'Fee charged (p99): ' . $feeCharged->getP99() . PHP_EOL;

$maxFee = $feeStats->getMaxFee();  // MaxFeeResponse
echo 'Max fee (min): ' . $maxFee->getMin() . PHP_EOL;
echo 'Max fee (mode): ' . $maxFee->getMode() . PHP_EOL;
echo 'Max fee (p99): ' . $maxFee->getP99() . PHP_EOL;

// Root endpoint (network info)
$root = $sdk->root();

// Health check
$health = $sdk->health();
```

---

## Pagination Patterns

All list endpoints support cursor-based pagination via `cursor()`, `limit()`, and `order()`.

### Basic Pagination

```php
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

// First page
$page = $sdk->transactions()
    ->forAccount($accountId)
    ->limit(25)
    ->order('desc')
    ->execute();

$records = $page->getTransactions();

// Process records
foreach ($records as $tx) {
    $hash = $tx->getHash();
    $pagingToken = $tx->getPagingToken();
}

// Next page — all page responses have getNextPage() / getPreviousPage()
$nextPage = $page->getNextPage();   // returns null if no more pages
$prevPage = $page->getPreviousPage();

// Alternative: manual cursor-based pagination
if ($records->count() > 0) {
    $arr = $records->toArray();
    $lastToken = end($arr)->getPagingToken();
    $nextPage = $sdk->transactions()
        ->forAccount($accountId)
        ->cursor($lastToken)
        ->limit(25)
        ->order('desc')
        ->execute();
}
```

### Pagination Parameters

- `cursor(string)` -- paging token from a previous response record
- `limit(int)` -- number of records per page (max 200, default varies)
- `order(string)` -- `"asc"` (oldest first, default) or `"desc"` (newest first)

---

## Error Handling

```php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

$sdk = StellarSDK::getTestNetInstance();

try {
    $account = $sdk->requestAccount($accountId);
} catch (HorizonRequestException $e) {
    $statusCode = $e->getStatusCode();

    if ($statusCode === 404) {
        // Account not found
    } elseif ($statusCode === 429) {
        // Rate limited -- check retry-after header
        $retryAfter = $e->getRetryAfter();
    } else {
        // Other Horizon error
        $horizonError = $e->getHorizonErrorResponse();
        if ($horizonError !== null) {
            $errorTitle = $horizonError->getTitle();
            $errorDetail = $horizonError->getDetail();
        }
    }
}
```

### Rate Limiting

Horizon enforces rate limits per IP. Monitor response headers:

- `X-Ratelimit-Limit` -- requests allowed per window
- `X-Ratelimit-Remaining` -- requests remaining
- `X-Ratelimit-Reset` -- seconds until reset

When receiving HTTP 429, use exponential backoff before retrying.

### Transaction Submission

```php
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

$sdk = StellarSDK::getTestNetInstance();

try {
    // Synchronous submission (waits for ledger inclusion)
    $response = $sdk->submitTransaction($transaction);

    if ($response->isSuccessful()) {
        $hash = $response->getHash();
        $ledger = $response->getLedger();
    } else {
        $extras = $response->getExtras();
        if ($extras !== null) {
            $resultCodes = $extras->getResultCodes();
            $txCode = $resultCodes->getTransactionResultCode();
            $opCodes = $resultCodes->getOperationsResultCodes();
        }
    }
} catch (HorizonRequestException $e) {
    // Network or server error during submission
}

// Async submission (returns immediately, does not wait for ingestion)
$asyncResponse = $sdk->submitAsyncTransaction($transaction);
$txHash = $asyncResponse->hash;        // public property
$status = $asyncResponse->txStatus;    // PENDING, DUPLICATE, TRY_AGAIN_LATER, ERROR

// Poll for result after async submission
if ($status === SubmitAsyncTransactionResponse::TX_STATUS_PENDING) {
    sleep(5); // wait for ledger close
    try {
        $txResponse = $sdk->requestTransaction($txHash);
        echo 'Confirmed in ledger: ' . $txResponse->getLedger() . PHP_EOL;
    } catch (HorizonRequestException $e) {
        if ($e->getStatusCode() === 404) {
            // Not yet ingested — retry later
        }
    }
}
```

### Fee Bump Transaction Response

When submitting a fee bump transaction, the `SubmitTransactionResponse` includes both the outer and inner transaction hashes:

```php
$response = $sdk->submitTransaction($feeBumpTx);

if ($response->isSuccessful()) {
    // Outer fee bump transaction hash
    $feeBumpHash = $response->getHash();
    echo 'Fee bump hash: ' . $feeBumpHash . PHP_EOL;

    // Inner (original) transaction hash and details
    $innerTx = $response->getInnerTransactionResponse();
    if ($innerTx !== null) {
        echo 'Inner TX hash: ' . $innerTx->getHash() . PHP_EOL;
        echo 'Inner TX max fee: ' . $innerTx->getMaxFee() . PHP_EOL;
    }

    // Fee bump wrapper details
    $feeBumpDetails = $response->getFeeBumpTransactionResponse();
    if ($feeBumpDetails !== null) {
        echo 'Fee bump outer hash: ' . $feeBumpDetails->getHash() . PHP_EOL;
    }
}
```

**Note:** `SubmitTransactionResponse` does NOT have a `getInnerTransactionHash()` method. Use `$response->getInnerTransactionResponse()->getHash()` instead.
