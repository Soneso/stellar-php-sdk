# Horizon Streaming (SSE)

The PHP SDK supports real-time streaming from Horizon via Server-Sent Events (SSE). Streaming uses Guzzle with `'stream' => true` and `Accept: text/event-stream` headers. The base `RequestBuilder` class provides the `getAndStream()` method, which runs an infinite blocking loop reading SSE data and dispatching parsed JSON to a callback.

## How Streaming Works

All streaming flows through `RequestBuilder::getAndStream()`:

- Opens a persistent HTTP connection with `'read_timeout' => null`
- Reads line-by-line from the response body
- Ignores `data: "hello"` (handshake) and `data: "byebye"` (disconnect) messages
- Parses `data: {...}` lines as JSON and passes the decoded array to the callback
- On `ServerException`, retries after a 10-second delay (when `$retryOnServerException = true`)
- Runs in `while(true)` -- the stream blocks the process indefinitely

## Streaming Endpoints

Seven request builders expose a `stream()` method. Each parses raw JSON into a typed response object.

| Builder | Method | Callback Type |
|---------|--------|---------------|
| `TransactionsRequestBuilder` | `stream(callable $callback)` | `TransactionResponse` |
| `PaymentsRequestBuilder` | `stream(callable $callback)` | `OperationResponse` subclass |
| `OperationsRequestBuilder` | `stream(callable $callback)` | `OperationResponse` |
| `EffectsRequestBuilder` | `stream(callable $callback)` | `EffectResponse` |
| `LedgersRequestBuilder` | `stream(callable $callback)` | `LedgerResponse` |
| `TradesRequestBuilder` | `stream(callable $callback)` | `TradeResponse` |
| `OrderBookRequestBuilder` | `stream(callable $callback)` | `OrderBookResponse` |
| `OffersRequestBuilder` | `stream(callable $callback)` | `OfferResponse` |

The `AccountsRequestBuilder` uses separate methods:
- `streamAccount(string $accountId, callable $callback)` -- streams `AccountResponse`
- `streamAccountData(string $accountId, string $key, callable $callback)` -- streams `AccountDataValueResponse`

## Stream New Transactions

```php
<?php

declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;

$sdk = StellarSDK::getTestNetInstance();

// cursor("now") skips historical data and streams only new events
$sdk->transactions()
    ->cursor("now")
    ->stream(function (TransactionResponse $tx): void {
        printf(
            "Tx: %s | Ledger: %d | Ops: %d\n",
            $tx->getHash(),
            $tx->getLedger(),
            $tx->getOperationCount()
        );
    });
```

## Stream Payments for an Account

```php
<?php

declare(strict_types=1);

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;

$sdk = StellarSDK::getTestNetInstance();
$accountId = 'GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54';

$sdk->payments()
    ->forAccount($accountId)
    ->cursor("now")
    ->stream(function ($payment): void {
        if ($payment instanceof PaymentOperationResponse) {
            $assetStr = Asset::canonicalForm($payment->getAsset()); // "native" or "CODE:ISSUER"
            printf(
                "Payment: %s %s from %s to %s\n",
                $payment->getAmount(),
                $assetStr,
                $payment->getFrom(),
                $payment->getTo()
            );
        }
    });
```

The payments `stream()` method dispatches different response types based on the operation type field: `PaymentOperationResponse`, `CreateAccountOperationResponse`, `AccountMergeOperationResponse`, `PathPaymentStrictSendOperationResponse`, or `PathPaymentStrictReceiveOperationResponse`.

## Stream Ledger Closings

```php
<?php

declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Ledger\LedgerResponse;

$sdk = StellarSDK::getTestNetInstance();

$sdk->ledgers()
    ->cursor("now")
    ->stream(function (LedgerResponse $ledger): void {
        printf(
            "Ledger #%d closed at %s | Txs: %d\n",
            $ledger->getSequence(),
            $ledger->getClosedAt(),
            $ledger->getSuccessfulTransactionCount()
        );
    });
```

## Stream Effects

```php
<?php

declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Effects\EffectResponse;

$sdk = StellarSDK::getTestNetInstance();
$accountId = 'GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54';

// Stream effects for a specific account
$sdk->effects()
    ->forAccount($accountId)
    ->cursor("now")
    ->stream(function (EffectResponse $effect): void {
        // WRONG: $effect->getType() -- EffectResponse does NOT have getType()
        // CORRECT: $effect->getHumanReadableEffectType() -- e.g. "account_created"
        printf("Effect: %s (type: %s)\n", $effect->getId(), $effect->getHumanReadableEffectType());
    });
```

## Stream Trades

```php
<?php

declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Trades\TradeResponse;

$sdk = StellarSDK::getTestNetInstance();

$sdk->trades()
    ->cursor("now")
    ->stream(function (TradeResponse $trade): void {
        printf(
            "Trade: %s %s for %s %s\n",
            $trade->getBaseAmount(),
            $trade->getBaseAssetCode() ?? 'XLM',
            $trade->getCounterAmount(),
            $trade->getCounterAssetCode() ?? 'XLM'
        );
    });
```

## Stream Order Book

```php
<?php

declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\OrderBook\OrderBookResponse;

$sdk = StellarSDK::getTestNetInstance();
$buyingAsset = Asset::native();
$sellingAsset = Asset::createNonNativeAsset(
    'USDC',
    'GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5'
);

$sdk->orderBook()
    ->forBuyingAsset($buyingAsset)
    ->forSellingAsset($sellingAsset)
    ->cursor("now")
    ->stream(function (OrderBookResponse $orderBook): void {
        printf("Order Book updated - Bids: %d, Asks: %d\n",
            count($orderBook->getBids()),
            count($orderBook->getAsks())
        );
    });
```

