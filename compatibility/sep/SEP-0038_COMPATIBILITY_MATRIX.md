# SEP-0038 (Anchor RFQ API) Compatibility Matrix

**Generated:** 2026-02-03 15:20:31

**SEP Version:** N/A

**SEP Status:** Draft

**SDK Version:** 1.9.2

**SEP URL:** https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md

## SEP Summary

This protocol enables anchors to accept off-chain assets in exchange for
different on-chain assets, and vice versa. Specifically, it enables anchors to
provide quotes that can be referenced within the context of existing Stellar
Ecosystem Proposals. How the exchange of assets is facilitated is outside the
scope of this document.

## Overall Coverage

**Total Coverage:** 100% (58/58 fields)

- ✅ **Implemented:** 58/58
- ❌ **Not Implemented:** 0/58

**Required Fields:** 100% (39/39)

**Optional Fields:** 100% (19/19)

## Implementation Status

✅ **Implemented**

### Implementation Files

- `Soneso/StellarSDK/SEP/Quote/SEP38BuyDeliveryMethod.php`
- `Soneso/StellarSDK/SEP/Quote/SEP38Fee.php`
- `Soneso/StellarSDK/SEP/Quote/SEP38BadRequestException.php`
- `Soneso/StellarSDK/SEP/Quote/SEP38Asset.php`
- `Soneso/StellarSDK/SEP/Quote/SEP38PermissionDeniedException.php`
- `Soneso/StellarSDK/SEP/Quote/QuoteService.php`
- `Soneso/StellarSDK/SEP/Quote/SEP38SellAsset.php`
- `Soneso/StellarSDK/SEP/Quote/SEP38PriceResponse.php`
- `Soneso/StellarSDK/SEP/Quote/SEP38UnknownResponseException.php`
- `Soneso/StellarSDK/SEP/Quote/SEP38QuoteResponse.php`
- `Soneso/StellarSDK/SEP/Quote/SEP38NotFoundException.php`
- `Soneso/StellarSDK/SEP/Quote/SEP38SellDeliveryMethod.php`
- `Soneso/StellarSDK/SEP/Quote/SEP38FeeDetails.php`
- `Soneso/StellarSDK/SEP/Quote/SEP38InfoResponse.php`
- `Soneso/StellarSDK/SEP/Quote/SEP38PostQuoteRequest.php`
- `Soneso/StellarSDK/SEP/Quote/SEP38BuyAsset.php`
- `Soneso/StellarSDK/SEP/Quote/SEP38PricesResponse.php`

### Key Classes

- **`SEP38BuyDeliveryMethod`**: Implements SEP-0038 - Anchor RFQ API.
- **`SEP38Fee`**: Implements SEP-0038 - Anchor RFQ API.
- **`SEP38BadRequestException`**: Implements SEP-0038 - Anchor RFQ API.
- **`SEP38Asset`**: Implements SEP-0038 - Anchor RFQ API.
- **`SEP38PermissionDeniedException`**: Implements SEP-0038 - Anchor RFQ API.
- **`QuoteService`**: Implements SEP-0038 - Anchor RFQ API.
- **`SEP38SellAsset`**: Implements SEP-0038 - Anchor RFQ API.
- **`SEP38PriceResponse`**: Implements SEP-0038 - Anchor RFQ API.
- **`SEP38UnknownResponseException`**: Implements SEP-0038 - Anchor RFQ API.
- **`SEP38QuoteResponse`**: Implements SEP-0038 - Anchor RFQ API.
- **`SEP38NotFoundException`**: Implements SEP-0038 - Anchor RFQ API.
- **`SEP38SellDeliveryMethod`**: Implements SEP-0038 - Anchor RFQ API.
- **`SEP38FeeDetails`**: Implements SEP-0038 - Anchor RFQ API.
- **`SEP38InfoResponse`**: Implements SEP-0038 - Anchor RFQ API.
- **`SEP38PostQuoteRequest`**: Implements SEP-0038 - Anchor RFQ API.
- **`SEP38BuyAsset`**: Implements SEP-0038 - Anchor RFQ API.
- **`SEP38PricesResponse`**: Implements SEP-0038 - Anchor RFQ API.

