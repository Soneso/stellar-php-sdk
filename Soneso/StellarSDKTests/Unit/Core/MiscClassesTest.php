<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\Requests\AccountsRequestBuilder;
use Soneso\StellarSDK\Responses\Account\AccountsPageResponse;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Soneso\StellarSDK\Signer;
use Soneso\StellarSDK\SignedPayloadSigner;
use Soneso\StellarSDK\TimeBounds;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Xdr\XdrSignerKeyType;
use Soneso\StellarSDK\Xdr\XdrTransactionResultCode;

/**
 * Unit tests for miscellaneous classes with low coverage.
 * Tests RequestBuilder, PageResponse, Signer, and SubmitTransactionResponse.
 */
class MiscClassesTest extends TestCase
{
    private const TEST_ACCOUNT_ID = 'GAAZI4TCR3TY5OJHCTJC2A4QSY6CJWJH5IAJTGKIN2ER7LBNVKOCCWN7';
    private const TEST_SIGNER_ID = 'GBVOL67TMUQBGL4TZYNMY3ZQ5WGQYFPFD5VJRWXR72VA33VFNL225PL5';
    private const TEST_CURSOR = '123456789';

    // RequestBuilder Tests

    public function testRequestBuilderCursor(): void
    {
        $httpClient = $this->createMockedClient([new Response(200, [], $this->getSampleAccountsPageJson())]);
        $requestBuilder = new AccountsRequestBuilder($httpClient, 'https://horizon-testnet.stellar.org');

        $requestBuilder->cursor(self::TEST_CURSOR);
        $url = $requestBuilder->buildUrl();

        $this->assertStringContainsString('cursor=' . self::TEST_CURSOR, $url);
    }

    public function testRequestBuilderLimit(): void
    {
        $httpClient = $this->createMockedClient([new Response(200, [], $this->getSampleAccountsPageJson())]);
        $requestBuilder = new AccountsRequestBuilder($httpClient, 'https://horizon-testnet.stellar.org');

        $requestBuilder->limit(50);
        $url = $requestBuilder->buildUrl();

        $this->assertStringContainsString('limit=50', $url);
    }

    public function testRequestBuilderOrderAsc(): void
    {
        $httpClient = $this->createMockedClient([new Response(200, [], $this->getSampleAccountsPageJson())]);
        $requestBuilder = new AccountsRequestBuilder($httpClient, 'https://horizon-testnet.stellar.org');

        $requestBuilder->order('asc');
        $url = $requestBuilder->buildUrl();

        $this->assertStringContainsString('order=asc', $url);
    }

    public function testRequestBuilderOrderDesc(): void
    {
        $httpClient = $this->createMockedClient([new Response(200, [], $this->getSampleAccountsPageJson())]);
        $requestBuilder = new AccountsRequestBuilder($httpClient, 'https://horizon-testnet.stellar.org');

        $requestBuilder->order('desc');
        $url = $requestBuilder->buildUrl();

        $this->assertStringContainsString('order=desc', $url);
    }

    public function testRequestBuilderChaining(): void
    {
        $httpClient = $this->createMockedClient([new Response(200, [], $this->getSampleAccountsPageJson())]);
        $requestBuilder = new AccountsRequestBuilder($httpClient, 'https://horizon-testnet.stellar.org');

        $requestBuilder->cursor(self::TEST_CURSOR)->limit(25)->order('desc');
        $url = $requestBuilder->buildUrl();

        $this->assertStringContainsString('cursor=' . self::TEST_CURSOR, $url);
        $this->assertStringContainsString('limit=25', $url);
        $this->assertStringContainsString('order=desc', $url);
    }

    // PageResponse Tests

    public function testPageResponseHasNextPage(): void
    {
        $httpClient = $this->createMockedClient([new Response(200, [], $this->getSampleAccountsPageJson())]);
        $requestBuilder = new AccountsRequestBuilder($httpClient, 'https://horizon-testnet.stellar.org');

        $response = $requestBuilder->execute();

        $this->assertTrue($response->hasNextPage());
    }

    public function testPageResponseHasPrevPage(): void
    {
        $httpClient = $this->createMockedClient([new Response(200, [], $this->getSampleAccountsPageJson())]);
        $requestBuilder = new AccountsRequestBuilder($httpClient, 'https://horizon-testnet.stellar.org');

        $response = $requestBuilder->execute();

        $this->assertTrue($response->hasPrevPage());
    }

