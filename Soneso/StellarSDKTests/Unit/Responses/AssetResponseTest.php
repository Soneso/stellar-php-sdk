<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Responses;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Responses\Asset\AssetResponse;
use Soneso\StellarSDK\Responses\Asset\AssetAccountsResponse;
use Soneso\StellarSDK\Responses\Asset\AssetBalancesResponse;
use Soneso\StellarSDK\Responses\Asset\AssetFlagsResponse;
use Soneso\StellarSDK\Responses\Asset\AssetLinksResponse;
use Soneso\StellarSDK\Responses\Asset\AssetsResponse;
use Soneso\StellarSDK\Responses\Asset\AssetsPageResponse;

/**
 * Unit tests for all Asset Response classes
 *
 * Tests JSON parsing and getter methods for Asset-related response classes.
 * Covers AssetResponse, AssetAccountsResponse, AssetBalancesResponse,
 * AssetFlagsResponse, AssetLinksResponse, AssetsResponse, and AssetsPageResponse.
 */
class AssetResponseTest extends TestCase
{
    /**
     * Helper method to create complete asset JSON data for credit_alphanum4 asset
     */
    private function getCompleteAssetAlphanum4Json(): array
    {
        return [
            'asset_type' => 'credit_alphanum4',
            'asset_code' => 'USD',
            'asset_issuer' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
            'paging_token' => 'USD_GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5_credit_alphanum4',
            'accounts' => [
                'authorized' => 1500,
                'authorized_to_maintain_liabilities' => 150,
                'unauthorized' => 50
            ],
            'balances' => [
                'authorized' => '15000000.0000000',
                'authorized_to_maintain_liabilities' => '1500000.0000000',
                'unauthorized' => '500000.0000000'
            ],
            'claimable_balances_amount' => '100000.0000000',
            'liquidity_pools_amount' => '250000.0000000',
            'contracts_amount' => '75000.0000000',
            'num_claimable_balances' => 25,
            'num_liquidity_pools' => 10,
            'num_contracts' => 5,
            'flags' => [
                'auth_required' => true,
                'auth_revocable' => true,
                'auth_immutable' => false,
                'auth_clawback_enabled' => true
            ],
            '_links' => [
                'toml' => [
                    'href' => 'https://example.com/.well-known/stellar.toml'
                ]
            ]
        ];
    }

    /**
     * Helper method to create complete asset JSON data for credit_alphanum12 asset
     */
    private function getCompleteAssetAlphanum12Json(): array
    {
        return [
            'asset_type' => 'credit_alphanum12',
            'asset_code' => 'LONGASSETCODE',
            'asset_issuer' => 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7',
            'paging_token' => 'LONGASSETCODE_GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7_credit_alphanum12',
            'accounts' => [
                'authorized' => 2500,
                'authorized_to_maintain_liabilities' => 250,
                'unauthorized' => 100
            ],
            'balances' => [
                'authorized' => '25000000.0000000',
                'authorized_to_maintain_liabilities' => '2500000.0000000',
                'unauthorized' => '1000000.0000000'
            ],
            'claimable_balances_amount' => '200000.0000000',
            'liquidity_pools_amount' => '500000.0000000',
            'contracts_amount' => '150000.0000000',
            'num_claimable_balances' => 50,
            'num_liquidity_pools' => 20,
            'num_contracts' => 10,
            'flags' => [
                'auth_required' => false,
                'auth_revocable' => false,
                'auth_immutable' => true,
                'auth_clawback_enabled' => false
            ],
            '_links' => [
                'toml' => [
                    'href' => 'https://issuer.example.com/.well-known/stellar.toml'
                ]
            ]
        ];
    }

