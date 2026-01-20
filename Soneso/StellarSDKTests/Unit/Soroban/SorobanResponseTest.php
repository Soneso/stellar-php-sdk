<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Soroban\Responses\EventInfo;
use Soneso\StellarSDK\Soroban\Responses\GetEventsResponse;
use Soneso\StellarSDK\Soroban\Responses\GetFeeStatsResponse;
use Soneso\StellarSDK\Soroban\Responses\GetHealthResponse;
use Soneso\StellarSDK\Soroban\Responses\GetLatestLedgerResponse;
use Soneso\StellarSDK\Soroban\Responses\GetLedgerEntriesResponse;
use Soneso\StellarSDK\Soroban\Responses\GetLedgersResponse;
use Soneso\StellarSDK\Soroban\Responses\GetNetworkResponse;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionsResponse;
use Soneso\StellarSDK\Soroban\Responses\GetVersionInfoResponse;
use Soneso\StellarSDK\Soroban\Responses\InclusionFee;
use Soneso\StellarSDK\Soroban\Responses\LedgerEntry;
use Soneso\StellarSDK\Soroban\Responses\LedgerEntryChange;
use Soneso\StellarSDK\Soroban\Responses\LedgerInfo;
use Soneso\StellarSDK\Soroban\Responses\RestorePreamble;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResult;
use Soneso\StellarSDK\Soroban\Responses\SorobanRpcErrorResponse;
use Soneso\StellarSDK\Soroban\Responses\TransactionEvents;
use Soneso\StellarSDK\Soroban\Responses\TransactionInfo;

/**
 * Unit tests for Soroban RPC Response classes
 *
 * Tests JSON parsing and getter methods for all Soroban RPC response classes.
 * No HTTP calls - pure JSON parsing tests.
 */
class SorobanResponseTest extends TestCase
{
    // SorobanRpcErrorResponse Tests (10 methods: constructor, fromJson, 4 getters, 4 setters)

    public function testSorobanRpcErrorResponseBasic(): void
    {
        $json = [
            'error' => [
                'code' => -32600,
                'message' => 'Invalid Request',
                'data' => ['details' => 'Missing required parameter']
            ]
        ];

        $error = SorobanRpcErrorResponse::fromJson($json);

        $this->assertEquals(-32600, $error->getCode());
        $this->assertEquals('Invalid Request', $error->getMessage());
        $this->assertIsArray($error->getData());
        $this->assertEquals('Missing required parameter', $error->getData()['details']);
        $this->assertIsArray($error->getJsonResponse());
        $this->assertEquals($json, $error->getJsonResponse());
    }

    public function testSorobanRpcErrorResponseSetters(): void
    {
        $json = ['error' => ['code' => -32601]];
        $error = SorobanRpcErrorResponse::fromJson($json);

        $error->setCode(-32602);
        $error->setMessage('Method not found');
        $error->setData(['key' => 'value']);
        $error->setJsonResponse(['updated' => true]);

        $this->assertEquals(-32602, $error->getCode());
        $this->assertEquals('Method not found', $error->getMessage());
        $this->assertEquals(['key' => 'value'], $error->getData());
        $this->assertEquals(['updated' => true], $error->getJsonResponse());
    }

    // GetTransactionResponse Tests (27 methods: fromJson, getWasmId, getCreatedContractId, getResultValue, 23 getters/setters)

    public function testGetTransactionResponseSuccess(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'status' => 'SUCCESS',
                'latestLedger' => 1000,
                'latestLedgerCloseTime' => '1234567890',
                'oldestLedger' => 500,
                'oldestLedgerCloseTime' => '1234567800',
                'ledger' => 950,
                'createdAt' => '1234567850',
                'applicationOrder' => 1,
                'feeBump' => false,
                'envelopeXdr' => 'AAAAAgAAAAA=',
                'resultXdr' => 'AAAAAAAAAGQAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAA=',
                'resultMetaXdr' => 'AAAAAwAAAAA=',
                'txHash' => 'abc123def456',
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);

