# SEP-24: Interactive Deposit and Withdrawal

SEP-24 defines how to move money between traditional financial systems and the Stellar network. The anchor hosts a web interface where users complete the deposit or withdrawal process—the web UI handles KYC and payment method selection.

Use SEP-24 when:
- You want to deposit fiat currency (USD, EUR, etc.) to receive Stellar tokens
- You want to withdraw Stellar tokens back to a bank account or other payment method
- The anchor needs to collect information interactively from the user
- You're building a wallet that integrates with regulated on/off ramps

See the [SEP-24 specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md) for protocol details.

## Quick example

This example shows how to start a deposit flow. The anchor returns a URL where users complete the deposit process interactively:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;

// Create service from anchor's domain
$service = InteractiveService::fromDomain("testanchor.stellar.org");

// Start a deposit flow (requires JWT token from SEP-10 or SEP-45)
$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";

$response = $service->deposit($request);

// Open this URL in a browser or webview for the user
$interactiveUrl = $response->url;
$transactionId = $response->id;

echo "Open: $interactiveUrl\n";
echo "Transaction ID: $transactionId\n";
```

## Creating the interactive service

The `InteractiveService` class provides all SEP-24 operations. Create it from an anchor's domain (which discovers the transfer server URL from stellar.toml) or provide a direct URL.

**From an anchor's domain** (recommended):

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;

// Loads the TRANSFER_SERVER_SEP0024 URL from stellar.toml
$service = InteractiveService::fromDomain("testanchor.stellar.org");
```

**From a direct URL**:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;

$service = new InteractiveService("https://api.anchor.com/sep24");
```

**With a custom HTTP client** (useful for testing or custom configurations):

```php
<?php

use GuzzleHttp\Client;
use Soneso\StellarSDK\SEP\Interactive\InteractiveService;

$httpClient = new Client([
    'timeout' => 30,
    'headers' => ['User-Agent' => 'MyWallet/1.0']
]);

$service = InteractiveService::fromDomain("testanchor.stellar.org", $httpClient);
```

## Getting anchor information

Before starting a deposit or withdrawal, query the `/info` endpoint to see what assets the anchor supports and their fee structures:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

// Get anchor info (optionally specify language code like "de" for German)
$info = $service->info();

// Check supported deposit assets
$depositAssets = $info->depositAssets;
if ($depositAssets !== null) {
    foreach ($depositAssets as $code => $asset) {
        echo "Deposit: $code\n";
        echo "  Enabled: " . ($asset->enabled ? "Yes" : "No") . "\n";
        if ($asset->minAmount !== null) {
            echo "  Min: " . $asset->minAmount . "\n";
        }
        if ($asset->maxAmount !== null) {
            echo "  Max: " . $asset->maxAmount . "\n";
        }
        if ($asset->feeFixed !== null) {
            echo "  Fixed fee: " . $asset->feeFixed . "\n";
        }
        if ($asset->feePercent !== null) {
            echo "  Percent fee: " . $asset->feePercent . "%\n";
        }
        if ($asset->feeMinimum !== null) {
            echo "  Minimum fee: " . $asset->feeMinimum . "\n";
        }
    }
}

// Check supported withdrawal assets
$withdrawAssets = $info->withdrawAssets;

// Check feature support (claimable balances, account creation)
$features = $info->featureFlags;
if ($features !== null) {
    echo "Account creation supported: " . ($features->accountCreation ? "Yes" : "No") . "\n";
    echo "Claimable balances supported: " . ($features->claimableBalances ? "Yes" : "No") . "\n";
}

// Check if the deprecated fee endpoint is available
$feeInfo = $info->feeEndpointInfo;
if ($feeInfo !== null && $feeInfo->enabled) {
    echo "Fee endpoint is available\n";
    echo "Requires authentication: " . ($feeInfo->authenticationRequired ? "Yes" : "No") . "\n";
}
```

## Deposit flow

A deposit converts external funds (bank transfer, card, crypto from another chain) into Stellar tokens sent to your account. The user provides payment details through the anchor's web interface and completes KYC if required.

### Basic deposit

