<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Responses;

use PHPUnit\Framework\TestCase;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Responses\Account\AccountBalanceResponse;
use Soneso\StellarSDK\Responses\Account\AccountBalancesResponse;
use Soneso\StellarSDK\Responses\Account\AccountSignerResponse;
use Soneso\StellarSDK\Responses\Account\AccountSignersResponse;
use Soneso\StellarSDK\Responses\Account\AccountThresholdsResponse;
use Soneso\StellarSDK\Responses\Account\AccountFlagsResponse;
use Soneso\StellarSDK\Responses\Account\AccountLinksResponse;
use Soneso\StellarSDK\Responses\Account\AccountDataResponse;
use Soneso\StellarSDK\Responses\Account\AccountDataValueResponse;
use Soneso\StellarSDK\Responses\Account\AccountsPageResponse;
use Soneso\StellarSDK\Responses\Account\AccountsResponse;

/**
 * Unit tests for all Account Response classes
 *
 * Tests JSON parsing and getter methods for Account-related response classes.
 * Covers AccountResponse, balance, signer, threshold, flags, links, and data responses.
 */
class AccountResponseTest extends TestCase
{
    /**
     * Helper method to create complete account JSON data
     */
    private function getCompleteAccountJson(): array
    {
        return [
            'account_id' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'sequence' => '123456789012',
            'subentry_count' => 5,
            'inflation_destination' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
            'home_domain' => 'example.com',
            'last_modified_ledger' => 1234567,
            'last_modified_time' => '2019-02-28T17:00:00Z',
            'sequence_ledger' => 1234560,
            'sequence_time' => '2019-02-28T16:55:00Z',
            'thresholds' => [
                'low_threshold' => 0,
                'med_threshold' => 2,
                'high_threshold' => 5
            ],
            'flags' => [
                'auth_required' => true,
                'auth_revocable' => true,
                'auth_immutable' => false,
                'auth_clawback_enabled' => true
            ],
            'balances' => [
                [
                    'balance' => '10000.0000000',
                    'asset_type' => 'native',
                    'buying_liabilities' => '100.0000000',
                    'selling_liabilities' => '200.0000000'
                ],
                [
                    'balance' => '5000.0000000',
                    'limit' => '10000.0000000',
                    'asset_type' => 'credit_alphanum4',
                    'asset_code' => 'USD',
                    'asset_issuer' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
                    'buying_liabilities' => '50.0000000',
                    'selling_liabilities' => '100.0000000',
                    'is_authorized' => true,
                    'is_authorized_to_maintain_liabilities' => true,
                    'is_clawback_enabled' => false,
                    'last_modified_ledger' => 1234500,
                    'sponsor' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5'
                ],
                [
                    'balance' => '250.0000000',
                    'asset_type' => 'liquidity_pool_shares',
                    'liquidity_pool_id' => 'dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7'
                ]
            ],
            'signers' => [
                [
                    'key' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
                    'weight' => 1,
                    'type' => 'ed25519_public_key'
                ],
                [
                    'key' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
                    'weight' => 2,
                    'type' => 'ed25519_public_key',
                    'sponsor' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7'
                ]
            ],
            'data' => [
                'config' => 'dGVzdCBkYXRh',
                'metadata' => 'YW5vdGhlciB0ZXN0'
            ],
            'num_sponsoring' => 3,
            'num_sponsored' => 1,
            'sponsor' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
            'paging_token' => '123456789012',
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7'],
                'transactions' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7/transactions'],
                'operations' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7/operations'],
                'effects' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7/effects'],
                'offers' => ['href' => 'https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7/offers']
            ]
        ];
    }

    // AccountResponse Tests

    public function testAccountResponseFromJson(): void
    {
        $json = $this->getCompleteAccountJson();
        $response = AccountResponse::fromJson($json);

        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getAccountId());
        $this->assertEquals('123456789012', $response->getSequenceNumber()->toString());
        $this->assertEquals(5, $response->getSubentryCount());
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $response->getInflationDestination());
        $this->assertEquals('example.com', $response->getHomeDomain());
        $this->assertEquals(1234567, $response->getLastModifiedLedger());
        $this->assertEquals('2019-02-28T17:00:00Z', $response->getLastModifiedTime());
        $this->assertEquals(1234560, $response->getSequenceLedger());
        $this->assertEquals('2019-02-28T16:55:00Z', $response->getSequenceTime());
        $this->assertEquals(3, $response->getNumSponsoring());
        $this->assertEquals(1, $response->getNumSponsored());
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $response->getSponsor());
        $this->assertEquals('123456789012', $response->getPagingToken());
    }

    public function testAccountResponseThresholds(): void
    {
        $json = $this->getCompleteAccountJson();
        $response = AccountResponse::fromJson($json);
        $thresholds = $response->getThresholds();

        $this->assertInstanceOf(AccountThresholdsResponse::class, $thresholds);
        $this->assertEquals(0, $thresholds->getLowThreshold());
        $this->assertEquals(2, $thresholds->getMedThreshold());
        $this->assertEquals(5, $thresholds->getHighThreshold());
    }

    public function testAccountResponseFlags(): void
    {
        $json = $this->getCompleteAccountJson();
        $response = AccountResponse::fromJson($json);
        $flags = $response->getFlags();

        $this->assertInstanceOf(AccountFlagsResponse::class, $flags);
        $this->assertTrue($flags->isAuthRequired());
        $this->assertTrue($flags->isAuthRevocable());
        $this->assertFalse($flags->isAuthImmutable());
        $this->assertTrue($flags->isAuthClawbackEnabled());
    }

    public function testAccountResponseBalances(): void
    {
        $json = $this->getCompleteAccountJson();
        $response = AccountResponse::fromJson($json);
        $balances = $response->getBalances();

        $this->assertInstanceOf(AccountBalancesResponse::class, $balances);
        $this->assertEquals(3, $balances->count());

        $balancesArray = $balances->toArray();
        $nativeBalance = $balancesArray[0];
        $this->assertEquals('10000.0000000', $nativeBalance->getBalance());
        $this->assertEquals('native', $nativeBalance->getAssetType());
        $this->assertEquals('100.0000000', $nativeBalance->getBuyingLiabilities());
        $this->assertEquals('200.0000000', $nativeBalance->getSellingLiabilities());

        $usdBalance = $balancesArray[1];
        $this->assertEquals('5000.0000000', $usdBalance->getBalance());
        $this->assertEquals('credit_alphanum4', $usdBalance->getAssetType());
        $this->assertEquals('USD', $usdBalance->getAssetCode());
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $usdBalance->getAssetIssuer());
        $this->assertEquals('10000.0000000', $usdBalance->getLimit());
        $this->assertTrue($usdBalance->getIsAuthorized());
        $this->assertTrue($usdBalance->getIsAuthorizedToMaintainLiabilities());
        $this->assertFalse($usdBalance->getIsClawbackEnabled());
        $this->assertEquals(1234500, $usdBalance->getLastModifiedLedger());
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $usdBalance->getSponsor());

        $lpBalance = $balancesArray[2];
        $this->assertEquals('liquidity_pool_shares', $lpBalance->getAssetType());
        $this->assertEquals('dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7', $lpBalance->getLiquidityPoolId());
    }

    public function testAccountResponseSigners(): void
    {
        $json = $this->getCompleteAccountJson();
        $response = AccountResponse::fromJson($json);
        $signers = $response->getSigners();

        $this->assertInstanceOf(AccountSignersResponse::class, $signers);
        $this->assertEquals(2, $signers->count());

        $signersArray = $signers->toArray();
        $signer1 = $signersArray[0];
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $signer1->getKey());
        $this->assertEquals(1, $signer1->getWeight());
        $this->assertEquals('ed25519_public_key', $signer1->getType());
        $this->assertNull($signer1->getSponsor());

        $signer2 = $signersArray[1];
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $signer2->getKey());
        $this->assertEquals(2, $signer2->getWeight());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $signer2->getSponsor());
    }

    public function testAccountResponseData(): void
    {
        $json = $this->getCompleteAccountJson();
        $response = AccountResponse::fromJson($json);
        $data = $response->getData();

        $this->assertInstanceOf(AccountDataResponse::class, $data);
        $this->assertCount(2, $data->getData());
        $this->assertArrayHasKey('config', $data->getData());
        $this->assertArrayHasKey('metadata', $data->getData());
        $this->assertEquals('dGVzdCBkYXRh', $data->getBase64Encoded('config'));
        $this->assertEquals('test data', $data->get('config'));
    }

    public function testAccountResponseLinks(): void
    {
        $json = $this->getCompleteAccountJson();
        $response = AccountResponse::fromJson($json);
        $links = $response->getLinks();

        $this->assertInstanceOf(AccountLinksResponse::class, $links);
        $this->assertEquals('https://horizon.stellar.org/accounts/GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $links->getSelf()->getHref());
        $this->assertStringContainsString('/transactions', $links->getTransactions()->getHref());
        $this->assertStringContainsString('/operations', $links->getOperations()->getHref());
        $this->assertStringContainsString('/effects', $links->getEffects()->getHref());
        $this->assertStringContainsString('/offers', $links->getOffers()->getHref());
    }

    public function testAccountResponseKeyPair(): void
    {
        $json = $this->getCompleteAccountJson();
        $response = AccountResponse::fromJson($json);
        $keyPair = $response->getKeyPair();

        $this->assertNotNull($keyPair);
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $keyPair->getAccountId());
    }

    public function testAccountResponseIncrementedSequenceNumber(): void
    {
        $json = $this->getCompleteAccountJson();
        $response = AccountResponse::fromJson($json);

        $incrementedSeq = $response->getIncrementedSequenceNumber();
        $this->assertEquals('123456789013', $incrementedSeq->toString());

        // Verify original sequence not changed
        $this->assertEquals('123456789012', $response->getSequenceNumber()->toString());
    }

    public function testAccountResponseIncrementSequenceNumber(): void
    {
        $json = $this->getCompleteAccountJson();
        $response = AccountResponse::fromJson($json);

        $response->incrementSequenceNumber();
        $this->assertEquals('123456789013', $response->getSequenceNumber()->toString());
    }

    public function testAccountResponseMuxedAccount(): void
    {
        $json = $this->getCompleteAccountJson();
        $response = AccountResponse::fromJson($json);

        // Test without muxed ID
        $muxedAccount1 = $response->getMuxedAccount();
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $muxedAccount1->getEd25519AccountId());
        $this->assertNull($response->getMuxedAccountMed25519Id());

        // Test with muxed ID
        $response->setMuxedAccountMed25519Id(123456);
        $this->assertEquals(123456, $response->getMuxedAccountMed25519Id());
        $muxedAccount2 = $response->getMuxedAccount();
        // When muxed ID is set, getAccountId() returns the M-address
        $this->assertStringStartsWith('M', $muxedAccount2->getAccountId());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $muxedAccount2->getEd25519AccountId());
    }

    public function testAccountResponseOptionalFields(): void
    {
        $minimalJson = [
            'account_id' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'sequence' => '123456789012',
            'subentry_count' => 0,
            'last_modified_ledger' => 1234567,
            'last_modified_time' => '2019-02-28T17:00:00Z',
            'thresholds' => [
                'low_threshold' => 0,
                'med_threshold' => 0,
                'high_threshold' => 0
            ],
            'flags' => [
                'auth_required' => false,
                'auth_revocable' => false,
                'auth_immutable' => false,
                'auth_clawback_enabled' => false
            ],
            'balances' => [],
            'signers' => [],
            'data' => [],
            'num_sponsoring' => 0,
            'num_sponsored' => 0,
            'paging_token' => '123456789012'
        ];

        $response = AccountResponse::fromJson($minimalJson);

        $this->assertNull($response->getInflationDestination());
        $this->assertNull($response->getHomeDomain());
        $this->assertNull($response->getSponsor());
        $this->assertNull($response->getSequenceLedger());
        $this->assertNull($response->getSequenceTime());
    }

    // AccountBalanceResponse Tests

    public function testAccountBalanceResponseNative(): void
    {
        $json = [
            'balance' => '10000.0000000',
            'asset_type' => 'native',
            'buying_liabilities' => '100.0000000',
            'selling_liabilities' => '200.0000000'
        ];

        $balance = AccountBalanceResponse::fromJson($json);

        $this->assertEquals('10000.0000000', $balance->getBalance());
        $this->assertEquals('native', $balance->getAssetType());
        $this->assertNull($balance->getAssetCode());
        $this->assertNull($balance->getAssetIssuer());
        $this->assertNull($balance->getLiquidityPoolId());
        $this->assertEquals('100.0000000', $balance->getBuyingLiabilities());
        $this->assertEquals('200.0000000', $balance->getSellingLiabilities());
        $this->assertNull($balance->getLimit());
        $this->assertNull($balance->getSponsor());
        $this->assertNull($balance->getIsAuthorized());
        $this->assertNull($balance->getIsAuthorizedToMaintainLiabilities());
        $this->assertNull($balance->getIsClawbackEnabled());
        $this->assertNull($balance->getLastModifiedLedger());
    }

    public function testAccountBalanceResponseCreditAsset(): void
    {
        $json = [
            'balance' => '5000.0000000',
            'limit' => '10000.0000000',
            'asset_type' => 'credit_alphanum12',
            'asset_code' => 'LONGASSET',
            'asset_issuer' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
            'buying_liabilities' => '50.0000000',
            'selling_liabilities' => '100.0000000',
            'is_authorized' => true,
            'is_authorized_to_maintain_liabilities' => false,
            'is_clawback_enabled' => true,
            'last_modified_ledger' => 1234500,
            'sponsor' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7'
        ];

        $balance = AccountBalanceResponse::fromJson($json);

        $this->assertEquals('5000.0000000', $balance->getBalance());
        $this->assertEquals('credit_alphanum12', $balance->getAssetType());
        $this->assertEquals('LONGASSET', $balance->getAssetCode());
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $balance->getAssetIssuer());
        $this->assertEquals('10000.0000000', $balance->getLimit());
        $this->assertTrue($balance->getIsAuthorized());
        $this->assertFalse($balance->getIsAuthorizedToMaintainLiabilities());
        $this->assertTrue($balance->getIsClawbackEnabled());
        $this->assertEquals(1234500, $balance->getLastModifiedLedger());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $balance->getSponsor());
    }

    public function testAccountBalanceResponseLiquidityPool(): void
    {
        $json = [
            'balance' => '250.0000000',
            'asset_type' => 'liquidity_pool_shares',
            'liquidity_pool_id' => 'dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7'
        ];

        $balance = AccountBalanceResponse::fromJson($json);

        $this->assertEquals('250.0000000', $balance->getBalance());
        $this->assertEquals('liquidity_pool_shares', $balance->getAssetType());
        $this->assertEquals('dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7', $balance->getLiquidityPoolId());
        $this->assertNull($balance->getAssetCode());
        $this->assertNull($balance->getAssetIssuer());
    }

    // AccountSignerResponse Tests

    public function testAccountSignerResponseEd25519(): void
    {
        $json = [
            'key' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'weight' => 5,
            'type' => 'ed25519_public_key',
            'sponsor' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5'
        ];

        $signer = AccountSignerResponse::fromJson($json);

        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $signer->getKey());
        $this->assertEquals(5, $signer->getWeight());
        $this->assertEquals('ed25519_public_key', $signer->getType());
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $signer->getSponsor());
    }

    public function testAccountSignerResponseHashX(): void
    {
        $json = [
            'key' => 'XABC123',
            'weight' => 3,
            'type' => 'sha256_hash'
        ];

        $signer = AccountSignerResponse::fromJson($json);

        $this->assertEquals('XABC123', $signer->getKey());
        $this->assertEquals(3, $signer->getWeight());
        $this->assertEquals('sha256_hash', $signer->getType());
        $this->assertNull($signer->getSponsor());
    }

    public function testAccountSignerResponsePreAuth(): void
    {
        $json = [
            'key' => 'TABC123',
            'weight' => 10,
            'type' => 'preauth_tx'
        ];

        $signer = AccountSignerResponse::fromJson($json);

        $this->assertEquals('TABC123', $signer->getKey());
        $this->assertEquals(10, $signer->getWeight());
        $this->assertEquals('preauth_tx', $signer->getType());
    }

    // AccountThresholdsResponse Tests

    public function testAccountThresholdsResponse(): void
    {
        $json = [
            'low_threshold' => 1,
            'med_threshold' => 3,
            'high_threshold' => 5
        ];

        $thresholds = AccountThresholdsResponse::fromJson($json);

        $this->assertEquals(1, $thresholds->getLowThreshold());
        $this->assertEquals(3, $thresholds->getMedThreshold());
        $this->assertEquals(5, $thresholds->getHighThreshold());
    }

    public function testAccountThresholdsResponseZero(): void
    {
        $json = [
            'low_threshold' => 0,
            'med_threshold' => 0,
            'high_threshold' => 0
        ];

        $thresholds = AccountThresholdsResponse::fromJson($json);

        $this->assertEquals(0, $thresholds->getLowThreshold());
        $this->assertEquals(0, $thresholds->getMedThreshold());
        $this->assertEquals(0, $thresholds->getHighThreshold());
    }

    // AccountFlagsResponse Tests

    public function testAccountFlagsResponseAllTrue(): void
    {
        $json = [
            'auth_required' => true,
            'auth_revocable' => true,
            'auth_immutable' => true,
            'auth_clawback_enabled' => true
        ];

        $flags = AccountFlagsResponse::fromJson($json);

        $this->assertTrue($flags->isAuthRequired());
        $this->assertTrue($flags->isAuthRevocable());
        $this->assertTrue($flags->isAuthImmutable());
        $this->assertTrue($flags->isAuthClawbackEnabled());
    }

    public function testAccountFlagsResponseAllFalse(): void
    {
        $json = [
            'auth_required' => false,
            'auth_revocable' => false,
            'auth_immutable' => false,
            'auth_clawback_enabled' => false
        ];

        $flags = AccountFlagsResponse::fromJson($json);

        $this->assertFalse($flags->isAuthRequired());
        $this->assertFalse($flags->isAuthRevocable());
        $this->assertFalse($flags->isAuthImmutable());
        $this->assertFalse($flags->isAuthClawbackEnabled());
    }

    // AccountDataResponse Tests

    public function testAccountDataResponse(): void
    {
        $json = [
            'data' => [
                'config' => 'dGVzdCBkYXRh',
                'metadata' => 'YW5vdGhlciB0ZXN0',
                'settings' => 'c2V0dGluZ3MgZGF0YQ=='
            ]
        ];

        $data = AccountDataResponse::fromJson($json);

        $this->assertCount(3, $data->getData());
        $this->assertArrayHasKey('config', $data->getData());
        $this->assertArrayHasKey('metadata', $data->getData());
        $this->assertArrayHasKey('settings', $data->getData());

        $this->assertEquals('dGVzdCBkYXRh', $data->getBase64Encoded('config'));
        $this->assertEquals('test data', $data->get('config'));

        $this->assertEquals('YW5vdGhlciB0ZXN0', $data->getBase64Encoded('metadata'));
        $this->assertEquals('another test', $data->get('metadata'));

        $this->assertEquals('c2V0dGluZ3MgZGF0YQ==', $data->getBase64Encoded('settings'));
        $this->assertEquals('settings data', $data->get('settings'));

        $keys = $data->getKeys();
        $this->assertCount(3, $keys);
        $this->assertContains('config', $keys);
        $this->assertContains('metadata', $keys);
        $this->assertContains('settings', $keys);
    }

    public function testAccountDataResponseEmpty(): void
    {
        $json = ['data' => []];

        $data = AccountDataResponse::fromJson($json);

        $this->assertCount(0, $data->getData());
        $this->assertNull($data->get('nonexistent'));
        $this->assertNull($data->getBase64Encoded('nonexistent'));
    }

    // AccountDataValueResponse Tests

    public function testAccountDataValueResponse(): void
    {
        $json = ['value' => 'dGVzdCBkYXRh'];

        $dataValue = AccountDataValueResponse::fromJson($json);

        $this->assertEquals('dGVzdCBkYXRh', $dataValue->getValue());
        $this->assertEquals('test data', $dataValue->getDecodedValue());
    }

    public function testAccountDataValueResponseEmptyValue(): void
    {
        $json = ['value' => ''];

        $dataValue = AccountDataValueResponse::fromJson($json);

        $this->assertEquals('', $dataValue->getValue());
    }
}
