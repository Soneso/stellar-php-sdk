<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Requests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\OrderBook\OrderBookResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;

/**
 * Unit tests for OrderBookRequestBuilder
 *
 * Tests URL construction, query parameters, and filtering methods for the order book endpoint.
 * OrderBookRequestBuilder methods to test:
 * 1. forBuyingAsset()
 * 2. forSellingAsset()
 * 3. cursor()
 * 4. limit()
 * 5. order()
 * 6. request()
 * 7. execute()
 * 8. stream()
 * 9. buildUrl() (inherited, tested via execute)
 */
class OrderBookRequestBuilderTest extends TestCase
{
    private function createMockClient(string &$requestedUrl, int $statusCode = 200, array $responseData = []): Client
    {
        $defaultResponse = [
            'bids' => [],
            'asks' => [],
            'base' => [
                'asset_type' => 'native'
            ],
            'counter' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'USD',
                'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
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

    public function testForBuyingAssetNative(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $buyingAsset = Asset::native();
        $sdk->orderBook()->forBuyingAsset($buyingAsset)->execute();

        $this->assertStringContainsString('buying_asset_type=native', $requestedUrl);
        $this->assertStringNotContainsString('buying_asset_code', $requestedUrl);
        $this->assertStringNotContainsString('buying_asset_issuer', $requestedUrl);
    }

    public function testForBuyingAssetCreditAlphanum4(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $buyingAsset = Asset::createNonNativeAsset('USD', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');
        $sdk->orderBook()->forBuyingAsset($buyingAsset)->execute();

        $this->assertStringContainsString('buying_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('buying_asset_code=USD', $requestedUrl);
        $this->assertStringContainsString('buying_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $requestedUrl);
    }

    public function testForBuyingAssetCreditAlphanum12(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $buyingAsset = Asset::createNonNativeAsset('STELLARTOKEN', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');
        $sdk->orderBook()->forBuyingAsset($buyingAsset)->execute();

        $this->assertStringContainsString('buying_asset_type=credit_alphanum12', $requestedUrl);
        $this->assertStringContainsString('buying_asset_code=STELLARTOKEN', $requestedUrl);
        $this->assertStringContainsString('buying_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $requestedUrl);
    }

    public function testForSellingAssetNative(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sellingAsset = Asset::native();
        $sdk->orderBook()->forSellingAsset($sellingAsset)->execute();

        $this->assertStringContainsString('selling_asset_type=native', $requestedUrl);
        $this->assertStringNotContainsString('selling_asset_code', $requestedUrl);
        $this->assertStringNotContainsString('selling_asset_issuer', $requestedUrl);
    }

    public function testForSellingAssetCreditAlphanum4(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sellingAsset = Asset::createNonNativeAsset('EUR', 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');
        $sdk->orderBook()->forSellingAsset($sellingAsset)->execute();

        $this->assertStringContainsString('selling_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('selling_asset_code=EUR', $requestedUrl);
        $this->assertStringContainsString('selling_asset_issuer=GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $requestedUrl);
    }

    public function testForSellingAssetCreditAlphanum12(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sellingAsset = Asset::createNonNativeAsset('BITCOINTOKEN', 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');
        $sdk->orderBook()->forSellingAsset($sellingAsset)->execute();

        $this->assertStringContainsString('selling_asset_type=credit_alphanum12', $requestedUrl);
        $this->assertStringContainsString('selling_asset_code=BITCOINTOKEN', $requestedUrl);
        $this->assertStringContainsString('selling_asset_issuer=GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $requestedUrl);
    }

    public function testCursor(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->orderBook()->cursor('12345')->execute();

        $this->assertStringContainsString('cursor=12345', $requestedUrl);
    }

    public function testCursorNow(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->orderBook()->cursor('now')->execute();

        $this->assertStringContainsString('cursor=now', $requestedUrl);
    }

    public function testLimit(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->orderBook()->limit(10)->execute();

        $this->assertStringContainsString('limit=10', $requestedUrl);
    }

    public function testLimitForDepth(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->orderBook()->limit(20)->execute();

        $this->assertStringContainsString('limit=20', $requestedUrl);
    }

    public function testOrderAscending(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->orderBook()->order('asc')->execute();

        $this->assertStringContainsString('order=asc', $requestedUrl);
    }

    public function testOrderDescending(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->orderBook()->order('desc')->execute();

        $this->assertStringContainsString('order=desc', $requestedUrl);
    }

    public function testExecuteReturnsOrderBookResponse(): void
    {
        $requestedUrl = '';
        $responseData = [
            'bids' => [
                [
                    'price_r' => ['n' => 100, 'd' => 150],
                    'price' => '0.6666667',
                    'amount' => '50.0000000'
                ]
            ],
            'asks' => [
                [
                    'price_r' => ['n' => 150, 'd' => 100],
                    'price' => '1.5000000',
                    'amount' => '100.0000000'
                ]
            ],
            'base' => [
                'asset_type' => 'native'
            ],
            'counter' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'USD',
                'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
            ]
        ];

        $client = $this->createMockClient($requestedUrl, 200, $responseData);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $response = $sdk->orderBook()->execute();

        $this->assertInstanceOf(OrderBookResponse::class, $response);
        $this->assertEquals(1, $response->getBids()->count());
        $this->assertEquals(1, $response->getAsks()->count());
        $this->assertEquals('0.6666667', $response->getBids()->toArray()[0]->getPrice());
        $this->assertEquals('1.5000000', $response->getAsks()->toArray()[0]->getPrice());
    }

    public function testRequest(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $customUrl = 'https://horizon.stellar.org/order_book?buying_asset_type=native&selling_asset_type=credit_alphanum4';
        $response = $sdk->orderBook()->request($customUrl);

        $this->assertInstanceOf(OrderBookResponse::class, $response);
        $this->assertEquals($customUrl, $requestedUrl);
    }

    public function testOrderBookForNativeToCredit(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $buyingAsset = Asset::native();
        $sellingAsset = Asset::createNonNativeAsset('USD', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');

        $sdk->orderBook()
            ->forBuyingAsset($buyingAsset)
            ->forSellingAsset($sellingAsset)
            ->execute();

        $this->assertStringContainsString('buying_asset_type=native', $requestedUrl);
        $this->assertStringContainsString('selling_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('selling_asset_code=USD', $requestedUrl);
        $this->assertStringContainsString('selling_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $requestedUrl);
    }

    public function testOrderBookForCreditToNative(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $buyingAsset = Asset::createNonNativeAsset('EUR', 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');
        $sellingAsset = Asset::native();

        $sdk->orderBook()
            ->forBuyingAsset($buyingAsset)
            ->forSellingAsset($sellingAsset)
            ->execute();

        $this->assertStringContainsString('buying_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('buying_asset_code=EUR', $requestedUrl);
        $this->assertStringContainsString('buying_asset_issuer=GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $requestedUrl);
        $this->assertStringContainsString('selling_asset_type=native', $requestedUrl);
    }

    public function testOrderBookForCreditToCredit(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $buyingAsset = Asset::createNonNativeAsset('USD', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');
        $sellingAsset = Asset::createNonNativeAsset('EUR', 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');

        $sdk->orderBook()
            ->forBuyingAsset($buyingAsset)
            ->forSellingAsset($sellingAsset)
            ->execute();

        $this->assertStringContainsString('buying_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('buying_asset_code=USD', $requestedUrl);
        $this->assertStringContainsString('buying_asset_issuer=GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $requestedUrl);
        $this->assertStringContainsString('selling_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('selling_asset_code=EUR', $requestedUrl);
        $this->assertStringContainsString('selling_asset_issuer=GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN', $requestedUrl);
    }

    public function testOrderBookWith12CharAssets(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $buyingAsset = Asset::createNonNativeAsset('STELLARTOKEN', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');
        $sellingAsset = Asset::createNonNativeAsset('BITCOINTOKEN', 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');

        $sdk->orderBook()
            ->forBuyingAsset($buyingAsset)
            ->forSellingAsset($sellingAsset)
            ->execute();

        $this->assertStringContainsString('buying_asset_type=credit_alphanum12', $requestedUrl);
        $this->assertStringContainsString('buying_asset_code=STELLARTOKEN', $requestedUrl);
        $this->assertStringContainsString('selling_asset_type=credit_alphanum12', $requestedUrl);
        $this->assertStringContainsString('selling_asset_code=BITCOINTOKEN', $requestedUrl);
    }

    public function testOrderBookWithLimitAndOrder(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $buyingAsset = Asset::native();
        $sellingAsset = Asset::createNonNativeAsset('USD', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');

        $sdk->orderBook()
            ->forBuyingAsset($buyingAsset)
            ->forSellingAsset($sellingAsset)
            ->limit(5)
            ->order('desc')
            ->execute();

        $this->assertStringContainsString('limit=5', $requestedUrl);
        $this->assertStringContainsString('order=desc', $requestedUrl);
    }

    public function testOrderBookWithAllParameters(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $buyingAsset = Asset::createNonNativeAsset('USD', 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX');
        $sellingAsset = Asset::createNonNativeAsset('EUR', 'GA5ZSEJYB37JRC5AVCIA5MOP4RHTM335X2KGX3IHOJAPP5RE34K4KZVN');

        $sdk->orderBook()
            ->forBuyingAsset($buyingAsset)
            ->forSellingAsset($sellingAsset)
            ->limit(15)
            ->order('asc')
            ->cursor('now')
            ->execute();

        $this->assertStringContainsString('buying_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('buying_asset_code=USD', $requestedUrl);
        $this->assertStringContainsString('selling_asset_type=credit_alphanum4', $requestedUrl);
        $this->assertStringContainsString('selling_asset_code=EUR', $requestedUrl);
        $this->assertStringContainsString('limit=15', $requestedUrl);
        $this->assertStringContainsString('order=asc', $requestedUrl);
        $this->assertStringContainsString('cursor=now', $requestedUrl);
    }

    public function testOrderBookResponseWithEmptyBidsAndAsks(): void
    {
        $requestedUrl = '';
        $responseData = [
            'bids' => [],
            'asks' => [],
            'base' => [
                'asset_type' => 'native'
            ],
            'counter' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'USD',
                'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
            ]
        ];

        $client = $this->createMockClient($requestedUrl, 200, $responseData);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $response = $sdk->orderBook()->execute();

        $this->assertInstanceOf(OrderBookResponse::class, $response);
        $this->assertEquals(0, $response->getBids()->count());
        $this->assertEquals(0, $response->getAsks()->count());
    }

    public function testOrderBookResponseWithMultipleBidsAndAsks(): void
    {
        $requestedUrl = '';
        $responseData = [
            'bids' => [
                [
                    'price_r' => ['n' => 100, 'd' => 150],
                    'price' => '0.6666667',
                    'amount' => '50.0000000'
                ],
                [
                    'price_r' => ['n' => 95, 'd' => 150],
                    'price' => '0.6333333',
                    'amount' => '75.0000000'
                ]
            ],
            'asks' => [
                [
                    'price_r' => ['n' => 150, 'd' => 100],
                    'price' => '1.5000000',
                    'amount' => '100.0000000'
                ],
                [
                    'price_r' => ['n' => 155, 'd' => 100],
                    'price' => '1.5500000',
                    'amount' => '125.0000000'
                ]
            ],
            'base' => [
                'asset_type' => 'native'
            ],
            'counter' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'USD',
                'asset_issuer' => 'GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
            ]
        ];

        $client = $this->createMockClient($requestedUrl, 200, $responseData);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $response = $sdk->orderBook()->execute();

        $this->assertInstanceOf(OrderBookResponse::class, $response);
        $this->assertEquals(2, $response->getBids()->count());
        $this->assertEquals(2, $response->getAsks()->count());

        $bids = $response->getBids()->toArray();
        $this->assertEquals('0.6666667', $bids[0]->getPrice());
        $this->assertEquals('0.6333333', $bids[1]->getPrice());

        $asks = $response->getAsks()->toArray();
        $this->assertEquals('1.5000000', $asks[0]->getPrice());
        $this->assertEquals('1.5500000', $asks[1]->getPrice());
    }

    public function testBaseUrl(): void
    {
        $requestedUrl = '';
        $client = $this->createMockClient($requestedUrl);

        $sdk = new StellarSDK('https://horizon.stellar.org');
        $sdk->setHttpClient($client);

        $sdk->orderBook()->execute();

        $this->assertStringContainsString('order_book', $requestedUrl);
    }
}
