<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Responses;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Responses\Trades\TradeResponse;
use Soneso\StellarSDK\Responses\Trades\TradesPageResponse;
use Soneso\StellarSDK\Responses\Trades\TradesResponse;
use Soneso\StellarSDK\Responses\Trades\TradePriceResponse;
use Soneso\StellarSDK\Responses\Trades\TradeLinksResponse;
use Soneso\StellarSDK\Responses\TradeAggregations\TradeAggregationResponse;
use Soneso\StellarSDK\Responses\TradeAggregations\TradeAggregationsPageResponse;
use Soneso\StellarSDK\Responses\TradeAggregations\TradeAggregationsResponse;

/**
 * Unit tests for all Trade Response classes
 *
 * Tests JSON parsing and getter methods for Trade and TradeAggregation response classes.
 * Covers TradeResponse (25 methods), TradeAggregationResponse (15 methods), and related classes.
 */
class TradeResponseTest extends TestCase
{
    // TradePriceResponse Tests

    public function testTradePriceResponseFromJson(): void
    {
        $json = [
            'n' => '157',
            'd' => '100'
        ];

        $response = TradePriceResponse::fromJson($json);

        $this->assertEquals('157', $response->getN());
        $this->assertEquals('100', $response->getD());
    }

    public function testTradePriceResponseWithLargeNumbers(): void
    {
        $json = [
            'n' => '999999999999999999',
            'd' => '1000000000000000000'
        ];

        $response = TradePriceResponse::fromJson($json);

        $this->assertEquals('999999999999999999', $response->getN());
        $this->assertEquals('1000000000000000000', $response->getD());
    }

    public function testTradePriceResponseWithSmallFraction(): void
    {
        $json = [
            'n' => '1',
            'd' => '10000'
        ];

        $response = TradePriceResponse::fromJson($json);

        $this->assertEquals('1', $response->getN());
        $this->assertEquals('10000', $response->getD());
    }

    // TradeLinksResponse Tests

