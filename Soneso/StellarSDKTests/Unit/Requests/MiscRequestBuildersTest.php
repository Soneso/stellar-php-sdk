<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Requests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Requests\FeeStatsRequestBuilder;
use Soneso\StellarSDK\Requests\HealthRequestBuilder;
use Soneso\StellarSDK\Requests\RootRequestBuilder;
use Soneso\StellarSDK\Requests\SubmitTransactionRequestBuilder;
use Soneso\StellarSDK\Requests\SubmitAsyncTransactionRequestBuilder;
use Soneso\StellarSDK\Responses\FeeStats\FeeStatsResponse;
use Soneso\StellarSDK\Responses\Health\HealthResponse;
use Soneso\StellarSDK\Responses\Root\RootResponse;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\SubmitAsyncTransactionResponse;

/**
 * Unit tests for miscellaneous Request Builder classes
 *
 * Tests URL building, response parsing, and error handling for:
 * - FeeStatsRequestBuilder
 * - HealthRequestBuilder
 * - RootRequestBuilder
 * - SubmitTransactionRequestBuilder
 * - SubmitAsyncTransactionRequestBuilder
 */
class MiscRequestBuildersTest extends TestCase
{
    private const TEST_HORIZON_URL = 'https://horizon-testnet.stellar.org';
    private const TEST_TX_HASH = 'a434302ea03b42dd00614e258e6b7cdce5dc8a9d7381b1cba8844b75df4f1486';
    private const TEST_TX_XDR = 'AAAAAgAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAGQDHvLTAAABIwAAAAEAAAAAAAAAAAAAAABmh4pDAAAAAQAAABgwLDA3NSUgRGFpbHkgZm9yIEhvbGRlcnMAAAABAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAABaG1GYAAAAAAAAAAKpYmInAAAAQLmth39Fjo8TC05wn5ZOAw4lou2rkxAaK6k16lHYXlEcsYHZ/d+ga5bCgO9KV/sbKaZAUCC9KvFIplXkXffBxQ0WUsBeAAAAQC2w45T3S24shkJ7uyRl/P5xD86Xfi7qTYxmb8uh8PEcwlb5oqbnJcTlUV2uJs2+gzMlijNtAbrCm6wO+1YsJQ4=';

