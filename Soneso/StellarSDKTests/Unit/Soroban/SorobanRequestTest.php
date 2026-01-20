<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Soroban;

use PHPUnit\Framework\TestCase;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Soroban\Requests\EventFilter;
use Soneso\StellarSDK\Soroban\Requests\EventFilters;
use Soneso\StellarSDK\Soroban\Requests\GetEventsRequest;
use Soneso\StellarSDK\Soroban\Requests\GetLedgersRequest;
use Soneso\StellarSDK\Soroban\Requests\GetTransactionsRequest;
use Soneso\StellarSDK\Soroban\Requests\PaginationOptions;
use Soneso\StellarSDK\Soroban\Requests\ResourceConfig;
use Soneso\StellarSDK\Soroban\Requests\SimulateTransactionRequest;
use Soneso\StellarSDK\Soroban\Requests\TopicFilter;
use Soneso\StellarSDK\Soroban\Requests\TopicFilters;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\InvokeContractHostFunction;
use Soneso\StellarSDK\InvokeHostFunctionOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SorobanServer;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Xdr\XdrSCVal;

class SorobanRequestTest extends TestCase
{
    public function setUp(): void
    {
        // Turn on error reporting
        error_reporting(E_ALL);
    }

    // =====================================================
    // TopicFilter Tests (3 methods)
    // =====================================================

    public function testTopicFilterConstructor(): void
    {
        $segmentMatchers = ["*", "segment2"];
        $topicFilter = new TopicFilter($segmentMatchers);

        $this->assertSame($segmentMatchers, $topicFilter->segmentMatchers);
        $this->assertSame($segmentMatchers, $topicFilter->getSegmentMatchers());
    }

    public function testTopicFilterGetRequestParams(): void
    {
        $segmentMatchers = ["*", XdrSCVal::forSymbol("transfer")->toBase64Xdr()];
        $topicFilter = new TopicFilter($segmentMatchers);

        $params = $topicFilter->getRequestParams();

        $this->assertIsArray($params);
        $this->assertEquals($segmentMatchers, $params);
    }

    public function testTopicFilterWithWildcards(): void
    {
        $segmentMatchers = ["*", "*", "specific_segment"];
        $topicFilter = new TopicFilter($segmentMatchers);

        $params = $topicFilter->getRequestParams();

        $this->assertCount(3, $params);
        $this->assertEquals("*", $params[0]);
        $this->assertEquals("*", $params[1]);
        $this->assertEquals("specific_segment", $params[2]);
    }

    // =====================================================
    // TopicFilters Tests (5 methods)
    // =====================================================

    public function testTopicFiltersConstructor(): void
    {
        $filter1 = new TopicFilter(["*", "segment1"]);
        $filter2 = new TopicFilter(["segment2", "*"]);

        $topicFilters = new TopicFilters($filter1, $filter2);

        $this->assertEquals(2, $topicFilters->count());
    }

    public function testTopicFiltersCurrent(): void
    {
        $filter1 = new TopicFilter(["*", "segment1"]);
        $filter2 = new TopicFilter(["segment2", "*"]);

        $topicFilters = new TopicFilters($filter1, $filter2);
        $topicFilters->rewind();

        $current = $topicFilters->current();
        $this->assertInstanceOf(TopicFilter::class, $current);
        $this->assertEquals($filter1, $current);
    }

    public function testTopicFiltersAdd(): void
    {
        $filter1 = new TopicFilter(["*", "segment1"]);
        $topicFilters = new TopicFilters($filter1);

        $this->assertEquals(1, $topicFilters->count());

        $filter2 = new TopicFilter(["segment2", "*"]);
        $topicFilters->add($filter2);

        $this->assertEquals(2, $topicFilters->count());
    }

    public function testTopicFiltersCount(): void
    {
        $filter1 = new TopicFilter(["*"]);
        $filter2 = new TopicFilter(["segment1"]);
        $filter3 = new TopicFilter(["segment2"]);

        $topicFilters = new TopicFilters($filter1, $filter2, $filter3);

        $this->assertEquals(3, $topicFilters->count());
    }

    public function testTopicFiltersToArray(): void
    {
        $filter1 = new TopicFilter(["*", "segment1"]);
        $filter2 = new TopicFilter(["segment2", "*"]);

        $topicFilters = new TopicFilters($filter1, $filter2);

        $array = $topicFilters->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertEquals($filter1, $array[0]);
        $this->assertEquals($filter2, $array[1]);
    }

    // =====================================================
    // EventFilter Tests (5 methods)
    // =====================================================

