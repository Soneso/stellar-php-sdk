# SEP-38: Anchor RFQ API

Get exchange quotes between Stellar assets and off-chain assets (like fiat currencies).

## Overview

SEP-38 enables anchors to provide price quotes for asset exchanges. Use it when you need to:

- Show users estimated conversion rates before a deposit or withdrawal
- Lock in a firm exchange rate for a transaction
- Get available trading pairs from an anchor

Quotes come in two types:
- **Indicative quotes**: Estimated prices that may change (via `GET /prices` and `GET /price`)
- **Firm quotes**: Locked prices valid for a limited time (via `POST /quote`)

SEP-38 is used alongside SEP-6, SEP-24, or SEP-31 for the actual asset transfer.

## Quick example

This example shows how to connect to an anchor's quote service and fetch available assets and indicative prices:

```php
<?php

use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38Asset;
use Soneso\StellarSDK\SEP\Quote\SEP38BuyAsset;

// Connect to anchor's quote service using stellar.toml discovery
$quoteService = QuoteService::fromDomain("anchor.example.com");

// Get available assets for trading
$info = $quoteService->info();
foreach ($info->assets as $asset) {
    echo $asset->asset . "\n";
}

// Get indicative prices for selling 100 USD
$prices = $quoteService->prices(
    sellAsset: "iso4217:USD",
    sellAmount: "100"
);

foreach ($prices->buyAssets as $buyAsset) {
    echo "Buy " . $buyAsset->asset . " at price " . $buyAsset->price . "\n";
}
```

## Detailed usage

### Creating the service

The `QuoteService` class has methods for all SEP-38 endpoints. You can create an instance by domain discovery or with a direct URL.

**From stellar.toml (recommended):**

The service address is automatically resolved from the anchor's `ANCHOR_QUOTE_SERVER` field in stellar.toml:

```php
<?php

use Soneso\StellarSDK\SEP\Quote\QuoteService;

$quoteService = QuoteService::fromDomain("anchor.example.com");
```

**With a direct URL:**

If you already know the quote server URL, you can instantiate the service directly:

```php
<?php

use Soneso\StellarSDK\SEP\Quote\QuoteService;

$quoteService = new QuoteService("https://anchor.example.com/sep38");
```

**With a custom HTTP client:**

For advanced use cases, you can provide your own Guzzle HTTP client:

```php
<?php

use GuzzleHttp\Client;
use Soneso\StellarSDK\SEP\Quote\QuoteService;

$httpClient = new Client(['timeout' => 30]);
$quoteService = QuoteService::fromDomain("anchor.example.com", $httpClient);
```

### Asset identification format

SEP-38 uses a specific format for identifying assets in requests and responses:

| Type | Format | Example |
|------|--------|---------|
| Stellar asset | `stellar:CODE:ISSUER` | `stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN` |
| Fiat currency | `iso4217:CODE` | `iso4217:USD` |

### Getting available assets (GET /info)

The `info()` method returns all Stellar and off-chain assets available for trading, along with their supported delivery methods and country restrictions:

```php
<?php

use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38Asset;
use Soneso\StellarSDK\SEP\Quote\SEP38SellDeliveryMethod;
use Soneso\StellarSDK\SEP\Quote\SEP38BuyDeliveryMethod;

$quoteService = QuoteService::fromDomain("anchor.example.com");

// Authentication is optional for this endpoint
$jwtToken = null; // Or obtain via SEP-10/SEP-45 for personalized results

$info = $quoteService->info($jwtToken);

foreach ($info->assets as $asset) {
    echo "Asset: " . $asset->asset . "\n";
    
    // Check country restrictions for fiat assets
    if ($asset->countryCodes !== null) {
        echo "  Available in: " . implode(", ", $asset->countryCodes) . "\n";
    }
    
    // Check delivery methods for selling to the anchor
    if ($asset->sellDeliveryMethods !== null) {
        echo "  Sell delivery methods:\n";
        foreach ($asset->sellDeliveryMethods as $method) {
            echo "    - " . $method->name . ": " . $method->description . "\n";
        }
    }
    
    // Check delivery methods for receiving from the anchor
    if ($asset->buyDeliveryMethods !== null) {
        echo "  Buy delivery methods:\n";
        foreach ($asset->buyDeliveryMethods as $method) {
            echo "    - " . $method->name . ": " . $method->description . "\n";
        }
    }
}
```

### Getting indicative prices (GET /prices)

The `prices()` method returns indicative (non-binding) exchange rates for multiple assets. Use this to show users what they can receive for a given amount.