## Coverage by Section

| Section | Coverage | Required Coverage | Implemented | Total |
|---------|----------|-------------------|-------------|-------|
| Asset Fields | 100% | 100% | 4 | 4 |
| Buy Asset Fields | 100% | 100% | 3 | 3 |
| Delivery Method Fields | 100% | 100% | 2 | 2 |
| Fee Details Fields | 100% | 100% | 3 | 3 |
| Fee Fields | 100% | 100% | 3 | 3 |
| Get Quote Endpoint | 100% | 100% | 1 | 1 |
| Info Endpoint | 100% | 100% | 1 | 1 |
| Info Response Fields | 100% | 100% | 1 | 1 |
| Post Quote Endpoint | 100% | 100% | 1 | 1 |
| Post Quote Request Fields | 100% | 100% | 9 | 9 |
| Price Endpoint | 100% | 100% | 1 | 1 |
| Price Request Parameters | 100% | 100% | 8 | 8 |
| Price Response Fields | 100% | 100% | 5 | 5 |
| Prices Endpoint | 100% | 100% | 1 | 1 |
| Prices Request Parameters | 100% | 100% | 5 | 5 |
| Prices Response Fields | 100% | 100% | 1 | 1 |
| Quote Response Fields | 100% | 100% | 9 | 9 |

## Detailed Field Comparison

### Asset Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `asset` | ✓ | ✅ | `asset` | Asset identifier in Asset Identification Format |
| `sell_delivery_methods` |  | ✅ | `sellDeliveryMethods` | Array of delivery methods for selling this asset |
| `buy_delivery_methods` |  | ✅ | `buyDeliveryMethods` | Array of delivery methods for buying this asset |
| `country_codes` |  | ✅ | `countryCodes` | Array of ISO 3166-2 or ISO 3166-1 alpha-2 country codes |

### Buy Asset Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `asset` | ✓ | ✅ | `asset` | Asset identifier in Asset Identification Format |
| `price` | ✓ | ✅ | `price` | Price offered by anchor for one unit of buy_asset |
| `decimals` | ✓ | ✅ | `decimals` | Number of decimals for the buy asset |

### Delivery Method Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `name` | ✓ | ✅ | `name` | Delivery method name identifier |
| `description` | ✓ | ✅ | `description` | Human-readable description of the delivery method |

### Fee Details Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `name` | ✓ | ✅ | `name` | Name identifier for the fee component |
| `amount` | ✓ | ✅ | `amount` | Fee amount as decimal string |
| `description` |  | ✅ | `description` | Human-readable description of the fee |

### Fee Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `total` | ✓ | ✅ | `total` | Total fee amount as decimal string |
| `asset` | ✓ | ✅ | `asset` | Asset identifier for the fee |
| `details` |  | ✅ | `details` | Optional array of fee breakdown objects |

### Get Quote Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `get_quote_endpoint` | ✓ | ✅ | `getQuote` | GET /quote/:id - Fetch a previously-provided firm quote |

### Info Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `info_endpoint` | ✓ | ✅ | `info` | GET /info - Returns supported Stellar and off-chain assets available for trading |

### Info Response Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `assets` | ✓ | ✅ | `assets` | Array of asset objects supported for trading |

### Post Quote Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `post_quote_endpoint` | ✓ | ✅ | `postQuote` | POST /quote - Request a firm quote for asset exchange |

### Post Quote Request Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `context` | ✓ | ✅ | - | Context for quote usage (sep6 or sep31) |
| `sell_asset` | ✓ | ✅ | `sellAsset` | Asset client would like to sell |
| `buy_asset` | ✓ | ✅ | `buyAsset` | Asset client would like to exchange for sell_asset |
| `sell_amount` |  | ✅ | `sellAmount` | Amount of sell_asset to exchange (mutually exclusive with buy_amount) |
| `buy_amount` |  | ✅ | `buyAmount` | Amount of buy_asset to exchange for (mutually exclusive with sell_amount) |
| `expire_after` |  | ✅ | - | Requested expiration timestamp for the quote (ISO 8601) |
| `sell_delivery_method` |  | ✅ | - | Delivery method for off-chain sell asset |
| `buy_delivery_method` |  | ✅ | - | Delivery method for off-chain buy asset |
| `country_code` |  | ✅ | - | ISO 3166-2 or ISO 3166-1 alpha-2 country code |

