# SEP-08: Regulated Assets

SEP-08 defines a protocol for assets that require issuer approval for every transaction. These "regulated assets" enable compliance with securities laws, KYC/AML requirements, velocity limits, and jurisdiction-based restrictions.

**Use SEP-08 when:**
- Transacting with assets marked as `regulated=true` in stellar.toml
- Working with securities tokens or compliance-controlled assets
- Building wallets that support regulated asset transfers

**How it works:** Before submitting a transaction involving a regulated asset to the Stellar network, you must first submit it to the issuer's approval server. The server evaluates the transaction against compliance rules and, if approved, signs it with the issuer's key.

**Spec:** [SEP-0008 v1.7.4](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0008.md)

## Quick example

This example shows the basic flow: discovering a regulated asset and submitting a transaction for approval:

```php
<?php

use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionSuccess;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRejected;

// Create service from anchor domain - loads stellar.toml automatically
$service = RegulatedAssetsService::fromDomain("regulated-asset-issuer.com");

// Get regulated assets defined in stellar.toml
$regulatedAssets = $service->regulatedAssets;
echo "Found " . count($regulatedAssets) . " regulated asset(s)" . PHP_EOL;

// Submit a transaction for approval
$signedTxXdr = "AAAAAgAAAA..."; // Your signed transaction as base64 XDR
$response = $service->postTransaction(
    tx: $signedTxXdr,
    approvalServer: $regulatedAssets[0]->approvalServer
);

if ($response instanceof SEP08PostTransactionSuccess) {
    echo "Approved! Submit this transaction: " . $response->tx . PHP_EOL;
} elseif ($response instanceof SEP08PostTransactionRejected) {
    echo "Rejected: " . $response->error . PHP_EOL;
}
```

## How regulated assets work

Per SEP-08, regulated assets require a specific setup and workflow:

1. **Issuer flags**: Asset issuer account has `AUTH_REQUIRED` and `AUTH_REVOCABLE` flags set. This allows the issuer to grant and revoke transaction authorization atomically.
2. **stellar.toml discovery**: The issuer's stellar.toml (SEP-01) defines the asset as `regulated=true` and specifies an `approval_server` URL.
3. **Transaction composition**: Transactions are structured with operations that authorize accounts, perform the transfer, and deauthorize accounts—all atomically. Wallets can either submit simple payment transactions and let the approval server add the authorization operations (returning a `revised` transaction), or build compliant transactions manually using `SetTrustLineFlags` operations.
4. **Approval flow**: Wallet submits the signed transaction to the approval server (not the Stellar network). Note that approval servers must support CORS to allow browser-based wallets to interact with them directly.
5. **Compliance check**: The server evaluates the transaction against its regulatory rules.
6. **Signing**: If approved, the server signs and returns the transaction.
7. **Network submission**: Wallet submits the fully-signed transaction to the Stellar network.

## Creating the service

### From domain

Load stellar.toml from the issuer's domain and extract all regulated asset definitions:

```php
<?php

use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;

// Loads stellar.toml and extracts regulated assets
$service = RegulatedAssetsService::fromDomain("regulated-asset-issuer.com");

// Access discovered regulated assets
foreach ($service->regulatedAssets as $asset) {
    echo $asset->getCode() . " issued by " . $asset->getIssuer() . PHP_EOL;
}
```

### From StellarToml data

If you've already loaded the stellar.toml data, pass it directly to the constructor. The stellar.toml must contain a `NETWORK_PASSPHRASE` field:

```php
<?php

use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\SEP\Toml\StellarToml;

$toml = StellarToml::fromDomain("regulated-asset-issuer.com");
$service = new RegulatedAssetsService($toml);
```

### With custom HTTP client

You can provide a custom Guzzle HTTP client for approval server requests. Useful for testing, proxying, or custom timeout configuration:

```php
<?php

use GuzzleHttp\Client;
use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;

$httpClient = new Client([
    'timeout' => 30,
    'headers' => ['User-Agent' => 'MyWallet/1.0']
]);

$service = RegulatedAssetsService::fromDomain(
    domain: "regulated-asset-issuer.com",
    httpClient: $httpClient
);
```

### Service properties

After initialization, the service exposes these properties:

```php
<?php

use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;

$service = RegulatedAssetsService::fromDomain("regulated-asset-issuer.com");

// Array of RegulatedAsset objects discovered from stellar.toml
$assets = $service->regulatedAssets;

// The StellarToml data used to initialize the service
$tomlData = $service->tomlData;

// The configured StellarSDK instance (for Horizon requests)
$sdk = $service->sdk;

// The network (used for transaction signing context)
$network = $service->network;
```