    public function testTradeLinksResponseFromJson(): void
    {
        $json = [
            'self' => ['href' => 'https://horizon.stellar.org/trades/1'],
            'base' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7'],
            'counter' => ['href' => 'https://horizon.stellar.org/accounts/GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6'],
            'operation' => ['href' => 'https://horizon.stellar.org/operations/12884905985']
        ];

        $response = TradeLinksResponse::fromJson($json);

        $this->assertNotNull($response->getSelf());
        $this->assertEquals('https://horizon.stellar.org/trades/1', $response->getSelf()->getHref());
        $this->assertNotNull($response->getBase());
        $this->assertEquals('https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getBase()->getHref());
        $this->assertNotNull($response->getCounter());
        $this->assertEquals('https://horizon.stellar.org/accounts/GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getCounter()->getHref());
        $this->assertNotNull($response->getOperation());
        $this->assertEquals('https://horizon.stellar.org/operations/12884905985', $response->getOperation()->getHref());
    }

    // TradeResponse Tests - Orderbook Trade

    public function testTradeResponseOrderbookTrade(): void
    {
        $json = [
            'id' => '3697472920621057-0',
            'paging_token' => '3697472920621057-0',
            'ledger_close_time' => '2024-01-15T10:30:45Z',
            'trade_type' => 'orderbook',
            'offer_id' => '165563085',
            'base_offer_id' => '165563085',
            'base_account' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'base_amount' => '100.0000000',
            'base_asset_type' => 'native',
            'counter_offer_id' => '165563086',
            'counter_account' => 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6',
            'counter_amount' => '150.0000000',
            'counter_asset_type' => 'credit_alphanum4',
            'counter_asset_code' => 'USD',
            'counter_asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX',
            'base_is_seller' => true,
            'price' => [
                'n' => '3',
                'd' => '2'
            ],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/trades/3697472920621057-0'],
                'base' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7'],
                'counter' => ['href' => 'https://horizon.stellar.org/accounts/GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6'],
                'operation' => ['href' => 'https://horizon.stellar.org/operations/12884905985']
            ]
        ];

        $response = TradeResponse::fromJson($json);

        $this->assertEquals('3697472920621057-0', $response->getId());
        $this->assertEquals('3697472920621057-0', $response->getPagingToken());
        $this->assertEquals('2024-01-15T10:30:45Z', $response->getLedgerCloseTime());
        $this->assertEquals('orderbook', $response->getTradeType());
        $this->assertEquals('165563085', $response->getOfferId());
        $this->assertEquals('165563085', $response->getBaseOfferId());
        $this->assertNull($response->getBaseLiquidityPoolId());
        $this->assertNull($response->getLiquidityPoolFeeBp());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getBaseAccount());
        $this->assertEquals('100.0000000', $response->getBaseAmount());
        $this->assertEquals('native', $response->getBaseAssetType());
        $this->assertNull($response->getBaseAssetCode());
        $this->assertNull($response->getBaseAssetIssuer());
        $this->assertEquals('165563086', $response->getCounterOfferId());
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getCounterAccount());
        $this->assertEquals('150.0000000', $response->getCounterAmount());
        $this->assertEquals('credit_alphanum4', $response->getCounterAssetType());
        $this->assertEquals('USD', $response->getCounterAssetCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getCounterAssetIssuer());
        $this->assertNull($response->getCounterLiquidityPoolId());
        $this->assertTrue($response->isBaseIsSeller());
        $this->assertInstanceOf(TradePriceResponse::class, $response->getPrice());
        $this->assertEquals('3', $response->getPrice()->getN());
        $this->assertEquals('2', $response->getPrice()->getD());
        $this->assertInstanceOf(TradeLinksResponse::class, $response->getLinks());
    }

    public function testTradeResponseLiquidityPoolTrade(): void
    {
        $json = [
            'id' => '3697472920621058-0',
            'paging_token' => '3697472920621058-0',
            'ledger_close_time' => '2024-01-15T10:31:00Z',
            'trade_type' => 'liquidity_pool',
            'base_liquidity_pool_id' => '67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9',
            'liquidity_pool_fee_bp' => 30,
            'base_amount' => '50.0000000',
            'base_asset_type' => 'native',
            'counter_account' => 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6',
            'counter_amount' => '75.0000000',
            'counter_asset_type' => 'credit_alphanum4',
            'counter_asset_code' => 'USDC',
            'counter_asset_issuer' => 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
            'base_is_seller' => false,
            'price' => [
                'n' => '150',
                'd' => '100'
            ],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/trades/3697472920621058-0'],
                'base' => ['href' => 'https://horizon.stellar.org/liquidity_pools/67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9'],
                'counter' => ['href' => 'https://horizon.stellar.org/accounts/GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6'],
                'operation' => ['href' => 'https://horizon.stellar.org/operations/12884905986']
            ]
        ];

        $response = TradeResponse::fromJson($json);

        $this->assertEquals('3697472920621058-0', $response->getId());
        $this->assertEquals('3697472920621058-0', $response->getPagingToken());
        $this->assertEquals('2024-01-15T10:31:00Z', $response->getLedgerCloseTime());
        $this->assertEquals('liquidity_pool', $response->getTradeType());
        $this->assertNull($response->getOfferId());
        $this->assertNull($response->getBaseOfferId());
        $this->assertEquals('67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9', $response->getBaseLiquidityPoolId());
        $this->assertEquals(30, $response->getLiquidityPoolFeeBp());
        $this->assertNull($response->getBaseAccount());
        $this->assertEquals('50.0000000', $response->getBaseAmount());
        $this->assertEquals('native', $response->getBaseAssetType());
        $this->assertNull($response->getCounterOfferId());
        $this->assertEquals('GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6', $response->getCounterAccount());
        $this->assertEquals('75.0000000', $response->getCounterAmount());
        $this->assertEquals('credit_alphanum4', $response->getCounterAssetType());
        $this->assertEquals('USDC', $response->getCounterAssetCode());
        $this->assertEquals('GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $response->getCounterAssetIssuer());
        $this->assertFalse($response->isBaseIsSeller());
        $this->assertInstanceOf(TradePriceResponse::class, $response->getPrice());
        $this->assertEquals('150', $response->getPrice()->getN());
        $this->assertEquals('100', $response->getPrice()->getD());
    }

    public function testTradeResponseWithCredit12Asset(): void
    {
        $json = [
            'id' => '3697472920621059-0',
            'paging_token' => '3697472920621059-0',
            'ledger_close_time' => '2024-01-15T10:32:00Z',
            'trade_type' => 'orderbook',
            'base_offer_id' => '165563087',
            'base_account' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'base_amount' => '200.0000000',
            'base_asset_type' => 'credit_alphanum12',
            'base_asset_code' => 'STELLARTOKEN',
            'base_asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX',
            'counter_offer_id' => '165563088',
            'counter_account' => 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6',
            'counter_amount' => '300.0000000',
            'counter_asset_type' => 'native',
            'base_is_seller' => true,
            'price' => [
                'n' => '3',
                'd' => '2'
            ],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/trades/3697472920621059-0'],
                'base' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7'],
                'counter' => ['href' => 'https://horizon.stellar.org/accounts/GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6'],
                'operation' => ['href' => 'https://horizon.stellar.org/operations/12884905987']
            ]
        ];

        $response = TradeResponse::fromJson($json);

        $this->assertEquals('credit_alphanum12', $response->getBaseAssetType());
        $this->assertEquals('STELLARTOKEN', $response->getBaseAssetCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $response->getBaseAssetIssuer());
        $this->assertEquals('native', $response->getCounterAssetType());
        $this->assertNull($response->getCounterAssetCode());
        $this->assertNull($response->getCounterAssetIssuer());
    }

    public function testTradeResponseWithCounterLiquidityPool(): void
    {
        $json = [
            'id' => '3697472920621060-0',
            'paging_token' => '3697472920621060-0',
            'ledger_close_time' => '2024-01-15T10:33:00Z',
            'trade_type' => 'liquidity_pool',
            'base_account' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'base_amount' => '100.0000000',
            'base_asset_type' => 'native',
            'counter_liquidity_pool_id' => '67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9',
            'liquidity_pool_fee_bp' => 30,
            'counter_amount' => '150.0000000',
            'counter_asset_type' => 'credit_alphanum4',
            'counter_asset_code' => 'USDC',
            'counter_asset_issuer' => 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN',
            'base_is_seller' => true,
            'price' => [
                'n' => '150',
                'd' => '100'
            ],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/trades/3697472920621060-0'],
                'base' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7'],
                'counter' => ['href' => 'https://horizon.stellar.org/liquidity_pools/67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9'],
                'operation' => ['href' => 'https://horizon.stellar.org/operations/12884905988']
            ]
        ];

        $response = TradeResponse::fromJson($json);

        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getBaseAccount());
        $this->assertNull($response->getBaseLiquidityPoolId());
        $this->assertNull($response->getCounterAccount());
        $this->assertEquals('67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9', $response->getCounterLiquidityPoolId());
        $this->assertEquals(30, $response->getLiquidityPoolFeeBp());
    }

    // TradesResponse Tests

    public function testTradesResponseCollection(): void
    {
        $trade1 = TradeResponse::fromJson([
            'id' => '1',
            'paging_token' => '1',
            'ledger_close_time' => '2024-01-15T10:30:45Z',
            'trade_type' => 'orderbook',
            'base_amount' => '100.0000000',
            'base_asset_type' => 'native',
            'counter_amount' => '150.0000000',
            'counter_asset_type' => 'native',
            'base_is_seller' => true,
            'price' => ['n' => '3', 'd' => '2'],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/trades/1'],
                'base' => ['href' => 'https://horizon.stellar.org/accounts/GA1'],
                'counter' => ['href' => 'https://horizon.stellar.org/accounts/GA2'],
                'operation' => ['href' => 'https://horizon.stellar.org/operations/1']
            ]
        ]);

        $trade2 = TradeResponse::fromJson([
            'id' => '2',
            'paging_token' => '2',
            'ledger_close_time' => '2024-01-15T10:31:00Z',
            'trade_type' => 'orderbook',
            'base_amount' => '200.0000000',
            'base_asset_type' => 'native',
            'counter_amount' => '300.0000000',
            'counter_asset_type' => 'native',
            'base_is_seller' => false,
            'price' => ['n' => '3', 'd' => '2'],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/trades/2'],
                'base' => ['href' => 'https://horizon.stellar.org/accounts/GA3'],
                'counter' => ['href' => 'https://horizon.stellar.org/accounts/GA4'],
                'operation' => ['href' => 'https://horizon.stellar.org/operations/2']
            ]
        ]);

        $collection = new TradesResponse($trade1, $trade2);

        $this->assertEquals(2, $collection->count());

        $array = $collection->toArray();
        $this->assertCount(2, $array);
        $this->assertInstanceOf(TradeResponse::class, $array[0]);
        $this->assertInstanceOf(TradeResponse::class, $array[1]);
        $this->assertEquals('1', $array[0]->getId());
        $this->assertEquals('2', $array[1]->getId());

        $trade3 = TradeResponse::fromJson([
            'id' => '3',
            'paging_token' => '3',
            'ledger_close_time' => '2024-01-15T10:32:00Z',
            'trade_type' => 'orderbook',
            'base_amount' => '300.0000000',
            'base_asset_type' => 'native',
            'counter_amount' => '450.0000000',
            'counter_asset_type' => 'native',
            'base_is_seller' => true,
            'price' => ['n' => '3', 'd' => '2'],
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/trades/3'],
                'base' => ['href' => 'https://horizon.stellar.org/accounts/GA5'],
                'counter' => ['href' => 'https://horizon.stellar.org/accounts/GA6'],
                'operation' => ['href' => 'https://horizon.stellar.org/operations/3']
            ]
        ]);

        $collection->add($trade3);
        $this->assertEquals(3, $collection->count());

        $iteratedIds = [];
        foreach ($collection as $trade) {
            $iteratedIds[] = $trade->getId();
        }
        $this->assertEquals(['1', '2', '3'], $iteratedIds);
    }

    // TradesPageResponse Tests

    public function testTradesPageResponseFromJson(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/trades?cursor=&limit=2&order=asc'],
                'next' => ['href' => 'https://horizon.stellar.org/trades?cursor=3697472920621057-0&limit=2&order=asc'],
                'prev' => ['href' => 'https://horizon.stellar.org/trades?cursor=3697472920621057-0&limit=2&order=desc']
            ],
            '_embedded' => [
                'records' => [
                    [
                        'id' => '3697472920621057-0',
                        'paging_token' => '3697472920621057-0',
                        'ledger_close_time' => '2024-01-15T10:30:45Z',
                        'trade_type' => 'orderbook',
                        'base_offer_id' => '165563085',
                        'base_account' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
                        'base_amount' => '100.0000000',
                        'base_asset_type' => 'native',
                        'counter_offer_id' => '165563086',
                        'counter_account' => 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6',
                        'counter_amount' => '150.0000000',
                        'counter_asset_type' => 'native',
                        'base_is_seller' => true,
                        'price' => ['n' => '3', 'd' => '2'],
                        '_links' => [
                            'self' => ['href' => 'https://horizon.stellar.org/trades/3697472920621057-0'],
                            'base' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7'],
                            'counter' => ['href' => 'https://horizon.stellar.org/accounts/GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6'],
                            'operation' => ['href' => 'https://horizon.stellar.org/operations/12884905985']
                        ]
                    ],
                    [
                        'id' => '3697472920621058-0',
                        'paging_token' => '3697472920621058-0',
                        'ledger_close_time' => '2024-01-15T10:31:00Z',
                        'trade_type' => 'liquidity_pool',
                        'base_liquidity_pool_id' => '67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9',
                        'liquidity_pool_fee_bp' => 30,
                        'base_amount' => '50.0000000',
                        'base_asset_type' => 'native',
                        'counter_account' => 'GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6',
                        'counter_amount' => '75.0000000',
                        'counter_asset_type' => 'native',
                        'base_is_seller' => false,
                        'price' => ['n' => '150', 'd' => '100'],
                        '_links' => [
                            'self' => ['href' => 'https://horizon.stellar.org/trades/3697472920621058-0'],
                            'base' => ['href' => 'https://horizon.stellar.org/liquidity_pools/67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9'],
                            'counter' => ['href' => 'https://horizon.stellar.org/accounts/GBVKI23OQZCANDUZ2YYHMJJH2PPJZZWHE4PCOQJ7UFGMYPBPZVXCYPW6'],
                            'operation' => ['href' => 'https://horizon.stellar.org/operations/12884905986']
                        ]
                    ]
                ]
            ]
        ];

        $response = TradesPageResponse::fromJson($json);

        $this->assertInstanceOf(TradesResponse::class, $response->getTrades());
        $this->assertEquals(2, $response->getTrades()->count());

        $trades = $response->getTrades()->toArray();
        $this->assertEquals('3697472920621057-0', $trades[0]->getId());
        $this->assertEquals('orderbook', $trades[0]->getTradeType());
        $this->assertEquals('3697472920621058-0', $trades[1]->getId());
        $this->assertEquals('liquidity_pool', $trades[1]->getTradeType());

        $this->assertEquals('https://horizon.stellar.org/trades?cursor=3697472920621057-0&limit=2&order=asc', $response->getLinks()->getNext()->getHref());
        $this->assertEquals('https://horizon.stellar.org/trades?cursor=3697472920621057-0&limit=2&order=desc', $response->getLinks()->getPrev()->getHref());
    }

    // TradeAggregationResponse Tests

    public function testTradeAggregationResponseFromJson(): void
    {
        $json = [
            'timestamp' => '1705315200000',
            'trade_count' => '26',
            'base_volume' => '1000.0000000',
            'counter_volume' => '1500.0000000',
            'avg' => '1.5000000',
            'high' => '1.6000000',
            'high_r' => ['n' => '8', 'd' => '5'],
            'low' => '1.4000000',
            'low_r' => ['n' => '7', 'd' => '5'],
            'open' => '1.4500000',
            'open_r' => ['n' => '29', 'd' => '20'],
            'close' => '1.5500000',
            'close_r' => ['n' => '31', 'd' => '20']
        ];

        $response = TradeAggregationResponse::fromJson($json);

        $this->assertEquals('1705315200000', $response->getTimestamp());
        $this->assertEquals('26', $response->getTradeCount());
        $this->assertEquals('1000.0000000', $response->getBaseVolume());
        $this->assertEquals('1500.0000000', $response->getCounterVolume());
        $this->assertEquals('1.5000000', $response->getAveragePrice());
        $this->assertEquals('1.6000000', $response->getHighPrice());
        $this->assertEquals('1.4000000', $response->getLowPrice());
        $this->assertEquals('1.4500000', $response->getOpenPrice());
        $this->assertEquals('1.5500000', $response->getClosePrice());

        $this->assertInstanceOf(TradePriceResponse::class, $response->getHighPriceR());
        $this->assertEquals('8', $response->getHighPriceR()->getN());
        $this->assertEquals('5', $response->getHighPriceR()->getD());

        $this->assertInstanceOf(TradePriceResponse::class, $response->getLowPriceR());
        $this->assertEquals('7', $response->getLowPriceR()->getN());
        $this->assertEquals('5', $response->getLowPriceR()->getD());

        $this->assertInstanceOf(TradePriceResponse::class, $response->getOpenPriceR());
        $this->assertEquals('29', $response->getOpenPriceR()->getN());
        $this->assertEquals('20', $response->getOpenPriceR()->getD());

        $this->assertInstanceOf(TradePriceResponse::class, $response->getClosePriceR());
        $this->assertEquals('31', $response->getClosePriceR()->getN());
        $this->assertEquals('20', $response->getClosePriceR()->getD());
    }

    public function testTradeAggregationResponseWithLargeVolumes(): void
    {
        $json = [
            'timestamp' => '1705315200000',
            'trade_count' => '1500',
            'base_volume' => '999999999.9999999',
            'counter_volume' => '1499999999.9999999',
            'avg' => '1.5000000',
            'high' => '2.0000000',
            'high_r' => ['n' => '2', 'd' => '1'],
            'low' => '1.0000000',
            'low_r' => ['n' => '1', 'd' => '1'],
            'open' => '1.2500000',
            'open_r' => ['n' => '5', 'd' => '4'],
            'close' => '1.7500000',
            'close_r' => ['n' => '7', 'd' => '4']
        ];

        $response = TradeAggregationResponse::fromJson($json);

        $this->assertEquals('1500', $response->getTradeCount());
        $this->assertEquals('999999999.9999999', $response->getBaseVolume());
        $this->assertEquals('1499999999.9999999', $response->getCounterVolume());
        $this->assertEquals('2.0000000', $response->getHighPrice());
        $this->assertEquals('1.0000000', $response->getLowPrice());
    }

    public function testTradeAggregationResponseWithMinimalData(): void
    {
        $json = [
            'timestamp' => '1705315200000',
            'trade_count' => '1',
            'base_volume' => '0.0000001',
            'counter_volume' => '0.0000001',
            'avg' => '1.0000000',
            'high' => '1.0000000',
            'high_r' => ['n' => '1', 'd' => '1'],
            'low' => '1.0000000',
            'low_r' => ['n' => '1', 'd' => '1'],
            'open' => '1.0000000',
            'open_r' => ['n' => '1', 'd' => '1'],
            'close' => '1.0000000',
            'close_r' => ['n' => '1', 'd' => '1']
        ];

        $response = TradeAggregationResponse::fromJson($json);

        $this->assertEquals('1', $response->getTradeCount());
        $this->assertEquals('0.0000001', $response->getBaseVolume());
        $this->assertEquals('0.0000001', $response->getCounterVolume());
        $this->assertEquals('1.0000000', $response->getHighPrice());
        $this->assertEquals('1.0000000', $response->getLowPrice());
        $this->assertEquals('1.0000000', $response->getOpenPrice());
        $this->assertEquals('1.0000000', $response->getClosePrice());
    }

    // TradeAggregationsResponse Tests

    public function testTradeAggregationsResponseCollection(): void
    {
        $agg1 = TradeAggregationResponse::fromJson([
            'timestamp' => '1705315200000',
            'trade_count' => '10',
            'base_volume' => '100.0000000',
            'counter_volume' => '150.0000000',
            'avg' => '1.5000000',
            'high' => '1.6000000',
            'high_r' => ['n' => '8', 'd' => '5'],
            'low' => '1.4000000',
            'low_r' => ['n' => '7', 'd' => '5'],
            'open' => '1.5000000',
            'open_r' => ['n' => '3', 'd' => '2'],
            'close' => '1.5000000',
            'close_r' => ['n' => '3', 'd' => '2']
        ]);

        $agg2 = TradeAggregationResponse::fromJson([
            'timestamp' => '1705318800000',
            'trade_count' => '15',
            'base_volume' => '200.0000000',
            'counter_volume' => '300.0000000',
            'avg' => '1.5000000',
            'high' => '1.6000000',
            'high_r' => ['n' => '8', 'd' => '5'],
            'low' => '1.4000000',
            'low_r' => ['n' => '7', 'd' => '5'],
            'open' => '1.5000000',
            'open_r' => ['n' => '3', 'd' => '2'],
            'close' => '1.5000000',
            'close_r' => ['n' => '3', 'd' => '2']
        ]);

        $collection = new TradeAggregationsResponse($agg1, $agg2);

        $this->assertEquals(2, $collection->count());

        $array = $collection->toArray();
        $this->assertCount(2, $array);
        $this->assertInstanceOf(TradeAggregationResponse::class, $array[0]);
        $this->assertInstanceOf(TradeAggregationResponse::class, $array[1]);
        $this->assertEquals('1705315200000', $array[0]->getTimestamp());
        $this->assertEquals('1705318800000', $array[1]->getTimestamp());

        $agg3 = TradeAggregationResponse::fromJson([
            'timestamp' => '1705322400000',
            'trade_count' => '20',
            'base_volume' => '300.0000000',
            'counter_volume' => '450.0000000',
            'avg' => '1.5000000',
            'high' => '1.6000000',
            'high_r' => ['n' => '8', 'd' => '5'],
            'low' => '1.4000000',
            'low_r' => ['n' => '7', 'd' => '5'],
            'open' => '1.5000000',
            'open_r' => ['n' => '3', 'd' => '2'],
            'close' => '1.5000000',
            'close_r' => ['n' => '3', 'd' => '2']
        ]);

        $collection->add($agg3);
        $this->assertEquals(3, $collection->count());

        $iteratedTimestamps = [];
        foreach ($collection as $agg) {
            $iteratedTimestamps[] = $agg->getTimestamp();
        }
        $this->assertEquals(['1705315200000', '1705318800000', '1705322400000'], $iteratedTimestamps);
    }

    // TradeAggregationsPageResponse Tests

    public function testTradeAggregationsPageResponseFromJson(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/trade_aggregations?base_asset_type=native&counter_asset_code=USD&counter_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX&counter_asset_type=credit_alphanum4&limit=2&order=asc&resolution=3600000&start_time=1705315200000'],
                'next' => ['href' => 'https://horizon.stellar.org/trade_aggregations?base_asset_type=native&counter_asset_code=USD&counter_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX&counter_asset_type=credit_alphanum4&limit=2&order=asc&resolution=3600000&start_time=1705322400000'],
                'prev' => ['href' => 'https://horizon.stellar.org/trade_aggregations?base_asset_type=native&counter_asset_code=USD&counter_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX&counter_asset_type=credit_alphanum4&limit=2&order=desc&resolution=3600000&start_time=1705315200000']
            ],
            '_embedded' => [
                'records' => [
                    [
                        'timestamp' => '1705315200000',
                        'trade_count' => '26',
                        'base_volume' => '1000.0000000',
                        'counter_volume' => '1500.0000000',
                        'avg' => '1.5000000',
                        'high' => '1.6000000',
                        'high_r' => ['n' => '8', 'd' => '5'],
                        'low' => '1.4000000',
                        'low_r' => ['n' => '7', 'd' => '5'],
                        'open' => '1.4500000',
                        'open_r' => ['n' => '29', 'd' => '20'],
                        'close' => '1.5500000',
                        'close_r' => ['n' => '31', 'd' => '20']
                    ],
                    [
                        'timestamp' => '1705318800000',
                        'trade_count' => '32',
                        'base_volume' => '1200.0000000',
                        'counter_volume' => '1800.0000000',
                        'avg' => '1.5000000',
                        'high' => '1.7000000',
                        'high_r' => ['n' => '17', 'd' => '10'],
                        'low' => '1.3000000',
                        'low_r' => ['n' => '13', 'd' => '10'],
                        'open' => '1.5500000',
                        'open_r' => ['n' => '31', 'd' => '20'],
                        'close' => '1.4000000',
                        'close_r' => ['n' => '7', 'd' => '5']
                    ]
                ]
            ]
        ];

        $response = TradeAggregationsPageResponse::fromJson($json);

        $this->assertInstanceOf(TradeAggregationsResponse::class, $response->getTradeAggregations());
        $this->assertEquals(2, $response->getTradeAggregations()->count());

        $aggregations = $response->getTradeAggregations()->toArray();
        $this->assertEquals('1705315200000', $aggregations[0]->getTimestamp());
        $this->assertEquals('26', $aggregations[0]->getTradeCount());
        $this->assertEquals('1705318800000', $aggregations[1]->getTimestamp());
        $this->assertEquals('32', $aggregations[1]->getTradeCount());

        $this->assertStringContainsString('start_time=1705322400000', $response->getLinks()->getNext()->getHref());
        $this->assertStringContainsString('order=desc', $response->getLinks()->getPrev()->getHref());
    }

    public function testTradeAggregationsPageResponseWithEmptyRecords(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/trade_aggregations?resolution=3600000'],
                'next' => ['href' => 'https://horizon.stellar.org/trade_aggregations?resolution=3600000'],
                'prev' => ['href' => 'https://horizon.stellar.org/trade_aggregations?resolution=3600000']
            ],
            '_embedded' => [
                'records' => []
            ]
        ];

        $response = TradeAggregationsPageResponse::fromJson($json);

        $this->assertInstanceOf(TradeAggregationsResponse::class, $response->getTradeAggregations());
        $this->assertEquals(0, $response->getTradeAggregations()->count());
    }

    public function testTradeAggregationsPageResponseWithSingleRecord(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/trade_aggregations?resolution=3600000'],
                'next' => ['href' => 'https://horizon.stellar.org/trade_aggregations?resolution=3600000'],
                'prev' => ['href' => 'https://horizon.stellar.org/trade_aggregations?resolution=3600000']
            ],
            '_embedded' => [
                'records' => [
                    [
                        'timestamp' => '1705315200000',
                        'trade_count' => '5',
                        'base_volume' => '100.0000000',
                        'counter_volume' => '150.0000000',
                        'avg' => '1.5000000',
                        'high' => '1.5000000',
                        'high_r' => ['n' => '3', 'd' => '2'],
                        'low' => '1.5000000',
                        'low_r' => ['n' => '3', 'd' => '2'],
                        'open' => '1.5000000',
                        'open_r' => ['n' => '3', 'd' => '2'],
                        'close' => '1.5000000',
                        'close_r' => ['n' => '3', 'd' => '2']
                    ]
                ]
            ]
        ];

        $response = TradeAggregationsPageResponse::fromJson($json);

        $this->assertEquals(1, $response->getTradeAggregations()->count());

        $aggregations = $response->getTradeAggregations()->toArray();
        $this->assertEquals('1705315200000', $aggregations[0]->getTimestamp());
        $this->assertEquals('5', $aggregations[0]->getTradeCount());
    }
}
