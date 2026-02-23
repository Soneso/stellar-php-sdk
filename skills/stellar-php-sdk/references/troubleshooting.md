# Troubleshooting Guide

## Exception Hierarchy

```
\ErrorException
  └── HorizonRequestException        # All Horizon API errors

\RuntimeException                     # Soroban RPC HTTP errors (non-2xx status)

\InvalidArgumentException             # JSON parse errors, invalid XDR, bad parameters

\ErrorException
  └── SorobanContractParserException  # WASM bytecode parsing errors

GuzzleHttp\Exception\GuzzleException  # Network/HTTP transport failures
  ├── RequestException                # Request-level failures
  │   └── BadResponseException        # 4xx/5xx HTTP responses
  │       ├── ClientException         # 4xx errors
  │       └── ServerException         # 5xx errors
  └── ConnectException                # Connection failures (DNS, timeout)
```

## Horizon Error Handling

### HorizonRequestException

Every failed Horizon request throws `HorizonRequestException`. It wraps Guzzle exceptions and provides structured error details.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

$sdk = StellarSDK::getTestNetInstance();

try {
    $account = $sdk->requestAccount('GINVALID_ACCOUNT_ID');
} catch (HorizonRequestException $e) {
    $statusCode = $e->getStatusCode();          // e.g., 404
    $url = $e->getRequestedUrl();               // Requested URL
    $method = $e->getHttpMethod();              // GET, POST, etc.
    $retryAfter = $e->getRetryAfter();          // Set on 429 rate-limit responses

    $errorResponse = $e->getHorizonErrorResponse();
    if ($errorResponse !== null) {
        $type = $errorResponse->getType();      // Error type URL
        $title = $errorResponse->getTitle();    // Short description
        $status = $errorResponse->getStatus();  // HTTP status code
        $detail = $errorResponse->getDetail();  // Human-readable detail

        // For transaction failures, extras contains result codes
        $extras = $errorResponse->getExtras();
        if ($extras !== null) {
            $txResultCode = $extras->getResultCodesTransaction();   // e.g., "tx_failed"
            $opResultCodes = $extras->getResultCodesOperation();    // e.g., ["op_underfunded"]
            $envelopeXdr = $extras->getEnvelopeXdr();               // Transaction envelope
            $resultXdr = $extras->getResultXdr();                   // Result XDR for debugging
        }
    }
}
```

### Common HTTP Status Codes

| Status | Meaning | Typical Cause |
|--------|---------|---------------|
| 400 | Bad Request | Malformed transaction, invalid parameters |
| 404 | Not Found | Account/transaction/resource does not exist |
| 429 | Too Many Requests | Rate limit exceeded; check `getRetryAfter()` |
| 500 | Internal Server Error | Horizon server issue |
| 504 | Gateway Timeout | Horizon overloaded or transaction took too long |

## Transaction Submission Errors

### Handling SubmitTransactionResponse

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

$sdk = StellarSDK::getTestNetInstance();

// Assume $transaction is a signed Transaction object
try {
    $response = $sdk->submitTransaction($transaction);

    if ($response->isSuccessful()) {
        $hash = $response->getHash();
        // Transaction confirmed in ledger
    } else {
        // Transaction included but operations failed
        $extras = $response->getExtras();
        if ($extras !== null) {
            $resultCodes = $extras->getResultCodes();
            if ($resultCodes !== null) {
                $txCode = $resultCodes->getTransactionResultCode();
                $opCodes = $resultCodes->getOperationsResultCodes();
            }
        }
    }
} catch (HorizonRequestException $e) {
    // HTTP-level failure (400, 504, etc.)
    $errorResponse = $e->getHorizonErrorResponse();
    if ($errorResponse !== null) {
        $extras = $errorResponse->getExtras();
        if ($extras !== null) {
            $txCode = $extras->getResultCodesTransaction();
            $opCodes = $extras->getResultCodesOperation();
        }
    }
}
```

### Transaction Result Codes Reference

