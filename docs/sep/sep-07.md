# SEP-07: URI Scheme for Delegated Signing

SEP-07 defines a URI scheme (`web+stellar:`) that enables applications to request transaction signing from external wallets. Instead of handling private keys directly, your application generates a URI that a wallet can open, sign, and submit.

**When to use:** Building web applications that need users to sign transactions, creating payment request links, QR codes for payments, or integrating with hardware wallets or other signing services.

See the [SEP-07 specification](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0007.md) for complete protocol details.

## Quick example

The simplest way to create a payment request URI is with `generatePayOperationURI()`. This creates a `web+stellar:pay?` URI that any SEP-07 compliant wallet can process.

```php
<?php

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();

// Generate a payment request URI for 100 USDC
$uri = $uriScheme->generatePayOperationURI(
    destinationAccountId: 'GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV',
    amount: '100',
    assetCode: 'USDC',
    assetIssuer: 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN'
);

echo $uri . PHP_EOL;
// Output: web+stellar:pay?destination=GDGUF4SC...&amount=100&asset_code=USDC&asset_issuer=GA5ZSEJY...
```

## Generating URIs

### Transaction signing (tx operation)

The `tx` operation requests a wallet to sign a specific XDR-encoded transaction. Use this when you have full control over the transaction structure and need an exact transaction to be signed.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();

// Source account keypair (the account that will sign)
$sourceKeyPair = KeyPair::fromSeed('SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CPMLIHJPFV5RXN5M6CSS');
$accountId = $sourceKeyPair->getAccountId();
$sourceAccount = $sdk->requestAccount($accountId);

// Build a transaction that sets the home domain
$setOptionsOp = (new SetOptionsOperationBuilder())
    ->setSourceAccount($accountId)
    ->setHomeDomain('www.example.com')
    ->build();

$transaction = (new TransactionBuilder($sourceAccount))
    ->addOperation($setOptionsOp)
    ->build();

// Generate a SEP-07 URI from the unsigned transaction
$uriScheme = new URIScheme();
$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: $transaction->toEnvelopeXdrBase64()
);

echo $uri . PHP_EOL;
// Output: web+stellar:tx?xdr=AAAAAgAAAAD...
```

### Transaction URI with all options

The `generateSignTransactionURI()` method accepts optional parameters for callbacks, messages, signature verification, and more.

```php
<?php

use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();
$xdrBase64 = 'AAAAAgAAAAD...'; // Your transaction XDR

$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: $xdrBase64,
    replace: null,  // Field replacement spec (see "Field replacement with Txrep" section)
    callback: 'url:https://example.com/callback',  // Where to POST signed tx
    publicKey: 'GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV', // Which account should sign
    chain: null,  // Nested SEP-07 URI that triggered this one
    message: 'Please sign to update your account settings',  // User-facing message (max 300 chars)
    networkPassphrase: Network::testnet()->getNetworkPassphrase(),  // Omit for public network
    originDomain: 'example.com'  // Your domain (requires signing the URI)
);

echo $uri . PHP_EOL;
```

### Field replacement with Txrep (replace parameter)

The `replace` parameter lets you specify fields in the transaction that should be filled in by the wallet user. This uses the [SEP-11 Txrep](sep-11.md) format to identify fields. Useful when you want the user to provide certain values like source account or destination.

```php
<?php

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();
$xdrBase64 = 'AAAAAgAAAAD...'; // Transaction XDR with placeholder accounts

// Format: field1:refId1,field2:refId2;refId1:hint1,refId2:hint2
// The user will be asked to provide accounts for X and Y
$replace = 'sourceAccount:X,operations[0].destination:Y;X:Account to pay fees from,Y:Account to receive tokens';

$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: $xdrBase64,
    replace: $replace
);

echo $uri . PHP_EOL;
```

### Transaction chaining (chain parameter)

The `chain` parameter embeds a previous SEP-07 URI that triggered the creation of this one. This is informational and enables verification of the full request chain. Chains can nest up to 7 levels deep.

```php
<?php

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();
$xdrBase64 = 'AAAAAgAAAAD...';

