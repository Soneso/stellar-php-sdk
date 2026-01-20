<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Responses;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimableBalanceResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimableBalancesPageResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimableBalancesResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimantResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimantsResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimantPredicateResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimantPredicatesResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimableBalanceLinksResponse;
use Soneso\StellarSDK\Asset;

/**
 * Unit tests for all ClaimableBalance Response classes
 *
 * Tests JSON parsing and getter methods for ClaimableBalance-related response classes.
 * Covers ClaimableBalanceResponse, ClaimableBalancesPageResponse, ClaimantResponse,
 * ClaimantPredicateResponse (all predicate types), ClaimableBalanceLinksResponse,
 * ClaimantsResponse, ClaimableBalancesResponse, and ClaimantPredicatesResponse.
 */
class ClaimableBalanceResponseTest extends TestCase
{
    /**
     * Helper method to create complete claimable balance JSON data
     */
    private function getCompleteClaimableBalanceJson(): array
    {
        return [
            'id' => '00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072',
            'asset' => 'native',
            'amount' => '1000.0000000',
            'sponsor' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
            'last_modified_ledger' => 123456,
            'last_modified_time' => '2021-08-04T20:01:00Z',
            'paging_token' => '123456-00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072',
            'claimants' => [
                [
                    'destination' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
                    'predicate' => [
                        'unconditional' => true
                    ]
                ]
            ],
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/claimable_balances/00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072'
                ]
            ]
        ];
    }

    /**
     * Helper method to create claimable balance with credit_alphanum4 asset
     */
    private function getClaimableBalanceWithAlphanum4Json(): array
    {
        return [
            'id' => '00000000178826fbfe339e1f5c53417c6fedfe2c05e8bec14303143ec46b38981b09c3f9',
            'asset' => 'USD:GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
            'amount' => '500.5000000',
            'sponsor' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'last_modified_ledger' => 234567,
            'last_modified_time' => '2021-08-05T15:30:00Z',
            'paging_token' => '234567-00000000178826fbfe339e1f5c53417c6fedfe2c05e8bec14303143ec46b38981b09c3f9',
            'claimants' => [
                [
                    'destination' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
                    'predicate' => [
                        'abs_before' => '1628182800'
                    ]
                ]
            ],
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/claimable_balances/00000000178826fbfe339e1f5c53417c6fedfe2c05e8bec14303143ec46b38981b09c3f9'
                ]
            ]
        ];
    }

    /**
     * Helper method to create claimable balance with credit_alphanum12 asset
     */
    private function getClaimableBalanceWithAlphanum12Json(): array
    {
        return [
            'id' => '00000000a8e0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8',
            'asset' => 'LONGASSET123:GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'amount' => '2500.0000000',
            'sponsor' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
            'last_modified_ledger' => 345678,
            'paging_token' => '345678-00000000a8e0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8',
            'claimants' => [
                [
                    'destination' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
                    'predicate' => [
                        'rel_before' => '86400'
                    ]
                ]
            ],
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/claimable_balances/00000000a8e0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8'
                ]
            ]
        ];
    }

    /**
     * Helper method to create claimable balance with multiple claimants
     */
    private function getClaimableBalanceWithMultipleClaimantsJson(): array
    {
        return [
            'id' => '00000000b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9',
            'asset' => 'native',
            'amount' => '10000.0000000',
            'sponsor' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
            'last_modified_ledger' => 456789,
            'paging_token' => '456789-00000000b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9',
            'claimants' => [
                [
                    'destination' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
                    'predicate' => [
                        'unconditional' => true
                    ]
                ],
                [
                    'destination' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
                    'predicate' => [
                        'abs_before' => '1628182800'
                    ]
                ],
                [
                    'destination' => 'GC5AFVR4D2VN5TCGCHVHG6RKJTVD7SRMFCLX6ZOYIQTHLBSSOU3UBN4N',
                    'predicate' => [
                        'rel_before' => '3600'
                    ]
                ]
            ],
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/claimable_balances/00000000b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9'
                ]
            ]
        ];
    }

    /**
     * Helper method to create claimable balance without optional last_modified_time
     */
    private function getClaimableBalanceWithoutLastModifiedTimeJson(): array
    {
        return [
            'id' => '00000000c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0',
            'asset' => 'native',
            'amount' => '100.0000000',
            'sponsor' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'last_modified_ledger' => 567890,
            'paging_token' => '567890-00000000c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0',
            'claimants' => [
                [
                    'destination' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
                    'predicate' => [
                        'unconditional' => true
                    ]
                ]
            ],
            '_links' => [
                'self' => [
                    'href' => 'https://horizon.stellar.org/claimable_balances/00000000c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0'
                ]
            ]
        ];
    }

    // ClaimableBalanceResponse Tests

    public function testClaimableBalanceResponseFromJsonComplete(): void
    {
        $json = $this->getCompleteClaimableBalanceJson();
        $response = ClaimableBalanceResponse::fromJson($json);

        $this->assertEquals('00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072', $response->getBalanceId());
        $this->assertInstanceOf(Asset::class, $response->getAsset());
        $this->assertEquals('native', $response->getAsset()->getType());
        $this->assertEquals('1000.0000000', $response->getAmount());
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $response->getSponsor());
        $this->assertEquals(123456, $response->getLastModifiedLedger());
        $this->assertEquals('2021-08-04T20:01:00Z', $response->getLastModifiedTime());
        $this->assertEquals('123456-00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072', $response->getPagingToken());
    }

    public function testClaimableBalanceResponseWithAlphanum4(): void
    {
        $json = $this->getClaimableBalanceWithAlphanum4Json();
        $response = ClaimableBalanceResponse::fromJson($json);

        $this->assertEquals('00000000178826fbfe339e1f5c53417c6fedfe2c05e8bec14303143ec46b38981b09c3f9', $response->getBalanceId());
        $this->assertInstanceOf(Asset::class, $response->getAsset());
        $this->assertEquals('credit_alphanum4', $response->getAsset()->getType());
        $this->assertEquals('USD', $response->getAsset()->getCode());
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $response->getAsset()->getIssuer());
        $this->assertEquals('500.5000000', $response->getAmount());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getSponsor());
        $this->assertEquals(234567, $response->getLastModifiedLedger());
        $this->assertEquals('2021-08-05T15:30:00Z', $response->getLastModifiedTime());
    }

    public function testClaimableBalanceResponseWithAlphanum12(): void
    {
        $json = $this->getClaimableBalanceWithAlphanum12Json();
        $response = ClaimableBalanceResponse::fromJson($json);

        $this->assertEquals('00000000a8e0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8', $response->getBalanceId());
        $this->assertInstanceOf(Asset::class, $response->getAsset());
        $this->assertEquals('credit_alphanum12', $response->getAsset()->getType());
        $this->assertEquals('LONGASSET123', $response->getAsset()->getCode());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getAsset()->getIssuer());
        $this->assertEquals('2500.0000000', $response->getAmount());
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $response->getSponsor());
        $this->assertEquals(345678, $response->getLastModifiedLedger());
    }

    public function testClaimableBalanceResponseWithoutLastModifiedTime(): void
    {
        $json = $this->getClaimableBalanceWithoutLastModifiedTimeJson();
        $response = ClaimableBalanceResponse::fromJson($json);

        $this->assertEquals('00000000c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5c6d7e8f9a0', $response->getBalanceId());
        $this->assertEquals('100.0000000', $response->getAmount());
        $this->assertEquals(567890, $response->getLastModifiedLedger());
        $this->assertNull($response->getLastModifiedTime());
    }

    public function testClaimableBalanceResponseClaimants(): void
    {
        $json = $this->getCompleteClaimableBalanceJson();
        $response = ClaimableBalanceResponse::fromJson($json);
        $claimants = $response->getClaimants();

        $this->assertInstanceOf(ClaimantsResponse::class, $claimants);
        $this->assertEquals(1, $claimants->count());

        $claimantsArray = $claimants->toArray();
        $this->assertCount(1, $claimantsArray);
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $claimantsArray[0]->getDestination());
    }

    public function testClaimableBalanceResponseMultipleClaimants(): void
    {
        $json = $this->getClaimableBalanceWithMultipleClaimantsJson();
        $response = ClaimableBalanceResponse::fromJson($json);
        $claimants = $response->getClaimants();

        $this->assertEquals(3, $claimants->count());

        $claimantsArray = $claimants->toArray();
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $claimantsArray[0]->getDestination());
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $claimantsArray[1]->getDestination());
        $this->assertEquals('GC5AFVR4D2VN5TCGCHVHG6RKJTVD7SRMFCLX6ZOYIQTHLBSSOU3UBN4N', $claimantsArray[2]->getDestination());
    }

    public function testClaimableBalanceResponseLinks(): void
    {
        $json = $this->getCompleteClaimableBalanceJson();
        $response = ClaimableBalanceResponse::fromJson($json);
        $links = $response->getLinks();

        $this->assertInstanceOf(ClaimableBalanceLinksResponse::class, $links);
        $this->assertEquals('https://horizon.stellar.org/claimable_balances/00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072', $links->getSelf()->getHref());
    }

    // ClaimantResponse Tests

    public function testClaimantResponseFromJson(): void
    {
        $json = [
            'destination' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'predicate' => [
                'unconditional' => true
            ]
        ];

        $response = ClaimantResponse::fromJson($json);

        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getDestination());
        $this->assertInstanceOf(ClaimantPredicateResponse::class, $response->getPredicate());
    }

    public function testClaimantResponseWithAbsoluteTime(): void
    {
        $json = [
            'destination' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
            'predicate' => [
                'abs_before' => '1628182800'
            ]
        ];

        $response = ClaimantResponse::fromJson($json);

        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $response->getDestination());
        $this->assertInstanceOf(ClaimantPredicateResponse::class, $response->getPredicate());
        $this->assertEquals('1628182800', $response->getPredicate()->getBeforeAbsoluteTime());
    }

    public function testClaimantResponseWithRelativeTime(): void
    {
        $json = [
            'destination' => 'GC5AFVR4D2VN5TCGCHVHG6RKJTVD7SRMFCLX6ZOYIQTHLBSSOU3UBN4N',
            'predicate' => [
                'rel_before' => '3600'
            ]
        ];

        $response = ClaimantResponse::fromJson($json);

        $this->assertEquals('GC5AFVR4D2VN5TCGCHVHG6RKJTVD7SRMFCLX6ZOYIQTHLBSSOU3UBN4N', $response->getDestination());
        $this->assertEquals('3600', $response->getPredicate()->getBeforeRelativeTime());
    }

    // ClaimantPredicateResponse Tests - Unconditional

    public function testClaimantPredicateResponseUnconditional(): void
    {
        $json = ['unconditional' => true];
        $response = ClaimantPredicateResponse::fromJson($json);

        $this->assertTrue($response->getUnconditional());
        $this->assertNull($response->getAnd());
        $this->assertNull($response->getOr());
        $this->assertNull($response->getNot());
        $this->assertNull($response->getBeforeAbsoluteTime());
        $this->assertNull($response->getBeforeRelativeTime());
    }

    // ClaimantPredicateResponse Tests - Absolute Time

    public function testClaimantPredicateResponseAbsoluteTime(): void
    {
        $json = ['abs_before' => '1628182800'];
        $response = ClaimantPredicateResponse::fromJson($json);

        $this->assertEquals('1628182800', $response->getBeforeAbsoluteTime());
        $this->assertNull($response->getUnconditional());
        $this->assertNull($response->getBeforeRelativeTime());
    }

    public function testClaimantPredicateResponseAbsoluteTimeCamelCase(): void
    {
        $json = ['absBefore' => '1628182800'];
        $response = ClaimantPredicateResponse::fromJson($json);

        $this->assertEquals('1628182800', $response->getBeforeAbsoluteTime());
    }

    // ClaimantPredicateResponse Tests - Relative Time

    public function testClaimantPredicateResponseRelativeTime(): void
    {
        $json = ['rel_before' => '86400'];
        $response = ClaimantPredicateResponse::fromJson($json);

        $this->assertEquals('86400', $response->getBeforeRelativeTime());
        $this->assertNull($response->getUnconditional());
        $this->assertNull($response->getBeforeAbsoluteTime());
    }

    public function testClaimantPredicateResponseRelativeTimeCamelCase(): void
    {
        $json = ['relBefore' => '3600'];
        $response = ClaimantPredicateResponse::fromJson($json);

        $this->assertEquals('3600', $response->getBeforeRelativeTime());
    }

    // ClaimantPredicateResponse Tests - AND

    public function testClaimantPredicateResponseAnd(): void
    {
        $json = [
            'and' => [
                ['abs_before' => '1628182800'],
                ['rel_before' => '86400']
            ]
        ];

        $response = ClaimantPredicateResponse::fromJson($json);

        $this->assertNull($response->getUnconditional());
        $this->assertInstanceOf(ClaimantPredicatesResponse::class, $response->getAnd());
        $this->assertEquals(2, $response->getAnd()->count());

        $andPredicates = $response->getAnd()->toArray();
        $this->assertEquals('1628182800', $andPredicates[0]->getBeforeAbsoluteTime());
        $this->assertEquals('86400', $andPredicates[1]->getBeforeRelativeTime());
    }

    public function testClaimantPredicateResponseAndMultiple(): void
    {
        $json = [
            'and' => [
                ['abs_before' => '1628182800'],
                ['rel_before' => '86400'],
                ['unconditional' => true]
            ]
        ];

        $response = ClaimantPredicateResponse::fromJson($json);
        $andPredicates = $response->getAnd();

        $this->assertEquals(3, $andPredicates->count());

        $predicatesArray = $andPredicates->toArray();
        $this->assertEquals('1628182800', $predicatesArray[0]->getBeforeAbsoluteTime());
        $this->assertEquals('86400', $predicatesArray[1]->getBeforeRelativeTime());
        $this->assertTrue($predicatesArray[2]->getUnconditional());
    }

    // ClaimantPredicateResponse Tests - OR

    public function testClaimantPredicateResponseOr(): void
    {
        $json = [
            'or' => [
                ['abs_before' => '1628182800'],
                ['unconditional' => true]
            ]
        ];

        $response = ClaimantPredicateResponse::fromJson($json);

        $this->assertNull($response->getUnconditional());
        $this->assertInstanceOf(ClaimantPredicatesResponse::class, $response->getOr());
        $this->assertEquals(2, $response->getOr()->count());

        $orPredicates = $response->getOr()->toArray();
        $this->assertEquals('1628182800', $orPredicates[0]->getBeforeAbsoluteTime());
        $this->assertTrue($orPredicates[1]->getUnconditional());
    }

    public function testClaimantPredicateResponseOrMultiple(): void
    {
        $json = [
            'or' => [
                ['rel_before' => '3600'],
                ['abs_before' => '1628182800'],
                ['unconditional' => true]
            ]
        ];

        $response = ClaimantPredicateResponse::fromJson($json);
        $orPredicates = $response->getOr();

        $this->assertEquals(3, $orPredicates->count());

        $predicatesArray = $orPredicates->toArray();
        $this->assertEquals('3600', $predicatesArray[0]->getBeforeRelativeTime());
        $this->assertEquals('1628182800', $predicatesArray[1]->getBeforeAbsoluteTime());
        $this->assertTrue($predicatesArray[2]->getUnconditional());
    }

    // ClaimantPredicateResponse Tests - NOT

    public function testClaimantPredicateResponseNot(): void
    {
        $json = [
            'not' => [
                'abs_before' => '1628182800'
            ]
        ];

        $response = ClaimantPredicateResponse::fromJson($json);

        $this->assertNull($response->getUnconditional());
        $this->assertInstanceOf(ClaimantPredicateResponse::class, $response->getNot());
        $this->assertEquals('1628182800', $response->getNot()->getBeforeAbsoluteTime());
    }

    public function testClaimantPredicateResponseNotUnconditional(): void
    {
        $json = [
            'not' => [
                'unconditional' => true
            ]
        ];

        $response = ClaimantPredicateResponse::fromJson($json);

        $this->assertInstanceOf(ClaimantPredicateResponse::class, $response->getNot());
        $this->assertTrue($response->getNot()->getUnconditional());
    }

    public function testClaimantPredicateResponseNotRelativeTime(): void
    {
        $json = [
            'not' => [
                'rel_before' => '7200'
            ]
        ];

        $response = ClaimantPredicateResponse::fromJson($json);

        $this->assertEquals('7200', $response->getNot()->getBeforeRelativeTime());
    }

    // ClaimantPredicateResponse Tests - Complex Nested

    public function testClaimantPredicateResponseNestedAndOr(): void
    {
        $json = [
            'and' => [
                [
                    'or' => [
                        ['abs_before' => '1628182800'],
                        ['rel_before' => '3600']
                    ]
                ],
                ['unconditional' => true]
            ]
        ];

        $response = ClaimantPredicateResponse::fromJson($json);

        $this->assertInstanceOf(ClaimantPredicatesResponse::class, $response->getAnd());
        $andPredicates = $response->getAnd()->toArray();
        $this->assertEquals(2, count($andPredicates));

        $orPredicate = $andPredicates[0];
        $this->assertInstanceOf(ClaimantPredicatesResponse::class, $orPredicate->getOr());
        $this->assertEquals(2, $orPredicate->getOr()->count());
    }

    public function testClaimantPredicateResponseNestedNotAnd(): void
    {
        $json = [
            'not' => [
                'and' => [
                    ['abs_before' => '1628182800'],
                    ['rel_before' => '86400']
                ]
            ]
        ];

        $response = ClaimantPredicateResponse::fromJson($json);

        $this->assertInstanceOf(ClaimantPredicateResponse::class, $response->getNot());
        $notPredicate = $response->getNot();
        $this->assertInstanceOf(ClaimantPredicatesResponse::class, $notPredicate->getAnd());
        $this->assertEquals(2, $notPredicate->getAnd()->count());
    }

    public function testClaimantPredicateResponseComplexNested(): void
    {
        $json = [
            'or' => [
                [
                    'and' => [
                        ['abs_before' => '1628182800'],
                        ['rel_before' => '3600']
                    ]
                ],
                [
                    'not' => [
                        'unconditional' => true
                    ]
                ]
            ]
        ];

        $response = ClaimantPredicateResponse::fromJson($json);

        $this->assertInstanceOf(ClaimantPredicatesResponse::class, $response->getOr());
        $orPredicates = $response->getOr()->toArray();
        $this->assertEquals(2, count($orPredicates));

        $andPredicate = $orPredicates[0];
        $this->assertInstanceOf(ClaimantPredicatesResponse::class, $andPredicate->getAnd());
        $this->assertEquals(2, $andPredicate->getAnd()->count());

        $notPredicate = $orPredicates[1];
        $this->assertInstanceOf(ClaimantPredicateResponse::class, $notPredicate->getNot());
        $this->assertTrue($notPredicate->getNot()->getUnconditional());
    }

    // ClaimableBalanceLinksResponse Tests

    public function testClaimableBalanceLinksResponseFromJson(): void
    {
        $json = [
            'self' => [
                'href' => 'https://horizon.stellar.org/claimable_balances/00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072'
            ]
        ];

        $response = ClaimableBalanceLinksResponse::fromJson($json);

        $this->assertEquals('https://horizon.stellar.org/claimable_balances/00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072', $response->getSelf()->getHref());
    }

    public function testClaimableBalanceLinksResponseDifferentUrl(): void
    {
        $json = [
            'self' => [
                'href' => 'https://horizon-testnet.stellar.org/claimable_balances/test-id'
            ]
        ];

        $response = ClaimableBalanceLinksResponse::fromJson($json);

        $this->assertEquals('https://horizon-testnet.stellar.org/claimable_balances/test-id', $response->getSelf()->getHref());
    }

    // ClaimantsResponse Tests

    public function testClaimantsResponseEmpty(): void
    {
        $claimants = new ClaimantsResponse();

        $this->assertEquals(0, $claimants->count());
        $this->assertEquals([], $claimants->toArray());
    }

    public function testClaimantsResponseConstructor(): void
    {
        $claimant1 = ClaimantResponse::fromJson([
            'destination' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'predicate' => ['unconditional' => true]
        ]);
        $claimant2 = ClaimantResponse::fromJson([
            'destination' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
            'predicate' => ['abs_before' => '1628182800']
        ]);

        $claimants = new ClaimantsResponse($claimant1, $claimant2);

        $this->assertEquals(2, $claimants->count());
        $claimantsArray = $claimants->toArray();
        $this->assertCount(2, $claimantsArray);
        $this->assertSame($claimant1, $claimantsArray[0]);
        $this->assertSame($claimant2, $claimantsArray[1]);
    }

    public function testClaimantsResponseAdd(): void
    {
        $claimants = new ClaimantsResponse();
        $this->assertEquals(0, $claimants->count());

        $claimant1 = ClaimantResponse::fromJson([
            'destination' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'predicate' => ['unconditional' => true]
        ]);
        $claimants->add($claimant1);
        $this->assertEquals(1, $claimants->count());

        $claimant2 = ClaimantResponse::fromJson([
            'destination' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
            'predicate' => ['rel_before' => '86400']
        ]);
        $claimants->add($claimant2);
        $this->assertEquals(2, $claimants->count());
    }

    public function testClaimantsResponseIteration(): void
    {
        $claimant1 = ClaimantResponse::fromJson([
            'destination' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'predicate' => ['unconditional' => true]
        ]);
        $claimant2 = ClaimantResponse::fromJson([
            'destination' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
            'predicate' => ['abs_before' => '1628182800']
        ]);

        $claimants = new ClaimantsResponse($claimant1, $claimant2);

        $iteratedClaimants = [];
        foreach ($claimants as $claimant) {
            $this->assertInstanceOf(ClaimantResponse::class, $claimant);
            $iteratedClaimants[] = $claimant;
        }

        $this->assertCount(2, $iteratedClaimants);
        $this->assertSame($claimant1, $iteratedClaimants[0]);
        $this->assertSame($claimant2, $iteratedClaimants[1]);
    }

    public function testClaimantsResponseCurrent(): void
    {
        $claimant1 = ClaimantResponse::fromJson([
            'destination' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'predicate' => ['unconditional' => true]
        ]);
        $claimant2 = ClaimantResponse::fromJson([
            'destination' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
            'predicate' => ['abs_before' => '1628182800']
        ]);

        $claimants = new ClaimantsResponse($claimant1, $claimant2);

        $claimants->rewind();
        $this->assertSame($claimant1, $claimants->current());
        $claimants->next();
        $this->assertSame($claimant2, $claimants->current());
    }

    // ClaimantPredicatesResponse Tests

    public function testClaimantPredicatesResponseEmpty(): void
    {
        $predicates = new ClaimantPredicatesResponse();

        $this->assertEquals(0, $predicates->count());
        $this->assertEquals([], $predicates->toArray());
    }

    public function testClaimantPredicatesResponseConstructor(): void
    {
        $predicate1 = ClaimantPredicateResponse::fromJson(['abs_before' => '1628182800']);
        $predicate2 = ClaimantPredicateResponse::fromJson(['rel_before' => '86400']);

        $predicates = new ClaimantPredicatesResponse($predicate1, $predicate2);

        $this->assertEquals(2, $predicates->count());
        $predicatesArray = $predicates->toArray();
        $this->assertCount(2, $predicatesArray);
        $this->assertSame($predicate1, $predicatesArray[0]);
        $this->assertSame($predicate2, $predicatesArray[1]);
    }

    public function testClaimantPredicatesResponseAdd(): void
    {
        $predicates = new ClaimantPredicatesResponse();
        $this->assertEquals(0, $predicates->count());

        $predicate1 = ClaimantPredicateResponse::fromJson(['unconditional' => true]);
        $predicates->add($predicate1);
        $this->assertEquals(1, $predicates->count());

        $predicate2 = ClaimantPredicateResponse::fromJson(['abs_before' => '1628182800']);
        $predicates->add($predicate2);
        $this->assertEquals(2, $predicates->count());
    }

    public function testClaimantPredicatesResponseIteration(): void
    {
        $predicate1 = ClaimantPredicateResponse::fromJson(['abs_before' => '1628182800']);
        $predicate2 = ClaimantPredicateResponse::fromJson(['rel_before' => '86400']);

        $predicates = new ClaimantPredicatesResponse($predicate1, $predicate2);

        $iteratedPredicates = [];
        foreach ($predicates as $predicate) {
            $this->assertInstanceOf(ClaimantPredicateResponse::class, $predicate);
            $iteratedPredicates[] = $predicate;
        }

        $this->assertCount(2, $iteratedPredicates);
        $this->assertSame($predicate1, $iteratedPredicates[0]);
        $this->assertSame($predicate2, $iteratedPredicates[1]);
    }

    public function testClaimantPredicatesResponseCurrent(): void
    {
        $predicate1 = ClaimantPredicateResponse::fromJson(['abs_before' => '1628182800']);
        $predicate2 = ClaimantPredicateResponse::fromJson(['rel_before' => '86400']);

        $predicates = new ClaimantPredicatesResponse($predicate1, $predicate2);

        $predicates->rewind();
        $this->assertSame($predicate1, $predicates->current());
        $predicates->next();
        $this->assertSame($predicate2, $predicates->current());
    }

    // ClaimableBalancesResponse Tests

    public function testClaimableBalancesResponseEmpty(): void
    {
        $balances = new ClaimableBalancesResponse();

        $this->assertEquals(0, $balances->count());
        $this->assertEquals([], $balances->toArray());
    }

    public function testClaimableBalancesResponseConstructor(): void
    {
        $balance1 = ClaimableBalanceResponse::fromJson($this->getCompleteClaimableBalanceJson());
        $balance2 = ClaimableBalanceResponse::fromJson($this->getClaimableBalanceWithAlphanum4Json());

        $balances = new ClaimableBalancesResponse($balance1, $balance2);

        $this->assertEquals(2, $balances->count());
        $balancesArray = $balances->toArray();
        $this->assertCount(2, $balancesArray);
        $this->assertSame($balance1, $balancesArray[0]);
        $this->assertSame($balance2, $balancesArray[1]);
    }

    public function testClaimableBalancesResponseAdd(): void
    {
        $balances = new ClaimableBalancesResponse();
        $this->assertEquals(0, $balances->count());

        $balance1 = ClaimableBalanceResponse::fromJson($this->getCompleteClaimableBalanceJson());
        $balances->add($balance1);
        $this->assertEquals(1, $balances->count());

        $balance2 = ClaimableBalanceResponse::fromJson($this->getClaimableBalanceWithAlphanum4Json());
        $balances->add($balance2);
        $this->assertEquals(2, $balances->count());

        $balance3 = ClaimableBalanceResponse::fromJson($this->getClaimableBalanceWithAlphanum12Json());
        $balances->add($balance3);
        $this->assertEquals(3, $balances->count());
    }

    public function testClaimableBalancesResponseIteration(): void
    {
        $balance1 = ClaimableBalanceResponse::fromJson($this->getCompleteClaimableBalanceJson());
        $balance2 = ClaimableBalanceResponse::fromJson($this->getClaimableBalanceWithAlphanum4Json());

        $balances = new ClaimableBalancesResponse($balance1, $balance2);

        $iteratedBalances = [];
        foreach ($balances as $balance) {
            $this->assertInstanceOf(ClaimableBalanceResponse::class, $balance);
            $iteratedBalances[] = $balance;
        }

        $this->assertCount(2, $iteratedBalances);
        $this->assertSame($balance1, $iteratedBalances[0]);
        $this->assertSame($balance2, $iteratedBalances[1]);
    }

    public function testClaimableBalancesResponseCurrent(): void
    {
        $balance1 = ClaimableBalanceResponse::fromJson($this->getCompleteClaimableBalanceJson());
        $balance2 = ClaimableBalanceResponse::fromJson($this->getClaimableBalanceWithAlphanum4Json());

        $balances = new ClaimableBalancesResponse($balance1, $balance2);

        $balances->rewind();
        $this->assertSame($balance1, $balances->current());
        $balances->next();
        $this->assertSame($balance2, $balances->current());
    }

    // ClaimableBalancesPageResponse Tests

    public function testClaimableBalancesPageResponseFromJson(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/claimable_balances?cursor=&limit=10&order=asc'],
                'next' => ['href' => 'https://horizon.stellar.org/claimable_balances?cursor=next_cursor&limit=10&order=asc'],
                'prev' => ['href' => 'https://horizon.stellar.org/claimable_balances?cursor=prev_cursor&limit=10&order=desc']
            ],
            '_embedded' => [
                'records' => [
                    $this->getCompleteClaimableBalanceJson(),
                    $this->getClaimableBalanceWithAlphanum4Json(),
                    $this->getClaimableBalanceWithAlphanum12Json()
                ]
            ]
        ];

        $page = ClaimableBalancesPageResponse::fromJson($json);
        $balances = $page->getClaimableBalances();

        $this->assertInstanceOf(ClaimableBalancesResponse::class, $balances);
        $this->assertEquals(3, $balances->count());

        $balancesArray = $balances->toArray();
        $this->assertEquals('native', $balancesArray[0]->getAsset()->getType());
        $this->assertEquals('credit_alphanum4', $balancesArray[1]->getAsset()->getType());
        $this->assertEquals('credit_alphanum12', $balancesArray[2]->getAsset()->getType());
    }

    public function testClaimableBalancesPageResponseEmpty(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/claimable_balances?cursor=&limit=10&order=asc'],
                'next' => ['href' => 'https://horizon.stellar.org/claimable_balances?cursor=&limit=10&order=asc'],
                'prev' => ['href' => 'https://horizon.stellar.org/claimable_balances?cursor=&limit=10&order=desc']
            ],
            '_embedded' => [
                'records' => []
            ]
        ];

        $page = ClaimableBalancesPageResponse::fromJson($json);
        $balances = $page->getClaimableBalances();

        $this->assertInstanceOf(ClaimableBalancesResponse::class, $balances);
        $this->assertEquals(0, $balances->count());
    }

    public function testClaimableBalancesPageResponseSingleBalance(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/claimable_balances?asset=native'],
                'next' => ['href' => 'https://horizon.stellar.org/claimable_balances?cursor=next&limit=10&order=asc'],
                'prev' => ['href' => 'https://horizon.stellar.org/claimable_balances?cursor=prev&limit=10&order=desc']
            ],
            '_embedded' => [
                'records' => [
                    $this->getCompleteClaimableBalanceJson()
                ]
            ]
        ];

        $page = ClaimableBalancesPageResponse::fromJson($json);
        $balances = $page->getClaimableBalances();

        $this->assertEquals(1, $balances->count());
        $balancesArray = $balances->toArray();
        $this->assertEquals('1000.0000000', $balancesArray[0]->getAmount());
    }

    public function testClaimableBalancesPageResponseMultipleBalances(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/claimable_balances?limit=5'],
                'next' => ['href' => 'https://horizon.stellar.org/claimable_balances?cursor=next&limit=5&order=asc'],
                'prev' => ['href' => 'https://horizon.stellar.org/claimable_balances?cursor=prev&limit=5&order=desc']
            ],
            '_embedded' => [
                'records' => [
                    $this->getCompleteClaimableBalanceJson(),
                    $this->getClaimableBalanceWithAlphanum4Json(),
                    $this->getClaimableBalanceWithAlphanum12Json(),
                    $this->getClaimableBalanceWithMultipleClaimantsJson(),
                    $this->getClaimableBalanceWithoutLastModifiedTimeJson()
                ]
            ]
        ];

        $page = ClaimableBalancesPageResponse::fromJson($json);
        $balances = $page->getClaimableBalances();

        $this->assertEquals(5, $balances->count());
    }

    // Integration Tests

    public function testClaimableBalanceResponseCompleteIntegration(): void
    {
        $json = $this->getCompleteClaimableBalanceJson();
        $balance = ClaimableBalanceResponse::fromJson($json);

        $this->assertEquals('00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072', $balance->getBalanceId());
        $this->assertEquals('native', $balance->getAsset()->getType());
        $this->assertEquals('1000.0000000', $balance->getAmount());
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $balance->getSponsor());
        $this->assertEquals(123456, $balance->getLastModifiedLedger());
        $this->assertEquals('2021-08-04T20:01:00Z', $balance->getLastModifiedTime());

        $claimants = $balance->getClaimants();
        $this->assertEquals(1, $claimants->count());
        $claimantsArray = $claimants->toArray();
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $claimantsArray[0]->getDestination());
        $this->assertTrue($claimantsArray[0]->getPredicate()->getUnconditional());

        $links = $balance->getLinks();
        $this->assertEquals('https://horizon.stellar.org/claimable_balances/00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072', $links->getSelf()->getHref());
    }

    public function testClaimableBalanceResponseMultipleClaimantsIntegration(): void
    {
        $json = $this->getClaimableBalanceWithMultipleClaimantsJson();
        $balance = ClaimableBalanceResponse::fromJson($json);

        $this->assertEquals('10000.0000000', $balance->getAmount());

        $claimants = $balance->getClaimants();
        $this->assertEquals(3, $claimants->count());

        $claimantsArray = $claimants->toArray();

        $claimant0 = $claimantsArray[0];
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $claimant0->getDestination());
        $this->assertTrue($claimant0->getPredicate()->getUnconditional());

        $claimant1 = $claimantsArray[1];
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $claimant1->getDestination());
        $this->assertEquals('1628182800', $claimant1->getPredicate()->getBeforeAbsoluteTime());

        $claimant2 = $claimantsArray[2];
        $this->assertEquals('GC5AFVR4D2VN5TCGCHVHG6RKJTVD7SRMFCLX6ZOYIQTHLBSSOU3UBN4N', $claimant2->getDestination());
        $this->assertEquals('3600', $claimant2->getPredicate()->getBeforeRelativeTime());
    }

    public function testClaimableBalancesPageWithDifferentAssetTypes(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/claimable_balances'],
                'next' => ['href' => 'https://horizon.stellar.org/claimable_balances?cursor=next'],
                'prev' => ['href' => 'https://horizon.stellar.org/claimable_balances?cursor=prev']
            ],
            '_embedded' => [
                'records' => [
                    $this->getCompleteClaimableBalanceJson(),
                    $this->getClaimableBalanceWithAlphanum4Json(),
                    $this->getClaimableBalanceWithAlphanum12Json()
                ]
            ]
        ];

        $page = ClaimableBalancesPageResponse::fromJson($json);
        $balances = $page->getClaimableBalances();
        $balancesArray = $balances->toArray();

        $this->assertEquals(3, $balances->count());

        $nativeBalance = $balancesArray[0];
        $this->assertEquals('native', $nativeBalance->getAsset()->getType());
        $this->assertEquals('1000.0000000', $nativeBalance->getAmount());

        $alphanum4Balance = $balancesArray[1];
        $this->assertEquals('credit_alphanum4', $alphanum4Balance->getAsset()->getType());
        $this->assertEquals('USD', $alphanum4Balance->getAsset()->getCode());
        $this->assertEquals('500.5000000', $alphanum4Balance->getAmount());

        $alphanum12Balance = $balancesArray[2];
        $this->assertEquals('credit_alphanum12', $alphanum12Balance->getAsset()->getType());
        $this->assertEquals('LONGASSET123', $alphanum12Balance->getAsset()->getCode());
        $this->assertEquals('2500.0000000', $alphanum12Balance->getAmount());
    }

    public function testComplexPredicateIntegration(): void
    {
        $json = [
            'or' => [
                [
                    'and' => [
                        ['abs_before' => '1628182800'],
                        [
                            'not' => [
                                'rel_before' => '3600'
                            ]
                        ]
                    ]
                ],
                ['unconditional' => true]
            ]
        ];

        $predicate = ClaimantPredicateResponse::fromJson($json);

        $this->assertInstanceOf(ClaimantPredicatesResponse::class, $predicate->getOr());
        $orPredicates = $predicate->getOr()->toArray();
        $this->assertEquals(2, count($orPredicates));

        $andPredicate = $orPredicates[0];
        $this->assertInstanceOf(ClaimantPredicatesResponse::class, $andPredicate->getAnd());
        $andPredicatesArray = $andPredicate->getAnd()->toArray();
        $this->assertEquals(2, count($andPredicatesArray));
        $this->assertEquals('1628182800', $andPredicatesArray[0]->getBeforeAbsoluteTime());

        $notPredicate = $andPredicatesArray[1];
        $this->assertInstanceOf(ClaimantPredicateResponse::class, $notPredicate->getNot());
        $this->assertEquals('3600', $notPredicate->getNot()->getBeforeRelativeTime());

        $unconditionalPredicate = $orPredicates[1];
        $this->assertTrue($unconditionalPredicate->getUnconditional());
    }
}