    public function testEventFilterConstructor(): void
    {
        $contractIds = ["CAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABSC4"];
        $topicFilter = new TopicFilter(["*", XdrSCVal::forSymbol("transfer")->toBase64Xdr()]);
        $topicFilters = new TopicFilters($topicFilter);

        $eventFilter = new EventFilter(
            type: "contract",
            contractIds: $contractIds,
            topics: $topicFilters
        );

        $this->assertEquals("contract", $eventFilter->type);
        $this->assertEquals($contractIds, $eventFilter->contractIds);
        $this->assertEquals($topicFilters, $eventFilter->topics);
    }

    public function testEventFilterGetRequestParams(): void
    {
        $contractIds = ["CAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABSC4"];
        $topicFilter = new TopicFilter(["*", "segment"]);
        $topicFilters = new TopicFilters($topicFilter);

        $eventFilter = new EventFilter(
            type: "contract",
            contractIds: $contractIds,
            topics: $topicFilters
        );

        $params = $eventFilter->getRequestParams();

        $this->assertIsArray($params);
        $this->assertEquals("contract", $params['type']);
        $this->assertArrayHasKey('contractIds', $params);
        $this->assertCount(1, $params['contractIds']);
        $this->assertEquals($contractIds[0], $params['contractIds'][0]);
        $this->assertArrayHasKey('topics', $params);
    }

    public function testEventFilterGetType(): void
    {
        $eventFilter = new EventFilter(type: "system");

        $this->assertEquals("system", $eventFilter->getType());
    }

    public function testEventFilterGetContractIds(): void
    {
        $contractIds = [
            "CAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABSC4",
            "CBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBABSC5"
        ];
        $eventFilter = new EventFilter(contractIds: $contractIds);

        $this->assertEquals($contractIds, $eventFilter->getContractIds());
    }

    public function testEventFilterGetTopics(): void
    {
        $topicFilter = new TopicFilter(["*", "segment"]);
        $topicFilters = new TopicFilters($topicFilter);

        $eventFilter = new EventFilter(topics: $topicFilters);

        $this->assertEquals($topicFilters, $eventFilter->getTopics());
    }

    // =====================================================
    // EventFilters Tests (5 methods)
    // =====================================================

    public function testEventFiltersConstructor(): void
    {
        $filter1 = new EventFilter(type: "contract");
        $filter2 = new EventFilter(type: "system");

        $eventFilters = new EventFilters($filter1, $filter2);

        $this->assertEquals(2, $eventFilters->count());
    }

    public function testEventFiltersCurrent(): void
    {
        $filter1 = new EventFilter(type: "contract");
        $filter2 = new EventFilter(type: "system");

        $eventFilters = new EventFilters($filter1, $filter2);
        $eventFilters->rewind();

        $current = $eventFilters->current();
        $this->assertInstanceOf(EventFilter::class, $current);
        $this->assertEquals($filter1, $current);
    }

    public function testEventFiltersAdd(): void
    {
        $filter1 = new EventFilter(type: "contract");
        $eventFilters = new EventFilters($filter1);

        $this->assertEquals(1, $eventFilters->count());

        $filter2 = new EventFilter(type: "system");
        $eventFilters->add($filter2);

        $this->assertEquals(2, $eventFilters->count());
    }

    public function testEventFiltersCount(): void
    {
        $filter1 = new EventFilter(type: "contract");
        $filter2 = new EventFilter(type: "system");
        $filter3 = new EventFilter(type: "diagnostic");

        $eventFilters = new EventFilters($filter1, $filter2, $filter3);

        $this->assertEquals(3, $eventFilters->count());
    }

    public function testEventFiltersToArray(): void
    {
        $filter1 = new EventFilter(type: "contract");
        $filter2 = new EventFilter(type: "system");

        $eventFilters = new EventFilters($filter1, $filter2);

        $array = $eventFilters->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertEquals($filter1, $array[0]);
        $this->assertEquals($filter2, $array[1]);
    }

    // =====================================================
    // PaginationOptions Tests (6 methods)
    // =====================================================

    public function testPaginationOptionsConstructor(): void
    {
        $cursor = "0164090849041387521-0000000000";
        $limit = 100;

        $pagination = new PaginationOptions($cursor, $limit);

        $this->assertEquals($cursor, $pagination->cursor);
        $this->assertEquals($limit, $pagination->limit);
    }

    public function testPaginationOptionsGetRequestParams(): void
    {
        $cursor = "0164090849041387521-0000000000";
        $limit = 50;

        $pagination = new PaginationOptions($cursor, $limit);
        $params = $pagination->getRequestParams();

        $this->assertIsArray($params);
        $this->assertEquals($cursor, $params['cursor']);
        $this->assertEquals($limit, $params['limit']);
    }

