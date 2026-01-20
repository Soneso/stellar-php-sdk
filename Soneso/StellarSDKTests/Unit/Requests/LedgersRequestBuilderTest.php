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
use Soneso\StellarSDK\Requests\LedgersRequestBuilder;
use Soneso\StellarSDK\Responses\Ledger\LedgerResponse;
use Soneso\StellarSDK\Responses\Ledger\LedgersPageResponse;

class LedgersRequestBuilderTest extends TestCase
{
    private string $ledgerResponse = <<<JSON
{
  "_links": {
    "self": {
      "href": "https://horizon-testnet.stellar.org/ledgers/123456"
    },
    "transactions": {
      "href": "https://horizon-testnet.stellar.org/ledgers/123456/transactions{?cursor,limit,order}",
      "templated": true
    },
    "operations": {
      "href": "https://horizon-testnet.stellar.org/ledgers/123456/operations{?cursor,limit,order}",
      "templated": true
    },
    "payments": {
      "href": "https://horizon-testnet.stellar.org/ledgers/123456/payments{?cursor,limit,order}",
      "templated": true
    },
    "effects": {
      "href": "https://horizon-testnet.stellar.org/ledgers/123456/effects{?cursor,limit,order}",
      "templated": true
    }
  },
  "id": "743a1f566e87e2fc02e5d88ba8da2b33a4f2f83d6483261a7a1f1b650dc4859e",
  "paging_token": "529811640377344",
  "hash": "743a1f566e87e2fc02e5d88ba8da2b33a4f2f83d6483261a7a1f1b650dc4859e",
  "prev_hash": "c9e60dc2a9c42fc91c9ef0c81ad01dc64ef6ff2eb41aa51ba85c0ff63bf54d05",
  "sequence": 123456,
  "successful_transaction_count": 15,
  "failed_transaction_count": 0,
  "operation_count": 45,
  "tx_set_operation_count": 45,
  "closed_at": "2024-01-15T10:30:00Z",
  "total_coins": "105443902087.3472865",
  "fee_pool": "1871136.0723929",
  "base_fee_in_stroops": 100,
  "base_reserve_in_stroops": 5000000,
  "max_tx_set_size": 1000,
  "protocol_version": 20,
  "header_xdr": "AAAAFMnmDcKpxC/JHJ7wyBrQHcZO9v8utBqlG6hcD/Y79U0F"
}
JSON;

    private string $ledgersPageResponse = <<<JSON
{
  "_links": {
    "self": {
      "href": "https://horizon-testnet.stellar.org/ledgers?cursor=\u0026limit=2\u0026order=asc"
    },
    "next": {
      "href": "https://horizon-testnet.stellar.org/ledgers?cursor=8589938688\u0026limit=2\u0026order=asc"
    },
    "prev": {
      "href": "https://horizon-testnet.stellar.org/ledgers?cursor=4294971392\u0026limit=2\u0026order=desc"
    }
  },
  "_embedded": {
    "records": [
      {
        "_links": {
          "self": {
            "href": "https://horizon-testnet.stellar.org/ledgers/1"
          },
          "transactions": {
            "href": "https://horizon-testnet.stellar.org/ledgers/1/transactions{?cursor,limit,order}",
            "templated": true
          },
          "operations": {
            "href": "https://horizon-testnet.stellar.org/ledgers/1/operations{?cursor,limit,order}",
            "templated": true
          },
          "payments": {
            "href": "https://horizon-testnet.stellar.org/ledgers/1/payments{?cursor,limit,order}",
            "templated": true
          },
          "effects": {
            "href": "https://horizon-testnet.stellar.org/ledgers/1/effects{?cursor,limit,order}",
            "templated": true
          }
        },
        "id": "e8e10918f9c000c73119abe54cf089f59f9015cc93c49ccf00b5e8b9afb6e6b1",
        "paging_token": "4294971392",
        "hash": "e8e10918f9c000c73119abe54cf089f59f9015cc93c49ccf00b5e8b9afb6e6b1",
        "prev_hash": "0000000000000000000000000000000000000000000000000000000000000000",
        "sequence": 1,
        "successful_transaction_count": 0,
        "failed_transaction_count": 0,
        "operation_count": 0,
        "tx_set_operation_count": 0,
        "closed_at": "1970-01-01T00:00:00Z",
        "total_coins": "100000000000.0000000",
        "fee_pool": "0.0000000",
        "base_fee_in_stroops": 100,
        "base_reserve_in_stroops": 5000000,
        "max_tx_set_size": 500,
        "protocol_version": 0,
        "header_xdr": "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA"
      },
      {
        "_links": {
          "self": {
            "href": "https://horizon-testnet.stellar.org/ledgers/2"
          },
          "transactions": {
            "href": "https://horizon-testnet.stellar.org/ledgers/2/transactions{?cursor,limit,order}",
            "templated": true
          },
          "operations": {
            "href": "https://horizon-testnet.stellar.org/ledgers/2/operations{?cursor,limit,order}",
            "templated": true
          },
          "payments": {
            "href": "https://horizon-testnet.stellar.org/ledgers/2/payments{?cursor,limit,order}",
            "templated": true
          },
          "effects": {
            "href": "https://horizon-testnet.stellar.org/ledgers/2/effects{?cursor,limit,order}",
            "templated": true
          }
        },
        "id": "5b98e1ba19d846b20e050a3e059b0293c3cf1eb97f9504c5632815be63f231ce",
        "paging_token": "8589938688",
        "hash": "5b98e1ba19d846b20e050a3e059b0293c3cf1eb97f9504c5632815be63f231ce",
        "prev_hash": "e8e10918f9c000c73119abe54cf089f59f9015cc93c49ccf00b5e8b9afb6e6b1",
        "sequence": 2,
        "successful_transaction_count": 1,
        "failed_transaction_count": 0,
        "operation_count": 1,
        "tx_set_operation_count": 1,
        "closed_at": "2015-07-16T23:29:30Z",
        "total_coins": "100000000000.0000000",
        "fee_pool": "0.0000100",
        "base_fee_in_stroops": 100,
        "base_reserve_in_stroops": 5000000,
        "max_tx_set_size": 500,
        "protocol_version": 0,
        "header_xdr": "AAAAA"
      }
    ]
  }
}
JSON;