> **Note:** The SDK currently supports querying by `sellAsset` only. The `buyAsset` query option introduced in SEP-38 v2.3.0+ is not yet implemented.

```php
<?php

use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38BuyAsset;

$quoteService = QuoteService::fromDomain("anchor.example.com");

// What can I buy for 100 USD?
$prices = $quoteService->prices(
    sellAsset: "iso4217:USD",
    sellAmount: "100",
    jwt: $jwtToken // Optional
);

foreach ($prices->buyAssets as $buyAsset) {
    echo "Asset: " . $buyAsset->asset . "\n";
    echo "Price: " . $buyAsset->price . "\n";
    echo "Decimals: " . $buyAsset->decimals . "\n";
}
```

**With delivery method and country code:**

For off-chain assets, you can specify delivery methods and country codes to get more accurate pricing:

```php
<?php

use Soneso\StellarSDK\SEP\Quote\QuoteService;

$quoteService = QuoteService::fromDomain("anchor.example.com");

// What USDC can I buy for 500 BRL via PIX in Brazil?
$prices = $quoteService->prices(
    sellAsset: "iso4217:BRL",
    sellAmount: "500",
    sellDeliveryMethod: "PIX",
    countryCode: "BR",
    jwt: $jwtToken
);

foreach ($prices->buyAssets as $buyAsset) {
    echo $buyAsset->asset . " at " . $buyAsset->price . "\n";
}
```

### Getting a price for a specific pair (GET /price)

The `price()` method returns an indicative price for a specific asset pair with detailed fee information. You must provide either `sellAmount` or `buyAmount`, but not both:

```php
<?php

use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38PriceResponse;

$quoteService = QuoteService::fromDomain("anchor.example.com");

// How much USDC do I get for 100 USD? (SEP-6 deposit context)
$price = $quoteService->price(
    context: "sep6",
    sellAsset: "iso4217:USD",
    buyAsset: "stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
    sellAmount: "100",
    jwt: $jwtToken
);

echo "Total price (with fees): " . $price->totalPrice . "\n";
echo "Price (without fees): " . $price->price . "\n";
echo "Sell amount: " . $price->sellAmount . "\n";
echo "Buy amount: " . $price->buyAmount . "\n";
echo "Fee total: " . $price->fee->total . " " . $price->fee->asset . "\n";
```

**Query by buy amount instead:**

If you know how much you want to receive, specify `buyAmount` instead:

```php
<?php

use Soneso\StellarSDK\SEP\Quote\QuoteService;

$quoteService = QuoteService::fromDomain("anchor.example.com");

// How much USD do I need to get 50 USDC?
$price = $quoteService->price(
    context: "sep6",
    sellAsset: "iso4217:USD",
    buyAsset: "stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
    buyAmount: "50",
    jwt: $jwtToken
);

echo "You need to sell: " . $price->sellAmount . " USD\n";
echo "You will receive: " . $price->buyAmount . " USDC\n";
```

**With delivery methods:**

Specify delivery methods for more accurate quotes when working with off-chain assets:

```php
<?php

use Soneso\StellarSDK\SEP\Quote\QuoteService;

$quoteService = QuoteService::fromDomain("anchor.example.com");

// BRL to USDC via PIX in Brazil, for SEP-31 cross-border payment
$price = $quoteService->price(
    context: "sep31",
    sellAsset: "iso4217:BRL",
    buyAsset: "stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
    sellAmount: "500",
    sellDeliveryMethod: "PIX",
    countryCode: "BR",
    jwt: $jwtToken
);
```

**Working with fee details:**

The response includes a detailed fee breakdown when provided by the anchor:

```php
<?php

use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38FeeDetails;

$quoteService = QuoteService::fromDomain("anchor.example.com");

$price = $quoteService->price(
    context: "sep6",
    sellAsset: "iso4217:BRL",
    buyAsset: "stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
    sellAmount: "500",
    jwt: $jwtToken
);

echo "Total fee: " . $price->fee->total . " " . $price->fee->asset . "\n";

// Check for detailed fee breakdown
if ($price->fee->details !== null) {
    foreach ($price->fee->details as $detail) {
        echo "  - " . $detail->name . ": " . $detail->amount;
        if ($detail->description !== null) {
            echo " (" . $detail->description . ")";
        }
        echo "\n";
    }
}
```

### Requesting a firm quote (POST /quote)

Firm quotes lock in a guaranteed price for a limited time. Authentication is required. Use the `SEP38PostQuoteRequest` class to build your request:

```php
<?php

use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38PostQuoteRequest;
use Soneso\StellarSDK\SEP\Quote\SEP38QuoteResponse;

$quoteService = QuoteService::fromDomain("anchor.example.com");

// Build the quote request
$request = new SEP38PostQuoteRequest(
    context: "sep24",
    sellAsset: "iso4217:USD",
    buyAsset: "stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
    sellAmount: "100"
);

// Submit the request (JWT is required)
$quote = $quoteService->postQuote($request, $jwtToken);

echo "Quote ID: " . $quote->id . "\n";
echo "Expires at: " . $quote->expiresAt->format('Y-m-d H:i:s') . "\n";
echo "Total price: " . $quote->totalPrice . "\n";
echo "Price (without fees): " . $quote->price . "\n";
echo "You sell: " . $quote->sellAmount . " (" . $quote->sellAsset . ")\n";
echo "You receive: " . $quote->buyAmount . " (" . $quote->buyAsset . ")\n";
```

**With expiration preference:**

You can request a minimum expiration time using the `expireAfter` parameter. The anchor may provide a longer expiration but should not provide a shorter one:

```php
<?php

use DateTime;
use DateInterval;
use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38PostQuoteRequest;

$quoteService = QuoteService::fromDomain("anchor.example.com");

// Request quote valid for at least 1 hour
$expireAfter = new DateTime();
$expireAfter->add(new DateInterval('PT1H'));

$request = new SEP38PostQuoteRequest(
    context: "sep24",
    sellAsset: "iso4217:USD",
    buyAsset: "stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
    sellAmount: "100",
    expireAfter: $expireAfter
);

$quote = $quoteService->postQuote($request, $jwtToken);
echo "Quote valid until: " . $quote->expiresAt->format('c') . "\n";
```

**With delivery methods:**

Include delivery methods when exchanging off-chain assets:

```php
<?php

use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38PostQuoteRequest;

$quoteService = QuoteService::fromDomain("anchor.example.com");

// Quote for selling BRL via bank transfer, buying USDC
$request = new SEP38PostQuoteRequest(
    context: "sep6",
    sellAsset: "iso4217:BRL",
    buyAsset: "stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
    sellAmount: "1000",
    sellDeliveryMethod: "ACH",
    countryCode: "BR"
);

$quote = $quoteService->postQuote($request, $jwtToken);

// Delivery methods are echoed back in the response if provided
if ($quote->sellDeliveryMethod !== null) {
    echo "Sell delivery method: " . $quote->sellDeliveryMethod . "\n";
}
if ($quote->buyDeliveryMethod !== null) {
    echo "Buy delivery method: " . $quote->buyDeliveryMethod . "\n";
}
```

### Retrieving a previous quote (GET /quote/:id)

Use `getQuote()` to retrieve a previously-created firm quote by its ID. This is useful for checking the quote status or retrieving details after creation. Authentication is required:

```php
<?php

use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38QuoteResponse;

$quoteService = QuoteService::fromDomain("anchor.example.com");

// Use the ID from postQuote() response
$quoteId = "de762cda-a193-4961-861e-57b31fed6eb3";
$quote = $quoteService->getQuote($quoteId, $jwtToken);

echo "Quote ID: " . $quote->id . "\n";
echo "Expires at: " . $quote->expiresAt->format('Y-m-d H:i:s') . "\n";
echo "Still valid: " . ($quote->expiresAt > new DateTime() ? "Yes" : "No") . "\n";
```

## Price formulas

The SEP-38 spec defines these relationships between price, amounts, and fees:

```
sell_amount = total_price * buy_amount
```

When the fee is in the sell asset:
```
sell_amount - fee = price * buy_amount
```

When the fee is in the buy asset:
```
sell_amount = price * (buy_amount + fee)
```

## Error handling

The SDK provides specific exception classes for different error scenarios. Always wrap quote service calls in try-catch blocks for production use:

