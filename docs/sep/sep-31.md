# SEP-31: Cross-Border Payments

SEP-31 defines a protocol for sending payments between financial accounts that exist outside the Stellar network. A Sending Anchor initiates a payment on behalf of a Sending Client, which is delivered by a Receiving Anchor to a Receiving Client. The actual value transfer happens on the Stellar network, but the endpoints are traditional accounts (bank accounts, mobile wallets, etc.).

Use SEP-31 when:
- Building a remittance service or payment corridor
- Sending money to a recipient who needs funds delivered off-chain
- You're a Sending Anchor integrating with Receiving Anchors
- Processing B2B cross-border payments

The PHP SDK supports the Sending Anchor side of SEP-31.

See the [SEP-31 specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0031.md) for protocol details.

## How Cross-Border Payments Work

1. **Authenticate**: Get JWT token via SEP-10 with pre-authorized Stellar account
2. **Discover**: Query anchor info via GET /info to learn supported assets and KYC requirements
3. **KYC**: Register sender and receiver via SEP-12 if required
4. **Quote** (optional): Get exchange rate via SEP-38 for guaranteed rates
5. **Initiate**: POST transaction to Receiving Anchor
6. **Pay**: Send Stellar payment with the exact memo provided
7. **Track**: Monitor status until completed or handle errors

## Quick Example

This example shows the minimal flow for starting a cross-border payment. It assumes you've already completed KYC via SEP-12:

```php
<?php

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31PostTransactionsRequest;

// Connect to receiving anchor's direct payment server
$service = CrossBorderPaymentsService::fromDomain("receivinganchor.com");

// Create payment request with pre-registered sender and receiver IDs
$request = new SEP31PostTransactionsRequest(
    amount: 100.00,
    assetCode: "USDC",
    assetIssuer: "GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
    senderId: "sender-customer-id",   // From SEP-12 PUT /customer
    receiverId: "receiver-customer-id" // From SEP-12 PUT /customer
);

$response = $service->postTransactions($request, $jwtToken);

// Get payment instructions to complete the Stellar transaction
echo "Transaction ID: " . $response->id . "\n";
echo "Send to: " . $response->stellarAccountId . "\n";
echo "Memo (" . $response->stellarMemoType . "): " . $response->stellarMemo . "\n";
```

## Creating the Service

`CrossBorderPaymentsService` handles all SEP-31 operations. Create it from a domain (recommended) or a direct URL.

**From the receiving anchor's domain** (recommended approach that loads `DIRECT_PAYMENT_SERVER` from stellar.toml):

```php
<?php

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;

// Loads DIRECT_PAYMENT_SERVER from stellar.toml automatically
$service = CrossBorderPaymentsService::fromDomain("receivinganchor.com");
```

**From a direct URL** (when you already know the server endpoint):

```php
<?php

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;

$service = new CrossBorderPaymentsService("https://api.receivinganchor.com/sep31");
```

## Getting Anchor Information

Before initiating a transaction, query the anchor to find out what assets they accept, their limits, fees, and required KYC types:

```php
<?php

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;

$service = CrossBorderPaymentsService::fromDomain("receivinganchor.com");

// Get info about supported receiving assets
$info = $service->info($jwtToken);

// Iterate through all supported assets
foreach ($info->receiveAssets as $assetCode => $assetInfo) {
    echo "Asset: $assetCode\n";
    echo "  Min amount: " . ($assetInfo->minAmount ?? "No limit") . "\n";
    echo "  Max amount: " . ($assetInfo->maxAmount ?? "No limit") . "\n";
    echo "  Fixed fee: " . ($assetInfo->feeFixed ?? "N/A") . "\n";
    echo "  Percent fee: " . ($assetInfo->feePercent ?? "N/A") . "%\n";
    
    // Check SEP-38 quote support
    echo "  Quotes supported: " . ($assetInfo->quotesSupported ? "Yes" : "No") . "\n";
    echo "  Quotes required: " . ($assetInfo->quotesRequired ? "Yes" : "No") . "\n";
    
    // Check required KYC types for senders and receivers
    if (!empty($assetInfo->sep12Info->senderTypes)) {
        echo "  Sender KYC types:\n";
        foreach ($assetInfo->sep12Info->senderTypes as $type => $description) {
            echo "    - $type: $description\n";
        }
    }
    
    if (!empty($assetInfo->sep12Info->receiverTypes)) {
        echo "  Receiver KYC types:\n";
        foreach ($assetInfo->sep12Info->receiverTypes as $type => $description) {
            echo "    - $type: $description\n";
        }
    }
}
```

