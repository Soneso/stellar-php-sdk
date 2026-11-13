<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Soroban\Responses\ContractErrorProvenance;
use Soneso\StellarSDK\Soroban\Responses\GetTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SendTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResult;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResults;
use Soneso\StellarSDK\Soroban\Responses\SorobanRpcResponse;
use Soneso\StellarSDK\Soroban\Responses\SorobanRpcErrorResponse;
use Soneso\StellarSDK\Soroban\Responses\TransactionEvents;
use Soneso\StellarSDK\Soroban\Responses\SimulateTransactionResponse;
use Soneso\StellarSDK\Soroban\Responses\LedgerEntryChange;
use Soneso\StellarSDK\Soroban\Responses\ReportedContractError;
use Soneso\StellarSDK\Soroban\Responses\RestorePreamble;
use Soneso\StellarSDK\Xdr\XdrContractEventType;
use Soneso\StellarSDK\Xdr\XdrSCError;
use Soneso\StellarSDK\Xdr\XdrSCErrorCode;
use Soneso\StellarSDK\Xdr\XdrSCErrorType;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrDiagnosticEvent;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;

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

    // SimulateTransactionResponse tests

    public function testSimulateTransactionResponseFromJsonSuccess(): void
    {
        $json = [
            'result' => [
                'latestLedger' => 123456,
                'minResourceFee' => '100000',
                'results' => [
                    ['xdr' => 'AAAAEAAAAAEAAAAPAAAADkhlbGxvLCBXb3JsZCEAAA==', 'auth' => []]
                ],
                'events' => ['event1_base64', 'event2_base64']
            ]
        ];

        $response = SimulateTransactionResponse::fromJson($json);

        $this->assertEquals(123456, $response->getLatestLedger());
        $this->assertEquals(100000, $response->getMinResourceFee());
        $this->assertNotNull($response->getResults());
        $this->assertEquals(1, $response->getResults()->count());
        $this->assertIsArray($response->getEvents());
        $this->assertCount(2, $response->getEvents());
        $this->assertNull($response->getResultError());
    }

    public function testSimulateTransactionResponseFromJsonWithError(): void
    {
        $json = [
            'result' => [
                'error' => 'HostError: something went wrong',
                'latestLedger' => 123456
            ]
        ];

        $response = SimulateTransactionResponse::fromJson($json);

        $this->assertEquals(123456, $response->getLatestLedger());
        $this->assertEquals('HostError: something went wrong', $response->getResultError());
        $this->assertNull($response->getResults());
    }

    /** @dataProvider contractErrorHeaders */
    public function testReportedContractErrorUsesTheLeadingHeader(?string $header, ?array $events, ?array $expected): void
    {
        $response = SimulateTransactionResponse::fromJson(['result' => ['error' => $header, 'events' => $events]]);
        try {
            $reported = $response->reportedContractError();
        } catch (\Throwable $error) {
            $this->fail('Contract error extraction must not throw: ' . $error->getMessage());
        }
        SimulationFailureFixtures::assertReport($expected, $reported);
        $this->assertSame($header, $response->resultError);
        $this->assertSame($events, $response->events);
    }

    public static function contractErrorHeaders(): iterable
    {
        $event = SimulationFailureFixtures::capture('case-a')['result']['events'][6];
        $host = ContractErrorProvenance::HOST_ERROR;
        $diagnostic = [3201, ContractErrorProvenance::DIAGNOSTIC_EVENTS, SimulationFailureFixtures::POLICY];
        $captured = SimulationFailureFixtures::capture('case-c2-sac-negative-transfer')['result']['error'];
        foreach (['captured header' => $captured, 'whitespace' => " \tHostError:  Error( Contract , #0008 ) \t",
                  'optional prefix' => 'Error(Contract, #8)', 'CR' => $captured . "\rtail",
                  'LF' => $captured . "\ntail", 'CRLF' => $captured . "\r\ntail"] as $label => $header) {
            yield $label => [$header, [$event], [8, $host, null]];
        }
        foreach (['absent error' => null, 'empty error' => '', 'arbitrary text' => 'arbitrary text',
                  'storage' => 'Error(Storage, MissingValue)', 'vm' => 'Error(Vm, InvalidAction)',
                  'space before parenthesis' => 'Error (Contract, #8)', 'trailing junk' => 'Error(Contract, #8) junk',
                  'leading blank line' => "\n" . $captured] as $label => $header) {
            yield $label => [$header, [$event], null];
        }
        yield 'context' => [SimulationFailureFixtures::CONTEXT, [$event], $diagnostic];
        yield 'auth' => [SimulationFailureFixtures::AUTH, [$event], $diagnostic];
        yield 'absent events' => [SimulationFailureFixtures::CONTEXT, null, null];
        yield 'empty events' => [SimulationFailureFixtures::AUTH, [], null];
        yield 'text log only' => [SimulationFailureFixtures::AUTH . "\nEvent log: Error(Contract, #3016)", null, null];
        foreach (['zero' => ['#0', 0], 'maximum' => ['#4294967295', 4294967295],
                  'padded maximum' => ['#00004294967295', 4294967295]] as $label => [$text, $code]) {
            yield $label => ["Error(Contract, $text)", [$event], [$code, $host, null]];
        }
        foreach (['negative' => '#-1', 'plus sign' => '#+1', 'overflow' => '#4294967296',
                  'long overflow' => '#99999999999999999999', 'missing digits' => '#', 'fraction' => '#1.0'] as $label => $text) {
            yield $label => ["Error(Contract, $text)", [$event], null];
        }
    }

    /** @dataProvider contractErrorEvents */
    public function testReportedContractErrorSelectsTheEarliestQualifyingEvent(array $events, ?array $expected): void
    {
        $response = SimulateTransactionResponse::fromJson(['result' => ['error' => SimulationFailureFixtures::CONTEXT, 'events' => $events]]);
        try {
            $reported = $response->reportedContractError();
        } catch (\Throwable $error) {
            $this->fail('Contract error extraction must not throw: ' . $error->getMessage());
        }
        SimulationFailureFixtures::assertReport($expected, $reported);
        $this->assertSame(SimulationFailureFixtures::CONTEXT, $response->resultError);
        $this->assertSame($events, $response->events);
    }

    public static function contractErrorEvents(): iterable
    {
        $symbol = XdrSCVal::forSymbol('error');
        $error = SimulationFailureFixtures::contractError(3201);
        $crypto = new XdrSCError(XdrSCErrorType::SCE_CRYPTO());
        $crypto->code = XdrSCErrorCode::SCEC_INVALID_INPUT();
        foreach (['invalid base64' => '%%%', 'truncated XDR' => 'AAAA',
                  'unknown body' => SimulationFailureFixtures::event(bodyVersion: 1),
                  'contract event' => SimulationFailureFixtures::event(type: XdrContractEventType::CONTRACT_EVENT_TYPE_CONTRACT),
                  'no topics' => SimulationFailureFixtures::event(topics: []),
                  'one topic' => SimulationFailureFixtures::event(topics: [$symbol]),
                  'three topics' => SimulationFailureFixtures::event(topics: [$symbol, $error, $error]),
                  'string topic' => SimulationFailureFixtures::event(topics: [XdrSCVal::forString('error'), $error]),
                  'other symbol' => SimulationFailureFixtures::event(topics: [XdrSCVal::forSymbol('other'), $error]),
                  'integer topic' => SimulationFailureFixtures::event(topics: [$symbol, XdrSCVal::forU32(3201)]),
                  'crypto error' => SimulationFailureFixtures::event(topics: [$symbol, XdrSCVal::forError($crypto)]),
                  'data only' => SimulationFailureFixtures::event(topics: [], data: $error)] as $label => $event) {
            yield $label => [[$event], null];
        }
        $policy = SimulationFailureFixtures::event();
        $verifier = SimulationFailureFixtures::event(3114, SimulationFailureFixtures::VERIFIER);
        $provenance = ContractErrorProvenance::DIAGNOSTIC_EVENTS;
        $expectedPolicy = [3201, $provenance, SimulationFailureFixtures::POLICY];
        yield 'qualifying event' => [[$policy], $expectedPolicy];
        yield 'successful call first' => [[SimulationFailureFixtures::event(3114, SimulationFailureFixtures::VERIFIER, true), $policy], $expectedPolicy];
        yield 'absent contract ID' => [[SimulationFailureFixtures::event(id: null)], [3201, $provenance, null]];
        yield 'verifier first' => [[$verifier, $policy, $verifier], [3114, $provenance, SimulationFailureFixtures::VERIFIER]];
        yield 'policy first' => [[$policy, $verifier, $verifier], $expectedPolicy];
        yield 'zero' => [[SimulationFailureFixtures::event(0)], [0, $provenance, SimulationFailureFixtures::POLICY]];
        yield 'maximum' => [[SimulationFailureFixtures::event(4294967295)], [4294967295, $provenance, SimulationFailureFixtures::POLICY]];
    }

    /** @dataProvider reportedContractErrorCodes */
    public function testReportedContractErrorEnforcesUnsignedBounds(int $code, ?string $message): void
    {
        if ($message !== null) {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessageMatches('/\A' . preg_quote($message, '/') . '\z/');
        }
        $value = new ReportedContractError($code, ContractErrorProvenance::HOST_ERROR);
        SimulationFailureFixtures::assertReport([$code, ContractErrorProvenance::HOST_ERROR, null], $value);
    }

    public static function reportedContractErrorCodes(): array
    {
        return ['zero' => [0, null], 'maximum' => [4294967295, null],
            'negative' => [-1, 'Contract error code -1 must be between 0 and 4294967295'],
            'overflow' => [4294967296, 'Contract error code 4294967296 must be between 0 and 4294967295']];
    }

    public function testSimulateTransactionResponseFromJsonWithRpcError(): void
    {
        $json = [
            'error' => [
                'code' => -32602,
                'message' => 'Invalid params'
            ]
        ];

        $response = SimulateTransactionResponse::fromJson($json);

        $this->assertNotNull($response->error);
        $this->assertEquals(-32602, $response->error->getCode());
        $this->assertEquals('Invalid params', $response->error->getMessage());
    }

    public function testSimulateTransactionResponseSetters(): void
    {
        $response = SimulateTransactionResponse::fromJson(['result' => ['latestLedger' => 100]]);

        $response->setLatestLedger(200);
        $this->assertEquals(200, $response->getLatestLedger());

        $response->setMinResourceFee(50000);
        $this->assertEquals(50000, $response->getMinResourceFee());

        $response->setResultError('Some error');
        $this->assertEquals('Some error', $response->getResultError());

        $events = ['evt1', 'evt2'];
        $response->setEvents($events);
        $this->assertEquals($events, $response->getEvents());

        $results = new SimulateTransactionResults();
        $response->setResults($results);
        $this->assertSame($results, $response->getResults());

        $response->setTransactionData(null);
        $this->assertNull($response->getTransactionData());

        $response->setRestorePreamble(null);
        $this->assertNull($response->getRestorePreamble());

        $response->setStateChanges(null);
        $this->assertNull($response->getStateChanges());
    }

    public function testSimulateTransactionResponseGetFootprintNull(): void
    {
        $json = [
            'result' => [
                'latestLedger' => 123456
            ]
        ];

        $response = SimulateTransactionResponse::fromJson($json);
        $footprint = $response->getFootprint();

        $this->assertNull($footprint);
    }

    public function testSimulateTransactionResponseGetSorobanAuthNull(): void
    {
        $json = [
            'result' => [
                'latestLedger' => 123456
            ]
        ];

        $response = SimulateTransactionResponse::fromJson($json);
        $auth = $response->getSorobanAuth();

        $this->assertNull($auth);
    }

    public function testSimulateTransactionResponseGetSorobanAuthEmptyResults(): void
    {
        $json = [
            'result' => [
                'latestLedger' => 123456,
                'results' => []
            ]
        ];

        $response = SimulateTransactionResponse::fromJson($json);
        $auth = $response->getSorobanAuth();

        $this->assertNull($auth);
    }

    public function testSimulateTransactionResponseWithStateChanges(): void
    {
        $json = [
            'result' => [
                'latestLedger' => 123456,
                'stateChanges' => [
                    [
                        'type' => 'created',
                        'key' => 'AAAABgAAAAE=',
                        'after' => 'AAAABgAAAAE='
                    ],
                    [
                        'type' => 'updated',
                        'key' => 'AAAABgAAAAI=',
                        'before' => 'AAAABgAAAAI=',
                        'after' => 'AAAABgAAAAM='
                    ]
                ]
            ]
        ];

        $response = SimulateTransactionResponse::fromJson($json);

        $this->assertNotNull($response->getStateChanges());
        $this->assertCount(2, $response->getStateChanges());
        $this->assertInstanceOf(LedgerEntryChange::class, $response->getStateChanges()[0]);
    }

    public function testGetTransactionResponseConstants(): void
    {
        $this->assertEquals('SUCCESS', GetTransactionResponse::STATUS_SUCCESS);
        $this->assertEquals('NOT_FOUND', GetTransactionResponse::STATUS_NOT_FOUND);
        $this->assertEquals('FAILED', GetTransactionResponse::STATUS_FAILED);
    }

    public function testGetTransactionResponseGetResultValueNullOnError(): void
    {
        $json = [
            'error' => [
                'code' => -32600,
                'message' => 'Error'
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);
        $resultValue = $response->getResultValue();

        $this->assertNull($resultValue);
    }

    public function testGetTransactionResponseGetResultValueNullOnNotSuccess(): void
    {
        $json = [
            'result' => [
                'status' => 'NOT_FOUND',
                'latestLedger' => 123456
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);
        $resultValue = $response->getResultValue();

        $this->assertNull($resultValue);
    }

    public function testGetTransactionResponseGetResultValueNullOnNoMeta(): void
    {
        $json = [
            'result' => [
                'status' => 'SUCCESS',
                'latestLedger' => 123456
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);
        $resultValue = $response->getResultValue();

        $this->assertNull($resultValue);
    }

    public function testGetTransactionResponseGetCreatedContractIdNull(): void
    {
        $json = [
            'result' => [
                'status' => 'SUCCESS',
                'latestLedger' => 123456
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);
        $contractId = $response->getCreatedContractId();

        $this->assertNull($contractId);
    }

    public function testGetTransactionResponseGetWasmIdNull(): void
    {
        $json = [
            'result' => [
                'status' => 'SUCCESS',
                'latestLedger' => 123456
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);
        $wasmId = $response->getWasmId();

        $this->assertNull($wasmId);
    }

    public function testGetTransactionResponseGetXdrTransactionResultNull(): void
    {
        $json = [
            'result' => [
                'status' => 'SUCCESS'
            ]
        ];

        $response = GetTransactionResponse::fromJson($json);
        $xdrResult = $response->getXdrTransactionResult();

        $this->assertNull($xdrResult);
    }

    public function testSimulateTransactionResultFromJson(): void
    {
        $json = [
            'xdr' => 'AAAAEAAAAAEAAAAPAAAADkhlbGxvLCBXb3JsZCEAAA==',
            'auth' => ['auth_entry_1', 'auth_entry_2']
        ];

        $result = SimulateTransactionResult::fromJson($json);

        $this->assertEquals('AAAAEAAAAAEAAAAPAAAADkhlbGxvLCBXb3JsZCEAAA==', $result->xdr);
        $this->assertIsArray($result->auth);
        $this->assertCount(2, $result->auth);
    }

    public function testSimulateTransactionResultWithEmptyAuth(): void
    {
        $json = [
            'xdr' => 'AAAAEAAAAAEAAAAPAAAADkhlbGxvLCBXb3JsZCEAAA==',
            'auth' => []
        ];

        $result = SimulateTransactionResult::fromJson($json);

        $this->assertEquals('AAAAEAAAAAEAAAAPAAAADkhlbGxvLCBXb3JsZCEAAA==', $result->xdr);
        $this->assertEmpty($result->auth);
        $this->assertIsArray($result->auth);
    }

    public function testSimulateTransactionResultGetResultValue(): void
    {
        // A valid base64 XDR SCVal encoding for a simple bool true value
        // SCVal type 0 (SCV_BOOL) with value true = AAAAAAAAAAE=
        $json = [
            'xdr' => 'AAAAAAAAAAE=',
            'auth' => []
        ];

        $result = SimulateTransactionResult::fromJson($json);
        $scVal = $result->getResultValue();

        $this->assertInstanceOf(XdrSCVal::class, $scVal);
    }

    public function testSimulateTransactionResultGetters(): void
    {
        $json = [
            'xdr' => 'AAAAEAAAAAEAAAAPAAAADkhlbGxvLCBXb3JsZCEAAA==',
            'auth' => ['auth1', 'auth2']
        ];

        $result = SimulateTransactionResult::fromJson($json);

        $this->assertEquals('AAAAEAAAAAEAAAAPAAAADkhlbGxvLCBXb3JsZCEAAA==', $result->getXdr());
        $this->assertEquals(['auth1', 'auth2'], $result->getAuth());
    }
}