// The original URI that triggered this request
$originalUri = 'web+stellar:tx?xdr=AAAA...&origin_domain=original.com&signature=...';

$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: $xdrBase64,
    chain: $originalUri,  // Embed the original request for audit purposes
    callback: 'url:https://multisig-coordinator.com/collect',
    originDomain: 'multisig-coordinator.com'
);

echo $uri . PHP_EOL;
```

### Multisig coordination

The `callback` parameter is particularly useful for multisig coordination services. Instead of submitting directly to the network, the signed transaction is POSTed to a coordination service that collects signatures from multiple parties.

```php
<?php

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();
$xdrBase64 = 'AAAAAgAAAAD...'; // Transaction requiring multiple signatures

// Generate URI that sends signed tx to a multisig coordinator
$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: $xdrBase64,
    callback: 'url:https://multisig-service.example.com/collect',
    message: 'Sign to approve the 2-of-3 multisig transaction',
    originDomain: 'multisig-service.example.com'
);

// Each signer receives this URI and signs independently
// The coordinator collects signatures and submits when threshold is met
echo $uri . PHP_EOL;
```

### Payment request (pay operation)

The `pay` operation requests a payment to a destination without pre-building a transaction. The wallet can choose the payment method (direct or path payment) and source asset.

```php
<?php

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();

// Simple XLM payment (no asset_code means native XLM)
$uri = $uriScheme->generatePayOperationURI(
    destinationAccountId: 'GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV',
    amount: '50.5'
);
echo $uri . PHP_EOL;
// Output: web+stellar:pay?destination=GDGUF4SC...&amount=50.5
```

### Payment with asset and memo

When accepting payments for specific assets or with order tracking via memos, specify the full payment details.

```php
<?php

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();

// Payment with specific asset and text memo
$uri = $uriScheme->generatePayOperationURI(
    destinationAccountId: 'GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV',
    amount: '100',
    assetCode: 'USDC',
    assetIssuer: 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    memo: 'order-12345',
    memoType: 'MEMO_TEXT'
);
echo $uri . PHP_EOL;
```

### Payment with hash or return memo

For `MEMO_HASH` and `MEMO_RETURN` memo types, the memo value must be base64-encoded before being passed to the method.

```php
<?php

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();

// MEMO_HASH requires base64 encoding of the 32-byte hash
$hashBytes = hash('sha256', 'my-unique-identifier', true);
$memoValue = base64_encode($hashBytes);

$uri = $uriScheme->generatePayOperationURI(
    destinationAccountId: 'GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV',
    amount: '100',
    memo: $memoValue,
    memoType: 'MEMO_HASH'
);
echo $uri . PHP_EOL;
```

### Donation request (no amount)

Omit the amount to let the user decide how much to send. Useful for donations or tips.

```php
<?php

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();

// Omitting amount allows user to specify any amount
$uri = $uriScheme->generatePayOperationURI(
    destinationAccountId: 'GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV',
    message: 'Support our open source project!'
);

echo $uri . PHP_EOL;
// Output: web+stellar:pay?destination=GDGUF4SC...&msg=Support%20our%20open%20source%20project%21
```

## Signing URIs for origin verification

If your application issues SEP-07 URIs and wants to prove authenticity, sign them with a keypair whose public key is published as `URI_REQUEST_SIGNING_KEY` in your [stellar.toml](sep-01.md) file.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();

// Your signing keypair - the public key must match URI_REQUEST_SIGNING_KEY in your stellar.toml
$signerKeyPair = KeyPair::fromSeed('SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CPMLIHJPFV5RXN5M6CSS');

// First generate the URI with origin_domain (signature will be added by signURI)
$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: 'AAAAAgAAAAD...',
    originDomain: 'example.com'
);

// Sign the URI - this appends the signature parameter
$signedUri = $uriScheme->signURI($uri, $signerKeyPair);

echo $signedUri . PHP_EOL;
// Output: web+stellar:tx?xdr=...&origin_domain=example.com&signature=bIZ53bPK...
```

