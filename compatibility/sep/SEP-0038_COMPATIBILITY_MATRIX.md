# SEP-38: Anchor RFQ API

**Status:** ✅ Supported  
**SDK Version:** 1.9.5  
**Generated:** 2026-03-11 21:41 UTC  
**Spec:** [https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md](https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md)

## Overall Coverage

**Total Coverage:** 100.0% (53/53 fields)

- ✅ **Implemented:** 53/53
- ❌ **Not Implemented:** 0/53

## Coverage by Section

| Section | Coverage | Implemented | Total |
|---------|----------|-------------|-------|
| Service Endpoints | 100.0% | 5 | 5 |
| Info Response Fields | 100.0% | 1 | 1 |
| Asset Fields | 100.0% | 4 | 4 |
| Prices Response Fields | 100.0% | 2 | 2 |
| Buy Asset Fields | 100.0% | 3 | 3 |
| Sell Asset Fields | 100.0% | 3 | 3 |
| Price Response Fields | 100.0% | 5 | 5 |
| POST /quote Request Fields | 100.0% | 9 | 9 |
| Quote Response Fields | 100.0% | 11 | 11 |
| Fee Fields | 100.0% | 3 | 3 |
| Fee Details Fields | 100.0% | 3 | 3 |
| Sell Delivery Method Fields | 100.0% | 2 | 2 |
| Buy Delivery Method Fields | 100.0% | 2 | 2 |

## Service Endpoints

QuoteService API methods

| Feature | Status | Notes |
|---------|--------|-------|
| `GET /info` | ✅ Supported | `QuoteService.info()` |
| `GET /prices` | ✅ Supported | `QuoteService.prices()` |
| `GET /price` | ✅ Supported | `QuoteService.price()` |
| `POST /quote` | ✅ Supported | `QuoteService.postQuote()` |
| `GET /quote/:id` | ✅ Supported | `QuoteService.getQuote()` |

## Info Response Fields

SEP38InfoResponse properties

| Feature | Status | Notes |
|---------|--------|-------|
| `assets` | ✅ Supported | `Required. SEP38InfoResponse.$assets` |

## Asset Fields

SEP38Asset properties

| Feature | Status | Notes |
|---------|--------|-------|
| `asset` | ✅ Supported | `Required. SEP38Asset.$asset` |
| `sell_delivery_methods` | ✅ Supported | `SEP38Asset.$sellDeliveryMethods` |
| `buy_delivery_methods` | ✅ Supported | `SEP38Asset.$buyDeliveryMethods` |
| `country_codes` | ✅ Supported | `SEP38Asset.$countryCodes` |

## Prices Response Fields

SEP38PricesResponse properties

| Feature | Status | Notes |
|---------|--------|-------|
| `buy_assets` | ✅ Supported | `SEP38PricesResponse.$buyAssets` |
| `sell_assets` | ✅ Supported | `SEP38PricesResponse.$sellAssets` |

## Buy Asset Fields

SEP38BuyAsset properties

| Feature | Status | Notes |
|---------|--------|-------|
| `asset` | ✅ Supported | `Required. SEP38BuyAsset.$asset` |
| `price` | ✅ Supported | `Required. SEP38BuyAsset.$price` |
| `decimals` | ✅ Supported | `Required. SEP38BuyAsset.$decimals` |

## Sell Asset Fields

SEP38SellAsset properties

| Feature | Status | Notes |
|---------|--------|-------|
| `asset` | ✅ Supported | `Required. SEP38SellAsset.$asset` |
| `price` | ✅ Supported | `Required. SEP38SellAsset.$price` |
| `decimals` | ✅ Supported | `Required. SEP38SellAsset.$decimals` |

## Price Response Fields

SEP38PriceResponse properties