| Code | Cause | Solution |
|------|-------|----------|
| `tx_failed` | One or more operations failed | Check operation result codes |
| `tx_bad_seq` | Sequence number mismatch | Reload account, rebuild transaction |
| `tx_insufficient_fee` | Fee below network minimum | Increase `setMaxOperationFee()` or use `requestFeeStats()` |
| `tx_bad_auth` | Invalid signature or insufficient weight | Verify signer key and threshold weights |
| `tx_no_source_account` | Source account does not exist | Create/fund the account first |
| `tx_too_early` | Current time before minTime bound | Adjust TimeBounds or wait |
| `tx_too_late` | Current time past maxTime bound | Rebuild with new TimeBounds |
| `tx_insufficient_balance` | Account lacks XLM for fee + reserves | Fund the source account |

### Operation Result Codes Reference

| Code | Cause | Solution |
|------|-------|----------|
| `op_underfunded` | Insufficient balance for payment | Check available balance minus reserves |
| `op_no_trust` | Destination has no trustline for asset | Destination must call ChangeTrustOperation first |
| `op_not_authorized` | Asset requires authorization | Issuer must authorize the trustline |
| `op_line_full` | Destination trustline at limit | Destination must increase trust limit |
| `op_no_destination` | Destination account does not exist | Create account first or verify address |
| `op_low_reserve` | Below minimum XLM reserve | Add XLM to cover base reserve (0.5 XLM per entry) |
| `op_already_exists` | Offer/entry already exists | Use update instead of create |
| `op_no_issuer` | Asset issuer account not found | Verify issuer account ID |

### Handling Bad Sequence Numbers

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

/**
 * Submits a transaction with automatic retry on tx_bad_seq errors.
 * The $buildTransaction callable receives an account and returns a signed Transaction.
 *
 * @param StellarSDK $sdk Horizon client
 * @param string $sourceAccountId G-address of the source account
 * @param KeyPair $signer Signing keypair
 * @param callable $buildTransaction fn(TransactionBuilderAccount $account): Transaction
 * @param int $maxRetries Maximum retry attempts
 * @return \Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse
 */
function submitWithSequenceRetry(
    StellarSDK $sdk,
    string $sourceAccountId,
    KeyPair $signer,
    callable $buildTransaction,
    int $maxRetries = 3,
): \Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse {
    $attempt = 0;
    while ($attempt < $maxRetries) {
        try {
            $account = $sdk->requestAccount($sourceAccountId);
            $transaction = $buildTransaction($account);
            $transaction->sign($signer, Network::testnet());
            $response = $sdk->submitTransaction($transaction);

            if ($response->isSuccessful()) {
                return $response;
            }

            $txCode = $response->getExtras()
                ?->getResultCodes()
                ?->getTransactionResultCode();

            if ($txCode === 'tx_bad_seq') {
                $attempt++;
                continue;
            }

            throw new \RuntimeException("Transaction failed: " . ($txCode ?? 'unknown'));
        } catch (HorizonRequestException $e) {
            $txCode = $e->getHorizonErrorResponse()
                ?->getExtras()
                ?->getResultCodesTransaction();

            if ($txCode === 'tx_bad_seq' && $attempt < $maxRetries - 1) {
                $attempt++;
                continue;
            }
            throw $e;
        }
    }

    throw new \RuntimeException("Max retries exceeded for transaction submission");
}
```

## Soroban RPC Errors

### Simulation Failures

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;

$server = new SorobanServer('https://soroban-testnet.stellar.org');

$simRequest = new SimulateTransactionRequest(transaction: $transaction);
$simResponse = $server->simulateTransaction($simRequest);

// Check for RPC-level errors (malformed request, server issues)
if ($simResponse->error !== null) {
    $errorCode = $simResponse->error->getCode();
    $errorMessage = $simResponse->error->getMessage();
    // Handle RPC error
}

// Check for simulation-level errors (contract execution failure)
if ($simResponse->resultError !== null) {
    $errorDetail = $simResponse->resultError;
    // Common causes:
    // - Contract function does not exist
    // - Wrong argument types or count
    // - Contract panic/assertion failure
    // - Insufficient authorization
}

// Successful simulation -- apply results to transaction
if ($simResponse->transactionData !== null) {
    $transaction->setSorobanTransactionData($simResponse->transactionData);
    $transaction->addResourceFee($simResponse->minResourceFee);
    $sorobanAuth = $simResponse->getSorobanAuth();
    if ($sorobanAuth !== null) {
        $transaction->setSorobanAuth($sorobanAuth);
    }
}
```