    public function testGetSpecificLedger(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->ledgerResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $this->assertEquals("GET", $request->getMethod());
            $this->assertStringContainsString("ledgers/123456", $request->getUri()->getPath());
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new LedgersRequestBuilder($httpClient);
        $response = $requestBuilder->ledger("123456");

        $this->assertInstanceOf(LedgerResponse::class, $response);
        $this->assertEquals("123456", $response->getSequence()->toString());
        $this->assertEquals("743a1f566e87e2fc02e5d88ba8da2b33a4f2f83d6483261a7a1f1b650dc4859e", $response->getHash());
        $this->assertEquals(15, $response->getSuccessfulTransactionCount());
        $this->assertEquals(0, $response->getFailedTransactionCount());
        $this->assertEquals(45, $response->getOperationCount());
        $this->assertEquals(20, $response->getProtocolVersion());
    }

    public function testGetLedgersWithPagination(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->ledgersPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $this->assertEquals("GET", $request->getMethod());
            $uri = $request->getUri();
            $this->assertStringContainsString("ledgers", $uri->getPath());
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("2", $params['limit']);
            $this->assertEquals("asc", $params['order']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new LedgersRequestBuilder($httpClient);
        $response = $requestBuilder->limit(2)->order("asc")->execute();

        $this->assertInstanceOf(LedgersPageResponse::class, $response);
        $ledgers = $response->getLedgers();
        $this->assertCount(2, $ledgers->toArray());

        $firstLedger = $ledgers->toArray()[0];
        $this->assertEquals("1", $firstLedger->getSequence()->toString());
        $this->assertEquals(0, $firstLedger->getSuccessfulTransactionCount());

        $secondLedger = $ledgers->toArray()[1];
        $this->assertEquals("2", $secondLedger->getSequence()->toString());
        $this->assertEquals(1, $secondLedger->getSuccessfulTransactionCount());
    }

    public function testGetLedgersWithCursor(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->ledgersPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('cursor', $params);
            $this->assertEquals("4294971392", $params['cursor']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new LedgersRequestBuilder($httpClient);
        $response = $requestBuilder->cursor("4294971392")->execute();

        $this->assertInstanceOf(LedgersPageResponse::class, $response);
    }

    public function testGetLedgersDescendingOrder(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->ledgersPageResponse)
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
        $requestBuilder = new LedgersRequestBuilder($httpClient);
        $response = $requestBuilder->order("desc")->execute();

        $this->assertInstanceOf(LedgersPageResponse::class, $response);
    }

    public function testGetLedgersWithLimit(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->ledgersPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertArrayHasKey('limit', $params);
            $this->assertEquals("10", $params['limit']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new LedgersRequestBuilder($httpClient);
        $response = $requestBuilder->limit(10)->execute();

        $this->assertInstanceOf(LedgersPageResponse::class, $response);
    }

    public function testGetLedgersWithMultipleParameters(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->ledgersPageResponse)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $uri = $request->getUri();
            parse_str($uri->getQuery(), $params);
            $this->assertEquals("now", $params['cursor']);
            $this->assertEquals("20", $params['limit']);
            $this->assertEquals("desc", $params['order']);
            return $request;
        }));

        $httpClient = new Client(['handler' => $stack]);
        $requestBuilder = new LedgersRequestBuilder($httpClient);
        $response = $requestBuilder
            ->cursor("now")
            ->limit(20)
            ->order("desc")
            ->execute();

        $this->assertInstanceOf(LedgersPageResponse::class, $response);
    }
}
