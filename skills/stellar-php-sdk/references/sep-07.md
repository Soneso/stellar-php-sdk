# SEP-07: URI Scheme for Delegated Signing

**Purpose:** Generate `web+stellar:` URIs that request transaction signing from external wallets without exposing private keys.
**Prerequisites:** None
**SDK Namespace:** `Soneso\StellarSDK\SEP\URIScheme`
**Spec:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0007.md

## Table of Contents

1. [Quick Start](#quick-start)
2. [Generate Pay URI](#generate-pay-uri)
3. [Generate Transaction URI](#generate-transaction-uri)
4. [Sign a URI](#sign-a-uri)
5. [Validate a URI](#validate-a-uri)
6. [Sign and Submit a Transaction](#sign-and-submit-a-transaction)
7. [Extract URI Parameters](#extract-uri-parameters)
8. [SubmitUriSchemeTransactionResponse](#submiturisschemetransactionresponse)
9. [URISchemeError](#urischerneerror)
10. [Testing with Mock HTTP](#testing-with-mock-http)
11. [Parameter Constants](#parameter-constants)
12. [Common Pitfalls](#common-pitfalls)

---

## Quick Start

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();

// Payment request URI (web+stellar:pay?)
$uri = $uriScheme->generatePayOperationURI(
    destinationAccountId: 'GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV',
    amount: '100',
    assetCode: 'USDC',
    assetIssuer: 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    memo: 'order-12345',
    memoType: 'MEMO_TEXT'
);

echo $uri . PHP_EOL;
// web+stellar:pay?destination=GDGUF4SC...&amount=100&asset_code=USDC&asset_issuer=GA5ZSEJY...&memo=order-12345&memo_type=MEMO_TEXT
```

---

## Generate Pay URI

`generatePayOperationURI()` creates a `web+stellar:pay?` URI. The wallet can choose the payment path (direct payment or path payment) and source asset.

### Minimum (destination only — donation/open amount)

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();

$uri = $uriScheme->generatePayOperationURI(
    destinationAccountId: 'GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV'
);
// web+stellar:pay?destination=GDGUF4SC...
```

### With all pay parameters

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();

$uri = $uriScheme->generatePayOperationURI(
    destinationAccountId: 'GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV',
    amount: '100',                         // string amount; omit to let user choose
    assetCode: 'USDC',                     // omit for native XLM
    assetIssuer: 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    memo: 'order-12345',                   // text value; see note below for hash memos
    memoType: 'MEMO_TEXT',                 // MEMO_TEXT | MEMO_ID | MEMO_HASH | MEMO_RETURN
    callback: 'url:https://example.com/callback',  // must be prefixed with "url:"
    message: 'Payment for order 12345',    // max 300 chars, shown to wallet user
    networkPassphrase: Network::testnet()->getNetworkPassphrase(), // omit for public network
    originDomain: 'example.com',           // requires signURI() call after generation
    signature: null                        // leave null; signURI() appends this
);

echo $uri . PHP_EOL;
```

**MEMO_HASH / MEMO_RETURN:** The memo value must be base64-encoded before passing it.

```php
$hashBytes = hash('sha256', 'my-identifier', true); // raw binary
$memo = base64_encode($hashBytes);
$uri = $uriScheme->generatePayOperationURI(
    destinationAccountId: 'GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV',
    memo: $memo,
    memoType: 'MEMO_HASH'
);
```

---

## Generate Transaction URI

`generateSignTransactionURI()` creates a `web+stellar:tx?` URI that asks a wallet to sign a specific pre-built transaction.

### Minimum (XDR only)

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();
$xdrBase64 = $transaction->toEnvelopeXdrBase64(); // from a built Transaction object

$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: $xdrBase64
);
echo $uri . PHP_EOL;
// web+stellar:tx?xdr=AAAAAgAAAAD...
```

### Build transaction then generate URI

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;

$sdk = StellarSDK::getTestNetInstance();

$sourceKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$sourceAccount = $sdk->requestAccount($sourceKeyPair->getAccountId());

$setOptionsOp = (new SetOptionsOperationBuilder())
    ->setHomeDomain('www.example.com')
    ->build();

$transaction = (new TransactionBuilder($sourceAccount))
    ->addOperation($setOptionsOp)
    ->build();

$uriScheme = new URIScheme();
$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: $transaction->toEnvelopeXdrBase64()
);

echo $uri . PHP_EOL;
```

### With all tx parameters

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();

$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: $xdrBase64,
    replace: 'sourceAccount:X,operations[0].destination:Y;X:Account to pay fees from,Y:Recipient', // SEP-11 Txrep replacement spec
    callback: 'url:https://multisig.example.com/collect',  // must be prefixed with "url:"
    publicKey: 'GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV', // which account should sign
    chain: 'web+stellar:tx?xdr=AAAA...&origin_domain=originator.com&signature=...', // prior URI that triggered this one; max 7 levels deep
    message: 'Please sign to update your home domain',  // max 300 chars
    networkPassphrase: Network::testnet()->getNetworkPassphrase(), // omit for public network
    originDomain: 'example.com',           // requires signURI() call after generation
    signature: null                        // leave null; signURI() appends this
);

echo $uri . PHP_EOL;
```

---

## Sign a URI

`signURI()` appends a cryptographic `signature` parameter to the URI. The signature proves the URI originated from the domain in `origin_domain`. The corresponding public key must be published as `URI_REQUEST_SIGNING_KEY` in the domain's `stellar.toml`.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();

// Generate URI with origin_domain FIRST (signURI requires it to be present)
$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: $xdrBase64,
    originDomain: 'example.com'
);

// Sign with the keypair whose public key is in stellar.toml as URI_REQUEST_SIGNING_KEY
$signerKeyPair = KeyPair::fromSeed(getenv('URI_SIGNING_SEED'));
$signedUri = $uriScheme->signURI($uri, $signerKeyPair);

echo $signedUri . PHP_EOL;
// web+stellar:tx?xdr=...&origin_domain=example.com&signature=bIZ53bPK...
```

`signURI()` returns the full signed URI string. It throws `RuntimeException` if the internal signature verification fails (this is a safety check, not a normal failure path).

**Pay URI signing works identically:**

```php
$uri = $uriScheme->generatePayOperationURI(
    destinationAccountId: 'GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV',
    amount: '50',
    originDomain: 'example.com'
);
$signedUri = $uriScheme->signURI($uri, $signerKeyPair);
```

---

## Validate a URI

`checkUIRSchemeIsValid()` (note: **UIR** typo in method name, maintained for backward compatibility) validates a signed URI by:
1. Checking `origin_domain` exists and is a valid FQDN
2. Checking `signature` exists
3. Fetching `stellar.toml` from the origin domain (makes HTTP request)
4. Extracting `URI_REQUEST_SIGNING_KEY` from the TOML
5. Cryptographically verifying the signature

Returns `true` on success; throws `URISchemeError` on any failure.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;
use Soneso\StellarSDK\SEP\URIScheme\URISchemeError;

$uriScheme = new URIScheme();
$uri = 'web+stellar:tx?xdr=...&origin_domain=example.com&signature=...';

try {
    $isValid = $uriScheme->checkUIRSchemeIsValid($uri);
    // Only trust origin_domain after successful validation
    $originDomain = $uriScheme->getParameterValue(URIScheme::originDomainParameterName, $uri);
    echo "Verified request from: {$originDomain}" . PHP_EOL;

} catch (URISchemeError $e) {
    switch ($e->getCode()) {
        case URISchemeError::missingOriginDomain:   // 2
            echo "No origin_domain — unsigned/untrusted URI" . PHP_EOL;
            break;
        case URISchemeError::invalidOriginDomain:   // 1
            echo "origin_domain is not a valid FQDN" . PHP_EOL;
            break;
        case URISchemeError::missingSignature:       // 3
            echo "origin_domain present but signature missing" . PHP_EOL;
            break;
        case URISchemeError::tomlNotFoundOrInvalid: // 4
            echo "Could not fetch stellar.toml from origin domain" . PHP_EOL;
            break;
        case URISchemeError::tomlSignatureMissing:  // 5
            echo "stellar.toml has no URI_REQUEST_SIGNING_KEY" . PHP_EOL;
            break;
        case URISchemeError::invalidSignature:       // 0
            echo "Signature verification failed — possible tampering" . PHP_EOL;
            break;
    }
}
```

---

## Sign and Submit a Transaction

`signAndSubmitTransaction()` extracts the transaction from a `web+stellar:tx?` URI, signs it with the provided keypair, and submits it either to a callback URL or directly to the Stellar network.

- If `callback` parameter is present and starts with `url:`, POSTs the signed XDR to that URL.
- Otherwise, submits directly to the Stellar network.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();
$uri = 'web+stellar:tx?xdr=AAAAAgAAAAD...';

$signerKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));

try {
    $response = $uriScheme->signAndSubmitTransaction(
        url: $uri,
        signerKeyPair: $signerKeyPair,
        network: Network::testnet()  // defaults to public network if null
    );

    if ($response->getSubmitTransactionResponse() !== null) {
        // Submitted directly to Stellar network
        $txResponse = $response->getSubmitTransactionResponse();
        if ($txResponse->isSuccessful()) {
            echo "Transaction successful! Hash: " . $txResponse->getHash() . PHP_EOL;
        } else {
            $codes = $txResponse->getExtras()->getResultCodes();
            echo "Failed: " . $codes->getTransactionResultCode() . PHP_EOL;
            foreach ($codes->getOperationsResultCodes() as $i => $opCode) {
                echo "  Operation {$i}: {$opCode}" . PHP_EOL;
            }
        }
    } elseif ($response->getCallBackResponse() !== null) {
        // Submitted to callback URL
        $httpResponse = $response->getCallBackResponse();
        echo "Callback status: " . $httpResponse->getStatusCode() . PHP_EOL;
        echo "Callback body: " . $httpResponse->getBody()->getContents() . PHP_EOL;
    }

} catch (HorizonRequestException $e) {
    echo "Horizon error: " . $e->getMessage() . PHP_EOL;
} catch (\GuzzleHttp\Exception\GuzzleException $e) {
    echo "HTTP error posting to callback: " . $e->getMessage() . PHP_EOL;
} catch (\InvalidArgumentException $e) {
    echo "Invalid URI (missing or bad xdr parameter): " . $e->getMessage() . PHP_EOL;
}
```

---

## Extract URI Parameters

`getParameterValue()` extracts any query parameter from a SEP-07 URI. Returns `null` if not present.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();
$uri = 'web+stellar:pay?destination=GDGUF4SC...&amount=100&memo=order-123&msg=Payment';

$destination = $uriScheme->getParameterValue(URIScheme::destinationParameterName, $uri); // "GDGUF4SC..."
$amount      = $uriScheme->getParameterValue(URIScheme::amountParameterName, $uri);      // "100"
$memo        = $uriScheme->getParameterValue(URIScheme::memoParameterName, $uri);        // "order-123"
$message     = $uriScheme->getParameterValue(URIScheme::messageParameterName, $uri);     // "Payment"
$callback    = $uriScheme->getParameterValue(URIScheme::callbackParameterName, $uri);    // null (not in URI)

// Also works on tx URIs
$txUri = 'web+stellar:tx?xdr=AAAA...&network_passphrase=Test+SDF+Network+%3B+September+2015';
$xdr              = $uriScheme->getParameterValue(URIScheme::xdrParameterName, $txUri);
$networkPhrase    = $uriScheme->getParameterValue(URIScheme::networkPassphraseParameterName, $txUri);
$pubkey           = $uriScheme->getParameterValue(URIScheme::publicKeyParameterName, $txUri); // null if absent
$originDomain     = $uriScheme->getParameterValue(URIScheme::originDomainParameterName, $txUri);
$signature        = $uriScheme->getParameterValue(URIScheme::signatureParameterName, $txUri);
```

You can also pass the raw string parameter name instead of the constant:

```php
$xdr = $uriScheme->getParameterValue('xdr', $txUri);
```

---

## SubmitUriSchemeTransactionResponse

Returned by `signAndSubmitTransaction()`. Exactly one of the two properties is non-null, depending on whether a callback URL was present in the URI.

```php
use Soneso\StellarSDK\SEP\URIScheme\SubmitUriSchemeTransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Psr\Http\Message\ResponseInterface;

// Direct network submission path:
$txResponse = $response->getSubmitTransactionResponse(); // SubmitTransactionResponse|null
if ($txResponse !== null) {
    $txResponse->isSuccessful();      // bool
    $txResponse->getHash();           // string — transaction hash
    $txResponse->getLedger();         // int
    $txResponse->getEnvelopeXdr();    // string — signed envelope XDR
    $txResponse->getResultXdr();      // string — result XDR
    $extras = $txResponse->getExtras();
    if ($extras !== null) {
        $extras->getResultCodes()->getTransactionResultCode(); // e.g. "tx_failed"
        $extras->getResultCodes()->getOperationsResultCodes(); // array of op codes
    }
}

// Callback URL submission path:
$callbackResponse = $response->getCallBackResponse(); // ResponseInterface|null (PSR-7)
if ($callbackResponse !== null) {
    $callbackResponse->getStatusCode();             // int — HTTP status
    $callbackResponse->getBody()->getContents();    // string — response body
    $callbackResponse->getHeaders();                // array
}
```

---

## URISchemeError

`URISchemeError` extends `ErrorException`. Thrown by `checkUIRSchemeIsValid()`.

```php
use Soneso\StellarSDK\SEP\URIScheme\URISchemeError;

// Error code constants (int values):
URISchemeError::invalidSignature;    // 0 — Ed25519 signature mismatch
URISchemeError::invalidOriginDomain; // 1 — origin_domain not a valid FQDN
URISchemeError::missingOriginDomain; // 2 — origin_domain parameter absent
URISchemeError::missingSignature;    // 3 — signature parameter absent
URISchemeError::tomlNotFoundOrInvalid; // 4 — stellar.toml not found or malformed
URISchemeError::tomlSignatureMissing;  // 5 — URI_REQUEST_SIGNING_KEY absent from stellar.toml

// Methods:
$e->getCode();     // int — one of the constants above
$e->toString();    // string — human-readable message, e.g. "URISchemeError: invalid Signature"
$e->getMessage();  // string — inherited from ErrorException (may be empty)
```

`toString()` return values by code:

| Code | Constant | `toString()` output |
|------|----------|---------------------|
| 0 | `invalidSignature` | `URISchemeError: invalid Signature` |
| 1 | `invalidOriginDomain` | `URISchemeError: invalid Origin Domain` |
| 2 | `missingOriginDomain` | `URISchemeError: missing Origin Domain` |
| 3 | `missingSignature` | `URISchemeError: missing Signature` |
| 4 | `tomlNotFoundOrInvalid` | `URISchemeError: toml not found or invalid` |
| 5 | `tomlSignatureMissing` | `URISchemeError: Toml Signature Missing` |

---

## Testing with Mock HTTP

`setMockHandlerStack()` replaces the internal Guzzle HTTP client with a mock. Use it to test stellar.toml fetching and callback submissions without making real network requests.

### Mock successful validation

```php
<?php declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$signerKeyPair = KeyPair::random();
$signerAccountId = $signerKeyPair->getAccountId();

$uriScheme = new URIScheme();

// Build and sign a URI
$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: 'AAAAAgAAAAD...',
    originDomain: 'example.com'
);
$signedUri = $uriScheme->signURI($uri, $signerKeyPair);

// Mock stellar.toml that returns our signing key
$tomlContent = 'URI_REQUEST_SIGNING_KEY="' . $signerAccountId . '"';
$mockHandler = new MockHandler([
    new Response(200, [], $tomlContent),
]);
$handlerStack = HandlerStack::create($mockHandler);
$uriScheme->setMockHandlerStack($handlerStack);

$isValid = $uriScheme->checkUIRSchemeIsValid($signedUri); // true
```

### Mock callback submission

```php
<?php declare(strict_types=1);

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;

$uriScheme = new URIScheme();

// Mock callback endpoint returning 200
$mockHandler = new MockHandler([
    new Response(200, [], '{"status":"ok"}'),
]);
$handlerStack = HandlerStack::create($mockHandler);
$uriScheme->setMockHandlerStack($handlerStack);

$signerKeyPair = KeyPair::fromSeed(getenv('STELLAR_SECRET_SEED'));
$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: 'AAAAAgAAAAD...',
    callback: 'url:https://example.com/submit'
);

$response = $uriScheme->signAndSubmitTransaction($uri, $signerKeyPair, Network::testnet());

// callBackResponse is set; submitTransactionResponse is null
echo $response->getCallBackResponse()->getStatusCode() . PHP_EOL; // 200
```

### Mock TOML failures

```php
// Mock 404 -> tomlNotFoundOrInvalid
$mockHandler = new MockHandler([new Response(404, [], 'Not Found')]);

// Mock TOML without URI_REQUEST_SIGNING_KEY -> tomlSignatureMissing
$tomlWithoutKey = "[DOCUMENTATION]\nORG_NAME = \"Example\"\n";
$mockHandler = new MockHandler([new Response(200, [], $tomlWithoutKey)]);

// Mock TOML with wrong key -> invalidSignature
$wrongKey = 'URI_REQUEST_SIGNING_KEY="GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV"';
$mockHandler = new MockHandler([new Response(200, [], $wrongKey)]);
```

---

## Parameter Constants

All constants are on the `URIScheme` class:

| Constant | String value | Used in |
|----------|-------------|---------|
| `URIScheme::uriSchemeName` | `web+stellar:` | URI prefix |
| `URIScheme::signOperation` | `tx?` | tx URI operation string |
| `URIScheme::payOperation` | `pay?` | pay URI operation string |
| `URIScheme::xdrParameterName` | `xdr` | tx URI — transaction XDR |
| `URIScheme::replaceParameterName` | `replace` | tx URI — Txrep field spec |
| `URIScheme::callbackParameterName` | `callback` | both — callback URL |
| `URIScheme::publicKeyParameterName` | `pubkey` | tx URI — required signer |
| `URIScheme::chainParameterName` | `chain` | tx URI — nested URI |
| `URIScheme::messageParameterName` | `msg` | both — user-facing message |
| `URIScheme::networkPassphraseParameterName` | `network_passphrase` | both |
| `URIScheme::originDomainParameterName` | `origin_domain` | both — for signing |
| `URIScheme::signatureParameterName` | `signature` | both — URI signature |
| `URIScheme::destinationParameterName` | `destination` | pay URI |
| `URIScheme::amountParameterName` | `amount` | pay URI |
| `URIScheme::assetCodeParameterName` | `asset_code` | pay URI |
| `URIScheme::assetIssuerParameterName` | `asset_issuer` | pay URI |
| `URIScheme::memoParameterName` | `memo` | pay URI |
| `URIScheme::memoTypeParameterName` | `memo_type` | pay URI |
| `URIScheme::uriSchemePrefix` | `stellar.sep.7 - URI Scheme` | signing payload prefix |

---

## Common Pitfalls

**Method name has a typo — UIR not URI:**

```php
// WRONG: checkURISchemeIsValid() -- method does NOT exist
$uriScheme->checkURISchemeIsValid($uri); // fatal error: Call to undefined method

// CORRECT: checkUIRSchemeIsValid() -- UIR (backward-compatible typo)
$uriScheme->checkUIRSchemeIsValid($uri);
```

**Callback value must be prefixed with "url:":**

```php
// WRONG: raw URL — signAndSubmitTransaction() will NOT route to callback
$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: $xdrBase64,
    callback: 'https://example.com/submit'  // missing "url:" prefix
);
// No callback is sent; falls through to direct network submission

// CORRECT: prefix with "url:"
$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: $xdrBase64,
    callback: 'url:https://example.com/submit'
);
```

**Signing requires origin_domain to already be in the URI:**

```php
// WRONG: generate without origin_domain, then try to sign
$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: $xdrBase64
);
$signedUri = $uriScheme->signURI($uri, $keyPair); // appends signature, but no origin_domain
// checkUIRSchemeIsValid() will throw URISchemeError::missingOriginDomain

// CORRECT: include origin_domain at generation time
$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: $xdrBase64,
    originDomain: 'example.com'
);
$signedUri = $uriScheme->signURI($uri, $keyPair);
```

**Do not pass signature to generateSignTransactionURI — use signURI() instead:**

```php
// WRONG: manually computing and passing signature to generateSignTransactionURI()
$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: $xdrBase64,
    originDomain: 'example.com',
    signature: $myComputedSignature  // incorrect — will produce wrong payload
);

// CORRECT: generate URI first, then sign with signURI()
$uri = $uriScheme->generateSignTransactionURI(
    transactionEnvelopeXdrBase64: $xdrBase64,
    originDomain: 'example.com'
);
$signedUri = $uriScheme->signURI($uri, $keyPair); // handles payload construction internally
```

**getParameterValue() parameter order — name first, URL second:**

```php
// WRONG: URL first
$value = $uriScheme->getParameterValue($uri, 'xdr'); // null (swapped args)

// CORRECT: parameter name first, URL second
$value = $uriScheme->getParameterValue('xdr', $uri);
// or using constants:
$value = $uriScheme->getParameterValue(URIScheme::xdrParameterName, $uri);
```

**signAndSubmitTransaction() defaults to public network when network is null:**

```php
// WRONG: omitting network for testnet transactions
$response = $uriScheme->signAndSubmitTransaction($uri, $keyPair);
// Submits to PUBLIC network — transactions will fail with tx_bad_seq or be lost

// CORRECT: always pass the network explicitly
$response = $uriScheme->signAndSubmitTransaction($uri, $keyPair, Network::testnet());
```

**URISchemeError::getCode() not getMessage() for error identification:**

```php
// WRONG: getMessage() returns empty string for URISchemeError
if ($e->getMessage() === 'missing Signature') { ... } // never matches

// CORRECT: getCode() returns the integer constant
if ($e->getCode() === URISchemeError::missingSignature) { ... }

// ALSO CORRECT: toString() returns the human-readable description
echo $e->toString(); // "URISchemeError: missing Signature"
```
