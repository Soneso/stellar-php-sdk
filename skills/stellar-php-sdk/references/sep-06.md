# SEP-06: Deposit and Withdrawal API

**Purpose:** Programmatic deposits and withdrawals through anchors without user-facing web flows.
**Prerequisites:** Requires JWT from SEP-10 (see [sep-10.md](sep-10.md))
**SDK Namespace:** `Soneso\StellarSDK\SEP\TransferServerService`
**Spec:** SEP-0006 v4.3.0

## Table of Contents

- [Service Initialization](#service-initialization)
- [Info Endpoint](#info-endpoint)
- [Deposit Flow](#deposit-flow)
- [Deposit Exchange Flow (cross-asset)](#deposit-exchange-flow-cross-asset)
- [Withdraw Flow](#withdraw-flow)
- [Withdraw Exchange Flow (cross-asset)](#withdraw-exchange-flow-cross-asset)
- [Fee Endpoint](#fee-endpoint)
- [Transaction History](#transaction-history)
- [Single Transaction Status](#single-transaction-status)
- [Patch Transaction](#patch-transaction)
- [Error Handling](#error-handling)
- [Transaction Statuses](#transaction-statuses)
- [Common Pitfalls](#common-pitfalls)

---

## Service Initialization

### From domain (recommended)

Reads `TRANSFER_SERVER` from the anchor's `stellar.toml` via SEP-01 automatically.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

// Throws Exception if TRANSFER_SERVER is not found in stellar.toml
$transferService = TransferServerService::fromDomain('testanchor.stellar.org');
```

### Direct URL constructor

Use when you already have the transfer server URL.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = new TransferServerService('https://testanchor.stellar.org/sep6');
```

Constructor signature:
```
new TransferServerService(string $serviceAddress, ?Client $httpClient = null)
```

The constructor strips trailing slashes from `$serviceAddress`. You can pass a custom Guzzle `Client` for timeouts or proxy configuration.

---

## Info Endpoint

Query anchor capabilities before initiating deposits or withdrawals. All fields in response objects are `null` when not provided by the anchor.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain('testanchor.stellar.org');

// Optional: pass JWT and/or language code
$info = $transferService->info(jwt: $jwtToken, language: 'en');
```

`info()` signature:
```
info(?string $jwt = null, ?string $language = null): InfoResponse
```

### InfoResponse fields

| Property | Type | Description |
|----------|------|-------------|
| `$depositAssets` | `array<string, DepositAsset>\|null` | Keyed by asset code |
| `$depositExchangeAssets` | `array<string, DepositExchangeAsset>\|null` | Keyed by asset code |
| `$withdrawAssets` | `array<string, WithdrawAsset>\|null` | Keyed by asset code |
| `$withdrawExchangeAssets` | `array<string, WithdrawExchangeAsset>\|null` | Keyed by asset code |
| `$feeInfo` | `AnchorFeeInfo\|null` | Fee endpoint availability |
| `$transactionsInfo` | `AnchorTransactionsInfo\|null` | Transactions endpoint availability |
| `$transactionInfo` | `AnchorTransactionInfo\|null` | Transaction endpoint availability |
| `$featureFlags` | `AnchorFeatureFlags\|null` | Feature support flags |

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain('testanchor.stellar.org');
$info = $transferService->info();

// --- Deposit assets (keyed by asset code string) ---
foreach ($info->depositAssets as $code => $asset) {
    // $asset is DepositAsset
    echo "$code enabled: " . ($asset->enabled ? 'yes' : 'no') . PHP_EOL;
    echo "  auth required: " . ($asset->authenticationRequired ? 'yes' : 'no') . PHP_EOL;
    echo "  fee fixed: " . ($asset->feeFixed ?? 'none') . PHP_EOL;
    echo "  fee percent: " . ($asset->feePercent ?? 'none') . PHP_EOL;
    echo "  min: " . ($asset->minAmount ?? 'none') . PHP_EOL;
    echo "  max: " . ($asset->maxAmount ?? 'none') . PHP_EOL;

    // Deprecated fields array (keyed by field name, values are AnchorField)
    if ($asset->fields) {
        foreach ($asset->fields as $fieldName => $field) {
            echo "  field $fieldName: " . $field->description . PHP_EOL;
            echo "    optional: " . ($field->optional ? 'yes' : 'no') . PHP_EOL;
            if ($field->choices) {
                echo "    choices: " . implode(', ', $field->choices) . PHP_EOL;
            }
        }
    }
}

// --- Withdraw assets ---
foreach ($info->withdrawAssets as $code => $asset) {
    // $asset is WithdrawAsset
    echo "$code withdraw enabled: " . ($asset->enabled ? 'yes' : 'no') . PHP_EOL;
    echo "  fee fixed: " . ($asset->feeFixed ?? 'none') . PHP_EOL;
    echo "  min: " . ($asset->minAmount ?? 'none') . PHP_EOL;

    // types: keyed by type name (e.g. 'bank_account', 'cash')
    // each value is array<string, AnchorField>|null
    if ($asset->types) {
        foreach ($asset->types as $typeName => $fields) {
            echo "  type: $typeName" . PHP_EOL;
            if ($fields) {
                foreach ($fields as $fieldName => $field) {
                    echo "    field $fieldName: " . $field->description . PHP_EOL;
                }
            }
        }
    }
}

// --- Deposit exchange assets (for cross-asset deposits) ---
if ($info->depositExchangeAssets) {
    foreach ($info->depositExchangeAssets as $code => $asset) {
        // $asset is DepositExchangeAsset (same shape as DepositAsset)
        echo "$code deposit-exchange enabled: " . ($asset->enabled ? 'yes' : 'no') . PHP_EOL;
    }
}

// --- Withdraw exchange assets (for cross-asset withdrawals) ---
if ($info->withdrawExchangeAssets) {
    foreach ($info->withdrawExchangeAssets as $code => $asset) {
        // $asset is WithdrawExchangeAsset (same shape as WithdrawAsset)
        echo "$code withdraw-exchange enabled: " . ($asset->enabled ? 'yes' : 'no') . PHP_EOL;
    }
}

// --- Endpoint availability ---
echo "fee endpoint enabled: " . ($info->feeInfo?->enabled ? 'yes' : 'no') . PHP_EOL;
echo "fee auth required: " . ($info->feeInfo?->authenticationRequired ? 'yes' : 'no') . PHP_EOL;
echo "fee description: " . ($info->feeInfo?->description ?? '') . PHP_EOL;
echo "transactions endpoint: " . ($info->transactionsInfo?->enabled ? 'yes' : 'no') . PHP_EOL;
echo "transaction endpoint: " . ($info->transactionInfo?->enabled ? 'yes' : 'no') . PHP_EOL;

// --- Feature flags ---
if ($info->featureFlags) {
    // accountCreation defaults to true, claimableBalances defaults to false
    echo "account creation: " . ($info->featureFlags->accountCreation ? 'yes' : 'no') . PHP_EOL;
    echo "claimable balances: " . ($info->featureFlags->claimableBalances ? 'yes' : 'no') . PHP_EOL;
}
```

### Asset object fields summary

**DepositAsset** and **DepositExchangeAsset** share the same fields:

| Property | Type | Description |
|----------|------|-------------|
| `$enabled` | `bool` | Whether deposits are supported |
| `$authenticationRequired` | `bool\|null` | Whether JWT is required |
| `$feeFixed` | `float\|null` | Fixed fee in asset units |
| `$feePercent` | `float\|null` | Percentage fee in percentage points |
| `$minAmount` | `float\|null` | Minimum deposit amount |
| `$maxAmount` | `float\|null` | Maximum deposit amount |
| `$fields` | `array<string, AnchorField>\|null` | Deprecated: field requirements |

**WithdrawAsset** and **WithdrawExchangeAsset** share the same fields, but instead of `$fields` they have:

| Property | Type | Description |
|----------|------|-------------|
| `$types` | `array<string, array<string, AnchorField>\|null>\|null` | Withdrawal methods with field requirements |

**AnchorField** (individual field descriptor):

| Property | Type | Description |
|----------|------|-------------|
| `$description` | `string\|null` | Human-readable description |
| `$optional` | `bool\|null` | Whether field is optional (defaults to false) |
| `$choices` | `array<string>\|null` | Valid values |

---

## Deposit Flow

A deposit is where the user sends an external asset (cash, BTC, bank transfer) to the anchor, and the anchor sends equivalent Stellar tokens to the user's account. Call `info()` first to check the asset's `minAmount`/`maxAmount` and whether `type` is required by the anchor.

### DepositRequest constructor

Required positional parameters: `$assetCode`, `$account`. All others are optional and use named arguments.

```php
new DepositRequest(
    string $assetCode,
    string $account,
    ?string $memoType = null,
    ?string $memo = null,
    ?string $emailAddress = null,
    ?string $type = null,
    ?string $walletName = null,      // deprecated
    ?string $walletUrl = null,       // deprecated
    ?string $lang = null,
    ?string $onChangeCallback = null,
    ?string $amount = null,
    ?string $countryCode = null,
    ?string $claimableBalanceSupported = null,
    ?string $customerId = null,
    ?string $locationId = null,
    ?array $extraFields = null,
    ?string $jwt = null,
)
```

### Basic deposit request

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TransferServerService\DepositRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;
use Soneso\StellarSDK\SEP\TransferServerService\AuthenticationRequiredException;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationNeededException;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationStatusException;

$transferService = TransferServerService::fromDomain('testanchor.stellar.org');

$request = new DepositRequest(
    assetCode: 'USD',
    account: 'GCQTGZQTVZ...',  // Stellar account to receive tokens (G... or M... muxed)
    jwt: $jwtToken,
);

try {
    $response = $transferService->deposit($request);

    // $response is DepositResponse
    // how: deprecated terse instructions string
    if ($response->how) {
        echo 'How: ' . $response->how . PHP_EOL;
    }

    // instructions: structured key-value deposit instructions (preferred over 'how')
    // keys are SEP-9 field names; values are DepositInstruction objects
    if ($response->instructions) {
        foreach ($response->instructions as $key => $instruction) {
            // $instruction->value: the field value (e.g. bank account number)
            // $instruction->description: human-readable label
            echo "$key: " . $instruction->value . ' (' . $instruction->description . ')' . PHP_EOL;
        }
    }

    // id: anchor's transaction ID for status polling
    if ($response->id) {
        echo 'Transaction ID: ' . $response->id . PHP_EOL;
    }

    // eta: estimated seconds to credit (uninitialized when absent — use isset())
    if (isset($response->eta)) {
        echo 'ETA: ' . $response->eta . 's' . PHP_EOL;
    }

    // feeFixed / feePercent
    if ($response->feeFixed !== null) {
        echo 'Fee (fixed): ' . $response->feeFixed . PHP_EOL;
    }
    if ($response->feePercent !== null) {
        echo 'Fee (%): ' . $response->feePercent . PHP_EOL;
    }

    // minAmount / maxAmount
    if ($response->minAmount !== null) {
        echo 'Min: ' . $response->minAmount . PHP_EOL;
    }
    if ($response->maxAmount !== null) {
        echo 'Max: ' . $response->maxAmount . PHP_EOL;
    }

    // extraInfo: optional additional message
    if ($response->extraInfo?->message) {
        echo 'Note: ' . $response->extraInfo->message . PHP_EOL;
    }

} catch (AuthenticationRequiredException $e) {
    // No JWT or invalid JWT — authenticate via SEP-10 first
    echo 'Auth required' . PHP_EOL;

} catch (CustomerInformationNeededException $e) {
    // $e->response is CustomerInformationNeededResponse
    // $e->response->fields is array<string> of SEP-12 field names to submit
    echo 'KYC required: ' . implode(', ', $e->response->fields) . PHP_EOL;

} catch (CustomerInformationStatusException $e) {
    // $e->response is CustomerInformationStatusResponse
    // $e->response->status: 'pending' or 'denied'
    // $e->response->moreInfoUrl: ?string
    // $e->response->eta: ?int (seconds)
    echo 'KYC status: ' . $e->response->status . PHP_EOL;
}
```

### Deposit request with all options

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TransferServerService\DepositRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain('testanchor.stellar.org');

$request = new DepositRequest(
    assetCode: 'USD',
    account: 'GCQTGZQTVZ...',
    memoType: 'id',                               // text, id, or hash
    memo: '12345',
    emailAddress: 'user@example.com',
    type: 'SEPA',                                 // deposit method (SEPA, SWIFT, bank_account, cash, etc.)
    lang: 'en',                                   // ISO 639-1 language code
    onChangeCallback: 'https://wallet.example.com/callback',
    amount: '500.00',                             // helps anchor determine KYC needs
    countryCode: 'USA',                           // ISO 3166-1 alpha-3
    claimableBalanceSupported: 'true',            // 'true' or 'false' as string
    customerId: 'cust-123',                       // SEP-12 customer ID
    locationId: 'loc-456',                        // cash drop-off location
    extraFields: ['custom_field' => 'value'],     // anchor-specific fields
    jwt: $jwtToken,
);

$response = $transferService->deposit($request);
```

### DepositResponse fields

| Property | Type | Description |
|----------|------|-------------|
| `$how` | `string\|null` | Deprecated. Terse deposit instructions |
| `$instructions` | `array<string, DepositInstruction>\|null` | Structured deposit instructions |
| `$id` | `string\|null` | Anchor transaction ID |
| `$eta` | `int\|null` | Estimated seconds to credit |
| `$minAmount` | `float\|null` | Minimum deposit amount |
| `$maxAmount` | `float\|null` | Maximum deposit amount |
| `$feeFixed` | `float\|null` | Fixed fee in deposited asset units |
| `$feePercent` | `float\|null` | Percentage fee |
| `$extraInfo` | `ExtraInfo\|null` | Additional info; only field is `$message: ?string` |

**DepositInstruction** (each element of `$instructions`):

| Property | Type | Description |
|----------|------|-------------|
| `$value` | `string` | The field value (e.g., bank account number) |
| `$description` | `string` | Human-readable label |

---

## Deposit Exchange Flow (cross-asset)

For currency-converting deposits (e.g., deposit BRL cash, receive USDC on Stellar). Requires anchor support for SEP-38 quotes.

### DepositExchangeRequest constructor

Required positional parameters: `$destinationAsset`, `$sourceAsset`, `$amount`, `$account`.

```php
new DepositExchangeRequest(
    string $destinationAsset,          // Stellar asset code to receive (must match deposit-exchange info)
    string $sourceAsset,               // Off-chain asset in SEP-38 format (e.g. 'iso4217:BRL')
    string $amount,                    // Amount in source asset
    string $account,                   // Stellar account to receive tokens
    ?string $quoteId = null,           // SEP-38 quote ID for locked exchange rate
    ?string $memoType = null,
    ?string $memo = null,
    ?string $emailAddress = null,
    ?string $type = null,              // Deposit method
    ?string $walletName = null,        // deprecated
    ?string $walletUrl = null,         // deprecated
    ?string $lang = null,
    ?string $onChangeCallback = null,
    ?string $countryCode = null,
    ?string $claimableBalanceSupported = null,
    ?string $customerId = null,
    ?string $locationId = null,
    ?array $extraFields = null,
    ?string $jwt = null,
)
```

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TransferServerService\DepositExchangeRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain('testanchor.stellar.org');

// Deposit BRL (off-chain) and receive USDC on Stellar
$request = new DepositExchangeRequest(
    destinationAsset: 'USDC',          // on-chain Stellar asset code
    sourceAsset: 'iso4217:BRL',        // SEP-38 asset identification format
    amount: '480.00',                  // in source asset (BRL)
    account: 'GCQTGZQTVZ...',
    quoteId: '282837',                 // SEP-38 quote ID (locks exchange rate)
    type: 'bank_account',
    jwt: $jwtToken,
);

// Returns DepositResponse (same shape as regular deposit)
$response = $transferService->depositExchange($request);

echo 'Transaction ID: ' . $response->id . PHP_EOL;
if ($response->instructions) {
    foreach ($response->instructions as $key => $instruction) {
        echo "$key: " . $instruction->value . PHP_EOL;
    }
}
```

`depositExchange()` returns `DepositResponse` (same class as regular deposit).

---

## Withdraw Flow

A withdrawal is where the user sends Stellar tokens to the anchor's account, and the anchor sends equivalent external assets (cash, bank transfer, etc.) to the user's off-chain destination.

### WithdrawRequest constructor

Required positional parameters: `$assetCode`, `$type`. All others are optional.

```php
new WithdrawRequest(
    string $assetCode,
    string $type,                      // crypto, bank_account, cash, mobile, bill_payment, etc.
    ?string $dest = null,              // deprecated: destination account/address
    ?string $destExtra = null,         // deprecated: routing number, BIC, etc.
    ?string $account = null,           // source Stellar account
    ?string $memo = null,              // deprecated when using SEP-10
    ?string $memoType = null,          // deprecated
    ?string $walletName = null,        // deprecated
    ?string $walletUrl = null,         // deprecated
    ?string $lang = null,
    ?string $onChangeCallback = null,
    ?string $amount = null,
    ?string $countryCode = null,
    ?string $refundMemo = null,        // memo for refund payments
    ?string $refundMemoType = null,    // id, text, or hash (required if refundMemo set)
    ?string $customerId = null,
    ?string $locationId = null,
    ?array $extraFields = null,
    ?string $jwt = null,
)
```

### Basic withdraw request

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TransferServerService\WithdrawRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationNeededException;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationStatusException;

$transferService = TransferServerService::fromDomain('testanchor.stellar.org');

$request = new WithdrawRequest(
    assetCode: 'USDC',
    type: 'bank_account',
    account: 'GCQTGZQTVZ...',  // source Stellar account
    amount: '500.00',
    jwt: $jwtToken,
);

try {
    $response = $transferService->withdraw($request);

    // $response is WithdrawResponse

    // accountId: anchor's Stellar account to send tokens to
    if ($response->accountId) {
        echo 'Send payment to: ' . $response->accountId . PHP_EOL;
    }

    // memo / memoType: include in the Stellar payment
    if ($response->memoType && $response->memo) {
        echo 'Memo (' . $response->memoType . '): ' . $response->memo . PHP_EOL;
    }

    // id: anchor transaction ID for status polling
    if ($response->id) {
        echo 'Transaction ID: ' . $response->id . PHP_EOL;
    }

    // eta, feeFixed, feePercent, minAmount, maxAmount same as DepositResponse
    if (isset($response->eta)) {
        echo 'ETA: ' . $response->eta . 's' . PHP_EOL;
    }
    if ($response->feeFixed !== null) {
        echo 'Fee: ' . $response->feeFixed . PHP_EOL;
    }

    // extraInfo->message
    if ($response->extraInfo?->message) {
        echo 'Note: ' . $response->extraInfo->message . PHP_EOL;
    }

} catch (CustomerInformationNeededException $e) {
    echo 'KYC required: ' . implode(', ', $e->response->fields) . PHP_EOL;

} catch (CustomerInformationStatusException $e) {
    echo 'KYC status: ' . $e->response->status . PHP_EOL;
}
```

### Withdraw request with all options

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TransferServerService\WithdrawRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain('testanchor.stellar.org');

$request = new WithdrawRequest(
    assetCode: 'USDC',
    type: 'bank_account',
    account: 'GCQTGZQTVZ...',
    lang: 'en',
    onChangeCallback: 'https://wallet.example.com/callback',
    amount: '1000.00',
    countryCode: 'DEU',
    refundMemo: 'refund-123',        // if refundMemo set, refundMemoType must also be set
    refundMemoType: 'text',
    customerId: 'cust-123',
    locationId: 'loc-456',
    extraFields: ['bank_name' => 'Example Bank'],
    jwt: $jwtToken,
);

$response = $transferService->withdraw($request);
```

### WithdrawResponse fields

| Property | Type | Description |
|----------|------|-------------|
| `$accountId` | `string\|null` | Anchor's Stellar account to send payment to |
| `$memoType` | `string\|null` | Memo type: text, id, or hash |
| `$memo` | `string\|null` | Memo value to include in the Stellar payment |
| `$id` | `string\|null` | Anchor transaction ID |
| `$eta` | `int\|null` | Estimated seconds to credit |
| `$minAmount` | `float\|null` | Minimum withdrawal amount |
| `$maxAmount` | `float\|null` | Maximum withdrawal amount |
| `$feeFixed` | `float\|null` | Fixed fee in withdrawn asset units |
| `$feePercent` | `float\|null` | Percentage fee |
| `$extraInfo` | `ExtraInfo\|null` | Additional info; only field is `$message: ?string` |

---

## Withdraw Exchange Flow (cross-asset)

For currency-converting withdrawals (e.g., send USDC on Stellar, receive NGN to bank account). Requires anchor support for SEP-38 quotes.

### WithdrawExchangeRequest constructor

Required positional parameters: `$sourceAsset`, `$destinationAsset`, `$amount`, `$type`.

```php
new WithdrawExchangeRequest(
    string $sourceAsset,               // on-chain Stellar asset code to withdraw
    string $destinationAsset,          // off-chain asset in SEP-38 format (e.g. 'iso4217:NGN')
    string $amount,                    // amount in source asset
    string $type,                      // withdrawal method: bank_account, cash, crypto, etc.
    ?string $dest = null,              // deprecated
    ?string $destExtra = null,         // deprecated
    ?string $quoteId = null,           // SEP-38 quote ID
    ?string $account = null,           // source Stellar account
    ?string $memo = null,              // deprecated when using SEP-10
    ?string $memoType = null,          // deprecated
    ?string $walletName = null,        // deprecated
    ?string $walletUrl = null,         // deprecated
    ?string $lang = null,
    ?string $onChangeCallback = null,
    ?string $countryCode = null,
    ?string $refundMemo = null,
    ?string $refundMemoType = null,
    ?string $customerId = null,
    ?string $locationId = null,
    ?array $extraFields = null,
    ?string $jwt = null,
)
```

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TransferServerService\WithdrawExchangeRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain('testanchor.stellar.org');

// Send USDC on Stellar, receive NGN to bank account
$request = new WithdrawExchangeRequest(
    sourceAsset: 'USDC',               // on-chain asset
    destinationAsset: 'iso4217:NGN',   // SEP-38 format for off-chain
    amount: '100.00',                  // in source asset (USDC)
    type: 'bank_account',
    quoteId: '282838',                 // SEP-38 quote ID
    account: 'GCQTGZQTVZ...',
    jwt: $jwtToken,
);

// Returns WithdrawResponse (same shape as regular withdraw)
$response = $transferService->withdrawExchange($request);

echo 'Send payment to: ' . $response->accountId . PHP_EOL;
if ($response->memo) {
    echo 'Memo (' . $response->memoType . '): ' . $response->memo . PHP_EOL;
}
echo 'Transaction ID: ' . $response->id . PHP_EOL;
```

`withdrawExchange()` returns `WithdrawResponse` (same class as regular withdraw).

---

## Fee Endpoint

Query fees before initiating a transfer. Only available if `$info->feeInfo?->enabled` is true.

### FeeRequest constructor

```php
new FeeRequest(
    string $operation,    // 'deposit' or 'withdraw'
    string $assetCode,    // Stellar asset code
    float $amount,        // amount to deposit or withdraw
    ?string $type = null, // deposit/withdrawal method
    ?string $jwt = null,
)
```

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TransferServerService\FeeRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain('testanchor.stellar.org');

// Check fee endpoint availability first
$info = $transferService->info();
if ($info->feeInfo?->enabled) {
    $feeRequest = new FeeRequest(
        operation: 'deposit',       // 'deposit' or 'withdraw'
        assetCode: 'ETH',
        amount: 2034.09,            // float, not string
        type: 'SEPA',
        jwt: $jwtToken,
    );

    $feeResponse = $transferService->fee($feeRequest);
    // $feeResponse->fee is float: total fee in asset units
    echo 'Fee: ' . $feeResponse->fee . PHP_EOL;
}
```

`fee()` returns `FeeResponse` with a single property: `$fee: float`.

---

## Transaction History

List all transactions for an account with optional filtering.

### AnchorTransactionsRequest constructor

```php
new AnchorTransactionsRequest(
    string $assetCode,
    string $account,
    ?DateTime $noOlderThan = null,  // filter: return transactions on or after this date
    ?int $limit = null,             // max results
    ?string $kind = null,           // 'deposit', 'deposit-exchange', 'withdrawal', 'withdrawal-exchange'
    ?string $pagingId = null,       // pagination: return transactions before this ID (exclusive)
    ?string $lang = null,
    ?string $jwt = null,
)
```

```php
<?php declare(strict_types=1);

use DateTime;
use Soneso\StellarSDK\SEP\TransferServerService\AnchorTransactionsRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain('testanchor.stellar.org');

$request = new AnchorTransactionsRequest(
    assetCode: 'USD',
    account: 'GCQTGZQTVZ...',
    noOlderThan: new DateTime('-30 days'),
    limit: 10,
    kind: 'deposit',               // filter by kind
    jwt: $jwtToken,
);

$response = $transferService->transactions($request);
// $response->transactions is array<AnchorTransaction>

foreach ($response->transactions as $tx) {
    echo 'ID: ' . $tx->id . PHP_EOL;
    echo '  kind: ' . $tx->kind . PHP_EOL;       // deposit, deposit-exchange, withdrawal, withdrawal-exchange
    echo '  status: ' . $tx->status . PHP_EOL;
    echo '  amountIn: ' . ($tx->amountIn ?? 'pending') . PHP_EOL;
    echo '  amountOut: ' . ($tx->amountOut ?? 'pending') . PHP_EOL;
    echo '  amountFee: ' . ($tx->amountFee ?? 'pending') . PHP_EOL;
    echo '  startedAt: ' . $tx->startedAt . PHP_EOL;
    echo '  completedAt: ' . ($tx->completedAt ?? '-') . PHP_EOL;

    // Exchange transactions include asset fields (SEP-38 identification format)
    if ($tx->amountInAsset) {
        echo '  amountInAsset: ' . $tx->amountInAsset . PHP_EOL;
    }
    if ($tx->amountOutAsset) {
        echo '  amountOutAsset: ' . $tx->amountOutAsset . PHP_EOL;
    }

    // Fee details (preferred over deprecated amountFee/amountFeeAsset)
    if ($tx->feeDetails) {
        echo '  feeTotal: ' . $tx->feeDetails->total . PHP_EOL;
        echo '  feeAsset: ' . $tx->feeDetails->asset . PHP_EOL;
        if ($tx->feeDetails->details) {
            foreach ($tx->feeDetails->details as $detail) {
                echo '    ' . $detail->name . ': ' . $detail->amount . PHP_EOL;
            }
        }
    }

    // Refund information
    if ($tx->refunds) {
        echo '  refunds.amountRefunded: ' . $tx->refunds->amountRefunded . PHP_EOL;
        echo '  refunds.amountFee: ' . $tx->refunds->amountFee . PHP_EOL;
        foreach ($tx->refunds->payments as $payment) {
            echo '  refund payment id: ' . $payment->id . ' (' . $payment->idType . ')' . PHP_EOL;
            echo '    amount: ' . $payment->amount . ' fee: ' . $payment->fee . PHP_EOL;
        }
    }
}
```

---

## Single Transaction Status

Query a specific transaction by one of three identifiers.

### AnchorTransactionRequest (property assignment, not constructor args)

`AnchorTransactionRequest` has no positional constructor — assign properties directly:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TransferServerService\AnchorTransactionRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain('testanchor.stellar.org');

// Query by anchor transaction ID
$request = new AnchorTransactionRequest();
$request->id = '82fhs729f63dh0v4';
$request->jwt = $jwtToken;

$response = $transferService->transaction($request);
// $response->transaction is AnchorTransaction

$tx = $response->transaction;
echo 'Status: ' . $tx->status . PHP_EOL;
echo 'Kind: ' . $tx->kind . PHP_EOL;

// Also query by Stellar transaction hash
$request2 = new AnchorTransactionRequest();
$request2->stellarTransactionId = '17a670bc424ff5ce3b386dbfaae9990b66a2a37b4fbe51547e8794962a3f9e6a';
$request2->jwt = $jwtToken;

// Or by external transaction ID
$request3 = new AnchorTransactionRequest();
$request3->externalTransactionId = '1238234';
$request3->jwt = $jwtToken;
```

Available properties on `AnchorTransactionRequest`:
- `$id: ?string`
- `$stellarTransactionId: ?string`
- `$externalTransactionId: ?string`
- `$lang: ?string`
- `$jwt: ?string`

### AnchorTransaction — all fields

```php
$tx = $response->transaction; // AnchorTransaction

// Required fields
$tx->id;                    // string — anchor-generated unique ID
$tx->kind;                  // string — deposit, deposit-exchange, withdrawal, withdrawal-exchange
$tx->status;                // string — see Transaction Statuses section

// Optional status / timing
$tx->statusEta;             // ?int — estimated seconds until status change
$tx->moreInfoUrl;           // ?string — URL for more account/status info
$tx->startedAt;             // ?string — ISO 8601 UTC
$tx->updatedAt;             // ?string — ISO 8601 UTC
$tx->completedAt;           // ?string — ISO 8601 UTC
$tx->userActionRequiredBy;  // ?string — ISO 8601 deadline for user action

// Amount fields (strings with up to 7 decimals)
$tx->amountIn;              // ?string
$tx->amountInAsset;         // ?string — SEP-38 format; present for exchange transactions
$tx->amountOut;             // ?string
$tx->amountOutAsset;        // ?string — SEP-38 format; present for exchange transactions
$tx->amountFee;             // ?string — deprecated; use feeDetails
$tx->amountFeeAsset;        // ?string — deprecated; use feeDetails

// Fee details (preferred)
$tx->feeDetails;            // ?FeeDetails — total, asset, and optional breakdown
// $tx->feeDetails->total: string
// $tx->feeDetails->asset: string
// $tx->feeDetails->details: ?array<FeeDetailsDetails> (name, amount, ?description)

// Quote
$tx->quoteId;               // ?string — SEP-38 quote ID if used

// Account/address info
$tx->from;                  // ?string — sent-from address (BTC, IBAN, bank account, or Stellar)
$tx->to;                    // ?string — sent-to address
$tx->externalExtra;         // ?string — extra info (routing number, BIC, etc.)
$tx->externalExtraText;     // ?string — bank name or store name

// Deposit-specific
$tx->depositMemo;           // ?string — memo used on the Stellar payment
$tx->depositMemoType;       // ?string

// Withdrawal-specific
$tx->withdrawAnchorAccount; // ?string — anchor's Stellar account for receiving payment
$tx->withdrawMemo;          // ?string — memo to use in the Stellar payment to anchor
$tx->withdrawMemoType;      // ?string

// Stellar/external IDs
$tx->stellarTransactionId;  // ?string — Stellar tx hash
$tx->externalTransactionId; // ?string — external system ID

// Status messages
$tx->message;               // ?string — human-readable status explanation

// Refunds
$tx->refunded;              // ?bool — deprecated; use $tx->refunds
$tx->refunds;               // ?TransactionRefunds
// $tx->refunds->amountRefunded: string — total refunded in amountInAsset units
// $tx->refunds->amountFee: string — total refund fees
// $tx->refunds->payments: array<TransactionRefundPayment>
//   $payment->id: string — Stellar tx hash or external ID
//   $payment->idType: string — 'stellar' or 'external'
//   $payment->amount: string
//   $payment->fee: string

// Pending info update (when status = pending_transaction_info_update)
$tx->requiredInfoMessage;   // ?string — human-readable explanation
$tx->requiredInfoUpdates;   // ?array<string, AnchorField> — fields to provide via PATCH

// Deposit instructions
$tx->instructions;          // ?array<string, DepositInstruction>

// Claimable balance
$tx->claimableBalanceId;    // ?string — Claimable Balance ID for deposit (if used)
```

---

## Patch Transaction

When a transaction reaches `pending_transaction_info_update` status, use PATCH to supply the requested fields.

### PatchTransactionRequest constructor

```php
new PatchTransactionRequest(
    string $id,              // transaction ID
    array $fields,           // key-value pairs of fields to update
    ?string $jwt,
)
```

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TransferServerService\AnchorTransactionRequest;
use Soneso\StellarSDK\SEP\TransferServerService\PatchTransactionRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;

$transferService = TransferServerService::fromDomain('testanchor.stellar.org');

// 1. Check what fields are needed
$txRequest = new AnchorTransactionRequest();
$txRequest->id = '82fhs729f63dh0v4';
$txRequest->jwt = $jwtToken;

$txResponse = $transferService->transaction($txRequest);
$tx = $txResponse->transaction;

if ($tx->status === 'pending_transaction_info_update') {
    if ($tx->requiredInfoMessage) {
        echo 'Message: ' . $tx->requiredInfoMessage . PHP_EOL;
    }
    if ($tx->requiredInfoUpdates) {
        foreach ($tx->requiredInfoUpdates as $fieldName => $field) {
            // $field is AnchorField with $description, $optional, $choices
            echo 'Required: ' . $fieldName . ' — ' . $field->description . PHP_EOL;
        }
    }

    // 2. Submit the updated fields
    $patchRequest = new PatchTransactionRequest(
        id: '82fhs729f63dh0v4',
        fields: [
            'dest' => '12345678901234',    // bank account number
            'dest_extra' => '021000021',   // routing number
        ],
        jwt: $jwtToken,
    );

    // Returns Psr\Http\Message\ResponseInterface (raw HTTP response)
    $response = $transferService->patchTransaction($patchRequest);
    echo 'PATCH status: ' . $response->getStatusCode() . PHP_EOL;
}
```

`patchTransaction()` returns `Psr\Http\Message\ResponseInterface`, not a typed SDK response. Check `$response->getStatusCode()` for success (200).

---

## Error Handling

Three domain-specific exceptions plus standard HTTP exceptions:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\TransferServerService\DepositRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;
use Soneso\StellarSDK\SEP\TransferServerService\AuthenticationRequiredException;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationNeededException;
use Soneso\StellarSDK\SEP\TransferServerService\CustomerInformationStatusException;
use GuzzleHttp\Exception\GuzzleException;
use Exception;

$transferService = TransferServerService::fromDomain('testanchor.stellar.org');

try {
    $request = new DepositRequest(
        assetCode: 'USD',
        account: 'GCQTGZQTVZ...',
        jwt: $jwtToken,
    );
    $response = $transferService->deposit($request);

} catch (AuthenticationRequiredException $e) {
    // HTTP 403 with type=authentication_required
    // The endpoint requires a JWT but none was provided (or it was invalid)
    // Solution: authenticate via SEP-10 first and pass the token in the request
    echo 'Auth required — get a JWT via SEP-10' . PHP_EOL;

} catch (CustomerInformationNeededException $e) {
    // HTTP 403 with type=non_interactive_customer_info_needed
    // $e->response is CustomerInformationNeededResponse
    // $e->response->fields is array<string> listing the SEP-12 field names needed
    echo 'KYC fields required: ' . implode(', ', $e->response->fields) . PHP_EOL;
    // Submit the listed fields via SEP-12 PUT /customer, then retry

} catch (CustomerInformationStatusException $e) {
    // HTTP 403 with type=customer_info_status
    // $e->response is CustomerInformationStatusResponse
    $status = $e->response->status; // 'pending' or 'denied'
    if ($status === 'denied') {
        echo 'KYC denied.' . PHP_EOL;
        if ($e->response->moreInfoUrl) {
            echo 'Details: ' . $e->response->moreInfoUrl . PHP_EOL;
        }
    } elseif ($status === 'pending') {
        echo 'KYC under review.' . PHP_EOL;
        if ($e->response->eta !== null) {
            echo 'ETA: ' . $e->response->eta . 's' . PHP_EOL;
        }
    }

} catch (GuzzleException $e) {
    // Network/HTTP error (timeout, server error, etc.)
    echo 'HTTP error: ' . $e->getMessage() . PHP_EOL;

} catch (Exception $e) {
    // Domain not found, TRANSFER_SERVER missing from stellar.toml, JSON parse errors, etc.
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
```

### Exception reference

| Exception | When thrown | `$e->response` type |
|-----------|-------------|---------------------|
| `AuthenticationRequiredException` | No/invalid JWT token | none |
| `CustomerInformationNeededException` | KYC data required | `CustomerInformationNeededResponse` |
| `CustomerInformationStatusException` | KYC pending or denied | `CustomerInformationStatusResponse` |

**CustomerInformationNeededResponse:**
- `$fields: array<string>` — list of SEP-12 field names to submit

**CustomerInformationStatusResponse:**
- `$status: string` — `'pending'` or `'denied'`
- `$moreInfoUrl: ?string`
- `$eta: ?int` — estimated seconds until status update

---

## Transaction Statuses

| Status | Meaning |
|--------|---------|
| `incomplete` | Missing required info; user action needed (non-interactive) |
| `pending_user_transfer_start` | Waiting for user to send funds to anchor |
| `pending_user_transfer_complete` | User sent funds; anchor processing |
| `pending_external` | Waiting on external system (bank, crypto network) |
| `pending_anchor` | Anchor is processing |
| `pending_stellar` | Stellar transaction pending |
| `pending_trust` | User must add trustline for the asset |
| `pending_customer_info_update` | Anchor needs more KYC info via SEP-12 |
| `pending_transaction_info_update` | Anchor needs more transaction info — check `requiredInfoUpdates`, then PATCH |
| `on_hold` | On hold (e.g., compliance review) |
| `completed` | Successfully completed |
| `refunded` | Refunded to user |
| `expired` | Timed out without completion |
| `no_market` | No market available for conversion |
| `too_small` | Amount below minimum |
| `too_large` | Amount exceeds maximum |
| `error` | Unrecoverable error |

---

## Common Pitfalls

**WRONG: accessing `$response->account_id` on WithdrawResponse — the property is `accountId`**

```php
// WRONG: snake_case -- no such property
$anchor = $response->account_id;

// CORRECT: camelCase property
$anchor = $response->accountId;
```

**WRONG: iterating withdraw types as if they were AnchorField objects directly**

`WithdrawAsset::$types` is `array<string, array<string, AnchorField>|null>|null` — each type maps to a fields array, not a single field:

```php
// WRONG: treating each type value as AnchorField
foreach ($asset->types as $typeName => $field) {
    echo $field->description; // TypeError — $field is array or null, not AnchorField
}

// CORRECT: each type value is an array of AnchorField objects (or null)
foreach ($asset->types as $typeName => $fields) {
    if ($fields) {
        foreach ($fields as $fieldName => $field) {
            echo $field->description . PHP_EOL; // AnchorField
        }
    }
}
```

**WRONG: using `AnchorTransactionRequest` constructor with named args**

`AnchorTransactionRequest` has no constructor with parameters — set properties directly:

```php
// WRONG: no such constructor params
$request = new AnchorTransactionRequest(id: '82fhs729f63dh0v4', jwt: $jwtToken);

// CORRECT: assign properties after construction
$request = new AnchorTransactionRequest();
$request->id = '82fhs729f63dh0v4';
$request->jwt = $jwtToken;
```

**WRONG: passing amount as float to DepositRequest/WithdrawRequest**

The `amount` parameter on `DepositRequest` and `WithdrawRequest` is typed `?string`, not `float`:

```php
// WRONG: float
$request = new DepositRequest(assetCode: 'USD', account: $acct, amount: 100.0);

// CORRECT: string
$request = new DepositRequest(assetCode: 'USD', account: $acct, amount: '100.00');
```

Note: `FeeRequest::$amount` IS typed `float` — that is correct for fee queries only.

**WRONG: calling `$transferService->info()` without checking assets before access**

`InfoResponse::$depositAssets` can be `null`. Always null-check before iteration:

```php
// WRONG: will error if anchor has no deposit assets
foreach ($info->depositAssets as $code => $asset) { ... }

// CORRECT: check first
if ($info->depositAssets) {
    foreach ($info->depositAssets as $code => $asset) { ... }
}

// Or use null-safe / ?? to check a specific asset
$usdDeposit = $info->depositAssets['USD'] ?? null;
if ($usdDeposit && $usdDeposit->enabled) { ... }
```

**WRONG: accessing `DepositResponse` properties without `isset()` check**

`DepositResponse::$how`, `$eta`, and `$instructions` have no default value — they are uninitialized (not `null`) when absent from the anchor's JSON response. Accessing them directly throws a fatal error:

```php
// WRONG: throws "Typed property must not be accessed before initialization"
echo $response->how;          // Fatal error if anchor didn't return 'how'
echo $response->instructions; // Fatal error if anchor didn't return 'instructions'

// CORRECT: use isset() before accessing $how, $eta, $instructions
if (isset($response->how)) {
    echo $response->how;
}
if (isset($response->instructions)) {
    foreach ($response->instructions as $name => $instruction) { ... }
}
```

Note: `$id`, `$minAmount`, `$maxAmount`, `$feeFixed`, `$feePercent`, `$extraInfo` all have `= null` defaults and are safe to access directly. `WithdrawResponse` properties are also all safe (all have `= null` defaults).

**WRONG: checking `patchTransaction()` response as a typed SDK object**

`patchTransaction()` returns `Psr\Http\Message\ResponseInterface`, not an SDK response class:

```php
// WRONG: no such property
$response = $transferService->patchTransaction($request);
echo $response->status; // null — ResponseInterface has no 'status' property

// CORRECT: use PSR-7 method
echo $response->getStatusCode(); // e.g. 200
```

**WRONG: forgetting `refundMemoType` when setting `refundMemo`**

Both fields must be set together. Setting one without the other may be rejected by the anchor:

```php
// WRONG: only one set
$request = new WithdrawRequest(assetCode: 'USDC', type: 'bank_account',
    refundMemo: 'ref-123');

// CORRECT: set both
$request = new WithdrawRequest(assetCode: 'USDC', type: 'bank_account',
    refundMemo: 'ref-123', refundMemoType: 'text');
```

**WRONG: using `funding_method` instead of `type`**

The SEP-06 spec introduced `funding_method` as the successor to `type` (v4.3.0). The SDK currently supports `$type`; `funding_method` may be added in a future release. Only `$type` exists:

```php
// WRONG: no such parameter — funding_method does not exist in the SDK
$request = new DepositRequest(assetCode: 'USD', account: $acct, funding_method: 'bank_account');

// CORRECT: use $type
$request = new DepositRequest(assetCode: 'USD', account: $acct, type: 'bank_account');
```

**WRONG: using getters on `AnchorTransactionResponse` / `AnchorTransactionsResponse`**

SEP-06 response objects use promoted constructor properties (direct access), NOT Horizon-style getters:

```php
// WRONG: Horizon-style getters — no such methods
$tx = $response->getTransaction();
$txs = $listResponse->getTransactions();

// CORRECT: direct property access (promoted constructor)
$tx = $response->transaction;       // AnchorTransaction
$txs = $listResponse->transactions; // array<AnchorTransaction>
```

**WRONG: using `withdrawExchange` with the source and destination swapped**

For `WithdrawExchangeRequest`, `sourceAsset` is the Stellar on-chain asset you're sending, and `destinationAsset` is the off-chain asset you want to receive (in SEP-38 format):

```php
// WRONG: swapped — you would be sending fiat and receiving Stellar (that's depositExchange)
$request = new WithdrawExchangeRequest(
    sourceAsset: 'iso4217:NGN',   // WRONG for withdrawExchange
    destinationAsset: 'USDC',
    ...
);

// CORRECT: sourceAsset is the on-chain Stellar asset
$request = new WithdrawExchangeRequest(
    sourceAsset: 'USDC',           // Stellar asset to send
    destinationAsset: 'iso4217:NGN', // off-chain asset to receive
    amount: '100.00',
    type: 'bank_account',
    jwt: $jwtToken,
);
```

---

## Related SEPs

- [SEP-01](sep-01.md) — Stellar TOML (service discovery, provides `TRANSFER_SERVER`)
- [SEP-10](sep-10.md) — Web Authentication (required for most SEP-06 operations)
- [SEP-12](sep-12.md) — KYC API (submit customer info when `CustomerInformationNeededException` is thrown)
- [SEP-24](sep-24.md) — Interactive deposits/withdrawals (alternative approach with web popup)
- [SEP-38](sep-38.md) — Anchor RFQ API (quotes used with deposit-exchange and withdraw-exchange)
