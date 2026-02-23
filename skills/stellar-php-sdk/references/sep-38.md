# SEP-38: Anchor RFQ API

**Purpose:** Get exchange quotes between Stellar assets and off-chain assets for use in SEP-6, SEP-24, and SEP-31 flows.
**Prerequisites:** JWT from SEP-10 required for `postQuote()` and `getQuote()`; optional for `info()`, `prices()`, and `price()`
**SDK Namespace:** `Soneso\StellarSDK\SEP\Quote`

## Table of Contents

- [Quick Start](#quick-start)
- [Creating the Service](#creating-the-service)
- [Asset Identification Format](#asset-identification-format)
- [GET /info — Available Assets](#get-info--available-assets)
- [GET /prices — Indicative Prices (Multi-Asset)](#get-prices--indicative-prices-multi-asset)
- [GET /price — Indicative Price (Single Pair)](#get-price--indicative-price-single-pair)
- [POST /quote — Request a Firm Quote](#post-quote--request-a-firm-quote)
- [GET /quote/:id — Retrieve a Firm Quote](#get-quoteid--retrieve-a-firm-quote)
- [Response Objects Reference](#response-objects-reference)
- [Fee Objects](#fee-objects)
- [Delivery Methods](#delivery-methods)
- [Error Handling](#error-handling)
- [Price Formulas](#price-formulas)
- [Common Pitfalls](#common-pitfalls)

---

## Quick Start

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Quote\QuoteService;

// Connect via domain discovery (reads ANCHOR_QUOTE_SERVER from stellar.toml)
$quoteService = QuoteService::fromDomain('anchor.example.com');

// Get available assets
$info = $quoteService->info();
foreach ($info->assets as $asset) {
    echo $asset->asset . PHP_EOL;
}

// Get indicative prices for selling 100 USDC
$prices = $quoteService->prices(
    sellAsset: 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    sellAmount: '100',
);
foreach ($prices->buyAssets as $buyAsset) {
    echo $buyAsset->asset . ' at ' . $buyAsset->price . PHP_EOL;
}
```

---

## Creating the Service

### From domain (recommended)

`QuoteService::fromDomain()` fetches the anchor's `stellar.toml`, reads the `ANCHOR_QUOTE_SERVER` field, and returns a configured `QuoteService`. Throws `Exception` if the toml is missing or `ANCHOR_QUOTE_SERVER` is absent.

```php
<?php declare(strict_types=1);

use Exception;
use Soneso\StellarSDK\SEP\Quote\QuoteService;

try {
    $quoteService = QuoteService::fromDomain('anchor.example.com');
} catch (Exception $e) {
    echo 'Could not load quote service: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
```

Signature:
```
QuoteService::fromDomain(string $domain, ?Client $httpClient = null): QuoteService
```

### With a direct URL

Use when you already know the quote server address.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Quote\QuoteService;

$quoteService = new QuoteService('https://anchor.example.com/sep38');
```

Constructor signature:
```
new QuoteService(string $serviceAddress, ?Client $httpClient = null)
```

### With a custom HTTP client

Pass a Guzzle `Client` for custom timeouts, proxies, or SSL configuration:

```php
<?php declare(strict_types=1);

use GuzzleHttp\Client;
use Soneso\StellarSDK\SEP\Quote\QuoteService;

$httpClient = new Client(['timeout' => 30]);
$quoteService = QuoteService::fromDomain('anchor.example.com', $httpClient);
```

---

## Asset Identification Format

SEP-38 uses a specific string format to identify assets. Always use this format as-is — do not construct `Asset` objects.

| Asset type | Format | Example |
|------------|--------|---------|
| Stellar asset | `stellar:CODE:ISSUER` | `stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN` |
| Fiat currency | `iso4217:CODE` | `iso4217:USD` |
| Fiat (3-letter country variant) | `iso4217:CODE` | `iso4217:BRL` |

```php
// WRONG: passing a Stellar Asset object
// CORRECT: use the string identifier format
$sellAsset = 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN';
$buyAsset  = 'iso4217:USD';
```

---

## GET /info — Available Assets

Returns all Stellar and off-chain assets available for trading, with optional delivery methods and country restrictions.

Authentication is optional. Pass a JWT to receive personalized results.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38Asset;
use Soneso\StellarSDK\SEP\Quote\SEP38SellDeliveryMethod;
use Soneso\StellarSDK\SEP\Quote\SEP38BuyDeliveryMethod;

$quoteService = QuoteService::fromDomain('anchor.example.com');

// JWT is optional — pass null for unauthenticated call
$info = $quoteService->info($jwtToken);

foreach ($info->assets as $asset) {
    // $asset is SEP38Asset
    echo 'Asset: ' . $asset->asset . PHP_EOL;

    // Country codes for fiat assets — null if no restriction
    if ($asset->countryCodes !== null) {
        echo '  Countries: ' . implode(', ', $asset->countryCodes) . PHP_EOL;
    }

    // Methods for delivering off-chain assets TO the anchor
    if ($asset->sellDeliveryMethods !== null) {
        foreach ($asset->sellDeliveryMethods as $method) {
            // $method is SEP38SellDeliveryMethod
            echo '  Sell via: ' . $method->name . ' — ' . $method->description . PHP_EOL;
        }
    }

    // Methods for receiving off-chain assets FROM the anchor
    if ($asset->buyDeliveryMethods !== null) {
        foreach ($asset->buyDeliveryMethods as $method) {
            // $method is SEP38BuyDeliveryMethod
            echo '  Buy via: ' . $method->name . ' — ' . $method->description . PHP_EOL;
        }
    }
}
```

Method signature:
```
info(?string $jwt = null): SEP38InfoResponse
throws: SEP38BadRequestException, SEP38UnknownResponseException, GuzzleException
```

### SEP38InfoResponse properties

| Property | Type | Description |
|----------|------|-------------|
| `$assets` | `array<SEP38Asset>` | All supported assets |

### SEP38Asset properties

| Property | Type | Description |
|----------|------|-------------|
| `$asset` | `string` | Asset identifier in SEP-38 format |
| `$sellDeliveryMethods` | `array<SEP38SellDeliveryMethod>\|null` | Methods for delivering this asset to the anchor; null if none |
| `$buyDeliveryMethods` | `array<SEP38BuyDeliveryMethod>\|null` | Methods for receiving this asset from the anchor; null if none |
| `$countryCodes` | `array<string>\|null` | ISO country codes where this asset is available; null if unrestricted |

---

## GET /prices — Indicative Prices (Multi-Asset)

Returns indicative (non-binding) prices for all tradeable assets when given a `sellAsset` and `sellAmount`. Use this to show users what they can receive before committing to a specific pair.

Authentication is optional.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38BuyAsset;

$quoteService = QuoteService::fromDomain('anchor.example.com');

$prices = $quoteService->prices(
    sellAsset: 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    sellAmount: '100',
    jwt: $jwtToken, // optional
);

// $prices->buyAssets is array<SEP38BuyAsset>|null
if ($prices->buyAssets !== null) {
    foreach ($prices->buyAssets as $buyAsset) {
        echo $buyAsset->asset . PHP_EOL;    // e.g. "iso4217:BRL"
        echo $buyAsset->price . PHP_EOL;    // e.g. "0.18" (price of one sell-asset unit)
        echo $buyAsset->decimals . PHP_EOL; // int, e.g. 2
    }
}
```

### With delivery method and country code

For off-chain assets, providing delivery method and country code yields more accurate indicative prices:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Quote\QuoteService;

$quoteService = QuoteService::fromDomain('anchor.example.com');

// What USDC can I buy for 500 BRL sent via PIX in Brazil?
$prices = $quoteService->prices(
    sellAsset: 'iso4217:BRL',
    sellAmount: '500',
    sellDeliveryMethod: 'PIX',  // name from info()->assets[n]->sellDeliveryMethods
    countryCode: 'BRA',         // ISO 3166-1 alpha-3 or ISO 3166-2 code
    jwt: $jwtToken,
);
```

Method signature:
```
prices(
    string  $sellAsset,
    string  $sellAmount,
    ?string $sellDeliveryMethod = null,
    ?string $buyDeliveryMethod = null,
    ?string $countryCode = null,
    ?string $jwt = null,
): SEP38PricesResponse
throws: SEP38BadRequestException, SEP38UnknownResponseException, GuzzleException
```

### SEP38PricesResponse properties

| Property | Type | Description |
|----------|------|-------------|
| `$buyAssets` | `array<SEP38BuyAsset>\|null` | Assets available to buy when a `sellAsset` was specified |
| `$sellAssets` | `array<SEP38SellAsset>\|null` | Assets available to sell when a `buyAsset` was specified (SEP-38 v2.3.0+) |

### SEP38BuyAsset properties

| Property | Type | Description |
|----------|------|-------------|
| `$asset` | `string` | Asset identifier |
| `$price` | `string` | Indicative price of one sell-asset unit in terms of this buy asset |
| `$decimals` | `int` | Decimal precision for this asset |

### SEP38SellAsset properties

| Property | Type | Description |
|----------|------|-------------|
| `$asset` | `string` | Asset identifier |
| `$price` | `string` | Indicative price of one buy-asset unit in terms of this sell asset |
| `$decimals` | `int` | Decimal precision for this asset |

> **Note:** The `prices()` method only accepts `sellAsset`/`sellAmount` parameters. The SEP-38 v2.3.0 `buyAsset`/`buyAmount` query option is not yet available in this method. Use `price()` for reverse lookups.

---

## GET /price — Indicative Price (Single Pair)

Returns an indicative price for a specific asset pair with fee details. You must provide either `sellAmount` or `buyAmount`, but not both. Providing both or neither throws `InvalidArgumentException`.

Authentication is optional.

```php
<?php declare(strict_types=1);

use InvalidArgumentException;
use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38PriceResponse;

$quoteService = QuoteService::fromDomain('anchor.example.com');

// Query by sell amount: how much BRL do I get for 100 USDC?
$price = $quoteService->price(
    context: 'sep6',    // 'sep6', 'sep24', or 'sep31'
    sellAsset: 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    buyAsset: 'iso4217:BRL',
    sellAmount: '100',  // provide sellAmount OR buyAmount, not both
    jwt: $jwtToken,
);

echo 'Total price (with fees): ' . $price->totalPrice . PHP_EOL;
echo 'Price (without fees):    ' . $price->price . PHP_EOL;
echo 'Sell amount:             ' . $price->sellAmount . PHP_EOL;
echo 'Buy amount:              ' . $price->buyAmount . PHP_EOL;
echo 'Fee total:               ' . $price->fee->total . ' ' . $price->fee->asset . PHP_EOL;
```

### Query by buy amount

If you know the desired receive amount, use `buyAmount` instead:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Quote\QuoteService;

$quoteService = QuoteService::fromDomain('anchor.example.com');

// How much USDC do I need to sell to receive 500 BRL?
$price = $quoteService->price(
    context: 'sep6',
    sellAsset: 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    buyAsset: 'iso4217:BRL',
    buyAmount: '500', // provide buyAmount when you know the target receive amount
    jwt: $jwtToken,
);

echo 'You need to sell: ' . $price->sellAmount . ' USDC' . PHP_EOL;
echo 'You will receive: ' . $price->buyAmount . ' BRL' . PHP_EOL;
```

### With delivery methods

For off-chain assets, specify delivery methods for more accurate quotes:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Quote\QuoteService;

$quoteService = QuoteService::fromDomain('anchor.example.com');

$price = $quoteService->price(
    context: 'sep31',
    sellAsset: 'iso4217:BRL',
    buyAsset: 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    sellAmount: '500',
    sellDeliveryMethod: 'PIX',
    countryCode: 'BRA',
    jwt: $jwtToken,
);
```

### Reading fee details

The response always includes a `SEP38Fee` object. The optional `details` array contains itemized fee components:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38Fee;
use Soneso\StellarSDK\SEP\Quote\SEP38FeeDetails;

$price = $quoteService->price(
    context: 'sep6',
    sellAsset: 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    buyAsset: 'iso4217:BRL',
    sellAmount: '100',
);

$fee = $price->fee; // SEP38Fee

echo 'Fee total: ' . $fee->total . ' (' . $fee->asset . ')' . PHP_EOL;

if ($fee->details !== null) {
    foreach ($fee->details as $detail) {
        // $detail is SEP38FeeDetails
        echo '  - ' . $detail->name . ': ' . $detail->amount;
        if ($detail->description !== null) {
            echo ' (' . $detail->description . ')';
        }
        echo PHP_EOL;
    }
}
```

Method signature:
```
price(
    string  $context,
    string  $sellAsset,
    string  $buyAsset,
    ?string $sellAmount = null,
    ?string $buyAmount = null,
    ?string $sellDeliveryMethod = null,
    ?string $buyDeliveryMethod = null,
    ?string $countryCode = null,
    ?string $jwt = null,
): SEP38PriceResponse
throws: InvalidArgumentException, SEP38BadRequestException, SEP38UnknownResponseException, GuzzleException
```

### SEP38PriceResponse properties

| Property | Type | Description |
|----------|------|-------------|
| `$totalPrice` | `string` | Total price including fees: `sell_amount = total_price * buy_amount` |
| `$price` | `string` | Exchange rate without fees |
| `$sellAmount` | `string` | Amount of the sell asset |
| `$buyAmount` | `string` | Amount of the buy asset |
| `$fee` | `SEP38Fee` | Fee structure (always present) |

---

## POST /quote — Request a Firm Quote

A firm quote guarantees the exchange rate for a limited time. Authentication is **required** — the `$jwt` parameter is non-nullable. Either `sellAmount` or `buyAmount` must be set in the request, but not both. Providing both or neither throws `InvalidArgumentException`.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38PostQuoteRequest;
use Soneso\StellarSDK\SEP\Quote\SEP38QuoteResponse;

$quoteService = QuoteService::fromDomain('anchor.example.com');

$request = new SEP38PostQuoteRequest(
    context: 'sep24',   // 'sep6', 'sep24', or 'sep31'
    sellAsset: 'iso4217:USD',
    buyAsset: 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    sellAmount: '100',  // OR buyAmount — not both
);

// JWT is required (non-nullable)
$quote = $quoteService->postQuote($request, $jwtToken);

echo 'Quote ID:    ' . $quote->id . PHP_EOL;
echo 'Expires at:  ' . $quote->expiresAt->format('Y-m-d H:i:s') . PHP_EOL;
echo 'Total price: ' . $quote->totalPrice . PHP_EOL;
echo 'Price:       ' . $quote->price . PHP_EOL;
echo 'Sell:        ' . $quote->sellAmount . ' ' . $quote->sellAsset . PHP_EOL;
echo 'Buy:         ' . $quote->buyAmount . ' ' . $quote->buyAsset . PHP_EOL;
echo 'Fee:         ' . $quote->fee->total . ' ' . $quote->fee->asset . PHP_EOL;
```

### Request with expiration preference

Use `expireAfter` to request a minimum quote validity period. The anchor may grant a longer expiration but will not give a shorter one:

```php
<?php declare(strict_types=1);

use DateTime;
use DateInterval;
use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38PostQuoteRequest;

$quoteService = QuoteService::fromDomain('anchor.example.com');

$expireAfter = new DateTime();
$expireAfter->add(new DateInterval('PT1H')); // request at least 1 hour validity

$request = new SEP38PostQuoteRequest(
    context: 'sep24',
    sellAsset: 'iso4217:USD',
    buyAsset: 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    sellAmount: '100',
    expireAfter: $expireAfter, // DateTime|null
);

$quote = $quoteService->postQuote($request, $jwtToken);
echo 'Valid until: ' . $quote->expiresAt->format('c') . PHP_EOL;
```

### Request with delivery methods

Include delivery method names (from `info()`) when exchanging off-chain assets:

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38PostQuoteRequest;

$quoteService = QuoteService::fromDomain('anchor.example.com');

$request = new SEP38PostQuoteRequest(
    context: 'sep6',
    sellAsset: 'iso4217:BRL',
    buyAsset: 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    buyAmount: '100',
    sellDeliveryMethod: 'PIX',  // name from info()->assets[n]->sellDeliveryMethods
    countryCode: 'BRA',
);

$quote = $quoteService->postQuote($request, $jwtToken);

// Delivery methods are echoed back in the response when provided
if ($quote->sellDeliveryMethod !== null) {
    echo 'Sell via: ' . $quote->sellDeliveryMethod . PHP_EOL;
}
if ($quote->buyDeliveryMethod !== null) {
    echo 'Buy via: ' . $quote->buyDeliveryMethod . PHP_EOL;
}
```

Method signature:
```
postQuote(SEP38PostQuoteRequest $request, string $jwt): SEP38QuoteResponse
throws: InvalidArgumentException, SEP38BadRequestException,
        SEP38PermissionDeniedException, SEP38UnknownResponseException, GuzzleException
```

### SEP38PostQuoteRequest constructor

```
new SEP38PostQuoteRequest(
    string    $context,
    string    $sellAsset,
    string    $buyAsset,
    ?string   $sellAmount = null,
    ?string   $buyAmount = null,
    ?DateTime $expireAfter = null,
    ?string   $sellDeliveryMethod = null,
    ?string   $buyDeliveryMethod = null,
    ?string   $countryCode = null,
)
```

All constructor parameters are public properties accessible directly (e.g., `$request->sellAmount`). The `toJson()` method serializes it for the HTTP request body.

---

## GET /quote/:id — Retrieve a Firm Quote

Retrieves a previously-created firm quote by its ID. Authentication is **required** — the `$jwt` parameter is non-nullable.

```php
<?php declare(strict_types=1);

use DateTime;
use Soneso\StellarSDK\SEP\Quote\QuoteService;

$quoteService = QuoteService::fromDomain('anchor.example.com');

$quoteId = 'de762cda-a193-4961-861e-57b31fed6eb3'; // from postQuote() response
$quote = $quoteService->getQuote($quoteId, $jwtToken);

echo 'Quote ID:   ' . $quote->id . PHP_EOL;
echo 'Expires at: ' . $quote->expiresAt->format('Y-m-d H:i:s') . PHP_EOL;
echo 'Still valid: ' . ($quote->expiresAt > new DateTime() ? 'Yes' : 'No') . PHP_EOL;
echo 'Sell: ' . $quote->sellAmount . ' ' . $quote->sellAsset . PHP_EOL;
echo 'Buy:  ' . $quote->buyAmount . ' ' . $quote->buyAsset . PHP_EOL;
```

Method signature:
```
getQuote(string $id, string $jwt): SEP38QuoteResponse
throws: SEP38BadRequestException, SEP38PermissionDeniedException,
        SEP38NotFoundException, SEP38UnknownResponseException, GuzzleException
```

### SEP38QuoteResponse properties

| Property | Type | Description |
|----------|------|-------------|
| `$id` | `string` | Unique quote identifier |
| `$expiresAt` | `DateTime` | When this quote expires |
| `$totalPrice` | `string` | Total price including fees |
| `$price` | `string` | Exchange rate without fees |
| `$sellAsset` | `string` | The asset being sold (SEP-38 format) |
| `$sellAmount` | `string` | Amount of the sell asset |
| `$buyAsset` | `string` | The asset being purchased (SEP-38 format) |
| `$buyAmount` | `string` | Amount of the buy asset |
| `$fee` | `SEP38Fee` | Fee structure (always present) |
| `$sellDeliveryMethod` | `string\|null` | Delivery method used for sell asset |
| `$buyDeliveryMethod` | `string\|null` | Delivery method used for buy asset |

---

## Response Objects Reference

### SEP38Fee

Represents the total fee and optional itemized breakdown.

```php
use Soneso\StellarSDK\SEP\Quote\SEP38Fee;
use Soneso\StellarSDK\SEP\Quote\SEP38FeeDetails;

// Properties
$fee->total;    // string — total fee amount
$fee->asset;    // string — asset the fee is charged in (SEP-38 format)
$fee->details;  // array<SEP38FeeDetails>|null — itemized breakdown, null if not provided
```

### SEP38FeeDetails

Represents one line item in a fee breakdown.

```php
use Soneso\StellarSDK\SEP\Quote\SEP38FeeDetails;

// Properties
$detail->name;        // string — fee component name (e.g. "Service fee", "PIX fee")
$detail->amount;      // string — amount for this component
$detail->description; // string|null — optional human-readable explanation
```

---

## Delivery Methods

`SEP38SellDeliveryMethod` and `SEP38BuyDeliveryMethod` are identical in structure. Both have:

| Property | Type | Description |
|----------|------|-------------|
| `$name` | `string` | Identifier used as parameter value (e.g. `"PIX"`, `"ACH"`, `"cash"`) |
| `$description` | `string` | Human-readable description of the delivery method |

Use the `$name` value as the `sellDeliveryMethod` or `buyDeliveryMethod` parameter in `prices()`, `price()`, and `SEP38PostQuoteRequest`.

```php
<?php declare(strict_types=1);

use Soneso\StellarSDK\SEP\Quote\QuoteService;

$quoteService = QuoteService::fromDomain('anchor.example.com');
$info = $quoteService->info($jwtToken);

// Find the BRL asset and list its sell delivery method names
foreach ($info->assets as $asset) {
    if ($asset->asset === 'iso4217:BRL') {
        if ($asset->sellDeliveryMethods !== null) {
            foreach ($asset->sellDeliveryMethods as $method) {
                // Use $method->name as the sellDeliveryMethod parameter
                echo $method->name . ': ' . $method->description . PHP_EOL;
            }
        }
    }
}
```

---

## Error Handling

Always wrap quote service calls in `try-catch` blocks in production. The SDK throws specific exception classes for each HTTP error code:

```php
<?php declare(strict_types=1);

use InvalidArgumentException;
use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38PostQuoteRequest;
use Soneso\StellarSDK\SEP\Quote\SEP38BadRequestException;
use Soneso\StellarSDK\SEP\Quote\SEP38NotFoundException;
use Soneso\StellarSDK\SEP\Quote\SEP38PermissionDeniedException;
use Soneso\StellarSDK\SEP\Quote\SEP38UnknownResponseException;

$quoteService = QuoteService::fromDomain('anchor.example.com');

try {
    $request = new SEP38PostQuoteRequest(
        context: 'sep24',
        sellAsset: 'iso4217:USD',
        buyAsset: 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
        sellAmount: '100',
    );
    $quote = $quoteService->postQuote($request, $jwtToken);
    echo 'Quote ID: ' . $quote->id . PHP_EOL;

} catch (InvalidArgumentException $e) {
    // Both sellAmount and buyAmount provided, or neither provided
    echo 'Invalid request: ' . $e->getMessage() . PHP_EOL;

} catch (SEP38BadRequestException $e) {
    // HTTP 400 — invalid params, unsupported asset pair, unknown context
    echo 'Bad request: ' . $e->getMessage() . PHP_EOL;

} catch (SEP38PermissionDeniedException $e) {
    // HTTP 403 — missing JWT, expired JWT, or user not authorized
    echo 'Permission denied: ' . $e->getMessage() . PHP_EOL;

} catch (SEP38NotFoundException $e) {
    // HTTP 404 — quote ID not found (getQuote only)
    echo 'Quote not found: ' . $e->getMessage() . PHP_EOL;

} catch (SEP38UnknownResponseException $e) {
    // Other HTTP errors (5xx, etc.)
    echo 'Unexpected error: ' . $e->getMessage() . PHP_EOL;
}
```

### Exception reference

| Exception | HTTP Status | Thrown by | Common cause |
|-----------|-------------|-----------|--------------|
| `InvalidArgumentException` | N/A | `price()`, `postQuote()` | Both or neither of `sellAmount`/`buyAmount` provided |
| `SEP38BadRequestException` | 400 | all methods | Invalid asset format, unsupported pair, missing required field |
| `SEP38PermissionDeniedException` | 403 | `postQuote()`, `getQuote()` | Missing or expired JWT, user not authorized |
| `SEP38NotFoundException` | 404 | `getQuote()` | Quote ID doesn't exist or has expired |
| `SEP38UnknownResponseException` | other | all methods | Server error or unexpected response |

All exception classes extend `Exception` and are in the `Soneso\StellarSDK\SEP\Quote` namespace.

---

## Price Formulas

The relationship between price, total_price, amounts, and fees:

```
sell_amount = total_price * buy_amount
```

When the fee is denominated in the **sell** asset:
```
sell_amount - fee.total = price * buy_amount
```

When the fee is denominated in the **buy** asset:
```
sell_amount = price * (buy_amount + fee.total)
```

`total_price` always includes fees. `price` is the raw exchange rate before fees.

---

## Common Pitfalls

**Wrong: providing both sellAmount and buyAmount**

```php
// WRONG: throws InvalidArgumentException at the SDK level — never reaches the server
$price = $quoteService->price(
    context: 'sep6',
    sellAsset: 'iso4217:USD',
    buyAsset: 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    sellAmount: '100',
    buyAmount: '95',  // WRONG: cannot provide both
);

// CORRECT: provide exactly one
$price = $quoteService->price(
    context: 'sep6',
    sellAsset: 'iso4217:USD',
    buyAsset: 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    sellAmount: '100',
);
```

**Wrong: providing neither sellAmount nor buyAmount**

```php
// WRONG: throws InvalidArgumentException — at least one amount is required
$request = new SEP38PostQuoteRequest(
    context: 'sep24',
    sellAsset: 'iso4217:USD',
    buyAsset: 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    // no sellAmount, no buyAmount
);
$quoteService->postQuote($request, $jwtToken); // throws InvalidArgumentException

// CORRECT: provide exactly one amount
$request = new SEP38PostQuoteRequest(
    context: 'sep24',
    sellAsset: 'iso4217:USD',
    buyAsset: 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    sellAmount: '100',
);
```

**Wrong: missing JWT for postQuote and getQuote**

```php
// WRONG: $jwt parameter is string (non-nullable) for postQuote and getQuote
// Passing null causes a PHP TypeError at runtime
$quoteService->postQuote($request, null);  // TypeError
$quoteService->getQuote('quote-id', null); // TypeError

// CORRECT: always authenticate first via SEP-10
$jwtToken = $webAuth->jwtToken($accountId, [$keyPair]); // string
$quote = $quoteService->postQuote($request, $jwtToken);
```

**Wrong: using totalPrice as the exchange rate**

```php
// WRONG: totalPrice includes fees — it is not the raw exchange rate
$exchangeRate = $price->totalPrice; // misleading for display purposes

// CORRECT: use price for the raw rate, totalPrice for the effective sell/buy calculation
$rawRate = $price->price;       // exchange rate without fees
$effectiveRate = $price->totalPrice; // rate that satisfies sell_amount = total_price * buy_amount
```

**Wrong: constructing asset identifiers manually from Asset objects**

```php
use Soneso\StellarSDK\Asset;

// WRONG: SEP-38 uses its own string format, not the Stellar SDK Asset class
$asset = Asset::createNonNativeAsset('USDC', 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');
$prices = $quoteService->prices(sellAsset: $asset); // TypeError: expects string

// CORRECT: use the SEP-38 string format directly
$prices = $quoteService->prices(
    sellAsset: 'stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
    sellAmount: '100',
);
```

**Wrong: treating expiresAt as a string**

```php
// WRONG: expiresAt is a DateTime object, not a string
echo $quote->expiresAt; // TypeError or fatal

// CORRECT: format it
echo $quote->expiresAt->format('Y-m-d H:i:s') . PHP_EOL;
echo $quote->expiresAt->format('c') . PHP_EOL; // ISO 8601

// Check expiry
$isValid = $quote->expiresAt > new DateTime();
```

**Wrong: assuming fee->details is always present**

```php
// WRONG: details is null when the anchor does not provide an itemized breakdown
foreach ($price->fee->details as $detail) { /* may throw on null */ }

// CORRECT: null-check first
if ($price->fee->details !== null) {
    foreach ($price->fee->details as $detail) {
        echo $detail->name . ': ' . $detail->amount . PHP_EOL;
    }
}
```

---

## Related SEPs

- [SEP-10](sep-10.md) — Web Authentication (provides JWT for authenticated endpoints)
- [SEP-45](sep-45.md) — Web Authentication for Soroban contract accounts
- [SEP-01](sep-01.md) — stellar.toml (provides `ANCHOR_QUOTE_SERVER` consumed by `QuoteService::fromDomain()`)
- [SEP-06](sep-06.md) — Deposit/Withdrawal API (use `context: 'sep6'`)
- [SEP-24](sep-24.md) — Interactive Deposit/Withdrawal (use `context: 'sep24'`)
- [SEP-31](sep-31.md) — Cross-Border Payments (use `context: 'sep31'`)
