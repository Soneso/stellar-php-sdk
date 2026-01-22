<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soneso\StellarSDK\Requests\LiquidityPoolsRequestBuilder;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolsPageResponse;

class LiquidityPoolsRequestBuilderTest extends TestCase
{
    private string $liquidityPoolResponse = <<<JSON
{
  "_links": {
    "self": {
      "href": "https://horizon-testnet.stellar.org/liquidity_pools/67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9"
    },
    "transactions": {
      "href": "https://horizon-testnet.stellar.org/liquidity_pools/67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9/transactions{?cursor,limit,order}",
      "templated": true
    },
    "operations": {
      "href": "https://horizon-testnet.stellar.org/liquidity_pools/67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9/operations{?cursor,limit,order}",
      "templated": true
    }
  },
  "id": "67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9",
  "paging_token": "67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9",
  "fee_bp": 30,
  "type": "constant_product",
  "total_trustlines": "150",
  "total_shares": "5000.0000000",
  "reserves": [
    {
      "amount": "1000.0000000",
      "asset": "native"
    },
    {
      "amount": "2000.0000000",
      "asset": "USD:GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5"
    }
  ],
  "last_modified_ledger": 123456,
  "last_modified_time": "2024-01-15T10:30:00Z"
}
JSON;

    private string $liquidityPoolsPageResponse = <<<JSON
{
  "_links": {
    "self": {
      "href": "https://horizon-testnet.stellar.org/liquidity_pools?cursor=\u0026limit=2\u0026order=asc"
    },
    "next": {
      "href": "https://horizon-testnet.stellar.org/liquidity_pools?cursor=67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9\u0026limit=2\u0026order=asc"
    },
    "prev": {
      "href": "https://horizon-testnet.stellar.org/liquidity_pools?cursor=ae2f3bbcb59746f8d929f06beaa95d426b4b6bf00dc26b4e4e17c34796d6eae2\u0026limit=2\u0026order=desc"
    }
  },
  "_embedded": {
    "records": [
      {
        "_links": {
          "self": {
            "href": "https://horizon-testnet.stellar.org/liquidity_pools/ae2f3bbcb59746f8d929f06beaa95d426b4b6bf00dc26b4e4e17c34796d6eae2"
          }
        },
        "id": "ae2f3bbcb59746f8d929f06beaa95d426b4b6bf00dc26b4e4e17c34796d6eae2",
        "paging_token": "ae2f3bbcb59746f8d929f06beaa95d426b4b6bf00dc26b4e4e17c34796d6eae2",
        "fee_bp": 30,
        "type": "constant_product",
        "total_trustlines": "150",
        "total_shares": "5000.0000000",
        "reserves": [
          {
            "amount": "1000.0000000",
            "asset": "native"
          },
          {
            "amount": "2000.0000000",
            "asset": "USD:GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5"
          }
        ],
        "last_modified_ledger": 123456,
        "last_modified_time": "2024-01-15T10:30:00Z"
      },
      {
        "_links": {
          "self": {
            "href": "https://horizon-testnet.stellar.org/liquidity_pools/67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9"
          }
        },
        "id": "67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9",
        "paging_token": "67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9",
        "fee_bp": 30,
        "type": "constant_product",
        "total_trustlines": "200",
        "total_shares": "10000.0000000",
        "reserves": [
          {
            "amount": "3000.0000000",
            "asset": "BTC:GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U"
          },
          {
            "amount": "4000.0000000",
            "asset": "ETH:GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5"
          }
        ],
        "last_modified_ledger": 123457,
        "last_modified_time": "2024-01-15T10:31:00Z"
      }
    ]
  }
}
JSON;

    public function testGetSpecificLiquidityPool(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->liquidityPoolResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $this->assertEquals("GET", $request->getMethod());
            $this->assertStringContainsString("liquidity_pools/67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9",
                $request->getUri()->getPath());
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new LiquidityPoolsRequestBuilder($httpClient);
        $response = $requestBuilder->forPoolId("67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9");

        $this->assertInstanceOf(LiquidityPoolResponse::class, $response);
        $this->assertEquals("67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9", $response->getPoolId());
        $this->assertEquals(30, $response->getFee());
        $this->assertEquals("constant_product", $response->getType());
        $this->assertEquals("150", $response->getTotalTrustlines());
        $this->assertEquals("5000.0000000", $response->getTotalShares());
    }

