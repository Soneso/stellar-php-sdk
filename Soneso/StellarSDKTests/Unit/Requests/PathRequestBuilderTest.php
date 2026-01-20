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
use Soneso\StellarSDK\Requests\StrictReceivePathsRequestBuilder;
use Soneso\StellarSDK\Requests\StrictSendPathsRequestBuilder;
use Soneso\StellarSDK\Responses\PaymentPath\PathsPageResponse;

class PathRequestBuilderTest extends TestCase
{
    private string $pathsPageResponse = <<<JSON
{
  "_links": {
    "self": {
      "href": "https://horizon-testnet.stellar.org/paths/strict-receive"
    }
  },
  "_embedded": {
    "records": [
      {
        "source_asset_type": "credit_alphanum4",
        "source_asset_code": "BTC",
        "source_asset_issuer": "GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U",
        "source_amount": "0.0001234",
        "destination_asset_type": "credit_alphanum4",
        "destination_asset_code": "USD",
        "destination_asset_issuer": "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5",
        "destination_amount": "100.0000000",
        "path": [
          {
            "asset_type": "native"
          }
        ]
      },
      {
        "source_asset_type": "native",
        "source_amount": "52.1234567",
        "destination_asset_type": "credit_alphanum4",
        "destination_asset_code": "USD",
        "destination_asset_issuer": "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5",
        "destination_amount": "100.0000000",
        "path": []
      }
    ]
  }
}
JSON;

    public function testStrictReceivePathsWithDestinationAmount(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $this->assertEquals("GET", $request->getMethod());
            $this->assertStringContainsString("paths/strict-receive", $request->getUri()->getPath());
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("100", $params['destination_amount']);
            $this->assertEquals("credit_alphanum4", $params['destination_asset_type']);
            $this->assertEquals("USD", $params['destination_asset_code']);
            $this->assertEquals("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['destination_asset_issuer']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new StrictReceivePathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5");

        $response = $requestBuilder
            ->forDestinationAmount("100")
            ->forDestinationAsset($asset)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
        $paths = $response->getPaths();
        $this->assertCount(2, $paths->toArray());
    }