    public function testPaginationOptionsGetCursor(): void
    {
        $cursor = "test_cursor_123";
        $pagination = new PaginationOptions($cursor);

        $this->assertEquals($cursor, $pagination->getCursor());
    }

    public function testPaginationOptionsSetCursor(): void
    {
        $pagination = new PaginationOptions();
        $cursor = "new_cursor_456";

        $pagination->setCursor($cursor);

        $this->assertEquals($cursor, $pagination->getCursor());
    }

    public function testPaginationOptionsGetLimit(): void
    {
        $limit = 200;
        $pagination = new PaginationOptions(null, $limit);

        $this->assertEquals($limit, $pagination->getLimit());
    }

    public function testPaginationOptionsSetLimit(): void
    {
        $pagination = new PaginationOptions();
        $limit = 500;

        $pagination->setLimit($limit);

        $this->assertEquals($limit, $pagination->getLimit());
    }

    // =====================================================
    // ResourceConfig Tests (4 methods)
    // =====================================================

    public function testResourceConfigConstructor(): void
    {
        $instructionLeeway = 5000000;
        $resourceConfig = new ResourceConfig($instructionLeeway);

        $this->assertEquals($instructionLeeway, $resourceConfig->instructionLeeway);
    }

    public function testResourceConfigGetRequestParams(): void
    {
        $instructionLeeway = 3000000;
        $resourceConfig = new ResourceConfig($instructionLeeway);

        $params = $resourceConfig->getRequestParams();

        $this->assertIsArray($params);
        $this->assertArrayHasKey('instructionLeeway', $params);
        $this->assertEquals($instructionLeeway, $params['instructionLeeway']);
    }

    public function testResourceConfigGetInstructionLeeway(): void
    {
        $instructionLeeway = 7000000;
        $resourceConfig = new ResourceConfig($instructionLeeway);

        $this->assertEquals($instructionLeeway, $resourceConfig->getInstructionLeeway());
    }

    public function testResourceConfigSetInstructionLeeway(): void
    {
        $resourceConfig = new ResourceConfig(1000000);
        $newLeeway = 9000000;

        $resourceConfig->setInstructionLeeway($newLeeway);

        $this->assertEquals($newLeeway, $resourceConfig->getInstructionLeeway());
    }

    // =====================================================
    // GetEventsRequest Tests (10 methods)
    // =====================================================

    public function testGetEventsRequestConstructor(): void
    {
        $startLedger = 100;
        $endLedger = 200;
        $filter = new EventFilter(type: "contract");
        $filters = new EventFilters($filter);
        $pagination = new PaginationOptions("cursor", 50);

        $request = new GetEventsRequest($startLedger, $endLedger, $filters, $pagination);

        $this->assertEquals($startLedger, $request->startLedger);
        $this->assertEquals($endLedger, $request->endLedger);
        $this->assertEquals($filters, $request->filters);
        $this->assertEquals($pagination, $request->paginationOptions);
    }

    public function testGetEventsRequestGetRequestParams(): void
    {
        $startLedger = 150;
        $endLedger = 250;
        $filter = new EventFilter(type: "system");
        $filters = new EventFilters($filter);
        $pagination = new PaginationOptions("cursor123", 100);

        $request = new GetEventsRequest($startLedger, $endLedger, $filters, $pagination);
        $params = $request->getRequestParams();

        $this->assertIsArray($params);
        $this->assertEquals($startLedger, $params['startLedger']);
        $this->assertEquals($endLedger, $params['endLedger']);
        $this->assertArrayHasKey('filters', $params);
        $this->assertIsArray($params['filters']);
        $this->assertArrayHasKey('pagination', $params);
    }

    public function testGetEventsRequestGetStartLedger(): void
    {
        $startLedger = 300;
        $request = new GetEventsRequest($startLedger);

        $this->assertEquals($startLedger, $request->getStartLedger());
    }

    public function testGetEventsRequestSetStartLedger(): void
    {
        $request = new GetEventsRequest();
        $startLedger = 400;

        $request->setStartLedger($startLedger);

        $this->assertEquals($startLedger, $request->getStartLedger());
    }

    public function testGetEventsRequestGetEndLedger(): void
    {
        $endLedger = 500;
        $request = new GetEventsRequest(null, $endLedger);

        $this->assertEquals($endLedger, $request->getEndLedger());
    }

    public function testGetEventsRequestSetEndLedger(): void
    {
        $request = new GetEventsRequest();
        $endLedger = 600;

        $request->setEndLedger($endLedger);

        $this->assertEquals($endLedger, $request->getEndLedger());
    }

