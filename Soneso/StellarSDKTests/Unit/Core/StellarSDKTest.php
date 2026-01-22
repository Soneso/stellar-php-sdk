<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\Memo;
use Soneso\StellarSDK\Requests\AccountsRequestBuilder;
use Soneso\StellarSDK\Requests\AssetsRequestBuilder;
use Soneso\StellarSDK\Requests\EffectsRequestBuilder;
use Soneso\StellarSDK\Requests\LedgersRequestBuilder;
use Soneso\StellarSDK\Requests\OffersRequestBuilder;
use Soneso\StellarSDK\Requests\OperationsRequestBuilder;
use Soneso\StellarSDK\Requests\OrderBookRequestBuilder;
use Soneso\StellarSDK\Requests\PaymentsRequestBuilder;
use Soneso\StellarSDK\Requests\TradesRequestBuilder;
use Soneso\StellarSDK\Requests\TransactionsRequestBuilder;
use Soneso\StellarSDK\Requests\ClaimableBalancesRequestBuilder;
use Soneso\StellarSDK\Requests\LiquidityPoolsRequestBuilder;
use Soneso\StellarSDK\Requests\FeeStatsRequestBuilder;
use Soneso\StellarSDK\Requests\FindPathsRequestBuilder;
use Soneso\StellarSDK\Requests\StrictSendPathsRequestBuilder;
use Soneso\StellarSDK\Requests\StrictReceivePathsRequestBuilder;
use Soneso\StellarSDK\Requests\TradeAggregationsRequestBuilder;
use Soneso\StellarSDK\Responses\Root\RootResponse;
use Soneso\StellarSDK\Responses\Health\HealthResponse;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\Responses\FeeStats\FeeStatsResponse;
use Soneso\StellarSDK\Responses\Ledger\LedgerResponse;
use Soneso\StellarSDK\Responses\Transaction\TransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Soneso\StellarSDK\Responses\Transaction\SubmitAsyncTransactionResponse;
use Soneso\StellarSDK\Responses\ClaimableBalances\ClaimableBalanceResponse;
use Soneso\StellarSDK\Responses\Offers\OfferResponse;
use Soneso\StellarSDK\Responses\LiquidityPools\LiquidityPoolResponse;
use Soneso\StellarSDK\Responses\Operations\OperationResponse;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

/**
 * Unit tests for StellarSDK
 *
 * Tests the main SDK class including singleton instances, request builder factories,
 * direct request methods, transaction submission, and error handling.
 */
class StellarSDKTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
    private const TEST_TRANSACTION_HASH = 'a12b3c4d5e6f7890abcdef1234567890abcdef1234567890abcdef1234567890';
    private const TEST_LEDGER_SEQ = '123456';
    private const TEST_CLAIMABLE_BALANCE_ID = '00000000929b20b72e5890ab51c24f1cc46fa01c4f318d8d33367d24dd614cfdf5491072';
    private const TEST_OFFER_ID = '12345';
    private const TEST_POOL_ID = 'dd7b1ab831c273310ddbec6f97870aa83c2fbd78ce22aded37ecbf4f3380fac7';
    private const TEST_OPERATION_ID = '123456789012345';

    /**
     * Helper method to create a mocked SDK with predefined responses
     */
    private function createMockedSdk(array $responses): StellarSDK
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $sdk->setHttpClient($client);
        return $sdk;
    }

    /**
     * Helper method to get sample account JSON response
     */
    private function getSampleAccountJson(): string
    {
        return json_encode([
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/accounts/' . self::TEST_ACCOUNT_ID]
            ],
            'id' => self::TEST_ACCOUNT_ID,
            'account_id' => self::TEST_ACCOUNT_ID,
            'sequence' => '123456789012',
            'subentry_count' => 3,
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
                    'asset_type' => 'native'
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
     * Helper method to get sample root JSON response
     */
    private function getSampleRootJson(): string
    {
        return json_encode([
            'horizon_version' => '2.30.0',
            'core_version' => 'v21.0.0',
            'history_latest_ledger' => 123456,
            'history_elder_ledger' => 1,
            'core_latest_ledger' => 123456,
            'network_passphrase' => 'Test SDF Network ; September 2015',
            'current_protocol_version' => 21,
            'core_supported_protocol_version' => 21,
            'ingest_latest_ledger' => 123456,
            'history_latest_ledger_closed_at' => '2024-01-20T12:00:00Z',
            'protocol_version' => 21
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
     * Helper method to get sample fee stats JSON response
     */
    private function getSampleFeeStatsJson(): string
    {
        return json_encode([
            'last_ledger' => '123456',
            'last_ledger_base_fee' => '100',
            'ledger_capacity_usage' => '0.5',
            'fee_charged' => [
                'min' => '100',
                'mode' => '100',
                'p10' => '100',
                'p20' => '100',
                'p30' => '100',
                'p40' => '100',
                'p50' => '100',
                'p60' => '100',
                'p70' => '200',
                'p80' => '300',
                'p90' => '500',
                'p95' => '1000',
                'p99' => '5000',
                'max' => '10000'
            ],
            'max_fee' => [
                'min' => '100',
                'mode' => '100',
                'p10' => '100',
                'p20' => '100',
                'p30' => '100',
                'p40' => '100',
                'p50' => '100',
                'p60' => '100',
                'p70' => '200',
                'p80' => '300',
                'p90' => '500',
                'p95' => '1000',
                'p99' => '5000',
                'max' => '10000'
            ]
        ]);
    }

    /**
     * Helper method to get sample ledger JSON response
     */
    private function getSampleLedgerJson(): string
    {
        return json_encode([
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/ledgers/' . self::TEST_LEDGER_SEQ]
            ],
            'id' => 'ledger_id',
            'paging_token' => '123456',
            'hash' => 'ledger_hash',
            'sequence' => (int)self::TEST_LEDGER_SEQ,
            'successful_transaction_count' => 10,
            'failed_transaction_count' => 1,
            'operation_count' => 20,
            'tx_set_operation_count' => 20,
            'closed_at' => '2024-01-20T12:00:00Z',
            'total_coins' => '100000000000.0000000',
            'fee_pool' => '1000.0000000',
            'base_fee_in_stroops' => 100,
            'base_reserve_in_stroops' => 5000000,
            'max_tx_set_size' => 1000,
            'protocol_version' => 21,
            'header_xdr' => 'AAAAEw=='
        ]);
    }

    /**
     * Helper method to get sample transaction JSON response
     */
    private function getSampleTransactionJson(): string
    {
        return json_encode([
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/transactions/' . self::TEST_TRANSACTION_HASH]
            ],
            'id' => self::TEST_TRANSACTION_HASH,
            'paging_token' => '123456789012345',
            'successful' => true,
            'hash' => self::TEST_TRANSACTION_HASH,
            'ledger' => 123456,
            'created_at' => '2024-01-20T12:00:00Z',
            'source_account' => self::TEST_ACCOUNT_ID,
            'source_account_sequence' => '123456789012',
            'fee_account' => self::TEST_ACCOUNT_ID,
            'fee_charged' => '100',
            'max_fee' => '1000',
            'operation_count' => 1,
            'envelope_xdr' => 'AAAAAgAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAGQDHvLTAAABIwAAAAEAAAAAAAAAAAAAAABmh4pDAAAAAQAAABgwLDA3NSUgRGFpbHkgZm9yIEhvbGRlcnMAAAABAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAABaG1GYAAAAAAAAAAKpYmInAAAAQLmth39Fjo8TC05wn5ZOAw4lou2rkxAaK6k16lHYXlEcsYHZ/d+ga5bCgO9KV/sbKaZAUCC9KvFIplXkXffBxQ0WUsBeAAAAQC2w45T3S24shkJ7uyRl/P5xD86Xfi7qTYxmb8uh8PEcwlb5oqbnJcTlUV2uJs2+gzMlijNtAbrCm6wO+1YsJQ4=',
            'result_xdr' => 'AAAAAAAAAGQAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAA=',
            'result_meta_xdr' => 'AAAAAwAAAAAAAAACAAAAAwMgANMAAAAAAAAAABpkeES/191B7z9YrUh5J2wySk7XDoZU7ulEgyoWUsBeAAAAAAHJUdQDHvLTAAABIgAAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAAAAAAAAAAAAAAAAAAMAAAAAAyAAyAAAAABmh4mjAAAAAAAAAAEDIADTAAAAAAAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAAAByVHUAx7y0wAAASMAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgANMAAAAAZoeJ4wAAAAAAAAABAAAABAAAAAMDIADTAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAKTCBRhynx//////////wAAAAEAAAABAAAA1xsdUZsAAAAAAAAAAAAAAAAAAAAAAAAAAQMgANMAAAABAAAAAEOS3eq8CQUgHPBA1RMi5Vg7qVqRYg/+phWaqHCpYmInAAAAAUhVTgAAAAAAYqvLVFIr7zrouOae2L2ZCcrBlj9GH/FhAvIptvt76+QAApMHukZ45H//////////AAAAAQAAAAEAAADXGx1RmwAAAAAAAAAAAAAAAAAAAAAAAAADAx/LTQAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAdVOSfYYf/////////8AAAABAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEDIADTAAAAAQAAAAAsqHb+7EzH/s5AbXo3k6x2lY+XRkZJ4MhWlZCqw5XbAgAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAAB1ahlR7B//////////wAAAAEAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            'fee_meta_xdr' => 'AAAAAgAAAAMDIADIAAAAAAAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAAAByVI4Ax7y0wAAASIAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgAMgAAAAAZoeJowAAAAAAAAABAyAA0wAAAAAAAAAAGmR4RL/X3UHvP1itSHknbDJKTtcOhlTu6USDKhZSwF4AAAAAAclR1AMe8tMAAAEiAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAACAAAAAAAAAAAAAAAAAAAAAwAAAAADIADIAAAAAGaHiaMAAAAA',
            'memo_type' => 'text',
            'memo' => '00075% Daily for Holders',
            'signatures' => [
                'qa2Hf0WOjxMLTnCflk4DDiWi7auTEBorqTXqUdheURyxgdn936BrlsKA70pX+xsppkBQIL0q8UimVeRd98HFDRZSwF4AAABALbDjlPdLbiyGQnu7JGX8/nEPzpd+LupNjGZvy6Hw8RzCVvmipuclxOVRXa4mzb6DMyWKM20BusKbrA77ViwlDg=='
            ]
        ]);
    }

    /**
     * Helper method to get sample submit transaction JSON response
     */
    private function getSampleSubmitTransactionJson(): string
    {
        return json_encode([
            'hash' => self::TEST_TRANSACTION_HASH,
            'ledger' => 123456,
            'envelope_xdr' => 'AAAAAgAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAGQDHvLTAAABIwAAAAEAAAAAAAAAAAAAAABmh4pDAAAAAQAAABgwLDA3NSUgRGFpbHkgZm9yIEhvbGRlcnMAAAABAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAABaG1GYAAAAAAAAAAKpYmInAAAAQLmth39Fjo8TC05wn5ZOAw4lou2rkxAaK6k16lHYXlEcsYHZ/d+ga5bCgO9KV/sbKaZAUCC9KvFIplXkXffBxQ0WUsBeAAAAQC2w45T3S24shkJ7uyRl/P5xD86Xfi7qTYxmb8uh8PEcwlb5oqbnJcTlUV2uJs2+gzMlijNtAbrCm6wO+1YsJQ4=',
            'result_xdr' => 'AAAAAAAAAGQAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAA=',
            'result_meta_xdr' => 'AAAAAwAAAAAAAAACAAAAAwMgANMAAAAAAAAAABpkeES/191B7z9YrUh5J2wySk7XDoZU7ulEgyoWUsBeAAAAAAHJUdQDHvLTAAABIgAAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAAAAAAAAAAAAAAAAAAMAAAAAAyAAyAAAAABmh4mjAAAAAAAAAAEDIADTAAAAAAAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAAAByVHUAx7y0wAAASMAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgANMAAAAAZoeJ4wAAAAAAAAABAAAABAAAAAMDIADTAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAKTCBRhynx//////////wAAAAEAAAABAAAA1xsdUZsAAAAAAAAAAAAAAAAAAAAAAAAAAQMgANMAAAABAAAAAEOS3eq8CQUgHPBA1RMi5Vg7qVqRYg/+phWaqHCpYmInAAAAAUhVTgAAAAAAYqvLVFIr7zrouOae2L2ZCcrBlj9GH/FhAvIptvt76+QAApMHukZ45H//////////AAAAAQAAAAEAAADXGx1RmwAAAAAAAAAAAAAAAAAAAAAAAAADAx/LTQAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAdVOSfYYf/////////8AAAABAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEDIADTAAAAAQAAAAAsqHb+7EzH/s5AbXo3k6x2lY+XRkZJ4MhWlZCqw5XbAgAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAAB1ahlR7B//////////wAAAAEAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA='
        ]);
    }

    /**
     * Helper method to get sample submit async transaction JSON response
     */
    private function getSampleSubmitAsyncTransactionJson(): string
    {
        return json_encode([
            'tx_status' => 'PENDING',
            'hash' => self::TEST_TRANSACTION_HASH,
            'error_result_xdr' => null
        ]);
    }

    /**
     * Helper method to get sample claimable balance JSON response
     */
    private function getSampleClaimableBalanceJson(): string
    {
        return json_encode([
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/claimable_balances/' . self::TEST_CLAIMABLE_BALANCE_ID]
            ],
            'id' => self::TEST_CLAIMABLE_BALANCE_ID,
            'asset' => 'native',
            'amount' => '100.0000000',
            'sponsor' => self::TEST_ACCOUNT_ID,
            'last_modified_ledger' => 123456,
            'last_modified_time' => '2024-01-20T12:00:00Z',
            'claimants' => [
                [
                    'destination' => self::TEST_ACCOUNT_ID,
                    'predicate' => [
                        'unconditional' => true
                    ]
                ]
            ],
            'flags' => [
                'clawback_enabled' => false
            ],
            'paging_token' => self::TEST_CLAIMABLE_BALANCE_ID
        ]);
    }

    /**
     * Helper method to get sample offer JSON response
     */
    private function getSampleOfferJson(): string
    {
        return json_encode([
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/offers/' . self::TEST_OFFER_ID]
            ],
            'id' => self::TEST_OFFER_ID,
            'paging_token' => self::TEST_OFFER_ID,
            'seller' => self::TEST_ACCOUNT_ID,
            'selling' => [
                'asset_type' => 'native'
            ],
            'buying' => [
                'asset_type' => 'credit_alphanum4',
                'asset_code' => 'USD',
                'asset_issuer' => self::TEST_ACCOUNT_ID
            ],
            'amount' => '100.0000000',
            'price_r' => [
                'n' => 1,
                'd' => 1
            ],
            'price' => '1.0000000',
            'last_modified_ledger' => 123456,
            'last_modified_time' => '2024-01-20T12:00:00Z',
            'sponsor' => self::TEST_ACCOUNT_ID
        ]);
    }

    /**
     * Helper method to get sample liquidity pool JSON response
     */
    private function getSampleLiquidityPoolJson(): string
    {
        return json_encode([
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/liquidity_pools/' . self::TEST_POOL_ID]
            ],
            'id' => self::TEST_POOL_ID,
            'paging_token' => self::TEST_POOL_ID,
            'fee_bp' => 30,
            'type' => 'constant_product',
            'total_trustlines' => '100',
            'total_shares' => '1000.0000000',
            'reserves' => [
                [
                    'asset' => 'native',
                    'amount' => '500.0000000'
                ],
                [
                    'asset' => 'USD:' . self::TEST_ACCOUNT_ID,
                    'amount' => '500.0000000'
                ]
            ],
            'last_modified_ledger' => 123456,
            'last_modified_time' => '2024-01-20T12:00:00Z'
        ]);
    }

    /**
     * Helper method to get sample operation JSON response
     */
    private function getSampleOperationJson(): string
    {
        return json_encode([
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/operations/' . self::TEST_OPERATION_ID]
            ],
            'id' => self::TEST_OPERATION_ID,
            'paging_token' => self::TEST_OPERATION_ID,
            'transaction_successful' => true,
            'source_account' => self::TEST_ACCOUNT_ID,
            'type' => 'create_account',
            'type_i' => 0,
            'created_at' => '2024-01-20T12:00:00Z',
            'transaction_hash' => self::TEST_TRANSACTION_HASH,
            'starting_balance' => '100.0000000',
            'funder' => self::TEST_ACCOUNT_ID,
            'account' => 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5'
        ]);
    }

    /**
     * Test singleton instance for public network
     */
    public function testGetPublicNetInstance(): void
    {
        $instance1 = StellarSDK::getPublicNetInstance();
        $instance2 = StellarSDK::getPublicNetInstance();

        $this->assertInstanceOf(StellarSDK::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test singleton instance for test network
     */
    public function testGetTestNetInstance(): void
    {
        $instance1 = StellarSDK::getTestNetInstance();
        $instance2 = StellarSDK::getTestNetInstance();

        $this->assertInstanceOf(StellarSDK::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test singleton instance for future network
     */
    public function testGetFutureNetInstance(): void
    {
        $instance1 = StellarSDK::getFutureNetInstance();
        $instance2 = StellarSDK::getFutureNetInstance();

        $this->assertInstanceOf(StellarSDK::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test custom SDK instance with custom URI
     */
    public function testCustomInstance(): void
    {
        $sdk = new StellarSDK('https://custom-horizon.example.com');

        $this->assertInstanceOf(StellarSDK::class, $sdk);
        $this->assertInstanceOf(Client::class, $sdk->getHttpClient());
    }

    /**
     * Test setHttpClient and getHttpClient
     */
    public function testSetAndGetHttpClient(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $customClient = new Client(['timeout' => 30]);

        $sdk->setHttpClient($customClient);
        $retrievedClient = $sdk->getHttpClient();

        $this->assertSame($customClient, $retrievedClient);
    }

    /**
     * Test accounts request builder factory
     */
    public function testAccountsRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->accounts();

        $this->assertInstanceOf(AccountsRequestBuilder::class, $builder);
    }

    /**
     * Test assets request builder factory
     */
    public function testAssetsRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->assets();

        $this->assertInstanceOf(AssetsRequestBuilder::class, $builder);
    }

    /**
     * Test effects request builder factory
     */
    public function testEffectsRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->effects();

        $this->assertInstanceOf(EffectsRequestBuilder::class, $builder);
    }

    /**
     * Test ledgers request builder factory
     */
    public function testLedgersRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->ledgers();

        $this->assertInstanceOf(LedgersRequestBuilder::class, $builder);
    }

    /**
     * Test offers request builder factory
     */
    public function testOffersRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->offers();

        $this->assertInstanceOf(OffersRequestBuilder::class, $builder);
    }

    /**
     * Test operations request builder factory
     */
    public function testOperationsRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->operations();

        $this->assertInstanceOf(OperationsRequestBuilder::class, $builder);
    }

    /**
     * Test order book request builder factory
     */
    public function testOrderBookRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->orderBook();

        $this->assertInstanceOf(OrderBookRequestBuilder::class, $builder);
    }

    /**
     * Test payments request builder factory
     */
    public function testPaymentsRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->payments();

        $this->assertInstanceOf(PaymentsRequestBuilder::class, $builder);
    }

    /**
     * Test trades request builder factory
     */
    public function testTradesRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->trades();

        $this->assertInstanceOf(TradesRequestBuilder::class, $builder);
    }

    /**
     * Test transactions request builder factory
     */
    public function testTransactionsRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->transactions();

        $this->assertInstanceOf(TransactionsRequestBuilder::class, $builder);
    }

    /**
     * Test claimable balances request builder factory
     */
    public function testClaimableBalancesRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->claimableBalances();

        $this->assertInstanceOf(ClaimableBalancesRequestBuilder::class, $builder);
    }

    /**
     * Test liquidity pools request builder factory
     */
    public function testLiquidityPoolsRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->liquidityPools();

        $this->assertInstanceOf(LiquidityPoolsRequestBuilder::class, $builder);
    }

    /**
     * Test fee stats request builder factory
     */
    public function testFeeStatsRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->feeStats();

        $this->assertInstanceOf(FeeStatsRequestBuilder::class, $builder);
    }

    /**
     * Test find paths request builder factory
     */
    public function testFindPathsRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->findPaths();

        $this->assertInstanceOf(FindPathsRequestBuilder::class, $builder);
    }

    /**
     * Test find strict send paths request builder factory
     */
    public function testFindStrictSendPathsRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->findStrictSendPaths();

        $this->assertInstanceOf(StrictSendPathsRequestBuilder::class, $builder);
    }

    /**
     * Test find strict receive paths request builder factory
     */
    public function testFindStrictReceivePathsRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->findStrictReceivePaths();

        $this->assertInstanceOf(StrictReceivePathsRequestBuilder::class, $builder);
    }

    /**
     * Test trade aggregations request builder factory
     */
    public function testTradeAggregationsRequestBuilder(): void
    {
        $sdk = new StellarSDK('https://horizon-testnet.stellar.org');
        $builder = $sdk->tradeAggregations();

        $this->assertInstanceOf(TradeAggregationsRequestBuilder::class, $builder);
    }

    /**
     * Test root endpoint
     */
    public function testRoot(): void
    {
        $sdk = $this->createMockedSdk([
            new Response(200, [], $this->getSampleRootJson())
        ]);

        $response = $sdk->root();

        $this->assertInstanceOf(RootResponse::class, $response);
        $this->assertEquals('2.30.0', $response->getHorizonVersion());
        $this->assertEquals('v21.0.0', $response->getCoreVersion());
        $this->assertEquals('Test SDF Network ; September 2015', $response->getNetworkPassphrase());
    }

    /**
     * Test health endpoint
     */
    public function testHealth(): void
    {
        $sdk = $this->createMockedSdk([
            new Response(200, [], $this->getSampleHealthJson())
        ]);

        $response = $sdk->health();

        $this->assertInstanceOf(HealthResponse::class, $response);
        $this->assertTrue($response->getDatabaseConnected());
        $this->assertTrue($response->getCoreUp());
        $this->assertTrue($response->getCoreSynced());
    }

    /**
     * Test requestAccount method
     */
    public function testRequestAccount(): void
    {
        $sdk = $this->createMockedSdk([
            new Response(200, [], $this->getSampleAccountJson())
        ]);

        $response = $sdk->requestAccount(self::TEST_ACCOUNT_ID);

        $this->assertInstanceOf(AccountResponse::class, $response);
        $this->assertEquals(self::TEST_ACCOUNT_ID, $response->getAccountId());
        $this->assertEquals('123456789012', $response->getSequenceNumber());
    }

    /**
     * Test accountExists returns true for existing account
     */
    public function testAccountExistsTrue(): void
    {
        $sdk = $this->createMockedSdk([
            new Response(200, [], $this->getSampleAccountJson())
        ]);

        $exists = $sdk->accountExists(self::TEST_ACCOUNT_ID);

        $this->assertTrue($exists);
    }

    /**
     * Test accountExists returns false for non-existing account
     */
    public function testAccountExistsFalse(): void
    {
        $sdk = $this->createMockedSdk([
            new Response(404, [], json_encode([
                'type' => 'https://stellar.org/horizon-errors/not_found',
                'title' => 'Resource Missing',
                'status' => 404,
                'detail' => 'The resource at the url requested was not found.'
            ]))
        ]);

        $exists = $sdk->accountExists(self::TEST_ACCOUNT_ID);

        $this->assertFalse($exists);
    }

    /**
     * Test accountExists throws exception on other errors
     */
    public function testAccountExistsThrowsOnOtherErrors(): void
    {
        $this->expectException(HorizonRequestException::class);

        $sdk = $this->createMockedSdk([
            new Response(500, [], json_encode([
                'type' => 'https://stellar.org/horizon-errors/server_error',
                'title' => 'Internal Server Error',
                'status' => 500,
                'detail' => 'Internal server error occurred'
            ]))
        ]);

        $sdk->accountExists(self::TEST_ACCOUNT_ID);
    }

    /**
     * Test requestLedger method
     */
    public function testRequestLedger(): void
    {
        $sdk = $this->createMockedSdk([
            new Response(200, [], $this->getSampleLedgerJson())
        ]);

        $response = $sdk->requestLedger(self::TEST_LEDGER_SEQ);

        $this->assertInstanceOf(LedgerResponse::class, $response);
        $sequence = $response->getSequence();
        if (is_object($sequence)) {
            $this->assertEquals(self::TEST_LEDGER_SEQ, (string)$sequence);
        } else {
            $this->assertEquals((int)self::TEST_LEDGER_SEQ, $sequence);
        }
    }

    /**
     * Test requestTransaction method
     */
    public function testRequestTransaction(): void
    {
        $sdk = $this->createMockedSdk([
            new Response(200, [], $this->getSampleTransactionJson())
        ]);

        $response = $sdk->requestTransaction(self::TEST_TRANSACTION_HASH);

        $this->assertInstanceOf(TransactionResponse::class, $response);
        $this->assertEquals(self::TEST_TRANSACTION_HASH, $response->getHash());
    }

    /**
     * Test requestFeeStats method
     */
    public function testRequestFeeStats(): void
    {
        $sdk = $this->createMockedSdk([
            new Response(200, [], $this->getSampleFeeStatsJson())
        ]);

        $response = $sdk->requestFeeStats();

        $this->assertInstanceOf(FeeStatsResponse::class, $response);
        $this->assertEquals('123456', $response->getLastLedger());
        $this->assertEquals('100', $response->getLastLedgerBaseFee());
    }

    /**
     * Test requestClaimableBalance method
     */
    public function testRequestClaimableBalance(): void
    {
        $sdk = $this->createMockedSdk([
            new Response(200, [], $this->getSampleClaimableBalanceJson())
        ]);

        $response = $sdk->requestClaimableBalance(self::TEST_CLAIMABLE_BALANCE_ID);

        $this->assertInstanceOf(ClaimableBalanceResponse::class, $response);
        $this->assertEquals(self::TEST_CLAIMABLE_BALANCE_ID, $response->getBalanceId());
    }

    /**
     * Test requestOffer method
     */
    public function testRequestOffer(): void
    {
        $sdk = $this->createMockedSdk([
            new Response(200, [], $this->getSampleOfferJson())
        ]);

        $response = $sdk->requestOffer(self::TEST_OFFER_ID);

        $this->assertInstanceOf(OfferResponse::class, $response);
        $this->assertEquals(self::TEST_OFFER_ID, $response->getOfferId());
    }

    /**
     * Test requestLiquidityPool method
     */
    public function testRequestLiquidityPool(): void
    {
        $sdk = $this->createMockedSdk([
            new Response(200, [], $this->getSampleLiquidityPoolJson())
        ]);

        $response = $sdk->requestLiquidityPool(self::TEST_POOL_ID);

        $this->assertInstanceOf(LiquidityPoolResponse::class, $response);
        $this->assertEquals(self::TEST_POOL_ID, $response->getPoolId());
    }

    /**
     * Test requestOperation method
     */
    public function testRequestOperation(): void
    {
        $sdk = $this->createMockedSdk([
            new Response(200, [], $this->getSampleOperationJson())
        ]);

        $response = $sdk->requestOperation(self::TEST_OPERATION_ID);

        $this->assertInstanceOf(OperationResponse::class, $response);
        $this->assertEquals(self::TEST_OPERATION_ID, $response->getOperationId());
    }

    /**
     * Test submitTransaction method with XDR
     *
     * Note: We test with envelope XDR directly to avoid complex transaction building
     */
    public function testSubmitTransactionWithXdr(): void
    {
        $this->markTestSkipped('Transaction submission tested via submitTransactionEnvelopeXdrBase64');
    }

    /**
     * Test submitTransactionEnvelopeXdrBase64 method
     */
    public function testSubmitTransactionEnvelopeXdrBase64(): void
    {
        $sdk = $this->createMockedSdk([
            new Response(200, [], $this->getSampleSubmitTransactionJson())
        ]);

        $validXdr = 'AAAAAgAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAGQDHvLTAAABIwAAAAEAAAAAAAAAAAAAAABmh4pDAAAAAQAAABgwLDA3NSUgRGFpbHkgZm9yIEhvbGRlcnMAAAABAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAABaG1GYAAAAAAAAAAKpYmInAAAAQLmth39Fjo8TC05wn5ZOAw4lou2rkxAaK6k16lHYXlEcsYHZ/d+ga5bCgO9KV/sbKaZAUCC9KvFIplXkXffBxQ0WUsBeAAAAQC2w45T3S24shkJ7uyRl/P5xD86Xfi7qTYxmb8uh8PEcwlb5oqbnJcTlUV2uJs2+gzMlijNtAbrCm6wO+1YsJQ4=';

        $response = $sdk->submitTransactionEnvelopeXdrBase64($validXdr);

        $this->assertInstanceOf(SubmitTransactionResponse::class, $response);
        $this->assertEquals(self::TEST_TRANSACTION_HASH, $response->getHash());
    }

    /**
     * Test submitAsyncTransaction method with XDR
     *
     * Note: We test with envelope XDR directly to avoid complex transaction building
     */
    public function testSubmitAsyncTransactionWithXdr(): void
    {
        $this->markTestSkipped('Async transaction submission tested via submitAsyncTransactionEnvelopeXdrBase64');
    }

    /**
     * Test submitAsyncTransactionEnvelopeXdrBase64 method
     */
    public function testSubmitAsyncTransactionEnvelopeXdrBase64(): void
    {
        $sdk = $this->createMockedSdk([
            new Response(200, [], $this->getSampleSubmitAsyncTransactionJson())
        ]);

        $response = $sdk->submitAsyncTransactionEnvelopeXdrBase64('AAAAEw==');

        $this->assertInstanceOf(SubmitAsyncTransactionResponse::class, $response);
        $this->assertEquals(self::TEST_TRANSACTION_HASH, $response->hash);
        $this->assertEquals('PENDING', $response->txStatus);
    }

    /**
     * Test checkMemoRequired functionality
     *
     * Note: checkMemoRequired requires complex transaction building and is better tested in integration tests
     */
    public function testCheckMemoRequiredSkipped(): void
    {
        $this->markTestSkipped('checkMemoRequired tested in integration tests due to complex transaction dependencies');
    }

    /**
     * Test error handling for HTTP exceptions
     */
    public function testErrorHandlingForHttpExceptions(): void
    {
        $this->expectException(HorizonRequestException::class);

        $sdk = $this->createMockedSdk([
            new Response(400, [], json_encode([
                'type' => 'https://stellar.org/horizon-errors/bad_request',
                'title' => 'Bad Request',
                'status' => 400,
                'detail' => 'Invalid request'
            ]))
        ]);

        $sdk->requestAccount(self::TEST_ACCOUNT_ID);
    }

    /**
     * Test version constant
     */
    public function testVersionConstant(): void
    {
        $this->assertIsString(StellarSDK::VERSION_NR);
        $this->assertNotEmpty(StellarSDK::VERSION_NR);
    }

    /**
     * Test network URL constants
     */
    public function testNetworkUrlConstants(): void
    {
        $this->assertEquals('https://horizon.stellar.org', StellarSDK::$PUBLIC_NET_HORIZON_URL);
        $this->assertEquals('https://horizon-testnet.stellar.org', StellarSDK::$TEST_NET_HORIZON_URL);
        $this->assertEquals('https://horizon-futurenet.stellar.org', StellarSDK::$FUTURE_NET_HORIZON_URL);
    }
}