## Discovering regulated assets

The `RegulatedAsset` class extends `AssetTypeCreditAlphanum`, so it can be used wherever a standard asset is expected. It adds approval server information required for the compliance workflow:

```php
<?php

use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;

$service = RegulatedAssetsService::fromDomain("regulated-asset-issuer.com");

foreach ($service->regulatedAssets as $asset) {
    // Standard asset properties (inherited from AssetTypeCreditAlphanum)
    echo "Asset: " . $asset->getCode() . PHP_EOL;
    echo "Issuer: " . $asset->getIssuer() . PHP_EOL;
    echo "Type: " . $asset->getType() . PHP_EOL;  // credit_alphanum4 or credit_alphanum12
    
    // SEP-08 specific properties
    echo "Approval server: " . $asset->approvalServer . PHP_EOL;
    
    if ($asset->approvalCriteria !== null) {
        echo "Criteria: " . $asset->approvalCriteria . PHP_EOL;
    }
}
```

## Checking authorization requirements

Before transacting, verify the issuer account has proper authorization flags set. Per SEP-08, regulated asset issuers must have both `AUTH_REQUIRED` and `AUTH_REVOCABLE` flags enabled:

```php
<?php

use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;

$service = RegulatedAssetsService::fromDomain("regulated-asset-issuer.com");
$asset = $service->regulatedAssets[0];

// Checks that issuer has AUTH_REQUIRED and AUTH_REVOCABLE flags
$needsApproval = $service->authorizationRequired($asset);

if ($needsApproval) {
    echo "Asset requires approval server for all transactions" . PHP_EOL;
} else {
    echo "Warning: Issuer flags not properly configured for regulated assets" . PHP_EOL;
}
```

## Building a transaction for approval

Create and sign your transaction normally, then submit the base64-encoded XDR to the approval server:

```php
<?php

use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();
$service = RegulatedAssetsService::fromDomain("regulated-asset-issuer.com");
$regulatedAsset = $service->regulatedAssets[0];

// Sender's keypair
$senderKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG...");
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());

// Build the payment transaction using the regulated asset
$asset = Asset::createNonNativeAsset($regulatedAsset->getCode(), $regulatedAsset->getIssuer());

$payment = (new PaymentOperationBuilder(
    destinationAccountId: "GDEST...",
    asset: $asset,
    amount: "100"
))->build();

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation($payment)
    ->build();

// Sign with sender's key
$transaction->sign($senderKeyPair, Network::testnet());

// Convert to base64 XDR for submission to approval server
$txXdr = $transaction->toEnvelopeXdrBase64();
$response = $service->postTransaction(
    tx: $txXdr,
    approvalServer: $regulatedAsset->approvalServer
);
```

### Multiple regulated assets

When a transaction involves multiple regulated assets from different issuers (e.g., a path payment through several assets), each issuer's approval server must sign the transaction. Submit the transaction to each approval server sequentially, using the signed output from one server as input to the next. All issuers must approve before the transaction can be submitted to the Stellar network.

## Handling approval responses

The approval server returns one of five response types. Use `instanceof` checks to determine the response type and handle it:

```php
<?php

use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionSuccess;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRevised;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionPending;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionActionRequired;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRejected;
use Soneso\StellarSDK\StellarSDK;

$service = RegulatedAssetsService::fromDomain("regulated-asset-issuer.com");
$response = $service->postTransaction($txXdr, $approvalServer);

if ($response instanceof SEP08PostTransactionSuccess) {
    // Transaction approved and signed by issuer - submit to network
    echo "Approved!" . PHP_EOL;
    if ($response->message !== null) {
        echo "Message: " . $response->message . PHP_EOL;
    }
    $sdk = StellarSDK::getTestNetInstance();
    $result = $sdk->submitTransactionEnvelopeXdrBase64($response->tx);
    
} elseif ($response instanceof SEP08PostTransactionRevised) {
    // Transaction was modified for compliance - REVIEW CAREFULLY before submitting
    echo "Revised for compliance: " . $response->message . PHP_EOL;
    // WARNING: Always inspect the revised transaction to ensure it matches your intent
    // The issuer may have added operations (fees, compliance ops) but should not change
    // the core intent of your transaction
    
} elseif ($response instanceof SEP08PostTransactionPending) {
    // Approval pending - retry after the timeout period
    // Note: timeout is in MILLISECONDS per SEP-08 spec
    $timeoutMs = $response->timeout;
    echo "Pending. Check again in " . ($timeoutMs / 1000) . " seconds" . PHP_EOL;
    if ($response->message !== null) {
        echo "Message: " . $response->message . PHP_EOL;
    }
    
} elseif ($response instanceof SEP08PostTransactionActionRequired) {
    // User action needed - see "Handling Action Required" section
    echo "Action required: " . $response->message . PHP_EOL;
    echo "Action URL: " . $response->actionUrl . PHP_EOL;
    
} elseif ($response instanceof SEP08PostTransactionRejected) {
    // Transaction rejected - cannot be made compliant
    echo "Rejected: " . $response->error . PHP_EOL;
}
```

