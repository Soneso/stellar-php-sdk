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
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum4;
use Soneso\StellarSDK\Requests\AccountsRequestBuilder;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Responses\Account\AccountDataValueResponse;
use Soneso\StellarSDK\Responses\Account\AccountsPageResponse;
use Soneso\StellarSDK\StellarSDK;

/**
 * Unit tests for AccountsRequestBuilder
 *
 * Tests URL building, parameter setting, filtering, and response parsing
 * for the accounts endpoint in Horizon.
 */
class AccountsRequestBuilderTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
    private const TEST_SIGNER_ID = 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5';
    private const TEST_SPONSOR_ID = 'GCEZWKCA5VLDNRLN3RPRJMRZOX3Z6G5CHCGSNFHEYVXM3XOJMDS674JZ';
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
     * Helper method to get sample account JSON response
     */
    private function getSampleAccountJson(): string
    {
        return json_encode([
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/accounts/' . self::TEST_ACCOUNT_ID],
                'transactions' => ['href' => 'https://horizon-testnet.stellar.org/accounts/' . self::TEST_ACCOUNT_ID . '/transactions{?cursor,limit,order}', 'templated' => true],
                'operations' => ['href' => 'https://horizon-testnet.stellar.org/accounts/' . self::TEST_ACCOUNT_ID . '/operations{?cursor,limit,order}', 'templated' => true],
                'payments' => ['href' => 'https://horizon-testnet.stellar.org/accounts/' . self::TEST_ACCOUNT_ID . '/payments{?cursor,limit,order}', 'templated' => true],
                'effects' => ['href' => 'https://horizon-testnet.stellar.org/accounts/' . self::TEST_ACCOUNT_ID . '/effects{?cursor,limit,order}', 'templated' => true],
                'offers' => ['href' => 'https://horizon-testnet.stellar.org/accounts/' . self::TEST_ACCOUNT_ID . '/offers{?cursor,limit,order}', 'templated' => true],
                'trades' => ['href' => 'https://horizon-testnet.stellar.org/accounts/' . self::TEST_ACCOUNT_ID . '/trades{?cursor,limit,order}', 'templated' => true],
                'data' => ['href' => 'https://horizon-testnet.stellar.org/accounts/' . self::TEST_ACCOUNT_ID . '/data/{key}', 'templated' => true]
            ],
            'id' => self::TEST_ACCOUNT_ID,
            'account_id' => self::TEST_ACCOUNT_ID,
            'sequence' => '123456789012',
            'subentry_count' => 3,
            'home_domain' => 'example.com',
            'last_modified_ledger' => 1234567,
            'last_modified_time' => '2024-01-20T12:00:00Z',
            'thresholds' => [
                'low_threshold' => 0,
                'med_threshold' => 2,
                'high_threshold' => 5
            ],
            'flags' => [
                'auth_required' => false,
                'auth_revocable' => false,
                'auth_immutable' => false,
                'auth_clawback_enabled' => false
            ],
            'balances' => [
                [
                    'balance' => '10000.0000000',
                    'asset_type' => 'native',
                    'buying_liabilities' => '0.0000000',
                    'selling_liabilities' => '0.0000000'
                ]
            ],
            'signers' => [
                [
                    'key' => self::TEST_ACCOUNT_ID,
                    'weight' => 1,
                    'type' => 'ed25519_public_key'
                ]
            ],
            'data' => [],
            'num_sponsoring' => 0,
            'num_sponsored' => 0,
            'paging_token' => self::TEST_ACCOUNT_ID
        ]);
    }

    /**
     * Helper method to get sample accounts page JSON response
     */
    private function getSampleAccountsPageJson(): string
    {
        $account = json_decode($this->getSampleAccountJson(), true);
        return json_encode([
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/accounts?cursor=&limit=10&order=asc'],
                'next' => ['href' => 'https://horizon-testnet.stellar.org/accounts?cursor=123456789&limit=10&order=asc'],
                'prev' => ['href' => 'https://horizon-testnet.stellar.org/accounts?cursor=123456789&limit=10&order=desc']
            ],
            '_embedded' => [
                'records' => [$account]
            ]
        ]);
    }

    /**
     * Helper method to get sample account data value JSON response
     */
    private function getSampleAccountDataValueJson(): string
    {
        return json_encode([
            'value' => 'dGVzdCBkYXRh'
        ]);
    }

    /**
     * Test basic URL building for accounts endpoint
     */
    public function testBuildBasicUrl(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $url = $builder->buildUrl();

        $this->assertStringContainsString('accounts?', $url);
    }

    /**
     * Test cursor parameter setting
     */
    public function testCursorParameter(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $builder->cursor(self::TEST_CURSOR);
        $url = $builder->buildUrl();

        $this->assertStringContainsString('cursor=' . self::TEST_CURSOR, $url);
    }

    /**
     * Test limit parameter setting
     */
    public function testLimitParameter(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

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
        $builder = new AccountsRequestBuilder($client);

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
        $builder = new AccountsRequestBuilder($client);

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
        $builder = new AccountsRequestBuilder($client);

        $builder->cursor(self::TEST_CURSOR)
                ->limit(100)
                ->order('desc');
        $url = $builder->buildUrl();

        $this->assertStringContainsString('cursor=' . self::TEST_CURSOR, $url);
        $this->assertStringContainsString('limit=100', $url);
        $this->assertStringContainsString('order=desc', $url);
    }

    /**
     * Test forSigner filter
     */
    public function testForSigner(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $builder->forSigner(self::TEST_SIGNER_ID);
        $url = $builder->buildUrl();

        $this->assertStringContainsString('signer=' . self::TEST_SIGNER_ID, $url);
    }

    /**
     * Test forAsset filter
     */
    public function testForAsset(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $asset = new AssetTypeCreditAlphanum4('USD', self::TEST_SIGNER_ID);
        $builder->forAsset($asset);
        $url = $builder->buildUrl();

        $expectedAsset = 'USD%3A' . self::TEST_SIGNER_ID;
        $this->assertStringContainsString('asset=', $url);
        $this->assertStringContainsString($expectedAsset, $url);
    }

    /**
     * Test forLiquidityPool filter with hex ID
     */
    public function testForLiquidityPoolHex(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $builder->forLiquidityPool(self::TEST_LIQUIDITY_POOL_ID);
        $url = $builder->buildUrl();

        $this->assertStringContainsString('liquidity_pool=' . self::TEST_LIQUIDITY_POOL_ID, $url);
    }

    /**
     * Test forSponsor filter
     */
    public function testForSponsor(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $builder->forSponsor(self::TEST_SPONSOR_ID);
        $url = $builder->buildUrl();

        $this->assertStringContainsString('sponsor=' . self::TEST_SPONSOR_ID, $url);
    }

    /**
     * Test that forSigner and forAsset are mutually exclusive
     */
    public function testForSignerAndForAssetMutuallyExclusive(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot set both asset and signer');

        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $asset = new AssetTypeCreditAlphanum4('USD', self::TEST_SIGNER_ID);
        $builder->forAsset($asset);
        $builder->forSigner(self::TEST_SIGNER_ID);
    }

    /**
     * Test that forSigner and forLiquidityPool are mutually exclusive
     */
    public function testForSignerAndForLiquidityPoolMutuallyExclusive(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot set both liquidity_pool and signer');

        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $builder->forLiquidityPool(self::TEST_LIQUIDITY_POOL_ID);
        $builder->forSigner(self::TEST_SIGNER_ID);
    }

    /**
     * Test that forSigner and forSponsor are mutually exclusive
     */
    public function testForSignerAndForSponsorMutuallyExclusive(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot set both sponsor and signer');

        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $builder->forSponsor(self::TEST_SPONSOR_ID);
        $builder->forSigner(self::TEST_SIGNER_ID);
    }

    /**
     * Test that forAsset and forLiquidityPool are mutually exclusive
     */
    public function testForAssetAndForLiquidityPoolMutuallyExclusive(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot set both liquidity_pool and asset');

        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $asset = new AssetTypeCreditAlphanum4('USD', self::TEST_SIGNER_ID);
        $builder->forAsset($asset);
        $builder->forLiquidityPool(self::TEST_LIQUIDITY_POOL_ID);
    }

    /**
     * Test that forAsset and forSponsor are mutually exclusive
     */
    public function testForAssetAndForSponsorMutuallyExclusive(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot set both sponsor and asset');

        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $asset = new AssetTypeCreditAlphanum4('USD', self::TEST_SIGNER_ID);
        $builder->forAsset($asset);
        $builder->forSponsor(self::TEST_SPONSOR_ID);
    }

    /**
     * Test that forLiquidityPool and forSponsor are mutually exclusive
     */
    public function testForLiquidityPoolAndForSponsorMutuallyExclusive(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot set both sponsor and liquidity_pool');

        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $builder->forLiquidityPool(self::TEST_LIQUIDITY_POOL_ID);
        $builder->forSponsor(self::TEST_SPONSOR_ID);
    }

    /**
     * Test account method with mocked response
     */
    public function testAccount(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountJson())
        ]);
        $builder = new AccountsRequestBuilder($client);

        $response = $builder->account(self::TEST_ACCOUNT_ID);

        $this->assertInstanceOf(AccountResponse::class, $response);
        $this->assertEquals(self::TEST_ACCOUNT_ID, $response->getAccountId());
        $this->assertEquals('123456789012', $response->getSequenceNumber());
        $this->assertEquals('example.com', $response->getHomeDomain());
    }

    /**
     * Test accountData method with mocked response
     */
    public function testAccountData(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountDataValueJson())
        ]);
        $builder = new AccountsRequestBuilder($client);

        $response = $builder->accountData(self::TEST_ACCOUNT_ID, 'config');

        $this->assertInstanceOf(AccountDataValueResponse::class, $response);
        $this->assertEquals('dGVzdCBkYXRh', $response->getValue());
        $this->assertEquals('test data', $response->getDecodedValue());
    }

    /**
     * Test execute method returns paginated accounts
     */
    public function testExecute(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountsPageJson())
        ]);
        $builder = new AccountsRequestBuilder($client);

        $response = $builder->limit(10)->execute();

        $this->assertInstanceOf(AccountsPageResponse::class, $response);
        $accounts = $response->getAccounts();
        $this->assertNotNull($accounts);
        $this->assertIsArray($accounts->toArray());
        $this->assertGreaterThan(0, count($accounts->toArray()));
    }

    /**
     * Test request method with full URL
     */
    public function testRequest(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountsPageJson())
        ]);
        $builder = new AccountsRequestBuilder($client);

        $response = $builder->request('https://horizon-testnet.stellar.org/accounts?limit=10');

        $this->assertInstanceOf(AccountsPageResponse::class, $response);
    }

    /**
     * Test forSigner with pagination
     */
    public function testForSignerWithPagination(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountsPageJson())
        ]);
        $builder = new AccountsRequestBuilder($client);

        $response = $builder->forSigner(self::TEST_SIGNER_ID)
                            ->limit(20)
                            ->order('desc')
                            ->execute();

        $this->assertInstanceOf(AccountsPageResponse::class, $response);
    }

    /**
     * Test URL building for account method
     */
    public function testAccountUrlBuilding(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountJson())
        ]);
        $builder = new AccountsRequestBuilder($client);

        // This will build the URL internally
        $builder->account(self::TEST_ACCOUNT_ID);

        // If we got here without exception, URL building worked
        $this->assertTrue(true);
    }

    /**
     * Test URL building for accountData method
     */
    public function testAccountDataUrlBuilding(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountDataValueJson())
        ]);
        $builder = new AccountsRequestBuilder($client);

        // This will build the URL internally
        $builder->accountData(self::TEST_ACCOUNT_ID, 'config');

        // If we got here without exception, URL building worked
        $this->assertTrue(true);
    }

    /**
     * Test method chaining returns correct instance
     */
    public function testMethodChaining(): void
    {
        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $result = $builder->cursor(self::TEST_CURSOR);
        $this->assertInstanceOf(AccountsRequestBuilder::class, $result);

        $result = $builder->limit(50);
        $this->assertInstanceOf(AccountsRequestBuilder::class, $result);

        $result = $builder->order('desc');
        $this->assertInstanceOf(AccountsRequestBuilder::class, $result);

        $result = $builder->forSigner(self::TEST_SIGNER_ID);
        $this->assertInstanceOf(AccountsRequestBuilder::class, $result);
    }

    /**
     * Test forLiquidityPool filter with L-prefixed ID (StrKey encoded)
     */
    public function testForLiquidityPoolWithLAddress(): void
    {
        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountsPageJson())
        ]);
        $builder = new AccountsRequestBuilder($client);

        // L-prefixed liquidity pool ID (StrKey encoded)
        $lPoolId = 'LA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJUPJN';

        $builder->forLiquidityPool($lPoolId);
        $url = $builder->buildUrl();

        // The L-address should be decoded to hex format
        $this->assertStringContainsString('liquidity_pool=', $url);
        // The decoded hex should not contain the L prefix
        $this->assertStringNotContainsString('LA7QYNF7', $url);
        // The URL should contain a 64-character hex string (32 bytes)
        $this->assertMatchesRegularExpression('/liquidity_pool=[0-9a-f]{64}/', $url);
    }

    /**
     * Test that forAsset and forSigner mutual exclusivity works in reverse order
     */
    public function testForAssetBeforeSignerMutuallyExclusive(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot set both asset and signer');

        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $builder->forSigner(self::TEST_SIGNER_ID);
        $asset = new AssetTypeCreditAlphanum4('USD', self::TEST_SIGNER_ID);
        $builder->forAsset($asset);
    }

    /**
     * Test that forLiquidityPool before forAsset is mutually exclusive
     */
    public function testForLiquidityPoolBeforeAssetMutuallyExclusive(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot set both liquidity_pool and asset');

        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $builder->forLiquidityPool(self::TEST_LIQUIDITY_POOL_ID);
        $asset = new AssetTypeCreditAlphanum4('USD', self::TEST_SIGNER_ID);
        $builder->forAsset($asset);
    }

    /**
     * Test that forSponsor before forLiquidityPool is mutually exclusive
     */
    public function testForSponsorBeforeLiquidityPoolMutuallyExclusive(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('cannot set both sponsor and liquidity_pool');

        $client = $this->createMockedClient([]);
        $builder = new AccountsRequestBuilder($client);

        $builder->forSponsor(self::TEST_SPONSOR_ID);
        $builder->forLiquidityPool(self::TEST_LIQUIDITY_POOL_ID);
    }

    /**
     * Test that setSegments throws exception when called twice
     */
    public function testSetSegmentsThrowsExceptionWhenCalledTwice(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('URL segments have been already added.');

        $client = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountJson()),
            new Response(200, [], $this->getSampleAccountJson())
        ]);
        $builder = new AccountsRequestBuilder($client);

        // First call sets segments via account()
        $builder->account(self::TEST_ACCOUNT_ID);

        // Second call should throw - using accountData() which also calls setSegments
        $builder->accountData(self::TEST_ACCOUNT_ID, 'config');
    }
}