    public function testStrictReceivePathsWithDestinationAccount(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('destination_account', $params);
            $this->assertEquals("GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54", $params['destination_account']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new StrictReceivePathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5");

        $response = $requestBuilder
            ->forDestinationAccount("GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54")
            ->forDestinationAmount("100")
            ->forDestinationAsset($asset)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    public function testStrictReceivePathsWithSourceAccount(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('source_account', $params);
            $this->assertEquals("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['source_account']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new StrictReceivePathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5");

        $response = $requestBuilder
            ->forSourceAccount("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5")
            ->forDestinationAmount("100")
            ->forDestinationAsset($asset)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    public function testStrictReceivePathsWithSourceAssets(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('source_assets', $params);
            $this->assertEquals("native,USD:GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['source_assets']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new StrictReceivePathsRequestBuilder($httpClient);
        $destAsset = Asset::createNonNativeAsset("USD", "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5");
        $sourceAssets = [
            Asset::native(),
            Asset::createNonNativeAsset("USD", "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5")
        ];

        $response = $requestBuilder
            ->forSourceAssets($sourceAssets)
            ->forDestinationAmount("100")
            ->forDestinationAsset($destAsset)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    public function testStrictReceivePathsWithNativeAsset(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("native", $params['destination_asset_type']);
            $this->assertArrayNotHasKey('destination_asset_code', $params);
            $this->assertArrayNotHasKey('destination_asset_issuer', $params);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new StrictReceivePathsRequestBuilder($httpClient);

        $response = $requestBuilder
            ->forDestinationAmount("100")
            ->forDestinationAsset(Asset::native())
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    public function testStrictSendPathsWithSourceAmount(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $this->assertEquals("GET", $request->getMethod());
            $this->assertStringContainsString("paths/strict-send", $request->getUri()->getPath());
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("50", $params['source_amount']);
            $this->assertEquals("credit_alphanum4", $params['source_asset_type']);
            $this->assertEquals("BTC", $params['source_asset_code']);
            $this->assertEquals("GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U", $params['source_asset_issuer']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new StrictSendPathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("BTC", "GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U");

        $response = $requestBuilder
            ->forSourceAmount("50")
            ->forSourceAsset($asset)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    public function testStrictSendPathsWithSourceAccount(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('source_account', $params);
            $this->assertEquals("GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54", $params['source_account']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new StrictSendPathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("BTC", "GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U");

        $response = $requestBuilder
            ->forSourceAccount("GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54")
            ->forSourceAmount("50")
            ->forSourceAsset($asset)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    public function testStrictSendPathsWithDestinationAccount(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('destination_account', $params);
            $this->assertEquals("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['destination_account']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new StrictSendPathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("BTC", "GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U");

        $response = $requestBuilder
            ->forDestinationAccount("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5")
            ->forSourceAmount("50")
            ->forSourceAsset($asset)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    public function testStrictSendPathsWithDestinationAssets(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('destination_assets', $params);
            $this->assertEquals("native,USD:GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['destination_assets']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new StrictSendPathsRequestBuilder($httpClient);
        $sourceAsset = Asset::createNonNativeAsset("BTC", "GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U");
        $destinationAssets = [
            Asset::native(),
            Asset::createNonNativeAsset("USD", "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5")
        ];

        $response = $requestBuilder
            ->forDestinationAssets($destinationAssets)
            ->forSourceAmount("50")
            ->forSourceAsset($sourceAsset)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    public function testStrictSendPathsWithNativeSourceAsset(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("native", $params['source_asset_type']);
            $this->assertArrayNotHasKey('source_asset_code', $params);
            $this->assertArrayNotHasKey('source_asset_issuer', $params);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new StrictSendPathsRequestBuilder($httpClient);

        $response = $requestBuilder
            ->forSourceAmount("50")
            ->forSourceAsset(Asset::native())
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    public function testStrictReceivePathsWithPagination(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
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
        $requestBuilder = new StrictReceivePathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5");

        $response = $requestBuilder
            ->forDestinationAmount("100")
            ->forDestinationAsset($asset)
            ->limit(10)
            ->order("desc")
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    public function testStrictSendPathsWithPagination(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("20", $params['limit']);
            $this->assertEquals("asc", $params['order']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new StrictSendPathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("BTC", "GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U");

        $response = $requestBuilder
            ->forSourceAmount("50")
            ->forSourceAsset($asset)
            ->limit(20)
            ->order("asc")
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    public function testStrictReceivePathsWithCursor(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('cursor', $params);
            $this->assertEquals("test_cursor", $params['cursor']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new StrictReceivePathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5");

        $response = $requestBuilder
            ->forDestinationAmount("100")
            ->forDestinationAsset($asset)
            ->cursor("test_cursor")
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    public function testStrictSendPathsWithCursor(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('cursor', $params);
            $this->assertEquals("test_cursor", $params['cursor']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new StrictSendPathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("BTC", "GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U");

        $response = $requestBuilder
            ->forSourceAmount("50")
            ->forSourceAsset($asset)
            ->cursor("test_cursor")
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    public function testStrictReceivePathsWithAllParameters(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54", $params['destination_account']);
            $this->assertEquals("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['source_account']);
            $this->assertEquals("100", $params['destination_amount']);
            $this->assertEquals("10", $params['limit']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new StrictReceivePathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", "GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5");

        $response = $requestBuilder
            ->forDestinationAccount("GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54")
            ->forSourceAccount("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5")
            ->forDestinationAmount("100")
            ->forDestinationAsset($asset)
            ->limit(10)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    public function testStrictSendPathsWithAllParameters(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54", $params['source_account']);
            $this->assertEquals("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5", $params['destination_account']);
            $this->assertEquals("50", $params['source_amount']);
            $this->assertEquals("20", $params['limit']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new StrictSendPathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("BTC", "GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U");

        $response = $requestBuilder
            ->forSourceAccount("GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54")
            ->forDestinationAccount("GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5")
            ->forSourceAmount("50")
            ->forSourceAsset($asset)
            ->limit(20)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }
}
