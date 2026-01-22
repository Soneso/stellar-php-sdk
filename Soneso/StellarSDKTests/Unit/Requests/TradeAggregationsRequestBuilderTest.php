<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Requests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\TradeAggregations\TradeAggregationsPageResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;

/**
 * Unit tests for TradeAggregationsRequestBuilder
 *
 * Tests URL construction, query parameters, and filtering methods for the trade aggregations endpoint.
 * TradeAggregationsRequestBuilder has 12 methods to test:
 * 1. forBaseAsset()
 * 2. forCounterAsset()
 * 3. forStartTime()
 * 4. forEndTime()
 * 5. forResolution()
 * 6. forOffset()
 * 7. cursor()
 * 8. limit()
 * 9. order()
 * 10. request()
 * 11. execute()
 * 12. buildUrl() (inherited, tested via execute)
 */
class TradeAggregationsRequestBuilderTest extends TestCase
{
    private function createMockClient(string &$requestedUrl, int $statusCode = 200, array $responseData = []): Client
    {
        $defaultResponse = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/trade_aggregations'],
                'next' => ['href' => 'https://horizon.stellar.org/trade_aggregations?cursor=next'],
                'prev' => ['href' => 'https://horizon.stellar.org/trade_aggregations?cursor=prev']
            ],
            '_embedded' => [
                'records' => []
            ]
        ];

        $responseData = array_merge($defaultResponse, $responseData);

        $mock = new MockHandler([
            new Response($statusCode, ['Content-Type' => 'application/json'], json_encode($responseData))
        ]);

        $handlerStack = HandlerStack::create($mock);

        $history = [];
        $handlerStack->push(Middleware::history($history));

        $handlerStack->push(Middleware::mapRequest(function ($request) use (&$requestedUrl) {
            $requestedUrl = (string) $request->getUri();
            return $request;
        }));

        return new Client(['handler' => $handlerStack]);
    }

    public function testForBaseAssetNative(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $baseAsset = Asset::native();
        $sdk->tradeAggregations()->forBaseAsset($baseAsset)->execute();

        $this->assertStringContainsString('base_asset_type=native', $requestedUrl);
        $this->assertStringNotContainsString('base_asset_code', $requestedUrl);
        $this->assertStringNotContainsString('base_asset_issuer', $requestedUrl);
    }

    public function testForBaseAssetCreditAlphanum4(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $baseAsset = Asset::createNonNativeAsset('USD', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');
        $sdk->tradeAggregations()->forBaseAsset($baseAsset)->execute();

        $this->assertStringContainsString('base_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('base_asset_code=USD', $requestedUrl);
        $this->assertStringContainsString('base_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $requestedUrl);
    }

    public function testForBaseAssetCreditAlphanum12(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $baseAsset = Asset::createNonNativeAsset('STELLARTOKEN', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');
        $sdk->tradeAggregations()->forBaseAsset($baseAsset)->execute();

        $this->assertStringContainsString('base_asset_type=credit_alphanum12', $requestedUrl);
        $this->assertStringContainsString('base_asset_code=STELLARTOKEN', $requestedUrl);
        $this->assertStringContainsString('base_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $requestedUrl);
    }

    public function testForCounterAssetNative(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $counterAsset = Asset::native();
        $sdk->tradeAggregations()->forCounterAsset($counterAsset)->execute();

        $this->assertStringContainsString('counter_asset_type=native', $requestedUrl);
        $this->assertStringNotContainsString('counter_asset_code', $requestedUrl);
        $this->assertStringNotContainsString('counter_asset_issuer', $requestedUrl);
    }

    public function testForCounterAssetCreditAlphanum4(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $counterAsset = Asset::createNonNativeAsset('USDC', 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');
        $sdk->tradeAggregations()->forCounterAsset($counterAsset)->execute();

        $this->assertStringContainsString('counter_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('counter_asset_code=USDC', $requestedUrl);
        $this->assertStringContainsString('counter_asset_issuer=GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $requestedUrl);
    }

    public function testForCounterAssetCreditAlphanum12(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $counterAsset = Asset::createNonNativeAsset('LONGASSET', 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');
        $sdk->tradeAggregations()->forCounterAsset($counterAsset)->execute();

        $this->assertStringContainsString('counter_asset_type=credit_alphanum12', $requestedUrl);
        $this->assertStringContainsString('counter_asset_code=LONGASSET', $requestedUrl);
        $this->assertStringContainsString('counter_asset_issuer=GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $requestedUrl);
    }

    public function testForStartTime(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->tradeAggregations()->forStartTime('1705315200000')->execute();

        $this->assertStringContainsString('start_time=1705315200000', $requestedUrl);
    }

    public function testForEndTime(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->tradeAggregations()->forEndTime('1705401600000')->execute();

        $this->assertStringContainsString('end_time=1705401600000', $requestedUrl);
    }

    public function testForResolutionOneMinute(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->tradeAggregations()->forResolution('60000')->execute();

        $this->assertStringContainsString('resolution=60000', $requestedUrl);
    }

    public function testForResolutionFiveMinutes(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->tradeAggregations()->forResolution('300000')->execute();

        $this->assertStringContainsString('resolution=300000', $requestedUrl);
    }

    public function testForResolutionOneHour(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->tradeAggregations()->forResolution('3600000')->execute();

        $this->assertStringContainsString('resolution=3600000', $requestedUrl);
    }

    public function testForResolutionOneDay(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->tradeAggregations()->forResolution('86400000')->execute();

        $this->assertStringContainsString('resolution=86400000', $requestedUrl);
    }

    public function testForResolutionOneWeek(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->tradeAggregations()->forResolution('604800000')->execute();

        $this->assertStringContainsString('resolution=604800000', $requestedUrl);
    }

    public function testForOffset(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->tradeAggregations()->forOffset('3600000')->execute();

        $this->assertStringContainsString('offset=3600000', $requestedUrl);
    }

    public function testForOffsetZero(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->tradeAggregations()->forOffset('0')->execute();

        $this->assertStringContainsString('offset=0', $requestedUrl);
    }

    public function testCursor(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->tradeAggregations()->cursor('1705315200000')->execute();

        $this->assertStringContainsString('cursor=1705315200000', $requestedUrl);
    }

    public function testCursorNow(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->tradeAggregations()->cursor('now')->execute();

        $this->assertStringContainsString('cursor=now', $requestedUrl);
    }

    public function testLimit(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->tradeAggregations()->limit(200)->execute();

        $this->assertStringContainsString('limit=200', $requestedUrl);
    }

    public function testOrderAscending(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->tradeAggregations()->order('asc')->execute();

        $this->assertStringContainsString('order=asc', $requestedUrl);
    }

    public function testOrderDescending(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->tradeAggregations()->order('desc')->execute();

        $this->assertStringContainsString('order=desc', $requestedUrl);
    }

    public function testExecuteReturnsTradeAggregationsPageResponse(): void
    {
        $requestedUrl = '';
        $responseData = [
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
                    ]
                ]
            ]
        ];

        $client = $this->createMockClient($requestedUrl, 200, $responseData);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $response = $sdk->tradeAggregations()->execute();

        $this->assertInstanceOf(TradeAggregationsPageResponse::class, $response);
        $this->assertEquals(1, $response->getTradeAggregations()->count());
        $this->assertEquals('1705315200000', $response->getTradeAggregations()->toArray()[0]->getTimestamp());
    }

    public function testRequest(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $customUrl = 'https://horizon.stellar.org/trade_aggregations?resolution=3600000&limit=10';
        $response = $sdk->tradeAggregations()->request($customUrl);

        $this->assertInstanceOf(TradeAggregationsPageResponse::class, $response);
        $this->assertEquals($customUrl, $requestedUrl);
    }

    public function testCompleteTradeAggregationRequest(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $baseAsset = Asset::native();
        $counterAsset = Asset::createNonNativeAsset('USD', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');

        $sdk->tradeAggregations()
            ->forBaseAsset($baseAsset)
            ->forCounterAsset($counterAsset)
            ->forResolution('3600000')
            ->forStartTime('1705315200000')
            ->forEndTime('1705401600000')
            ->forOffset('0')
            ->limit(100)
            ->order('asc')
            ->execute();

        $this->assertStringContainsString('base_asset_type=native', $requestedUrl);
        $this->assertStringContainsString('counter_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('counter_asset_code=USD', $requestedUrl);
        $this->assertStringContainsString('counter_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $requestedUrl);
        $this->assertStringContainsString('resolution=3600000', $requestedUrl);
        $this->assertStringContainsString('start_time=1705315200000', $requestedUrl);
        $this->assertStringContainsString('end_time=1705401600000', $requestedUrl);
        $this->assertStringContainsString('offset=0', $requestedUrl);
        $this->assertStringContainsString('limit=100', $requestedUrl);
        $this->assertStringContainsString('order=asc', $requestedUrl);
    }

    public function testTradeAggregationWithCreditToCredit(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $baseAsset = Asset::createNonNativeAsset('USD', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');
        $counterAsset = Asset::createNonNativeAsset('EUR', 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');

        $sdk->tradeAggregations()
            ->forBaseAsset($baseAsset)
            ->forCounterAsset($counterAsset)
            ->forResolution('60000')
            ->execute();

        $this->assertStringContainsString('base_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('base_asset_code=USD', $requestedUrl);
        $this->assertStringContainsString('base_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $requestedUrl);
        $this->assertStringContainsString('counter_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('counter_asset_code=EUR', $requestedUrl);
        $this->assertStringContainsString('counter_asset_issuer=GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $requestedUrl);
        $this->assertStringContainsString('resolution=60000', $requestedUrl);
    }

    public function testTradeAggregationWith12CharAssets(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $baseAsset = Asset::createNonNativeAsset('STELLARTOKEN', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');
        $counterAsset = Asset::createNonNativeAsset('BITCOINTOKEN', 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');

        $sdk->tradeAggregations()
            ->forBaseAsset($baseAsset)
            ->forCounterAsset($counterAsset)
            ->forResolution('86400000')
            ->execute();

        $this->assertStringContainsString('base_asset_type=credit_alphanum12', $requestedUrl);
        $this->assertStringContainsString('base_asset_code=STELLARTOKEN', $requestedUrl);
        $this->assertStringContainsString('counter_asset_type=credit_alphanum12', $requestedUrl);
        $this->assertStringContainsString('counter_asset_code=BITCOINTOKEN', $requestedUrl);
        $this->assertStringContainsString('resolution=86400000', $requestedUrl);
    }

    public function testBaseUrl(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->tradeAggregations()->execute();

        $this->assertStringContainsString('trade_aggregations', $requestedUrl);
    }

    public function testTradeAggregationResponseAllGetters(): void
    {
        $requestedUrl = '';
        $responseData = [
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
                    ]
                ]
            ]
        ];

        $client = $this->createMockClient($requestedUrl, 200, $responseData);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $baseAsset = Asset::createNonNativeAsset('SONESO', 'GAOF7ARG3ZAVUA63GCLXG5JQTMBAH3ZFYHGLGJLDXGDSXQRHD72LLGOB');
        $counterAsset = Asset::createNonNativeAsset('COOL', 'GAZKB7OEYRUVL6TSBXI74D2IZS4JRCPBXJZ37MDDYAEYBOMHXUYIX5YL');

        $response = $sdk->tradeAggregations()
            ->forBaseAsset($baseAsset)
            ->forCounterAsset($counterAsset)
            ->forResolution('60000')
            ->order('desc')
            ->execute();

        $this->assertInstanceOf(TradeAggregationsPageResponse::class, $response);
        $this->assertEquals(1, $response->getTradeAggregations()->count());

        $tradeAggregation = $response->getTradeAggregations()->toArray()[0];

        // Test all getters
        $this->assertEquals('1705315200000', $tradeAggregation->getTimestamp());
        $this->assertEquals('26', $tradeAggregation->getTradeCount());
        $this->assertEquals('1000.0000000', $tradeAggregation->getBaseVolume());
        $this->assertEquals('1500.0000000', $tradeAggregation->getCounterVolume());
        $this->assertEquals('1.5000000', $tradeAggregation->getAveragePrice());
        $this->assertEquals('1.6000000', $tradeAggregation->getHighPrice());
        $this->assertEquals(8, $tradeAggregation->getHighPriceR()->getN());
        $this->assertEquals(5, $tradeAggregation->getHighPriceR()->getD());
        $this->assertEquals('1.4000000', $tradeAggregation->getLowPrice());
        $this->assertEquals(7, $tradeAggregation->getLowPriceR()->getN());
        $this->assertEquals(5, $tradeAggregation->getLowPriceR()->getD());
        $this->assertEquals('1.4500000', $tradeAggregation->getOpenPrice());
        $this->assertEquals(29, $tradeAggregation->getOpenPriceR()->getN());
        $this->assertEquals(20, $tradeAggregation->getOpenPriceR()->getD());
        $this->assertEquals('1.5500000', $tradeAggregation->getClosePrice());
        $this->assertEquals(31, $tradeAggregation->getClosePriceR()->getN());
        $this->assertEquals(20, $tradeAggregation->getClosePriceR()->getD());
    }

    public function testTradeAggregationResponseMultipleRecords(): void
    {
        $requestedUrl = '';
        $responseData = [
            '_embedded' => [
                'records' => [
                    [
                        'timestamp' => '1705315200000',
                        'trade_count' => '10',
                        'base_volume' => '500.0000000',
                        'counter_volume' => '750.0000000',
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
                        'trade_count' => '15',
                        'base_volume' => '800.0000000',
                        'counter_volume' => '1200.0000000',
                        'avg' => '1.5500000',
                        'high' => '1.7000000',
                        'high_r' => ['n' => '17', 'd' => '10'],
                        'low' => '1.4500000',
                        'low_r' => ['n' => '29', 'd' => '20'],
                        'open' => '1.5500000',
                        'open_r' => ['n' => '31', 'd' => '20'],
                        'close' => '1.6500000',
                        'close_r' => ['n' => '33', 'd' => '20']
                    ]
                ]
            ]
        ];

        $client = $this->createMockClient($requestedUrl, 200, $responseData);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $response = $sdk->tradeAggregations()
            ->forResolution('3600000')
            ->execute();

        $this->assertEquals(2, $response->getTradeAggregations()->count());

        $records = $response->getTradeAggregations()->toArray();
        $this->assertEquals('1705315200000', $records[0]->getTimestamp());
        $this->assertEquals('10', $records[0]->getTradeCount());
        $this->assertEquals('1705318800000', $records[1]->getTimestamp());
        $this->assertEquals('15', $records[1]->getTradeCount());
    }

    public function testTradeAggregationEmptyResponse(): void
    {
        $requestedUrl = '';
        $responseData = [
            '_embedded' => [
                'records' => []
            ]
        ];

        $client = $this->createMockClient($requestedUrl, 200, $responseData);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $response = $sdk->tradeAggregations()
            ->forResolution('60000')
            ->execute();

        $this->assertInstanceOf(TradeAggregationsPageResponse::class, $response);
        $this->assertEquals(0, $response->getTradeAggregations()->count());
    }
}