### Response types reference

| Response Class | Status | HTTP Code | Meaning |
|---------------|--------|-----------|---------|
| `SEP08PostTransactionSuccess` | `success` | 200 | Approved and signed—submit to network |
| `SEP08PostTransactionRevised` | `revised` | 200 | Modified for compliance—review before submitting |
| `SEP08PostTransactionPending` | `pending` | 200 | Check back after `timeout` milliseconds |
| `SEP08PostTransactionActionRequired` | `action_required` | 200 | User must complete action at URL |
| `SEP08PostTransactionRejected` | `rejected` | 400 | Denied—see error message |

## Handling action required

When the approval server needs additional information (KYC data, terms acceptance, etc.), it returns an `action_required` status. The SDK supports both GET and POST action methods:

```php
<?php

use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionActionRequired;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostActionDone;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostActionNextUrl;

$service = RegulatedAssetsService::fromDomain("regulated-asset-issuer.com");
$response = $service->postTransaction($txXdr, $approvalServer);

if ($response instanceof SEP08PostTransactionActionRequired) {
    echo "Action needed: " . $response->message . PHP_EOL;
    
    // Check what SEP-9 KYC fields are requested
    if ($response->actionFields !== null) {
        echo "Requested fields:" . PHP_EOL;
        foreach ($response->actionFields as $field) {
            echo "  - $field" . PHP_EOL;
        }
    }
    
    // Handle based on action method (GET or POST)
    if ($response->actionMethod === "POST") {
        // Submit fields programmatically if you have them
        $actionResponse = $service->postAction(
            url: $response->actionUrl,
            actionFields: [
                "email_address" => "user@example.com",
                "mobile_number" => "+1234567890"
            ]
        );
        
        if ($actionResponse instanceof SEP08PostActionDone) {
            // Action complete - resubmit the original transaction
            echo "Action complete. Resubmitting transaction..." . PHP_EOL;
            $response = $service->postTransaction($txXdr, $approvalServer);
            
        } elseif ($actionResponse instanceof SEP08PostActionNextUrl) {
            // More steps needed - user must complete action in browser
            echo "Further action required at: " . $actionResponse->nextUrl . PHP_EOL;
            if ($actionResponse->message !== null) {
                echo "Message: " . $actionResponse->message . PHP_EOL;
            }
        }
    } else {
        // action_method is GET (or not specified) - open URL in browser
        // You can append action fields as query parameters
        echo "Open in browser: " . $response->actionUrl . PHP_EOL;
    }
}
```

## Complete workflow example

This example shows the full approval flow for a regulated asset transfer, including all response type handling:

```php
<?php

use Exception;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionSuccess;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRevised;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionPending;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionActionRequired;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRejected;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

// Setup
$sdk = StellarSDK::getTestNetInstance();
$service = RegulatedAssetsService::fromDomain("regulated-asset-issuer.com");
$regulatedAsset = $service->regulatedAssets[0];

$senderKeyPair = KeyPair::fromSeed("SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG...");
$recipientId = "GDESTINATION...";

// Verify asset requires approval (issuer has proper flags)
if (!$service->authorizationRequired($regulatedAsset)) {
    throw new Exception("Asset issuer not properly configured for regulation");
}

// Build transaction
$senderAccount = $sdk->requestAccount($senderKeyPair->getAccountId());
$asset = Asset::createNonNativeAsset($regulatedAsset->getCode(), $regulatedAsset->getIssuer());

$transaction = (new TransactionBuilder($senderAccount))
    ->addOperation(
        (new PaymentOperationBuilder($recipientId, $asset, "100"))->build()
    )
    ->build();

$transaction->sign($senderKeyPair, Network::testnet());
$txXdr = $transaction->toEnvelopeXdrBase64();

// Submit for approval
$response = $service->postTransaction($txXdr, $regulatedAsset->approvalServer);

// Handle response
$approvedTx = null;

if ($response instanceof SEP08PostTransactionSuccess) {
    $approvedTx = $response->tx;
    
} elseif ($response instanceof SEP08PostTransactionRevised) {
    // IMPORTANT: Review revised transaction before accepting
    // The message should explain what was modified
    echo "Transaction revised: " . $response->message . PHP_EOL;
    $approvedTx = $response->tx;
    
} elseif ($response instanceof SEP08PostTransactionPending) {
    // Timeout is in milliseconds
    $waitSeconds = $response->timeout / 1000;
    echo "Try again in " . $waitSeconds . " seconds" . PHP_EOL;
    
} elseif ($response instanceof SEP08PostTransactionActionRequired) {
    echo "User action needed at: " . $response->actionUrl . PHP_EOL;
    
} elseif ($response instanceof SEP08PostTransactionRejected) {
    throw new Exception("Transaction rejected: " . $response->error);
}

// Submit approved transaction to Stellar network
if ($approvedTx !== null) {
    $result = $sdk->submitTransactionEnvelopeXdrBase64($approvedTx);
    echo "Transaction submitted: " . $result->getHash() . PHP_EOL;
}
```

## Error handling

The SDK throws specific exceptions for different error conditions:

```php
<?php

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08IncompleteInitData;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08InvalidPostActionResponse;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08InvalidPostTransactionResponse;

try {
    $service = RegulatedAssetsService::fromDomain("regulated-asset-issuer.com");
    $response = $service->postTransaction($txXdr, $approvalServer);
    
} catch (SEP08IncompleteInitData $e) {
    // stellar.toml is missing required NETWORK_PASSPHRASE or HORIZON_URL
    // and the SDK couldn't determine them from other sources
    echo "stellar.toml incomplete: " . $e->getMessage() . PHP_EOL;
    
} catch (SEP08InvalidPostTransactionResponse $e) {
    // Approval server returned malformed or unexpected response
    // getMessage() contains details, getCode() contains HTTP status
    echo "Invalid response from approval server: " . $e->getMessage() . PHP_EOL;
    echo "HTTP status: " . $e->getCode() . PHP_EOL;
    
} catch (SEP08InvalidPostActionResponse $e) {
    // Action endpoint returned malformed or unexpected response
    echo "Invalid action response: " . $e->getMessage() . PHP_EOL;
    
} catch (HorizonRequestException $e) {
    // Failed to load issuer account (for authorizationRequired check)
    // or failed to submit transaction to network
    echo "Horizon error: " . $e->getMessage() . PHP_EOL;
    
} catch (GuzzleException $e) {
    // Network error (connection timeout, DNS failure, etc.)
    echo "Network request failed: " . $e->getMessage() . PHP_EOL;
    
} catch (Exception $e) {
    // stellar.toml loading failed or other unexpected error
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
```

### Exception reference

| Exception | When Thrown |
|-----------|-------------|
| `SEP08IncompleteInitData` | Service can't determine network passphrase or Horizon URL |
| `SEP08InvalidPostTransactionResponse` | Approval server response is malformed or has unknown status |
| `SEP08InvalidPostActionResponse` | Action URL response is malformed or has unknown result |
| `HorizonRequestException` | Horizon API calls fail (account lookup, transaction submission) |
| `GuzzleException` | Network-level errors (timeouts, connection failures) |

## Security considerations

### Reviewing revised transactions

When you receive a `revised` response, **always inspect the transaction before submitting**. Per SEP-08, the approval server should only add operations (like authorization ops), not modify your original operations' intent. However, malicious servers could attempt to:

- Add operations that spend funds from your account
- Change payment destinations or amounts
- Add unexpected fees

Best practice: Compare the revised transaction with your original to ensure only expected operations were added.

### Authorization flags

The `AUTH_REQUIRED` and `AUTH_REVOCABLE` flags on the issuer account are required for security. They ensure:
- No one can transact the asset without explicit authorization
- Authorization can be revoked if compliance issues arise
- Transactions are atomic (authorize → transact → deauthorize happens together)

## Related SEPs

- [SEP-01](sep-01.md) - stellar.toml (defines regulated assets with `regulated`, `approval_server`, `approval_criteria`)
- [SEP-09](sep-09.md) - Standard KYC fields (used in `action_required` flows)
- [SEP-10](sep-10.md) - Web authentication (approval servers may require this for identity verification)

---

[Back to SEP Overview](README.md)
