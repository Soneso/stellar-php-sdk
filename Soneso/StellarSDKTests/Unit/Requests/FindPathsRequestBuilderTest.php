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
use Soneso\StellarSDK\Requests\FindPathsRequestBuilder;
use Soneso\StellarSDK\Responses\PaymentPath\PathsPageResponse;

/**
 * Unit tests for FindPathsRequestBuilder (deprecated)
 *
 * Tests URL building, parameter setting, and response parsing
 * for the deprecated find paths endpoint in Horizon.
 */
class FindPathsRequestBuilderTest extends TestCase
{
    private const TEST_SOURCE_ACCOUNT = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const TEST_DESTINATION_ACCOUNT = 'GBQECQVAS2FJ7DLCUXDASZAJQLWPXNTCR2FXSCTV2ATHWKIE6T7MKBU4';
    private const TEST_ISSUER = 'GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5';

    private string $pathsPageResponse = <<<JSON
{
  "_links": {
    "self": {
      "href": "https://horizon-testnet.stellar.org/paths"
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

    /**
     * Helper method to create a mocked HTTP client
     */
    private function createMockedClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        return new Client(['handler' => $handlerStack, 'base_uri' => 'https://horizon-testnet.stellar.org']);
    }

    /**
     * Test basic URL building for paths endpoint
     */
    public function testBuildBasicUrl(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new FindPathsRequestBuilder($client);

        $url = $builder->buildUrl();

        $this->assertStringContainsString('paths?', $url);
    }

    /**
     * Test forDestinationAccount parameter
     */
    public function testForDestinationAccount(): void
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
            $this->assertEquals(self::TEST_DESTINATION_ACCOUNT, $params['destination_account']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $builder = new FindPathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", self::TEST_ISSUER);

        $response = $builder
            ->forDestinationAccount(self::TEST_DESTINATION_ACCOUNT)
            ->forDestinationAmount("100")
            ->forDestinationAsset($asset)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    /**
     * Test forSourceAccount parameter
     */
    public function testForSourceAccount(): void
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
            $this->assertEquals(self::TEST_SOURCE_ACCOUNT, $params['source_account']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $builder = new FindPathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", self::TEST_ISSUER);

        $response = $builder
            ->forSourceAccount(self::TEST_SOURCE_ACCOUNT)
            ->forDestinationAmount("100")
            ->forDestinationAsset($asset)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    /**
     * Test forDestinationAmount parameter
     */
    public function testForDestinationAmount(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('destination_amount', $params);
            $this->assertEquals("100.50", $params['destination_amount']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $builder = new FindPathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", self::TEST_ISSUER);

        $response = $builder
            ->forDestinationAmount("100.50")
            ->forDestinationAsset($asset)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    /**
     * Test forDestinationAsset with credit alphanum asset
     */
    public function testForDestinationAssetCreditAlphanum(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("credit_alphanum4", $params['destination_asset_type']);
            $this->assertEquals("USD", $params['destination_asset_code']);
            $this->assertEquals(self::TEST_ISSUER, $params['destination_asset_issuer']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $builder = new FindPathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", self::TEST_ISSUER);

        $response = $builder
            ->forDestinationAmount("100")
            ->forDestinationAsset($asset)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    /**
     * Test forDestinationAsset with native XLM
     */
    public function testForDestinationAssetNative(): void
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
        $builder = new FindPathsRequestBuilder($httpClient);

        $response = $builder
            ->forDestinationAmount("100")
            ->forDestinationAsset(Asset::native())
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    /**
     * Test cursor parameter
     */
    public function testCursor(): void
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
        $builder = new FindPathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", self::TEST_ISSUER);

        $response = $builder
            ->forDestinationAmount("100")
            ->forDestinationAsset($asset)
            ->cursor("test_cursor")
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    /**
     * Test limit parameter
     */
    public function testLimit(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('limit', $params);
            $this->assertEquals("20", $params['limit']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $builder = new FindPathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", self::TEST_ISSUER);

        $response = $builder
            ->forDestinationAmount("100")
            ->forDestinationAsset($asset)
            ->limit(20)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    /**
     * Test order parameter
     */
    public function testOrder(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('order', $params);
            $this->assertEquals("desc", $params['order']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $builder = new FindPathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", self::TEST_ISSUER);

        $response = $builder
            ->forDestinationAmount("100")
            ->forDestinationAsset($asset)
            ->order("desc")
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    /**
     * Test request method with URL
     */
    public function testRequest(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->pathsPageResponse)
        ]);
        $builder = new FindPathsRequestBuilder($client);

        $response = $builder->request('https://horizon-testnet.stellar.org/paths?destination_amount=100');

        $this->assertInstanceOf(PathsPageResponse::class, $response);
        $this->assertNotNull($response->getPaths());
    }

    /**
     * Test execute method builds URL correctly
     */
    public function testExecute(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->pathsPageResponse)
        ]);
        $builder = new FindPathsRequestBuilder($client);
        $asset = Asset::createNonNativeAsset("USD", self::TEST_ISSUER);

        $response = $builder
            ->forDestinationAmount("100")
            ->forDestinationAsset($asset)
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
        $paths = $response->getPaths();
        $this->assertNotNull($paths);
        $this->assertCount(2, $paths->toArray());
    }

    /**
     * Test all parameters combined
     */
    public function testAllParameters(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->pathsPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals(self::TEST_SOURCE_ACCOUNT, $params['source_account']);
            $this->assertEquals(self::TEST_DESTINATION_ACCOUNT, $params['destination_account']);
            $this->assertEquals("100", $params['destination_amount']);
            $this->assertEquals("credit_alphanum4", $params['destination_asset_type']);
            $this->assertEquals("USD", $params['destination_asset_code']);
            $this->assertEquals(self::TEST_ISSUER, $params['destination_asset_issuer']);
            $this->assertEquals("10", $params['limit']);
            $this->assertEquals("desc", $params['order']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $builder = new FindPathsRequestBuilder($httpClient);
        $asset = Asset::createNonNativeAsset("USD", self::TEST_ISSUER);

        $response = $builder
            ->forSourceAccount(self::TEST_SOURCE_ACCOUNT)
            ->forDestinationAccount(self::TEST_DESTINATION_ACCOUNT)
            ->forDestinationAmount("100")
            ->forDestinationAsset($asset)
            ->limit(10)
            ->order("desc")
            ->execute();

        $this->assertInstanceOf(PathsPageResponse::class, $response);
    }

    /**
     * Test method chaining returns correct instance
     */
    public function testMethodChaining(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new FindPathsRequestBuilder($client);

        $result = $builder->forSourceAccount(self::TEST_SOURCE_ACCOUNT);
        $this->assertInstanceOf(FindPathsRequestBuilder::class, $result);

        $result = $builder->forDestinationAccount(self::TEST_DESTINATION_ACCOUNT);
        $this->assertInstanceOf(FindPathsRequestBuilder::class, $result);

        $result = $builder->forDestinationAmount("100");
        $this->assertInstanceOf(FindPathsRequestBuilder::class, $result);

        $asset = Asset::createNonNativeAsset("USD", self::TEST_ISSUER);
        $result = $builder->forDestinationAsset($asset);
        $this->assertInstanceOf(FindPathsRequestBuilder::class, $result);

        $result = $builder->cursor("test");
        $this->assertInstanceOf(FindPathsRequestBuilder::class, $result);

        $result = $builder->limit(10);
        $this->assertInstanceOf(FindPathsRequestBuilder::class, $result);

        $result = $builder->order("asc");
        $this->assertInstanceOf(FindPathsRequestBuilder::class, $result);
    }

    /**
     * Test response parsing with paths
     */
    public function testResponseParsing(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->pathsPageResponse)
        ]);
        $builder = new FindPathsRequestBuilder($client);
        $asset = Asset::createNonNativeAsset("USD", self::TEST_ISSUER);

        $response = $builder
            ->forDestinationAmount("100")
            ->forDestinationAsset($asset)
            ->execute();

        $paths = $response->getPaths();
        $this->assertCount(2, $paths->toArray());

        $firstPath = $paths->toArray()[0];
        $this->assertEquals("0.0001234", $firstPath->getSourceAmount());
        $this->assertEquals("100.0000000", $firstPath->getDestinationAmount());
        $this->assertEquals("credit_alphanum4", $firstPath->getSourceAssetType());
        $this->assertEquals("BTC", $firstPath->getSourceAssetCode());

        $secondPath = $paths->toArray()[1];
        $this->assertEquals("52.1234567", $secondPath->getSourceAmount());
        $this->assertEquals("native", $secondPath->getSourceAssetType());
    }
}
