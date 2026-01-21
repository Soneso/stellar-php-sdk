<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResult;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResults;
use Soneso\StellarSDK\Soroban\Responses\SorobanRpcResponse;
use Soneso\StellarSDK\Soroban\Responses\SorobanRpcErrorResponse;
use Soneso\StellarSDK\Soroban\Responses\TransactionEvents;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrDiagnosticEvent;

class SorobanResponsesTest extends TestCase
{
    public function testGetTransactionResponseGetters(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'status' => 'SUCCESS',
                'latestLedger' => 123456,
                'latestLedgerCloseTime' => '1234567890',
                'oldestLedger' => 123400,
                'oldestLedgerCloseTime' => '1234567800',
                'ledger' => 123450,
                'createdAt' => '1234567850',
                'applicationOrder' => 1,
                'feeBump' => false,
                'envelopeXdr' => 'AAAAAgAAAAA=',
                'resultXdr' => 'AAAAAAAAAGQAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAA=',
                'resultMetaXdr' => 'AAAAAwAAAAA=',
                'txHash' => 'abc123',
                'diagnosticEventsXdr' => ['AAAABgAAAAA=']
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);

        $this->assertEquals('SUCCESS', $response->getStatus());
        $this->assertEquals(123456, $response->getLatestLedger());
        $this->assertEquals('1234567890', $response->getLatestLedgerCloseTime());
        $this->assertEquals(123400, $response->getOldestLedger());
        $this->assertEquals('1234567800', $response->getOldestLedgerCloseTime());
        $this->assertEquals(123450, $response->getLedger());
        $this->assertEquals('1234567850', $response->getCreatedAt());
        $this->assertEquals(1, $response->getApplicationOrder());
        $this->assertFalse($response->getFeeBump());
        $this->assertEquals('AAAAAgAAAAA=', $response->getEnvelopeXdr());
        $this->assertEquals('AAAAAAAAAGQAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAA=', $response->getResultXdr());
        $this->assertEquals('AAAAAwAAAAA=', $response->getResultMetaXdr());
        $this->assertEquals('abc123', $response->getTxHash());
        $this->assertIsArray($response->getDiagnosticEventsXdr());
        $this->assertCount(1, $response->getDiagnosticEventsXdr());
    }

    public function testGetTransactionResponseSetters(): void
    {
        $response = GetTransactionResponse::fromJson(['result' => []]);

        $response->setTxHash('newhash123');
        $this->assertEquals('newhash123', $response->getTxHash());

        $diagnosticEvents = ['event1', 'event2'];
        $response->setDiagnosticEventsXdr($diagnosticEvents);
        $this->assertEquals($diagnosticEvents, $response->getDiagnosticEventsXdr());

        $events = TransactionEvents::fromJson([]);
        $response->setEvents($events);
        $this->assertSame($events, $response->getEvents());
    }

    public function testGetTransactionResponseGetXdrTransactionEnvelope(): void
    {
        $json = [
            'result' => [
                'status' => 'SUCCESS',
                'envelopeXdr' => null
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);
        $xdrEnvelope = $response->getXdrTransactionEnvelope();

        $this->assertNull($xdrEnvelope);
    }

    public function testGetTransactionResponseGetXdrTransactionResult(): void
    {
        $validResultXdr = 'AAAAAAAAAGQAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAA=';

        $json = [
            'result' => [
                'status' => 'SUCCESS',
                'resultXdr' => $validResultXdr
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);
        $xdrResult = $response->getXdrTransactionResult();

        $this->assertNotNull($xdrResult);
    }

    public function testGetTransactionResponseGetXdrTransactionMeta(): void
    {
        $json = [
            'result' => [
                'status' => 'SUCCESS',
                'resultXdr' => null
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);
        $xdrMeta = $response->getXdrTransactionMeta();

        $this->assertNull($xdrMeta);
    }

    public function testGetTransactionResponseWithEvents(): void
    {
        $json = [
            'result' => [
                'status' => 'SUCCESS',
                'events' => [
                    'events' => []
                ]
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);

        $this->assertNotNull($response->events);
        $this->assertInstanceOf(TransactionEvents::class, $response->getEvents());
    }

    public function testGetTransactionResponseNotFound(): void
    {
        $json = [
            'result' => [
                'status' => 'NOT_FOUND',
                'latestLedger' => 123456,
                'latestLedgerCloseTime' => '1234567890',
                'oldestLedger' => 123400,
                'oldestLedgerCloseTime' => '1234567800'
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);

        $this->assertEquals('NOT_FOUND', $response->getStatus());
        $this->assertNull($response->getLedger());
        $this->assertNull($response->getCreatedAt());
    }

    public function testGetTransactionResponseFailed(): void
    {
        $json = [
            'result' => [
                'status' => 'FAILED',
                'latestLedger' => 123456,
                'latestLedgerCloseTime' => '1234567890',
                'ledger' => 123450,
                'createdAt' => '1234567850'
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);

        $this->assertEquals('FAILED', $response->getStatus());
        $this->assertEquals(123450, $response->getLedger());
        $this->assertEquals('1234567850', $response->getCreatedAt());
    }

    public function testSendTransactionResponseGetters(): void
    {
        $json = [
            'result' => [
                'hash' => 'abc123def456',
                'status' => 'PENDING',
                'latestLedger' => 123456,
                'latestLedgerCloseTime' => '1234567890'
            ]
        ];

        $response = SendTransactionResponse::fromJson($json);

        $this->assertEquals('abc123def456', $response->getHash());
        $this->assertEquals('PENDING', $response->getStatus());
        $this->assertEquals(123456, $response->getLatestLedger());
        $this->assertEquals('1234567890', $response->getLatestLedgerCloseTime());
        $this->assertNull($response->getErrorResultXdr());
        $this->assertNull($response->getDiagnosticEvents());
    }

    public function testSendTransactionResponseWithError(): void
    {
        $errorResultXdr = 'AAAAAAAAAGT////7AAAAAA==';

        $json = [
            'result' => [
                'hash' => 'abc123',
                'status' => 'ERROR',
                'latestLedger' => 123456,
                'latestLedgerCloseTime' => '1234567890',
                'errorResultXdr' => $errorResultXdr
            ]
        ];

        $response = SendTransactionResponse::fromJson($json);

        $this->assertEquals('ERROR', $response->getStatus());
        $this->assertEquals($errorResultXdr, $response->getErrorResultXdr());
    }

    public function testSendTransactionResponseGetErrorXdrTransactionResult(): void
    {
        $errorResultXdr = 'AAAAAAAAAGT////7AAAAAA==';

        $json = [
            'result' => [
                'hash' => 'abc123',
                'status' => 'ERROR',
                'latestLedger' => 123456,
                'latestLedgerCloseTime' => '1234567890',
                'errorResultXdr' => $errorResultXdr
            ]
        ];

        $response = SendTransactionResponse::fromJson($json);
        $xdrResult = $response->getErrorXdrTransactionResult();

        $this->assertNotNull($xdrResult);
    }

    public function testSendTransactionResponseStatusConstants(): void
    {
        $this->assertEquals('PENDING', SendTransactionResponse::STATUS_PENDING);
        $this->assertEquals('DUPLICATE', SendTransactionResponse::STATUS_DUPLICATE);
        $this->assertEquals('TRY_AGAIN_LATER', SendTransactionResponse::STATUS_TRY_AGAIN_LATER);
        $this->assertEquals('ERROR', SendTransactionResponse::STATUS_ERROR);
    }

    public function testSimulateTransactionResultsIteration(): void
    {
        $result1 = new SimulateTransactionResult([]);
        $result2 = new SimulateTransactionResult([]);
        $result3 = new SimulateTransactionResult([]);

        $results = new SimulateTransactionResults($result1, $result2, $result3);

        $this->assertEquals(3, $results->count());

        $count = 0;
        foreach ($results as $result) {
            $this->assertInstanceOf(SimulateTransactionResult::class, $result);
            $count++;
        }
        $this->assertEquals(3, $count);
    }

    public function testSimulateTransactionResultsAdd(): void
    {
        $results = new SimulateTransactionResults();

        $this->assertEquals(0, $results->count());

        $result1 = new SimulateTransactionResult([]);
        $results->add($result1);

        $this->assertEquals(1, $results->count());
    }

    public function testSimulateTransactionResultsToArray(): void
    {
        $result1 = new SimulateTransactionResult([]);
        $result2 = new SimulateTransactionResult([]);

        $results = new SimulateTransactionResults($result1, $result2);
        $array = $results->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertSame($result1, $array[0]);
        $this->assertSame($result2, $array[1]);
    }

    public function testSimulateTransactionResultsCurrent(): void
    {
        $result1 = new SimulateTransactionResult([]);
        $result2 = new SimulateTransactionResult([]);

        $results = new SimulateTransactionResults($result1, $result2);

        $results->rewind();
        $this->assertSame($result1, $results->current());

        $results->next();
        if ($results->valid()) {
            $this->assertSame($result2, $results->current());
        }
    }

    public function testSorobanRpcResponseGettersSetters(): void
    {
        $jsonResponse = ['jsonrpc' => '2.0', 'id' => 1];
        $response = new class($jsonResponse) extends SorobanRpcResponse {};

        $this->assertEquals($jsonResponse, $response->getJsonResponse());

        $newJson = ['jsonrpc' => '2.0', 'id' => 2];
        $response->setJsonResponse($newJson);
        $this->assertEquals($newJson, $response->getJsonResponse());

        $this->assertNull($response->getError());

        $errorResponse = SorobanRpcErrorResponse::fromJson([
            'error' => [
                'code' => -32603,
                'message' => 'Internal error'
            ]
        ]);
        $response->setError($errorResponse);

        $this->assertNotNull($response->getError());
        $this->assertSame($errorResponse, $response->getError());
    }

    public function testSorobanRpcErrorResponseParsing(): void
    {
        $json = [
            'error' => [
                'code' => -32601,
                'message' => 'Method not found',
                'data' => ['method' => 'unknownMethod']
            ]
        ];

        $errorResponse = SorobanRpcErrorResponse::fromJson($json);

        $this->assertEquals(-32601, $errorResponse->getCode());
        $this->assertEquals('Method not found', $errorResponse->getMessage());
        $this->assertIsArray($errorResponse->getData());
        $this->assertEquals(['method' => 'unknownMethod'], $errorResponse->getData());
    }

    public function testGetTransactionResponseWithError(): void
    {
        $json = [
            'error' => [
                'code' => -32600,
                'message' => 'Invalid Request'
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);

        $this->assertNotNull($response->error);
        $this->assertEquals(-32600, $response->error->getCode());
        $this->assertEquals('Invalid Request', $response->error->getMessage());
    }

    public function testSendTransactionResponseWithRpcError(): void
    {
        $json = [
            'error' => [
                'code' => -32603,
                'message' => 'Internal error',
                'data' => ['details' => 'Network error']
            ]
        ];

        $response = SendTransactionResponse::fromJson($json);

        $this->assertNotNull($response->error);
        $this->assertEquals(-32603, $response->error->getCode());
        $this->assertEquals('Internal error', $response->error->getMessage());
        $this->assertIsArray($response->error->getData());
    }
}