## Complete Payment Flow

### Step 1: Register Sender and Receiver (SEP-12)

Before creating a transaction, register both the sender and receiver via SEP-12. The anchor's `/info` response tells you which `type` values to use:

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$kycService = KYCService::fromDomain("receivinganchor.com");

// Register the sender (person initiating the payment)
$senderFields = new NaturalPersonKYCFields();
$senderFields->firstName = "Jane";
$senderFields->lastName = "Sender";
$senderFields->emailAddress = "jane@sender.com";

$senderKyc = new StandardKYCFields();
$senderKyc->naturalPersonKYCFields = $senderFields;

$senderRequest = new PutCustomerInfoRequest();
$senderRequest->jwt = $jwtToken;
$senderRequest->KYCFields = $senderKyc;
$senderRequest->type = "sep31-sender"; // Use type from /info response

$senderResponse = $kycService->putCustomerInfo($senderRequest);
$senderId = $senderResponse->getId();

// Register the receiver (person receiving the funds)
$receiverFields = new NaturalPersonKYCFields();
$receiverFields->firstName = "Bob";
$receiverFields->lastName = "Receiver";

$receiverRequest = new PutCustomerInfoRequest();
$receiverRequest->jwt = $jwtToken;
$receiverRequest->KYCFields = new StandardKYCFields();
$receiverRequest->KYCFields->naturalPersonKYCFields = $receiverFields;
$receiverRequest->type = "sep31-receiver"; // Use type from /info response

// Include custom fields for payment delivery (e.g., bank account info)
$receiverRequest->customFields = [
    "bank_account_number" => "1234567890",
    "bank_routing_number" => "021000021",
];

$receiverResponse = $kycService->putCustomerInfo($receiverRequest);
$receiverId = $receiverResponse->getId();
```

### Step 2: Get a Quote (Optional, SEP-38)

If you want a locked-in exchange rate when converting between assets, request a firm quote from the Receiving Anchor before starting the transaction:

```php
<?php

use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38PostQuoteRequest;

$quoteService = QuoteService::fromDomain("receivinganchor.com");

// Request a firm quote for asset conversion
$quoteRequest = new SEP38PostQuoteRequest(
    context: "sep31",
    sellAsset: "stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
    buyAsset: "iso4217:BRL",
    sellAmount: "100"
);

$quote = $quoteService->postQuote($quoteRequest, $jwtToken);
$quoteId = $quote->id;
$expiresAt = $quote->expiresAt->format('Y-m-d H:i:s');

echo "Quote ID: $quoteId (expires: $expiresAt)\n";
echo "Rate: " . $quote->price . "\n";
echo "You'll receive: " . $quote->buyAmount . " BRL\n";

// IMPORTANT: Submit the Stellar payment before the quote expires!
```

### Step 3: Initiate the Transaction

Create the transaction with the Receiving Anchor. Include the quote ID if you got one:

```php
<?php

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31PostTransactionsRequest;

$service = CrossBorderPaymentsService::fromDomain("receivinganchor.com");

$request = new SEP31PostTransactionsRequest(
    amount: 100.00,
    assetCode: "USDC",
    assetIssuer: "GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
    destinationAsset: "iso4217:BRL",      // Off-chain asset for delivery
    senderId: $senderId,                   // From SEP-12
    receiverId: $receiverId,               // From SEP-12
    quoteId: $quoteId                      // Optional: lock in exchange rate
);

$response = $service->postTransactions($request, $jwtToken);

$transactionId = $response->id;
$stellarAccount = $response->stellarAccountId;
$memo = $response->stellarMemo;
$memoType = $response->stellarMemoType;

echo "Transaction created: $transactionId\n";
echo "Send 100 USDC to: $stellarAccount\n";
echo "With memo ($memoType): $memo\n";
```

### Step 4: Handle Pending Status (If Needed)

If the `stellarAccountId` and `stellarMemo` are not present in the POST response, the anchor is still processing the request. Poll until the status becomes `pending_sender`:

> **Polling Best Practice:** Instead of using fixed sleep intervals, implement exponential backoff or respect the `status_eta` field from the transaction response. The `status_eta` provides the anchor's estimate for when the status will change, reducing unnecessary API calls and load on the anchor's servers.

```php
<?php

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;