### Expired Footprints (Restore Required)

When simulation detects expired ledger entries, `restorePreamble` is present. You must restore entries before submitting the original transaction.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\RestoreFootprintOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Crypto\KeyPair;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$sourceKeyPair = KeyPair::fromSeed('SCZANGBA5YHTNYVVV3C7CAZMCLXPILHSE7HG3EQOVLU7BFXQMB3AVJY');

$simResponse = $server->simulateTransaction(
    new SimulateTransactionRequest(transaction: $transaction)
);

if ($simResponse->restorePreamble !== null) {
    $restorePreamble = $simResponse->restorePreamble;
    $account = $server->getAccount($sourceKeyPair->getAccountId());

    $restoreOp = (new RestoreFootprintOperationBuilder())->build();
    $restoreTx = (new TransactionBuilder($account))
        ->addOperation($restoreOp)
        ->build();

    $restoreTx->setSorobanTransactionData($restorePreamble->transactionData);
    $restoreTx->addResourceFee($restorePreamble->minResourceFee);
    $restoreTx->sign($sourceKeyPair, Network::testnet());

    $sendResponse = $server->sendTransaction($restoreTx);

    // Poll for restore completion
    $txResponse = $server->getTransaction($sendResponse->hash);
    while ($txResponse->status === GetTransactionResponse::STATUS_NOT_FOUND) {
        sleep(3);
        $txResponse = $server->getTransaction($sendResponse->hash);
    }

    if ($txResponse->status === GetTransactionResponse::STATUS_SUCCESS) {
        // Now re-simulate and submit the original transaction
    }
}
```

### SendTransaction Status Codes

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;

// After: $sendResponse = $server->sendTransaction($signedTx);

switch ($sendResponse->status) {
    case SendTransactionResponse::STATUS_PENDING:
        // Accepted -- poll getTransaction() for final result
        break;

    case SendTransactionResponse::STATUS_DUPLICATE:
        // Already submitted -- poll getTransaction() with the hash
        break;

    case SendTransactionResponse::STATUS_TRY_AGAIN_LATER:
        // Network congestion -- wait and retry
        sleep(5);
        break;

    case SendTransactionResponse::STATUS_ERROR:
        // Submission failed
        $errorResult = $sendResponse->getErrorXdrTransactionResult();
        $diagnosticEvents = $sendResponse->getDiagnosticEvents();
        break;
}
```

### Polling for Transaction Completion

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Soroban\SorobanServer;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;

/**
 * Polls the RPC server until a transaction reaches a terminal status.
 *
 * @param SorobanServer $server RPC server client
 * @param string $txHash Hex-encoded transaction hash
 * @param int $timeoutSeconds Maximum wait time
 * @param int $intervalSeconds Polling interval
 * @return GetTransactionResponse Terminal response (SUCCESS or FAILED)
 */
function pollTransaction(
    SorobanServer $server,
    string $txHash,
    int $timeoutSeconds = 30,
    int $intervalSeconds = 3,
): GetTransactionResponse {
    $elapsed = 0;
    while ($elapsed < $timeoutSeconds) {
        $response = $server->getTransaction($txHash);

        if ($response->status === GetTransactionResponse::STATUS_SUCCESS) {
            return $response;
        }

        if ($response->status === GetTransactionResponse::STATUS_FAILED) {
            throw new \RuntimeException(
                "Soroban transaction {$txHash} failed."
            );
        }

        // STATUS_NOT_FOUND means still processing
        sleep($intervalSeconds);
        $elapsed += $intervalSeconds;
    }

    throw new \RuntimeException(
        "Transaction {$txHash} not confirmed within {$timeoutSeconds}s"
    );
}
```

## Network and Connectivity Issues

### Guzzle Timeout Configuration

```php
<?php declare(strict_types=1);

use GuzzleHttp\Client;
use Soneso\StellarSDK\StellarSDK;

$httpClient = new Client([
    'timeout' => 30,            // Total request timeout in seconds
    'connect_timeout' => 10,    // Connection timeout in seconds
]);

$sdk = new StellarSDK('https://horizon-testnet.stellar.org');
$sdk->setHttpClient($httpClient);
```

### Handling Connection Failures

```php
<?php declare(strict_types=1);