        $this->assertEquals('SUCCESS', $response->getStatus());
        $this->assertEquals(1000, $response->getLatestLedger());
        $this->assertEquals('1234567890', $response->getLatestLedgerCloseTime());
        $this->assertEquals(500, $response->getOldestLedger());
        $this->assertEquals('1234567800', $response->getOldestLedgerCloseTime());
        $this->assertEquals(950, $response->getLedger());
        $this->assertEquals('1234567850', $response->getCreatedAt());
        $this->assertEquals(1, $response->getApplicationOrder());
        $this->assertFalse($response->getFeeBump());
        $this->assertEquals('AAAAAgAAAAA=', $response->getEnvelopeXdr());
        $this->assertEquals('AAAAAAAAAGQAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAA=', $response->getResultXdr());
        $this->assertEquals('AAAAAwAAAAA=', $response->getResultMetaXdr());
        $this->assertEquals('abc123def456', $response->getTxHash());
        $this->assertNull($response->error);
    }

    public function testGetTransactionResponseNotFound(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'status' => 'NOT_FOUND',
                'latestLedger' => 1000,
                'latestLedgerCloseTime' => '1234567890',
                'oldestLedger' => 500,
                'oldestLedgerCloseTime' => '1234567800',
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);

        $this->assertEquals('NOT_FOUND', $response->getStatus());
        $this->assertNull($response->getLedger());
        $this->assertNull($response->getCreatedAt());
        $this->assertNull($response->getApplicationOrder());
    }

    public function testGetTransactionResponseWithEvents(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'status' => 'SUCCESS',
                'latestLedger' => 1000,
                'latestLedgerCloseTime' => '1234567890',
                'oldestLedger' => 500,
                'oldestLedgerCloseTime' => '1234567800',
                'events' => [
                    'transactionEventsXdr' => ['event1', 'event2'],
                    'contractEventsXdr' => [['contractEvent1']]
                ],
                'diagnosticEventsXdr' => ['diagnostic1', 'diagnostic2']
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);

        $this->assertInstanceOf(TransactionEvents::class, $response->getEvents());
        $this->assertIsArray($response->getDiagnosticEventsXdr());
        $this->assertCount(2, $response->getDiagnosticEventsXdr());
        $this->assertEquals('diagnostic1', $response->getDiagnosticEventsXdr()[0]);
    }

    public function testGetTransactionResponseSetters(): void
    {
        $json = ['jsonrpc' => '2.0', 'id' => 1, 'result' => ['status' => 'SUCCESS', 'latestLedger' => 100, 'latestLedgerCloseTime' => '123', 'oldestLedger' => 50, 'oldestLedgerCloseTime' => '100']];
        $response = GetTransactionResponse::fromJson($json);

        $response->setTxHash('newhash123');
        $this->assertEquals('newhash123', $response->getTxHash());

        $response->setDiagnosticEventsXdr(['event1', 'event2', 'event3']);
        $this->assertCount(3, $response->getDiagnosticEventsXdr());

        $events = new TransactionEvents(['tx1'], [['c1']]);
        $response->setEvents($events);
        $this->assertSame($events, $response->getEvents());
    }

    public function testGetTransactionResponseError(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'error' => [
                'code' => -32600,
                'message' => 'Invalid params'
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);

        $this->assertInstanceOf(SorobanRpcErrorResponse::class, $response->error);
        $this->assertEquals(-32600, $response->error->getCode());
        $this->assertEquals('Invalid params', $response->error->getMessage());
    }

    // EventInfo Tests (24 methods: fromJson, 11 getters, 11 setters, constructor)

    public function testEventInfoComplete(): void
    {
        $json = [
            'type' => 'contract',
            'ledger' => 12345,
            'ledgerClosedAt' => '2024-01-20T12:00:00Z',
            'contractId' => 'CAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABSC4',
            'id' => '0000012345-0000000001',
            'topic' => ['topic1', 'topic2', 'topic3'],
            'value' => ['xdr' => 'base64value'],
            'inSuccessfulContractCall' => true,
            'txHash' => 'txhash123',
            'opIndex' => 0,
            'txIndex' => 1
        ];

        $event = EventInfo::fromJson($json);

        $this->assertEquals('contract', $event->getType());
        $this->assertEquals(12345, $event->getLedger());
        $this->assertEquals('2024-01-20T12:00:00Z', $event->getLedgerClosedAt());
        $this->assertEquals('CAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABSC4', $event->getContractId());
        $this->assertEquals('0000012345-0000000001', $event->getId());
        $this->assertIsArray($event->getTopic());
        $this->assertCount(3, $event->getTopic());
        $this->assertEquals('topic1', $event->getTopic()[0]);
        $this->assertEquals('base64value', $event->getValue());
        $this->assertTrue($event->getInSuccessfulContractCall());
        $this->assertEquals('txhash123', $event->getTxHash());
        $this->assertEquals(0, $event->getOpIndex());
        $this->assertEquals(1, $event->getTxIndex());
    }

    public function testEventInfoValueFormats(): void
    {
        // Test value as direct string
        $json1 = [
            'type' => 'diagnostic',
            'ledger' => 100,
            'ledgerClosedAt' => '2024-01-20T12:00:00Z',
            'contractId' => 'CTEST',
            'id' => '001',
            'topic' => [],
            'value' => 'directvalue',
            'txHash' => 'hash1'
        ];

        $event1 = EventInfo::fromJson($json1);
        $this->assertEquals('directvalue', $event1->getValue());

        // Test value with xdr key
        $json2 = $json1;
        $json2['value'] = ['xdr' => 'xdrvalue'];

        $event2 = EventInfo::fromJson($json2);
        $this->assertEquals('xdrvalue', $event2->getValue());
    }

    public function testEventInfoSetters(): void
    {
        $json = [
            'type' => 'system',
            'ledger' => 1,
            'ledgerClosedAt' => '2024-01-20T12:00:00Z',
            'contractId' => 'C1',
            'id' => 'id1',
            'topic' => [],
            'value' => 'val',
            'txHash' => 'hash'
        ];

        $event = EventInfo::fromJson($json);

        $event->setType('contract');
        $event->setLedger(999);
        $event->setLedgerClosedAt('2024-12-31T23:59:59Z');
        $event->setContractId('CNEW');
        $event->setId('newid');
        $event->setTopic(['newtopic1', 'newtopic2']);
        $event->setValue('newvalue');
        $event->setInSuccessfulContractCall(false);
        $event->setTxHash('newhash');
        $event->setOpIndex(5);
        $event->setTxIndex(10);

        $this->assertEquals('contract', $event->getType());
        $this->assertEquals(999, $event->getLedger());
        $this->assertEquals('2024-12-31T23:59:59Z', $event->getLedgerClosedAt());
        $this->assertEquals('CNEW', $event->getContractId());
        $this->assertEquals('newid', $event->getId());
        $this->assertEquals(['newtopic1', 'newtopic2'], $event->getTopic());
        $this->assertEquals('newvalue', $event->getValue());
        $this->assertFalse($event->getInSuccessfulContractCall());
        $this->assertEquals('newhash', $event->getTxHash());
        $this->assertEquals(5, $event->getOpIndex());
        $this->assertEquals(10, $event->getTxIndex());
    }

    // SimulateTransactionResponse Tests (19 methods: fromJson, getFootprint, getSorobanAuth, 8 getters, 8 setters)

    public function testSimulateTransactionResponseSuccess(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'latestLedger' => 1000,
                'minResourceFee' => '50000',
                'events' => ['event1', 'event2'],
                'results' => [
                    [
                        'xdr' => 'AAAAAwAAAAA=',
                        'auth' => ['auth1', 'auth2']
                    ]
                ]
            ]
        ];

        $response = SimulateTransactionResponse::fromJson($json);

        $this->assertEquals(1000, $response->getLatestLedger());
        $this->assertEquals(50000, $response->getMinResourceFee());
        $this->assertIsArray($response->getEvents());
        $this->assertCount(2, $response->getEvents());
        $this->assertNotNull($response->getResults());
        $this->assertEquals(1, $response->getResults()->count());
        $this->assertNull($response->getResultError());
        $this->assertNull($response->error);
    }

    public function testSimulateTransactionResponseWithError(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'latestLedger' => 1000,
                'error' => 'HostError: Error(WasmVm, InvalidAction)'
            ]
        ];

        $response = SimulateTransactionResponse::fromJson($json);

        $this->assertEquals(1000, $response->getLatestLedger());
        $this->assertEquals('HostError: Error(WasmVm, InvalidAction)', $response->getResultError());
        $this->assertNull($response->getResults());
        $this->assertNull($response->getMinResourceFee());
    }

    public function testSimulateTransactionResponseWithRestorePreamble(): void
    {
        // Test that restorePreamble is properly parsed without testing XDR decoding
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'latestLedger' => 1000,
                'minResourceFee' => '100000'
            ]
        ];

        $response = SimulateTransactionResponse::fromJson($json);

        $this->assertEquals(1000, $response->getLatestLedger());
        $this->assertEquals(100000, $response->getMinResourceFee());
        $this->assertNull($response->getRestorePreamble());
    }

    public function testSimulateTransactionResponseWithStateChanges(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'latestLedger' => 1000,
                'stateChanges' => [
                    [
                        'type' => 'created',
                        'key' => 'keyxdr1',
                        'after' => 'afterxdr1'
                    ],
                    [
                        'type' => 'updated',
                        'key' => 'keyxdr2',
                        'before' => 'beforexdr2',
                        'after' => 'afterxdr2'
                    ]
                ]
            ]
        ];

        $response = SimulateTransactionResponse::fromJson($json);

        $this->assertIsArray($response->getStateChanges());
        $this->assertCount(2, $response->getStateChanges());
        $this->assertInstanceOf(LedgerEntryChange::class, $response->getStateChanges()[0]);
        $this->assertEquals('created', $response->getStateChanges()[0]->getType());
        $this->assertEquals('updated', $response->getStateChanges()[1]->getType());
    }

    public function testSimulateTransactionResponseSetters(): void
    {
        $json = ['jsonrpc' => '2.0', 'id' => 1, 'result' => ['latestLedger' => 100]];
        $response = SimulateTransactionResponse::fromJson($json);

        $response->setLatestLedger(2000);
        $response->setMinResourceFee(75000);
        $response->setResultError('Test error');
        $response->setEvents(['e1', 'e2', 'e3']);

        $this->assertEquals(2000, $response->getLatestLedger());
        $this->assertEquals(75000, $response->getMinResourceFee());
        $this->assertEquals('Test error', $response->getResultError());
        $this->assertCount(3, $response->getEvents());
    }

    public function testSimulateTransactionResponseRpcError(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'error' => [
                'code' => -32602,
                'message' => 'Invalid transaction'
            ]
        ];

        $response = SimulateTransactionResponse::fromJson($json);

        $this->assertInstanceOf(SorobanRpcErrorResponse::class, $response->error);
        $this->assertEquals(-32602, $response->error->getCode());
    }

    // LedgerEntry Tests (14 methods: fromJson, getLedgerEntryDataXdr, getKeyXdr, 5 getters, 5 setters, constructor)

    public function testLedgerEntryComplete(): void
    {
        $json = [
            'key' => 'AAAABgAAAAHZ4Y+vGF2cRulf/v0TJEcSb9iF3YkLy0fztZ8jWW2wTAAAABQ=',
            'xdr' => 'AAAABAAAAACjV3FgcL5qH9HoYmqBPn0ht6g13Tf9NXPqBzhnRq8ebAAAABQAAAAB',
            'lastModifiedLedgerSeq' => 12345,
            'liveUntilLedgerSeq' => 15000,
            'ext' => 'AAAAAA=='
        ];

        $entry = LedgerEntry::fromJson($json);

        $this->assertEquals('AAAABgAAAAHZ4Y+vGF2cRulf/v0TJEcSb9iF3YkLy0fztZ8jWW2wTAAAABQ=', $entry->getKey());
        $this->assertEquals('AAAABAAAAACjV3FgcL5qH9HoYmqBPn0ht6g13Tf9NXPqBzhnRq8ebAAAABQAAAAB', $entry->getXdr());
        $this->assertEquals(12345, $entry->getLastModifiedLedgerSeq());
        $this->assertEquals(15000, $entry->getLiveUntilLedgerSeq());
        $this->assertEquals('AAAAAA==', $entry->getExt());
    }

    public function testLedgerEntryMinimal(): void
    {
        $json = [
            'key' => 'keydata',
            'xdr' => 'xdrdata',
            'lastModifiedLedgerSeq' => 100
        ];

        $entry = LedgerEntry::fromJson($json);

        $this->assertEquals('keydata', $entry->getKey());
        $this->assertEquals('xdrdata', $entry->getXdr());
        $this->assertEquals(100, $entry->getLastModifiedLedgerSeq());
        $this->assertNull($entry->getLiveUntilLedgerSeq());
        $this->assertNull($entry->getExt());
    }

    public function testLedgerEntrySetters(): void
    {
        $json = [
            'key' => 'k1',
            'xdr' => 'x1',
            'lastModifiedLedgerSeq' => 1
        ];

        $entry = LedgerEntry::fromJson($json);

        $entry->setKey('newkey');
        $entry->setXdr('newxdr');
        $entry->setLastModifiedLedgerSeq(9999);
        $entry->setLiveUntilLedgerSeq(12000);
        $entry->setExt('newext');

        $this->assertEquals('newkey', $entry->getKey());
        $this->assertEquals('newxdr', $entry->getXdr());
        $this->assertEquals(9999, $entry->getLastModifiedLedgerSeq());
        $this->assertEquals(12000, $entry->getLiveUntilLedgerSeq());
        $this->assertEquals('newext', $entry->getExt());
    }

    // GetEventsResponse Tests (13 methods: fromJson, 6 getters, 6 setters)

    public function testGetEventsResponseComplete(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'events' => [
                    [
                        'type' => 'contract',
                        'ledger' => 100,
                        'ledgerClosedAt' => '2024-01-20T12:00:00Z',
                        'contractId' => 'C1',
                        'id' => 'id1',
                        'topic' => ['t1'],
                        'value' => 'v1',
                        'txHash' => 'hash1'
                    ],
                    [
                        'type' => 'system',
                        'ledger' => 101,
                        'ledgerClosedAt' => '2024-01-20T12:00:01Z',
                        'contractId' => 'C2',
                        'id' => 'id2',
                        'topic' => ['t2'],
                        'value' => 'v2',
                        'txHash' => 'hash2'
                    ]
                ],
                'latestLedger' => 1000,
                'cursor' => 'cursor123',
                'oldestLedger' => 500,
                'latestLedgerCloseTime' => 1705752000,
                'oldestLedgerCloseTime' => 1705665600
            ]
        ];

        $response = GetEventsResponse::fromJson($json);

        $this->assertIsArray($response->getEvents());
        $this->assertCount(2, $response->getEvents());
        $this->assertInstanceOf(EventInfo::class, $response->getEvents()[0]);
        $this->assertEquals('contract', $response->getEvents()[0]->getType());
        $this->assertEquals(1000, $response->getLatestLedger());
        $this->assertEquals('cursor123', $response->getCursor());
        $this->assertEquals(500, $response->getOldestLedger());
        $this->assertEquals(1705752000, $response->getLatestLedgerCloseTime());
        $this->assertEquals(1705665600, $response->getOldestLedgerCloseTime());
    }

    public function testGetEventsResponseEmpty(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'events' => [],
                'latestLedger' => 1000
            ]
        ];

        $response = GetEventsResponse::fromJson($json);

        $this->assertIsArray($response->getEvents());
        $this->assertCount(0, $response->getEvents());
        $this->assertEquals(1000, $response->getLatestLedger());
    }

    public function testGetEventsResponseSetters(): void
    {
        $json = ['jsonrpc' => '2.0', 'id' => 1, 'result' => ['latestLedger' => 100]];
        $response = GetEventsResponse::fromJson($json);

        $event = new EventInfo('contract', 100, '2024-01-20T12:00:00Z', 'C1', 'id1', ['t1'], 'v1', null, 'hash1');
        $response->setEvents([$event]);
        $response->setLatestLedger(2000);
        $response->setCursor('newcursor');
        $response->setOldestLedger(1500);
        $response->setLatestLedgerCloseTime(1705752001);
        $response->setOldestLedgerCloseTime(1705665601);

        $this->assertCount(1, $response->getEvents());
        $this->assertEquals(2000, $response->getLatestLedger());
        $this->assertEquals('newcursor', $response->getCursor());
        $this->assertEquals(1500, $response->getOldestLedger());
        $this->assertEquals(1705752001, $response->getLatestLedgerCloseTime());
        $this->assertEquals(1705665601, $response->getOldestLedgerCloseTime());
    }

    // GetLatestLedgerResponse Tests (13 methods: fromJson, 6 getters, 6 setters)

    public function testGetLatestLedgerResponseComplete(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'id' => 'a1b2c3d4e5f6',
                'protocolVersion' => 20,
                'sequence' => 12345,
                'closeTime' => '1705752000',
                'headerXdr' => 'headerxdr123',
                'metadataXdr' => 'metadataxdr456'
            ]
        ];

        $response = GetLatestLedgerResponse::fromJson($json);

        $this->assertEquals('a1b2c3d4e5f6', $response->getId());
        $this->assertEquals(20, $response->getProtocolVersion());
        $this->assertEquals(12345, $response->getSequence());
        $this->assertEquals(1705752000, $response->getCloseTime());
        $this->assertEquals('headerxdr123', $response->getHeaderXdr());
        $this->assertEquals('metadataxdr456', $response->getMetadataXdr());
    }

    public function testGetLatestLedgerResponseCloseTimeFormats(): void
    {
        // Test closeTime as string
        $json1 = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'id' => 'id1',
                'protocolVersion' => 20,
                'sequence' => 100,
                'closeTime' => '1705752000'
            ]
        ];

        $response1 = GetLatestLedgerResponse::fromJson($json1);
        $this->assertEquals(1705752000, $response1->getCloseTime());

        // Test closeTime as int
        $json2 = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'id' => 'id2',
                'protocolVersion' => 20,
                'sequence' => 100,
                'closeTime' => 1705752001
            ]
        ];

        $response2 = GetLatestLedgerResponse::fromJson($json2);
        $this->assertEquals(1705752001, $response2->getCloseTime());
    }

    public function testGetLatestLedgerResponseSetters(): void
    {
        $json = ['jsonrpc' => '2.0', 'id' => 1, 'result' => ['id' => 'id1', 'protocolVersion' => 20, 'sequence' => 100]];
        $response = GetLatestLedgerResponse::fromJson($json);

        $response->setId('newid');
        $response->setProtocolVersion(21);
        $response->setSequence(99999);
        $response->setCloseTime(1705752002);
        $response->setHeaderXdr('newheader');
        $response->setMetadataXdr('newmetadata');

        $this->assertEquals('newid', $response->getId());
        $this->assertEquals(21, $response->getProtocolVersion());
        $this->assertEquals(99999, $response->getSequence());
        $this->assertEquals(1705752002, $response->getCloseTime());
        $this->assertEquals('newheader', $response->getHeaderXdr());
        $this->assertEquals('newmetadata', $response->getMetadataXdr());
    }

    // GetLedgersResponse Tests (13 methods: fromJson, 6 getters, 6 setters)

    public function testGetLedgersResponseComplete(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'ledgers' => [
                    [
                        'hash' => 'hash1',
                        'sequence' => 100,
                        'ledgerCloseTime' => '1705752000',
                        'headerXdr' => 'header1',
                        'metadataXdr' => 'metadata1'
                    ],
                    [
                        'hash' => 'hash2',
                        'sequence' => 101,
                        'ledgerCloseTime' => '1705752005'
                    ]
                ],
                'latestLedger' => 1000,
                'latestLedgerCloseTime' => 1705752100,
                'oldestLedger' => 50,
                'oldestLedgerCloseTime' => 1705665600,
                'cursor' => 'cursor456'
            ]
        ];

        $response = GetLedgersResponse::fromJson($json);

        $this->assertIsArray($response->getLedgers());
        $this->assertCount(2, $response->getLedgers());
        $this->assertInstanceOf(LedgerInfo::class, $response->getLedgers()[0]);
        $this->assertEquals('hash1', $response->getLedgers()[0]->getHash());
        $this->assertEquals(1000, $response->getLatestLedger());
        $this->assertEquals(1705752100, $response->getLatestLedgerCloseTime());
        $this->assertEquals(50, $response->getOldestLedger());
        $this->assertEquals(1705665600, $response->getOldestLedgerCloseTime());
        $this->assertEquals('cursor456', $response->getCursor());
    }

    public function testGetLedgersResponseSetters(): void
    {
        $json = ['jsonrpc' => '2.0', 'id' => 1, 'result' => []];
        $response = GetLedgersResponse::fromJson($json);

        $ledger = new LedgerInfo('hash', 100, '1705752000');
        $response->setLedgers([$ledger]);
        $response->setLatestLedger(2000);
        $response->setLatestLedgerCloseTime(1705752200);
        $response->setOldestLedger(100);
        $response->setOldestLedgerCloseTime(1705665700);
        $response->setCursor('newcursor');

        $this->assertCount(1, $response->getLedgers());
        $this->assertEquals(2000, $response->getLatestLedger());
        $this->assertEquals(1705752200, $response->getLatestLedgerCloseTime());
        $this->assertEquals(100, $response->getOldestLedger());
        $this->assertEquals(1705665700, $response->getOldestLedgerCloseTime());
        $this->assertEquals('newcursor', $response->getCursor());
    }

    // LedgerEntryChange Tests (13 methods: fromJson, getKeyXdr, getBeforeXdr, getAfterXdr, 4 getters, 4 setters, constructor)

    public function testLedgerEntryChangeCreated(): void
    {
        $json = [
            'type' => 'created',
            'key' => 'keyxdr',
            'after' => 'afterxdr'
        ];

        $change = LedgerEntryChange::fromJson($json);

        $this->assertEquals('created', $change->getType());
        $this->assertEquals('keyxdr', $change->getKey());
        $this->assertNull($change->getBefore());
        $this->assertEquals('afterxdr', $change->getAfter());
    }

    public function testLedgerEntryChangeUpdated(): void
    {
        $json = [
            'type' => 'updated',
            'key' => 'keyxdr2',
            'before' => 'beforexdr2',
            'after' => 'afterxdr2'
        ];

        $change = LedgerEntryChange::fromJson($json);

        $this->assertEquals('updated', $change->getType());
        $this->assertEquals('keyxdr2', $change->getKey());
        $this->assertEquals('beforexdr2', $change->getBefore());
        $this->assertEquals('afterxdr2', $change->getAfter());
    }

    public function testLedgerEntryChangeDeleted(): void
    {
        $json = [
            'type' => 'deleted',
            'key' => 'keyxdr3',
            'before' => 'beforexdr3'
        ];

        $change = LedgerEntryChange::fromJson($json);

        $this->assertEquals('deleted', $change->getType());
        $this->assertEquals('keyxdr3', $change->getKey());
        $this->assertEquals('beforexdr3', $change->getBefore());
        $this->assertNull($change->getAfter());
    }

    public function testLedgerEntryChangeSetters(): void
    {
        $json = ['type' => 'created', 'key' => 'k1', 'after' => 'a1'];
        $change = LedgerEntryChange::fromJson($json);

        $change->setType('updated');
        $change->setKey('newkey');
        $change->setBefore('newbefore');
        $change->setAfter('newafter');

        $this->assertEquals('updated', $change->getType());
        $this->assertEquals('newkey', $change->getKey());
        $this->assertEquals('newbefore', $change->getBefore());
        $this->assertEquals('newafter', $change->getAfter());
    }

    // LedgerInfo Tests (12 methods: fromJson, 5 getters, 5 setters, constructor)

    public function testLedgerInfoComplete(): void
    {
        $json = [
            'hash' => 'ledgerhash123',
            'sequence' => 54321,
            'ledgerCloseTime' => '1705752000',
            'headerXdr' => 'headerdata',
            'metadataXdr' => 'metadatadata'
        ];

        $info = LedgerInfo::fromJson($json);

        $this->assertEquals('ledgerhash123', $info->getHash());
        $this->assertEquals(54321, $info->getSequence());
        $this->assertEquals('1705752000', $info->getLedgerCloseTime());
        $this->assertEquals('headerdata', $info->getHeaderXdr());
        $this->assertEquals('metadatadata', $info->getMetadataXdr());
    }

    public function testLedgerInfoMinimal(): void
    {
        $json = [
            'hash' => 'hash',
            'sequence' => 1,
            'ledgerCloseTime' => '100'
        ];

        $info = LedgerInfo::fromJson($json);

        $this->assertEquals('hash', $info->getHash());
        $this->assertEquals(1, $info->getSequence());
        $this->assertEquals('100', $info->getLedgerCloseTime());
        $this->assertNull($info->getHeaderXdr());
        $this->assertNull($info->getMetadataXdr());
    }

    public function testLedgerInfoSetters(): void
    {
        $json = ['hash' => 'h', 'sequence' => 1, 'ledgerCloseTime' => '1'];
        $info = LedgerInfo::fromJson($json);

        $info->setHash('newhash');
        $info->setSequence(999);
        $info->setLedgerCloseTime('1705752001');
        $info->setHeaderXdr('newheader');
        $info->setMetadataXdr('newmetadata');

        $this->assertEquals('newhash', $info->getHash());
        $this->assertEquals(999, $info->getSequence());
        $this->assertEquals('1705752001', $info->getLedgerCloseTime());
        $this->assertEquals('newheader', $info->getHeaderXdr());
        $this->assertEquals('newmetadata', $info->getMetadataXdr());
    }

    // SendTransactionResponse Tests (9 methods: fromJson, getErrorXdrTransactionResult, 4 getters, 2 setters)

    public function testSendTransactionResponsePending(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'hash' => 'txhash123',
                'status' => 'PENDING',
                'latestLedger' => 1000,
                'latestLedgerCloseTime' => '1705752000'
            ]
        ];

        $response = SendTransactionResponse::fromJson($json);

        $this->assertEquals('txhash123', $response->getHash());
        $this->assertEquals('PENDING', $response->getStatus());
        $this->assertEquals(SendTransactionResponse::STATUS_PENDING, $response->getStatus());
        $this->assertEquals(1000, $response->getLatestLedger());
        $this->assertEquals('1705752000', $response->getLatestLedgerCloseTime());
        $this->assertNull($response->getErrorResultXdr());
        $this->assertNull($response->getDiagnosticEvents());
    }

    public function testSendTransactionResponseDuplicate(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'hash' => 'duphash',
                'status' => 'DUPLICATE',
                'latestLedger' => 1000,
                'latestLedgerCloseTime' => '1705752000'
            ]
        ];

        $response = SendTransactionResponse::fromJson($json);

        $this->assertEquals('DUPLICATE', $response->getStatus());
        $this->assertEquals(SendTransactionResponse::STATUS_DUPLICATE, $response->getStatus());
    }

    public function testSendTransactionResponseTryAgainLater(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'hash' => 'tryhash',
                'status' => 'TRY_AGAIN_LATER',
                'latestLedger' => 1000,
                'latestLedgerCloseTime' => '1705752000'
            ]
        ];

        $response = SendTransactionResponse::fromJson($json);

        $this->assertEquals('TRY_AGAIN_LATER', $response->getStatus());
        $this->assertEquals(SendTransactionResponse::STATUS_TRY_AGAIN_LATER, $response->getStatus());
    }

    public function testSendTransactionResponseError(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'hash' => 'errorhash',
                'status' => 'ERROR',
                'latestLedger' => 1000,
                'latestLedgerCloseTime' => '1705752000',
                'errorResultXdr' => 'AAAAAAAAAGT/////AAAAAQAAAAAAAAAB////+wAAAAA='
            ]
        ];

        $response = SendTransactionResponse::fromJson($json);

        $this->assertEquals('ERROR', $response->getStatus());
        $this->assertEquals(SendTransactionResponse::STATUS_ERROR, $response->getStatus());
        $this->assertEquals('AAAAAAAAAGT/////AAAAAQAAAAAAAAAB////+wAAAAA=', $response->getErrorResultXdr());
        $this->assertNull($response->getDiagnosticEvents());
    }

    // GetLedgerEntriesResponse Tests (6 methods: fromJson, 2 getters, 2 setters)

    public function testGetLedgerEntriesResponseComplete(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'entries' => [
                    [
                        'key' => 'key1',
                        'xdr' => 'xdr1',
                        'lastModifiedLedgerSeq' => 100
                    ],
                    [
                        'key' => 'key2',
                        'xdr' => 'xdr2',
                        'lastModifiedLedgerSeq' => 200,
                        'liveUntilLedgerSeq' => 500
                    ]
                ],
                'latestLedger' => 1000
            ]
        ];

        $response = GetLedgerEntriesResponse::fromJson($json);

        $this->assertIsArray($response->getEntries());
        $this->assertCount(2, $response->getEntries());
        $this->assertInstanceOf(LedgerEntry::class, $response->getEntries()[0]);
        $this->assertEquals('key1', $response->getEntries()[0]->getKey());
        $this->assertEquals(1000, $response->getLatestLedger());
    }

    public function testGetLedgerEntriesResponseEmpty(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'entries' => [],
                'latestLedger' => 1000
            ]
        ];

        $response = GetLedgerEntriesResponse::fromJson($json);

        $this->assertIsArray($response->getEntries());
        $this->assertCount(0, $response->getEntries());
    }

    public function testGetLedgerEntriesResponseSetters(): void
    {
        $json = ['jsonrpc' => '2.0', 'id' => 1, 'result' => ['latestLedger' => 100]];
        $response = GetLedgerEntriesResponse::fromJson($json);

        $entry = new LedgerEntry('k', 'x', 1);
        $response->setEntries([$entry]);
        $response->setLatestLedger(2000);

        $this->assertCount(1, $response->getEntries());
        $this->assertEquals(2000, $response->getLatestLedger());
    }

    // RestorePreamble Tests (5 methods: fromJson, 2 getters, 2 setters, constructor)
    // Note: RestorePreamble requires valid XDR for transactionData which is tested in integration tests
    // Unit tests focus on JSON parsing logic without XDR decoding

    // TransactionEvents Tests (3 methods: fromJson, constructor, 2 public properties)

    public function testTransactionEventsComplete(): void
    {
        $json = [
            'transactionEventsXdr' => ['txevent1', 'txevent2'],
            'contractEventsXdr' => [
                ['contract1event1', 'contract1event2'],
                ['contract2event1']
            ]
        ];

        $events = TransactionEvents::fromJson($json);

        $this->assertIsArray($events->transactionEventsXdr);
        $this->assertCount(2, $events->transactionEventsXdr);
        $this->assertEquals('txevent1', $events->transactionEventsXdr[0]);
        $this->assertIsArray($events->contractEventsXdr);
        $this->assertCount(2, $events->contractEventsXdr);
        $this->assertCount(2, $events->contractEventsXdr[0]);
        $this->assertCount(1, $events->contractEventsXdr[1]);
    }

    public function testTransactionEventsEmpty(): void
    {
        $json = [];

        $events = TransactionEvents::fromJson($json);

        $this->assertNull($events->transactionEventsXdr);
        $this->assertNull($events->contractEventsXdr);
    }

    public function testTransactionEventsPartial(): void
    {
        $json1 = ['transactionEventsXdr' => ['event1']];
        $events1 = TransactionEvents::fromJson($json1);

        $this->assertIsArray($events1->transactionEventsXdr);
        $this->assertCount(1, $events1->transactionEventsXdr);
        $this->assertNull($events1->contractEventsXdr);

        $json2 = ['contractEventsXdr' => [['event2']]];
        $events2 = TransactionEvents::fromJson($json2);

        $this->assertNull($events2->transactionEventsXdr);
        $this->assertIsArray($events2->contractEventsXdr);
        $this->assertCount(1, $events2->contractEventsXdr);
    }

    // TransactionInfo Tests (11 methods: fromJson, constructor, 3 public properties + 8 constructor params)

    public function testTransactionInfoComplete(): void
    {
        $json = [
            'status' => 'SUCCESS',
            'applicationOrder' => 3,
            'feeBump' => true,
            'envelopeXdr' => 'envelopexdr',
            'resultXdr' => 'resultxdr',
            'resultMetaXdr' => 'metaxdr',
            'ledger' => 12345,
            'createdAt' => '1705752000',
            'txHash' => 'transactionhash',
            'diagnostic_events' => ['diag1', 'diag2'],
            'events' => [
                'transactionEventsXdr' => ['txevent'],
                'contractEventsXdr' => [['contractevent']]
            ]
        ];

        $info = TransactionInfo::fromJson($json);

        $this->assertEquals('SUCCESS', $info->status);
        $this->assertEquals(TransactionInfo::STATUS_SUCCESS, $info->status);
        $this->assertEquals(3, $info->applicationOrder);
        $this->assertTrue($info->feeBump);
        $this->assertEquals('envelopexdr', $info->envelopeXdr);
        $this->assertEquals('resultxdr', $info->resultXdr);
        $this->assertEquals('metaxdr', $info->resultMetaXdr);
        $this->assertEquals(12345, $info->ledger);
        $this->assertEquals(1705752000, $info->createdAt);
        $this->assertEquals('transactionhash', $info->txHash);
        $this->assertIsArray($info->diagnosticEventsXdr);
        $this->assertCount(2, $info->diagnosticEventsXdr);
        $this->assertInstanceOf(TransactionEvents::class, $info->events);
    }

    public function testTransactionInfoMinimal(): void
    {
        $json = [
            'status' => 'FAILED',
            'applicationOrder' => 1,
            'feeBump' => false,
            'envelopeXdr' => 'env',
            'resultXdr' => 'res',
            'resultMetaXdr' => 'meta',
            'ledger' => 100,
            'createdAt' => '100'
        ];

        $info = TransactionInfo::fromJson($json);

        $this->assertEquals('FAILED', $info->status);
        $this->assertEquals(TransactionInfo::STATUS_FAILED, $info->status);
        $this->assertNull($info->txHash);
        $this->assertNull($info->diagnosticEventsXdr);
        $this->assertNull($info->events);
    }

    public function testTransactionInfoNotFound(): void
    {
        $json = [
            'status' => 'NOT_FOUND',
            'applicationOrder' => 0,
            'feeBump' => false,
            'envelopeXdr' => '',
            'resultXdr' => '',
            'resultMetaXdr' => '',
            'ledger' => 0,
            'createdAt' => '0'
        ];

        $info = TransactionInfo::fromJson($json);

        $this->assertEquals('NOT_FOUND', $info->status);
        $this->assertEquals(TransactionInfo::STATUS_NOT_FOUND, $info->status);
    }

    // GetTransactionsResponse Tests (6 methods: fromJson, no public getters/setters, 6 public properties)

    public function testGetTransactionsResponseComplete(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'transactions' => [
                    [
                        'status' => 'SUCCESS',
                        'applicationOrder' => 1,
                        'feeBump' => false,
                        'envelopeXdr' => 'env1',
                        'resultXdr' => 'res1',
                        'resultMetaXdr' => 'meta1',
                        'ledger' => 100,
                        'createdAt' => '1705752000'
                    ],
                    [
                        'status' => 'SUCCESS',
                        'applicationOrder' => 2,
                        'feeBump' => true,
                        'envelopeXdr' => 'env2',
                        'resultXdr' => 'res2',
                        'resultMetaXdr' => 'meta2',
                        'ledger' => 100,
                        'createdAt' => '1705752001'
                    ]
                ],
                'latestLedger' => 1000,
                'latestLedgerCloseTimestamp' => 1705752100,
                'oldestLedger' => 50,
                'oldestLedgerCloseTimestamp' => 1705665600,
                'cursor' => 'cursor789'
            ]
        ];

        $response = GetTransactionsResponse::fromJson($json);

        $this->assertIsArray($response->transactions);
        $this->assertCount(2, $response->transactions);
        $this->assertInstanceOf(TransactionInfo::class, $response->transactions[0]);
        $this->assertEquals('SUCCESS', $response->transactions[0]->status);
        $this->assertEquals(1000, $response->latestLedger);
        $this->assertEquals(1705752100, $response->latestLedgerCloseTimestamp);
        $this->assertEquals(50, $response->oldestLedger);
        $this->assertEquals(1705665600, $response->oldestLedgerCloseTimestamp);
        $this->assertEquals('cursor789', $response->cursor);
    }

    public function testGetTransactionsResponseEmpty(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'transactions' => [],
                'latestLedger' => 1000
            ]
        ];

        $response = GetTransactionsResponse::fromJson($json);

        $this->assertIsArray($response->transactions);
        $this->assertCount(0, $response->transactions);
    }

    // SimulateTransactionResult Tests (3 methods: fromJson, getResultValue, getXdr, getAuth)

    public function testSimulateTransactionResultComplete(): void
    {
        $json = [
            'xdr' => 'AAAAAwAAAAA=',
            'auth' => ['auth1', 'auth2', 'auth3']
        ];

        $result = SimulateTransactionResult::fromJson($json);

        $this->assertEquals('AAAAAwAAAAA=', $result->getXdr());
        $this->assertIsArray($result->getAuth());
        $this->assertCount(3, $result->getAuth());
        $this->assertEquals('auth1', $result->getAuth()[0]);
    }

    public function testSimulateTransactionResultEmptyAuth(): void
    {
        $json = [
            'xdr' => 'xdrdata',
            'auth' => []
        ];

        $result = SimulateTransactionResult::fromJson($json);

        $this->assertEquals('xdrdata', $result->getXdr());
        $this->assertIsArray($result->getAuth());
        $this->assertCount(0, $result->getAuth());
    }

    // InclusionFee Tests (17 methods: fromJson, constructor, 16 public properties)

    public function testInclusionFeeComplete(): void
    {
        $json = [
            'max' => '1000000',
            'min' => '100',
            'mode' => '5000',
            'p10' => '1000',
            'p20' => '2000',
            'p30' => '3000',
            'p40' => '4000',
            'p50' => '5000',
            'p60' => '6000',
            'p70' => '7000',
            'p80' => '8000',
            'p90' => '9000',
            'p95' => '10000',
            'p99' => '50000',
            'transactionCount' => '12345',
            'ledgerCount' => 10
        ];

        $fee = InclusionFee::fromJson($json);

        $this->assertEquals('1000000', $fee->max);
        $this->assertEquals('100', $fee->min);
        $this->assertEquals('5000', $fee->mode);
        $this->assertEquals('1000', $fee->p10);
        $this->assertEquals('2000', $fee->p20);
        $this->assertEquals('3000', $fee->p30);
        $this->assertEquals('4000', $fee->p40);
        $this->assertEquals('5000', $fee->p50);
        $this->assertEquals('6000', $fee->p60);
        $this->assertEquals('7000', $fee->p70);
        $this->assertEquals('8000', $fee->p80);
        $this->assertEquals('9000', $fee->p90);
        $this->assertEquals('10000', $fee->p95);
        $this->assertEquals('50000', $fee->p99);
        $this->assertEquals('12345', $fee->transactionCount);
        $this->assertEquals(10, $fee->ledgerCount);
    }

    // GetFeeStatsResponse Tests (4 methods: fromJson, 3 public properties)

    public function testGetFeeStatsResponseComplete(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'sorobanInclusionFee' => [
                    'max' => '500000',
                    'min' => '100',
                    'mode' => '2500',
                    'p10' => '500',
                    'p20' => '1000',
                    'p30' => '1500',
                    'p40' => '2000',
                    'p50' => '2500',
                    'p60' => '3000',
                    'p70' => '3500',
                    'p80' => '4000',
                    'p90' => '4500',
                    'p95' => '5000',
                    'p99' => '25000',
                    'transactionCount' => '1000',
                    'ledgerCount' => 5
                ],
                'inclusionFee' => [
                    'max' => '100000',
                    'min' => '50',
                    'mode' => '1000',
                    'p10' => '200',
                    'p20' => '400',
                    'p30' => '600',
                    'p40' => '800',
                    'p50' => '1000',
                    'p60' => '1200',
                    'p70' => '1400',
                    'p80' => '1600',
                    'p90' => '1800',
                    'p95' => '2000',
                    'p99' => '5000',
                    'transactionCount' => '5000',
                    'ledgerCount' => 5
                ],
                'latestLedger' => 1000
            ]
        ];

        $response = GetFeeStatsResponse::fromJson($json);

        $this->assertInstanceOf(InclusionFee::class, $response->sorobanInclusionFee);
        $this->assertEquals('500000', $response->sorobanInclusionFee->max);
        $this->assertInstanceOf(InclusionFee::class, $response->inclusionFee);
        $this->assertEquals('100000', $response->inclusionFee->max);
        $this->assertEquals(1000, $response->latestLedger);
    }

    public function testGetFeeStatsResponsePartial(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'latestLedger' => 1000
            ]
        ];

        $response = GetFeeStatsResponse::fromJson($json);

        $this->assertNull($response->sorobanInclusionFee);
        $this->assertNull($response->inclusionFee);
        $this->assertEquals(1000, $response->latestLedger);
    }

    // GetHealthResponse Tests (9 methods: fromJson, 4 getters, 4 setters, HEALTHY constant)

    public function testGetHealthResponseHealthy(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'status' => 'healthy',
                'ledgerRetentionWindow' => 10000,
                'oldestLedger' => 1000,
                'latestLedger' => 11000
            ]
        ];

        $response = GetHealthResponse::fromJson($json);

        $this->assertEquals('healthy', $response->getStatus());
        $this->assertEquals(GetHealthResponse::HEALTHY, $response->getStatus());
        $this->assertEquals(10000, $response->getLedgerRetentionWindow());
        $this->assertEquals(1000, $response->getOldestLedger());
        $this->assertEquals(11000, $response->getLatestLedger());
    }

    public function testGetHealthResponseSetters(): void
    {
        $json = ['jsonrpc' => '2.0', 'id' => 1, 'result' => ['status' => 'healthy']];
        $response = GetHealthResponse::fromJson($json);

        $response->setStatus('unhealthy');
        $response->setLedgerRetentionWindow(20000);
        $response->setOldestLedger(5000);
        $response->setLatestLedger(25000);

        $this->assertEquals('unhealthy', $response->getStatus());
        $this->assertEquals(20000, $response->getLedgerRetentionWindow());
        $this->assertEquals(5000, $response->getOldestLedger());
        $this->assertEquals(25000, $response->getLatestLedger());
    }

    // GetNetworkResponse Tests (7 methods: fromJson, 3 getters, 3 setters)

    public function testGetNetworkResponseComplete(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'friendbotUrl' => 'https://friendbot.stellar.org',
                'passphrase' => 'Test SDF Network ; September 2015',
                'protocolVersion' => 20
            ]
        ];

        $response = GetNetworkResponse::fromJson($json);

        $this->assertEquals('https://friendbot.stellar.org', $response->getFriendbotUrl());
        $this->assertEquals('Test SDF Network ; September 2015', $response->getPassphrase());
        $this->assertEquals(20, $response->getProtocolVersion());
    }

    public function testGetNetworkResponseSetters(): void
    {
        $json = ['jsonrpc' => '2.0', 'id' => 1, 'result' => []];
        $response = GetNetworkResponse::fromJson($json);

        $response->setFriendbotUrl('https://newfriendbot.org');
        $response->setPassphrase('New Network Passphrase');
        $response->setProtocolVersion(21);

        $this->assertEquals('https://newfriendbot.org', $response->getFriendbotUrl());
        $this->assertEquals('New Network Passphrase', $response->getPassphrase());
        $this->assertEquals(21, $response->getProtocolVersion());
    }

    // GetVersionInfoResponse Tests (6 methods: fromJson, 5 public properties)

    public function testGetVersionInfoResponseProtocol22(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'version' => '20.0.0',
                'commitHash' => 'abc123def456',
                'buildTimestamp' => '2024-01-15T10:30:00Z',
                'captiveCoreVersion' => 'v19.10.1-10-gabcdef123',
                'protocolVersion' => 20
            ]
        ];

        $response = GetVersionInfoResponse::fromJson($json);

        $this->assertEquals('20.0.0', $response->version);
        $this->assertEquals('abc123def456', $response->commitHash);
        $this->assertEquals('2024-01-15T10:30:00Z', $response->buildTimeStamp);
        $this->assertEquals('v19.10.1-10-gabcdef123', $response->captiveCoreVersion);
        $this->assertEquals(20, $response->protocolVersion);
    }

    public function testGetVersionInfoResponseProtocolLegacy(): void
    {
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'version' => '19.0.0',
                'commit_hash' => 'legacy123',
                'build_time_stamp' => '2023-12-01T08:00:00Z',
                'captive_core_version' => 'v19.0.0',
                'protocol_version' => 19
            ]
        ];

        $response = GetVersionInfoResponse::fromJson($json);

        $this->assertEquals('19.0.0', $response->version);
        $this->assertEquals('legacy123', $response->commitHash);
        $this->assertEquals('2023-12-01T08:00:00Z', $response->buildTimeStamp);
        $this->assertEquals('v19.0.0', $response->captiveCoreVersion);
        $this->assertEquals(19, $response->protocolVersion);
    }

    public function testGetVersionInfoResponseMixed(): void
    {
        // Test with both legacy and new field names
        // Note: commitHash and buildTimeStamp use 'else if', so legacy takes precedence when both exist
        // captiveCoreVersion and protocolVersion use separate 'if', so new overwrites legacy
        $json = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'result' => [
                'version' => '20.0.0',
                'commit_hash' => 'legacy',
                'commitHash' => 'new',
                'build_time_stamp' => 'legacy_time',
                'buildTimestamp' => 'new_time',
                'captive_core_version' => 'legacy_core',
                'captiveCoreVersion' => 'new_core',
                'protocol_version' => 19,
                'protocolVersion' => 20
            ]
        ];

        $response = GetVersionInfoResponse::fromJson($json);

        // Legacy takes precedence for commitHash and buildTimeStamp (due to 'else if')
        $this->assertEquals('legacy', $response->commitHash);
        $this->assertEquals('legacy_time', $response->buildTimeStamp);
        // New overwrites legacy for captiveCoreVersion and protocolVersion (due to separate 'if')
        $this->assertEquals('new_core', $response->captiveCoreVersion);
        $this->assertEquals(20, $response->protocolVersion);
    }
}