$service = CrossBorderPaymentsService::fromDomain("receivinganchor.com");

// Poll for payment details if not immediately available
$waitTime = 5; // Initial wait time in seconds
$maxWaitTime = 60; // Cap the wait time

while ($stellarAccount === null) {
    $txResponse = $service->getTransaction($transactionId, $jwtToken);
    
    if ($txResponse->status === "pending_sender") {
        $stellarAccount = $txResponse->stellarAccountId;
        $memo = $txResponse->stellarMemo;
        $memoType = $txResponse->stellarMemoType;
        break;
    } elseif ($txResponse->status === "error") {
        throw new Exception("Transaction failed: " . $txResponse->statusMessage);
    }
    
    echo "Status: " . $txResponse->status . " - waiting...\n";
    
    // Respect status_eta if provided, otherwise use exponential backoff
    if ($txResponse->statusEta !== null) {
        $waitTime = max(5, $txResponse->statusEta);
    } else {
        $waitTime = min($waitTime * 2, $maxWaitTime);
    }
    sleep($waitTime);
}
```

### Step 5: Send the Stellar Payment

Send the payment to the Stellar network using the exact memo from the anchor. The memo is how they match your payment to the transaction:

```php
<?php

use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Network;

$sdk = StellarSDK::getTestNetInstance();

$senderKeyPair = KeyPair::fromSeed("SXXXXX...");
$account = $sdk->requestAccount($senderKeyPair->getAccountId());

// Create the asset to send
$asset = Asset::createFromCanonicalForm(
    "USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN"
);

// Build payment operation to anchor's Stellar account
$payment = (new PaymentOperationBuilder($stellarAccount, $asset, "100"))
    ->build();

// Create the correct memo type based on anchor's response
$memoObj = match($memoType) {
    'id' => Memo::id((int)$memo),
    'text' => Memo::text($memo),
    'hash' => Memo::hash($memo),
    default => throw new Exception("Unknown memo type: $memoType")
};

// Build and sign the transaction
$transaction = (new TransactionBuilder($account))
    ->addOperation($payment)
    ->addMemo($memoObj)
    ->build();

$transaction->sign($senderKeyPair, Network::testnet());
$submitResponse = $sdk->submitTransaction($transaction);

echo "Payment submitted: " . $submitResponse->getHash() . "\n";
```

## Tracking Transaction Status

After sending the Stellar payment, check the transaction status to see how delivery is progressing:

```php
<?php

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31TransactionResponse;

$service = CrossBorderPaymentsService::fromDomain("receivinganchor.com");

$response = $service->getTransaction($transactionId, $jwtToken);

echo "Status: " . $response->status . "\n";
echo "Status message: " . ($response->statusMessage ?? "N/A") . "\n";
echo "Amount in: " . ($response->amountIn ?? "N/A") . "\n";
echo "Amount out: " . ($response->amountOut ?? "N/A") . "\n";

// Handle different status values
switch ($response->status) {
    case "pending_sender":
        echo "Waiting for Stellar payment from you\n";
        break;
        
    case "pending_stellar":
        echo "Stellar payment received, confirming on network\n";
        break;
        
    case "pending_receiver":
        echo "Processing - delivering funds to recipient\n";
        break;
        
    case "pending_external":
        echo "Submitted to external payment network, awaiting confirmation\n";
        break;
        
    case "pending_customer_info_update":
        echo "KYC update required - check SEP-12 for needed fields\n";
        // See "Handling KYC Update Requests" section below
        break;
        
    case "pending_transaction_info_update":
        echo "Transaction info update required (deprecated flow)\n";
        if ($response->requiredInfoUpdates) {
            print_r($response->requiredInfoUpdates);
        }
        break;
        
    case "completed":
        echo "Payment successfully delivered!\n";
        echo "Completed at: " . $response->completedAt . "\n";
        break;
        
    case "refunded":
        echo "Payment was refunded\n";
        if ($response->refunds) {
            echo "Refunded amount: " . $response->refunds->amountRefunded . "\n";
        }
        break;
        
    case "expired":
        echo "Transaction expired - quote may have expired before payment\n";
        break;
        
    case "error":
        echo "Error occurred: " . ($response->statusMessage ?? "Unknown error") . "\n";
        break;
}
```

## Handling KYC Update Requests

If the transaction status becomes `pending_customer_info_update`, the anchor needs more or corrected KYC information. Check SEP-12 to see what fields are required:

```php
<?php

