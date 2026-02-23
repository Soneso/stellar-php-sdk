# SEP-31: Cross-Border Payments

**Purpose:** Send payments through a Receiving Anchor to a recipient who receives funds off-chain (bank account, mobile wallet, etc.). You are the Sending Anchor; the PHP SDK implements the sending side only.
**Prerequisites:** Requires JWT from SEP-10 (see [sep-10.md](sep-10.md)). Often used with SEP-12 (KYC) and SEP-38 (quotes).
**SDK Namespace:** `Soneso\StellarSDK\SEP\CrossBorderPayments`

## Table of Contents

- [How It Works](#how-it-works)
- [Creating the Service](#creating-the-service)
- [GET /info — Discover Assets and KYC Requirements](#get-info)
- [POST /transactions — Initiate a Payment](#post-transactions)
- [GET /transactions/:id — Track Status](#get-transactionsid)
- [PUT /transactions/:id/callback — Register Callback](#put-callback)
- [PATCH /transactions/:id — Update Fields (Deprecated)](#patch-transactions)
- [Complete Payment Flow](#complete-payment-flow)
- [Transaction Statuses](#transaction-statuses)
- [Response Objects](#response-objects)
- [Exception Reference](#exception-reference)
- [Common Pitfalls](#common-pitfalls)

---

## How It Works

1. **Authenticate** — Get JWT via SEP-10 using the Sending Anchor's pre-authorized Stellar account
2. **Discover** — Query `GET /info` to learn supported assets, limits, fees, and required KYC types
3. **KYC** — Register sender and receiver via SEP-12 (if required by the Receiving Anchor)
4. **Quote** (optional) — Get a locked-in exchange rate via SEP-38
5. **Initiate** — `POST /transactions` to the Receiving Anchor; receive transaction ID and Stellar payment instructions
6. **Pay** — Send the Stellar payment to the anchor's account with the exact memo provided
7. **Track** — Poll `GET /transactions/:id` (or register a callback) until `completed` or handle errors

---

## Creating the Service

`CrossBorderPaymentsService` handles all SEP-31 operations.

### From a domain (recommended)

Loads `DIRECT_PAYMENT_SERVER` from the anchor's `stellar.toml` automatically:

```php
<?php declare(strict_types=1);

use Exception;
use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;

// Reads DIRECT_PAYMENT_SERVER from https://receivinganchor.com/.well-known/stellar.toml
try {
    $service = CrossBorderPaymentsService::fromDomain('receivinganchor.com');
} catch (Exception $e) {
    // Thrown if stellar.toml is unreachable or DIRECT_PAYMENT_SERVER is absent
    echo 'Cannot reach anchor: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
```

Signature:
```
CrossBorderPaymentsService::fromDomain(string $domain, ?Client $httpClient = null): CrossBorderPaymentsService
```

### From a direct URL

When you already know the server endpoint:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;

$service = new CrossBorderPaymentsService('https://api.receivinganchor.com/sep31');
```

Constructor signature:
```
new CrossBorderPaymentsService(string $serviceAddress, ?Client $httpClient = null)
```

---

## GET /info

Query the anchor to discover supported assets, limits, fees, and required SEP-12 KYC types.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31BadRequestException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31UnknownResponseException;
use GuzzleHttp\Exception\GuzzleException;

$service = CrossBorderPaymentsService::fromDomain('receivinganchor.com');

try {
    $info = $service->info($jwtToken);
} catch (SEP31BadRequestException $e) {
    echo 'Bad request: ' . $e->getMessage() . PHP_EOL;
    exit(1);
} catch (SEP31UnknownResponseException $e) {
    echo 'Unexpected response (HTTP ' . $e->getCode() . '): ' . $e->getMessage() . PHP_EOL;
    exit(1);
} catch (GuzzleException $e) {
    echo 'Network error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

// $info->receiveAssets is array<string, SEP31ReceiveAssetInfo> keyed by asset code
foreach ($info->receiveAssets as $assetCode => $assetInfo) {
    echo "Asset: $assetCode\n";
    echo "  Min amount:       " . ($assetInfo->minAmount ?? 'No limit') . "\n";
    echo "  Max amount:       " . ($assetInfo->maxAmount ?? 'No limit') . "\n";
    echo "  Fixed fee:        " . ($assetInfo->feeFixed ?? 'N/A') . "\n";
    echo "  Percent fee:      " . ($assetInfo->feePercent ?? 'N/A') . "%\n";
    echo "  Quotes supported: " . ($assetInfo->quotesSupported ? 'Yes' : 'No') . "\n";
    echo "  Quotes required:  " . ($assetInfo->quotesRequired ? 'Yes' : 'No') . "\n";

    // Funding methods (array<string>|null) — e.g. ["bank_account", "cash"]
    if ($assetInfo->fundingMethods !== null) {
        echo "  Funding methods:  " . implode(', ', $assetInfo->fundingMethods) . "\n";
    }

    // SEP-12 KYC types required for senders and receivers
    // sep12Info->senderTypes  : array<string, string>  (type key => description)
    // sep12Info->receiverTypes: array<string, string>  (type key => description)
    $sep12 = $assetInfo->sep12Info;
    foreach ($sep12->senderTypes as $type => $description) {
        echo "  Sender type '$type': $description\n";
    }
    foreach ($sep12->receiverTypes as $type => $description) {
        echo "  Receiver type '$type': $description\n";
    }
}
```

Method signature:
```
info(string $jwt, ?string $lang = null): SEP31InfoResponse
```

`$lang` is an optional ISO 639-1 language code (defaults to `en`) for human-readable error messages.

---

## POST /transactions

Initiate a payment. Returns a transaction ID and Stellar payment instructions (account + memo).

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31PostTransactionsRequest;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31CustomerInfoNeededException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31TransactionInfoNeededException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31BadRequestException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31UnknownResponseException;
use GuzzleHttp\Exception\GuzzleException;

$service = CrossBorderPaymentsService::fromDomain('receivinganchor.com');

$request = new SEP31PostTransactionsRequest(
    amount: 100.00,                                                          // required: float
    assetCode: 'USDC',                                                       // required: string
    assetIssuer: 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', // optional: string|null
    destinationAsset: 'iso4217:BRL',                                         // optional: SEP-38 format
    quoteId: $quoteId,                                                       // optional: from SEP-38
    senderId: $senderId,                                                     // optional: from SEP-12
    receiverId: $receiverId,                                                 // optional: from SEP-12
    refundMemo: 'refund-12345',                                              // optional: string|null
    refundMemoType: 'text',                                                  // optional: 'id'|'text'|'hash'
    fundingMethod: 'bank_account',                                           // optional: must match /info value
);
// Note: $fields constructor param exists for legacy compatibility but is deprecated

try {
    $response = $service->postTransactions($request, $jwtToken);
} catch (SEP31CustomerInfoNeededException $e) {
    // KYC data missing — register via SEP-12, then retry
    // $e->type is the SEP-12 customer type to register (string|null)
    echo 'KYC needed. Register via SEP-12 with type: ' . ($e->type ?? 'N/A') . PHP_EOL;
    exit(1);
} catch (SEP31TransactionInfoNeededException $e) {
    // Deprecated — anchor requires inline fields (legacy flow)
    // $e->fields is array|null of required fields
    echo 'Transaction fields needed (deprecated): ' . print_r($e->fields, true) . PHP_EOL;
    exit(1);
} catch (SEP31BadRequestException $e) {
    echo 'Bad request: ' . $e->getMessage() . PHP_EOL;
    exit(1);
} catch (SEP31UnknownResponseException $e) {
    echo 'Unexpected response (HTTP ' . $e->getCode() . '): ' . $e->getMessage() . PHP_EOL;
    exit(1);
} catch (GuzzleException $e) {
    echo 'Network error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

// SEP31PostTransactionsResponse fields:
$transactionId  = $response->id;                // string — always present
$stellarAccount = $response->stellarAccountId;  // string|null — may be null initially
$memo           = $response->stellarMemo;       // string|null
$memoType       = $response->stellarMemoType;   // string|null — 'text', 'hash', or 'id'

echo "Transaction ID: $transactionId\n";
if ($stellarAccount !== null) {
    echo "Send to: $stellarAccount with memo ($memoType): $memo\n";
} else {
    // Poll GET /transactions/:id until status is pending_sender to get payment instructions
    echo "Payment instructions not yet available — poll for status\n";
}
```

Method signature:
```
postTransactions(SEP31PostTransactionsRequest $request, string $jwt): SEP31PostTransactionsResponse
```

Accepts `200 OK` or `201 Created` from the server.

### SEP31PostTransactionsRequest constructor

```
new SEP31PostTransactionsRequest(
    float        $amount,
    string       $assetCode,
    ?string      $assetIssuer      = null,
    ?string      $destinationAsset = null,
    ?string      $quoteId          = null,
    ?string      $senderId         = null,
    ?string      $receiverId       = null,
    ?array       $fields           = null,  // DEPRECATED — use SEP-12 instead
    ?string      $lang             = null,
    ?string      $refundMemo       = null,
    ?string      $refundMemoType   = null,
    ?string      $fundingMethod    = null
)
```

All parameters are named and positional in this order. Use named arguments when omitting optional ones.

---

## GET /transactions/:id

Fetch the current state of a transaction.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31TransactionNotFoundException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31BadRequestException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31UnknownResponseException;
use GuzzleHttp\Exception\GuzzleException;

$service = CrossBorderPaymentsService::fromDomain('receivinganchor.com');

try {
    $tx = $service->getTransaction($transactionId, $jwtToken);
} catch (SEP31TransactionNotFoundException $e) {
    echo 'Transaction not found' . PHP_EOL;
    exit(1);
} catch (SEP31BadRequestException $e) {
    echo 'Bad request: ' . $e->getMessage() . PHP_EOL;
    exit(1);
} catch (SEP31UnknownResponseException $e) {
    echo 'Unexpected response (HTTP ' . $e->getCode() . '): ' . $e->getMessage() . PHP_EOL;
    exit(1);
} catch (GuzzleException $e) {
    echo 'Network error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Core status fields
echo 'Status:         ' . $tx->status . PHP_EOL;
echo 'Status ETA:     ' . ($tx->statusEta ?? 'N/A') . ' seconds' . PHP_EOL;
echo 'Status message: ' . ($tx->statusMessage ?? 'N/A') . PHP_EOL;

// Amount fields (all string|null)
echo 'Amount in:      ' . ($tx->amountIn ?? 'N/A') . PHP_EOL;
echo 'Amount in asset:' . ($tx->amountInAsset ?? 'N/A') . PHP_EOL;
echo 'Amount out:     ' . ($tx->amountOut ?? 'N/A') . PHP_EOL;
echo 'Amount out asset:' . ($tx->amountOutAsset ?? 'N/A') . PHP_EOL;
echo 'Amount fee:     ' . ($tx->amountFee ?? 'N/A') . PHP_EOL;       // deprecated field
echo 'Amount fee asset:' . ($tx->amountFeeAsset ?? 'N/A') . PHP_EOL; // deprecated field

// Timestamps (all string|null — UTC ISO 8601)
echo 'Started at:     ' . ($tx->startedAt ?? 'N/A') . PHP_EOL;
echo 'Updated at:     ' . ($tx->updatedAt ?? 'N/A') . PHP_EOL;
echo 'Completed at:   ' . ($tx->completedAt ?? 'N/A') . PHP_EOL;

// Payment identifiers (string|null)
echo 'Stellar tx ID:  ' . ($tx->stellarTransactionId ?? 'N/A') . PHP_EOL;
echo 'External tx ID: ' . ($tx->externalTransactionId ?? 'N/A') . PHP_EOL;

// Payment destination (may be populated after initial POST response)
echo 'Stellar account:' . ($tx->stellarAccountId ?? 'N/A') . PHP_EOL;
echo 'Stellar memo:   ' . ($tx->stellarMemo ?? 'N/A') . PHP_EOL;
echo 'Memo type:      ' . ($tx->stellarMemoType ?? 'N/A') . PHP_EOL;

// Quote ID if SEP-38 was used
echo 'Quote ID:       ' . ($tx->quoteId ?? 'N/A') . PHP_EOL;
```

Method signature:
```
getTransaction(string $id, string $jwt): SEP31TransactionResponse
```

### Polling for payment instructions

If `stellarAccountId` is `null` in the POST response, the anchor is still preparing. Poll until `status === 'pending_sender'`:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;

$service = CrossBorderPaymentsService::fromDomain('receivinganchor.com');

$stellarAccount = null;
$memo = null;
$memoType = null;
$waitTime = 5;
$maxWaitTime = 60;

while ($stellarAccount === null) {
    sleep($waitTime);

    $tx = $service->getTransaction($transactionId, $jwtToken);

    if ($tx->status === 'pending_sender') {
        $stellarAccount = $tx->stellarAccountId;
        $memo = $tx->stellarMemo;
        $memoType = $tx->stellarMemoType;
        break;
    } elseif ($tx->status === 'error') {
        throw new Exception('Transaction failed: ' . ($tx->statusMessage ?? 'unknown error'));
    }

    // Respect status_eta if provided, otherwise use exponential backoff
    if ($tx->statusEta !== null) {
        $waitTime = max(5, $tx->statusEta);
    } else {
        $waitTime = min($waitTime * 2, $maxWaitTime);
    }
}
```

### Handling all status values

```php
switch ($tx->status) {
    case 'pending_sender':
        // Awaiting Stellar payment from you; send now using $tx->stellarAccountId + $tx->stellarMemo
        break;
    case 'pending_stellar':
        // Stellar payment received, confirming on the network
        break;
    case 'pending_receiver':
        // Being processed by Receiving Anchor — delivering to recipient
        break;
    case 'pending_external':
        // Submitted to the external payment network, awaiting confirmation
        break;
    case 'pending_customer_info_update':
        // Anchor needs more/corrected KYC data — check SEP-12 GET /customer for required fields
        // $tx->requiredInfoMessage has a human-readable explanation (string|null)
        break;
    case 'pending_transaction_info_update':
        // Deprecated — anchor needs inline transaction fields
        // $tx->requiredInfoMessage describes what is needed
        // $tx->requiredInfoUpdates is array|null of required fields (same format as GET /info)
        break;
    case 'completed':
        // Funds successfully delivered to Receiving Client
        break;
    case 'refunded':
        // Payment was refunded — see $tx->refunds for details
        break;
    case 'expired':
        // Transaction abandoned or SEP-38 quote expired before payment was sent
        break;
    case 'error':
        // Catch-all error — check $tx->statusMessage for details
        break;
}
```

---

## PUT /callback

Register a URL for status-change notifications so you don't have to poll.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31TransactionCallbackNotSupportedException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31BadRequestException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31UnknownResponseException;
use GuzzleHttp\Exception\GuzzleException;

$service = CrossBorderPaymentsService::fromDomain('receivinganchor.com');

try {
    // Returns void on success (HTTP 204)
    $service->putTransactionCallback(
        $transactionId,
        'https://myanchor.com/callbacks/sep31', // must use HTTPS
        $jwtToken
    );
    echo "Callback registered\n";
} catch (SEP31TransactionCallbackNotSupportedException $e) {
    // HTTP 404 — anchor does not support callbacks; fall back to polling
    echo "Callbacks not supported — use polling instead\n";
} catch (SEP31BadRequestException $e) {
    echo 'Bad request: ' . $e->getMessage() . PHP_EOL;
} catch (SEP31UnknownResponseException $e) {
    echo 'Unexpected response (HTTP ' . $e->getCode() . '): ' . $e->getMessage() . PHP_EOL;
} catch (GuzzleException $e) {
    echo 'Network error: ' . $e->getMessage() . PHP_EOL;
}
```

Method signature:
```
putTransactionCallback(string $id, string $callbackUrl, string $jwt): void
```

### Verifying callback signatures

The Receiving Anchor signs each callback POST with their `SIGNING_KEY`. Verify it:

```php
// In your callback endpoint handler:
$signatureHeader = $_SERVER['HTTP_SIGNATURE'] ?? $_SERVER['HTTP_X_STELLAR_SIGNATURE'] ?? '';

// Format: t=<unix_timestamp>, s=<base64_signature>
preg_match('/t=(\d+),\s*s=(.+)/', $signatureHeader, $matches);
$timestamp = $matches[1] ?? null;
$signature  = base64_decode($matches[2] ?? '');

// Reject stale requests (older than 5 minutes)
if ($timestamp === null || (time() - (int)$timestamp) > 300) {
    http_response_code(400);
    exit('Request too old or missing timestamp');
}

// Signed payload: timestamp + "." + request_hostname + "." + raw_request_body
$body    = file_get_contents('php://input');
$payload = $timestamp . '.' . $_SERVER['HTTP_HOST'] . '.' . $body;

// Verify using the anchor's SIGNING_KEY from their stellar.toml
// Use sodium_crypto_sign_verify_detached() or Soneso\StellarSDK\Crypto\KeyPair::verifySignature()

http_response_code(204); // Acknowledge receipt — no content
```

---

## PATCH /transactions

**Deprecated.** Sends updated transaction fields to the anchor when status is `pending_transaction_info_update`. Use SEP-12 `PUT /customer` for all new implementations.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31TransactionNotFoundException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31BadRequestException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31UnknownResponseException;
use GuzzleHttp\Exception\GuzzleException;

$service = CrossBorderPaymentsService::fromDomain('receivinganchor.com');

// DEPRECATED: only use for legacy anchors that require it
// The $fields array is sent as {"fields": <your array>} in the request body
$fields = [
    'transaction' => [
        'receiver_bank_account'  => '12345678901234',
        'receiver_routing_number' => '021000021',
    ],
];

try {
    // Returns void on success (HTTP 200)
    $service->patchTransaction($transactionId, $fields, $jwtToken);
} catch (SEP31TransactionNotFoundException $e) {
    echo 'Transaction not found' . PHP_EOL;
} catch (SEP31BadRequestException $e) {
    echo 'Bad request (update not requested or invalid fields): ' . $e->getMessage() . PHP_EOL;
} catch (SEP31UnknownResponseException $e) {
    echo 'Unexpected response (HTTP ' . $e->getCode() . '): ' . $e->getMessage() . PHP_EOL;
} catch (GuzzleException $e) {
    echo 'Network error: ' . $e->getMessage() . PHP_EOL;
}
```

Method signature:
```
patchTransaction(string $id, array $fields, string $jwt): void
```

---

## Complete Payment Flow

End-to-end example combining SEP-10, SEP-12, and SEP-31:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;
use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31PostTransactionsRequest;

$domain = 'receivinganchor.com';

// Step 1: Authenticate via SEP-10
$senderKeyPair = KeyPair::fromSeed(getenv('SENDING_ANCHOR_SEED'));
$webAuth = WebAuth::fromDomain($domain, Network::testnet());
$jwtToken = $webAuth->jwtToken($senderKeyPair->getAccountId(), [$senderKeyPair]);

// Step 2: Query /info to learn KYC types
$service = CrossBorderPaymentsService::fromDomain($domain);
$info = $service->info($jwtToken);
$usdcInfo = $info->receiveAssets['USDC'];

// Step 3: Register sender via SEP-12
$kycService = KYCService::fromDomain($domain);

$senderFields = new NaturalPersonKYCFields();
$senderFields->firstName = 'Jane';
$senderFields->lastName  = 'Sender';
$senderFields->emailAddress = 'jane@example.com';

$senderRequest = new PutCustomerInfoRequest();
$senderRequest->jwt = $jwtToken;
$senderRequest->KYCFields = new StandardKYCFields();
$senderRequest->KYCFields->naturalPersonKYCFields = $senderFields;
// Use a type key from $usdcInfo->sep12Info->senderTypes
$senderRequest->type = array_key_first($usdcInfo->sep12Info->senderTypes);

$senderId = $kycService->putCustomerInfo($senderRequest)->getId();

// Step 4: Register receiver via SEP-12
$receiverFields = new NaturalPersonKYCFields();
$receiverFields->firstName = 'Bob';
$receiverFields->lastName  = 'Receiver';

$receiverRequest = new PutCustomerInfoRequest();
$receiverRequest->jwt = $jwtToken;
$receiverRequest->KYCFields = new StandardKYCFields();
$receiverRequest->KYCFields->naturalPersonKYCFields = $receiverFields;
$receiverRequest->type = array_key_first($usdcInfo->sep12Info->receiverTypes);
$receiverRequest->customFields = [
    'bank_account_number' => '1234567890',
    'bank_routing_number' => '021000021',
];

$receiverId = $kycService->putCustomerInfo($receiverRequest)->getId();

// Step 5: Initiate the transaction
$postRequest = new SEP31PostTransactionsRequest(
    amount: 100.00,
    assetCode: 'USDC',
    assetIssuer: 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    destinationAsset: 'iso4217:BRL',
    senderId: $senderId,
    receiverId: $receiverId,
    refundMemo: 'refund-' . uniqid(),
    refundMemoType: 'text',
);

$postResponse = $service->postTransactions($postRequest, $jwtToken);
$transactionId  = $postResponse->id;
$stellarAccount = $postResponse->stellarAccountId;
$memo           = $postResponse->stellarMemo;
$memoType       = $postResponse->stellarMemoType;

// Step 6: Poll if payment instructions not immediately available
if ($stellarAccount === null) {
    $waitTime = 5;
    do {
        sleep($waitTime);
        $tx = $service->getTransaction($transactionId, $jwtToken);
        if ($tx->status === 'pending_sender') {
            $stellarAccount = $tx->stellarAccountId;
            $memo           = $tx->stellarMemo;
            $memoType       = $tx->stellarMemoType;
        } elseif ($tx->status === 'error') {
            throw new Exception('Transaction error: ' . $tx->statusMessage);
        }
        $waitTime = min($waitTime * 2, 60);
    } while ($stellarAccount === null);
}

// Step 7: Send the Stellar payment
$sdk = StellarSDK::getTestNetInstance();
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

$asset = Asset::createNonNativeAsset(
    'USDC',
    'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN'
);

$paymentOp = (new PaymentOperationBuilder($stellarAccount, $asset, '100'))->build();

// IMPORTANT: memo type from anchor determines which Memo factory to use
$memoObj = match ($memoType) {
    'id'   => Memo::id((int) $memo),
    'text' => Memo::text($memo),
    'hash' => Memo::hash($memo),
    default => throw new Exception("Unknown memo type: $memoType"),
};

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($paymentOp)
    ->addMemo($memoObj)
    ->build();

$transaction->sign($senderKeyPair, Network::testnet());
$submitResponse = $sdk->submitTransaction($transaction);

if ($submitResponse->isSuccessful()) {
    echo 'Payment submitted: ' . $submitResponse->getHash() . PHP_EOL;
} else {
    $codes = $submitResponse->getExtras()->getResultCodes();
    throw new Exception('Payment failed: ' . $codes->getTransactionResultCode());
}

// Step 8: Track until completed
do {
    sleep(10);
    $tx = $service->getTransaction($transactionId, $jwtToken);
    echo 'Status: ' . $tx->status . PHP_EOL;
} while (!in_array($tx->status, ['completed', 'refunded', 'expired', 'error']));
```

---

## Transaction Statuses

| Status | Description |
|--------|-------------|
| `pending_sender` | Awaiting Stellar payment from Sending Anchor |
| `pending_stellar` | Stellar payment received, confirming on network |
| `pending_customer_info_update` | Anchor needs more/corrected KYC — query SEP-12 for required fields |
| `pending_transaction_info_update` | Anchor needs updated inline fields (deprecated — use SEP-12) |
| `pending_receiver` | Being processed by Receiving Anchor |
| `pending_external` | Submitted to external payment network, awaiting confirmation |
| `completed` | Funds delivered to Receiving Client |
| `refunded` | Funds refunded to Sending Anchor (see `$tx->refunds`) |
| `expired` | Transaction abandoned or SEP-38 quote expired before payment |
| `error` | Error occurred — check `$tx->statusMessage` |

---

## Response Objects

### SEP31InfoResponse

```
$info->receiveAssets  // array<string, SEP31ReceiveAssetInfo>  keyed by asset code
```

### SEP31ReceiveAssetInfo

```
$assetInfo->sep12Info          // SEP12TypesInfo  — KYC type definitions (always set)
$assetInfo->minAmount          // float|null      — minimum transaction amount
$assetInfo->maxAmount          // float|null      — maximum transaction amount
$assetInfo->feeFixed           // float|null      — fixed fee in asset units
$assetInfo->feePercent         // float|null      — percentage fee in percentage points
$assetInfo->quotesSupported    // bool|null       — true if SEP-38 quotes can be used
$assetInfo->quotesRequired     // bool|null       — true if SEP-38 quotes are mandatory
$assetInfo->fundingMethods     // array<string>|null  — supported payment rails
$assetInfo->senderSep12Type    // string|null     — DEPRECATED: single type string
$assetInfo->receiverSep12Type  // string|null     — DEPRECATED: single type string
$assetInfo->fields             // array|null      — DEPRECATED: inline fields
```

### SEP12TypesInfo

```
$sep12->senderTypes    // array<string, string>  — type key => human-readable description
$sep12->receiverTypes  // array<string, string>  — type key => human-readable description
```

### SEP31PostTransactionsResponse

```
$response->id                // string       — transaction ID (always present)
$response->stellarAccountId  // string|null  — Stellar account to send payment to
$response->stellarMemoType   // string|null  — 'text', 'hash', or 'id'
$response->stellarMemo       // string|null  — memo value to attach to payment
```

### SEP31TransactionResponse

```
$tx->id                      // string       — transaction ID
$tx->status                  // string       — see Transaction Statuses table
$tx->statusEta               // int|null     — estimated seconds until next status change
$tx->statusMessage           // string|null  — human-readable status description
$tx->amountIn                // string|null  — amount received by Receiving Anchor
$tx->amountInAsset           // string|null  — SEP-38 format asset (present when quote or destination_asset used)
$tx->amountOut               // string|null  — amount delivered to Receiving Client
$tx->amountOutAsset          // string|null  — SEP-38 format asset (present when quote or destination_asset used)
$tx->amountFee               // string|null  — DEPRECATED: fee amount
$tx->amountFeeAsset          // string|null  — DEPRECATED: fee asset
$tx->feeDetails              // SEP31FeeDetails|null  — structured fee breakdown (preferred over amountFee)
$tx->quoteId                 // string|null  — SEP-38 quote ID if used
$tx->stellarAccountId        // string|null  — Receiving Anchor's Stellar account
$tx->stellarMemoType         // string|null  — 'text', 'hash', or 'id'
$tx->stellarMemo             // string|null  — memo for Stellar payment
$tx->startedAt               // string|null  — UTC ISO 8601 creation timestamp
$tx->updatedAt               // string|null  — UTC ISO 8601 last status change timestamp
$tx->completedAt             // string|null  — UTC ISO 8601 completion timestamp
$tx->stellarTransactionId    // string|null  — Stellar network transaction hash
$tx->externalTransactionId   // string|null  — external network transaction ID
$tx->refunded                // bool|null    — DEPRECATED: use $tx->refunds instead
$tx->refunds                 // SEP31Refunds|null  — refund details when status is 'refunded'
$tx->requiredInfoMessage     // string|null  — human-readable message for info-update statuses
$tx->requiredInfoUpdates     // array|null   — required fields for pending_transaction_info_update
```

### SEP31FeeDetails

```
$feeDetails->total    // string               — total fee amount
$feeDetails->asset    // string               — SEP-38 format asset identifier
$feeDetails->details  // array<SEP31FeeDetailsDetails>|null  — individual fee components
```

### SEP31FeeDetailsDetails

```
$detail->name         // string       — fee component name (e.g. "ACH fee", "Service fee")
$detail->amount       // string       — fee component amount
$detail->description  // string|null  — optional description
```

Usage example:

```php
if ($tx->feeDetails !== null) {
    echo 'Total fee: ' . $tx->feeDetails->total . ' ' . $tx->feeDetails->asset . PHP_EOL;
    foreach ($tx->feeDetails->details ?? [] as $detail) {
        echo '  ' . $detail->name . ': ' . $detail->amount;
        if ($detail->description !== null) {
            echo ' (' . $detail->description . ')';
        }
        echo PHP_EOL;
    }
}
```

### SEP31Refunds

```
$refunds->amountRefunded  // string                    — total refunded (units of amount_in_asset)
$refunds->amountFee       // string                    — total refund processing fees
$refunds->payments        // array<SEP31RefundPayment>  — individual refund payments
```

### SEP31RefundPayment

```
$payment->id      // string  — Stellar transaction hash of the refund
$payment->amount  // string  — amount refunded in this payment
$payment->fee     // string  — fee deducted from this refund payment
```

Usage example:

```php
if ($tx->status === 'refunded' && $tx->refunds !== null) {
    echo 'Total refunded: ' . $tx->refunds->amountRefunded . PHP_EOL;
    echo 'Refund fees:    ' . $tx->refunds->amountFee . PHP_EOL;
    foreach ($tx->refunds->payments as $payment) {
        echo '  Stellar TX: ' . $payment->id . PHP_EOL;
        echo '  Amount: ' . $payment->amount . ', Fee: ' . $payment->fee . PHP_EOL;
    }
}
```

---

## Exception Reference

| Exception | HTTP Status | When Thrown | Key Fields |
|-----------|-------------|-------------|------------|
| `SEP31CustomerInfoNeededException` | 400 | `postTransactions()` — KYC data missing or insufficient | `$e->type` (string\|null) — SEP-12 customer type to use |
| `SEP31TransactionInfoNeededException` | 400 | `postTransactions()` — deprecated inline fields needed | `$e->fields` (array\|null) — required fields |
| `SEP31BadRequestException` | 400 | Any method — malformed request, invalid parameters | `$e->getMessage()`, `$e->getCode()` |
| `SEP31TransactionNotFoundException` | 404 | `getTransaction()`, `patchTransaction()` — unknown ID | `$e->getMessage()` |
| `SEP31TransactionCallbackNotSupportedException` | 404 | `putTransactionCallback()` — anchor doesn't support callbacks | — |
| `SEP31UnknownResponseException` | other | Any method — unexpected HTTP status | `$e->getMessage()`, `$e->getCode()` |

All exception classes are in `Soneso\StellarSDK\SEP\CrossBorderPayments`.

`SEP31CustomerInfoNeededException` has a public `$error = 'customer_info_needed'` property and a public `$type` property. `SEP31TransactionInfoNeededException` has `$error = 'transaction_info_needed'` and `$fields`.

---

## Common Pitfalls

**Memo is the payment key — use the exact value from the anchor:**

```php
// WRONG: hardcoding or guessing the memo
$tx->sign($senderKeyPair, Network::testnet());
// No memo set — anchor cannot match the payment to your transaction

// CORRECT: use stellarMemo + stellarMemoType exactly as provided
$memoObj = match ($memoType) {
    'id'   => Memo::id((int) $memo),
    'text' => Memo::text($memo),
    'hash' => Memo::hash($memo),
};
$transaction = (new TransactionBuilder($account))
    ->addOperation($paymentOp)
    ->addMemo($memoObj)  // required — do not omit
    ->build();
```

**`stellarAccountId` may be null after POST — always check before sending payment:**

```php
// WRONG: sending payment immediately without checking
$stellarAccount = $response->stellarAccountId; // may be null
$payment = new PaymentOperationBuilder($stellarAccount, ...); // null destination crashes

// CORRECT: check and poll if necessary
if ($response->stellarAccountId === null) {
    // Poll GET /transactions/:id until status is pending_sender
}
```

**Source account for the payment does NOT need to match the SEP-10 account:**

```php
// CORRECT: the Stellar payment can come from any account
// Only the memo is used to match the payment — not the source account
// The SEP-10 authenticated account is used for API authentication only
```

**`SEP31CustomerInfoNeededException::$type` is the SEP-12 customer type — not the customer ID:**

```php
// WRONG: passing $e->type as a customer ID
$senderId = $e->type; // this is a type string like "sep31-sender", not an ID

// CORRECT: use $e->type as the 'type' parameter for SEP-12 registration
$request->type = $e->type;
$senderId = $kycService->putCustomerInfo($request)->getId();
```

**`fundingMethods` is on `SEP31ReceiveAssetInfo`, not the service:**

```php
// WRONG: trying to call a method that doesn't exist
// $service->getFundingMethods() -- no such method

// CORRECT: read from the /info response
$info = $service->info($jwtToken);
$methods = $info->receiveAssets['USDC']->fundingMethods; // array<string>|null
```

**`patchTransaction` sends fields nested under `"fields"` key automatically:**

```php
// WRONG: wrapping the fields array yourself
$service->patchTransaction($id, ['fields' => ['transaction' => [...]]], $jwt);
// This produces: {"fields": {"fields": {"transaction": {...}}}} — double-nested

// CORRECT: pass the fields array directly — the SDK wraps it in "fields" automatically
$service->patchTransaction($id, ['transaction' => ['key' => 'value']], $jwt);
// This produces: {"fields": {"transaction": {"key": "value"}}}
```

**Quote expiration: send the Stellar payment before the SEP-38 quote expires:**

```php
// CORRECT: check quote expiry and send payment promptly
// $quote->expiresAt is the deadline — the Stellar payment must be submitted before then
// If the quote expires, the anchor may reject or use a different rate
```

---

## Related SEPs

- [SEP-01](sep-01.md) — stellar.toml discovery (provides `DIRECT_PAYMENT_SERVER` consumed by `fromDomain()`)
- [SEP-10](sep-10.md) — Web Authentication (provides the JWT required for all SEP-31 requests)
- [SEP-12](sep-12.md) — KYC API (register sender and receiver before initiating transactions)
- [SEP-38](sep-38.md) — Anchor RFQ API (get firm exchange rate quotes for `quote_id`)

<!-- DISCREPANCIES FOUND:

1. docs/sep/sep-31.md "Funding Methods" section states: "the funding_methods array from the
   /info response is not currently exposed by the SDK." This is WRONG. The source file
   SEP31ReceiveAssetInfo.php has a public $fundingMethods property (array<string>|null) and
   fromJson() maps it from $json['funding_methods']. The field is fully exposed. The test
   CrossBorderPaymentsTest::testGetInfo() verifies $usdc->fundingMethods correctly.

2. docs/sep/sep-31.md "Deprecated: Updating Transaction Info" shows patchTransaction called with
   a nested array ['transaction' => [...fields...]] passed directly as the $fields argument.
   The source CrossBorderPaymentsService::patchTransaction() sends ['fields' => $fields] in the
   request body. So the caller's $fields array is wrapped under the "fields" key. The test
   verifies this: $jsonData['fields'] equals the exact $fields array passed in. This means the
   docs example — passing ['transaction' => [...]] as $fields — produces the correct JSON
   {"fields": {"transaction": {...}}}. The docs example IS correct, but the note says
   "patchTransaction($id, ['transaction' => [...]], $jwt)" which produces the right output.
   No real discrepancy here, but the wrapping behavior is subtle and worth documenting.

3. docs/sep/sep-31.md shows SEP31TransactionInfoNeededException usage with
   "foreach ($e->fields as $field => $info)" where $info is described as possibly being
   an array with 'description'. The test confirms $e->fields is exactly the raw JSON
   ['transaction' => ['xxx' => 'yyy']] passed by the server — the structure varies by anchor.
   No SDK-level discrepancy but the docs example is reasonable.

JUDGMENT CALLS MADE:

1. The "PATCH /transactions" section explains that $fields is passed directly to patchTransaction()
   and the SDK wraps it in {"fields": ...} automatically. This is a genuine pitfall since the docs
   show the wrapped form inconsistently in different examples.

2. Added a WRONG/CORRECT for the patchTransaction double-wrapping pitfall, which is a real trap
   developers will hit if they read the raw SEP-31 spec (which shows "fields" at the top level)
   and try to replicate that structure manually.

3. The $lang parameter on info() is documented but not shown in most examples to keep them lean.

4. SEP31ReceiveAssetInfo::$fields (deprecated inline fields from GET /info) is documented in the
   response objects section but not emphasized since it is deprecated. Same for $senderSep12Type
   and $receiverSep12Type.

5. SEP31TransactionResponse::$refunded (deprecated bool) is listed in the response object table
   for completeness but the preferred $refunds object is what the example code uses.

-->
