<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Requests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Trades\TradesPageResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;

/**
 * Unit tests for TradesRequestBuilder
 *
 * Tests URL construction, query parameters, and filtering methods for the trades endpoint.
 * TradesRequestBuilder has 13 methods to test:
 * 1. forOffer()
 * 2. forTradeType()
 * 3. forBaseAsset()
 * 4. forCounterAsset()
 * 5. forLiquidityPool()
 * 6. forAccount()
 * 7. cursor()
 * 8. limit()
 * 9. order()
 * 10. request()
 * 11. execute()
 * 12. stream()
 * 13. buildUrl() (inherited, tested via execute)
 */
class TradesRequestBuilderTest extends TestCase
{
    private function createMockClient(string &$requestedUrl, int $statusCode = 200, array $responseData = []): Client
    {
        $defaultResponse = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/trades'],
                'next' => ['href' => 'https://horizon.stellar.org/trades?cursor=next'],
                'prev' => ['href' => 'https://horizon.stellar.org/trades?cursor=prev']
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

    public function testForOffer(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->trades()->forOffer('165563085')->execute();

        $this->assertStringContainsString('offer_id=165563085', $requestedUrl);
    }

    public function testForTradeTypeOrderbook(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->trades()->forTradeType('orderbook')->execute();

        $this->assertStringContainsString('trade_type=orderbook', $requestedUrl);
    }

    public function testForTradeTypeLiquidityPools(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->trades()->forTradeType('liquidity_pools')->execute();

        $this->assertStringContainsString('trade_type=liquidity_pools', $requestedUrl);
    }

    public function testForTradeTypeAll(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->trades()->forTradeType('all')->execute();

        $this->assertStringContainsString('trade_type=all', $requestedUrl);
    }

    public function testForBaseAssetNative(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $baseAsset = Asset::native();
        $sdk->trades()->forBaseAsset($baseAsset)->execute();

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
        $sdk->trades()->forBaseAsset($baseAsset)->execute();

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
        $sdk->trades()->forBaseAsset($baseAsset)->execute();

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
        $sdk->trades()->forCounterAsset($counterAsset)->execute();

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
        $sdk->trades()->forCounterAsset($counterAsset)->execute();

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
        $sdk->trades()->forCounterAsset($counterAsset)->execute();

        $this->assertStringContainsString('counter_asset_type=credit_alphanum12', $requestedUrl);
        $this->assertStringContainsString('counter_asset_code=LONGASSET', $requestedUrl);
        $this->assertStringContainsString('counter_asset_issuer=GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $requestedUrl);
    }

    public function testForLiquidityPoolWithHexId(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $poolId = '67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9';
        $sdk->trades()->forLiquidityPool($poolId)->execute();

        $this->assertStringContainsString('liquidity_pools/' . $poolId . '/trades', $requestedUrl);
    }

    public function testForLiquidityPoolWithLPrefixedId(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        // Create a valid L-prefixed liquidity pool ID
        $hexId = '67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9';
        $sdk->trades()->forLiquidityPool($hexId)->execute();

        $this->assertStringContainsString('liquidity_pools/' . $hexId . '/trades', $requestedUrl);
    }

    public function testForAccount(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $accountId = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
        $sdk->trades()->forAccount($accountId)->execute();

        $this->assertStringContainsString('accounts/' . $accountId . '/trades', $requestedUrl);
    }

    public function testCursor(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->trades()->cursor('3697472920621057-0')->execute();

        $this->assertStringContainsString('cursor=3697472920621057-0', $requestedUrl);
    }

    public function testCursorNow(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->trades()->cursor('now')->execute();

        $this->assertStringContainsString('cursor=now', $requestedUrl);
    }

    public function testLimit(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->trades()->limit(50)->execute();

        $this->assertStringContainsString('limit=50', $requestedUrl);
    }

    public function testOrderAscending(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->trades()->order('asc')->execute();

        $this->assertStringContainsString('order=asc', $requestedUrl);
    }

    public function testOrderDescending(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->trades()->order('desc')->execute();

        $this->assertStringContainsString('order=desc', $requestedUrl);
    }

    public function testExecuteReturnsTradesPageResponse(): void
    {
        $requestedUrl = '';
        $responseData = [
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
                            'base' => ['href' => 'https://horizon.stellar.org/accounts/GA1'],
                            'counter' => ['href' => 'https://horizon.stellar.org/accounts/GA2'],
                            'operation' => ['href' => 'https://horizon.stellar.org/operations/1']
                        ]
                    ]
                ]
            ]
        ];

        $client = $this->createMockClient($requestedUrl, 200, $responseData);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $response = $sdk->trades()->execute();

        $this->assertInstanceOf(TradesPageResponse::class, $response);
        $this->assertEquals(1, $response->getTrades()->count());
        $this->assertEquals('3697472920621057-0', $response->getTrades()->toArray()[0]->getId());
    }

    public function testRequest(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $customUrl = 'https://horizon.stellar.org/trades?cursor=test&limit=10';
        $response = $sdk->trades()->request($customUrl);

        $this->assertInstanceOf(TradesPageResponse::class, $response);
        $this->assertEquals($customUrl, $requestedUrl);
    }

    public function testMultipleFilters(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $baseAsset = Asset::native();
        $counterAsset = Asset::createNonNativeAsset('USD', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');

        $sdk->trades()
            ->forBaseAsset($baseAsset)
            ->forCounterAsset($counterAsset)
            ->forTradeType('orderbook')
            ->limit(20)
            ->order('desc')
            ->cursor('now')
            ->execute();

        $this->assertStringContainsString('base_asset_type=native', $requestedUrl);
        $this->assertStringContainsString('counter_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('counter_asset_code=USD', $requestedUrl);
        $this->assertStringContainsString('counter_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $requestedUrl);
        $this->assertStringContainsString('trade_type=orderbook', $requestedUrl);
        $this->assertStringContainsString('limit=20', $requestedUrl);
        $this->assertStringContainsString('order=desc', $requestedUrl);
        $this->assertStringContainsString('cursor=now', $requestedUrl);
    }

    public function testForOfferWithMultipleFilters(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->trades()
            ->forOffer('123456')
            ->limit(10)
            ->order('asc')
            ->execute();

        $this->assertStringContainsString('offer_id=123456', $requestedUrl);
        $this->assertStringContainsString('limit=10', $requestedUrl);
        $this->assertStringContainsString('order=asc', $requestedUrl);
    }

    public function testBaseUrl(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->trades()->execute();

        $this->assertStringContainsString('trades', $requestedUrl);
    }
}