Start a deposit by specifying the asset you want to receive. The anchor returns a URL to open in a browser or webview:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24DepositRequest();
$request->jwt = $jwtToken; // From SEP-10 or SEP-45 authentication
$request->assetCode = "USD";

$response = $service->deposit($request);

// Show the interactive URL to your user
$url = $response->url;
$transactionId = $response->id;

// The user completes the deposit in their browser
// Then poll for status updates (see "Tracking Transactions" below)
```

### Deposit with amount and account options

You can specify an amount, destination account (if different from the authenticated account), and memo for the deposit:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->amount = 100.00;

// Receive tokens on a different account than the one used for authentication
$request->account = "GXXXXXXX...";
$request->memo = "12345";
$request->memoType = "id"; // "text", "id", or "hash"

// Language for the interactive UI (RFC 4646 format)
$request->lang = "en-US";

$response = $service->deposit($request);
```

### Deposit with asset issuer

When the anchor supports multiple issuers for the same asset code, specify which issuer you want:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->assetIssuer = "GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX";

$response = $service->deposit($request);
```

### Deposit with SEP-38 quote

For cross-asset deposits (deposit EUR to receive USDC), use a SEP-38 quote to lock in an exchange rate:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

// First, get a quote from SEP-38 (see SEP-38 documentation)
$quoteId = "quote-abc-123";

$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USDC";
$request->quoteId = $quoteId;
$request->sourceAsset = "iso4217:EUR"; // Depositing EUR, receiving USDC tokens
$request->amount = 100.00; // Must match the quote's sell_amount

$response = $service->deposit($request);
```

### Pre-filling KYC data

Provide KYC data upfront to pre-fill the anchor's form:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

// Provide personal KYC information
$personFields = new NaturalPersonKYCFields();
$personFields->firstName = "Jane";
$personFields->lastName = "Doe";
$personFields->emailAddress = "jane@example.com";
$personFields->mobileNumber = "+1234567890";

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $personFields;

$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->kycFields = $kycFields;

$response = $service->deposit($request);
// The anchor will pre-fill these fields in the interactive form
```

### Pre-filling organization KYC data

For business accounts, provide organization KYC fields:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$orgFields = new OrganizationKYCFields();
$orgFields->name = "Acme Corporation";
$orgFields->registeredAddress = "123 Business St, Suite 100";
$orgFields->email = "contact@acme.com";

$kycFields = new StandardKYCFields();
$kycFields->organizationKYCFields = $orgFields;

$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->kycFields = $kycFields;

$response = $service->deposit($request);
```

### Custom fields and files

For anchor-specific KYC requirements not covered by standard SEP-9 fields, use custom fields and files:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";

// Custom text fields
$request->customFields = [
    "employer_name" => "Tech Corp",
    "occupation" => "Software Engineer"
];

// Custom file uploads (binary content)
$request->customFiles = [
    "proof_of_income" => file_get_contents("/path/to/document.pdf")
];

$response = $service->deposit($request);
```

### Deposit with claimable balance support

If your account doesn't have a trustline for the asset, request that the anchor use claimable balances:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->claimableBalanceSupported = "true";

$response = $service->deposit($request);
// The anchor may create a claimable balance instead of a direct payment
// Check the transaction's claimableBalanceId field after completion
```

### Deposit with SEP-12 customer ID

If you have an existing customer ID from SEP-12 KYC, include it to link the transaction:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->customerId = "customer-id-from-sep12";

$response = $service->deposit($request);
```

### Deposit native XLM

To deposit and receive native XLM (lumens), use the special `native` asset code:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24DepositRequest();
$request->jwt = $jwtToken;
$request->assetCode = "native";
// Note: Do not set assetIssuer for native assets

$response = $service->deposit($request);
```

## Withdrawal flow

A withdrawal converts Stellar tokens into external funds sent to a bank account, card, or other destination. The user completes the anchor's interactive flow, then sends tokens to the anchor's Stellar account.

### Basic withdrawal

Start a withdrawal by specifying the asset you want to withdraw:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24WithdrawRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24WithdrawRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";

$response = $service->withdraw($request);

// Show the interactive URL to your user
$url = $response->url;
$transactionId = $response->id;