use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoRequest;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;

$kycService = KYCService::fromDomain("receivinganchor.com");

// Check what fields need updating for the receiver
$getRequest = new GetCustomerInfoRequest();
$getRequest->jwt = $jwtToken;
$getRequest->id = $receiverId;
$getRequest->transactionId = $transactionId; // Link to the transaction

$customerInfo = $kycService->getCustomerInfo($getRequest);

// If status is NEEDS_INFO, check the 'fields' for required updates
if ($customerInfo->getStatus() === "NEEDS_INFO") {
    $requiredFields = $customerInfo->getFields();
    
    // Collect and submit the required information
    $updateRequest = new PutCustomerInfoRequest();
    $updateRequest->jwt = $jwtToken;
    $updateRequest->id = $receiverId;
    $updateRequest->transactionId = $transactionId;
    
    // Add the missing/corrected fields
    $updateRequest->customFields = [
        "bank_account_number" => "CORRECTED_ACCOUNT_NUMBER",
    ];
    
    $kycService->putCustomerInfo($updateRequest);
}
```

## Transaction Status Callbacks

You can register a callback URL so the anchor POSTs status updates to you instead of polling:

```php
<?php

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31TransactionCallbackNotSupportedException;

$service = CrossBorderPaymentsService::fromDomain("receivinganchor.com");

try {
    $service->putTransactionCallback(
        $transactionId,
        "https://myanchor.com/callbacks/sep31",
        $jwtToken
    );
    echo "Callback registered successfully\n";
} catch (SEP31TransactionCallbackNotSupportedException $e) {
    echo "Anchor doesn't support callbacks - use polling instead\n";
}
```

### Verifying Callback Signatures

When you receive callback POSTs, verify the signature. The anchor signs callbacks with their `SIGNING_KEY`:

```php
<?php

// In your callback endpoint handler:
// The signature is in the 'Signature' or 'X-Stellar-Signature' header
// Format: t=<timestamp>, s=<base64_signature>

$signatureHeader = $_SERVER['HTTP_SIGNATURE'] ?? $_SERVER['HTTP_X_STELLAR_SIGNATURE'] ?? '';

// Parse the header
preg_match('/t=(\d+),\s*s=(.+)/', $signatureHeader, $matches);
$timestamp = $matches[1];
$signature = base64_decode($matches[2]);

// Verify timestamp freshness (reject if > 2 minutes old)
if (time() - (int)$timestamp > 120) {
    http_response_code(400);
    exit('Request too old');
}

// The signed payload is: timestamp + "." + your_hostname + "." + request_body
$body = file_get_contents('php://input');
$payload = $timestamp . "." . $_SERVER['HTTP_HOST'] . "." . $body;

// Verify signature using anchor's SIGNING_KEY from their stellar.toml
// Use Soneso\StellarSDK\Crypto\KeyPair::verifySignature() or sodium_crypto_sign_verify_detached()
// Get the SIGNING_KEY from the anchor's stellar.toml file

http_response_code(204); // Success - no content
```

## Error Handling

The SDK throws specific exceptions for different error conditions:

```php
<?php

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31PostTransactionsRequest;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31CustomerInfoNeededException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31TransactionInfoNeededException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31TransactionNotFoundException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31TransactionCallbackNotSupportedException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31BadRequestException;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31UnknownResponseException;
use GuzzleHttp\Exception\GuzzleException;

$service = CrossBorderPaymentsService::fromDomain("receivinganchor.com");