## Cursor-Based Reconnection

To resume streaming from a known position (e.g., after a process restart), pass a saved cursor (paging token) instead of `"now"`:

```php
<?php

declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;

$sdk = StellarSDK::getTestNetInstance();

// Load last saved cursor from persistent storage (database, file, etc.)
$lastCursor = file_get_contents('/tmp/stellar_cursor.txt') ?: 'now';

$sdk->transactions()
    ->cursor($lastCursor)
    ->stream(function (TransactionResponse $tx): void {
        // Process the transaction
        printf("Tx: %s\n", $tx->getHash());

        // Persist cursor for recovery
        file_put_contents('/tmp/stellar_cursor.txt', $tx->getPagingToken());
    });
```

## Stopping a Stream

`getAndStream()` runs in `while(true)` and **ignores callback return values**. There is no `closeStream()` method. The only ways to stop:

1. **Throw an exception** inside the callback — breaks the loop, propagates to caller
2. **Call `exit()`** in a forked child process — terminates the child, parent continues
3. **Kill the process** externally (SIGTERM, SIGINT)

```php
// WRONG: return false to stop streaming -- return values are IGNORED
$sdk->payments()->cursor("now")->stream(function ($payment) {
    return false; // Does nothing, stream continues forever
});

// CORRECT: throw an exception to break out after receiving what you need
$sdk->payments()->cursor("now")->stream(function ($payment) {
    echo "Got payment: " . $payment->getOperationId() . PHP_EOL;
    throw new \RuntimeException('done'); // Breaks the infinite loop
});
```

## Stream with Process Fork (pcntl_fork)

To run a stream alongside other operations (e.g., send a payment then detect it via stream), use `pcntl_fork()` to run the stream in a child process. This is the standard pattern for testing or one-shot stream detection.

```php
<?php

declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Util\FriendBot;

$sdk = StellarSDK::getTestNetInstance();

// Set up sender and receiver
$senderKp = KeyPair::random();
$receiverKp = KeyPair::random();
FriendBot::fundTestAccount($senderKp->getAccountId());
FriendBot::fundTestAccount($receiverKp->getAccountId());

$pid = pcntl_fork();

if ($pid == 0) {
    // === CHILD PROCESS: stream payments ===
    $childSdk = StellarSDK::getTestNetInstance(); // New SDK instance for child
    $childSdk->payments()
        ->forAccount($receiverKp->getAccountId())
        ->cursor("now")
        ->stream(function ($payment) {
            if ($payment instanceof PaymentOperationResponse) {
                printf("Streamed payment: %s XLM from %s\n",
                    $payment->getAmount(), $payment->getFrom());
                exit(1); // Success — signal parent via exit code
            }
        });
    exit(0); // Timeout / no matching payment
}

// === PARENT PROCESS: send a payment ===
sleep(3); // Give child time to connect to stream

$sender = $sdk->requestAccount($senderKp->getAccountId());
$tx = (new TransactionBuilder($sender))
    ->addOperation(
        (new PaymentOperationBuilder($receiverKp->getAccountId(), Asset::native(), "100"))->build()
    )
    ->build();
$tx->sign($senderKp, Network::testnet());
$sdk->submitTransaction($tx);

// Wait for child to finish (with timeout)
$startTime = time();
$timeout = 30; // seconds
while (time() - $startTime < $timeout) {
    $result = pcntl_waitpid($pid, $status, WNOHANG);
    if ($result > 0) {
        $exitCode = pcntl_wexitstatus($status);
        echo $exitCode === 1 ? "Stream detected the payment!\n" : "Stream did not detect payment.\n";
        break;
    }
    usleep(500000); // 0.5s poll interval
}
if (time() - $startTime >= $timeout) {
    posix_kill($pid, SIGTERM); // Kill child on timeout
    echo "Timed out waiting for stream.\n";
}
```

Key points for `pcntl_fork`:
- **Create a new SDK instance in the child** — Guzzle HTTP clients are not fork-safe
- **Use `exit(1)` for success, `exit(0)` for failure** in the child — parent reads via `pcntl_wexitstatus()`
- **Use `cursor("now")`** to skip historical events and only stream new ones
- **Sleep before sending** the triggering transaction — give the child time to establish the SSE connection
- **Set a timeout in the parent** with `WNOHANG` polling and `posix_kill()` fallback
- **Requires `pcntl` extension** — available on Unix CLI only (not Apache/FPM)

## PHP Process Considerations

Streaming runs in a blocking infinite loop. Key considerations for production use:

- **Memory**: The loop itself is lightweight but long-running PHP processes can accumulate memory. Monitor `memory_get_usage()` and restart periodically if needed.
- **Timeouts**: The SDK sets `'read_timeout' => null` to disable read timeouts. Ensure your PHP `max_execution_time` is set to `0` for CLI scripts.
- **Error recovery**: `getAndStream()` automatically retries on `ServerException` with a 10-second delay. Non-server errors (network failures) will propagate as exceptions.
- **Signal handling**: Use `pcntl_signal()` to handle `SIGTERM`/`SIGINT` for graceful shutdown in CLI workers.
- **Supervisor**: Run streaming scripts under a process manager (Supervisor, systemd) to restart on crashes.

```php
// CLI script with proper timeout configuration
ini_set('max_execution_time', '0');  // No execution time limit
ini_set('memory_limit', '256M');     // Set appropriate memory limit
```