// After completing the form, poll for status to get withdrawal instructions
// When status is "pending_user_transfer_start", send the Stellar payment
```

### Withdrawal with options

Specify additional options like amount, source account, and language:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24WithdrawRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24WithdrawRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->amount = 500.00;

// Specify which Stellar account will send the withdrawal payment
$request->account = "GXXXXXXX...";

// Language for the interactive UI
$request->lang = "de"; // German

$response = $service->withdraw($request);
```

### Withdrawal with refund memo

Specify a memo for refunds if the withdrawal fails or is cancelled:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24WithdrawRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24WithdrawRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->amount = 500.00;

// Memo for refund payments
$request->refundMemo = "refund-123";
$request->refundMemoType = "text"; // "text", "id", or "hash"

$response = $service->withdraw($request);
```

### Withdrawal with SEP-38 quote (asset exchange)

For cross-asset withdrawals (send USDC, receive EUR in bank), use a SEP-38 quote:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24WithdrawRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

// First, get a quote from SEP-38 (see SEP-38 documentation)
$quoteId = "quote-xyz-789";

$request = new SEP24WithdrawRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USDC";
$request->quoteId = $quoteId;
$request->destinationAsset = "iso4217:EUR"; // Sending USDC, receiving EUR
$request->amount = 500.00; // Must match the quote's sell_amount

$response = $service->withdraw($request);
```

### Withdrawal with KYC data

Pre-fill KYC data for the withdrawal form:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24WithdrawRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$personFields = new NaturalPersonKYCFields();
$personFields->firstName = "John";
$personFields->lastName = "Smith";
$personFields->emailAddress = "john@example.com";

// Bank details go in FinancialAccountKYCFields
$bankFields = new FinancialAccountKYCFields();
$bankFields->bankAccountNumber = "123456789";
$bankFields->bankNumber = "987654321";
$personFields->financialAccountKYCFields = $bankFields;

$kycFields = new StandardKYCFields();
$kycFields->naturalPersonKYCFields = $personFields;

$request = new SEP24WithdrawRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->kycFields = $kycFields;

$response = $service->withdraw($request);
```

### Completing a withdrawal payment

After the user completes the interactive flow, poll the transaction endpoint to get payment instructions. When the status is `pending_user_transfer_start`, send the Stellar payment:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

// Poll for transaction status
$txRequest = new SEP24TransactionRequest();
$txRequest->jwt = $jwtToken;
$txRequest->id = $transactionId;

$txResponse = $service->transaction($txRequest);
$tx = $txResponse->transaction;

if ($tx->status === "pending_user_transfer_start") {
    // User needs to send the Stellar payment
    $withdrawAccount = $tx->withdrawAnchorAccount;
    $withdrawMemo = $tx->withdrawMemo;
    $withdrawMemoType = $tx->withdrawMemoType;
    $amount = $tx->amountIn;
    
    // Build and submit the payment transaction
    $sdk = StellarSDK::getTestNetInstance();
    $sourceKeyPair = KeyPair::fromSeed("SXXXXX...");
    $sourceAccountId = $sourceKeyPair->getAccountId();
    $sourceAccount = $sdk->requestAccount($sourceAccountId);
    
    $asset = Asset::createNonNativeAsset("USD", "ISSUER_ACCOUNT_ID");
    
    $paymentOp = (new PaymentOperationBuilder(
        $withdrawAccount,
        $asset,
        $amount
    ))->build();
    
    $memo = Memo::text($withdrawMemo); // Adjust based on withdrawMemoType
    
    $transaction = (new TransactionBuilder($sourceAccount))
        ->addOperation($paymentOp)
        ->addMemo($memo)
        ->build();
    
    $transaction->sign($sourceKeyPair, Network::testnet());
    $sdk->submitTransaction($transaction);
}
```

## Tracking transactions

After starting a deposit or withdrawal, poll the anchor for status updates. The SDK provides methods to query single transactions or list multiple transactions.

### Get a single transaction by ID

Query a specific transaction using its anchor-generated ID:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24TransactionRequest();
$request->jwt = $jwtToken;
$request->id = $transactionId; // From deposit/withdraw response

$response = $service->transaction($request);
$tx = $response->transaction;

