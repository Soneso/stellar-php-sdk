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
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Requests\ClaimableBalancesRequestBuilder;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimableBalanceResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimableBalancesPageResponse;

class ClaimableBalancesRequestBuilderTest extends TestCase
{
    private string $claimableBalanceResponse = <<<JSON
{
  "_links": {
    "self": {
      "href": "https://horizon-testnet.stellar.org/claimable_balances/00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb8ecfb01e"
    },
    "transactions": {
      "href": "https://horizon-testnet.stellar.org/claimable_balances/00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb8ecfb01e/transactions{?cursor,limit,order}",
      "templated": true
    },
    "operations": {
      "href": "https://horizon-testnet.stellar.org/claimable_balances/00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb8ecfb01e/operations{?cursor,limit,order}",
      "templated": true
    }
  },
  "id": "00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb8ecfb01e",
  "asset": "native",
  "amount": "100.0000000",
  "sponsor": "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5",
  "last_modified_ledger": 123456,
  "last_modified_time": "2024-01-15T10:30:00Z",
  "claimants": [
    {
      "destination": "GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54",
      "predicate": {
        "unconditional": true
      }
    }
  ],
  "flags": {
    "clawback_enabled": false
  },
  "paging_token": "123456-00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb8ecfb01e"
}
JSON;

    private string $claimableBalancesPageResponse = <<<JSON
{
  "_links": {
    "self": {
      "href": "https://horizon-testnet.stellar.org/claimable_balances?cursor=\u0026limit=2\u0026order=asc"
    },
    "next": {
      "href": "https://horizon-testnet.stellar.org/claimable_balances?cursor=2-00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072\u0026limit=2\u0026order=asc"
    },
    "prev": {
      "href": "https://horizon-testnet.stellar.org/claimable_balances?cursor=1-00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb8ecfb01e\u0026limit=2\u0026order=desc"
    }
  },
  "_embedded": {
    "records": [
      {
        "_links": {
          "self": {
            "href": "https://horizon-testnet.stellar.org/claimable_balances/00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb8ecfb01e"
          }
        },
        "id": "00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb8ecfb01e",
        "asset": "native",
        "amount": "100.0000000",
        "sponsor": "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5",
        "last_modified_ledger": 123456,
        "last_modified_time": "2024-01-15T10:30:00Z",
        "claimants": [
          {
            "destination": "GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54",
            "predicate": {
              "unconditional": true
            }
          }
        ],
        "flags": {
          "clawback_enabled": false
        },
        "paging_token": "123456-00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb8ecfb01e"
      },
      {
        "_links": {
          "self": {
            "href": "https://horizon-testnet.stellar.org/claimable_balances/00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072"
          }
        },
        "id": "00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072",
        "asset": "USD:GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5",
        "amount": "50.0000000",
        "sponsor": "GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U",
        "last_modified_ledger": 123457,
        "last_modified_time": "2024-01-15T10:31:00Z",
        "claimants": [
          {
            "destination": "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5",
            "predicate": {
              "abs_before": "2025-01-01T00:00:00Z"
            }
          }
        ],
        "flags": {
          "clawback_enabled": true
        },
        "paging_token": "123457-00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072"
      }
    ]
  }
}
JSON;