try {
    $request = new SEP31PostTransactionsRequest(
        amount: 100.00,
        assetCode: "USDC",
        assetIssuer: "GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
        senderId: $senderId,
        receiverId: $receiverId
    );
    
    $response = $service->postTransactions($request, $jwtToken);
    echo "Transaction created: " . $response->id . "\n";
    
} catch (SEP31CustomerInfoNeededException $e) {
    // KYC information missing - register customer via SEP-12
    echo "Customer info needed.\n";
    if ($e->type !== null) {
        echo "Use SEP-12 type: " . $e->type . "\n";
    }
    // 1. Call SEP-12 GET /customer?type={$e->type} to see required fields
    // 2. Call SEP-12 PUT /customer with the data
    // 3. Retry POST /transactions with the new customer ID
    
} catch (SEP31TransactionInfoNeededException $e) {
    // Transaction fields missing (deprecated - use SEP-12 instead)
    echo "Transaction fields needed (deprecated flow):\n";
    if ($e->fields !== null) {
        foreach ($e->fields as $field => $info) {
            $description = is_array($info) ? ($info['description'] ?? $field) : $info;
            echo "  - $field: $description\n";
        }
    }
    
} catch (SEP31BadRequestException $e) {
    // Invalid request data
    echo "Bad request: " . $e->getMessage() . "\n";
    echo "HTTP code: " . $e->getCode() . "\n";
    
} catch (SEP31TransactionNotFoundException $e) {
    // Transaction not found (for GET requests)
    echo "Transaction not found\n";
    
} catch (SEP31TransactionCallbackNotSupportedException $e) {
    // Callback registration not supported
    echo "Callbacks not supported by this anchor\n";
    
} catch (SEP31UnknownResponseException $e) {
    // Unexpected response from anchor
    echo "Unexpected response: " . $e->getMessage() . "\n";
    echo "HTTP code: " . $e->getCode() . "\n";
    
} catch (GuzzleException $e) {
    // Network or HTTP error
    echo "Network error: " . $e->getMessage() . "\n";
}
```

## Refund Configuration

You can specify a custom memo for the anchor to use when refunding you:

```php
<?php

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31PostTransactionsRequest;

$service = CrossBorderPaymentsService::fromDomain("receivinganchor.com");

$request = new SEP31PostTransactionsRequest(
    amount: 100.00,
    assetCode: "USDC",
    assetIssuer: "GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
    senderId: $senderId,
    receiverId: $receiverId,
    refundMemo: "refund-12345",   // Your identifier for refunds
    refundMemoType: "text"        // Can be: id, text, or hash
);

$response = $service->postTransactions($request, $jwtToken);
```

## Funding Methods

Some anchors support different payment rails (like SWIFT, SEPA, ACH) for delivering funds:

> **Note:** While the SDK fully supports specifying `fundingMethod` when creating transactions, the `funding_methods` array from the `/info` response is not currently exposed by the SDK. You'll need to consult the anchor's documentation or API directly to discover available funding methods.

```php
<?php

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31PostTransactionsRequest;

$service = CrossBorderPaymentsService::fromDomain("receivinganchor.com");

// Include the funding method when creating a transaction
$request = new SEP31PostTransactionsRequest(
    amount: 100.00,
    assetCode: "USDC",
    assetIssuer: "GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
    senderId: $senderId,
    receiverId: $receiverId,
    fundingMethod: "SWIFT" // or "SEPA", "ACH", etc. - depends on anchor
);

$response = $service->postTransactions($request, $jwtToken);
```

## Fee Details

Transactions can include fee breakdowns showing what makes up the total fee:

```php
<?php

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;

$service = CrossBorderPaymentsService::fromDomain("receivinganchor.com");

$response = $service->getTransaction($transactionId, $jwtToken);

// Check fee details if available
if ($response->feeDetails !== null) {
    echo "Total fee: " . $response->feeDetails->total . " " . $response->feeDetails->asset . "\n";
    
    // Show fee breakdown if provided
    if ($response->feeDetails->details !== null) {
        echo "Fee breakdown:\n";
        foreach ($response->feeDetails->details as $detail) {
            echo "  - " . $detail->name . ": " . $detail->amount;
            if ($detail->description !== null) {
                echo " (" . $detail->description . ")";
            }
            echo "\n";
        }
    }
}
```

## Handling Refunds

When a transaction is refunded, the response includes refund details:

```php
<?php

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;

$service = CrossBorderPaymentsService::fromDomain("receivinganchor.com");

$response = $service->getTransaction($transactionId, $jwtToken);

if ($response->status === "refunded" && $response->refunds !== null) {
    echo "Total refunded: " . $response->refunds->amountRefunded . "\n";
    echo "Refund fees: " . $response->refunds->amountFee . "\n";
    
    // Individual refund payments
    echo "Refund transactions:\n";
    foreach ($response->refunds->payments as $payment) {
        echo "  - Stellar TX: " . $payment->id . "\n";
        echo "    Amount: " . $payment->amount . "\n";
        echo "    Fee: " . $payment->fee . "\n";
    }
}
```

## Deprecated: Updating Transaction Info

The `patchTransaction` method is deprecated. Use SEP-12 to update customer information instead:

```php
<?php