echo "ID: " . $tx->id . "\n";
echo "Kind: " . $tx->kind . "\n";
echo "Status: " . $tx->status . "\n";
echo "Started: " . $tx->startedAt . "\n";

if ($tx->amountIn !== null) {
    echo "Amount in: " . $tx->amountIn . "\n";
}
if ($tx->amountOut !== null) {
    echo "Amount out: " . $tx->amountOut . "\n";
}
if ($tx->amountFee !== null) {
    echo "Fee: " . $tx->amountFee . "\n";
}
if ($tx->message !== null) {
    echo "Message: " . $tx->message . "\n";
}
if ($tx->moreInfoUrl !== null) {
    echo "More info: " . $tx->moreInfoUrl . "\n";
}
```

### Get transaction by Stellar transaction ID

Look up a transaction using its Stellar network transaction hash:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24TransactionRequest();
$request->jwt = $jwtToken;
$request->stellarTransactionId = "abc123def456..."; // Stellar transaction hash

$response = $service->transaction($request);
```

### Get transaction by external transaction ID

Look up a transaction using an external reference (e.g., bank transfer reference):

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24TransactionRequest();
$request->jwt = $jwtToken;
$request->externalTransactionId = "BANK-REF-123456";

$response = $service->transaction($request);
```

### Get transaction history

Query multiple transactions with filtering and pagination:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionsRequest;
use DateTime;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24TransactionsRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->limit = 10;
$request->kind = "deposit"; // or "withdrawal", or omit for both

// Only transactions after this date
$request->noOlderThan = new DateTime("2024-01-01");

// Language for localized responses
$request->lang = "en";

$response = $service->transactions($request);

foreach ($response->transactions as $tx) {
    echo $tx->id . ": " . $tx->kind . " - " . $tx->status;
    if ($tx->amountIn !== null) {
        echo " - " . $tx->amountIn;
    }
    echo "\n";
}
```

### Pagination with paging ID

For paginating through large transaction lists:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionsRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

// First page
$request = new SEP24TransactionsRequest();
$request->jwt = $jwtToken;
$request->assetCode = "USD";
$request->limit = 10;

$response = $service->transactions($request);
$transactions = $response->transactions;

// Get next page using the last transaction's ID
if (count($transactions) > 0) {
    $lastTx = end($transactions);
    
    $request->pagingId = $lastTx->id;
    $nextPage = $service->transactions($request);
}
```

## Transaction object details

The `SEP24Transaction` object contains detailed information about a transaction. Here are the key fields:

### Common fields (all transactions)

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique anchor-generated transaction ID |
| `kind` | string | `deposit` or `withdrawal` |
| `status` | string | Current status (see status table below) |
| `statusEta` | int | Estimated seconds until next status change |
| `kycVerified` | bool | Whether anchor verified user's KYC for this transaction |
| `moreInfoUrl` | string | URL with additional transaction details |
| `amountIn` | string | Amount received by anchor |
| `amountInAsset` | string | Asset received (SEP-38 format) |
| `amountOut` | string | Amount sent to user |
| `amountOutAsset` | string | Asset sent (SEP-38 format) |
| `amountFee` | string | Fee charged by anchor |
| `amountFeeAsset` | string | Asset for fee calculation |
| `quoteId` | string | SEP-38 quote ID if used |
| `startedAt` | string | Transaction start time (ISO 8601) |
| `completedAt` | string | Completion time (ISO 8601) |
| `updatedAt` | string | Last update time (ISO 8601) |
| `userActionRequiredBy` | string | Deadline for user action (ISO 8601) |
| `stellarTransactionId` | string | Stellar transaction hash |
| `externalTransactionId` | string | External system transaction ID |
| `message` | string | Human-readable status explanation |
| `from` | string | Source address/account |
| `to` | string | Destination address/account |

### Deposit-specific fields

| Field | Type | Description |
|-------|------|-------------|
| `depositMemo` | string | Memo used in the deposit payment |
| `depositMemoType` | string | Memo type (`text`, `id`, `hash`) |
| `claimableBalanceId` | string | Claimable balance ID if used |

### Withdrawal-specific fields

| Field | Type | Description |
|-------|------|-------------|
| `withdrawAnchorAccount` | string | Anchor's Stellar account to send payment to |
| `withdrawMemo` | string | Memo to include in the payment |
| `withdrawMemoType` | string | Memo type (`text`, `id`, `hash`) |

### Reading transaction fields

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24TransactionRequest();
$request->jwt = $jwtToken;
$request->id = $transactionId;

$response = $service->transaction($request);
$tx = $response->transaction;

// Check if KYC is verified
if ($tx->kycVerified === true) {
    echo "KYC verified for this transaction\n";
}

// Check for user action deadline
if ($tx->userActionRequiredBy !== null) {
    echo "Action required by: " . $tx->userActionRequiredBy . "\n";
}

// For deposits, check for claimable balance
if ($tx->kind === "deposit" && $tx->claimableBalanceId !== null) {
    echo "Claim balance: " . $tx->claimableBalanceId . "\n";
}

// For withdrawals in pending_user_transfer_start status
if ($tx->kind === "withdrawal" && $tx->status === "pending_user_transfer_start") {
    echo "Send " . $tx->amountIn . " to " . $tx->withdrawAnchorAccount . "\n";
    echo "With memo: " . $tx->withdrawMemo . " (" . $tx->withdrawMemoType . ")\n";
}
```