### Price Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `price_endpoint` | ✓ | ✅ | `price` | GET /price - Returns indicative price for a specific asset pair |

### Price Request Parameters

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `context` | ✓ | ✅ | - | Context for quote usage (sep6 or sep31) |
| `sell_asset` | ✓ | ✅ | `sellAsset` | Asset client would like to sell |
| `buy_asset` | ✓ | ✅ | `buyAsset` | Asset client would like to exchange for sell_asset |
| `sell_amount` |  | ✅ | `sellAmount` | Amount of sell_asset to exchange (mutually exclusive with buy_amount) |
| `buy_amount` |  | ✅ | `buyAmount` | Amount of buy_asset to exchange for (mutually exclusive with sell_amount) |
| `sell_delivery_method` |  | ✅ | - | Delivery method for off-chain sell asset |
| `buy_delivery_method` |  | ✅ | - | Delivery method for off-chain buy asset |
| `country_code` |  | ✅ | - | ISO 3166-2 or ISO 3166-1 alpha-2 country code |

### Price Response Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `total_price` | ✓ | ✅ | `totalPrice` | Total conversion price including fees |
| `price` | ✓ | ✅ | `price` | Base conversion price excluding fees |
| `sell_amount` | ✓ | ✅ | `sellAmount` | Amount of sell_asset that will be exchanged |
| `buy_amount` | ✓ | ✅ | `buyAmount` | Amount of buy_asset that will be received |
| `fee` | ✓ | ✅ | `fee` | Fee object with total, asset, and optional details |

### Prices Endpoint

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `prices_endpoint` | ✓ | ✅ | `prices` | GET /prices - Returns indicative prices of off-chain assets in exchange for Stellar assets |

### Prices Request Parameters

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `sell_asset` | ✓ | ✅ | `sellAsset` | Asset to sell using Asset Identification Format |
| `sell_amount` | ✓ | ✅ | `sellAmount` | Amount of sell_asset to exchange |
| `sell_delivery_method` |  | ✅ | - | Delivery method for off-chain sell asset |
| `buy_delivery_method` |  | ✅ | - | Delivery method for off-chain buy asset |
| `country_code` |  | ✅ | - | ISO 3166-2 or ISO 3166-1 alpha-2 country code |

### Prices Response Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `buy_assets` | ✓ | ✅ | `buyAssets` | Array of buy asset objects with prices |

### Quote Response Fields

| Field | Required | Status | SDK Property | Description |
|-------|----------|--------|--------------|-------------|
| `id` | ✓ | ✅ | `id` | Unique identifier for the quote |
| `expires_at` | ✓ | ✅ | `expiresAt` | Expiration timestamp for the quote (ISO 8601) |
| `total_price` | ✓ | ✅ | `totalPrice` | Total conversion price including fees |
| `price` | ✓ | ✅ | `price` | Base conversion price excluding fees |
| `sell_asset` | ✓ | ✅ | `sellAsset` | Asset to be sold |
| `sell_amount` | ✓ | ✅ | `sellAmount` | Amount of sell_asset to be exchanged |
| `buy_asset` | ✓ | ✅ | `buyAsset` | Asset to be bought |
| `buy_amount` | ✓ | ✅ | `buyAmount` | Amount of buy_asset to be received |
| `fee` | ✓ | ✅ | `fee` | Fee object with total, asset, and optional details |

## Implementation Gaps

🎉 **No gaps found!** All fields are implemented.

## Recommendations

✅ The SDK has full compatibility with SEP-0038!

## Legend

- ✅ **Implemented**: Field is implemented in SDK
- ❌ **Not Implemented**: Field is missing from SDK
- ✓ **Required**: Field is required by SEP specification
- (blank) **Optional**: Field is optional
