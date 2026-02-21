# SEP-06: Deposit and Withdrawal API

SEP-06 defines a standard protocol for programmatic deposits and withdrawals through anchors. Users send off-chain assets (USD via bank, BTC, etc.) to receive Stellar tokens, or redeem Stellar tokens for off-chain assets.

**Use SEP-06 when:**
- Building automated deposit/withdrawal flows
- Integrating anchor services programmatically without user-facing web flows
- You need direct API access (vs. SEP-24's interactive popup approach)

**Spec:** [SEP-0006](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md)

## Quick example

This example shows how to authenticate with an anchor via SEP-10 and initiate a deposit request.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\TransferServerService\DepositRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

// 1. Authenticate with the anchor via SEP-10
$webAuth = WebAuth::fromDomain("testanchor.stellar.org", Network::testnet());
$userKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");
$jwtToken = $webAuth->jwtToken($userKeyPair->getAccountId(), [$userKeyPair]);

// 2. Create transfer service and request deposit
$transferService = TransferServerService::fromDomain("testanchor.stellar.org");

$request = new DepositRequest(
    assetCode: "USD",
    account: $userKeyPair->getAccountId(),
    jwt: $jwtToken
);

$response = $transferService->deposit($request);

echo "Deposit instructions: " . $response->how . PHP_EOL;
echo "Fee: " . $response->feeFixed . PHP_EOL;
```

## Creating the service

### From domain (recommended)

The SDK discovers the `TRANSFER_SERVER` URL automatically from the anchor's `stellar.toml` file.

```php
<?php

use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

// Discovers TRANSFER_SERVER from stellar.toml via SEP-01
$transferService = TransferServerService::fromDomain("testanchor.stellar.org");
```

### Direct URL

If you already know the transfer server URL, construct the service directly.

```php
<?php

use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = new TransferServerService("https://testanchor.stellar.org/sep6");
```

## Querying anchor info

Before initiating deposits or withdrawals, query the info endpoint to discover supported assets, methods, and requirements.

```php
<?php

use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain("testanchor.stellar.org");
$info = $transferService->info();

// Check deposit assets and their limits
foreach ($info->depositAssets as $code => $asset) {
    echo "Deposit $code: " . ($asset->enabled ? "enabled" : "disabled") . PHP_EOL;
    if ($asset->authenticationRequired) {
        echo "  Authentication required" . PHP_EOL;
    }
    if ($asset->minAmount) {
        echo "  Min: $asset->minAmount" . PHP_EOL;
    }
    if ($asset->maxAmount) {
        echo "  Max: $asset->maxAmount" . PHP_EOL;
    }
    if ($asset->feeFixed) {
        echo "  Fixed fee: $asset->feeFixed" . PHP_EOL;
    }
    if ($asset->feePercent) {
        echo "  Percent fee: $asset->feePercent%" . PHP_EOL;
    }
}

// Check withdrawal assets
foreach ($info->withdrawAssets as $code => $asset) {
    echo "Withdraw $code: " . ($asset->enabled ? "enabled" : "disabled") . PHP_EOL;
}

// Check deposit-exchange assets (for cross-asset deposits with SEP-38 quotes)
if ($info->depositExchangeAssets) {
    foreach ($info->depositExchangeAssets as $code => $asset) {
        echo "Deposit-Exchange $code: " . ($asset->enabled ? "enabled" : "disabled") . PHP_EOL;
    }
}

// Check withdraw-exchange assets (for cross-asset withdrawals with SEP-38 quotes)
if ($info->withdrawExchangeAssets) {
    foreach ($info->withdrawExchangeAssets as $code => $asset) {
        echo "Withdraw-Exchange $code: " . ($asset->enabled ? "enabled" : "disabled") . PHP_EOL;
    }
}

// Feature flags
if ($info->featureFlags) {
    echo "Account creation supported: " . ($info->featureFlags->accountCreation ? "yes" : "no") . PHP_EOL;
    echo "Claimable balances supported: " . ($info->featureFlags->claimableBalances ? "yes" : "no") . PHP_EOL;
}

// Check endpoint availability
echo "Fee endpoint enabled: " . ($info->feeInfo?->enabled ? "yes" : "no") . PHP_EOL;
echo "Transactions endpoint enabled: " . ($info->transactionsInfo?->enabled ? "yes" : "no") . PHP_EOL;
echo "Transaction endpoint enabled: " . ($info->transactionInfo?->enabled ? "yes" : "no") . PHP_EOL;
```

## Deposits

A deposit is when a user sends an external asset (BTC, USD via bank, etc.) to an anchor and receives equivalent Stellar tokens in their account.

### Basic deposit request

Request deposit instructions from the anchor by specifying the asset code and destination Stellar account.

> **Note:** The `account` parameter accepts both regular Stellar accounts (`G...`) and Soroban contract accounts (`C...`) per SEP-06 v4.3.0. Contract accounts can authenticate via SEP-45.

> **Note:** The `type` parameter corresponds to the SEP-06 `funding_method` concept introduced in v4.3.0. The SDK currently supports `type`; `funding_method` may be added in a future release.

```php
<?php

use Soneso\StellarSDK\SEP\TransferServerService\DepositRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationNeededException;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationStatusException;

$transferService = TransferServerService::fromDomain("testanchor.stellar.org");

$request = new DepositRequest(
    assetCode: "USD",
    account: "GCQTGZQTVZ...",  // Stellar account to receive tokens (G... or C... for contracts)
    jwt: $jwtToken,
    type: "bank_account",      // Optional: deposit method (SEPA, SWIFT, etc.)
    amount: "100.00"           // Optional: helps anchor determine KYC needs
);

try {
    $response = $transferService->deposit($request);
    
    // Display deposit instructions to user
    if ($response->how) {
        echo "How to deposit: " . $response->how . PHP_EOL;
    }
    
    // Structured deposit instructions (preferred over 'how')
    if ($response->instructions) {
        foreach ($response->instructions as $key => $instruction) {
            echo "$key: " . $instruction->value . PHP_EOL;
            if ($instruction->description) {
                echo "  (" . $instruction->description . ")" . PHP_EOL;
            }
        }
    }
    
    // Save transaction ID for status tracking
    if ($response->id) {
        echo "Transaction ID: " . $response->id . PHP_EOL;
    }
    
    // Fee info
    if ($response->feeFixed) {
        echo "Fixed fee: " . $response->feeFixed . PHP_EOL;
    }
    if ($response->feePercent) {
        echo "Percent fee: " . $response->feePercent . "%" . PHP_EOL;
    }
    
    // Amount limits
    if ($response->minAmount) {
        echo "Minimum deposit: " . $response->minAmount . PHP_EOL;
    }
    if ($response->maxAmount) {
        echo "Maximum deposit: " . $response->maxAmount . PHP_EOL;
    }
    
    // Estimated time
    if ($response->eta) {
        echo "Estimated time: " . $response->eta . " seconds" . PHP_EOL;
    }
    
    // Extra info
    if ($response->extraInfo?->message) {
        echo "Note: " . $response->extraInfo->message . PHP_EOL;
    }
    
} catch (CustomerInformationNeededException $e) {
    // Anchor needs KYC info via SEP-12
    echo "Required fields: " . PHP_EOL;
    foreach ($e->response->fields as $field) {
        echo "  - $field" . PHP_EOL;
    }
    
} catch (CustomerInformationStatusException $e) {
    // KYC submitted but pending/denied
    echo "KYC status: " . $e->response->status . PHP_EOL;
    if ($e->response->moreInfoUrl) {
        echo "More info: " . $e->response->moreInfoUrl . PHP_EOL;
    }
}
```

### Deposit with all options

The `DepositRequest` class supports optional parameters for different use cases.

```php
<?php

use Soneso\StellarSDK\SEP\TransferServerService\DepositRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain("testanchor.stellar.org");

$request = new DepositRequest(
    assetCode: "USD",
    account: "GCQTGZQTVZ...",
    memoType: "id",                           // Memo type for Stellar payment (text, id, hash)
    memo: "12345",                            // Memo value
    emailAddress: "user@example.com",         // For anchor to send updates
    type: "SEPA",                             // Deposit method
    lang: "en",                               // Response language (RFC 4646)
    onChangeCallback: "https://wallet.example.com/callback",  // Status update webhook
    amount: "500.00",                         // Deposit amount
    countryCode: "USA",                       // ISO 3166-1 alpha-3
    claimableBalanceSupported: "true",        // Enable claimable balance for trustline-less deposits
    customerId: "cust-123",                   // SEP-12 customer ID if known
    locationId: "loc-456",                    // For cash deposits: pickup location
    extraFields: ["custom_field" => "value"], // Anchor-specific extra fields
    jwt: $jwtToken
);

$response = $transferService->deposit($request);
```

## Withdrawals

A withdrawal is when a user redeems Stellar tokens for their off-chain equivalent, such as sending USDC to receive USD in a bank account.

### Basic withdrawal request

Request withdrawal instructions by specifying the asset and withdrawal method.

> **Note:** The `account` parameter accepts both regular Stellar accounts (`G...`) and Soroban contract accounts (`C...`) per SEP-06 v4.3.0. The `type` parameter is deprecated in favor of `funding_method`.

```php
<?php

use Soneso\StellarSDK\SEP\TransferServerService\WithdrawRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationNeededException;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationStatusException;

$transferService = TransferServerService::fromDomain("testanchor.stellar.org");

$request = new WithdrawRequest(
    assetCode: "USDC",
    type: "bank_account",      // Withdrawal method: bank_account, cash, crypto, mobile, etc.
    jwt: $jwtToken,
    account: "GCQTGZQTVZ...",  // Optional: source Stellar account
    amount: "500.00"           // Optional: withdrawal amount
);

try {
    $response = $transferService->withdraw($request);
    
    // Where to send the Stellar payment
    if ($response->accountId) {
        echo "Send payment to: " . $response->accountId . PHP_EOL;
    }
    
    // Include memo in the payment
    if ($response->memoType && $response->memo) {
        echo "Memo ($response->memoType): " . $response->memo . PHP_EOL;
    }
    
    // Save transaction ID for status tracking
    if ($response->id) {
        echo "Transaction ID: " . $response->id . PHP_EOL;
    }
    
    // Fee info
    if ($response->feeFixed) {
        echo "Fixed fee: " . $response->feeFixed . PHP_EOL;
    }
    if ($response->feePercent) {
        echo "Percent fee: " . $response->feePercent . "%" . PHP_EOL;
    }
    
    // Amount limits
    if ($response->minAmount) {
        echo "Minimum withdrawal: " . $response->minAmount . PHP_EOL;
    }
    if ($response->maxAmount) {
        echo "Maximum withdrawal: " . $response->maxAmount . PHP_EOL;
    }
    
    // Estimated time
    if ($response->eta) {
        echo "Estimated time: " . $response->eta . " seconds" . PHP_EOL;
    }
    
} catch (CustomerInformationNeededException $e) {
    echo "Need KYC fields: " . implode(", ", $e->response->fields) . PHP_EOL;
    
} catch (CustomerInformationStatusException $e) {
    echo "KYC status: " . $e->response->status . PHP_EOL;
}
```

### Withdrawal with all options

The `WithdrawRequest` class supports parameters for refund handling, memos, and more.

```php
<?php

use Soneso\StellarSDK\SEP\TransferServerService\WithdrawRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain("testanchor.stellar.org");

$request = new WithdrawRequest(
    assetCode: "USDC",
    type: "bank_account",
    account: "GCQTGZQTVZ...",             // Source Stellar account
    lang: "en",                            // Response language
    onChangeCallback: "https://wallet.example.com/callback",
    amount: "1000.00",
    countryCode: "DEU",
    refundMemo: "refund-123",              // Memo for refund payments
    refundMemoType: "text",                // Refund memo type
    customerId: "cust-123",                // SEP-12 customer ID
    locationId: "loc-456",                 // For cash withdrawals: pickup location
    extraFields: ["bank_name" => "Example Bank"],
    jwt: $jwtToken
);

$response = $transferService->withdraw($request);
```

## Exchange operations (cross-asset)

For deposits or withdrawals with currency conversion (e.g., deposit BRL, receive USDC), use the exchange endpoints. These require anchor support for SEP-38 quotes.

### Deposit exchange

Deposit one asset (e.g., off-chain BRL) and receive a different Stellar asset (e.g., USDC).

```php
<?php

use Soneso\StellarSDK\SEP\TransferServerService\DepositExchangeRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain("testanchor.stellar.org");

// Deposit BRL, receive USDC on Stellar
$depositExchange = new DepositExchangeRequest(
    destinationAsset: "USDC",               // Stellar asset to receive
    sourceAsset: "iso4217:BRL",             // Off-chain asset being deposited (SEP-38 format)
    amount: "480.00",                       // Amount in source asset
    account: "GCQTGZQTVZ...",               // Stellar account to receive tokens
    quoteId: "282837",                      // Optional: SEP-38 quote ID for locked exchange rate
    type: "bank_account",                   // Deposit method
    jwt: $jwtToken
);

$response = $transferService->depositExchange($depositExchange);

echo "Transaction ID: " . $response->id . PHP_EOL;
if ($response->instructions) {
    foreach ($response->instructions as $key => $instruction) {
        echo "$key: " . $instruction->value . PHP_EOL;
    }
}
```

### Withdraw exchange

Send one Stellar asset (e.g., USDC) and receive a different off-chain asset (e.g., NGN).

```php
<?php

use Soneso\StellarSDK\SEP\TransferServerService\WithdrawExchangeRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain("testanchor.stellar.org");

// Withdraw USDC, receive NGN to bank
$withdrawExchange = new WithdrawExchangeRequest(
    sourceAsset: "USDC",                    // Stellar asset to send
    destinationAsset: "iso4217:NGN",        // Off-chain asset to receive (SEP-38 format)
    amount: "100.00",                       // Amount in source asset
    type: "bank_account",                   // Withdrawal method
    quoteId: "282838",                      // Optional: SEP-38 quote ID for locked exchange rate
    account: "GCQTGZQTVZ...",               // Source Stellar account
    jwt: $jwtToken
);

$response = $transferService->withdrawExchange($withdrawExchange);

echo "Transaction ID: " . $response->id . PHP_EOL;
echo "Send to: " . $response->accountId . PHP_EOL;
if ($response->memo) {
    echo "Memo: " . $response->memo . PHP_EOL;
}
```

## Checking fees

Query the fee endpoint to calculate fees before initiating transfers.

```php
<?php

use Soneso\StellarSDK\SEP\TransferServerService\FeeRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain("testanchor.stellar.org");

// Check if fee endpoint is enabled
$info = $transferService->info();
if ($info->feeInfo?->enabled) {
    $feeRequest = new FeeRequest(
        operation: "deposit",    // "deposit" or "withdraw"
        assetCode: "USD",
        amount: 100.00,
        type: "bank_account",    // Optional: deposit/withdrawal method
        jwt: $jwtToken
    );
    
    $feeResponse = $transferService->fee($feeRequest);
    echo "Fee for deposit: " . $feeResponse->fee . PHP_EOL;
}
```

## Transaction history

List all transactions for an account, with optional filtering by asset, type, and time range.

```php
<?php

use Soneso\StellarSDK\SEP\TransferServerService\AnchorTransactionsRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain("testanchor.stellar.org");

$request = new AnchorTransactionsRequest(
    assetCode: "USD",
    account: "GCQTGZQTVZ...",
    jwt: $jwtToken,
    noOlderThan: new DateTime("-30 days"),  // Optional: filter by date
    limit: 10,                               // Optional: max results
    kind: "deposit",                         // Optional: "deposit" or "withdrawal"
    pagingId: null,                          // Optional: for pagination
    lang: "en"                               // Optional: response language
);

$response = $transferService->transactions($request);

foreach ($response->transactions as $tx) {
    echo "Transaction: " . $tx->id . PHP_EOL;
    echo "  Kind: " . $tx->kind . PHP_EOL;
    echo "  Status: " . $tx->status . PHP_EOL;
    echo "  Amount In: " . ($tx->amountIn ?? "pending") . PHP_EOL;
    echo "  Amount Out: " . ($tx->amountOut ?? "pending") . PHP_EOL;
    echo "  Started: " . $tx->startedAt . PHP_EOL;
    
    // For exchange transactions
    if ($tx->amountInAsset) {
        echo "  Amount In Asset: " . $tx->amountInAsset . PHP_EOL;
    }
    if ($tx->amountOutAsset) {
        echo "  Amount Out Asset: " . $tx->amountOutAsset . PHP_EOL;
    }
    
    // Fee details
    if ($tx->feeDetails) {
        echo "  Total Fee: " . $tx->feeDetails->total . PHP_EOL;
    } elseif ($tx->amountFee) {
        echo "  Fee: " . $tx->amountFee . PHP_EOL;
    }
    
    // Refund information
    if ($tx->refunds) {
        echo "  Refunded: " . $tx->refunds->amountRefunded . PHP_EOL;
    }
    
    echo PHP_EOL;
}
```

## Single transaction status

Query a specific transaction by ID, Stellar transaction hash, or external transaction ID.

```php
<?php

use Soneso\StellarSDK\SEP\TransferServerService\AnchorTransactionRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain("testanchor.stellar.org");

// Query by anchor transaction ID
$request = new AnchorTransactionRequest();
$request->id = "82fhs729f63dh0v4";
$request->jwt = $jwtToken;

$response = $transferService->transaction($request);
$tx = $response->transaction;

echo "Status: " . $tx->status . PHP_EOL;
echo "Kind: " . $tx->kind . PHP_EOL;

// Check if user action is required by a deadline
if ($tx->userActionRequiredBy) {
    echo "Action required by: " . $tx->userActionRequiredBy . PHP_EOL;
}

// For withdrawals, show payment destination
if ($tx->withdrawAnchorAccount) {
    echo "Send to: " . $tx->withdrawAnchorAccount . PHP_EOL;
    echo "Memo: " . $tx->withdrawMemo . " (" . $tx->withdrawMemoType . ")" . PHP_EOL;
}

// For deposits, show deposit instructions
if ($tx->instructions) {
    foreach ($tx->instructions as $key => $instruction) {
        echo "$key: " . $instruction->value . PHP_EOL;
    }
}

// Check for claimable balance (deposit)
if ($tx->claimableBalanceId) {
    echo "Claimable Balance ID: " . $tx->claimableBalanceId . PHP_EOL;
}

// Also supports lookup by Stellar transaction hash
$request = new AnchorTransactionRequest();
$request->stellarTransactionId = "17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a";
$request->jwt = $jwtToken;
$response = $transferService->transaction($request);

// Or by external transaction ID
$request = new AnchorTransactionRequest();
$request->externalTransactionId = "1238234";
$request->jwt = $jwtToken;
$response = $transferService->transaction($request);
```

## Updating pending transactions

When an anchor requests more info via `pending_transaction_info_update` status, use this endpoint to provide the missing information.

```php
<?php

use Soneso\StellarSDK\SEP\TransferServerService\AnchorTransactionRequest;
use Soneso\StellarSDK\SEP\TransferServerService\PatchTransactionRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain("testanchor.stellar.org");

// First, check what fields are required
$txRequest = new AnchorTransactionRequest();
$txRequest->id = "82fhs729f63dh0v4";
$txRequest->jwt = $jwtToken;
$txResponse = $transferService->transaction($txRequest);

if ($txResponse->transaction->status === "pending_transaction_info_update") {
    // Check required fields
    if ($txResponse->transaction->requiredInfoUpdates) {
        echo "Required updates:" . PHP_EOL;
        foreach ($txResponse->transaction->requiredInfoUpdates as $field => $info) {
            echo "  - $field: " . $info->description . PHP_EOL;
        }
    }
    
    if ($txResponse->transaction->requiredInfoMessage) {
        echo "Message: " . $txResponse->transaction->requiredInfoMessage . PHP_EOL;
    }
    
    // Submit the updated information
    $patchRequest = new PatchTransactionRequest(
        id: "82fhs729f63dh0v4",
        fields: [
            "dest" => "12345678901234",        // Bank account
            "dest_extra" => "021000021"        // Routing number
        ],
        jwt: $jwtToken
    );
    
    $response = $transferService->patchTransaction($patchRequest);
    echo "Updated, status code: " . $response->getStatusCode() . PHP_EOL;
}
```

## Error handling

The SDK throws specific exceptions for different error conditions.

```php
<?php

use Soneso\StellarSDK\SEP\TransferServerService\DepositRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;
use Soneso\StellarSDK\SEP\TransferServerService\AuthenticationRequiredException;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationNeededException;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationStatusException;
use GuzzleHttp\Exception\GuzzleException;
use Exception;

try {
    $transferService = TransferServerService::fromDomain("testanchor.stellar.org");
    
    $request = new DepositRequest(
        assetCode: "USD",
        account: "GCQTGZQTVZ...",
        jwt: $jwtToken
    );
    
    $response = $transferService->deposit($request);
    
} catch (AuthenticationRequiredException $e) {
    // Endpoint requires SEP-10 authentication
    echo "Authentication required. Get a JWT token via SEP-10 first." . PHP_EOL;
    
} catch (CustomerInformationNeededException $e) {
    // Anchor needs KYC info - submit via SEP-12
    echo "KYC required. Fields needed:" . PHP_EOL;
    foreach ($e->response->fields as $field) {
        echo "  - $field" . PHP_EOL;
    }
    // Now use SEP-12 to submit the required customer information
    
} catch (CustomerInformationStatusException $e) {
    // KYC submitted but has issues
    $status = $e->response->status;
    if ($status === "denied") {
        echo "KYC denied. Contact anchor support." . PHP_EOL;
        if ($e->response->moreInfoUrl) {
            echo "Details: " . $e->response->moreInfoUrl . PHP_EOL;
        }
    } elseif ($status === "pending") {
        echo "KYC pending review. Try again later." . PHP_EOL;
        if ($e->response->eta) {
            echo "Estimated wait: " . $e->response->eta . " seconds" . PHP_EOL;
        }
    }
    
} catch (GuzzleException $e) {
    // Network/HTTP errors
    echo "Request failed: " . $e->getMessage() . PHP_EOL;
    
} catch (Exception $e) {
    // Domain not found, transfer server not available, etc.
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
```

### Common exceptions

| Exception | Cause | Solution |
|-----------|-------|----------|
| `AuthenticationRequiredException` | Missing or invalid JWT | Authenticate via SEP-10 first |
| `CustomerInformationNeededException` | KYC information required | Submit info via SEP-12 |
| `CustomerInformationStatusException` | KYC pending or denied | Wait for review or contact anchor |
| `GuzzleException` | Network or HTTP error | Check connectivity, retry |
| `Exception` | Domain/service unavailable | Verify anchor domain and availability |

## Transaction statuses

| Status | Meaning |
|--------|---------|
| `incomplete` | Transaction not yet ready, more info needed (non-interactive) |
| `pending_user_transfer_start` | Waiting for user to send funds to anchor |
| `pending_user_transfer_complete` | User sent funds, processing |
| `pending_external` | Waiting on external system (bank, crypto network) |
| `pending_anchor` | Anchor is processing the transaction |
| `pending_stellar` | Stellar transaction pending |
| `pending_trust` | User must add trustline for the asset |
| `pending_customer_info_update` | Anchor needs more KYC info. Use SEP-12 `GET /customer` to find required fields |
| `pending_transaction_info_update` | Anchor needs more transaction info. Query `/transaction` for `required_info_updates`, then use PATCH |
| `on_hold` | Transaction is on hold (e.g., compliance review) |
| `completed` | Transaction successfully completed |
| `refunded` | Transaction refunded to user |
| `expired` | Transaction timed out without completion |
| `no_market` | No market available for requested conversion |
| `too_small` | Transaction amount below minimum |
| `too_large` | Transaction amount exceeds maximum |
| `error` | Unrecoverable error occurred |

## Complete deposit flow

This example shows a complete deposit flow: authentication, info discovery, deposit initiation, and transaction polling.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\TransferServerService\AnchorTransactionRequest;
use Soneso\StellarSDK\SEP\TransferServerService\DepositRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationNeededException;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

$anchorDomain = "testanchor.stellar.org";
$userKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CJDQ66EQ7DZTPBRJFN4A");

// 1. Authenticate via SEP-10
$webAuth = WebAuth::fromDomain($anchorDomain, Network::testnet());
$jwtToken = $webAuth->jwtToken($userKeyPair->getAccountId(), [$userKeyPair]);

// 2. Create transfer service and check info
$transferService = TransferServerService::fromDomain($anchorDomain);
$info = $transferService->info();

// Verify deposit is supported for USD
$usdDeposit = $info->depositAssets["USD"] ?? null;
if (!$usdDeposit || !$usdDeposit->enabled) {
    throw new Exception("USD deposits not supported");
}

// 3. Initiate deposit
try {
    $depositRequest = new DepositRequest(
        assetCode: "USD",
        account: $userKeyPair->getAccountId(),
        type: "bank_account",
        amount: "100.00",
        claimableBalanceSupported: "true",
        jwt: $jwtToken
    );
    
    $depositResponse = $transferService->deposit($depositRequest);
    $transactionId = $depositResponse->id;
    
    echo "Deposit initiated. Transaction ID: $transactionId" . PHP_EOL;
    
    // Display deposit instructions
    if ($depositResponse->instructions) {
        echo "Deposit instructions:" . PHP_EOL;
        foreach ($depositResponse->instructions as $key => $instruction) {
            echo "  $key: " . $instruction->value . PHP_EOL;
        }
    }
    
} catch (CustomerInformationNeededException $e) {
    // Handle KYC requirements via SEP-12
    echo "KYC required. Submit via SEP-12: " . implode(", ", $e->response->fields) . PHP_EOL;
    exit(1);
}

// 4. Poll for transaction status
$txRequest = new AnchorTransactionRequest();
$txRequest->id = $transactionId;
$txRequest->jwt = $jwtToken;

$maxAttempts = 60;
$attempt = 0;

while ($attempt < $maxAttempts) {
    $txResponse = $transferService->transaction($txRequest);
    $status = $txResponse->transaction->status;
    
    echo "Status: $status" . PHP_EOL;
    
    switch ($status) {
        case "completed":
            echo "Deposit completed!" . PHP_EOL;
            echo "Amount received: " . $txResponse->transaction->amountOut . PHP_EOL;
            exit(0);
            
        case "pending_user_transfer_start":
            echo "Waiting for off-chain deposit..." . PHP_EOL;
            break;
            
        case "pending_trust":
            echo "Add trustline for the asset" . PHP_EOL;
            break;
            
        case "pending_customer_info_update":
            echo "Additional KYC required" . PHP_EOL;
            break;
            
        case "error":
        case "expired":
            echo "Transaction failed: " . ($txResponse->transaction->message ?? $status) . PHP_EOL;
            exit(1);
    }
    
    sleep(10);
    $attempt++;
}
```

## Related SEPs

- [SEP-01](sep-01.md) - Stellar TOML (service discovery)
- [SEP-10](sep-10.md) - Web authentication (required for most operations)
- [SEP-12](sep-12.md) - KYC API (for customer information submission)
- [SEP-24](sep-24.md) - Interactive deposits/withdrawals (alternative approach)
- [SEP-38](sep-38.md) - Quotes API (for exchange operations)
- [SEP-45](sep-45.md) - Soroban contract authentication (alternative to SEP-10 for contract accounts)

---

[Back to SEP Overview](README.md)