## Transaction statuses

The `status` field indicates the current state of the transaction:

| Status | Description |
|--------|-------------|
| `incomplete` | User hasn't completed the interactive flow yet |
| `pending_user_transfer_start` | Waiting for user to send funds to anchor |
| `pending_user_transfer_complete` | Stellar payment received, off-chain funds ready for pickup |
| `pending_external` | Waiting for external network confirmation (bank, crypto) |
| `pending_anchor` | Anchor is processing the transaction |
| `on_hold` | Transaction on hold pending compliance review |
| `pending_stellar` | Waiting for Stellar network transaction confirmation |
| `pending_trust` | User needs to add a trustline for the asset |
| `pending_user` | User action required (see message or more_info_url) |
| `completed` | Transaction finished successfully |
| `refunded` | Transaction was refunded (see refunds object) |
| `expired` | Transaction expired before completion |
| `no_market` | No market available for the asset exchange |
| `too_small` | Amount below minimum threshold |
| `too_large` | Amount above maximum threshold |
| `error` | Transaction failed due to an error |

## Handling refunds

When a transaction is refunded, check the `refunds` object for details:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

$request = new SEP24TransactionRequest();
$request->jwt = $jwtToken;
$request->id = $transactionId;

$response = $service->transaction($request);
$tx = $response->transaction;