    public function testGetSpecificClaimableBalance(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->claimableBalanceResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $this->assertEquals("GET", $request->getMethod());
            $this->assertStringContainsString("claimable_balances/00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb8ecfb01e",
                $request->getUri()->getPath());
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new ClaimableBalancesRequestBuilder($httpClient);
        $response = $requestBuilder->claimableBalance("00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb8ecfb01e");

        $this->assertInstanceOf(ClaimableBalanceResponse::class, $response);
        $this->assertEquals("00000000846c047755e4a46912336f56096b48ece78ddb5fbf6d90f0eb8ecfb01e", $response->getBalanceId());
        $this->assertInstanceOf(\Soneso\StellarSDK\AssetTypeNative::class, $response->getAsset());
        $this->assertEquals("100.0000000", $response->getAmount());
        $this->assertEquals("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $response->getSponsor());
    }

    public function testGetClaimableBalancesPage(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->claimableBalancesPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $this->assertEquals("GET", $request->getMethod());
            $this->assertStringContainsString("claimable_balances", $request->getUri()->getPath());
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new ClaimableBalancesRequestBuilder($httpClient);
        $response = $requestBuilder->execute();

        $this->assertInstanceOf(ClaimableBalancesPageResponse::class, $response);
        $balances = $response->getClaimableBalances();
        $this->assertCount(2, $balances->toArray());

        $firstBalance = $balances->toArray()[0];
        $this->assertInstanceOf(\Soneso\StellarSDK\AssetTypeNative::class, $firstBalance->getAsset());
        $this->assertEquals("100.0000000", $firstBalance->getAmount());

        $secondBalance = $balances->toArray()[1];
        $this->assertInstanceOf(\Soneso\StellarSDK\AssetTypeCreditAlphanum::class, $secondBalance->getAsset());
        $this->assertEquals("USD", $secondBalance->getAsset()->getCode());
        $this->assertEquals("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $secondBalance->getAsset()->getIssuer());
    }

    public function testGetClaimableBalancesFilteredBySponsor(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->claimableBalancesPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('sponsor', $params);
            $this->assertEquals("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['sponsor']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new ClaimableBalancesRequestBuilder($httpClient);
        $response = $requestBuilder->forSponsor("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5")->execute();

        $this->assertInstanceOf(ClaimableBalancesPageResponse::class, $response);
    }

    public function testGetClaimableBalancesFilteredByAsset(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->claimableBalancesPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('asset', $params);
            $this->assertEquals("USD:GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['asset']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new ClaimableBalancesRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5");
        $response = $requestBuilder->forAsset($asset)->execute();

        $this->assertInstanceOf(ClaimableBalancesPageResponse::class, $response);
    }

    public function testGetClaimableBalancesFilteredByClaimant(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->claimableBalancesPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('claimant', $params);
            $this->assertEquals("GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54", $params['claimant']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new ClaimableBalancesRequestBuilder($httpClient);
        $response = $requestBuilder->forClaimant("GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54")->execute();

        $this->assertInstanceOf(ClaimableBalancesPageResponse::class, $response);
    }

    public function testGetClaimableBalancesWithNativeAsset(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->claimableBalancesPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('asset', $params);
            $this->assertEquals("native", $params['asset']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new ClaimableBalancesRequestBuilder($httpClient);
        $asset = Asset::native();
        $response = $requestBuilder->forAsset($asset)->execute();

        $this->assertInstanceOf(ClaimableBalancesPageResponse::class, $response);
    }

    public function testGetClaimableBalancesWithPagination(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->claimableBalancesPageResponse)
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
        $requestBuilder = new ClaimableBalancesRequestBuilder($httpClient);
        $response = $requestBuilder->limit(10)->order("desc")->execute();

        $this->assertInstanceOf(ClaimableBalancesPageResponse::class, $response);
    }

    public function testGetClaimableBalancesWithCursor(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->claimableBalancesPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('cursor', $params);
            $this->assertEquals("123456", $params['cursor']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new ClaimableBalancesRequestBuilder($httpClient);
        $response = $requestBuilder->cursor("123456")->execute();

        $this->assertInstanceOf(ClaimableBalancesPageResponse::class, $response);
    }

    public function testGetClaimableBalancesWithMultipleFilters(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->claimableBalancesPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['sponsor']);
            $this->assertEquals("GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54", $params['claimant']);
            $this->assertEquals("USD:GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['asset']);
            $this->assertEquals("20", $params['limit']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new ClaimableBalancesRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5");

        $response = $requestBuilder
            ->forSponsor("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5")
            ->forClaimant("GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54")
            ->forAsset($asset)
            ->limit(20)
            ->execute();

        $this->assertInstanceOf(ClaimableBalancesPageResponse::class, $response);
    }
}
