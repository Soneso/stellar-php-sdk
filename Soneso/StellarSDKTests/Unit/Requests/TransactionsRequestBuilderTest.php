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
use Soneso\StellarSDK\Requests\TransactionsRequestBuilder;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionsPageResponse;

/**
 * Unit tests for TransactionsRequestBuilder
 *
 * Tests URL building, parameter setting, filtering, and response parsing
 * for the transactions endpoint in Horizon.
 */
class TransactionsRequestBuilderTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
    private const TEST_TRANSACTION_HASH = '5ebd5c0af4385500b53dd63b0ef5f6e8feef1a7e1c0b5a8e6e7f3f84c1f7f1c4';
    private const TEST_LEDGER_SEQ = '1234567';
    private const TEST_CLAIMABLE_BALANCE_ID = 'dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7';
    private const TEST_LIQUIDITY_POOL_ID = 'dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7';
    private const TEST_CURSOR = '123456789';

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
     * Helper method to get sample transaction JSON response
     */
    private function getSampleTransactionJson(): string
    {
        return json_encode([
            'memo' => 'Test transaction',
            'memo_bytes' => 'VGVzdCB0cmFuc2FjdGlvbg==',
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/transactions/' . self::TEST_TRANSACTION_HASH],
                'account' => ['href' => 'https://horizon-testnet.stellar.org/accounts/' . self::TEST_ACCOUNT_ID],
                'ledger' => ['href' => 'https://horizon-testnet.stellar.org/ledgers/52429011'],
                'operations' => ['href' => 'https://horizon-testnet.stellar.org/transactions/' . self::TEST_TRANSACTION_HASH . '/operations{?cursor,limit,order}', 'templated' => true],
                'effects' => ['href' => 'https://horizon-testnet.stellar.org/transactions/' . self::TEST_TRANSACTION_HASH . '/effects{?cursor,limit,order}', 'templated' => true],
                'precedes' => ['href' => 'https://horizon-testnet.stellar.org/transactions?order=asc&cursor=225180887607500800'],
                'succeeds' => ['href' => 'https://horizon-testnet.stellar.org/transactions?order=desc&cursor=225180887607500800'],
                'transaction' => ['href' => 'https://horizon-testnet.stellar.org/transactions/' . self::TEST_TRANSACTION_HASH]
            ],
            'id' => self::TEST_TRANSACTION_HASH,
            'paging_token' => '225180887607500800',
            'successful' => true,
            'hash' => self::TEST_TRANSACTION_HASH,
            'ledger' => 52429011,
            'created_at' => '2024-07-05T05:51:31Z',
            'source_account' => 'GANGI6CEX7L52QPPH5MK2SDZE5WDESSO24HIMVHO5FCIGKQWKLAF5E7O',
            'source_account_sequence' => '224884019467125027',
            'fee_account' => 'GANGI6CEX7L52QPPH5MK2SDZE5WDESSO24HIMVHO5FCIGKQWKLAF5E7O',
            'fee_charged' => '100',
            'max_fee' => '100',
            'operation_count' => 1,
            'envelope_xdr' => 'AAAAAgAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAGQDHvLTAAABIwAAAAEAAAAAAAAAAAAAAABmh4pDAAAAAQAAABgwLDA3NSUgRGFpbHkgZm9yIEhvbGRlcnMAAAABAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAABaG1GYAAAAAAAAAAKpYmInAAAAQLmth39Fjo8TC05wn5ZOAw4lou2rkxAaK6k16lHYXlEcsYHZ/d+ga5bCgO9KV/sbKaZAUCC9KvFIplXkXffBxQ0WUsBeAAAAQC2w45T3S24shkJ7uyRl/P5xD86Xfi7qTYxmb8uh8PEcwlb5oqbnJcTlUV2uJs2+gzMlijNtAbrCm6wO+1YsJQ4=',
            'result_xdr' => 'AAAAAAAAAGQAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAA=',
            'result_meta_xdr' => 'AAAAAwAAAAAAAAACAAAAAwMgANMAAAAAAAAAABpkeES/191B7z9YrUh5J2wySk7XDoZU7ulEgyoWUsBeAAAAAAHJUdQDHvLTAAABIgAAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAAAAAAAAAAAAAAAAAAMAAAAAAyAAyAAAAABmh4mjAAAAAAAAAAEDIADTAAAAAAAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAAAByVHUAx7y0wAAASMAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgANMAAAAAZoeJ4wAAAAAAAAABAAAABAAAAAMDIADTAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAKTCBRhynx//////////wAAAAEAAAABAAAA1xsdUZsAAAAAAAAAAAAAAAAAAAAAAAAAAQMgANMAAAABAAAAAEOS3eq8CQUgHPBA1RMi5Vg7qVqRYg/+phWaqHCpYmInAAAAAUhVTgAAAAAAYqvLVFIr7zrouOae2L2ZCcrBlj9GH/FhAvIptvt76+QAApMHukZ45H//////////AAAAAQAAAAEAAADXGx1RmwAAAAAAAAAAAAAAAAAAAAAAAAADAx/LTQAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAdVOSfYYf/////////8AAAABAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEDIADTAAAAAQAAAAAsqHb+7EzH/s5AbXo3k6x2lY+XRkZJ4MhWlZCqw5XbAgAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAAB1ahlR7B//////////wAAAAEAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            'fee_meta_xdr' => 'AAAAAgAAAAMDIADIAAAAAAAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAAAByVI4Ax7y0wAAASIAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgAMgAAAAAZoeJowAAAAAAAAABAyAA0wAAAAAAAAAAGmR4RL/X3UHvP1itSHknbDJKTtcOhlTu6USDKhZSwF4AAAAAAclR1AMe8tMAAAEiAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAACAAAAAAAAAAAAAAAAAAAAAwAAAAADIADIAAAAAGaHiaMAAAAA',
            'memo_type' => 'text',
            'signatures' => [
                'ua2Hf0WOjxMLTnCflk4DDiWi7auTEBorqTXqUdheURyxgdn936BrlsKA70pX+xsppkBQIL0q8UimVeRd98HFDQ==',
                'LbDjlPdLbiyGQnu7JGX8/nEPzpd+LupNjGZvy6Hw8RzCVvmipuclxOVRXa4mzb6DMyWKM20BusKbrA77ViwlDg=='
            ],
            'valid_after' => '1970-01-01T00:00:00Z',
            'valid_before' => '2024-07-05T05:53:07Z',
            'preconditions' => [
                'timebounds' => [
                    'min_time' => '0',
                    'max_time' => '1720158787'
                ]
            ]
        ]);
    }

    /**
     * Helper method to get sample transactions page JSON response
     */
    private function getSampleTransactionsPageJson(): string
    {
        $transaction = json_decode($this->getSampleTransactionJson(), true);
        return json_encode([
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/transactions?cursor=&limit=10&order=asc'],
                'next' => ['href' => 'https://horizon-testnet.stellar.org/transactions?cursor=123456&limit=10&order=asc'],
                'prev' => ['href' => 'https://horizon-testnet.stellar.org/transactions?cursor=123456&limit=10&order=desc']
            ],
            '_embedded' => [
                'records' => [$transaction]
            ]
        ]);
    }

    /**
     * Test basic URL building for transactions endpoint
     */
    public function testBuildBasicUrl(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new TransactionsRequestBuilder($client);

        $url = $builder->buildUrl();

        $this->assertStringContainsString('transactions?', $url);
    }

    /**
     * Test cursor parameter setting
     */
    public function testCursorParameter(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new TransactionsRequestBuilder($client);

        $builder->cursor(self::TEST_CURSOR);
        $url = $builder->buildUrl();

        $this->assertStringContainsString('cursor=' . self::TEST_CURSOR, $url);
    }

    /**
     * Test cursor with "now" value
     */
    public function testCursorNow(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new TransactionsRequestBuilder($client);

        $builder->cursor('now');
        $url = $builder->buildUrl();

        $this->assertStringContainsString('cursor=now', $url);
    }

    /**
     * Test limit parameter setting
     */
    public function testLimitParameter(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new TransactionsRequestBuilder($client);

        $builder->limit(50);
        $url = $builder->buildUrl();

        $this->assertStringContainsString('limit=50', $url);
    }

    /**
     * Test order parameter setting
     */
    public function testOrderParameter(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new TransactionsRequestBuilder($client);

        $builder->order('desc');
        $url = $builder->buildUrl();

        $this->assertStringContainsString('order=desc', $url);
    }

    /**
     * Test order parameter defaults to asc
     */
    public function testOrderParameterDefaultsToAsc(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new TransactionsRequestBuilder($client);

        $builder->order();
        $url = $builder->buildUrl();

        $this->assertStringContainsString('order=asc', $url);
    }

    /**
     * Test multiple parameters combined
     */
    public function testMultipleParameters(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new TransactionsRequestBuilder($client);

        $builder->cursor(self::TEST_CURSOR)
                ->limit(100)
                ->order('desc');
        $url = $builder->buildUrl();

        $this->assertStringContainsString('cursor=' . self::TEST_CURSOR, $url);
        $this->assertStringContainsString('limit=100', $url);
        $this->assertStringContainsString('order=desc', $url);
    }

    /**
     * Test includeFailed parameter set to true
     */
    public function testIncludeFailedTrue(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new TransactionsRequestBuilder($client);

        $builder->includeFailed(true);
        $url = $builder->buildUrl();

        $this->assertStringContainsString('include_failed=true', $url);
    }

    /**
     * Test includeFailed parameter set to false
     */
    public function testIncludeFailedFalse(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new TransactionsRequestBuilder($client);

        $builder->includeFailed(false);
        $url = $builder->buildUrl();

        $this->assertStringContainsString('include_failed=false', $url);
    }

    /**
     * Test forAccount filter
     */
    public function testForAccount(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new TransactionsRequestBuilder($client);

        $builder->forAccount(self::TEST_ACCOUNT_ID);
        $url = $builder->buildUrl();

        $this->assertStringContainsString('accounts/' . self::TEST_ACCOUNT_ID . '/transactions', $url);
    }

    /**
     * Test forLedger filter
     */
    public function testForLedger(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new TransactionsRequestBuilder($client);

        $builder->forLedger(self::TEST_LEDGER_SEQ);
        $url = $builder->buildUrl();

        $this->assertStringContainsString('ledgers/' . self::TEST_LEDGER_SEQ . '/transactions', $url);
    }

    /**
     * Test forClaimableBalance filter with hex ID
     */
    public function testForClaimableBalanceHex(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new TransactionsRequestBuilder($client);

        $builder->forClaimableBalance(self::TEST_CLAIMABLE_BALANCE_ID);
        $url = $builder->buildUrl();

        $this->assertStringContainsString('claimable_balances/' . self::TEST_CLAIMABLE_BALANCE_ID . '/transactions', $url);
    }

    /**
     * Test forLiquidityPool filter with hex ID
     */
    public function testForLiquidityPoolHex(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new TransactionsRequestBuilder($client);

        $builder->forLiquidityPool(self::TEST_LIQUIDITY_POOL_ID);
        $url = $builder->buildUrl();

        $this->assertStringContainsString('liquidity_pools/' . self::TEST_LIQUIDITY_POOL_ID . '/transactions', $url);
    }

    /**
     * Test transaction method with mocked response
     */
    public function testTransaction(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleTransactionJson())
        ]);
        $builder = new TransactionsRequestBuilder($client);

        $response = $builder->transaction(self::TEST_TRANSACTION_HASH);

        $this->assertInstanceOf(TransactionResponse::class, $response);
        $this->assertEquals(self::TEST_TRANSACTION_HASH, $response->getHash());
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('GANGI6CEX7L52QPPH5MK2SDZE5WDESSO24HIMVHO5FCIGKQWKLAF5E7O', $response->getSourceAccount());
    }

    /**
     * Test execute method returns paginated transactions
     */
    public function testExecute(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleTransactionsPageJson())
        ]);
        $builder = new TransactionsRequestBuilder($client);

        $response = $builder->limit(10)->execute();

        $this->assertInstanceOf(TransactionsPageResponse::class, $response);
        $transactions = $response->getTransactions();
        $this->assertNotNull($transactions);
        $this->assertIsArray($transactions->toArray());
        $this->assertGreaterThan(0, count($transactions->toArray()));
    }

    /**
     * Test request method with full URL
     */
    public function testRequest(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleTransactionsPageJson())
        ]);
        $builder = new TransactionsRequestBuilder($client);

        $response = $builder->request('https://horizon-testnet.stellar.org/transactions?limit=10');

        $this->assertInstanceOf(TransactionsPageResponse::class, $response);
    }

    /**
     * Test forAccount with pagination
     */
    public function testForAccountWithPagination(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleTransactionsPageJson())
        ]);
        $builder = new TransactionsRequestBuilder($client);

        $response = $builder->forAccount(self::TEST_ACCOUNT_ID)
                            ->limit(20)
                            ->order('desc')
                            ->execute();

        $this->assertInstanceOf(TransactionsPageResponse::class, $response);
    }

    /**
     * Test forAccount with includeFailed
     */
    public function testForAccountWithIncludeFailed(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleTransactionsPageJson())
        ]);
        $builder = new TransactionsRequestBuilder($client);

        $builder->forAccount(self::TEST_ACCOUNT_ID)
                ->includeFailed(true);
        $url = $builder->buildUrl();

        $this->assertStringContainsString('accounts/' . self::TEST_ACCOUNT_ID . '/transactions', $url);
        $this->assertStringContainsString('include_failed=true', $url);
    }

    /**
     * Test forLedger with pagination
     */
    public function testForLedgerWithPagination(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleTransactionsPageJson())
        ]);
        $builder = new TransactionsRequestBuilder($client);

        $response = $builder->forLedger(self::TEST_LEDGER_SEQ)
                            ->limit(50)
                            ->order('asc')
                            ->execute();

        $this->assertInstanceOf(TransactionsPageResponse::class, $response);
    }

    /**
     * Test forClaimableBalance with pagination
     */
    public function testForClaimableBalanceWithPagination(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleTransactionsPageJson())
        ]);
        $builder = new TransactionsRequestBuilder($client);

        $response = $builder->forClaimableBalance(self::TEST_CLAIMABLE_BALANCE_ID)
                            ->limit(25)
                            ->execute();

        $this->assertInstanceOf(TransactionsPageResponse::class, $response);
    }

    /**
     * Test forLiquidityPool with pagination
     */
    public function testForLiquidityPoolWithPagination(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleTransactionsPageJson())
        ]);
        $builder = new TransactionsRequestBuilder($client);

        $response = $builder->forLiquidityPool(self::TEST_LIQUIDITY_POOL_ID)
                            ->limit(30)
                            ->execute();

        $this->assertInstanceOf(TransactionsPageResponse::class, $response);
    }

    /**
     * Test URL building for transaction method
     */
    public function testTransactionUrlBuilding(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleTransactionJson())
        ]);
        $builder = new TransactionsRequestBuilder($client);

        // This will build the URL internally
        $builder->transaction(self::TEST_TRANSACTION_HASH);

        // If we got here without exception, URL building worked
        $this->assertTrue(true);
    }

    /**
     * Test method chaining returns correct instance
     */
    public function testMethodChaining(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new TransactionsRequestBuilder($client);

        $result = $builder->cursor(self::TEST_CURSOR);
        $this->assertInstanceOf(TransactionsRequestBuilder::class, $result);

        $result = $builder->limit(50);
        $this->assertInstanceOf(TransactionsRequestBuilder::class, $result);

        $result = $builder->order('desc');
        $this->assertInstanceOf(TransactionsRequestBuilder::class, $result);

        $result = $builder->includeFailed(true);
        $this->assertInstanceOf(TransactionsRequestBuilder::class, $result);

        $result = $builder->forAccount(self::TEST_ACCOUNT_ID);
        $this->assertInstanceOf(TransactionsRequestBuilder::class, $result);
    }

    /**
     * Test all parameters combined
     */
    public function testAllParametersCombined(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleTransactionsPageJson())
        ]);
        $builder = new TransactionsRequestBuilder($client);

        $response = $builder->forAccount(self::TEST_ACCOUNT_ID)
                            ->cursor(self::TEST_CURSOR)
                            ->limit(15)
                            ->order('desc')
                            ->includeFailed(true)
                            ->execute();

        $this->assertInstanceOf(TransactionsPageResponse::class, $response);
    }

    /**
     * Test streaming cursor with "now"
     */
    public function testStreamingCursorNow(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new TransactionsRequestBuilder($client);

        $builder->cursor('now');
        $url = $builder->buildUrl();

        $this->assertStringContainsString('cursor=now', $url);
        $this->assertStringContainsString('transactions?', $url);
    }

    /**
     * Test transaction response parsing
     */
    public function testTransactionResponseParsing(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleTransactionJson())
        ]);
        $builder = new TransactionsRequestBuilder($client);

        $response = $builder->transaction(self::TEST_TRANSACTION_HASH);

        $this->assertEquals(self::TEST_TRANSACTION_HASH, $response->getHash());
        $this->assertEquals(52429011, $response->getLedger());
        $this->assertEquals('GANGI6CEX7L52QPPH5MK2SDZE5WDESSO24HIMVHO5FCIGKQWKLAF5E7O', $response->getSourceAccount());
        $this->assertEquals('224884019467125027', $response->getSourceAccountSequence());
        $this->assertEquals('100', $response->getFeeCharged());
        $this->assertEquals('100', $response->getMaxFee());
        $this->assertEquals(1, $response->getOperationCount());
        $memo = $response->getMemo();
        $this->assertNotNull($memo);
        $this->assertEquals('Test transaction', $memo->getValue());
    }

    /**
     * Test transactions page response parsing
     */
    public function testTransactionsPageResponseParsing(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleTransactionsPageJson())
        ]);
        $builder = new TransactionsRequestBuilder($client);

        $response = $builder->execute();

        $this->assertInstanceOf(TransactionsPageResponse::class, $response);
        $transactions = $response->getTransactions();
        $this->assertNotNull($transactions);

        $txArray = $transactions->toArray();
        $this->assertCount(1, $txArray);

        $firstTx = $txArray[0];
        $this->assertEquals(self::TEST_TRANSACTION_HASH, $firstTx->getHash());
        $this->assertTrue($firstTx->isSuccessful());
    }

    /**
     * Test empty parameters
     */
    public function testEmptyParameters(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleTransactionsPageJson())
        ]);
        $builder = new TransactionsRequestBuilder($client);

        // Should work with no parameters set
        $response = $builder->execute();

        $this->assertInstanceOf(TransactionsPageResponse::class, $response);
    }
}