use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;

$service = CrossBorderPaymentsService::fromDomain("receivinganchor.com");

// DEPRECATED: Only use if anchor requires it for legacy compatibility
// Prefer updating via SEP-12 PUT /customer with transaction_id parameter
$service->patchTransaction(
    $transactionId,
    [
        'transaction' => [
            'receiver_bank_account' => '12345678901234',
            'receiver_routing_number' => '021000021',
        ]
    ],
    $jwtToken
);
```

## Transaction Statuses

| Status | Description |
|--------|-------------|
| `pending_sender` | Waiting for Stellar payment from Sending Anchor |
| `pending_stellar` | Stellar payment received, awaiting network confirmation |
| `pending_customer_info_update` | KYC update needed via SEP-12 |
| `pending_transaction_info_update` | Transaction fields need updating (deprecated) |
| `pending_receiver` | Payment being processed by Receiving Anchor |
| `pending_external` | Submitted to external network, awaiting confirmation |
| `completed` | Funds successfully delivered to Receiving Client |
| `refunded` | Payment was refunded (see `refunds` object for details) |
| `expired` | Transaction abandoned or quote expired before payment |
| `error` | An error occurred (check `statusMessage` for details) |

## SDK Classes Reference

| Class | Description |
|-------|-------------|
| `CrossBorderPaymentsService` | Main service for all SEP-31 operations |
| `SEP31InfoResponse` | Response from GET /info endpoint |
| `SEP31ReceiveAssetInfo` | Asset configuration including limits, fees, KYC types |
| `SEP12TypesInfo` | SEP-12 customer type definitions for senders/receivers |
| `SEP31PostTransactionsRequest` | Request body for initiating a transaction |
| `SEP31PostTransactionsResponse` | Response with transaction ID and payment details |
| `SEP31TransactionResponse` | Full transaction details from GET /transactions/:id |
| `SEP31FeeDetails` | Fee breakdown with total and individual components |
| `SEP31FeeDetailsDetails` | Individual fee component (name, amount, description) |
| `SEP31Refunds` | Refund summary with total amounts and payments |
| `SEP31RefundPayment` | Individual refund payment details |

### Exception Classes

| Exception | Cause |
|-----------|-------|
| `SEP31CustomerInfoNeededException` | KYC data missing - register via SEP-12 |
| `SEP31TransactionInfoNeededException` | Transaction fields missing (deprecated) |
| `SEP31TransactionNotFoundException` | Transaction ID not found |
| `SEP31TransactionCallbackNotSupportedException` | Anchor doesn't support callbacks |
| `SEP31BadRequestException` | Invalid request data (HTTP 400) |
| `SEP31UnknownResponseException` | Unexpected response from anchor |

## Important Notes

- **Memo is critical**: Always use the exact memo from the anchor. It's how they match your payment to the transaction.

- **Source account doesn't matter**: The Stellar payment can come from any account, not just the SEP-10 authenticated one. Only the memo matters.

- **Quote expiration**: If using SEP-38 quotes, send the Stellar payment before the quote expires. The transaction's `created_at` timestamp must be earlier than the quote's expiration.

- **KYC first**: Most anchors require SEP-12 KYC for both sender and receiver before accepting transactions.

- **HTTPS only**: All SEP-31 endpoints require HTTPS.

- **Authentication**: Every request needs a valid SEP-10 JWT token. The authenticated account must be pre-authorized by the Receiving Anchor.

## Related SEPs

- [SEP-1](sep-01.md) - stellar.toml (where `DIRECT_PAYMENT_SERVER` is published)
- [SEP-10](sep-10.md) - Web Authentication (required for SEP-31)
- [SEP-12](sep-12.md) - KYC API (register sender/receiver)
- [SEP-38](sep-38.md) - Anchor RFQ API (exchange rate quotes)

## Further Reading

- [SEP-31 SDK Test Cases](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/Unit/SEP/CrossBorderPayments/CrossBorderPaymentsTest.php) - Working examples and test scenarios

---

[Back to SEP Overview](README.md)