## Validating URIs

Before processing a URI from an untrusted source, validate it to verify the signature against the origin domain's stellar.toml. This protects users from tampered or malicious requests.

```php
<?php

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;
use Soneso\StellarSDK\SEP\URIScheme\URISchemeError;

$uriScheme = new URIScheme();
$uri = 'web+stellar:tx?xdr=...&origin_domain=example.com&signature=...';

try {
    // Validates signature against stellar.toml URI_REQUEST_SIGNING_KEY
    // Note: The method is named checkUIRSchemeIsValid() (UIR, not URI) for backward compatibility
    $isValid = $uriScheme->checkUIRSchemeIsValid($uri);
    
    // Only display origin_domain to user after successful validation
    $originDomain = $uriScheme->getParameterValue('origin_domain', $uri);
    echo "URI is valid and signed by: " . $originDomain . PHP_EOL;
    
} catch (URISchemeError $e) {
    // Handle specific validation failures
    switch ($e->getCode()) {
        case URISchemeError::missingOriginDomain:
            // URI has no origin_domain - treat as unsigned/untrusted
            echo "Warning: Unsigned URI request" . PHP_EOL;
            break;
        case URISchemeError::invalidOriginDomain:
            // origin_domain is not a valid fully qualified domain name
            echo "Error: Invalid origin domain format" . PHP_EOL;
            break;
        case URISchemeError::missingSignature:
            // origin_domain present but no signature - invalid request
            echo "Error: Origin domain specified but signature missing" . PHP_EOL;
            break;
        case URISchemeError::tomlNotFoundOrInvalid:
            // Could not fetch or parse stellar.toml from origin domain
            echo "Error: Could not fetch stellar.toml from origin domain" . PHP_EOL;
            break;
        case URISchemeError::tomlSignatureMissing:
            // stellar.toml exists but has no URI_REQUEST_SIGNING_KEY
            echo "Error: stellar.toml has no URI_REQUEST_SIGNING_KEY" . PHP_EOL;
            break;
        case URISchemeError::invalidSignature:
            // Signature does not match - possible tampering
            echo "Error: Signature verification failed - do not trust this URI" . PHP_EOL;
            break;
    }
}
```

## Signing and submitting transactions

Use `signAndSubmitTransaction()` to sign a transaction from a URI and submit it. The method handles submission to either a callback URL or directly to the Stellar network.

```php
<?php

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();

// The URI containing the transaction to sign
$uri = 'web+stellar:tx?xdr=AAAAAgAAAAD...';

// User's signing keypair
$signerKeyPair = KeyPair::fromSeed('SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CPMLIHJPFV5RXN5M6CSS');

// Sign and submit the transaction
$response = $uriScheme->signAndSubmitTransaction(
    url: $uri,
    signerKeyPair: $signerKeyPair,
    network: Network::testnet()
);

// Check the result - only one response type will be set
if ($response->getSubmitTransactionResponse() !== null) {
    // Transaction was submitted directly to the Stellar network
    $txResponse = $response->getSubmitTransactionResponse();
    if ($txResponse->isSuccessful()) {
        echo "Transaction successful!" . PHP_EOL;
        echo "Hash: " . $txResponse->getHash() . PHP_EOL;
    } else {
        echo "Transaction failed" . PHP_EOL;
        print_r($txResponse->getExtras()->getResultCodes());
    }
} elseif ($response->getCallBackResponse() !== null) {
    // Transaction was sent to the callback URL specified in the URI
    $httpResponse = $response->getCallBackResponse();
    echo "Callback response status: " . $httpResponse->getStatusCode() . PHP_EOL;
    echo "Callback response body: " . $httpResponse->getBody()->getContents() . PHP_EOL;
}
```

## Extracting URI parameters

Use `getParameterValue()` to extract specific query parameters from a SEP-07 URI.