use GuzzleHttp\Exception\ConnectException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\StellarSDK;

$sdk = StellarSDK::getTestNetInstance();

try {
    $response = $sdk->submitTransaction($transaction);
} catch (HorizonRequestException $e) {
    $previous = $e->getPrevious();

    if ($previous instanceof ConnectException) {
        // DNS resolution failure, connection refused, or network timeout
        // Safe to retry -- the transaction was never received by Horizon
    }

    if ($e->getStatusCode() === 429) {
        $waitSeconds = (int) ($e->getRetryAfter() ?? 5);
        sleep($waitSeconds);
        // Retry the request
    }

    if ($e->getStatusCode() === 504) {
        // Gateway timeout -- transaction may or may not have been received
        // Check by hash before resubmitting to avoid duplicates
    }
}
```

### Production Retry with Exponential Backoff

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Exceptions\HorizonRequestException;

/**
 * Executes a Horizon request with exponential backoff retry logic.
 *
 * @param callable $request The request callable to execute
 * @param int $maxRetries Maximum number of retry attempts
 * @return mixed The request result
 * @throws HorizonRequestException If all retries are exhausted
 */
function horizonRequestWithRetry(
    callable $request,
    int $maxRetries = 3,
): mixed {
    $attempt = 0;
    while (true) {
        try {
            return $request();
        } catch (HorizonRequestException $e) {
            $attempt++;

            if ($e->getStatusCode() === 429 && $attempt <= $maxRetries) {
                $wait = (int) ($e->getRetryAfter() ?? (2 ** $attempt));
                sleep($wait);
                continue;
            }

            if ($e->getStatusCode() >= 500 && $attempt <= $maxRetries) {
                sleep(2 ** $attempt);
                continue;
            }

            throw $e;
        }
    }
}
```

## Debugging Patterns

### Enable Soroban RPC Logging

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Soroban\SorobanServer;

$server = new SorobanServer('https://soroban-testnet.stellar.org');
$server->enableLogging = true;
// All RPC request/response JSON bodies are printed to stdout
```

### Inspect Transaction XDR Before Submission

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\AbstractTransaction;

// Get the base64 XDR envelope for external inspection
$envelopeXdr = $transaction->toEnvelopeXdrBase64();

// Decode and inspect a transaction from XDR string
$decoded = AbstractTransaction::fromEnvelopeBase64XdrString($envelopeXdr);
$operations = $decoded->getOperations();
$fee = $decoded->getFee();
$sourceAccount = $decoded->getSourceAccount();

foreach ($operations as $index => $op) {
    $opClass = get_class($op);
    // Log each operation type for verification
}
```

### Check Transaction Status on Horizon

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

$sdk = StellarSDK::getTestNetInstance();

try {
    $txResponse = $sdk->requestTransaction($txHash);
    $successful = $txResponse->isSuccessful();
    $ledger = $txResponse->getLedger();
    $createdAt = $txResponse->getCreatedAt();
} catch (HorizonRequestException $e) {
    if ($e->getStatusCode() === 404) {
        // Transaction not found -- may not have been submitted or still pending
    }
}
```

## Common Mistakes

**Wrong network passphrase:** Signing with `Network::testnet()` for a mainnet transaction produces invalid signatures. Always match the Network to your Horizon/RPC endpoint.

**Stale sequence numbers:** Building multiple transactions for the same account without submitting them sequentially causes `tx_bad_seq`. Always reload the account or increment the sequence number between builds.

**Insufficient fee for Soroban:** Soroban transactions require a resource fee from simulation on top of the base fee. Always call `simulateTransaction()` first and apply `minResourceFee` via `$transaction->addResourceFee()`.

**Missing trustline:** Sending non-native assets to an account without a trustline fails with `op_no_trust`. The destination must execute `ChangeTrustOperation` before receiving the asset.

**XLM reserve requirements:** Every subentry (trustline, offer, data entry, signer) requires 0.5 XLM base reserve. Creating entries without sufficient XLM fails with `op_low_reserve`.

**Forgetting to apply simulation data:** After simulating a Soroban transaction, you must call `setSorobanTransactionData()`, `addResourceFee()`, and `setSorobanAuth()` on the transaction before signing and submitting.
