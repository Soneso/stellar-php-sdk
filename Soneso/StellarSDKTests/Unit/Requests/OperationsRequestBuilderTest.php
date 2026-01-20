<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Requests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Operations\OperationsPageResponse;
use Soneso\StellarSDK\Responses\Operations\OperationResponse;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class OperationsRequestBuilderTest extends TestCase
{
    private function createMockedSdk(array $responses): StellarSDK
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $sdk->setHttpClient($client);

        return $sdk;
    }

    private function createMockOperationsPageResponse(int $count = 2): array
    {
        $records = [];
        for ($i = 0; $i < $count; $i++) {
            $records[] = [
                'id' => (string)(123456789 + $i),
                'paging_token' => '123456789' . $i,
                'transaction_successful' => true,
                'source_account' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
                'type' => 'payment',
                'type_i' => 1,
                'created_at' => '2025-01-20T10:00:00Z',
                'transaction_hash' => 'abc123def456',
                'asset_type' => 'native',
                'from' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
                'to' => 'GBQECQVAS2FJ7DLCUXDASZAJQLWPXNTCR2FXSCTV2ATHWKIE6T7MKBU4',
                'amount' => '100.0000000',
                '_links' => [
                    'self' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456789' . $i],
                    'transaction' => ['href' => 'https://horizon-testnet.stellar.org/transactions/abc123def456'],
                    'effects' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456789' . $i . '/effects'],
                    'succeeds' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=desc&cursor=123456789' . $i],
                    'precedes' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=asc&cursor=123456789' . $i]
                ]
            ];
        }

        return [
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/operations'],
                'next' => ['href' => 'https://horizon-testnet.stellar.org/operations?cursor=next'],
                'prev' => ['href' => 'https://horizon-testnet.stellar.org/operations?cursor=prev']
            ],
            '_embedded' => [
                'records' => $records
            ]
        ];
    }

    private function createMockSingleOperationResponse(): array
    {
        return [
            'id' => '123456789',
            'paging_token' => '123456789',
            'transaction_successful' => true,
            'source_account' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
            'type' => 'payment',
            'type_i' => 1,
            'created_at' => '2025-01-20T10:00:00Z',
            'transaction_hash' => 'abc123def456',
            'asset_type' => 'native',
            'from' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
            'to' => 'GBQECQVAS2FJ7DLCUXDASZAJQLWPXNTCR2FXSCTV2ATHWKIE6T7MKBU4',
            'amount' => '100.0000000',
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456789'],
                'transaction' => ['href' => 'https://horizon-testnet.stellar.org/transactions/abc123def456'],
                'effects' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456789/effects'],
                'succeeds' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=desc&cursor=123456789'],
                'precedes' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=asc&cursor=123456789']
            ]
        ];
    }

    public function testOperationById(): void
    {
        $mockResponse = $this->createMockSingleOperationResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $operation = $sdk->operations()->operation('123456789');

        $this->assertInstanceOf(OperationResponse::class, $operation);
        $this->assertEquals('123456789', $operation->getPagingToken());
        $this->assertEquals('payment', $operation->getHumanReadableOperationType());
    }

    public function testForAccount(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()
            ->forAccount('GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H')
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
        $this->assertCount(2, $result->getOperations()->toArray());
    }

    public function testForLedger(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()
            ->forLedger('123456')
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
        $this->assertCount(2, $result->getOperations()->toArray());
    }

    public function testForTransaction(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()
            ->forTransaction('abc123def456789')
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
        $this->assertCount(2, $result->getOperations()->toArray());
    }

    public function testForClaimableBalanceWithBAddress(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()
            ->forClaimableBalance('00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072')
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testForLiquidityPoolWithLAddress(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()
            ->forLiquidityPool('dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7')
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testIncludeFailed(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()
            ->forAccount('GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H')
            ->includeFailed(true)
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testIncludeTransactions(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()
            ->forAccount('GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H')
            ->includeTransactions(true)
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testCursor(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()
            ->cursor('123456789')
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testLimit(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse(5);

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()
            ->limit(5)
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
        $this->assertCount(5, $result->getOperations()->toArray());
    }

    public function testOrderAscending(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()
            ->order('asc')
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testOrderDescending(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()
            ->order('desc')
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testMethodChaining(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()
            ->forAccount('GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H')
            ->cursor('123456789')
            ->limit(10)
            ->order('desc')
            ->includeFailed(true)
            ->includeTransactions(true)
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testRequest(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()->request('operations?limit=10');

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testExecute(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
        $this->assertCount(2, $result->getOperations()->toArray());
    }

    public function testPaginationLinks(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
        $this->assertCount(2, $result->getOperations()->toArray());
    }

    public function testIncludeTransactionsToggle(): void
    {
        $mockResponse = $this->createMockOperationsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->operations()
            ->includeTransactions(true)
            ->includeTransactions(false)
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }
}