```php
<?php

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();
$uri = 'web+stellar:pay?destination=GDGUF4SC...&amount=100&memo=order-123&msg=Payment%20for%20order';

// Extract individual parameters
$destination = $uriScheme->getParameterValue(URIScheme::destinationParameterName, $uri);
$amount = $uriScheme->getParameterValue(URIScheme::amountParameterName, $uri);
$memo = $uriScheme->getParameterValue(URIScheme::memoParameterName, $uri);
$message = $uriScheme->getParameterValue(URIScheme::messageParameterName, $uri);
$callback = $uriScheme->getParameterValue(URIScheme::callbackParameterName, $uri); // null if not present

echo "Payment request:" . PHP_EOL;
echo "  Destination: {$destination}" . PHP_EOL;
echo "  Amount: {$amount}" . PHP_EOL;

if ($memo !== null) {
    echo "  Memo: {$memo}" . PHP_EOL;
}

if ($message !== null) {
    echo "  Message: {$message}" . PHP_EOL;
}

if ($callback !== null) {
    echo "  Callback: {$callback}" . PHP_EOL;
} else {
    echo "  Submit directly to network" . PHP_EOL;
}
```

### Available parameter constants

The `URIScheme` class provides constants for all standard parameter names:

| Constant | Value | Description |
|----------|-------|-------------|
| `xdrParameterName` | `xdr` | Transaction envelope XDR |
| `replaceParameterName` | `replace` | Txrep field replacement spec |
| `callbackParameterName` | `callback` | Callback URL for submission |
| `publicKeyParameterName` | `pubkey` | Required signing public key |
| `chainParameterName` | `chain` | Nested SEP-07 URI |
| `messageParameterName` | `msg` | User-facing message |
| `networkPassphraseParameterName` | `network_passphrase` | Network identifier |
| `originDomainParameterName` | `origin_domain` | Request originator domain |
| `signatureParameterName` | `signature` | URI signature |
| `destinationParameterName` | `destination` | Payment recipient |
| `amountParameterName` | `amount` | Payment amount |
| `assetCodeParameterName` | `asset_code` | Asset code |
| `assetIssuerParameterName` | `asset_issuer` | Asset issuer account |
| `memoParameterName` | `memo` | Transaction memo value |
| `memoTypeParameterName` | `memo_type` | Memo type |

## Error handling

Error handling for URI validation and transaction submission.

```php
<?php

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;
use Soneso\StellarSDK\SEP\URIScheme\URISchemeError;

$uriScheme = new URIScheme();

// 1. Handle invalid XDR in URI
try {
    $uri = 'web+stellar:tx?xdr=invalid-base64-data';
    $keyPair = KeyPair::fromSeed('SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CPMLIHJPFV5RXN5M6CSS');
    $uriScheme->signAndSubmitTransaction($uri, $keyPair, Network::testnet());
} catch (InvalidArgumentException $e) {
    echo "Invalid URI format: " . $e->getMessage() . PHP_EOL;
}

// 2. Handle URI validation errors
try {
    $uri = 'web+stellar:tx?xdr=...&origin_domain=example.com';
    $uriScheme->checkUIRSchemeIsValid($uri);
} catch (URISchemeError $e) {
    // Use toString() for human-readable error message
    echo $e->toString() . PHP_EOL;
    // Or check code for programmatic handling
    echo "Error code: " . $e->getCode() . PHP_EOL;
}

// 3. Handle transaction submission errors
try {
    $uri = 'web+stellar:tx?xdr=AAAAAgAAAAD...';
    $keyPair = KeyPair::fromSeed('SCZANGBA5YHTNYVVV3C7CAZMTQDBJHJG6C34CPMLIHJPFV5RXN5M6CSS');
    $response = $uriScheme->signAndSubmitTransaction($uri, $keyPair, Network::testnet());
    
    if ($response->getSubmitTransactionResponse() !== null) {
        $txResponse = $response->getSubmitTransactionResponse();
        if (!$txResponse->isSuccessful()) {
            // Transaction was submitted but failed
            $resultCodes = $txResponse->getExtras()->getResultCodes();
            echo "Transaction failed: " . $resultCodes->getTransactionResultCode() . PHP_EOL;
            
            if ($resultCodes->getOperationsResultCodes() !== null) {
                foreach ($resultCodes->getOperationsResultCodes() as $i => $opCode) {
                    echo "  Operation {$i}: {$opCode}" . PHP_EOL;
                }
            }
        }
    }
} catch (HorizonRequestException $e) {
    // Network or Horizon server error
    echo "Horizon error: " . $e->getMessage() . PHP_EOL;
} catch (GuzzleException $e) {
    // HTTP error when submitting to callback URL
    echo "HTTP error: " . $e->getMessage() . PHP_EOL;
} catch (Exception $e) {
    echo "Unexpected error: " . $e->getMessage() . PHP_EOL;
}
```