    public function testGetLiquidityPoolsPage(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->liquidityPoolsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $this->assertEquals("GET", $request->getMethod());
            $this->assertStringContainsString("liquidity_pools", $request->getUri()->getPath());
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new LiquidityPoolsRequestBuilder($httpClient);
        $response = $requestBuilder->execute();

        $this->assertInstanceOf(LiquidityPoolsPageResponse::class, $response);
        $pools = $response->getLiquidityPools();
        $this->assertCount(2, $pools->toArray());

        $firstPool = $pools->toArray()[0];
        $this->assertEquals("ae2f3bbcb59746f8d929f06beaa95d426b4b6bf00dc26b4e4e17c34796d6eae2", $firstPool->getPoolId());
        $this->assertEquals("150", $firstPool->getTotalTrustlines());

        $secondPool = $pools->toArray()[1];
        $this->assertEquals("67260c4c1807b262ff851b0a3fe141194936bb0215b2f77447f1df11998eabb9", $secondPool->getPoolId());
        $this->assertEquals("200", $secondPool->getTotalTrustlines());
    }

    public function testGetLiquidityPoolsFilteredByReserves(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->liquidityPoolsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('reserves', $params);
            $this->assertEquals("native,USD:GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['reserves']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new LiquidityPoolsRequestBuilder($httpClient);
        $response = $requestBuilder->forReserves("native", "USD:GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5")->execute();

        $this->assertInstanceOf(LiquidityPoolsPageResponse::class, $response);
    }

    public function testGetLiquidityPoolsFilteredByAccount(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->liquidityPoolsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('account', $params);
            $this->assertEquals("GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54", $params['account']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new LiquidityPoolsRequestBuilder($httpClient);
        $response = $requestBuilder->forAccount("GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54")->execute();

        $this->assertInstanceOf(LiquidityPoolsPageResponse::class, $response);
    }

    public function testGetLiquidityPoolsWithPagination(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->liquidityPoolsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("10", $params['limit']);
            $this->assertEquals("desc", $params['order']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new LiquidityPoolsRequestBuilder($httpClient);
        $response = $requestBuilder->limit(10)->order("desc")->execute();

        $this->assertInstanceOf(LiquidityPoolsPageResponse::class, $response);
    }

    public function testGetLiquidityPoolsWithCursor(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->liquidityPoolsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('cursor', $params);
            $this->assertEquals("ae2f3bbcb59746f8d929f06beaa95d426b4b6bf00dc26b4e4e17c34796d6eae2", $params['cursor']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new LiquidityPoolsRequestBuilder($httpClient);
        $response = $requestBuilder->cursor("ae2f3bbcb59746f8d929f06beaa95d426b4b6bf00dc26b4e4e17c34796d6eae2")->execute();

        $this->assertInstanceOf(LiquidityPoolsPageResponse::class, $response);
    }

    public function testGetLiquidityPoolsWithSingleReserve(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->liquidityPoolsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('reserves', $params);
            $this->assertEquals("native", $params['reserves']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new LiquidityPoolsRequestBuilder($httpClient);
        $response = $requestBuilder->forReserves("native")->execute();

        $this->assertInstanceOf(LiquidityPoolsPageResponse::class, $response);
    }

    public function testGetLiquidityPoolsWithMultipleReserves(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->liquidityPoolsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('reserves', $params);
            $this->assertEquals("BTC:GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U,ETH:GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5",
                $params['reserves']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new LiquidityPoolsRequestBuilder($httpClient);
        $response = $requestBuilder
            ->forReserves(
                "BTC:GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U",
                "ETH:GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5"
            )
            ->execute();

        $this->assertInstanceOf(LiquidityPoolsPageResponse::class, $response);
    }

    public function testGetLiquidityPoolsWithAllParameters(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->liquidityPoolsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("native,USD:GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['reserves']);
            $this->assertEquals("20", $params['limit']);
            $this->assertEquals("asc", $params['order']);
            $this->assertArrayHasKey('cursor', $params);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new LiquidityPoolsRequestBuilder($httpClient);
        $response = $requestBuilder
            ->forReserves("native", "USD:GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5")
            ->cursor("test_cursor")
            ->limit(20)
            ->order("asc")
            ->execute();

        $this->assertInstanceOf(LiquidityPoolsPageResponse::class, $response);
    }
}