    public function testPageResponseGetNextPage(): void
    {
        $httpClient = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountsPageJson()),
            new Response(200, [], $this->getSampleAccountsPageJson())
        ]);
        $requestBuilder = new AccountsRequestBuilder($httpClient, 'https://horizon-testnet.stellar.org');

        $response = $requestBuilder->execute();
        $nextPage = $response->getNextPage();

        $this->assertInstanceOf(AccountsPageResponse::class, $nextPage);
    }

    public function testPageResponseGetPreviousPage(): void
    {
        $httpClient = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountsPageJson()),
            new Response(200, [], $this->getSampleAccountsPageJson())
        ]);
        $requestBuilder = new AccountsRequestBuilder($httpClient, 'https://horizon-testnet.stellar.org');

        $response = $requestBuilder->execute();
        $prevPage = $response->getPreviousPage();

        $this->assertInstanceOf(AccountsPageResponse::class, $prevPage);
    }

    public function testPageResponseNavigationLinks(): void
    {
        $httpClient = $this->createMockedClient([
            new Response(200, [], $this->getSampleAccountsPageJson())
        ]);
        $requestBuilder = new AccountsRequestBuilder($httpClient, 'https://horizon-testnet.stellar.org');

        $response = $requestBuilder->execute();

        $links = $response->getLinks();
        $this->assertNotNull($links->getSelf());
        $this->assertNotNull($links->getNext());
        $this->assertNotNull($links->getPrev());
    }

    // Signer Tests

    public function testSignerEd25519PublicKey(): void
    {
        $keyPair = KeyPair::random();
        $signerKey = Signer::ed25519PublicKey($keyPair);

        $this->assertEquals(XdrSignerKeyType::ED25519, $signerKey->getType()->getValue());
    }

    public function testSignerSha256Hash(): void
    {
        $sha256Hash = 'da0d57da7d4850e7fc10d2a9d0ebc731f7afb40574c03395b17d49149b91f5be';
        $hash = hex2bin($sha256Hash);
        $signerKey = Signer::sha256Hash(\Soneso\StellarSDK\Crypto\StrKey::encodeSha256Hash($hash));

        $this->assertEquals(XdrSignerKeyType::HASH_X, $signerKey->getType()->getValue());
        $this->assertNotNull($signerKey->getHashX());
        $this->assertEquals($hash, $signerKey->getHashX());
    }

    public function testSignerPreAuthTx(): void
    {
        $sourceKeyPair = KeyPair::fromSeed('SCZANGBA5YHTNYVVV4C3U252E2B6P6F5T3U6MM63WBSBZATAQI3EBTQ4');

        $accountA = new Account($sourceKeyPair->getAccountId(), new BigInteger(1));

        $destKeyPair = KeyPair::random();
        $payment = (new \Soneso\StellarSDK\PaymentOperationBuilder($destKeyPair->getAccountId(), \Soneso\StellarSDK\Asset::native(), "10"))->build();

        $txBuilder = new TransactionBuilder($accountA);
        $txBuilder->addOperation($payment);
        $tx = $txBuilder->build();

        $signerKey = Signer::preAuthTx($tx, Network::testnet());

        $this->assertEquals(XdrSignerKeyType::PRE_AUTH_TX, $signerKey->getType()->getValue());
        $this->assertNotNull($signerKey->getPreAuthTx());
    }

    public function testSignerPreAuthTxHash(): void
    {
        $preAuthTxHash = '3389e9f0f1a65f19736cacf544c2e825313e8447f569233bb8db39aa607c8889';
        $hash = hex2bin($preAuthTxHash);
        $signerKey = Signer::preAuthTxHash(\Soneso\StellarSDK\Crypto\StrKey::encodePreAuthTx($hash));

        $this->assertEquals(XdrSignerKeyType::PRE_AUTH_TX, $signerKey->getType()->getValue());
        $this->assertNotNull($signerKey->getPreAuthTx());
        $this->assertEquals($hash, $signerKey->getPreAuthTx());
    }

    public function testSignerSignedPayload(): void
    {
        $accountStrKey = "GA7QYNF7SOWQ3GLR2BGMZEHXAVIRZA4KVWLTJJFC7MGXUA74P7UJVSGZ";
        $payloadHex = "0102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f20";
        $payload = hex2bin($payloadHex);

        $xdrAccountID = new \Soneso\StellarSDK\Xdr\XdrAccountID($accountStrKey);
        $signedPayloadSigner = new SignedPayloadSigner($xdrAccountID, $payload);
        $signerKey = Signer::signedPayload($signedPayloadSigner);

        $this->assertEquals(XdrSignerKeyType::ED25519_SIGNED_PAYLOAD, $signerKey->getType()->getValue());
        $this->assertNotNull($signerKey->getSignedPayload());
        $this->assertEquals($payload, $signerKey->getSignedPayload()->getPayload());
    }

    // SubmitTransactionResponse Tests

    public function testSubmitTransactionResponseSuccess(): void
    {
        $json = $this->getSuccessfulTransactionJson();
        $response = SubmitTransactionResponse::fromJson($json);

        $this->assertTrue($response->isSuccessful());
    }

    public function testSubmitTransactionResponseFailed(): void
    {
        $json = $this->getFailedTransactionJson();
        $response = SubmitTransactionResponse::fromJson($json);

        $this->assertFalse($response->isSuccessful());
    }

    public function testSubmitTransactionResponseExtras(): void
    {
        $json = $this->getFailedTransactionJson();
        $response = SubmitTransactionResponse::fromJson($json);

        $extras = $response->getExtras();
        $this->assertNotNull($extras);
        $this->assertNotNull($extras->getEnvelopeXdr());
        $this->assertNotNull($extras->getResultXdr());
    }

    public function testSubmitTransactionResponseSettersGetters(): void
    {
        $json = $this->getFailedTransactionJson();
        $response = SubmitTransactionResponse::fromJson($json);

        $originalExtras = $response->getExtras();
        $this->assertNotNull($originalExtras);

        $response->setExtras(null);
        $this->assertNull($response->getExtras());

        $response->setExtras($originalExtras);
        $this->assertNotNull($response->getExtras());
    }

    // Helper Methods

    private function createMockedClient(array $responses): Client
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        return new Client(['handler' => $handlerStack, 'base_uri' => 'https://horizon-testnet.stellar.org']);
    }

    private function getSampleAccountsPageJson(): string
    {
        return json_encode([
            '_links' => [
                'self' => ['href' => 'https://horizon-testnet.stellar.org/accounts?cursor=&limit=10&order=asc'],
                'next' => ['href' => 'https://horizon-testnet.stellar.org/accounts?cursor=next&limit=10&order=asc'],
                'prev' => ['href' => 'https://horizon-testnet.stellar.org/accounts?cursor=prev&limit=10&order=asc']
            ],
            '_embedded' => [
                'records' => [
                    [
                        'id' => self::TEST_ACCOUNT_ID,
                        'account_id' => self::TEST_ACCOUNT_ID,
                        'sequence' => '123456789012',
                        'subentry_count' => 0,
                        'last_modified_ledger' => 1234567,
                        'thresholds' => ['low_threshold' => 0, 'med_threshold' => 0, 'high_threshold' => 0],
                        'flags' => ['auth_required' => false, 'auth_revocable' => false],
                        'balances' => [['balance' => '10000.0000000', 'asset_type' => 'native']],
                        'signers' => [['key' => self::TEST_ACCOUNT_ID, 'weight' => 1, 'type' => 'ed25519_public_key']],
                        'data' => []
                    ]
                ]
            ]
        ]);
    }


    private function getSuccessfulTransactionJson(): array
    {
        return [
            'hash' => 'a434302ea03b42dd00614e258e6b7cdce5dc8a9d7381b1cba8844b75df4f1486',
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
            'memo_type' => 'none',
            'signatures' => ['sig1'],
            'valid_after' => '1970-01-01T00:00:00Z',
            'preconditions' => ['timebounds' => ['min_time' => '0', 'max_time' => '0']]
        ];
    }

    private function getFailedTransactionJson(): array
    {
        return [
            'hash' => 'ced549af061dc39758ce222f78f027e82b5077176a4e2efbeb4dc04086150b7d',
            'ledger' => 52429114,
            'created_at' => '2024-07-05T06:01:20Z',
            'source_account' => 'GAC6MRNVZNVFKAQRFGVJBNZ734T3HPJH3OVTKY433STDPZVRDI75UDLD',
            'source_account_sequence' => '169774989149474823',
            'fee_account' => 'GAC6MRNVZNVFKAQRFGVJBNZ734T3HPJH3OVTKY433STDPZVRDI75UDLD',
            'fee_charged' => '100',
            'max_fee' => '101',
            'operation_count' => 1,
            'envelope_xdr' => 'AAAAAgAAAAAF5kW1y2pVAhEpqpC3P98ns70n26s1Y5vcpjfmsRo/2gAAAGUCWyl0AAAQBwAAAAEAAAAAAAAAAAAAAABmh4w6AAAAAAAAAAEAAAABAAAAABDR1OY5pIP4DuC0MK3Wk8y/Cq8IWqrdDi5A0Fi5fOrjAAAAAgAAAAAAAAAAB8Ap6QAAAAAQ0dTmOaSD+A7gtDCt1pPMvwqvCFqq3Q4uQNBYuXzq4wAAAAF5WExNAAAAACI213D+DT4BUhl11c96xIQrcJXWsanXaNPppjLpmQa+AAAAAAfAKekAAAADAAAAAUJWTgAAAAAAEShm+lTZUjjj2ZcQshA+s474NGCWrBqmnq9nd6WvfqgAAAABQVFVQQAAAABblC5TrDPI/QqAzHwbGoXX2DipxBl3qtGLOvBX+OM98AAAAAF5WExNAAAAACI213D+DT4BUhl11c96xIQrcJXWsanXaNPppjLpmQa+AAAAAAAAAAKxGj/aAAAAQCjuVlhfl6G9ckJsEz4GwbOJWszHxtG7Lpja6yGjhC8W40yf/Uyc2AFlyMPxY3ujPyqc1yA7YeapeGnGNMLO/Ae5fOrjAAAAQK1szEc5G1Tk17+q1DjW39+N/01CtgQ/584UvKjUSbogqp8JHn6PxMK2iZC099p5GVcPE51kBKobhXl46yKPWAk=',
            'result_xdr' => 'AAAAAAAAAGT/////AAAAAQAAAAAAAAAC////9gAAAAA=',
            'result_meta_xdr' => 'AAAAAwAAAAAAAAACAAAAAwMgAToAAAAAAAAAAAXmRbXLalUCESmqkLc/3yezvSfbqzVjm9ymN+axGj/aAAAAAADw28wCWyl0AAAQBgAAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAAAAAAAAAAAAAAAAAAMAAAAAAx//TwAAAABmh4DsAAAAAAAAAAEDIAE6AAAAAAAAAAAF5kW1y2pVAhEpqpC3P98ns70n26s1Y5vcpjfmsRo/2gAAAAAA8NvMAlspdAAAEAcAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgAToAAAAAZoeMMAAAAAAAAAAAAAAAAAAAAAA=',
            'fee_meta_xdr' => 'AAAAAgAAAAMDH/9PAAAAAAAAAAAF5kW1y2pVAhEpqpC3P98ns70n26s1Y5vcpjfmsRo/2gAAAAAA8NwwAlspdAAAEAYAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMf/08AAAAAZoeA7AAAAAAAAAABAyABOgAAAAAAAAAABeZFtctqVQIRKaqQtz/fJ7O9J9urNWOb3KY35rEaP9oAAAAAAPDbzAJbKXQAABAGAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAACAAAAAAAAAAAAAAAAAAAAAwAAAAADH/9PAAAAAGaHgOwAAAAA',
            'memo_type' => 'none',
            'signatures' => ['sig1'],
            'valid_after' => '1970-01-01T00:00:00Z',
            'preconditions' => ['timebounds' => ['min_time' => '0', 'max_time' => '0']],
            'extras' => [
                'envelope_xdr' => 'AAAAAgAAAAAF5kW1y2pVAhEpqpC3P98ns70n26s1Y5vcpjfmsRo/2gAAAGUCWyl0AAAQBwAAAAEAAAAAAAAAAAAAAABmh4w6AAAAAAAAAAEAAAABAAAAABDR1OY5pIP4DuC0MK3Wk8y/Cq8IWqrdDi5A0Fi5fOrjAAAAAgAAAAAAAAAAB8Ap6QAAAAAQ0dTmOaSD+A7gtDCt1pPMvwqvCFqq3Q4uQNBYuXzq4wAAAAF5WExNAAAAACI213D+DT4BUhl11c96xIQrcJXWsanXaNPppjLpmQa+AAAAAAfAKekAAAADAAAAAUJWTgAAAAAAEShm+lTZUjjj2ZcQshA+s474NGCWrBqmnq9nd6WvfqgAAAABQVFVQQAAAABblC5TrDPI/QqAzHwbGoXX2DipxBl3qtGLOvBX+OM98AAAAAF5WExNAAAAACI213D+DT4BUhl11c96xIQrcJXWsanXaNPppjLpmQa+AAAAAAAAAAKxGj/aAAAAQCjuVlhfl6G9ckJsEz4GwbOJWszHxtG7Lpja6yGjhC8W40yf/Uyc2AFlyMPxY3ujPyqc1yA7YeapeGnGNMLO/Ae5fOrjAAAAQK1szEc5G1Tk17+q1DjW39+N/01CtgQ/584UvKjUSbogqp8JHn6PxMK2iZC099p5GVcPE51kBKobhXl46yKPWAk=',
                'result_xdr' => 'AAAAAAAAAGT/////AAAAAQAAAAAAAAAC////9gAAAAA=',
                'result_codes' => [
                    'transaction' => 'tx_failed'
                ]
            ]
        ];
    }

}