## Testing with mock HTTP handler

For unit testing, use `setMockHandlerStack()` to replace the HTTP client with a mock handler. This lets you test stellar.toml fetching and callback submissions without making actual network requests.

```php
<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

// Create mock responses
$mockHandler = new MockHandler([
    // Mock stellar.toml response
    new Response(200, [], 'URI_REQUEST_SIGNING_KEY="GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV"'),
    // Mock callback response
    new Response(200, [], '{"status": "ok"}'),
]);

$handlerStack = HandlerStack::create($mockHandler);

// Inject mock handler into URIScheme
$uriScheme = new URIScheme();
$uriScheme->setMockHandlerStack($handlerStack);

// Now HTTP requests will use mock responses
// ... your test code here
```

## QR codes

SEP-07 URIs can be encoded into QR codes for mobile scanning. Encode the complete URI into the QR code data.

```php
<?php

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();

$uri = $uriScheme->generatePayOperationURI(
    destinationAccountId: 'GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV',
    amount: '25',
    memo: 'coffee',
    memoType: 'MEMO_TEXT'
);

// Use any QR code library to encode the URI
// Example with a hypothetical QR library:
// $qrCode = QRCode::create($uri)->writeToFile('payment-qr.png');

echo "Encode this URI in a QR code: " . $uri . PHP_EOL;
```

## Security considerations

When implementing SEP-07 support, follow these security practices from the specification:

### For applications generating URIs

- **Always sign your URIs** with an `origin_domain` and `signature` when possible. Unsigned URIs should be treated as untrusted.
- **Publish your `URI_REQUEST_SIGNING_KEY`** in your stellar.toml file.
- **Include meaningful messages** in the `msg` parameter to help users understand what they're signing.
- **Use unique memos** to track individual payment requests.

### For wallets processing URIs

- **Always validate signed URIs** before displaying `origin_domain` to users.
- **Never auto-sign transactions** - always get explicit user consent.
- **Display transaction details clearly** so users understand what they're signing.
- **Warn users about unsigned URIs** - they are equivalent to HTTP vs HTTPS.
- **Track known destination addresses** and warn about new recipients.
- **Use fonts that distinguish similar characters** to prevent homograph attacks (e.g., distinguishing `l` from `I`, or Latin from Cyrillic characters).
- **Cache `URI_REQUEST_SIGNING_KEY`** per domain and alert users if it changes.

### Callback security

- **Callbacks receive signed transactions** - be careful what endpoints you trust.
- **Validate callback URLs** before sending signed transactions to them.
- **The `msg` field can be spoofed** - only trust message content after successful signature validation.

## Further reading

- [SEP-07 test cases](https://github.com/Soneso/stellar-php-sdk/blob/main/Soneso/StellarSDKTests/Integration/SEP007Test.php) - SDK test cases demonstrating URI generation, signing, and validation

## Related SEPs

- [SEP-01 stellar.toml](sep-01.md) - Where `URI_REQUEST_SIGNING_KEY` is published for signature verification
- [SEP-11 Txrep](sep-11.md) - Human-readable transaction format used in the `replace` parameter

---

[Back to SEP Overview](README.md)
