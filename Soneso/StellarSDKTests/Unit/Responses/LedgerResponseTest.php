<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Responses;

use PHPUnit\Framework\TestCase;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Responses\Ledger\LedgerResponse;
use Soneso\StellarSDK\Responses\Ledger\LedgerLinksResponse;
use Soneso\StellarSDK\Responses\Ledger\LedgersPageResponse;
use Soneso\StellarSDK\Responses\Ledger\LedgersResponse;

/**
 * Unit tests for all Ledger Response classes
 *
 * Tests JSON parsing and getter methods for Ledger-related response classes.
 * Covers LedgerResponse, LedgerLinksResponse, LedgersPageResponse, and LedgersResponse.
 */
class LedgerResponseTest extends TestCase
{
    /**
     * Helper method to create complete ledger JSON data
     */
    private function getCompleteLedgerJson(): array
    {
        return [
            'id' => '4db1e4f145e9ee75162040d26284795e0697e2e84084624e7c6c723ebbf80118',
            'paging_token' => '12884905984',
            'hash' => '4db1e4f145e9ee75162040d26284795e0697e2e84084624e7c6c723ebbf80118',
            'prev_hash' => '4b0b8bace3b2438b2404776ce57643966855487ba6384724a3c664c7aa4cd9e4',
            'sequence' => '3',
            'successful_transaction_count' => 8,
            'failed_transaction_count' => 2,
            'operation_count' => 23,
            'tx_set_operation_count' => 25,
            'closed_at' => '2015-09-30T17:15:54Z',
            'total_coins' => '100000000000.0000000',
            'fee_pool' => '0.0025900',
            'base_fee_in_stroops' => 100,
            'base_reserve_in_stroops' => 100000000,
            'max_tx_set_size' => 100,
            'protocol_version' => 20,
            'header_xdr' => 'AAAAAgAAAACfbfDS9N4sEp5DPeT+kYCF1OPWVqrGbPT06dIGhzIMWAAAAABmTW8cAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABkAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA',
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/ledgers/3'
                ],
                'transactions' => [
                    'href' => 'https://horizon.stellar.org/ledgers/3/transactions{?cursor,limit,order}',
                    'templated' => true
                ],
                'operations' => [
                    'href' => 'https://horizon.stellar.org/ledgers/3/operations{?cursor,limit,order}',
                    'templated' => true
                ],
                'payments' => [
                    'href' => 'https://horizon.stellar.org/ledgers/3/payments{?cursor,limit,order}',
                    'templated' => true
                ],
                'effects' => [
                    'href' => 'https://horizon.stellar.org/ledgers/3/effects{?cursor,limit,order}',
                    'templated' => true
                ]
            ]
        ];
    }

    /**
     * Helper method to create minimal ledger JSON data
     */
    private function getMinimalLedgerJson(): array
    {
        return [
            'id' => '4db1e4f145e9ee75162040d26284795e0697e2e84084624e7c6c723ebbf80118',
            'paging_token' => '12884905984',
            'hash' => '4db1e4f145e9ee75162040d26284795e0697e2e84084624e7c6c723ebbf80118',
            'sequence' => '3',
            'operation_count' => 23,
            'closed_at' => '2015-09-30T17:15:54Z',
            'total_coins' => '100000000000.0000000',
            'fee_pool' => '0.0025900',
            'base_fee_in_stroops' => 100,
            'base_reserve_in_stroops' => 100000000,
            'max_tx_set_size' => 100,
            'protocol_version' => 20,
            'header_xdr' => 'AAAAAgAAAACfbfDS9N4sEp5DPeT+kYCF1OPWVqrGbPT06dIGhzIMWAAAAABmTW8cAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABkAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA'
        ];
    }

    // LedgerResponse Tests

    public function testLedgerResponseFromJson(): void
    {
        $json = $this->getCompleteLedgerJson();
        $response = LedgerResponse::fromJson($json);

        $this->assertEquals('4db1e4f145e9ee75162040d26284795e0697e2e84084624e7c6c723ebbf80118', $response->getId());
        $this->assertEquals('12884905984', $response->getPagingToken());
        $this->assertEquals('4db1e4f145e9ee75162040d26284795e0697e2e84084624e7c6c723ebbf80118', $response->getHash());
        $this->assertEquals('4b0b8bace3b2438b2404776ce57643966855487ba6384724a3c664c7aa4cd9e4', $response->getPreviousHash());
        $this->assertInstanceOf(BigInteger::class, $response->getSequence());
        $this->assertEquals('3', $response->getSequence()->toString());
        $this->assertEquals(8, $response->getSuccessfulTransactionCount());
        $this->assertEquals(2, $response->getFailedTransactionCount());
        $this->assertEquals(23, $response->getOperationCount());
        $this->assertEquals(25, $response->getTxSetOperationCount());
        $this->assertEquals('2015-09-30T17:15:54Z', $response->getClosedAt());
        $this->assertEquals('100000000000.0000000', $response->getTotalCoins());
        $this->assertEquals('0.0025900', $response->getFeePool());
        $this->assertEquals(100, $response->getBaseFeeInStroops());
        $this->assertEquals(100000000, $response->getBaseReserveInStroops());
        $this->assertEquals(100, $response->getMaxTxSetSize());
        $this->assertEquals(20, $response->getProtocolVersion());
        $this->assertStringStartsWith('AAAAAgAAAACfbfDS9N4sEp5DPeT', $response->getHeaderXdr());
    }

    public function testLedgerResponseLinks(): void
    {
        $json = $this->getCompleteLedgerJson();
        $response = LedgerResponse::fromJson($json);
        $links = $response->getLinks();

        $this->assertInstanceOf(LedgerLinksResponse::class, $links);
        $this->assertEquals('https://horizon.stellar.org/ledgers/3', $links->getSelf()->getHref());
        $this->assertStringContainsString('/transactions', $links->getTransactions()->getHref());
        $this->assertTrue($links->getTransactions()->isTemplated());
        $this->assertStringContainsString('/operations', $links->getOperations()->getHref());
        $this->assertTrue($links->getOperations()->isTemplated());
        $this->assertStringContainsString('/payments', $links->getPayments()->getHref());
        $this->assertTrue($links->getPayments()->isTemplated());
        $this->assertStringContainsString('/effects', $links->getEffects()->getHref());
        $this->assertTrue($links->getEffects()->isTemplated());
    }

    public function testLedgerResponseOptionalFields(): void
    {
        $json = $this->getMinimalLedgerJson();
        $response = LedgerResponse::fromJson($json);

        $this->assertNull($response->getPreviousHash());
        $this->assertNull($response->getSuccessfulTransactionCount());
        $this->assertNull($response->getFailedTransactionCount());
        $this->assertNull($response->getTxSetOperationCount());
    }

    public function testLedgerResponseHighSequenceNumber(): void
    {
        $json = $this->getCompleteLedgerJson();
        $json['sequence'] = '999999999999999999';
        $response = LedgerResponse::fromJson($json);

        $this->assertInstanceOf(BigInteger::class, $response->getSequence());
        $this->assertEquals('999999999999999999', $response->getSequence()->toString());
    }

    public function testLedgerResponseZeroTransactionCounts(): void
    {
        $json = $this->getCompleteLedgerJson();
        $json['successful_transaction_count'] = 0;
        $json['failed_transaction_count'] = 0;
        $response = LedgerResponse::fromJson($json);

        $this->assertEquals(0, $response->getSuccessfulTransactionCount());
        $this->assertEquals(0, $response->getFailedTransactionCount());
    }

    public function testLedgerResponseZeroOperationCount(): void
    {
        $json = $this->getCompleteLedgerJson();
        $json['operation_count'] = 0;
        $json['tx_set_operation_count'] = 0;
        $response = LedgerResponse::fromJson($json);

        $this->assertEquals(0, $response->getOperationCount());
        $this->assertEquals(0, $response->getTxSetOperationCount());
    }

    public function testLedgerResponseHighProtocolVersion(): void
    {
        $json = $this->getCompleteLedgerJson();
        $json['protocol_version'] = 99;
        $response = LedgerResponse::fromJson($json);

        $this->assertEquals(99, $response->getProtocolVersion());
    }

    public function testLedgerResponseLargeFeePool(): void
    {
        $json = $this->getCompleteLedgerJson();
        $json['fee_pool'] = '123456789.9876543';
        $response = LedgerResponse::fromJson($json);

        $this->assertEquals('123456789.9876543', $response->getFeePool());
    }

    public function testLedgerResponseLargeTotalCoins(): void
    {
        $json = $this->getCompleteLedgerJson();
        $json['total_coins'] = '500000000000.0000000';
        $response = LedgerResponse::fromJson($json);

        $this->assertEquals('500000000000.0000000', $response->getTotalCoins());
    }

    public function testLedgerResponseHighBaseFee(): void
    {
        $json = $this->getCompleteLedgerJson();
        $json['base_fee_in_stroops'] = 5000;
        $response = LedgerResponse::fromJson($json);

        $this->assertEquals(5000, $response->getBaseFeeInStroops());
    }

    public function testLedgerResponseHighBaseReserve(): void
    {
        $json = $this->getCompleteLedgerJson();
        $json['base_reserve_in_stroops'] = 500000000;
        $response = LedgerResponse::fromJson($json);

        $this->assertEquals(500000000, $response->getBaseReserveInStroops());
    }

    public function testLedgerResponseLargeMaxTxSetSize(): void
    {
        $json = $this->getCompleteLedgerJson();
        $json['max_tx_set_size'] = 5000;
        $response = LedgerResponse::fromJson($json);

        $this->assertEquals(5000, $response->getMaxTxSetSize());
    }

    public function testLedgerResponseWithoutPreviousHash(): void
    {
        $json = $this->getCompleteLedgerJson();
        unset($json['prev_hash']);
        $response = LedgerResponse::fromJson($json);

        $this->assertNull($response->getPreviousHash());
    }

    public function testLedgerResponseTimestampFormat(): void
    {
        $json = $this->getCompleteLedgerJson();
        $json['closed_at'] = '2024-01-20T12:34:56Z';
        $response = LedgerResponse::fromJson($json);

        $this->assertEquals('2024-01-20T12:34:56Z', $response->getClosedAt());
    }

    public function testLedgerResponseDifferentHeaderXdr(): void
    {
        $json = $this->getCompleteLedgerJson();
        $json['header_xdr'] = 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
        $response = LedgerResponse::fromJson($json);

        $this->assertEquals('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', $response->getHeaderXdr());
    }

    public function testLedgerResponseSequenceNumberOne(): void
    {
        $json = $this->getCompleteLedgerJson();
        $json['sequence'] = '1';
        $response = LedgerResponse::fromJson($json);

        $this->assertEquals('1', $response->getSequence()->toString());
    }

    public function testLedgerResponseAllGetters(): void
    {
        $json = $this->getCompleteLedgerJson();
        $response = LedgerResponse::fromJson($json);

        // Test all getter methods exist and return expected types
        $this->assertIsString($response->getId());
        $this->assertIsString($response->getPagingToken());
        $this->assertIsString($response->getHash());
        $this->assertIsString($response->getPreviousHash());
        $this->assertInstanceOf(BigInteger::class, $response->getSequence());
        $this->assertIsInt($response->getSuccessfulTransactionCount());
        $this->assertIsInt($response->getFailedTransactionCount());
        $this->assertIsInt($response->getOperationCount());
        $this->assertIsInt($response->getTxSetOperationCount());
        $this->assertIsString($response->getClosedAt());
        $this->assertIsString($response->getTotalCoins());
        $this->assertIsString($response->getFeePool());
        $this->assertIsInt($response->getBaseFeeInStroops());
        $this->assertIsInt($response->getBaseReserveInStroops());
        $this->assertIsInt($response->getMaxTxSetSize());
        $this->assertIsInt($response->getProtocolVersion());
        $this->assertIsString($response->getHeaderXdr());
        $this->assertInstanceOf(LedgerLinksResponse::class, $response->getLinks());
    }

    // LedgerLinksResponse Tests

    public function testLedgerLinksResponseFromJson(): void
    {
        $json = [
            'self' => [
                'href' => 'https://horizon.stellar.org/ledgers/3'
            ],
            'transactions' => [
                'href' => 'https://horizon.stellar.org/ledgers/3/transactions{?cursor,limit,order}',
                'templated' => true
            ],
            'operations' => [
                'href' => 'https://horizon.stellar.org/ledgers/3/operations{?cursor,limit,order}',
                'templated' => true
            ],
            'payments' => [
                'href' => 'https://horizon.stellar.org/ledgers/3/payments{?cursor,limit,order}',
                'templated' => true
            ],
            'effects' => [
                'href' => 'https://horizon.stellar.org/ledgers/3/effects{?cursor,limit,order}',
                'templated' => true
            ]
        ];

        $links = LedgerLinksResponse::fromJson($json);

        $this->assertEquals('https://horizon.stellar.org/ledgers/3', $links->getSelf()->getHref());
        $this->assertStringContainsString('/transactions', $links->getTransactions()->getHref());
        $this->assertStringContainsString('/operations', $links->getOperations()->getHref());
        $this->assertStringContainsString('/payments', $links->getPayments()->getHref());
        $this->assertStringContainsString('/effects', $links->getEffects()->getHref());
    }

    public function testLedgerLinksResponseTemplatedLinks(): void
    {
        $json = [
            'self' => [
                'href' => 'https://horizon.stellar.org/ledgers/3'
            ],
            'transactions' => [
                'href' => 'https://horizon.stellar.org/ledgers/3/transactions{?cursor,limit,order}',
                'templated' => true
            ],
            'operations' => [
                'href' => 'https://horizon.stellar.org/ledgers/3/operations{?cursor,limit,order}',
                'templated' => true
            ],
            'payments' => [
                'href' => 'https://horizon.stellar.org/ledgers/3/payments{?cursor,limit,order}',
                'templated' => true
            ],
            'effects' => [
                'href' => 'https://horizon.stellar.org/ledgers/3/effects{?cursor,limit,order}',
                'templated' => true
            ]
        ];

        $links = LedgerLinksResponse::fromJson($json);

        $this->assertTrue($links->getTransactions()->isTemplated());
        $this->assertTrue($links->getOperations()->isTemplated());
        $this->assertTrue($links->getPayments()->isTemplated());
        $this->assertTrue($links->getEffects()->isTemplated());
    }

    public function testLedgerLinksResponseDifferentUrls(): void
    {
        $json = [
            'self' => [
                'href' => 'https://horizon.stellar.org/ledgers/100'
            ],
            'transactions' => [
                'href' => 'https://horizon.stellar.org/ledgers/100/transactions'
            ],
            'operations' => [
                'href' => 'https://horizon.stellar.org/ledgers/100/operations'
            ],
            'payments' => [
                'href' => 'https://horizon.stellar.org/ledgers/100/payments'
            ],
            'effects' => [
                'href' => 'https://horizon.stellar.org/ledgers/100/effects'
            ]
        ];

        $links = LedgerLinksResponse::fromJson($json);

        $this->assertStringContainsString('/ledgers/100', $links->getSelf()->getHref());
        $this->assertStringContainsString('/ledgers/100/transactions', $links->getTransactions()->getHref());
    }

    public function testLedgerLinksResponseAllGetters(): void
    {
        $json = [
            'self' => ['href' => 'https://horizon.stellar.org/ledgers/3'],
            'transactions' => ['href' => 'https://horizon.stellar.org/ledgers/3/transactions'],
            'operations' => ['href' => 'https://horizon.stellar.org/ledgers/3/operations'],
            'payments' => ['href' => 'https://horizon.stellar.org/ledgers/3/payments'],
            'effects' => ['href' => 'https://horizon.stellar.org/ledgers/3/effects']
        ];

        $links = LedgerLinksResponse::fromJson($json);

        $this->assertNotNull($links->getSelf());
        $this->assertNotNull($links->getTransactions());
        $this->assertNotNull($links->getOperations());
        $this->assertNotNull($links->getPayments());
        $this->assertNotNull($links->getEffects());
    }

    // LedgersResponse Tests

    public function testLedgersResponseEmpty(): void
    {
        $ledgers = new LedgersResponse();

        $this->assertEquals(0, $ledgers->count());
        $this->assertEmpty($ledgers->toArray());
    }

    public function testLedgersResponseConstructorWithLedgers(): void
    {
        $ledger1 = LedgerResponse::fromJson($this->getCompleteLedgerJson());

        $json2 = $this->getCompleteLedgerJson();
        $json2['sequence'] = '4';
        $ledger2 = LedgerResponse::fromJson($json2);

        $ledgers = new LedgersResponse($ledger1, $ledger2);

        $this->assertEquals(2, $ledgers->count());
    }

    public function testLedgersResponseAdd(): void
    {
        $ledgers = new LedgersResponse();
        $ledger = LedgerResponse::fromJson($this->getCompleteLedgerJson());

        $ledgers->add($ledger);

        $this->assertEquals(1, $ledgers->count());
    }

    public function testLedgersResponseAddMultiple(): void
    {
        $ledgers = new LedgersResponse();

        $ledger1 = LedgerResponse::fromJson($this->getCompleteLedgerJson());
        $ledgers->add($ledger1);

        $json2 = $this->getCompleteLedgerJson();
        $json2['sequence'] = '4';
        $ledger2 = LedgerResponse::fromJson($json2);
        $ledgers->add($ledger2);

        $json3 = $this->getCompleteLedgerJson();
        $json3['sequence'] = '5';
        $ledger3 = LedgerResponse::fromJson($json3);
        $ledgers->add($ledger3);

        $this->assertEquals(3, $ledgers->count());
    }

    public function testLedgersResponseToArray(): void
    {
        $ledgers = new LedgersResponse();

        $ledger1 = LedgerResponse::fromJson($this->getCompleteLedgerJson());
        $ledgers->add($ledger1);

        $json2 = $this->getCompleteLedgerJson();
        $json2['sequence'] = '4';
        $ledger2 = LedgerResponse::fromJson($json2);
        $ledgers->add($ledger2);

        $array = $ledgers->toArray();

        $this->assertIsArray($array);
        $this->assertCount(2, $array);
        $this->assertInstanceOf(LedgerResponse::class, $array[0]);
        $this->assertInstanceOf(LedgerResponse::class, $array[1]);
    }

    public function testLedgersResponseIteration(): void
    {
        $ledgers = new LedgersResponse();

        $ledger1 = LedgerResponse::fromJson($this->getCompleteLedgerJson());
        $ledgers->add($ledger1);

        $json2 = $this->getCompleteLedgerJson();
        $json2['sequence'] = '4';
        $ledger2 = LedgerResponse::fromJson($json2);
        $ledgers->add($ledger2);

        $count = 0;
        foreach ($ledgers as $ledger) {
            $this->assertInstanceOf(LedgerResponse::class, $ledger);
            $count++;
        }

        $this->assertEquals(2, $count);
    }

    public function testLedgersResponseCurrent(): void
    {
        $ledger1 = LedgerResponse::fromJson($this->getCompleteLedgerJson());
        $ledgers = new LedgersResponse($ledger1);

        // Rewind to ensure we're at the beginning
        $ledgers->rewind();
        $current = $ledgers->current();

        $this->assertInstanceOf(LedgerResponse::class, $current);
        $this->assertEquals('3', $current->getSequence()->toString());
    }

    // LedgersPageResponse Tests

    public function testLedgersPageResponseFromJson(): void
    {
        $json = [
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=&limit=3&order=desc'
                ],
                'next' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=12884905984&limit=3&order=desc'
                ],
                'prev' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=12884901888&limit=3&order=asc'
                ]
            ],
            '_embedded' => [
                'records' => [
                    $this->getCompleteLedgerJson(),
                    array_merge($this->getCompleteLedgerJson(), ['sequence' => '4']),
                    array_merge($this->getCompleteLedgerJson(), ['sequence' => '5'])
                ]
            ]
        ];

        $page = LedgersPageResponse::fromJson($json);

        $this->assertInstanceOf(LedgersPageResponse::class, $page);
        $this->assertInstanceOf(LedgersResponse::class, $page->getLedgers());
        $this->assertEquals(3, $page->getLedgers()->count());
    }

    public function testLedgersPageResponseGetLedgers(): void
    {
        $json = [
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=&limit=2&order=desc'
                ],
                'next' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=12884905984&limit=2&order=desc'
                ],
                'prev' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=12884901888&limit=2&order=asc'
                ]
            ],
            '_embedded' => [
                'records' => [
                    $this->getCompleteLedgerJson(),
                    array_merge($this->getCompleteLedgerJson(), ['sequence' => '4'])
                ]
            ]
        ];

        $page = LedgersPageResponse::fromJson($json);
        $ledgers = $page->getLedgers();

        $this->assertInstanceOf(LedgersResponse::class, $ledgers);
        $this->assertEquals(2, $ledgers->count());

        $array = $ledgers->toArray();
        $this->assertEquals('3', $array[0]->getSequence()->toString());
        $this->assertEquals('4', $array[1]->getSequence()->toString());
    }

    public function testLedgersPageResponseEmptyRecords(): void
    {
        $json = [
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=&limit=10&order=desc'
                ],
                'next' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=12884905984&limit=10&order=desc'
                ],
                'prev' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=12884901888&limit=10&order=asc'
                ]
            ],
            '_embedded' => [
                'records' => []
            ]
        ];

        $page = LedgersPageResponse::fromJson($json);
        $ledgers = $page->getLedgers();

        $this->assertEquals(0, $ledgers->count());
    }

    public function testLedgersPageResponseHasNextPage(): void
    {
        $json = [
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=&limit=10&order=desc'
                ],
                'next' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=12884905984&limit=10&order=desc'
                ],
                'prev' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=12884901888&limit=10&order=asc'
                ]
            ],
            '_embedded' => [
                'records' => [
                    $this->getCompleteLedgerJson()
                ]
            ]
        ];

        $page = LedgersPageResponse::fromJson($json);

        $this->assertTrue($page->hasNextPage());
    }

    public function testLedgersPageResponseHasPrevPage(): void
    {
        $json = [
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=&limit=10&order=desc'
                ],
                'next' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=12884905984&limit=10&order=desc'
                ],
                'prev' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=12884901888&limit=10&order=asc'
                ]
            ],
            '_embedded' => [
                'records' => [
                    $this->getCompleteLedgerJson()
                ]
            ]
        ];

        $page = LedgersPageResponse::fromJson($json);

        $this->assertTrue($page->hasPrevPage());
    }

    public function testLedgersPageResponseNavigationMethods(): void
    {
        $json = [
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=&limit=10&order=desc'
                ],
                'next' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=12884905984&limit=10&order=desc'
                ],
                'prev' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=12884901888&limit=10&order=asc'
                ]
            ],
            '_embedded' => [
                'records' => [
                    $this->getCompleteLedgerJson()
                ]
            ]
        ];

        $page = LedgersPageResponse::fromJson($json);

        // Test that both navigation links exist
        $this->assertTrue($page->hasNextPage());
        $this->assertTrue($page->hasPrevPage());
    }

    public function testLedgersPageResponsePaginationLinks(): void
    {
        $json = [
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=current&limit=10&order=desc'
                ],
                'next' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=next_cursor&limit=10&order=desc'
                ],
                'prev' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=prev_cursor&limit=10&order=asc'
                ]
            ],
            '_embedded' => [
                'records' => [
                    $this->getCompleteLedgerJson()
                ]
            ]
        ];

        $page = LedgersPageResponse::fromJson($json);
        $links = $page->getLinks();

        $this->assertStringContainsString('cursor=current', $links->getSelf()->getHref());
        $this->assertStringContainsString('cursor=next_cursor', $links->getNext()->getHref());
        $this->assertStringContainsString('cursor=prev_cursor', $links->getPrev()->getHref());
    }

    public function testLedgersPageResponseLargeResultSet(): void
    {
        $ledgers = [];
        for ($i = 1; $i <= 10; $i++) {
            $ledgerJson = $this->getCompleteLedgerJson();
            $ledgerJson['sequence'] = (string)$i;
            $ledgerJson['hash'] = 'hash' . $i;
            $ledgers[] = $ledgerJson;
        }

        $json = [
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/ledgers?limit=10'
                ],
                'next' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=10&limit=10'
                ],
                'prev' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=1&limit=10'
                ]
            ],
            '_embedded' => [
                'records' => $ledgers
            ]
        ];

        $page = LedgersPageResponse::fromJson($json);
        $ledgersResponse = $page->getLedgers();

        $this->assertEquals(10, $ledgersResponse->count());

        $array = $ledgersResponse->toArray();
        $this->assertEquals('1', $array[0]->getSequence()->toString());
        $this->assertEquals('10', $array[9]->getSequence()->toString());
    }

    public function testLedgersPageResponseLinks(): void
    {
        $json = [
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=&limit=10&order=desc'
                ],
                'next' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=12884905984&limit=10&order=desc'
                ],
                'prev' => [
                    'href' => 'https://horizon.stellar.org/ledgers?cursor=12884901888&limit=10&order=asc'
                ]
            ],
            '_embedded' => [
                'records' => []
            ]
        ];

        $page = LedgersPageResponse::fromJson($json);
        $links = $page->getLinks();

        $this->assertNotNull($links);
        $this->assertStringContainsString('horizon.stellar.org/ledgers', $links->getSelf()->getHref());
        $this->assertStringContainsString('cursor=12884905984', $links->getNext()->getHref());
        $this->assertStringContainsString('cursor=12884901888', $links->getPrev()->getHref());
    }

    public function testLedgersPageResponseMultipleLedgersIntegrity(): void
    {
        $json1 = $this->getCompleteLedgerJson();
        $json1['sequence'] = '100';
        $json1['hash'] = 'hash100';

        $json2 = $this->getCompleteLedgerJson();
        $json2['sequence'] = '101';
        $json2['hash'] = 'hash101';

        $json3 = $this->getCompleteLedgerJson();
        $json3['sequence'] = '102';
        $json3['hash'] = 'hash102';

        $pageJson = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/ledgers'],
                'next' => ['href' => 'https://horizon.stellar.org/ledgers?cursor=next'],
                'prev' => ['href' => 'https://horizon.stellar.org/ledgers?cursor=prev']
            ],
            '_embedded' => [
                'records' => [$json1, $json2, $json3]
            ]
        ];

        $page = LedgersPageResponse::fromJson($pageJson);
        $ledgers = $page->getLedgers()->toArray();

        $this->assertEquals('100', $ledgers[0]->getSequence()->toString());
        $this->assertEquals('hash100', $ledgers[0]->getHash());

        $this->assertEquals('101', $ledgers[1]->getSequence()->toString());
        $this->assertEquals('hash101', $ledgers[1]->getHash());

        $this->assertEquals('102', $ledgers[2]->getSequence()->toString());
        $this->assertEquals('hash102', $ledgers[2]->getHash());
    }
}