```php
<?php

use InvalidArgumentException;
use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38PostQuoteRequest;
use Soneso\StellarSDK\SEP\Quote\SEP38BadRequestException;
use Soneso\StellarSDK\SEP\Quote\SEP38NotFoundException;
use Soneso\StellarSDK\SEP\Quote\SEP38PermissionDeniedException;
use Soneso\StellarSDK\SEP\Quote\SEP38UnknownResponseException;

$quoteService = QuoteService::fromDomain("anchor.example.com");

try {
    $request = new SEP38PostQuoteRequest(
        context: "sep24",
        sellAsset: "iso4217:USD",
        buyAsset: "stellar:USDC:GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN",
        sellAmount: "100"
    );
    
    $quote = $quoteService->postQuote($request, $jwtToken);
    echo "Quote created: " . $quote->id . "\n";
    
} catch (InvalidArgumentException $e) {
    // Invalid parameters (e.g., both sellAmount and buyAmount provided)
    echo "Invalid request: " . $e->getMessage() . "\n";
    
} catch (SEP38BadRequestException $e) {
    // HTTP 400 - Invalid request parameters
    echo "Bad request: " . $e->getMessage() . "\n";
    
} catch (SEP38PermissionDeniedException $e) {
    // HTTP 403 - Authentication failed or not authorized
    echo "Permission denied: " . $e->getMessage() . "\n";
    
} catch (SEP38NotFoundException $e) {
    // HTTP 404 - Quote not found (for getQuote)
    echo "Quote not found: " . $e->getMessage() . "\n";
    
} catch (SEP38UnknownResponseException $e) {
    // Other HTTP errors
    echo "Unexpected error: " . $e->getMessage() . "\n";
}
```

### Exception reference

| Exception | HTTP Status | Common Causes | Solution |
|-----------|-------------|---------------|----------|
| `InvalidArgumentException` | N/A | Both `sellAmount` and `buyAmount` provided, or neither provided | Provide exactly one of the two amounts |
| `SEP38BadRequestException` | 400 | Invalid asset format, unsupported asset pair, invalid context | Check asset identifiers and required fields |
| `SEP38PermissionDeniedException` | 403 | Missing JWT, expired JWT, or user not authorized | Re-authenticate with SEP-10 or SEP-45 |
| `SEP38NotFoundException` | 404 | Quote ID doesn't exist (for `getQuote`) | Verify quote ID; it may have expired and been removed |
| `SEP38UnknownResponseException` | Other | Server error or unexpected response | Check anchor status; retry later |

## SDK classes reference

### Service class

| Class | Description |
|-------|-------------|
| `QuoteService` | Main service class with methods: `info()`, `prices()`, `price()`, `postQuote()`, `getQuote()` |

### Request classes

| Class | Description |
|-------|-------------|
| `SEP38PostQuoteRequest` | Request body for creating firm quotes via `postQuote()` |

### Response classes

| Class | Description |
|-------|-------------|
| `SEP38InfoResponse` | Response from `info()` containing available assets |
| `SEP38PricesResponse` | Response from `prices()` containing indicative prices for multiple assets |
| `SEP38PriceResponse` | Response from `price()` containing indicative price for a single pair |
| `SEP38QuoteResponse` | Response from `postQuote()` and `getQuote()` containing firm quote details |

### Model classes

| Class | Description |
|-------|-------------|
| `SEP38Asset` | Asset information including delivery methods and country availability |
| `SEP38BuyAsset` | Buy asset option with price from `prices()` response |
| `SEP38SellAsset` | Sell asset option with price from `prices()` response (v2.3.0+) |
| `SEP38Fee` | Fee structure with total amount and optional breakdown |
| `SEP38FeeDetails` | Individual fee component (name, amount, description) |
| `SEP38SellDeliveryMethod` | Method for delivering off-chain assets to the anchor |
| `SEP38BuyDeliveryMethod` | Method for receiving off-chain assets from the anchor |

### Exception classes

| Class | Description |
|-------|-------------|
| `SEP38BadRequestException` | HTTP 400 - Invalid request |
| `SEP38PermissionDeniedException` | HTTP 403 - Authentication required or failed |
| `SEP38NotFoundException` | HTTP 404 - Quote not found |
| `SEP38UnknownResponseException` | Other HTTP errors |

## Related SEPs

- [SEP-10](sep-10.md) - Authentication for traditional Stellar accounts
- [SEP-45](sep-45.md) - Authentication for smart contract accounts  
- [SEP-6](sep-06.md) - Programmatic deposit/withdrawal (uses quotes with `context: "sep6"`)
- [SEP-24](sep-24.md) - Interactive deposit/withdrawal (uses quotes with `context: "sep24"`)
- [SEP-31](sep-31.md) - Cross-border payments (uses quotes with `context: "sep31"`)

## Further reading

- [SDK test cases](../../Soneso/StellarSDKTests/Unit/SEP/Quote/QuoteTest.php) - Examples of all SEP-38 functionality

## Reference

- [SEP-38 Specification (v2.5.0)](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md)

---

[Back to SEP Overview](README.md)
