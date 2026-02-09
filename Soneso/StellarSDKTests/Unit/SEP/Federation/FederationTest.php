<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\Federation;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soneso\StellarSDK\SEP\Federation\Federation;
use Soneso\StellarSDK\SEP\Federation\FederationRequestBuilder;

class FederationTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testResolveStellarAddress(): void
    {
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->successResponse())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("name", $query_array["type"]);
            $this->assertEquals("bob*soneso.com", $query_array["q"]);
            return $request;
        }));

        $requestBuilder = (new FederationRequestBuilder(new Client(['handler' => $stack]), "https://stellarid.io/federation"))
            ->forStringToLookUp("bob*soneso.com")
            ->forType("name");
        $response = $requestBuilder->execute();
        $this->assertNotNull($response);
        $this->assertEquals("bob*soneso.com", $response->getStellarAddress());
        $this->assertEquals("GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI", $response->getAccountId());
        $this->assertEquals("text", $response->getMemoType());
        $this->assertEquals("hello memo text", $response->getMemo());
    }

    public function testResolveStellarAccountId(): void
    {
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->successResponse())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("id", $query_array["type"]);
            $this->assertEquals("GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI", $query_array["q"]);
            return $request;
        }));

        $response = Federation::resolveStellarAccountId("GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI",
            "https://stellarid.io/federation", httpClient: new Client(['handler' => $stack]));
        $this->assertNotNull($response);
        $this->assertEquals("bob*soneso.com", $response->getStellarAddress());
        $this->assertEquals("GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI", $response->getAccountId());
        $this->assertEquals("text", $response->getMemoType());
        $this->assertEquals("hello memo text", $response->getMemo());
    }

    private function successResponse() : string {
        return "{ \"stellar_address\": \"bob*soneso.com\", \"account_id\": \"GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI\",\"memo_type\": \"text\", \"memo\": \"hello memo text\"}";
    }

    public function testResolveTransactionId(): void
    {
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->successResponse())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("txid", $query_array["type"]);
            $this->assertEquals("ae05181b239bd4a64ba2fb8086901479a0bde86f8e912150e74241fe4f5f0948", $query_array["q"]);
            return $request;
        }));

        $response = Federation::resolveStellarTransactionId(txId: "ae05181b239bd4a64ba2fb8086901479a0bde86f8e912150e74241fe4f5f0948",
            federationServerUrl:"https://fedtest.io/federation", httpClient: new Client(['handler' => $stack]));
        $this->assertNotNull($response);
        $this->assertEquals("bob*soneso.com", $response->getStellarAddress());
        $this->assertEquals("GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI", $response->getAccountId());
        $this->assertEquals("text", $response->getMemoType());
        $this->assertEquals("hello memo text", $response->getMemo());

    }

    public function testResolveForward(): void
    {
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->successResponse())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            parse_str($request->getUri()->getQuery(), $query_array);
            $this->assertEquals("forward", $query_array["type"]);
            $this->assertEquals("bank_account", $query_array["forward_type"]);
            $this->assertEquals("BOPBPHMM", $query_array["swift"]);
            $this->assertEquals("2382376", $query_array["acct"]);
            return $request;
        }));

        $parameters = ["forward_type" => "bank_account", "swift" => "BOPBPHMM", "acct" => "2382376"];
        $response = Federation::resolveForward(queryParameters: $parameters,
            federationServerUrl:"https://fedtest.io/federation", httpClient: new Client(['handler' => $stack]));
        $this->assertNotNull($response);
        $this->assertEquals("bob*soneso.com", $response->getStellarAddress());
        $this->assertEquals("GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI", $response->getAccountId());
        $this->assertEquals("text", $response->getMemoType());
        $this->assertEquals("hello memo text", $response->getMemo());

    }
}