<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Requests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Operations\OperationsPageResponse;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\Responses\Operations\CreateAccountOperationResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class PaymentsRequestBuilderTest extends TestCase
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

    private function createMockPaymentsPageResponse(int $count = 2): array
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
                'self' => ['href' => 'https://horizon-testnet.stellar.org/payments'],
                'next' => ['href' => 'https://horizon-testnet.stellar.org/payments?cursor=next'],
                'prev' => ['href' => 'https://horizon-testnet.stellar.org/payments?cursor=prev']
            ],
            '_embedded' => [
                'records' => $records
            ]
        ];
    }

    public function testForAccount(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()
            ->forAccount('GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H')
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
        $this->assertCount(2, $result->getOperations()->toArray());
    }

    public function testForLedger(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()
            ->forLedger('123456')
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
        $this->assertCount(2, $result->getOperations()->toArray());
    }

    public function testForTransaction(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()
            ->forTransaction('abc123def456789')
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
        $this->assertCount(2, $result->getOperations()->toArray());
    }

    public function testIncludeTransactions(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()
            ->forAccount('GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H')
            ->includeTransactions(true)
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testIncludeTransactionsToggle(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()
            ->includeTransactions(true)
            ->includeTransactions(false)
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testIncludeFailed(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()
            ->forAccount('GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H')
            ->includeFailed(true)
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testIncludeFailedFalse(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()
            ->forAccount('GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H')
            ->includeFailed(false)
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testCursor(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()
            ->cursor('123456789')
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testCursorNow(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()
            ->cursor('now')
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testLimit(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse(15);

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()
            ->limit(15)
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
        $this->assertCount(15, $result->getOperations()->toArray());
    }

    public function testOrderAscending(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()
            ->order('asc')
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testOrderDescending(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()
            ->order('desc')
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testRequest(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()->request('payments?limit=25');

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testExecute(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
        $this->assertCount(2, $result->getOperations()->toArray());
    }

    public function testMethodChaining(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()
            ->forAccount('GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H')
            ->cursor('123456789')
            ->limit(20)
            ->order('desc')
            ->includeFailed(true)
            ->includeTransactions(true)
            ->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
    }

    public function testPaginationLinks(): void
    {
        $mockResponse = $this->createMockPaymentsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
        $this->assertCount(2, $result->getOperations()->toArray());
    }

    public function testPaymentTypes(): void
    {
        $mockResponse = [
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/payments'],
                'next' => ['href' => 'https://horizon-testnet.stellar.org/payments?cursor=next'],
                'prev' => ['href' => 'https://horizon-testnet.stellar.org/payments?cursor=prev']
            ],
            '_embedded' => [
                'records' => [
                    [
                        'id' => '123456789',
                        'paging_token' => '1234567890',
                        'transaction_successful' => true,
                        'source_account' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
                        'type' => 'create_account',
                        'type_i' => 0,
                        'created_at' => '2025-01-20T10:00:00Z',
                        'transaction_hash' => 'abc123def456',
                        'starting_balance' => '1000.0000000',
                        'funder' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
                        'account' => 'GBQECQVAS2FJ7DLCUXDASZAJQLWPXNTCR2FXSCTV2ATHWKIE6T7MKBU4',
                        '_links' => [
                            'self' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456789'],
                            'transaction' => ['href' => 'https://horizon-testnet.stellar.org/transactions/abc123def456'],
                            'effects' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456789/effects'],
                            'succeeds' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=desc&cursor=123456789'],
                            'precedes' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=asc&cursor=123456789']
                        ]
                    ],
                    [
                        'id' => '123456790',
                        'paging_token' => '1234567891',
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
                            'self' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456790'],
                            'transaction' => ['href' => 'https://horizon-testnet.stellar.org/transactions/abc123def456'],
                            'effects' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456790/effects'],
                            'succeeds' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=desc&cursor=123456790'],
                            'precedes' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=asc&cursor=123456790']
                        ]
                    ]
                ]
            ]
        ];

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()->execute();

        $operations = $result->getOperations()->toArray();
        $this->assertCount(2, $operations);
        $this->assertEquals('create_account', $operations[0]->getHumanReadableOperationType());
        $this->assertEquals('payment', $operations[1]->getHumanReadableOperationType());
    }

    public function testPathPaymentTypes(): void
    {
        $mockResponse = [
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/payments'],
                'next' => ['href' => 'https://horizon-testnet.stellar.org/payments?cursor=next'],
                'prev' => ['href' => 'https://horizon-testnet.stellar.org/payments?cursor=prev']
            ],
            '_embedded' => [
                'records' => [
                    [
                        'id' => '123456789',
                        'paging_token' => '1234567890',
                        'transaction_successful' => true,
                        'source_account' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
                        'type' => 'path_payment_strict_send',
                        'type_i' => 13,
                        'created_at' => '2025-01-20T10:00:00Z',
                        'transaction_hash' => 'abc123def456',
                        'asset_type' => 'native',
                        'from' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
                        'to' => 'GBQECQVAS2FJ7DLCUXDASZAJQLWPXNTCR2FXSCTV2ATHWKIE6T7MKBU4',
                        'amount' => '100.0000000',
                        'source_amount' => '100.0000000',
                        'source_max' => '100.0000000',
                        'source_asset_type' => 'native',
                        '_links' => [
                            'self' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456789'],
                            'transaction' => ['href' => 'https://horizon-testnet.stellar.org/transactions/abc123def456'],
                            'effects' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456789/effects'],
                            'succeeds' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=desc&cursor=123456789'],
                            'precedes' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=asc&cursor=123456789']
                        ]
                    ],
                    [
                        'id' => '123456790',
                        'paging_token' => '1234567891',
                        'transaction_successful' => true,
                        'source_account' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
                        'type' => 'path_payment_strict_receive',
                        'type_i' => 2,
                        'created_at' => '2025-01-20T10:00:00Z',
                        'transaction_hash' => 'abc123def456',
                        'asset_type' => 'native',
                        'from' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
                        'to' => 'GBQECQVAS2FJ7DLCUXDASZAJQLWPXNTCR2FXSCTV2ATHWKIE6T7MKBU4',
                        'amount' => '100.0000000',
                        'source_amount' => '100.0000000',
                        'source_max' => '100.0000000',
                        'source_asset_type' => 'native',
                        '_links' => [
                            'self' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456790'],
                            'transaction' => ['href' => 'https://horizon-testnet.stellar.org/transactions/abc123def456'],
                            'effects' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456790/effects'],
                            'succeeds' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=desc&cursor=123456790'],
                            'precedes' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=asc&cursor=123456790']
                        ]
                    ]
                ]
            ]
        ];

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()->execute();

        $operations = $result->getOperations()->toArray();
        $this->assertCount(2, $operations);
        $this->assertEquals('path_payment_strict_send', $operations[0]->getHumanReadableOperationType());
        $this->assertEquals('path_payment_strict_receive', $operations[1]->getHumanReadableOperationType());
    }

    public function testEmptyResponse(): void
    {
        $mockResponse = [
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/payments'],
                'next' => ['href' => 'https://horizon-testnet.stellar.org/payments?cursor=next'],
                'prev' => ['href' => 'https://horizon-testnet.stellar.org/payments?cursor=prev']
            ],
            '_embedded' => [
                'records' => []
            ]
        ];

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->payments()->execute();

        $this->assertInstanceOf(OperationsPageResponse::class, $result);
        $this->assertCount(0, $result->getOperations()->toArray());
    }
}
