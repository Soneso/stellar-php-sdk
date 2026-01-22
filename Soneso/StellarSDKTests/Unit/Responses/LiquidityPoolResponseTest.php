<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Responses;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolsResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolsPageResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolLinksResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolPriceResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReserveResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\ReservesResponse;

/**
 * Unit tests for all LiquidityPool Response classes
 *
 * Tests JSON parsing and getter methods for LiquidityPool-related response classes.
 * Covers constant_product pool type, reserves parsing, pagination, and all getter methods.
 */
class LiquidityPoolResponseTest extends TestCase
{
    /**
     * Helper method to create complete liquidity pool JSON data for constant_product type
     */
    private function getCompleteLiquidityPoolJson(): array
    {
        return [
            'id' => 'dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7',
            'paging_token' => '113725249324879873',
            'fee_bp' => 30,
            'type' => 'constant_product',
            'total_trustlines' => '500',
            'total_shares' => '5000.0000000',
            'reserves' => [
                [
                    'amount' => '1000.0000000',
                    'asset' => 'EURT:GAP5LETOV6YIE62YAM56STDANPRDO7ZFDBGSNHJQIYGGKSMOZAHOOS2S'
                ],
                [
                    'amount' => '2000.0000000',
                    'asset' => 'PHP:GAP5LETOV6YIE62YAM56STDANPRDO7ZFDBGSNHJQIYGGKSMOZAHOOS2S'
                ]
            ],
            'last_modified_ledger' => 1234567,
            'last_modified_time' => '2021-11-18T03:47:47Z',
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/liquidity_pools/dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7'
                ],
                'operations' => [
                    'href' => 'https://horizon.stellar.org/liquidity_pools/dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7/operations',
                    'templated' => true
                ],
                'transactions' => [
                    'href' => 'https://horizon.stellar.org/liquidity_pools/dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7/transactions',
                    'templated' => true
                ]
            ]
        ];
    }

    /**
     * Helper method to create minimal liquidity pool JSON data
     */
    private function getMinimalLiquidityPoolJson(): array
    {
        return [
            'id' => 'a468d41d8e9b8f3c9c3c8f8e2d4b1a9e7c6f5d4e3c2b1a0f9e8d7c6b5a4e3d2c',
            'paging_token' => '123456789',
            'fee_bp' => 30,
            'type' => 'constant_product',
            'total_trustlines' => '100',
            'total_shares' => '1000.0000000',
            'reserves' => [],
            'last_modified_ledger' => 100000,
            'last_modified_time' => '2021-01-01T00:00:00Z',
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/liquidity_pools/a468d41d8e9b8f3c9c3c8f8e2d4b1a9e7c6f5d4e3c2b1a0f9e8d7c6b5a4e3d2c']
            ]
        ];
    }

    /**
     * Helper method to create liquidity pools page JSON with embedded records
     */
    private function getLiquidityPoolsPageJson(): array
    {
        return [
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/liquidity_pools?cursor=&limit=10&order=asc'
                ],
                'next' => [
                    'href' => 'https://horizon.stellar.org/liquidity_pools?cursor=113725249324879873&limit=10&order=asc'
                ],
                'prev' => [
                    'href' => 'https://horizon.stellar.org/liquidity_pools?cursor=113725249324879873&limit=10&order=desc'
                ]
            ],
            '_embedded' => [
                'records' => [
                    $this->getCompleteLiquidityPoolJson(),
                    $this->getMinimalLiquidityPoolJson()
                ]
            ]
        ];
    }

    // LiquidityPoolResponse Tests

    public function testLiquidityPoolResponseFromJson(): void
    {
        $json = $this->getCompleteLiquidityPoolJson();
        $response = LiquidityPoolResponse::fromJson($json);

        $this->assertEquals('dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7', $response->getPoolId());
        $this->assertEquals('113725249324879873', $response->getPagingToken());
        $this->assertEquals(30, $response->getFee());
        $this->assertEquals('constant_product', $response->getType());
        $this->assertEquals('500', $response->getTotalTrustlines());
        $this->assertEquals('5000.0000000', $response->getTotalShares());
        $this->assertEquals(1234567, $response->getLastModifiedLedger());
        $this->assertEquals('2021-11-18T03:47:47Z', $response->getLastModifiedTime());
    }

    public function testLiquidityPoolResponseReserves(): void
    {
        $json = $this->getCompleteLiquidityPoolJson();
        $response = LiquidityPoolResponse::fromJson($json);
        $reserves = $response->getReserves();

        $this->assertInstanceOf(ReservesResponse::class, $reserves);
        $this->assertEquals(2, $reserves->count());

        $reservesArray = $reserves->toArray();
        $this->assertCount(2, $reservesArray);

        $reserve1 = $reservesArray[0];
        $this->assertInstanceOf(ReserveResponse::class, $reserve1);
        $this->assertEquals('1000.0000000', $reserve1->getAmount());
        $this->assertNotNull($reserve1->getAsset());
        $this->assertEquals('EURT', $reserve1->getAsset()->getCode());
        $this->assertEquals('GAP5LETOV6YIE62YAM56STDANPRDO7ZFDBGSNHJQIYGGKSMOZAHOOS2S', $reserve1->getAsset()->getIssuer());

        $reserve2 = $reservesArray[1];
        $this->assertInstanceOf(ReserveResponse::class, $reserve2);
        $this->assertEquals('2000.0000000', $reserve2->getAmount());
        $this->assertNotNull($reserve2->getAsset());
        $this->assertEquals('PHP', $reserve2->getAsset()->getCode());
        $this->assertEquals('GAP5LETOV6YIE62YAM56STDANPRDO7ZFDBGSNHJQIYGGKSMOZAHOOS2S', $reserve2->getAsset()->getIssuer());
    }

    public function testLiquidityPoolResponseLinks(): void
    {
        $json = $this->getCompleteLiquidityPoolJson();
        $response = LiquidityPoolResponse::fromJson($json);
        $links = $response->getLinks();

        $this->assertInstanceOf(LiquidityPoolLinksResponse::class, $links);
        $this->assertEquals('https://horizon.stellar.org/liquidity_pools/dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7', $links->getSelf()->getHref());
        $this->assertStringContainsString('/operations', $links->getOperations()->getHref());
        $this->assertTrue($links->getOperations()->isTemplated());
        $this->assertStringContainsString('/transactions', $links->getTransactions()->getHref());
        $this->assertTrue($links->getTransactions()->isTemplated());
    }

    public function testLiquidityPoolResponseMinimalData(): void
    {
        $json = $this->getMinimalLiquidityPoolJson();
        $response = LiquidityPoolResponse::fromJson($json);

        $this->assertEquals('a468d41d8e9b8f3c9c3c8f8e2d4b1a9e7c6f5d4e3c2b1a0f9e8d7c6b5a4e3d2c', $response->getPoolId());
        $this->assertEquals('123456789', $response->getPagingToken());
        $this->assertEquals(30, $response->getFee());
        $this->assertEquals('constant_product', $response->getType());
        $this->assertEquals('100', $response->getTotalTrustlines());
        $this->assertEquals('1000.0000000', $response->getTotalShares());
        $this->assertEquals(100000, $response->getLastModifiedLedger());
        $this->assertEquals('2021-01-01T00:00:00Z', $response->getLastModifiedTime());

        $reserves = $response->getReserves();
        $this->assertEquals(0, $reserves->count());
    }

    public function testLiquidityPoolResponseConstantProductType(): void
    {
        $json = $this->getCompleteLiquidityPoolJson();
        $response = LiquidityPoolResponse::fromJson($json);

        $this->assertEquals('constant_product', $response->getType());
    }

    public function testLiquidityPoolResponseFeeBasisPoints(): void
    {
        $json = $this->getCompleteLiquidityPoolJson();
        $json['fee_bp'] = 50;
        $response = LiquidityPoolResponse::fromJson($json);

        $this->assertEquals(50, $response->getFee());
    }

    // ReserveResponse Tests

    public function testReserveResponseCreditAsset(): void
    {
        $json = [
            'amount' => '1500.5000000',
            'asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
        ];

        $reserve = ReserveResponse::fromJson($json);

        $this->assertEquals('1500.5000000', $reserve->getAmount());
        $this->assertNotNull($reserve->getAsset());
        $this->assertEquals('USD', $reserve->getAsset()->getCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $reserve->getAsset()->getIssuer());
    }

    public function testReserveResponseNativeAsset(): void
    {
        $json = [
            'amount' => '1000.0000000',
            'asset' => 'native'
        ];

        $reserve = ReserveResponse::fromJson($json);

        $this->assertEquals('1000.0000000', $reserve->getAmount());
        $this->assertNotNull($reserve->getAsset());
        $this->assertEquals('native', $reserve->getAsset()->getType());
    }

    public function testReserveResponseAlphanum12Asset(): void
    {
        $json = [
            'amount' => '2500.0000000',
            'asset' => 'LONGASSET:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
        ];

        $reserve = ReserveResponse::fromJson($json);

        $this->assertEquals('2500.0000000', $reserve->getAmount());
        $this->assertNotNull($reserve->getAsset());
        $this->assertEquals('LONGASSET', $reserve->getAsset()->getCode());
        $this->assertEquals('GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX', $reserve->getAsset()->getIssuer());
    }

    // ReservesResponse Tests

    public function testReservesResponseIteration(): void
    {
        $reserve1Json = [
            'amount' => '1000.0000000',
            'asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
        ];
        $reserve2Json = [
            'amount' => '2000.0000000',
            'asset' => 'EUR:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
        ];

        $reserve1 = ReserveResponse::fromJson($reserve1Json);
        $reserve2 = ReserveResponse::fromJson($reserve2Json);

        $reserves = new ReservesResponse($reserve1, $reserve2);

        $this->assertEquals(2, $reserves->count());

        $count = 0;
        foreach ($reserves as $reserve) {
            $this->assertInstanceOf(ReserveResponse::class, $reserve);
            $count++;
        }
        $this->assertEquals(2, $count);
    }

    public function testReservesResponseAdd(): void
    {
        $reserves = new ReservesResponse();
        $this->assertEquals(0, $reserves->count());

        $reserveJson = [
            'amount' => '1000.0000000',
            'asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
        ];
        $reserve = ReserveResponse::fromJson($reserveJson);
        $reserves->add($reserve);

        $this->assertEquals(1, $reserves->count());
    }

    public function testReservesResponseToArray(): void
    {
        $reserve1Json = [
            'amount' => '1000.0000000',
            'asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
        ];
        $reserve2Json = [
            'amount' => '2000.0000000',
            'asset' => 'EUR:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
        ];

        $reserve1 = ReserveResponse::fromJson($reserve1Json);
        $reserve2 = ReserveResponse::fromJson($reserve2Json);

        $reserves = new ReservesResponse($reserve1, $reserve2);
        $array = $reserves->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertInstanceOf(ReserveResponse::class, $array[0]);
        $this->assertInstanceOf(ReserveResponse::class, $array[1]);
    }

    // LiquidityPoolLinksResponse Tests

    public function testLiquidityPoolLinksResponseFromJson(): void
    {
        $json = [
            'self' => [
                'href' => 'https://horizon.stellar.org/liquidity_pools/dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7'
            ],
            'operations' => [
                'href' => 'https://horizon.stellar.org/liquidity_pools/dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7/operations{?cursor,limit,order}',
                'templated' => true
            ],
            'transactions' => [
                'href' => 'https://horizon.stellar.org/liquidity_pools/dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7/transactions{?cursor,limit,order}',
                'templated' => true
            ]
        ];

        $links = LiquidityPoolLinksResponse::fromJson($json);

        $this->assertEquals('https://horizon.stellar.org/liquidity_pools/dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7', $links->getSelf()->getHref());
        $this->assertStringContainsString('/operations', $links->getOperations()->getHref());
        $this->assertTrue($links->getOperations()->isTemplated());
        $this->assertStringContainsString('/transactions', $links->getTransactions()->getHref());
        $this->assertTrue($links->getTransactions()->isTemplated());
    }

    public function testLiquidityPoolLinksResponseMinimal(): void
    {
        $json = [
            'self' => [
                'href' => 'https://horizon.stellar.org/liquidity_pools/test'
            ],
            'operations' => [
                'href' => 'https://horizon.stellar.org/liquidity_pools/test/operations'
            ],
            'transactions' => [
                'href' => 'https://horizon.stellar.org/liquidity_pools/test/transactions'
            ]
        ];

        $links = LiquidityPoolLinksResponse::fromJson($json);

        $this->assertEquals('https://horizon.stellar.org/liquidity_pools/test', $links->getSelf()->getHref());
        $this->assertEquals('https://horizon.stellar.org/liquidity_pools/test/operations', $links->getOperations()->getHref());
        $this->assertEquals('https://horizon.stellar.org/liquidity_pools/test/transactions', $links->getTransactions()->getHref());
    }

    // LiquidityPoolPriceResponse Tests

    public function testLiquidityPoolPriceResponseFromJson(): void
    {
        $json = [
            'n' => 100,
            'd' => 50
        ];

        $price = LiquidityPoolPriceResponse::fromJson($json);

        $this->assertEquals(100, $price->getN());
        $this->assertEquals(50, $price->getD());
    }

    public function testLiquidityPoolPriceResponseOne(): void
    {
        $json = [
            'n' => 1,
            'd' => 1
        ];

        $price = LiquidityPoolPriceResponse::fromJson($json);

        $this->assertEquals(1, $price->getN());
        $this->assertEquals(1, $price->getD());
    }

    public function testLiquidityPoolPriceResponseLargeNumbers(): void
    {
        $json = [
            'n' => 1000000,
            'd' => 500000
        ];

        $price = LiquidityPoolPriceResponse::fromJson($json);

        $this->assertEquals(1000000, $price->getN());
        $this->assertEquals(500000, $price->getD());
    }

    // LiquidityPoolsResponse Tests

    public function testLiquidityPoolsResponseIteration(): void
    {
        $pool1 = LiquidityPoolResponse::fromJson($this->getCompleteLiquidityPoolJson());
        $pool2 = LiquidityPoolResponse::fromJson($this->getMinimalLiquidityPoolJson());

        $pools = new LiquidityPoolsResponse($pool1, $pool2);

        $this->assertEquals(2, $pools->count());

        $count = 0;
        foreach ($pools as $pool) {
            $this->assertInstanceOf(LiquidityPoolResponse::class, $pool);
            $count++;
        }
        $this->assertEquals(2, $count);
    }

    public function testLiquidityPoolsResponseAdd(): void
    {
        $pools = new LiquidityPoolsResponse();
        $this->assertEquals(0, $pools->count());

        $pool = LiquidityPoolResponse::fromJson($this->getCompleteLiquidityPoolJson());
        $pools->add($pool);

        $this->assertEquals(1, $pools->count());
    }

    public function testLiquidityPoolsResponseToArray(): void
    {
        $pool1 = LiquidityPoolResponse::fromJson($this->getCompleteLiquidityPoolJson());
        $pool2 = LiquidityPoolResponse::fromJson($this->getMinimalLiquidityPoolJson());

        $pools = new LiquidityPoolsResponse($pool1, $pool2);
        $array = $pools->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertInstanceOf(LiquidityPoolResponse::class, $array[0]);
        $this->assertInstanceOf(LiquidityPoolResponse::class, $array[1]);
    }

    public function testLiquidityPoolsResponseEmpty(): void
    {
        $pools = new LiquidityPoolsResponse();

        $this->assertEquals(0, $pools->count());
        $this->assertEmpty($pools->toArray());
    }

    // LiquidityPoolsPageResponse Tests

    public function testLiquidityPoolsPageResponseFromJson(): void
    {
        $json = $this->getLiquidityPoolsPageJson();
        $response = LiquidityPoolsPageResponse::fromJson($json);

        $this->assertInstanceOf(LiquidityPoolsPageResponse::class, $response);

        $pools = $response->getLiquidityPools();
        $this->assertInstanceOf(LiquidityPoolsResponse::class, $pools);
        $this->assertEquals(2, $pools->count());

        $poolsArray = $pools->toArray();
        $this->assertEquals('dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7', $poolsArray[0]->getPoolId());
        $this->assertEquals('a468d41d8e9b8f3c9c3c8f8e2d4b1a9e7c6f5d4e3c2b1a0f9e8d7c6b5a4e3d2c', $poolsArray[1]->getPoolId());
    }

    public function testLiquidityPoolsPageResponsePaginationLinks(): void
    {
        $json = $this->getLiquidityPoolsPageJson();
        $response = LiquidityPoolsPageResponse::fromJson($json);

        $this->assertTrue($response->hasNextPage());
        $this->assertTrue($response->hasPrevPage());

        $links = $response->getLinks();
        $this->assertNotNull($links->getNext());
        $this->assertNotNull($links->getPrev());
        $this->assertStringContainsString('cursor=113725249324879873', $links->getNext()->getHref());
        $this->assertStringContainsString('order=asc', $links->getNext()->getHref());
        $this->assertStringContainsString('order=desc', $links->getPrev()->getHref());
    }

    public function testLiquidityPoolsPageResponseEmptyRecords(): void
    {
        $json = [
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/liquidity_pools?cursor=&limit=10&order=asc'
                ],
                'next' => [
                    'href' => 'https://horizon.stellar.org/liquidity_pools?cursor=&limit=10&order=asc'
                ],
                'prev' => [
                    'href' => 'https://horizon.stellar.org/liquidity_pools?cursor=&limit=10&order=desc'
                ]
            ],
            '_embedded' => [
                'records' => []
            ]
        ];

        $response = LiquidityPoolsPageResponse::fromJson($json);
        $pools = $response->getLiquidityPools();

        $this->assertEquals(0, $pools->count());
        $this->assertEmpty($pools->toArray());
    }

    public function testLiquidityPoolsPageResponseSingleRecord(): void
    {
        $json = [
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/liquidity_pools?cursor=&limit=1&order=asc'
                ],
                'next' => [
                    'href' => 'https://horizon.stellar.org/liquidity_pools?cursor=113725249324879873&limit=1&order=asc'
                ],
                'prev' => [
                    'href' => 'https://horizon.stellar.org/liquidity_pools?cursor=113725249324879873&limit=1&order=desc'
                ]
            ],
            '_embedded' => [
                'records' => [
                    $this->getCompleteLiquidityPoolJson()
                ]
            ]
        ];

        $response = LiquidityPoolsPageResponse::fromJson($json);
        $pools = $response->getLiquidityPools();

        $this->assertEquals(1, $pools->count());
        $poolsArray = $pools->toArray();
        $this->assertEquals('dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7', $poolsArray[0]->getPoolId());
    }

    // Integration Tests - Full Pool Data with Reserves

    public function testLiquidityPoolResponseWithMultipleReserves(): void
    {
        $json = [
            'id' => 'test-pool-id',
            'paging_token' => '999999999',
            'fee_bp' => 30,
            'type' => 'constant_product',
            'total_trustlines' => '1000',
            'total_shares' => '10000.0000000',
            'reserves' => [
                [
                    'amount' => '5000.0000000',
                    'asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
                ],
                [
                    'amount' => '5000.0000000',
                    'asset' => 'EUR:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
                ]
            ],
            'last_modified_ledger' => 999999,
            'last_modified_time' => '2021-12-31T23:59:59Z',
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/liquidity_pools/test-pool-id'],
                'operations' => ['href' => 'https://horizon.stellar.org/liquidity_pools/test-pool-id/operations'],
                'transactions' => ['href' => 'https://horizon.stellar.org/liquidity_pools/test-pool-id/transactions']
            ]
        ];

        $response = LiquidityPoolResponse::fromJson($json);

        $this->assertEquals('test-pool-id', $response->getPoolId());
        $this->assertEquals('constant_product', $response->getType());
        $this->assertEquals(30, $response->getFee());

        $reserves = $response->getReserves();
        $this->assertEquals(2, $reserves->count());

        $reservesArray = $reserves->toArray();
        $this->assertEquals('5000.0000000', $reservesArray[0]->getAmount());
        $this->assertEquals('USD', $reservesArray[0]->getAsset()->getCode());
        $this->assertEquals('5000.0000000', $reservesArray[1]->getAmount());
        $this->assertEquals('EUR', $reservesArray[1]->getAsset()->getCode());
    }

    public function testLiquidityPoolResponseWithNativeReserve(): void
    {
        $json = [
            'id' => 'native-pool-id',
            'paging_token' => '888888888',
            'fee_bp' => 30,
            'type' => 'constant_product',
            'total_trustlines' => '250',
            'total_shares' => '2500.0000000',
            'reserves' => [
                [
                    'amount' => '1250.0000000',
                    'asset' => 'native'
                ],
                [
                    'amount' => '1250.0000000',
                    'asset' => 'USD:GDUKMGUGDZQK6YHYA5Z6AY2G4XDSZPSZ3SW5UN3ARVMO6QSRDWP5YLEX'
                ]
            ],
            'last_modified_ledger' => 888888,
            'last_modified_time' => '2021-06-15T12:30:00Z',
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/liquidity_pools/native-pool-id'],
                'operations' => ['href' => 'https://horizon.stellar.org/liquidity_pools/native-pool-id/operations'],
                'transactions' => ['href' => 'https://horizon.stellar.org/liquidity_pools/native-pool-id/transactions']
            ]
        ];

        $response = LiquidityPoolResponse::fromJson($json);

        $reserves = $response->getReserves();
        $reservesArray = $reserves->toArray();

        $this->assertEquals('native', $reservesArray[0]->getAsset()->getType());
        $this->assertEquals('1250.0000000', $reservesArray[0]->getAmount());
        $this->assertEquals('USD', $reservesArray[1]->getAsset()->getCode());
    }

    public function testLiquidityPoolResponseZeroTrustlines(): void
    {
        $json = $this->getMinimalLiquidityPoolJson();
        $json['total_trustlines'] = '0';
        $json['total_shares'] = '0.0000000';

        $response = LiquidityPoolResponse::fromJson($json);

        $this->assertEquals('0', $response->getTotalTrustlines());
        $this->assertEquals('0.0000000', $response->getTotalShares());
    }

    public function testLiquidityPoolResponseLargeTrustlinesAndShares(): void
    {
        $json = $this->getCompleteLiquidityPoolJson();
        $json['total_trustlines'] = '999999999';
        $json['total_shares'] = '999999999.0000000';

        $response = LiquidityPoolResponse::fromJson($json);

        $this->assertEquals('999999999', $response->getTotalTrustlines());
        $this->assertEquals('999999999.0000000', $response->getTotalShares());
    }
}