if ($tx->status === "refunded" && $tx->refunds !== null) {
    $refund = $tx->refunds;
    
    echo "Total refunded: " . $refund->amountRefunded . "\n";
    echo "Refund fees: " . $refund->amountFee . "\n";
    
    // Individual refund payments
    foreach ($refund->payments as $payment) {
        echo "Payment ID: " . $payment->id . "\n";
        echo "Type: " . $payment->idType . "\n"; // "stellar" or "external"
        if ($payment->amount !== null) {
            echo "Amount: " . $payment->amount . "\n";
        }
        if ($payment->fee !== null) {
            echo "Fee: " . $payment->fee . "\n";
        }
    }
}
```

## Error handling

The SDK throws specific exceptions for different error scenarios:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24AuthenticationRequiredException;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionNotFoundException;
use Soneso\StellarSDK\SEP\Interactive\RequestErrorException;
use GuzzleHttp\Exception\GuzzleException;
use Exception;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

try {
    $request = new SEP24DepositRequest();
    $request->jwt = $jwtToken;
    $request->assetCode = "USD";
    
    $response = $service->deposit($request);
    echo "Interactive URL: " . $response->url . "\n";
    
} catch (SEP24AuthenticationRequiredException $e) {
    // HTTP 403: JWT token is invalid, expired, or missing
    // Re-authenticate with SEP-10 or SEP-45 and retry
    echo "Authentication required: " . $e->getMessage() . "\n";
    
} catch (RequestErrorException $e) {
    // HTTP 400 or other error: Invalid parameters, unsupported asset, etc.
    // Check the error message for details from the anchor
    echo "Request error: " . $e->getMessage() . "\n";
    echo "HTTP code: " . $e->getCode() . "\n";
    
} catch (GuzzleException $e) {
    // Network or HTTP connection error
    echo "Network error: " . $e->getMessage() . "\n";
    
} catch (Exception $e) {
    // Other unexpected errors
    echo "Unexpected error: " . $e->getMessage() . "\n";
}

// For transaction queries, handle the not-found case
try {
    $txRequest = new SEP24TransactionRequest();
    $txRequest->jwt = $jwtToken;
    $txRequest->id = "invalid-or-unknown-id";
    
    $response = $service->transaction($txRequest);
    
} catch (SEP24TransactionNotFoundException $e) {
    // HTTP 404: Transaction doesn't exist or doesn't belong to this user
    echo "Transaction not found\n";
    
} catch (SEP24AuthenticationRequiredException $e) {
    echo "Need to re-authenticate\n";
    
} catch (RequestErrorException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

## Fee information (deprecated)

The `/fee` endpoint is deprecated in favor of SEP-38. For anchors that still support it:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24FeeRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

// Check if fee endpoint is available
$info = $service->info();

if ($info->feeEndpointInfo !== null && $info->feeEndpointInfo->enabled) {
    $feeRequest = new SEP24FeeRequest(
        operation: "deposit",
        assetCode: "USD",
        amount: 1000.00,
        jwt: $jwtToken
    );
    
    // Optional: specify type (e.g., "SEPA", "bank_account")
    $feeRequest->type = "bank_account";
    
    $feeResponse = $service->fee($feeRequest);
    echo "Fee for \$1000 deposit: \$" . $feeResponse->fee . "\n";
}
```

> **Note:** New integrations should use [SEP-38](sep-38.md) `/price` endpoint for fee and exchange rate information.

## Polling strategy

When monitoring transactions, use exponential backoff to avoid hammering the server:

```php
<?php

use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;

$service = InteractiveService::fromDomain("testanchor.stellar.org");

function pollTransaction(
    InteractiveService $service,
    string $jwt,
    string $transactionId,
    array $terminalStatuses = ['completed', 'refunded', 'expired', 'error']
): ?object {
    $request = new SEP24TransactionRequest();
    $request->jwt = $jwt;
    $request->id = $transactionId;
    
    $attempts = 0;
    $maxAttempts = 60;
    $baseDelay = 2; // seconds
    
    while ($attempts < $maxAttempts) {
        $response = $service->transaction($request);
        $tx = $response->transaction;
        
        echo "Status: " . $tx->status . "\n";
        
        if (in_array($tx->status, $terminalStatuses)) {
            return $tx;
        }
        
        // Use status_eta if provided, otherwise exponential backoff
        if ($tx->statusEta !== null && $tx->statusEta > 0) {
            $delay = min($tx->statusEta, 60); // Cap at 60 seconds
        } else {
            $delay = min($baseDelay * pow(2, $attempts), 60);
        }
        
        sleep($delay);
        $attempts++;
    }
    
    return null; // Timeout
}

$completedTx = pollTransaction($service, $jwtToken, $transactionId);
if ($completedTx !== null) {
    echo "Transaction completed with status: " . $completedTx->status . "\n";
}
```

## Related specifications

- [SEP-1](sep-01.md) - stellar.toml (where `TRANSFER_SERVER_SEP0024` is published)
- [SEP-10](sep-10.md) - Web Authentication for traditional accounts (G... addresses)
- [SEP-45](sep-45.md) - Web Authentication for Contract Accounts (C... addresses)
- [SEP-12](sep-12.md) - KYC API (often used alongside SEP-24)
- [SEP-38](sep-38.md) - Anchor RFQ API (quotes for exchange rates)
- [SEP-6](sep-06.md) - Programmatic Deposit/Withdrawal (non-interactive alternative)

## Further reading

- [SDK test cases](https://github.com/Soneso/stellar-php-sdk/tree/main/Soneso/StellarSDKTests/Unit/SEP/Interactive) - examples covering deposits, withdrawals, transaction queries, and error handling

---

[Back to SEP Overview](README.md)