| Feature | Status | Notes |
|---------|--------|-------|
| `total_price` | ✅ Supported | `Required. SEP38PriceResponse.$totalPrice` |
| `price` | ✅ Supported | `Required. SEP38PriceResponse.$price` |
| `sell_amount` | ✅ Supported | `Required. SEP38PriceResponse.$sellAmount` |
| `buy_amount` | ✅ Supported | `Required. SEP38PriceResponse.$buyAmount` |
| `fee` | ✅ Supported | `Required. SEP38PriceResponse.$fee` |

## POST /quote Request Fields

SEP38PostQuoteRequest properties

| Feature | Status | Notes |
|---------|--------|-------|
| `context` | ✅ Supported | `Required. SEP38PostQuoteRequest.$context` |
| `sell_asset` | ✅ Supported | `Required. SEP38PostQuoteRequest.$sellAsset` |
| `buy_asset` | ✅ Supported | `Required. SEP38PostQuoteRequest.$buyAsset` |
| `sell_amount` | ✅ Supported | `SEP38PostQuoteRequest.$sellAmount` |
| `buy_amount` | ✅ Supported | `SEP38PostQuoteRequest.$buyAmount` |
| `expire_after` | ✅ Supported | `SEP38PostQuoteRequest.$expireAfter` |
| `sell_delivery_method` | ✅ Supported | `SEP38PostQuoteRequest.$sellDeliveryMethod` |
| `buy_delivery_method` | ✅ Supported | `SEP38PostQuoteRequest.$buyDeliveryMethod` |
| `country_code` | ✅ Supported | `SEP38PostQuoteRequest.$countryCode` |

## Quote Response Fields

SEP38QuoteResponse properties

| Feature | Status | Notes |
|---------|--------|-------|
| `id` | ✅ Supported | `Required. SEP38QuoteResponse.$id` |
| `expires_at` | ✅ Supported | `Required. SEP38QuoteResponse.$expiresAt` |
| `total_price` | ✅ Supported | `Required. SEP38QuoteResponse.$totalPrice` |
| `price` | ✅ Supported | `Required. SEP38QuoteResponse.$price` |
| `sell_asset` | ✅ Supported | `Required. SEP38QuoteResponse.$sellAsset` |
| `sell_amount` | ✅ Supported | `Required. SEP38QuoteResponse.$sellAmount` |
| `buy_asset` | ✅ Supported | `Required. SEP38QuoteResponse.$buyAsset` |
| `buy_amount` | ✅ Supported | `Required. SEP38QuoteResponse.$buyAmount` |
| `fee` | ✅ Supported | `Required. SEP38QuoteResponse.$fee` |
| `sell_delivery_method` | ✅ Supported | `SEP38QuoteResponse.$sellDeliveryMethod` |
| `buy_delivery_method` | ✅ Supported | `SEP38QuoteResponse.$buyDeliveryMethod` |

## Fee Fields

SEP38Fee properties

| Feature | Status | Notes |
|---------|--------|-------|
| `total` | ✅ Supported | `Required. SEP38Fee.$total` |
| `asset` | ✅ Supported | `Required. SEP38Fee.$asset` |
| `details` | ✅ Supported | `SEP38Fee.$details` |

## Fee Details Fields

SEP38FeeDetails properties

| Feature | Status | Notes |
|---------|--------|-------|
| `name` | ✅ Supported | `Required. SEP38FeeDetails.$name` |
| `amount` | ✅ Supported | `Required. SEP38FeeDetails.$amount` |
| `description` | ✅ Supported | `SEP38FeeDetails.$description` |

## Sell Delivery Method Fields

SEP38SellDeliveryMethod properties

| Feature | Status | Notes |
|---------|--------|-------|
| `name` | ✅ Supported | `Required. SEP38SellDeliveryMethod.$name` |
| `description` | ✅ Supported | `Required. SEP38SellDeliveryMethod.$description` |

## Buy Delivery Method Fields

SEP38BuyDeliveryMethod properties

| Feature | Status | Notes |
|---------|--------|-------|
| `name` | ✅ Supported | `Required. SEP38BuyDeliveryMethod.$name` |
| `description` | ✅ Supported | `Required. SEP38BuyDeliveryMethod.$description` |