    /**
     * Helper method to create native asset JSON data
     */
    private function getNativeAssetJson(): array
    {
        return [
            'asset_type' => 'native',
            'paging_token' => 'native',
            'accounts' => [
                'authorized' => 50000,
                'authorized_to_maintain_liabilities' => 5000,
                'unauthorized' => 1000
            ],
            'balances' => [
                'authorized' => '500000000.0000000',
                'authorized_to_maintain_liabilities' => '50000000.0000000',
                'unauthorized' => '10000000.0000000'
            ],
            'claimable_balances_amount' => '5000000.0000000',
            'liquidity_pools_amount' => '10000000.0000000',
            'num_claimable_balances' => 1000,
            'num_liquidity_pools' => 500,
            'flags' => [
                'auth_required' => false,
                'auth_revocable' => false,
                'auth_immutable' => false,
                'auth_clawback_enabled' => false
            ],
            '_links' => [
                'toml' => [
                    'href' => 'https://stellar.org/.well-known/stellar.toml'
                ]
            ]
        ];
    }

    // AssetResponse Tests

    public function testAssetResponseFromJsonAlphanum4(): void
    {
        $json = $this->getCompleteAssetAlphanum4Json();
        $response = AssetResponse::fromJson($json);

        $this->assertEquals('credit_alphanum4', $response->getAssetType());
        $this->assertEquals('USD', $response->getAssetCode());
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $response->getAssetIssuer());
        $this->assertEquals('USD_GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5_credit_alphanum4', $response->getPagingToken());
        $this->assertEquals('100000.0000000', $response->getClaimableBalancesAmount());
        $this->assertEquals('250000.0000000', $response->getLiquidityPoolsAmount());
        $this->assertEquals('75000.0000000', $response->getContractsAmount());
        $this->assertEquals(25, $response->getNumClaimableBalances());
        $this->assertEquals(10, $response->getNumLiquidityPools());
        $this->assertEquals(5, $response->getNumContracts());
    }

    public function testAssetResponseFromJsonAlphanum12(): void
    {
        $json = $this->getCompleteAssetAlphanum12Json();
        $response = AssetResponse::fromJson($json);

        $this->assertEquals('credit_alphanum12', $response->getAssetType());
        $this->assertEquals('LONGASSETCODE', $response->getAssetCode());
        $this->assertEquals('GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7', $response->getAssetIssuer());
        $this->assertEquals('LONGASSETCODE_GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7_credit_alphanum12', $response->getPagingToken());
        $this->assertEquals('200000.0000000', $response->getClaimableBalancesAmount());
        $this->assertEquals('500000.0000000', $response->getLiquidityPoolsAmount());
        $this->assertEquals('150000.0000000', $response->getContractsAmount());
        $this->assertEquals(50, $response->getNumClaimableBalances());
        $this->assertEquals(20, $response->getNumLiquidityPools());
        $this->assertEquals(10, $response->getNumContracts());
    }

    public function testAssetResponseFromJsonNative(): void
    {
        $json = $this->getNativeAssetJson();
        $response = AssetResponse::fromJson($json);

        $this->assertEquals('native', $response->getAssetType());
        $this->assertNull($response->getAssetCode());
        $this->assertNull($response->getAssetIssuer());
        $this->assertEquals('native', $response->getPagingToken());
        $this->assertEquals('5000000.0000000', $response->getClaimableBalancesAmount());
        $this->assertEquals('10000000.0000000', $response->getLiquidityPoolsAmount());
        $this->assertEquals(1000, $response->getNumClaimableBalances());
        $this->assertEquals(500, $response->getNumLiquidityPools());
    }

    public function testAssetResponseAccountsAlphanum4(): void
    {
        $json = $this->getCompleteAssetAlphanum4Json();
        $response = AssetResponse::fromJson($json);
        $accounts = $response->getAccounts();

        $this->assertInstanceOf(AssetAccountsResponse::class, $accounts);
        $this->assertEquals(1500, $accounts->getAuthorized());
        $this->assertEquals(150, $accounts->getAuthorizedToMaintainLiabilities());
        $this->assertEquals(50, $accounts->getUnauthorized());
    }

    public function testAssetResponseBalancesAlphanum4(): void
    {
        $json = $this->getCompleteAssetAlphanum4Json();
        $response = AssetResponse::fromJson($json);
        $balances = $response->getBalances();

        $this->assertInstanceOf(AssetBalancesResponse::class, $balances);
        $this->assertEquals('15000000.0000000', $balances->getAuthorized());
        $this->assertEquals('1500000.0000000', $balances->getAuthorizedToMaintainLiabilities());
        $this->assertEquals('500000.0000000', $balances->getUnauthorized());
    }

    public function testAssetResponseFlagsAlphanum4(): void
    {
        $json = $this->getCompleteAssetAlphanum4Json();
        $response = AssetResponse::fromJson($json);
        $flags = $response->getFlags();

        $this->assertInstanceOf(AssetFlagsResponse::class, $flags);
        $this->assertTrue($flags->isAuthRequired());
        $this->assertTrue($flags->isAuthRevocable());
        $this->assertFalse($flags->isAuthImmutable());
        $this->assertTrue($flags->isAuthClawbackEnabled());
    }

    public function testAssetResponseFlagsAlphanum12(): void
    {
        $json = $this->getCompleteAssetAlphanum12Json();
        $response = AssetResponse::fromJson($json);
        $flags = $response->getFlags();

        $this->assertInstanceOf(AssetFlagsResponse::class, $flags);
        $this->assertFalse($flags->isAuthRequired());
        $this->assertFalse($flags->isAuthRevocable());
        $this->assertTrue($flags->isAuthImmutable());
        $this->assertFalse($flags->isAuthClawbackEnabled());
    }

    public function testAssetResponseLinksAlphanum4(): void
    {
        $json = $this->getCompleteAssetAlphanum4Json();
        $response = AssetResponse::fromJson($json);
        $links = $response->getLinks();

        $this->assertInstanceOf(AssetLinksResponse::class, $links);
        $this->assertEquals('https://example.com/.well-known/stellar.toml', $links->getToml()->getHref());
    }

    public function testAssetResponseSettersForContracts(): void
    {
        $json = $this->getCompleteAssetAlphanum4Json();
        $response = AssetResponse::fromJson($json);

        $this->assertEquals(5, $response->getNumContracts());
        $this->assertEquals('75000.0000000', $response->getContractsAmount());

        $response->setNumContracts(15);
        $response->setContractsAmount('150000.0000000');

        $this->assertEquals(15, $response->getNumContracts());
        $this->assertEquals('150000.0000000', $response->getContractsAmount());

        $response->setNumContracts(null);
        $response->setContractsAmount(null);

        $this->assertNull($response->getNumContracts());
        $this->assertNull($response->getContractsAmount());
    }

    public function testAssetResponseOptionalFields(): void
    {
        $minimalJson = [
            'asset_type' => 'credit_alphanum4',
            'asset_code' => 'XYZ',
            'asset_issuer' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5',
            'paging_token' => 'XYZ_GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5_credit_alphanum4',
            'accounts' => [
                'authorized' => 100,
                'authorized_to_maintain_liabilities' => 10,
                'unauthorized' => 5
            ],
            'balances' => [
                'authorized' => '1000000.0000000',
                'authorized_to_maintain_liabilities' => '100000.0000000',
                'unauthorized' => '50000.0000000'
            ],
            'claimable_balances_amount' => '10000.0000000',
            'liquidity_pools_amount' => '20000.0000000',
            'num_claimable_balances' => 5,
            'num_liquidity_pools' => 2,
            'flags' => [
                'auth_required' => false,
                'auth_revocable' => false,
                'auth_immutable' => false,
                'auth_clawback_enabled' => false
            ],
            '_links' => [
                'toml' => [
                    'href' => 'https://example.com/.well-known/stellar.toml'
                ]
            ]
        ];

        $response = AssetResponse::fromJson($minimalJson);

        $this->assertNull($response->getNumContracts());
        $this->assertNull($response->getContractsAmount());
    }

    public function testAssetResponseNativeOptionalFields(): void
    {
        $minimalNativeJson = [
            'asset_type' => 'native',
            'paging_token' => 'native',
            'accounts' => [
                'authorized' => 10000,
                'authorized_to_maintain_liabilities' => 1000,
                'unauthorized' => 100
            ],
            'balances' => [
                'authorized' => '100000000.0000000',
                'authorized_to_maintain_liabilities' => '10000000.0000000',
                'unauthorized' => '1000000.0000000'
            ],
            'claimable_balances_amount' => '1000000.0000000',
            'liquidity_pools_amount' => '2000000.0000000',
            'num_claimable_balances' => 200,
            'num_liquidity_pools' => 100,
            'flags' => [
                'auth_required' => false,
                'auth_revocable' => false,
                'auth_immutable' => false,
                'auth_clawback_enabled' => false
            ],
            '_links' => [
                'toml' => [
                    'href' => 'https://stellar.org/.well-known/stellar.toml'
                ]
            ]
        ];

        $response = AssetResponse::fromJson($minimalNativeJson);

        $this->assertEquals('native', $response->getAssetType());
        $this->assertNull($response->getAssetCode());
        $this->assertNull($response->getAssetIssuer());
        $this->assertNull($response->getNumContracts());
        $this->assertNull($response->getContractsAmount());
    }

    // AssetAccountsResponse Tests

    public function testAssetAccountsResponseFromJson(): void
    {
        $json = [
            'authorized' => 1000,
            'authorized_to_maintain_liabilities' => 100,
            'unauthorized' => 50
        ];

        $response = AssetAccountsResponse::fromJson($json);

        $this->assertEquals(1000, $response->getAuthorized());
        $this->assertEquals(100, $response->getAuthorizedToMaintainLiabilities());
        $this->assertEquals(50, $response->getUnauthorized());
    }

    public function testAssetAccountsResponseZeroCounts(): void
    {
        $json = [
            'authorized' => 0,
            'authorized_to_maintain_liabilities' => 0,
            'unauthorized' => 0
        ];

        $response = AssetAccountsResponse::fromJson($json);

        $this->assertEquals(0, $response->getAuthorized());
        $this->assertEquals(0, $response->getAuthorizedToMaintainLiabilities());
        $this->assertEquals(0, $response->getUnauthorized());
    }

    public function testAssetAccountsResponseLargeCounts(): void
    {
        $json = [
            'authorized' => 1000000,
            'authorized_to_maintain_liabilities' => 500000,
            'unauthorized' => 250000
        ];

        $response = AssetAccountsResponse::fromJson($json);

        $this->assertEquals(1000000, $response->getAuthorized());
        $this->assertEquals(500000, $response->getAuthorizedToMaintainLiabilities());
        $this->assertEquals(250000, $response->getUnauthorized());
    }

    // AssetBalancesResponse Tests

    public function testAssetBalancesResponseFromJson(): void
    {
        $json = [
            'authorized' => '10000000.0000000',
            'authorized_to_maintain_liabilities' => '1000000.0000000',
            'unauthorized' => '500000.0000000'
        ];

        $response = AssetBalancesResponse::fromJson($json);

        $this->assertEquals('10000000.0000000', $response->getAuthorized());
        $this->assertEquals('1000000.0000000', $response->getAuthorizedToMaintainLiabilities());
        $this->assertEquals('500000.0000000', $response->getUnauthorized());
    }

    public function testAssetBalancesResponseZeroBalances(): void
    {
        $json = [
            'authorized' => '0.0000000',
            'authorized_to_maintain_liabilities' => '0.0000000',
            'unauthorized' => '0.0000000'
        ];

        $response = AssetBalancesResponse::fromJson($json);

        $this->assertEquals('0.0000000', $response->getAuthorized());
        $this->assertEquals('0.0000000', $response->getAuthorizedToMaintainLiabilities());
        $this->assertEquals('0.0000000', $response->getUnauthorized());
    }

    public function testAssetBalancesResponseLargeBalances(): void
    {
        $json = [
            'authorized' => '9999999999999.9999999',
            'authorized_to_maintain_liabilities' => '8888888888888.8888888',
            'unauthorized' => '7777777777777.7777777'
        ];

        $response = AssetBalancesResponse::fromJson($json);

        $this->assertEquals('9999999999999.9999999', $response->getAuthorized());
        $this->assertEquals('8888888888888.8888888', $response->getAuthorizedToMaintainLiabilities());
        $this->assertEquals('7777777777777.7777777', $response->getUnauthorized());
    }

    public function testAssetBalancesResponsePrecision(): void
    {
        $json = [
            'authorized' => '123.4567890',
            'authorized_to_maintain_liabilities' => '456.7890123',
            'unauthorized' => '789.0123456'
        ];

        $response = AssetBalancesResponse::fromJson($json);

        $this->assertEquals('123.4567890', $response->getAuthorized());
        $this->assertEquals('456.7890123', $response->getAuthorizedToMaintainLiabilities());
        $this->assertEquals('789.0123456', $response->getUnauthorized());
    }

    // AssetFlagsResponse Tests

    public function testAssetFlagsResponseAllTrue(): void
    {
        $json = [
            'auth_required' => true,
            'auth_revocable' => true,
            'auth_immutable' => true,
            'auth_clawback_enabled' => true
        ];

        $response = AssetFlagsResponse::fromJson($json);

        $this->assertTrue($response->isAuthRequired());
        $this->assertTrue($response->isAuthRevocable());
        $this->assertTrue($response->isAuthImmutable());
        $this->assertTrue($response->isAuthClawbackEnabled());
    }

    public function testAssetFlagsResponseAllFalse(): void
    {
        $json = [
            'auth_required' => false,
            'auth_revocable' => false,
            'auth_immutable' => false,
            'auth_clawback_enabled' => false
        ];

        $response = AssetFlagsResponse::fromJson($json);

        $this->assertFalse($response->isAuthRequired());
        $this->assertFalse($response->isAuthRevocable());
        $this->assertFalse($response->isAuthImmutable());
        $this->assertFalse($response->isAuthClawbackEnabled());
    }

    public function testAssetFlagsResponseMixed(): void
    {
        $json = [
            'auth_required' => true,
            'auth_revocable' => false,
            'auth_immutable' => true,
            'auth_clawback_enabled' => false
        ];

        $response = AssetFlagsResponse::fromJson($json);

        $this->assertTrue($response->isAuthRequired());
        $this->assertFalse($response->isAuthRevocable());
        $this->assertTrue($response->isAuthImmutable());
        $this->assertFalse($response->isAuthClawbackEnabled());
    }

    public function testAssetFlagsResponseCommonPattern(): void
    {
        $json = [
            'auth_required' => true,
            'auth_revocable' => true,
            'auth_immutable' => false,
            'auth_clawback_enabled' => false
        ];

        $response = AssetFlagsResponse::fromJson($json);

        $this->assertTrue($response->isAuthRequired());
        $this->assertTrue($response->isAuthRevocable());
        $this->assertFalse($response->isAuthImmutable());
        $this->assertFalse($response->isAuthClawbackEnabled());
    }

    // AssetLinksResponse Tests

    public function testAssetLinksResponseFromJson(): void
    {
        $json = [
            'toml' => [
                'href' => 'https://example.com/.well-known/stellar.toml'
            ]
        ];

        $response = AssetLinksResponse::fromJson($json);

        $this->assertEquals('https://example.com/.well-known/stellar.toml', $response->getToml()->getHref());
    }

    public function testAssetLinksResponseDifferentUrls(): void
    {
        $json = [
            'toml' => [
                'href' => 'https://issuer.stellar.org/.well-known/stellar.toml'
            ]
        ];

        $response = AssetLinksResponse::fromJson($json);

        $this->assertEquals('https://issuer.stellar.org/.well-known/stellar.toml', $response->getToml()->getHref());
    }

    public function testAssetLinksResponseHttpUrl(): void
    {
        $json = [
            'toml' => [
                'href' => 'http://testnet.stellar.org/.well-known/stellar.toml'
            ]
        ];

        $response = AssetLinksResponse::fromJson($json);

        $this->assertEquals('http://testnet.stellar.org/.well-known/stellar.toml', $response->getToml()->getHref());
    }

    // AssetsResponse Tests

    public function testAssetsResponseEmpty(): void
    {
        $assets = new AssetsResponse();

        $this->assertEquals(0, $assets->count());
        $this->assertEquals([], $assets->toArray());
    }

    public function testAssetsResponseConstructor(): void
    {
        $asset1 = AssetResponse::fromJson($this->getCompleteAssetAlphanum4Json());
        $asset2 = AssetResponse::fromJson($this->getCompleteAssetAlphanum12Json());
        $asset3 = AssetResponse::fromJson($this->getNativeAssetJson());

        $assets = new AssetsResponse($asset1, $asset2, $asset3);

        $this->assertEquals(3, $assets->count());
        $assetsArray = $assets->toArray();
        $this->assertCount(3, $assetsArray);
        $this->assertSame($asset1, $assetsArray[0]);
        $this->assertSame($asset2, $assetsArray[1]);
        $this->assertSame($asset3, $assetsArray[2]);
    }

    public function testAssetsResponseAdd(): void
    {
        $assets = new AssetsResponse();
        $this->assertEquals(0, $assets->count());

        $asset1 = AssetResponse::fromJson($this->getCompleteAssetAlphanum4Json());
        $assets->add($asset1);
        $this->assertEquals(1, $assets->count());

        $asset2 = AssetResponse::fromJson($this->getCompleteAssetAlphanum12Json());
        $assets->add($asset2);
        $this->assertEquals(2, $assets->count());

        $asset3 = AssetResponse::fromJson($this->getNativeAssetJson());
        $assets->add($asset3);
        $this->assertEquals(3, $assets->count());

        $assetsArray = $assets->toArray();
        $this->assertCount(3, $assetsArray);
    }

    public function testAssetsResponseIteration(): void
    {
        $asset1 = AssetResponse::fromJson($this->getCompleteAssetAlphanum4Json());
        $asset2 = AssetResponse::fromJson($this->getCompleteAssetAlphanum12Json());
        $asset3 = AssetResponse::fromJson($this->getNativeAssetJson());

        $assets = new AssetsResponse($asset1, $asset2, $asset3);

        $iteratedAssets = [];
        foreach ($assets as $asset) {
            $this->assertInstanceOf(AssetResponse::class, $asset);
            $iteratedAssets[] = $asset;
        }

        $this->assertCount(3, $iteratedAssets);
        $this->assertSame($asset1, $iteratedAssets[0]);
        $this->assertSame($asset2, $iteratedAssets[1]);
        $this->assertSame($asset3, $iteratedAssets[2]);
    }

    public function testAssetsResponseCurrent(): void
    {
        $asset1 = AssetResponse::fromJson($this->getCompleteAssetAlphanum4Json());
        $asset2 = AssetResponse::fromJson($this->getCompleteAssetAlphanum12Json());
        $asset3 = AssetResponse::fromJson($this->getNativeAssetJson());

        $assets = new AssetsResponse($asset1, $asset2, $asset3);

        $assets->rewind();
        $this->assertSame($asset1, $assets->current());
        $assets->next();
        $this->assertSame($asset2, $assets->current());
        $assets->next();
        $this->assertSame($asset3, $assets->current());
    }

    // AssetsPageResponse Tests

    public function testAssetsPageResponseFromJson(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/assets?cursor=&limit=10&order=asc'],
                'next' => ['href' => 'https://horizon.stellar.org/assets?cursor=next_cursor&limit=10&order=asc'],
                'prev' => ['href' => 'https://horizon.stellar.org/assets?cursor=prev_cursor&limit=10&order=desc']
            ],
            '_embedded' => [
                'records' => [
                    $this->getCompleteAssetAlphanum4Json(),
                    $this->getCompleteAssetAlphanum12Json(),
                    $this->getNativeAssetJson()
                ]
            ]
        ];

        $page = AssetsPageResponse::fromJson($json);
        $assets = $page->getAssets();

        $this->assertInstanceOf(AssetsResponse::class, $assets);
        $this->assertEquals(3, $assets->count());

        $assetsArray = $assets->toArray();
        $this->assertEquals('credit_alphanum4', $assetsArray[0]->getAssetType());
        $this->assertEquals('USD', $assetsArray[0]->getAssetCode());
        $this->assertEquals('credit_alphanum12', $assetsArray[1]->getAssetType());
        $this->assertEquals('LONGASSETCODE', $assetsArray[1]->getAssetCode());
        $this->assertEquals('native', $assetsArray[2]->getAssetType());
    }

    public function testAssetsPageResponseEmpty(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/assets?cursor=&limit=10&order=asc'],
                'next' => ['href' => 'https://horizon.stellar.org/assets?cursor=&limit=10&order=asc'],
                'prev' => ['href' => 'https://horizon.stellar.org/assets?cursor=&limit=10&order=desc']
            ],
            '_embedded' => [
                'records' => []
            ]
        ];

        $page = AssetsPageResponse::fromJson($json);
        $assets = $page->getAssets();

        $this->assertInstanceOf(AssetsResponse::class, $assets);
        $this->assertEquals(0, $assets->count());
    }

    public function testAssetsPageResponseSingleAsset(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/assets?asset_code=USD'],
                'next' => ['href' => 'https://horizon.stellar.org/assets?cursor=next&limit=10&order=asc'],
                'prev' => ['href' => 'https://horizon.stellar.org/assets?cursor=prev&limit=10&order=desc']
            ],
            '_embedded' => [
                'records' => [
                    $this->getCompleteAssetAlphanum4Json()
                ]
            ]
        ];

        $page = AssetsPageResponse::fromJson($json);
        $assets = $page->getAssets();

        $this->assertEquals(1, $assets->count());
        $assetsArray = $assets->toArray();
        $this->assertEquals('USD', $assetsArray[0]->getAssetCode());
    }

    public function testAssetsPageResponseMultipleAssets(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/assets?limit=5'],
                'next' => ['href' => 'https://horizon.stellar.org/assets?cursor=next&limit=5&order=asc'],
                'prev' => ['href' => 'https://horizon.stellar.org/assets?cursor=prev&limit=5&order=desc']
            ],
            '_embedded' => [
                'records' => [
                    $this->getCompleteAssetAlphanum4Json(),
                    $this->getCompleteAssetAlphanum12Json(),
                    $this->getNativeAssetJson(),
                    $this->getCompleteAssetAlphanum4Json(),
                    $this->getCompleteAssetAlphanum12Json()
                ]
            ]
        ];

        $page = AssetsPageResponse::fromJson($json);
        $assets = $page->getAssets();

        $this->assertEquals(5, $assets->count());
    }

    // Integration Tests

    public function testAssetResponseCompleteIntegration(): void
    {
        $json = $this->getCompleteAssetAlphanum4Json();
        $asset = AssetResponse::fromJson($json);

        $this->assertEquals('credit_alphanum4', $asset->getAssetType());
        $this->assertEquals('USD', $asset->getAssetCode());
        $this->assertEquals('GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5', $asset->getAssetIssuer());

        $accounts = $asset->getAccounts();
        $this->assertEquals(1500, $accounts->getAuthorized());
        $this->assertEquals(150, $accounts->getAuthorizedToMaintainLiabilities());
        $this->assertEquals(50, $accounts->getUnauthorized());

        $balances = $asset->getBalances();
        $this->assertEquals('15000000.0000000', $balances->getAuthorized());
        $this->assertEquals('1500000.0000000', $balances->getAuthorizedToMaintainLiabilities());
        $this->assertEquals('500000.0000000', $balances->getUnauthorized());

        $flags = $asset->getFlags();
        $this->assertTrue($flags->isAuthRequired());
        $this->assertTrue($flags->isAuthRevocable());
        $this->assertFalse($flags->isAuthImmutable());
        $this->assertTrue($flags->isAuthClawbackEnabled());

        $links = $asset->getLinks();
        $this->assertEquals('https://example.com/.well-known/stellar.toml', $links->getToml()->getHref());

        $this->assertEquals('100000.0000000', $asset->getClaimableBalancesAmount());
        $this->assertEquals('250000.0000000', $asset->getLiquidityPoolsAmount());
        $this->assertEquals('75000.0000000', $asset->getContractsAmount());
        $this->assertEquals(25, $asset->getNumClaimableBalances());
        $this->assertEquals(10, $asset->getNumLiquidityPools());
        $this->assertEquals(5, $asset->getNumContracts());
    }

    public function testAssetResponseNativeIntegration(): void
    {
        $json = $this->getNativeAssetJson();
        $asset = AssetResponse::fromJson($json);

        $this->assertEquals('native', $asset->getAssetType());
        $this->assertNull($asset->getAssetCode());
        $this->assertNull($asset->getAssetIssuer());

        $accounts = $asset->getAccounts();
        $this->assertEquals(50000, $accounts->getAuthorized());

        $balances = $asset->getBalances();
        $this->assertEquals('500000000.0000000', $balances->getAuthorized());

        $flags = $asset->getFlags();
        $this->assertFalse($flags->isAuthRequired());
        $this->assertFalse($flags->isAuthRevocable());
        $this->assertFalse($flags->isAuthImmutable());
        $this->assertFalse($flags->isAuthClawbackEnabled());

        $this->assertEquals('5000000.0000000', $asset->getClaimableBalancesAmount());
        $this->assertEquals('10000000.0000000', $asset->getLiquidityPoolsAmount());
        $this->assertEquals(1000, $asset->getNumClaimableBalances());
        $this->assertEquals(500, $asset->getNumLiquidityPools());
    }

    public function testAssetsPageWithDifferentAssetTypes(): void
    {
        $json = [
            '_links' => [
                'self' => ['href' => 'https://horizon.stellar.org/assets'],
                'next' => ['href' => 'https://horizon.stellar.org/assets?cursor=next'],
                'prev' => ['href' => 'https://horizon.stellar.org/assets?cursor=prev']
            ],
            '_embedded' => [
                'records' => [
                    $this->getNativeAssetJson(),
                    $this->getCompleteAssetAlphanum4Json(),
                    $this->getCompleteAssetAlphanum12Json()
                ]
            ]
        ];

        $page = AssetsPageResponse::fromJson($json);
        $assets = $page->getAssets();
        $assetsArray = $assets->toArray();

        $this->assertEquals(3, $assets->count());

        $nativeAsset = $assetsArray[0];
        $this->assertEquals('native', $nativeAsset->getAssetType());
        $this->assertNull($nativeAsset->getAssetCode());
        $this->assertNull($nativeAsset->getAssetIssuer());

        $alphanum4Asset = $assetsArray[1];
        $this->assertEquals('credit_alphanum4', $alphanum4Asset->getAssetType());
        $this->assertEquals('USD', $alphanum4Asset->getAssetCode());
        $this->assertNotNull($alphanum4Asset->getAssetIssuer());

        $alphanum12Asset = $assetsArray[2];
        $this->assertEquals('credit_alphanum12', $alphanum12Asset->getAssetType());
        $this->assertEquals('LONGASSETCODE', $alphanum12Asset->getAssetCode());
        $this->assertNotNull($alphanum12Asset->getAssetIssuer());
    }
}