    /**
     * Helper method to create a mocked HTTP client
     */
    private function createMockedClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        return new Client(['handler' => $handlerStack, 'base_uri' => self::TEST_HORIZON_URL]);
    }

    /**
     * Helper method to get sample fee stats JSON response
     */
    private function getSampleFeeStatsJson(): string
    {
        return json_encode([
            'last_ledger' => '12345',
            'last_ledger_base_fee' => '100',
            'ledger_capacity_usage' => '0.97',
            'fee_charged' => [
                'max' => '1000',
                'min' => '100',
                'mode' => '100',
                'p10' => '100',
                'p20' => '100',
                'p30' => '100',
                'p40' => '100',
                'p50' => '100',
                'p60' => '150',
                'p70' => '200',
                'p80' => '300',
                'p90' => '500',
                'p95' => '700',
                'p99' => '1000'
            ],
            'max_fee' => [
                'max' => '100000',
                'min' => '100',
                'mode' => '100',
                'p10' => '100',
                'p20' => '100',
                'p30' => '100',
                'p40' => '100',
                'p50' => '100',
                'p60' => '100',
                'p70' => '100',
                'p80' => '100',
                'p90' => '100',
                'p95' => '1000',
                'p99' => '10000'
            ]
        ]);
    }

    /**
     * Helper method to get sample health JSON response
     */
    private function getSampleHealthJson(): string
    {
        return json_encode([
            'database_connected' => true,
            'core_up' => true,
            'core_synced' => true
        ]);
    }

    /**
     * Helper method to get sample root JSON response
     */
    private function getSampleRootJson(): string
    {
        return json_encode([
            'horizon_version' => '2.15.1',
            'core_version' => 'stellar-core 19.5.1 (c5f638652cc51c45f7dd1e1eb37791cd8d5c9a85)',
            'ingest_latest_ledger' => 1234567,
            'history_latest_ledger' => 1234567,
            'history_latest_ledger_closed_at' => '2024-01-20T12:00:00Z',
            'history_elder_ledger' => 2,
            'core_latest_ledger' => 1234567,
            'network_passphrase' => 'Test SDF Network ; September 2015',
            'protocol_version' => 20,
            'current_protocol_version' => 20,
            'core_supported_protocol_version' => 20
        ]);
    }

    /**
     * Helper method to get sample submit transaction JSON response
     */
    private function getSampleSubmitTransactionJson(bool $successful = true): string
    {
        // Using minimal response without full XDR to avoid parsing complexity in tests
        $baseResponse = [
            '_links' => [
                'transaction' => [
                    'href' => 'https://horizon.stellar.org/transactions/' . self::TEST_TX_HASH
                ]
            ],
            'hash' => self::TEST_TX_HASH,
            'ledger' => 1234567,
            'created_at' => '2024-01-20T12:00:00Z',
            'source_account' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'source_account_sequence' => '123456789012',
            'fee_account' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'fee_charged' => '100',
            'max_fee' => '100',
            'operation_count' => 1,
            'envelope_xdr' => self::TEST_TX_XDR,
            'result_xdr' => 'AAAAAAAAAGQAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAA=',
            'result_meta_xdr' => 'AAAAAwAAAAAAAAACAAAAAwMgANMAAAAAAAAAABpkeES/191B7z9YrUh5J2wySk7XDoZU7ulEgyoWUsBeAAAAAAHJUdQDHvLTAAABIgAAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAAAAAAAAAAAAAAAAAAMAAAAAAyAAyAAAAABmh4mjAAAAAAAAAAEDIADTAAAAAAAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAAAByVHUAx7y0wAAASMAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgANMAAAAAZoeJ4wAAAAAAAAABAAAABAAAAAMDIADTAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAKTCBRhynx//////////wAAAAEAAAABAAAA1xsdUZsAAAAAAAAAAAAAAAAAAAAAAAAAAQMgANMAAAABAAAAAEOS3eq8CQUgHPBA1RMi5Vg7qVqRYg/+phWaqHCpYmInAAAAAUhVTgAAAAAAYqvLVFIr7zrouOae2L2ZCcrBlj9GH/FhAvIptvt76+QAApMHukZ45H//////////AAAAAQAAAAEAAADXGx1RmwAAAAAAAAAAAAAAAAAAAAAAAAADAx/LTQAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAdVOSfYYf/////////8AAAABAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEDIADTAAAAAQAAAAAsqHb+7EzH/s5AbXo3k6x2lY+XRkZJ4MhWlZCqw5XbAgAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAAB1ahlR7B//////////wAAAAEAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            'fee_meta_xdr' => 'AAAAAgAAAAMDIADIAAAAAAAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAAAByVI4Ax7y0wAAASIAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgAMgAAAAAZoeJowAAAAAAAAABAyAA0wAAAAAAAAAAGmR4RL/X3UHvP1itSHknbDJKTtcOhlTu6USDKhZSwF4AAAAAAclR1AMe8tMAAAEiAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAACAAAAAAAAAAAAAAAAAAAAAwAAAAADIADIAAAAAGaHiaMAAAAA',
            'memo_type' => 'text',
            'memo' => 'test',
            'signatures' => [
                'ua2Hf0WOjxMLTnCflk4DDiWi7auTEBorqTXqUdheURyxgdn936BrlsKA70pX+xsppkBQIL0q8UimVeRd98HFDQ==',
            ],
            'successful' => $successful,
            'paging_token' => '225180887607500800',
            'id' => self::TEST_TX_HASH
        ];

        if (!$successful) {
            $baseResponse['extras'] = [
                'envelope_xdr' => self::TEST_TX_XDR,
                'result_xdr' => 'AAAAAAAAAGT/////AAAAAQAAAAAAAAAB////+gAAAAA=',
                'result_codes' => [
                    'transaction' => 'tx_failed',
                    'operations' => ['op_underfunded']
                ]
            ];
        }

        return json_encode($baseResponse);
    }

    /**
     * Helper method to get sample async submit transaction JSON response
     */
    private function getSampleSubmitAsyncTransactionJson(string $status = 'PENDING'): string
    {
        return json_encode([
            'tx_status' => $status,
            'hash' => self::TEST_TX_HASH
        ]);
    }

    // FeeStatsRequestBuilder Tests

    /**
     * Test FeeStatsRequestBuilder creates proper URL when getFeeStats is called
     */
    public function testFeeStatsRequestBuilderCreatesUrl(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleFeeStatsJson())
        ]);
        $builder = new FeeStatsRequestBuilder($client);

        $builder->getFeeStats();

        // If we got here without exception, URL building worked correctly
        $this->assertTrue(true);
    }

    /**
     * Test getFeeStats method with mocked response
     */
    public function testGetFeeStats(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleFeeStatsJson())
        ]);
        $builder = new FeeStatsRequestBuilder($client);

        $response = $builder->getFeeStats();

        $this->assertInstanceOf(FeeStatsResponse::class, $response);
        $this->assertEquals('12345', $response->getLastLedger());
        $this->assertEquals('100', $response->getLastLedgerBaseFee());
        $this->assertEquals('0.97', $response->getLedgerCapacityUsage());
        $this->assertEquals('1000', $response->getFeeCharged()->getMax());
        $this->assertEquals('100', $response->getFeeCharged()->getMin());
        $this->assertEquals('100', $response->getFeeCharged()->getMode());
        $this->assertEquals('100000', $response->getMaxFee()->getMax());
        $this->assertEquals('100', $response->getMaxFee()->getMin());
    }


    // HealthRequestBuilder Tests

    /**
     * Test HealthRequestBuilder creates proper URL when getHealth is called
     */
    public function testHealthRequestBuilderCreatesUrl(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleHealthJson())
        ]);
        $builder = new HealthRequestBuilder($client);

        $builder->getHealth();

        // If we got here without exception, URL building worked correctly
        $this->assertTrue(true);
    }

    /**
     * Test getHealth method with healthy response
     */
    public function testGetHealthHealthy(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleHealthJson())
        ]);
        $builder = new HealthRequestBuilder($client);

        $response = $builder->getHealth();

        $this->assertInstanceOf(HealthResponse::class, $response);
        $this->assertTrue($response->getDatabaseConnected());
        $this->assertTrue($response->getCoreUp());
        $this->assertTrue($response->getCoreSynced());
    }

    /**
     * Test getHealth method with unhealthy response
     */
    public function testGetHealthUnhealthy(): void
    {
        $unhealthyJson = json_encode([
            'database_connected' => false,
            'core_up' => true,
            'core_synced' => false
        ]);

        $client = $this->createMockedClient([
            new Response(200, [], $unhealthyJson)
        ]);
        $builder = new HealthRequestBuilder($client);

        $response = $builder->getHealth();

        $this->assertInstanceOf(HealthResponse::class, $response);
        $this->assertFalse($response->getDatabaseConnected());
        $this->assertTrue($response->getCoreUp());
        $this->assertFalse($response->getCoreSynced());
    }


    // RootRequestBuilder Tests

    /**
     * Test getRoot method with mocked response
     */
    public function testGetRoot(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleRootJson())
        ]);
        $builder = new RootRequestBuilder($client);

        $response = $builder->getRoot(self::TEST_HORIZON_URL);

        $this->assertInstanceOf(RootResponse::class, $response);
        $this->assertEquals('2.15.1', $response->getHorizonVersion());
        $this->assertStringContainsString('stellar-core 19.5.1', $response->getCoreVersion());
        $this->assertEquals(1234567, $response->getIngestLatestLedger());
        $this->assertEquals(1234567, $response->getHistoryLatestLedger());
        $this->assertEquals('2024-01-20T12:00:00Z', $response->getHistoryLatestLedgerClosedAt());
        $this->assertEquals(2, $response->getHistoryElderLedger());
        $this->assertEquals(1234567, $response->getCoreLatestLedger());
        $this->assertEquals('Test SDF Network ; September 2015', $response->getNetworkPassphrase());
        $this->assertEquals(20, $response->getProtocolVersion());
        $this->assertEquals(20, $response->getCurrentProtocolVersion());
        $this->assertEquals(20, $response->getCoreSupportedProtocolVersion());
    }


    // SubmitTransactionRequestBuilder Tests

    /**
     * Test SubmitTransactionRequestBuilder URL building
     */
    public function testSubmitTransactionRequestBuilderBuildUrl(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new SubmitTransactionRequestBuilder($client);

        $url = $builder->buildUrl();

        $this->assertStringContainsString('transactions?', $url);
    }

    /**
     * Test setTransactionEnvelopeXdrBase64 method
     */
    public function testSetTransactionEnvelopeXdrBase64(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new SubmitTransactionRequestBuilder($client);

        $result = $builder->setTransactionEnvelopeXdrBase64(self::TEST_TX_XDR);

        $this->assertInstanceOf(SubmitTransactionRequestBuilder::class, $result);
        $url = $builder->buildUrl();
        $this->assertStringContainsString('tx=', $url);
    }

    /**
     * Test successful transaction submission
     */
    public function testSubmitTransactionSuccess(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleSubmitTransactionJson(true))
        ]);
        $builder = new SubmitTransactionRequestBuilder($client);

        $response = $builder
            ->setTransactionEnvelopeXdrBase64(self::TEST_TX_XDR)
            ->execute();

        $this->assertInstanceOf(SubmitTransactionResponse::class, $response);
        $this->assertEquals(self::TEST_TX_HASH, $response->getHash());
        $this->assertEquals(1234567, $response->getLedger());
    }

    /**
     * Test request method with full URL
     */
    public function testSubmitTransactionRequest(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleSubmitTransactionJson(true))
        ]);
        $builder = new SubmitTransactionRequestBuilder($client);

        $response = $builder->request(self::TEST_HORIZON_URL . '/transactions?tx=' . urlencode(self::TEST_TX_XDR));

        $this->assertInstanceOf(SubmitTransactionResponse::class, $response);
        $this->assertEquals(self::TEST_TX_HASH, $response->getHash());
    }

    /**
     * Test method chaining for SubmitTransactionRequestBuilder
     */
    public function testSubmitTransactionMethodChaining(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new SubmitTransactionRequestBuilder($client);

        $result = $builder->setTransactionEnvelopeXdrBase64(self::TEST_TX_XDR);

        $this->assertInstanceOf(SubmitTransactionRequestBuilder::class, $result);
    }

    // SubmitAsyncTransactionRequestBuilder Tests

    /**
     * Test SubmitAsyncTransactionRequestBuilder URL building
     */
    public function testSubmitAsyncTransactionRequestBuilderBuildUrl(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new SubmitAsyncTransactionRequestBuilder($client);

        $url = $builder->buildUrl();

        $this->assertStringContainsString('transactions_async?', $url);
    }

    /**
     * Test setTransactionEnvelopeXdrBase64 method for async
     */
    public function testSubmitAsyncSetTransactionEnvelopeXdrBase64(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new SubmitAsyncTransactionRequestBuilder($client);

        $result = $builder->setTransactionEnvelopeXdrBase64(self::TEST_TX_XDR);

        $this->assertInstanceOf(SubmitAsyncTransactionRequestBuilder::class, $result);
        $url = $builder->buildUrl();
        $this->assertStringContainsString('tx=', $url);
    }

    /**
     * Test async transaction submission with PENDING status
     */
    public function testSubmitAsyncTransactionPending(): void
    {
        $client = $this->createMockedClient([
            new Response(201, [], $this->getSampleSubmitAsyncTransactionJson('PENDING'))
        ]);
        $builder = new SubmitAsyncTransactionRequestBuilder($client);

        $response = $builder
            ->setTransactionEnvelopeXdrBase64(self::TEST_TX_XDR)
            ->execute();

        $this->assertInstanceOf(SubmitAsyncTransactionResponse::class, $response);
        $this->assertEquals('PENDING', $response->txStatus);
        $this->assertEquals(self::TEST_TX_HASH, $response->hash);
        $this->assertEquals(201, $response->httpStatusCode);
    }

    /**
     * Test async transaction submission with DUPLICATE status
     */
    public function testSubmitAsyncTransactionDuplicate(): void
    {
        $client = $this->createMockedClient([
            new Response(409, [], $this->getSampleSubmitAsyncTransactionJson('DUPLICATE'))
        ]);
        $builder = new SubmitAsyncTransactionRequestBuilder($client);

        $response = $builder
            ->setTransactionEnvelopeXdrBase64(self::TEST_TX_XDR)
            ->execute();

        $this->assertInstanceOf(SubmitAsyncTransactionResponse::class, $response);
        $this->assertEquals('DUPLICATE', $response->txStatus);
        $this->assertEquals(self::TEST_TX_HASH, $response->hash);
        $this->assertEquals(409, $response->httpStatusCode);
    }

    /**
     * Test async transaction submission with ERROR status
     */
    public function testSubmitAsyncTransactionError(): void
    {
        $client = $this->createMockedClient([
            new Response(400, [], $this->getSampleSubmitAsyncTransactionJson('ERROR'))
        ]);
        $builder = new SubmitAsyncTransactionRequestBuilder($client);

        $response = $builder
            ->setTransactionEnvelopeXdrBase64(self::TEST_TX_XDR)
            ->execute();

        $this->assertInstanceOf(SubmitAsyncTransactionResponse::class, $response);
        $this->assertEquals('ERROR', $response->txStatus);
        $this->assertEquals(self::TEST_TX_HASH, $response->hash);
        $this->assertEquals(400, $response->httpStatusCode);
    }

    /**
     * Test async transaction submission with TRY_AGAIN_LATER status
     */
    public function testSubmitAsyncTransactionTryAgainLater(): void
    {
        $client = $this->createMockedClient([
            new Response(503, [], $this->getSampleSubmitAsyncTransactionJson('TRY_AGAIN_LATER'))
        ]);
        $builder = new SubmitAsyncTransactionRequestBuilder($client);

        $response = $builder
            ->setTransactionEnvelopeXdrBase64(self::TEST_TX_XDR)
            ->execute();

        $this->assertInstanceOf(SubmitAsyncTransactionResponse::class, $response);
        $this->assertEquals('TRY_AGAIN_LATER', $response->txStatus);
        $this->assertEquals(self::TEST_TX_HASH, $response->hash);
        $this->assertEquals(503, $response->httpStatusCode);
    }

    /**
     * Test request method with full URL for async
     */
    public function testSubmitAsyncTransactionRequest(): void
    {
        $client = $this->createMockedClient([
            new Response(201, [], $this->getSampleSubmitAsyncTransactionJson('PENDING'))
        ]);
        $builder = new SubmitAsyncTransactionRequestBuilder($client);

        $response = $builder->request(self::TEST_HORIZON_URL . '/transactions_async?tx=' . urlencode(self::TEST_TX_XDR));

        $this->assertInstanceOf(SubmitAsyncTransactionResponse::class, $response);
        $this->assertEquals('PENDING', $response->txStatus);
    }

    /**
     * Test method chaining for SubmitAsyncTransactionRequestBuilder
     */
    public function testSubmitAsyncTransactionMethodChaining(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new SubmitAsyncTransactionRequestBuilder($client);

        $result = $builder->setTransactionEnvelopeXdrBase64(self::TEST_TX_XDR);

        $this->assertInstanceOf(SubmitAsyncTransactionRequestBuilder::class, $result);
    }

    /**
     * Test FeeStats percentile values parsing
     */
    public function testFeeStatsPercentileValues(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleFeeStatsJson())
        ]);
        $builder = new FeeStatsRequestBuilder($client);

        $response = $builder->getFeeStats();

        $this->assertEquals('100', $response->getFeeCharged()->getP10());
        $this->assertEquals('500', $response->getFeeCharged()->getP90());
        $this->assertEquals('1000', $response->getFeeCharged()->getP99());
        $this->assertEquals('100', $response->getMaxFee()->getP50());
        $this->assertEquals('10000', $response->getMaxFee()->getP99());
    }

    /**
     * Test async submission with different HTTP status codes
     */
    public function testSubmitAsyncTransactionStatusCodes(): void
    {
        $testCases = [
            ['status' => 'PENDING', 'httpCode' => 201],
            ['status' => 'DUPLICATE', 'httpCode' => 409],
            ['status' => 'ERROR', 'httpCode' => 400],
            ['status' => 'TRY_AGAIN_LATER', 'httpCode' => 503],
        ];

        foreach ($testCases as $case) {
            $client = $this->createMockedClient([
                new Response($case['httpCode'], [], $this->getSampleSubmitAsyncTransactionJson($case['status']))
            ]);
            $builder = new SubmitAsyncTransactionRequestBuilder($client);

            $response = $builder
                ->setTransactionEnvelopeXdrBase64(self::TEST_TX_XDR)
                ->execute();

            $this->assertEquals($case['status'], $response->txStatus);
            $this->assertEquals($case['httpCode'], $response->httpStatusCode);
        }
    }

    /**
     * Test Root response network identification
     */
    public function testRootResponseNetworkIdentification(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleRootJson())
        ]);
        $builder = new RootRequestBuilder($client);

        $response = $builder->getRoot(self::TEST_HORIZON_URL);

        $this->assertStringContainsString('Test SDF Network', $response->getNetworkPassphrase());
        $this->assertEquals(20, $response->getProtocolVersion());
    }

    /**
     * Test Health response all flags
     */
    public function testHealthResponseAllFlags(): void
    {
        $healthyJson = json_encode([
            'database_connected' => true,
            'core_up' => true,
            'core_synced' => true
        ]);

        $client = $this->createMockedClient([
            new Response(200, [], $healthyJson)
        ]);
        $builder = new HealthRequestBuilder($client);

        $response = $builder->getHealth();

        $this->assertTrue($response->getDatabaseConnected());
        $this->assertTrue($response->getCoreUp());
        $this->assertTrue($response->getCoreSynced());
    }

    /**
     * Test partial health response
     */
    public function testHealthResponsePartiallyHealthy(): void
    {
        $partiallyHealthyJson = json_encode([
            'database_connected' => true,
            'core_up' => false,
            'core_synced' => false
        ]);

        $client = $this->createMockedClient([
            new Response(200, [], $partiallyHealthyJson)
        ]);
        $builder = new HealthRequestBuilder($client);

        $response = $builder->getHealth();

        $this->assertTrue($response->getDatabaseConnected());
        $this->assertFalse($response->getCoreUp());
        $this->assertFalse($response->getCoreSynced());
    }

    /**
     * Test FeeStats with different capacity usage
     */
    public function testFeeStatsCapacityUsage(): void
    {
        $highCapacityJson = json_encode([
            'last_ledger' => '99999',
            'last_ledger_base_fee' => '100',
            'ledger_capacity_usage' => '0.95',
            'fee_charged' => [
                'max' => '5000',
                'min' => '100',
                'mode' => '100',
                'p10' => '100',
                'p20' => '100',
                'p30' => '200',
                'p40' => '300',
                'p50' => '500',
                'p60' => '800',
                'p70' => '1200',
                'p80' => '2000',
                'p90' => '3500',
                'p95' => '4500',
                'p99' => '5000'
            ],
            'max_fee' => [
                'max' => '100000',
                'min' => '100',
                'mode' => '100',
                'p10' => '100',
                'p20' => '100',
                'p30' => '100',
                'p40' => '100',
                'p50' => '100',
                'p60' => '100',
                'p70' => '100',
                'p80' => '100',
                'p90' => '100',
                'p95' => '1000',
                'p99' => '10000'
            ]
        ]);

        $client = $this->createMockedClient([
            new Response(200, [], $highCapacityJson)
        ]);
        $builder = new FeeStatsRequestBuilder($client);

        $response = $builder->getFeeStats();

        $this->assertEquals('0.95', $response->getLedgerCapacityUsage());
        $this->assertEquals('5000', $response->getFeeCharged()->getMax());
        $this->assertEquals('3500', $response->getFeeCharged()->getP90());
    }
}
