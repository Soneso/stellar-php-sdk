<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Requests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Responses\Effects\EffectsPageResponse;
use Soneso\StellarSDK\Responses\Effects\EffectResponse;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

class EffectsRequestBuilderTest extends TestCase
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

    private function createMockEffectsPageResponse(int $count = 2): array
    {
        $records = [];
        for ($i = 0; $i < $count; $i++) {
            $records[] = [
                'id' => '0000123456789-' . $i,
                'paging_token' => '123456789-' . $i,
                'account' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
                'type' => 'account_debited',
                'type_i' => 3,
                'created_at' => '2025-01-20T10:00:00Z',
                'asset_type' => 'native',
                'amount' => '100.0000000',
                '_links' => [
                    'operation' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456789'],
                    'succeeds' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=desc&cursor=123456789-' . $i],
                    'precedes' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=asc&cursor=123456789-' . $i]
                ]
            ];
        }

        return [
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/effects'],
                'next' => ['href' => 'https://horizon-testnet.stellar.org/effects?cursor=next'],
                'prev' => ['href' => 'https://horizon-testnet.stellar.org/effects?cursor=prev']
            ],
            '_embedded' => [
                'records' => $records
            ]
        ];
    }

    public function testForAccount(): void
    {
        $mockResponse = $this->createMockEffectsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()
            ->forAccount('GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H')
            ->execute();

        $this->assertInstanceOf(EffectsPageResponse::class, $result);
        $this->assertCount(2, $result->getEffects()->toArray());
    }

    public function testForLedger(): void
    {
        $mockResponse = $this->createMockEffectsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()
            ->forLedger('123456')
            ->execute();

        $this->assertInstanceOf(EffectsPageResponse::class, $result);
        $this->assertCount(2, $result->getEffects()->toArray());
    }

    public function testForTransaction(): void
    {
        $mockResponse = $this->createMockEffectsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()
            ->forTransaction('abc123def456789')
            ->execute();

        $this->assertInstanceOf(EffectsPageResponse::class, $result);
        $this->assertCount(2, $result->getEffects()->toArray());
    }

    public function testForOperation(): void
    {
        $mockResponse = $this->createMockEffectsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()
            ->forOperation('123456789')
            ->execute();

        $this->assertInstanceOf(EffectsPageResponse::class, $result);
        $this->assertCount(2, $result->getEffects()->toArray());
    }

    public function testForLiquidityPoolWithHex(): void
    {
        $mockResponse = $this->createMockEffectsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()
            ->forLiquidityPool('dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7')
            ->execute();

        $this->assertInstanceOf(EffectsPageResponse::class, $result);
    }

    public function testCursor(): void
    {
        $mockResponse = $this->createMockEffectsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()
            ->cursor('123456789-1')
            ->execute();

        $this->assertInstanceOf(EffectsPageResponse::class, $result);
    }

    public function testCursorNow(): void
    {
        $mockResponse = $this->createMockEffectsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()
            ->cursor('now')
            ->execute();

        $this->assertInstanceOf(EffectsPageResponse::class, $result);
    }

    public function testLimit(): void
    {
        $mockResponse = $this->createMockEffectsPageResponse(10);

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()
            ->limit(10)
            ->execute();

        $this->assertInstanceOf(EffectsPageResponse::class, $result);
        $this->assertCount(10, $result->getEffects()->toArray());
    }

    public function testOrderAscending(): void
    {
        $mockResponse = $this->createMockEffectsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()
            ->order('asc')
            ->execute();

        $this->assertInstanceOf(EffectsPageResponse::class, $result);
    }

    public function testOrderDescending(): void
    {
        $mockResponse = $this->createMockEffectsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()
            ->order('desc')
            ->execute();

        $this->assertInstanceOf(EffectsPageResponse::class, $result);
    }

    public function testRequest(): void
    {
        $mockResponse = $this->createMockEffectsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()->request('effects?limit=20');

        $this->assertInstanceOf(EffectsPageResponse::class, $result);
    }

    public function testExecute(): void
    {
        $mockResponse = $this->createMockEffectsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()->execute();

        $this->assertInstanceOf(EffectsPageResponse::class, $result);
        $this->assertCount(2, $result->getEffects()->toArray());
    }

    public function testMethodChaining(): void
    {
        $mockResponse = $this->createMockEffectsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()
            ->forAccount('GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H')
            ->cursor('123456789-1')
            ->limit(50)
            ->order('desc')
            ->execute();

        $this->assertInstanceOf(EffectsPageResponse::class, $result);
    }

    public function testPaginationLinks(): void
    {
        $mockResponse = $this->createMockEffectsPageResponse();

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()->execute();

        $this->assertInstanceOf(EffectsPageResponse::class, $result);
        $this->assertCount(2, $result->getEffects()->toArray());
    }

    public function testEffectTypes(): void
    {
        $mockResponse = [
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/effects'],
                'next' => ['href' => 'https://horizon-testnet.stellar.org/effects?cursor=next'],
                'prev' => ['href' => 'https://horizon-testnet.stellar.org/effects?cursor=prev']
            ],
            '_embedded' => [
                'records' => [
                    [
                        'id' => '0000123456789-1',
                        'paging_token' => '123456789-1',
                        'account' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
                        'type' => 'account_created',
                        'type_i' => 0,
                        'created_at' => '2025-01-20T10:00:00Z',
                        'starting_balance' => '1000.0000000',
                        '_links' => [
                            'operation' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456789'],
                            'succeeds' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=desc&cursor=123456789-1'],
                            'precedes' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=asc&cursor=123456789-1']
                        ]
                    ],
                    [
                        'id' => '0000123456789-2',
                        'paging_token' => '123456789-2',
                        'account' => 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H',
                        'type' => 'account_credited',
                        'type_i' => 2,
                        'created_at' => '2025-01-20T10:00:00Z',
                        'asset_type' => 'native',
                        'amount' => '100.0000000',
                        '_links' => [
                            'operation' => ['href' => 'https://horizon-testnet.stellar.org/operations/123456789'],
                            'succeeds' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=desc&cursor=123456789-2'],
                            'precedes' => ['href' => 'https://horizon-testnet.stellar.org/effects?order=asc&cursor=123456789-2']
                        ]
                    ]
                ]
            ]
        ];

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()->execute();

        $effects = $result->getEffects()->toArray();
        $this->assertCount(2, $effects);
        $this->assertEquals('account_created', $effects[0]->getHumanReadableEffectType());
        $this->assertEquals('account_credited', $effects[1]->getHumanReadableEffectType());
    }

    public function testEmptyResponse(): void
    {
        $mockResponse = [
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/effects'],
                'next' => ['href' => 'https://horizon-testnet.stellar.org/effects?cursor=next'],
                'prev' => ['href' => 'https://horizon-testnet.stellar.org/effects?cursor=prev']
            ],
            '_embedded' => [
                'records' => []
            ]
        ];

        $sdk = $this->createMockedSdk([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $sdk->effects()->execute();

        $this->assertInstanceOf(EffectsPageResponse::class, $result);
        $this->assertCount(0, $result->getEffects()->toArray());
    }
}
