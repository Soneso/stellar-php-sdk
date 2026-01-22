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
use Soneso\StellarSDK\Requests\AssetsRequestBuilder;
use Soneso\StellarSDK\Responses\Asset\AssetsPageResponse;

class AssetsRequestBuilderTest extends TestCase
{
    private string $assetsPageResponse = <<<JSON
{
  "_links": {
    "self": {
      "href": "https://horizon-testnet.stellar.org/assets?cursor=\u0026limit=2\u0026order=asc"
    },
    "next": {
      "href": "https://horizon-testnet.stellar.org/assets?cursor=USD_GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5_credit_alphanum4\u0026limit=2\u0026order=asc"
    },
    "prev": {
      "href": "https://horizon-testnet.stellar.org/assets?cursor=BTC_GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U_credit_alphanum4\u0026limit=2\u0026order=desc"
    }
  },
  "_embedded": {
    "records": [
      {
        "_links": {
          "toml": {
            "href": "https://www.stellar.org/.well-known/stellar.toml"
          }
        },
        "asset_type": "credit_alphanum4",
        "asset_code": "USD",
        "asset_issuer": "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5",
        "paging_token": "USD_GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5_credit_alphanum4",
        "accounts": {
          "authorized": 1234567,
          "authorized_to_maintain_liabilities": 123,
          "unauthorized": 45
        },
        "num_claimable_balances": 10,
        "num_liquidity_pools": 5,
        "num_contracts": 2,
        "num_archived_contracts": 1,
        "contract_id": "CAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABSC4",
        "balances": {
          "authorized": "50000000.0000000",
          "authorized_to_maintain_liabilities": "10000.0000000",
          "unauthorized": "5000.0000000"
        },
        "claimable_balances_amount": "1000.0000000",
        "liquidity_pools_amount": "5000.0000000",
        "contracts_amount": "10000.0000000",
        "archived_contracts_amount": "2000.0000000",
        "amount": "50016000.0000000",
        "num_accounts": 1234735,
        "flags": {
          "auth_required": false,
          "auth_revocable": false,
          "auth_immutable": false,
          "auth_clawback_enabled": false
        }
      },
      {
        "_links": {
          "toml": {
            "href": "https://www.bitcoin.org/.well-known/stellar.toml"
          }
        },
        "asset_type": "credit_alphanum4",
        "asset_code": "BTC",
        "asset_issuer": "GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U",
        "paging_token": "BTC_GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U_credit_alphanum4",
        "accounts": {
          "authorized": 987654,
          "authorized_to_maintain_liabilities": 50,
          "unauthorized": 20
        },
        "num_claimable_balances": 5,
        "num_liquidity_pools": 2,
        "num_contracts": 1,
        "num_archived_contracts": 0,
        "balances": {
          "authorized": "25000000.0000000",
          "authorized_to_maintain_liabilities": "5000.0000000",
          "unauthorized": "2500.0000000"
        },
        "claimable_balances_amount": "500.0000000",
        "liquidity_pools_amount": "2000.0000000",
        "contracts_amount": "5000.0000000",
        "amount": "25010000.0000000",
        "num_accounts": 987724,
        "flags": {
          "auth_required": true,
          "auth_revocable": true,
          "auth_immutable": false,
          "auth_clawback_enabled": false
        }
      }
    ]
  }
}
JSON;

    public function testGetAllAssets(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->assetsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $this->assertEquals("GET", $request->getMethod());
            $this->assertStringContainsString("assets", $request->getUri()->getPath());
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new AssetsRequestBuilder($httpClient);
        $response = $requestBuilder->execute();

        $this->assertInstanceOf(AssetsPageResponse::class, $response);
        $assets = $response->getAssets();
        $this->assertCount(2, $assets->toArray());

        $firstAsset = $assets->toArray()[0];
        $this->assertEquals("USD", $firstAsset->getAssetCode());
        $this->assertEquals("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $firstAsset->getAssetIssuer());
        $this->assertEquals(1234567, $firstAsset->getAccounts()->getAuthorized());
        $this->assertFalse($firstAsset->getFlags()->isAuthRequired());

        $secondAsset = $assets->toArray()[1];
        $this->assertEquals("BTC", $secondAsset->getAssetCode());
        $this->assertTrue($secondAsset->getFlags()->isAuthRequired());
    }

    public function testGetAssetsFilteredByCode(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->assetsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('asset_code', $params);
            $this->assertEquals("USD", $params['asset_code']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new AssetsRequestBuilder($httpClient);
        $response = $requestBuilder->forAssetCode("USD")->execute();

        $this->assertInstanceOf(AssetsPageResponse::class, $response);
    }

    public function testGetAssetsFilteredByIssuer(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->assetsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('asset_issuer', $params);
            $this->assertEquals("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['asset_issuer']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new AssetsRequestBuilder($httpClient);
        $response = $requestBuilder->forAssetIssuer("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5")->execute();

        $this->assertInstanceOf(AssetsPageResponse::class, $response);
    }

    public function testGetSpecificAssetByCodeAndIssuer(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->assetsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('asset_code', $params);
            $this->assertArrayHasKey('asset_issuer', $params);
            $this->assertEquals("USD", $params['asset_code']);
            $this->assertEquals("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['asset_issuer']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new AssetsRequestBuilder($httpClient);
        $response = $requestBuilder
            ->forAssetCode("USD")
            ->forAssetIssuer("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5")
            ->execute();

        $this->assertInstanceOf(AssetsPageResponse::class, $response);
    }

    public function testGetAssetsWithPagination(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->assetsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("50", $params['limit']);
            $this->assertEquals("asc", $params['order']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new AssetsRequestBuilder($httpClient);
        $response = $requestBuilder->limit(50)->order("asc")->execute();

        $this->assertInstanceOf(AssetsPageResponse::class, $response);
    }

    public function testGetAssetsWithCursor(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->assetsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('cursor', $params);
            $this->assertEquals("USD_GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5_credit_alphanum4",
                $params['cursor']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new AssetsRequestBuilder($httpClient);
        $response = $requestBuilder
            ->cursor("USD_GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5_credit_alphanum4")
            ->execute();

        $this->assertInstanceOf(AssetsPageResponse::class, $response);
    }

    public function testGetAssetsDescendingOrder(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->assetsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("desc", $params['order']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new AssetsRequestBuilder($httpClient);
        $response = $requestBuilder->order("desc")->execute();

        $this->assertInstanceOf(AssetsPageResponse::class, $response);
    }

    public function testGetAssetsWithAllParameters(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->assetsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("USD", $params['asset_code']);
            $this->assertEquals("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['asset_issuer']);
            $this->assertEquals("10", $params['limit']);
            $this->assertEquals("desc", $params['order']);
            $this->assertArrayHasKey('cursor', $params);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new AssetsRequestBuilder($httpClient);
        $response = $requestBuilder
            ->forAssetCode("USD")
            ->forAssetIssuer("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5")
            ->cursor("test_cursor")
            ->limit(10)
            ->order("desc")
            ->execute();

        $this->assertInstanceOf(AssetsPageResponse::class, $response);
    }
}
