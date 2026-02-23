# SEP-24: Interactive Deposit and Withdrawal

**Purpose:** Interactive web flows for depositing fiat currency to receive Stellar tokens, or withdrawing Stellar tokens to a bank account or other external payment method.
**Prerequisites:** Requires JWT from SEP-10 (or SEP-45 for contract accounts); anchor must publish `TRANSFER_SERVER_SEP0024` in `stellar.toml`
**SDK Namespace:** `Soneso\StellarSDK\SEP\Interactive`

## Table of Contents

- [Service Initialization](#service-initialization)
- [Info Endpoint](#info-endpoint)
- [Deposit Flow](#deposit-flow)
- [Withdrawal Flow](#withdrawal-flow)
- [Transaction Status Polling](#transaction-status-polling)
- [Transaction History](#transaction-history)
- [SEP24Transaction — All Fields](#sep24transaction--all-fields)
- [Transaction Statuses](#transaction-statuses)
- [Refund Objects](#refund-objects)
- [Fee Endpoint (deprecated)](#fee-endpoint-deprecated)
- [Error Handling](#error-handling)
- [Common Pitfalls](#common-pitfalls)

---

## Service Initialization

### From domain (recommended)

`InteractiveService::fromDomain()` fetches the anchor's `stellar.toml`, reads `TRANSFER_SERVER_SEP0024`, and returns a configured service instance. Throws `Exception` if the toml is missing or the field is absent.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;

$service = InteractiveService::fromDomain("testanchor.stellar.org");
```

Constructor signature:
```
InteractiveService::fromDomain(string $domain, ?Client $httpClient = null): InteractiveService
```

### Manual construction

Use when you already have the transfer server URL.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;

$service = new InteractiveService("https://api.anchor.com/sep24");
```

Constructor signature:
```
new InteractiveService(string $serviceAddress, ?Client $httpClient = null)
```

### With a custom HTTP client

```php
<?php declare(strict_types=1);

use GuzzleHttp\Client;
use Soneso\StellarSDK\SEP\Interactive\InteractiveService;

$httpClient = new Client(['timeout' => 30]);
$service = InteractiveService::fromDomain("testanchor.stellar.org", $httpClient);
```

---

## Info Endpoint

`info()` queries `GET /info` to discover supported assets, fee structures, and feature flags.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

// Optional: pass a language code (RFC 4646, e.g. "en", "de", "fr")
$info = $service->info("en");
```

Method signature:
```
info(?string $lang = null): SEP24InfoResponse
```

### SEP24InfoResponse fields

| Field | Type | Description |
|-------|------|-------------|
| `$depositAssets` | `array<string, SEP24DepositAsset>\|null` | Keyed by asset code; null if no deposit assets |
| `$withdrawAssets` | `array<string, SEP24WithdrawAsset>\|null` | Keyed by asset code; null if no withdraw assets |
| `$feeEndpointInfo` | `FeeEndpointInfo\|null` | Info about the (deprecated) `/fee` endpoint |
| `$featureFlags` | `FeatureFlags\|null` | Optional features the anchor supports |

### SEP24DepositAsset fields

| Field | Type | Description |
|-------|------|-------------|
| `$enabled` | `bool` | Whether deposit of this asset is supported |
| `$minAmount` | `float\|null` | Minimum deposit amount; no limit if null |
| `$maxAmount` | `float\|null` | Maximum deposit amount; no limit if null |
| `$feeFixed` | `float\|null` | Fixed fee in units of the deposited asset |
| `$feePercent` | `float\|null` | Percentage fee in percentage points |
| `$feeMinimum` | `float\|null` | Minimum fee in units of the deposited asset |

`SEP24WithdrawAsset` has the same fields for withdrawals.

### FeatureFlags fields

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `$accountCreation` | `bool` | `true` | Anchor can create accounts for users |
| `$claimableBalances` | `bool` | `false` | Anchor can send deposits as claimable balances |

### FeeEndpointInfo fields

| Field | Type | Description |
|-------|------|-------------|
| `$enabled` | `bool` | Whether the `/fee` endpoint is available |
| `$authenticationRequired` | `bool` | Whether SEP-10 auth is required for `/fee` |

### Example: reading info response

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositAsset;
use Soneso\StellarSDK\SEP\Interactive\SEP24WithdrawAsset;

$service = InteractiveService::fromDomain("testanchor.stellar.org");
$info = $service->info();

// Check deposit assets (keyed by asset code)
if ($info->depositAssets !== null) {
    foreach ($info->depositAssets as $code => $asset) {
        // $asset is SEP24DepositAsset
        if ($asset->enabled) {
            echo "Deposit $code: min=" . ($asset->minAmount ?? "none")
                . " max=" . ($asset->maxAmount ?? "none") . PHP_EOL;
            if ($asset->feeFixed !== null) {
                echo "  Fixed fee: " . $asset->feeFixed . PHP_EOL;
            }
            if ($asset->feePercent !== null) {
                echo "  Percent fee: " . $asset->feePercent . "%" . PHP_EOL;
            }
        }
    }
}

// Check withdraw assets
if ($info->withdrawAssets !== null) {
    $usd = $info->withdrawAssets["USD"] ?? null;
    if ($usd instanceof SEP24WithdrawAsset && $usd->enabled) {
        echo "USD withdrawal enabled" . PHP_EOL;
    }
}

// Check feature support
if ($info->featureFlags !== null) {
    echo "Account creation: " . ($info->featureFlags->accountCreation ? "yes" : "no") . PHP_EOL;
    echo "Claimable balances: " . ($info->featureFlags->claimableBalances ? "yes" : "no") . PHP_EOL;
}

// Check fee endpoint availability
if ($info->feeEndpointInfo !== null) {
    echo "Fee endpoint enabled: " . ($info->feeEndpointInfo->enabled ? "yes" : "no") . PHP_EOL;
}
```

---

## Deposit Flow

A deposit converts external funds (bank transfer, card, etc.) into Stellar tokens sent to the user's account. The anchor returns a URL where the user completes the process interactively.

`deposit()` posts to `POST /transactions/deposit/interactive`.

Method signature:
```
deposit(SEP24DepositRequest $request): SEP24InteractiveResponse
```

### SEP24DepositRequest fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `$jwt` | `string` | Yes | JWT from SEP-10 (or SEP-45) authentication |
| `$assetCode` | `string` | Yes | Asset code to receive; use `"native"` for XLM |
| `$assetIssuer` | `string\|null` | No | Issuer G... address; omit for `"native"` |
| `$sourceAsset` | `string\|null` | No | SEP-38 format asset the user sends (e.g. `"iso4217:EUR"`) |
| `$amount` | `float\|null` | No | Amount to deposit; must match quote if `$quoteId` set |
| `$quoteId` | `string\|null` | No | SEP-38 quote ID for cross-asset deposits |
| `$account` | `string\|null` | No | Destination Stellar or muxed account; defaults to JWT account |
| `$memo` | `string\|null` | No | Memo to attach; hash type must be base64-encoded |
| `$memoType` | `string\|null` | No | Memo type: `"text"`, `"id"`, or `"hash"` |
| `$lang` | `string\|null` | No | RFC 4646 language for the interactive UI (e.g. `"en-US"`) |
| `$claimableBalanceSupported` | `string\|null` | No | `"true"` if client supports claimable balances |
| `$customerId` | `string\|null` | No | SEP-12 customer ID to link the transaction |
| `$kycFields` | `StandardKYCFields\|null` | No | SEP-9 KYC data to pre-fill the interactive form |
| `$customFields` | `array\|null` | No | Non-standard KYC fields as `["key" => "value"]` |
| `$customFiles` | `array\|null` | No | Non-standard file uploads as `["key" => "binary_string"]` |
| `$walletName` | `string\|null` | No | Deprecated — wallet display name |
| `$walletUrl` | `string\|null` | No | Deprecated — wallet URL |

### SEP24InteractiveResponse fields

| Field | Type | Description |
|-------|------|-------------|
| `$type` | `string` | Always `"interactive_customer_info_needed"` |
| `$url` | `string` | URL to open in a browser or webview for the user |
| `$id` | `string` | Anchor-generated transaction ID for polling |

### Basic deposit

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;       // from SEP-10 authentication
$request->assetCode = "USDC";

$response = $service->deposit($request);

// Open this URL in a browser or webview for the user
echo "Open: " . $response->url . PHP_EOL;
echo "Transaction ID: " . $response->id . PHP_EOL;
// response->type is always "interactive_customer_info_needed"
```

### Deposit with amount and destination

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->amount = 100.00;
// Receive on a different account than the authenticated one
$request->account = "GXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";
$request->memo = "12345";
$request->memoType = "id";   // "text", "id", or "hash"
$request->lang = "en";

$response = $service->deposit($request);
```

### Deposit with SEP-38 quote (cross-asset)

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

// Get $quoteId from SEP-38 first (see sep-38.md)
$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USDC";
$request->sourceAsset = "iso4217:EUR";  // user sends EUR, receives USDC
$request->quoteId = "quote-abc-123";
$request->amount = 100.00;              // must match the quote's sell_amount

$response = $service->deposit($request);
```

### Deposit with KYC pre-fill

Pass KYC data to pre-fill the anchor's interactive form. Use `StandardKYCFields` with `NaturalPersonKYCFields` for individuals and `OrganizationKYCFields` for businesses.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$personFields = new NaturalPersonKYCFields();
$personFields->firstName = "Jane";
$personFields->lastName = "Doe";
$personFields->emailAddress = "jane@example.com";

// Bank details nested under person fields
$bankFields = new FinancialAccountKYCFields();
$bankFields->bankAccountNumber = "123456789";
$bankFields->bankNumber = "987654321";
$personFields->financialAccountKYCFields = $bankFields;

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $personFields;

// For business accounts:
$orgFields = new OrganizationKYCFields();
$orgFields->name = "Acme Corp";
$kycFields->organizationKYCFields = $orgFields;

$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->kycFields = $kycFields;

// Anchor-specific fields not in SEP-9
$request->customFields = ["employer_name" => "Tech Corp"];
$request->customFiles = ["proof_of_income" => file_get_contents("/path/to/doc.pdf")];

$response = $service->deposit($request);
```

### Deposit with claimable balance support

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
// Tell the anchor the client supports receiving claimable balances
// (useful if the account has no trustline for the asset)
$request->claimableBalanceSupported = "true";

$response = $service->deposit($request);
// Check $tx->claimableBalanceId after completion if the anchor used a claimable balance
```

---

## Withdrawal Flow

A withdrawal converts Stellar tokens into external funds sent to a bank account or other destination. After the user completes the interactive flow, the wallet must send a Stellar payment to the anchor.

`withdraw()` posts to `POST /transactions/withdraw/interactive`.

Method signature:
```
withdraw(SEP24WithdrawRequest $request): SEP24InteractiveResponse
```

### SEP24WithdrawRequest fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `$jwt` | `string` | Yes | JWT from SEP-10 (or SEP-45) authentication |
| `$assetCode` | `string` | Yes | Asset code to withdraw; use `"native"` for XLM |
| `$assetIssuer` | `string\|null` | No | Issuer G... address; omit for `"native"` |
| `$destinationAsset` | `string\|null` | No | SEP-38 format asset the user receives (e.g. `"iso4217:EUR"`) |
| `$amount` | `float\|null` | No | Amount to withdraw |
| `$quoteId` | `string\|null` | No | SEP-38 quote ID for cross-asset withdrawals |
| `$account` | `string\|null` | No | Source Stellar or muxed account; defaults to JWT account |
| `$refundMemo` | `string\|null` | No | Memo for refund payments; requires `$refundMemoType` |
| `$refundMemoType` | `string\|null` | No | Refund memo type: `"text"`, `"id"`, or `"hash"` |
| `$lang` | `string\|null` | No | RFC 4646 language for the interactive UI |
| `$customerId` | `string\|null` | No | SEP-12 customer ID |
| `$kycFields` | `StandardKYCFields\|null` | No | SEP-9 KYC data to pre-fill the interactive form |
| `$customFields` | `array\|null` | No | Non-standard KYC fields as `["key" => "value"]` |
| `$customFiles` | `array\|null` | No | Non-standard file uploads as `["key" => "binary_string"]` |
| `$memo` | `string\|null` | No | Deprecated — use SEP-10 JWT sub for shared accounts |
| `$memoType` | `string\|null` | No | Deprecated — type of deprecated `$memo` field |
| `$walletName` | `string\|null` | No | Deprecated — wallet display name |
| `$walletUrl` | `string\|null` | No | Deprecated — wallet URL |

### Basic withdrawal

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24WithdrawRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24WithdrawRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";

$response = $service->withdraw($request);

echo "Open: " . $response->url . PHP_EOL;
echo "Transaction ID: " . $response->id . PHP_EOL;
// After user completes the form, poll the transaction endpoint.
// When status is "pending_user_transfer_start", send the Stellar payment.
```

### Withdrawal with refund memo

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24WithdrawRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24WithdrawRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->amount = 500.00;
// Memo the anchor uses if it needs to send a refund payment back
$request->refundMemo = "refund-ref-123";
$request->refundMemoType = "text";   // "text", "id", or "hash"
// Must set both refundMemo and refundMemoType together

$response = $service->withdraw($request);
```

### Withdrawal with SEP-38 quote (cross-asset)

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24WithdrawRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

// Get $quoteId from SEP-38 first
$request = new SEP24WithdrawRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USDC";
$request->destinationAsset = "iso4217:EUR";  // user sends USDC, receives EUR
$request->quoteId = "quote-xyz-789";
$request->amount = 500.00;

$response = $service->withdraw($request);
```

### Completing a withdrawal: sending the Stellar payment

After the user completes the interactive flow, poll for `pending_user_transfer_start` status, then send the Stellar payment.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$txRequest = new SEP24TransactionRequest();
$txRequest->jwt = $jwtToken;
$txRequest->id = $transactionId;

$txResponse = $service->transaction($txRequest);
$tx = $txResponse->transaction;

if ($tx->status === "pending_user_transfer_start") {
    // Read withdrawal payment details from the transaction
    $anchorAccount = $tx->withdrawAnchorAccount;  // Anchor's Stellar account to pay
    $memo = $tx->withdrawMemo;                     // Memo to include; null if KYC not done
    $memoType = $tx->withdrawMemoType;             // "text", "id", or "hash"
    $amount = $tx->amountIn;                       // Amount to send

    $sdk = StellarSDK::getTestNetInstance();
    $sourceKeyPair = KeyPair::fromSeed(getenv("STELLAR_SECRET_SEED"));
    $sourceAccount = $sdk->requestAccount($sourceKeyPair->getAccountId());

    $asset = Asset::createNonNativeAsset("USD", "ISSUER_ACCOUNT_ID");

    $paymentOp = (new PaymentOperationBuilder($anchorAccount, $asset, $amount))->build();

    // Attach the memo the anchor specified
    $memoObj = Memo::text($memo); // adjust for memoType as needed

    $transaction = (new TransactionBuilder($sourceAccount))
        ->addOperation($paymentOp)
        ->addMemo($memoObj)
        ->build();

    $transaction->sign($sourceKeyPair, Network::testnet());
    $sdk->submitTransaction($transaction);
}
```

---

## Transaction Status Polling

Use `transaction()` to query a single transaction and `transactions()` for history. Always use the `id` returned from `deposit()` or `withdraw()` for polling.

### Query by anchor transaction ID

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24TransactionRequest();
$request->jwt = $jwtToken;
$request->id = $transactionId;          // from deposit/withdraw response
// OR $request->stellarTransactionId = "abc123...";   // Stellar network hash
// OR $request->externalTransactionId = "BANK-REF-123"; // bank/external reference
// Optional: $request->lang = "en";     // for localized message field

$response = $service->transaction($request);
$tx = $response->transaction;  // SEP24Transaction object

echo "Status: " . $tx->status . PHP_EOL;
echo "Kind: " . $tx->kind . PHP_EOL;
```

Method signature:
```
transaction(SEP24TransactionRequest $request): SEP24TransactionResponse
```

`SEP24TransactionRequest` fields: `$jwt` (required), `$id`, `$stellarTransactionId`, `$externalTransactionId`, `$lang`. At least one of the identifier fields must be set.

`SEP24TransactionResponse` has a single field: `$transaction` (`SEP24Transaction`).

### Polling loop with exponential backoff

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24Transaction;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$terminalStatuses = ['completed', 'refunded', 'expired', 'error', 'no_market', 'too_small', 'too_large'];

$request = new SEP24TransactionRequest();
$request->jwt = $jwtToken;
$request->id = $transactionId;

$maxAttempts = 60;
$baseDelay = 2;

for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
    $response = $service->transaction($request);
    $tx = $response->transaction;

    echo "Status: " . $tx->status . PHP_EOL;

    if (in_array($tx->status, $terminalStatuses)) {
        break; // transaction finished
    }

    if ($tx->status === "pending_user_transfer_start") {
        // User must send the Stellar payment now (for withdrawals)
        break;
    }

    // Use statusEta if provided; otherwise exponential backoff
    $delay = ($tx->statusEta !== null && $tx->statusEta > 0)
        ? min($tx->statusEta, 60)
        : min($baseDelay * (2 ** $attempt), 60);

    sleep($delay);
}
```

---

## Transaction History

`transactions()` returns a list of transactions for the authenticated account, filtered by asset. It queries `GET /transactions`.

Method signature:
```
transactions(SEP24TransactionsRequest $request): SEP24TransactionsResponse
```

### SEP24TransactionsRequest fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `$jwt` | `string` | Yes | JWT token |
| `$assetCode` | `string` | Yes | Asset code to filter by |
| `$noOlderThan` | `DateTime\|null` | No | Only include transactions from this date/time onward |
| `$limit` | `int\|null` | No | Maximum number of transactions to return |
| `$kind` | `string\|null` | No | `"deposit"`, `"withdrawal"`, `"deposit-exchange"`, or `"withdrawal-exchange"`; omit for all |
| `$pagingId` | `string\|null` | No | Returns transactions prior to (exclusive) this ID |
| `$lang` | `string\|null` | No | RFC 4646 language code |

`SEP24TransactionsResponse` has a single field: `$transactions` (`array<SEP24Transaction>`), always an array (never null; empty when no results).

```php
<?php declare(strict_types=1);

use DateTime;
use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionsRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24TransactionsRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->limit = 10;
$request->kind = "deposit";                          // omit for all kinds
$request->noOlderThan = new DateTime("2024-01-01");  // DateTime object
$request->lang = "en";

$response = $service->transactions($request);

foreach ($response->transactions as $tx) {
    echo $tx->id . ": " . $tx->kind . " - " . $tx->status . PHP_EOL;
}

// Pagination: pass the last transaction ID as pagingId for the next page
if (count($response->transactions) > 0) {
    $lastTx = end($response->transactions);
    $request->pagingId = $lastTx->id;
    $nextPage = $service->transactions($request);
}
```

---

## SEP24Transaction — All Fields

The `SEP24Transaction` object is returned inside `SEP24TransactionResponse->transaction` and each element of `SEP24TransactionsResponse->transactions`.

### Always-present fields

| PHP field | JSON key | Type | Description |
|-----------|----------|------|-------------|
| `$id` | `id` | `string` | Unique anchor-generated transaction ID |
| `$kind` | `kind` | `string` | `"deposit"`, `"withdrawal"`, `"deposit-exchange"`, or `"withdrawal-exchange"` |
| `$status` | `status` | `string` | Current processing status (see [Transaction Statuses](#transaction-statuses)) |
| `$startedAt` | `started_at` | `string` | ISO 8601 UTC start timestamp |

### Optional fields (all nullable)

| PHP field | JSON key | Type | Description |
|-----------|----------|------|-------------|
| `$statusEta` | `status_eta` | `int\|null` | Estimated seconds until next status change |
| `$kycVerified` | `kyc_verified` | `bool\|null` | Whether anchor verified user's KYC for this transaction |
| `$moreInfoUrl` | `more_info_url` | `string\|null` | URL with additional transaction details |
| `$amountIn` | `amount_in` | `string\|null` | Amount received by anchor (up to 7 decimals, as string) |
| `$amountInAsset` | `amount_in_asset` | `string\|null` | SEP-38 format asset received (e.g. `"iso4217:USD"` or `"stellar:USDC:G..."`) |
| `$amountOut` | `amount_out` | `string\|null` | Amount sent to user (up to 7 decimals, as string) |
| `$amountOutAsset` | `amount_out_asset` | `string\|null` | SEP-38 format asset sent to user |
| `$amountFee` | `amount_fee` | `string\|null` | Fee charged by anchor (as string) |
| `$amountFeeAsset` | `amount_fee_asset` | `string\|null` | SEP-38 format asset for fee |
| `$quoteId` | `quote_id` | `string\|null` | SEP-38 quote ID used when creating this transaction |
| `$completedAt` | `completed_at` | `string\|null` | ISO 8601 UTC completion timestamp |
| `$updatedAt` | `updated_at` | `string\|null` | ISO 8601 UTC last-update timestamp |
| `$userActionRequiredBy` | `user_action_required_by` | `string\|null` | Deadline for user action (ISO 8601 UTC) |
| `$stellarTransactionId` | `stellar_transaction_id` | `string\|null` | Stellar network transaction hash |
| `$externalTransactionId` | `external_transaction_id` | `string\|null` | External system transaction ID |
| `$message` | `message` | `string\|null` | Human-readable status explanation |
| `$refunded` | `refunded` | `bool\|null` | Deprecated — use `$refunds` and `"refunded"` status instead |
| `$refunds` | `refunds` | `Refund\|null` | Refund details if transaction was refunded |
| `$from` | `from` | `string\|null` | Deposit: sender address; Withdrawal: source Stellar address |
| `$to` | `to` | `string\|null` | Deposit: destination Stellar address; Withdrawal: destination address |

### Deposit-only fields

| PHP field | JSON key | Type | Description |
|-----------|----------|------|-------------|
| `$depositMemo` | `deposit_memo` | `string\|null` | Memo used in the deposit payment |
| `$depositMemoType` | `deposit_memo_type` | `string\|null` | Memo type for `$depositMemo` |
| `$claimableBalanceId` | `claimable_balance_id` | `string\|null` | ID of Claimable Balance used to send the asset |

### Withdrawal-only fields

| PHP field | JSON key | Type | Description |
|-----------|----------|------|-------------|
| `$withdrawAnchorAccount` | `withdraw_anchor_account` | `string\|null` | Anchor's Stellar account to send payment to |
| `$withdrawMemo` | `withdraw_memo` | `string\|null` | Memo to include in the payment; null if KYC not yet complete |
| `$withdrawMemoType` | `withdraw_memo_type` | `string\|null` | Memo type for `$withdrawMemo` |

### Reading transaction fields

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24TransactionRequest();
$request->jwt = $jwtToken;
$request->id = $transactionId;

$response = $service->transaction($request);
$tx = $response->transaction;

// Core fields — always present
echo "ID: " . $tx->id . PHP_EOL;
echo "Kind: " . $tx->kind . PHP_EOL;
echo "Status: " . $tx->status . PHP_EOL;
echo "Started: " . $tx->startedAt . PHP_EOL;

// Amount fields — strings when present (compare carefully)
if ($tx->amountIn !== null) {
    echo "Amount in: " . $tx->amountIn . PHP_EOL;
}
if ($tx->amountOut !== null) {
    echo "Amount out: " . $tx->amountOut . PHP_EOL;
}

// KYC and deadline
if ($tx->kycVerified === true) {
    echo "KYC verified" . PHP_EOL;
}
if ($tx->userActionRequiredBy !== null) {
    echo "Action required by: " . $tx->userActionRequiredBy . PHP_EOL;
}

// Withdrawal payment instructions
if ($tx->kind === "withdrawal" && $tx->status === "pending_user_transfer_start") {
    // withdrawMemo may be null if KYC is not yet complete
    echo "Send " . $tx->amountIn . " to " . $tx->withdrawAnchorAccount . PHP_EOL;
    if ($tx->withdrawMemo !== null) {
        echo "Memo: " . $tx->withdrawMemo . " (" . $tx->withdrawMemoType . ")" . PHP_EOL;
    }
}

// Deposit claimable balance
if ($tx->kind === "deposit" && $tx->claimableBalanceId !== null) {
    echo "Claim balance ID: " . $tx->claimableBalanceId . PHP_EOL;
}
```

---

## Transaction Statuses

The `$status` field on `SEP24Transaction`:

| Status | Description |
|--------|-------------|
| `incomplete` | User has not completed the interactive flow yet |
| `pending_user_transfer_start` | Waiting for user to send funds (deposit: external; withdrawal: Stellar payment) |
| `pending_user_transfer_complete` | Stellar payment received; off-chain processing pending |
| `pending_external` | Waiting for off-chain confirmation (bank transfer, etc.) |
| `pending_anchor` | Anchor is processing the transaction |
| `on_hold` | Transaction held pending compliance review |
| `pending_stellar` | Waiting for Stellar network confirmation |
| `pending_trust` | User must add a trustline for the asset before funds can be sent |
| `pending_user` | User must take an action; see `$message` or `$moreInfoUrl` |
| `completed` | Transaction finished successfully |
| `refunded` | Transaction was fully or partially refunded; see `$refunds` |
| `expired` | Transaction expired before completion |
| `no_market` | No market available for the asset pair (SEP-38 exchange) |
| `too_small` | Amount is below the anchor's minimum threshold |
| `too_large` | Amount exceeds the anchor's maximum threshold |
| `error` | Transaction failed due to an error |

---

## Refund Objects

When a transaction is refunded (`status === "refunded"` or `$refunds` is non-null), inspect the `$refunds` field on the transaction.

### Refund fields

| PHP field | JSON key | Type | Description |
|-----------|----------|------|-------------|
| `$amountRefunded` | `amount_refunded` | `string` | Total refunded to the user (in units of `amount_in_asset`) |
| `$amountFee` | `amount_fee` | `string` | Total fee charged for processing all refund payments |
| `$payments` | `payments` | `array<RefundPayment>` | Individual refund payment records |

### RefundPayment fields

| PHP field | JSON key | Type | Description |
|-----------|----------|------|-------------|
| `$id` | `id` | `string` | Stellar transaction hash or external payment reference |
| `$idType` | `id_type` | `string` | `"stellar"` or `"external"` |
| `$amount` | `amount` | `string` | Amount refunded by this payment |
| `$fee` | `fee` | `string` | Fee charged for this refund payment |

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24TransactionRequest();
$request->jwt = $jwtToken;
$request->id = $transactionId;

$response = $service->transaction($request);
$tx = $response->transaction;

if ($tx->refunds !== null) {
    $refund = $tx->refunds;  // Refund object

    echo "Total refunded: " . $refund->amountRefunded . PHP_EOL;
    echo "Refund fees: " . $refund->amountFee . PHP_EOL;

    // Individual refund payments
    foreach ($refund->payments as $payment) {
        // $payment is RefundPayment
        echo "Payment ID: " . $payment->id . PHP_EOL;
        echo "  Type: " . $payment->idType . PHP_EOL;  // "stellar" or "external"
        echo "  Amount: " . $payment->amount . PHP_EOL;
        echo "  Fee: " . $payment->fee . PHP_EOL;
    }
}
```

---

## Fee Endpoint (deprecated)

The `/fee` endpoint is deprecated in favor of SEP-38 `GET /price`. Only use it if the anchor's `/info` response indicates it is enabled.

Method signature:
```
fee(SEP24FeeRequest $request): SEP24FeeResponse
```

`SEP24FeeRequest` must be constructed with positional arguments:

```php
new SEP24FeeRequest(
    string  $operation,    // "deposit" or "withdraw"
    string  $assetCode,    // asset code
    float   $amount,       // amount to deposit/withdraw
    ?string $type = null,  // optional: payment type e.g. "SEPA", "bank_account"
    ?string $jwt = null    // optional: JWT token
)
```

`SEP24FeeResponse` has a single field: `$fee` (`float|null`).

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24FeeRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

// Always check if the fee endpoint is enabled first
$info = $service->info();
if ($info->feeEndpointInfo !== null && $info->feeEndpointInfo->enabled) {
    $feeRequest = new SEP24FeeRequest(
        operation: "deposit",
        assetCode: "USD",
        amount: 1000.00,
        type: "bank_account",    // optional
        jwt: $jwtToken           // required if authenticationRequired is true
    );

    $feeResponse = $service->fee($feeRequest);
    echo "Fee: " . $feeResponse->fee . PHP_EOL;
}
```

---

## Error Handling

All three exceptions extend `Exception` from `Soneso\StellarSDK\SEP\Interactive`.

| Exception | HTTP status | Trigger | Action |
|-----------|-------------|---------|--------|
| `SEP24AuthenticationRequiredException` | 403 | JWT missing, expired, or invalid | Re-authenticate with SEP-10/45 and retry |
| `RequestErrorException` | 400 / 5xx | Invalid parameters, unsupported asset, server error | Check `getMessage()` for anchor error details; do not retry 400s without fixing the request |
| `SEP24TransactionNotFoundException` | 404 | Transaction ID unknown or not owned by this user | Only thrown by `transaction()`, not by `transactions()` |

```php
<?php declare(strict_types=1);

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24AuthenticationRequiredException;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionNotFoundException;
use Soneso\StellarSDK\SEP\Interactive\RequestErrorException;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

try {
    $request = new SEP24DepositRequest();
    $request->jwt = $jwtToken;
    $request->assetCode = "USD";

    $response = $service->deposit($request);
    echo "URL: " . $response->url . PHP_EOL;

} catch (SEP24AuthenticationRequiredException $e) {
    // HTTP 403 — JWT is invalid, expired, or the endpoint requires auth
    echo "Need to re-authenticate: " . $e->getMessage() . PHP_EOL;

} catch (RequestErrorException $e) {
    // HTTP 400 — bad parameters, unsupported asset, etc.
    echo "Request error (" . $e->getCode() . "): " . $e->getMessage() . PHP_EOL;

} catch (GuzzleException $e) {
    // Network-level error
    echo "Network error: " . $e->getMessage() . PHP_EOL;

} catch (Exception $e) {
    echo "Unexpected error: " . $e->getMessage() . PHP_EOL;
}

// SEP24TransactionNotFoundException is only thrown by transaction(), not transactions()
try {
    $txRequest = new SEP24TransactionRequest();
    $txRequest->jwt = $jwtToken;
    $txRequest->id = "some-transaction-id";

    $response = $service->transaction($txRequest);

} catch (SEP24TransactionNotFoundException $e) {
    // HTTP 404 — ID not found or not owned by authenticated user
    echo "Transaction not found" . PHP_EOL;

} catch (SEP24AuthenticationRequiredException $e) {
    echo "Re-authenticate and retry" . PHP_EOL;

} catch (RequestErrorException $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
```

---

## Common Pitfalls

**Wrong: `SEP24FeeRequest` must use positional constructor — no public setters for core fields at construction**

```php
// WRONG: SEP24FeeRequest has no no-arg constructor — all three params are required
$req = new SEP24FeeRequest();

// CORRECT: pass operation, assetCode, amount positionally
$req = new SEP24FeeRequest("deposit", "USD", 100.00);
// Optional: $req = new SEP24FeeRequest("deposit", "USD", 100.00, "SEPA", $jwtToken);
```

**Wrong: setting `$assetIssuer` for native XLM**

```php
// WRONG: native assets have no issuer
$request->assetCode = "native";
$request->assetIssuer = "GABC..."; // invalid — anchor will reject

// CORRECT: omit assetIssuer for native
$request->assetCode = "native";
```

**Wrong: setting `$refundMemo` without `$refundMemoType` (or vice versa)**

```php
// WRONG: both fields must be set together
$request->refundMemo = "ref-123";
// Missing: $request->refundMemoType = "text";

// CORRECT: always set both together
$request->refundMemo = "ref-123";
$request->refundMemoType = "text";
```

**Wrong: accessing `$withdrawMemo` before KYC is complete**

```php
// WRONG: $withdrawMemo may be null if KYC is not yet verified
$tx->withdrawMemo; // could be null — do not use without null check

// CORRECT: always check
if ($tx->withdrawMemo !== null) {
    // Safe to use $tx->withdrawMemo
}
```

**Wrong: comparing `$amountIn`, `$amountOut`, `$amountFee` as floats**

These fields are `string|null`, not `float`. Cast to float only for arithmetic.

```php
// WRONG: these fields are strings
if ($tx->amountIn > 100.0) { ... }  // string > float comparison

// CORRECT: cast to float for comparison
if ($tx->amountIn !== null && floatval($tx->amountIn) > 100.0) { ... }
```

**Wrong: using `transactions()` to look up by transaction ID**

```php
// WRONG: transactions() requires assetCode and returns a list; it does not support ID lookup
$req = new SEP24TransactionsRequest();
$req->jwt = $jwtToken;
$req->id = $transactionId;  // field does not exist on SEP24TransactionsRequest

// CORRECT: use transaction() (singular) for ID-based lookup
$req = new SEP24TransactionRequest();
$req->jwt = $jwtToken;
$req->id = $transactionId;
$response = $service->transaction($req);
```

**Wrong: not handling `$withdrawMemo` being null in `pending_user_transfer_start`**

The anchor sets `$withdrawMemo` to null until KYC is complete, even when status is `pending_user_transfer_start`. Do not send the Stellar payment if the memo is null.

```php
// CORRECT: verify memo is available before sending the withdrawal payment
if ($tx->status === "pending_user_transfer_start") {
    if ($tx->withdrawMemo === null) {
        // KYC not yet verified — open $tx->moreInfoUrl or wait for status update
        echo "Waiting for KYC verification before sending payment" . PHP_EOL;
    } else {
        // Safe to send the payment with $tx->withdrawAnchorAccount and $tx->withdrawMemo
    }
}
```

---

## Related SEPs

- [SEP-01](sep-01.md) — stellar.toml (`TRANSFER_SERVER_SEP0024` is published here)
- [SEP-10](sep-10.md) — Web Authentication for traditional G... accounts
- [SEP-45](sep-45.md) — Web Authentication for Soroban contract accounts (C...)
- [SEP-12](sep-12.md) — KYC API (often used alongside SEP-24)
- [SEP-38](sep-38.md) — Anchor RFQ API (quotes for exchange rates; replaces `/fee` endpoint)
- [SEP-06](sep-06.md) — Programmatic Deposit/Withdrawal (non-interactive alternative)