    public function testGetEventsRequestGetFilters(): void
    {
        $filter = new EventFilter(type: "diagnostic");
        $filters = new EventFilters($filter);
        $request = new GetEventsRequest(null, null, $filters);

        $this->assertEquals($filters, $request->getFilters());
    }

    public function testGetEventsRequestSetFilters(): void
    {
        $request = new GetEventsRequest();
        $filter = new EventFilter(type: "contract");
        $filters = new EventFilters($filter);

        $request->setFilters($filters);

        $this->assertEquals($filters, $request->getFilters());
    }

    public function testGetEventsRequestGetPaginationOptions(): void
    {
        $pagination = new PaginationOptions("cursor789", 150);
        $request = new GetEventsRequest(null, null, null, $pagination);

        $this->assertEquals($pagination, $request->getPaginationOptions());
    }

    public function testGetEventsRequestSetPaginationOptions(): void
    {
        $request = new GetEventsRequest();
        $pagination = new PaginationOptions("cursor999", 200);

        $request->setPaginationOptions($pagination);

        $this->assertEquals($pagination, $request->getPaginationOptions());
    }

    // =====================================================
    // SimulateTransactionRequest Tests (8 methods)
    // =====================================================

    public function testSimulateTransactionRequestConstructor(): void
    {
        $transaction = $this->createMockTransaction();
        $resourceConfig = new ResourceConfig(5000000);
        $authMode = "enforce";

        $request = new SimulateTransactionRequest($transaction, $resourceConfig, $authMode);

        $this->assertEquals($transaction, $request->transaction);
        $this->assertEquals($resourceConfig, $request->resourceConfig);
        $this->assertEquals($authMode, $request->authMode);
    }

    public function testSimulateTransactionRequestGetRequestParams(): void
    {
        $transaction = $this->createMockTransaction();
        $resourceConfig = new ResourceConfig(3000000);
        $authMode = "record";

        $request = new SimulateTransactionRequest($transaction, $resourceConfig, $authMode);
        $params = $request->getRequestParams();

        $this->assertIsArray($params);
        $this->assertArrayHasKey('transaction', $params);
        $this->assertIsString($params['transaction']);
        $this->assertArrayHasKey('resourceConfig', $params);
        $this->assertEquals(3000000, $params['resourceConfig']['instructionLeeway']);
        $this->assertArrayHasKey('authMode', $params);
        $this->assertEquals("record", $params['authMode']);
    }

    public function testSimulateTransactionRequestGetTransaction(): void
    {
        $transaction = $this->createMockTransaction();
        $request = new SimulateTransactionRequest($transaction);

        $this->assertEquals($transaction, $request->getTransaction());
    }

    public function testSimulateTransactionRequestSetTransaction(): void
    {
        $transaction1 = $this->createMockTransaction();
        $request = new SimulateTransactionRequest($transaction1);

        $transaction2 = $this->createMockTransaction();
        $request->setTransaction($transaction2);

        $this->assertEquals($transaction2, $request->getTransaction());
    }

    public function testSimulateTransactionRequestGetResourceConfig(): void
    {
        $transaction = $this->createMockTransaction();
        $resourceConfig = new ResourceConfig(7000000);
        $request = new SimulateTransactionRequest($transaction, $resourceConfig);

        $this->assertEquals($resourceConfig, $request->getResourceConfig());
    }

    public function testSimulateTransactionRequestSetResourceConfig(): void
    {
        $transaction = $this->createMockTransaction();
        $request = new SimulateTransactionRequest($transaction);

        $resourceConfig = new ResourceConfig(9000000);
        $request->setResourceConfig($resourceConfig);

        $this->assertEquals($resourceConfig, $request->getResourceConfig());
    }

    public function testSimulateTransactionRequestGetAuthMode(): void
    {
        $transaction = $this->createMockTransaction();
        $request = new SimulateTransactionRequest($transaction, null, "record_allow_nonroot");

        $this->assertEquals("record_allow_nonroot", $request->getAuthMode());
    }

    public function testSimulateTransactionRequestSetAuthMode(): void
    {
        $transaction = $this->createMockTransaction();
        $request = new SimulateTransactionRequest($transaction);

        $request->setAuthMode("enforce");

        $this->assertEquals("enforce", $request->getAuthMode());
    }

    // =====================================================
    // GetLedgersRequest Tests (6 methods)
    // =====================================================

    public function testGetLedgersRequestConstructor(): void
    {
        $startLedger = 1000;
        $pagination = new PaginationOptions("cursor", 50);

        $request = new GetLedgersRequest($startLedger, $pagination);

        $this->assertEquals($startLedger, $request->startLedger);
        $this->assertEquals($pagination, $request->paginationOptions);
    }

    public function testGetLedgersRequestGetRequestParams(): void
    {
        $startLedger = 2000;
        $pagination = new PaginationOptions("cursor456", 100);

        $request = new GetLedgersRequest($startLedger, $pagination);
        $params = $request->getRequestParams();

        $this->assertIsArray($params);
        $this->assertEquals($startLedger, $params['startLedger']);
        $this->assertArrayHasKey('pagination', $params);
    }

    public function testGetLedgersRequestGetStartLedger(): void
    {
        $startLedger = 3000;
        $request = new GetLedgersRequest($startLedger);

        $this->assertEquals($startLedger, $request->getStartLedger());
    }

    public function testGetLedgersRequestSetStartLedger(): void
    {
        $request = new GetLedgersRequest();
        $startLedger = 4000;

        $request->setStartLedger($startLedger);

        $this->assertEquals($startLedger, $request->getStartLedger());
    }

    public function testGetLedgersRequestGetPaginationOptions(): void
    {
        $pagination = new PaginationOptions("cursor789", 150);
        $request = new GetLedgersRequest(null, $pagination);

        $this->assertEquals($pagination, $request->getPaginationOptions());
    }

    public function testGetLedgersRequestSetPaginationOptions(): void
    {
        $request = new GetLedgersRequest();
        $pagination = new PaginationOptions("cursor999", 200);

        $request->setPaginationOptions($pagination);

        $this->assertEquals($pagination, $request->getPaginationOptions());
    }

    // =====================================================
    // GetTransactionsRequest Tests (6 methods)
    // =====================================================

    public function testGetTransactionsRequestConstructor(): void
    {
        $startLedger = 5000;
        $pagination = new PaginationOptions("cursor", 75);

        $request = new GetTransactionsRequest($startLedger, $pagination);

        $this->assertEquals($startLedger, $request->startLedger);
        $this->assertEquals($pagination, $request->paginationOptions);
    }

    public function testGetTransactionsRequestGetRequestParams(): void
    {
        $startLedger = 6000;
        $pagination = new PaginationOptions("cursor111", 125);

        $request = new GetTransactionsRequest($startLedger, $pagination);
        $params = $request->getRequestParams();

        $this->assertIsArray($params);
        $this->assertEquals($startLedger, $params['startLedger']);
        $this->assertArrayHasKey('pagination', $params);
    }

    public function testGetTransactionsRequestGetStartLedger(): void
    {
        $startLedger = 7000;
        $request = new GetTransactionsRequest($startLedger);

        $this->assertEquals($startLedger, $request->getStartLedger());
    }

    public function testGetTransactionsRequestSetStartLedger(): void
    {
        $request = new GetTransactionsRequest();
        $startLedger = 8000;

        $request->setStartLedger($startLedger);

        $this->assertEquals($startLedger, $request->getStartLedger());
    }

    public function testGetTransactionsRequestGetPaginationOptions(): void
    {
        $pagination = new PaginationOptions("cursor222", 175);
        $request = new GetTransactionsRequest(null, $pagination);

        $this->assertEquals($pagination, $request->getPaginationOptions());
    }

    public function testGetTransactionsRequestSetPaginationOptions(): void
    {
        $request = new GetTransactionsRequest();
        $pagination = new PaginationOptions("cursor333", 225);

        $request->setPaginationOptions($pagination);

        $this->assertEquals($pagination, $request->getPaginationOptions());
    }

    // =====================================================
    // Helper Methods
    // =====================================================

    private function createMockTransaction(): \Soneso\StellarSDK\Transaction
    {
        $sourceAccount = Account::fromAccountId(
            "GDAT5HWTGIU4TSSZ4752OUC4SABDLTLZFRPZUJ3D6LKBNEPA7V2CIG54",
            new BigInteger(1)
        );

        $contractId = "CDCYWK73YTYFJZZSJ5V7EDFNHYBG4QN3VUNG2IGD27KJDDPNCZKBCBXK";
        $functionName = "hello";
        $arguments = [XdrSCVal::forSymbol("world")];

        $invokeContractHostFunction = new InvokeContractHostFunction($contractId, $functionName, $arguments);
        $builder = new InvokeHostFunctionOperationBuilder($invokeContractHostFunction);
        $op = $builder->build();

        return (new TransactionBuilder($sourceAccount))
            ->addOperation($op)
            ->build();
    }
}
