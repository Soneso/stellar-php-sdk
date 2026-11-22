<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\URIScheme;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Soneso\StellarSDK\SEP\URIScheme\SubmitUriSchemeTransactionResponse;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;
use Soneso\StellarSDK\SEP\URIScheme\URISchemeError;
use Soneso\StellarSDK\StellarSDK;
use InvalidArgumentException;

/**
 * Unit tests for SEP-7 URI Scheme classes
 */
class URISchemeTest extends TestCase
{
    private const TEST_ACCOUNT = 'GBRPYHIL2CI3FNQ4BXLFMNDLFJUNPU2HY3ZMFSHONUCEOASW7QC7OX2H';
    private const TEST_DESTINATION = 'GBQECQVAS2FJ7DLCUXDASZAJQLWPXNTCR2FXSCTV2ATHWKIE6T7MKBU4';
    private const TEST_SECRET = 'SC465CXQLIUL5S5Q62675EDD3HGTXWXKK4HFTHVDYF4GAAYFULPVDV7D';
    private const TEST_SIGNER_ACCOUNT = 'GBIFUEOM7HM5N335736UGYXAQSVHIMHD3KPGIZQGJXRRE6VSD4VRJVM4';
    private const TEST_XDR = 'AAAAAgAAAAA2YISI8AAPy2Fl+4Z5jvNHqRz9SClHHBH4vb/DhYrV5QAAAGQAAHGcAAAABAAAAAEAAAAAAAAAAAAAAABmgRXDAAAAAQAAABVIZWxsbywgU3RlbGxhciBXb3JsZCEAAAAAAAABAAAAAAAAAAEAAAAAbmgm1V2dg5V1mq1elMcG1txjSYKZ9wEgoSBaeW8UiFoAAAABVVNEQwAAAABzYWLsUZvT8QupERb1qVZfnpDqwMO7Q2f1k8PsYhVvswAAAAAF9eEAAAAAAAAAAAGFitXlAAAAQG3cOryMgNAXQMogDSkUspDbqA6E94tJnPE1B0gFxLEcrEZKbfNFoinkmXn5iVeN9Q6qE9sFuOaGnc2MyqOFnQM=';
    // Testnet transaction 11446413...aadc: one CREATE_CONTRACT_V2 host function that deploys the Stellar
    // Asset Contract of an AlphaNum4 asset with an empty constructor argument list, and one signature.
    private const SAC_V2_ENVELOPE = 'AAAAAgAAAAAMCf4TNV9jU4d5QPRyB0MihH9zgsUW6keMIZsIAP/UDgABIWIAWNn5AAAAAQAAAAEAAAAAAAAAAAAAAABq/6QvAAAAAAAAAAEAAAAAAAAAGAAAAAMAAAABAAAAAUxXVjIAAAAADAn+EzVfY1OHeUD0cgdDIoR/c4LFFupHjCGbCAD/1A4AAAABAAAAAAAAAAAAAAABAAAAAAAAAAAAAAABAAAABgAAAAFv3MrH8x/kbUTlOq9FidhQHGBb+ZN/BNe3GCwznhvb8wAAABQAAAABAAKrMQAAAAAAAAHcAAAAAAABIP4AAAABAP/UDgAAAEBDm1gYDzIAJHtXc0hWqix7I22s4B9ga7eWJp5+8eWxuT/608SPbN9y5hzli1R2NX9ARlcA5fNgcEUnW1Y6v8sD';
    private const SAC_V2_NETWORK_HASH = '11446413b5a7a95f8bb18e616a5187f19a5a7b146fdef3c5c2c77e5ea87aaadc';

    private ?Client $origPublicClient = null;
    private ?Client $origTestNetClient = null;
    private ?Client $origFutureNetClient = null;

    /** @var array<int, array<string, mixed>> */
    private array $publicHistory = [];
    /** @var array<int, array<string, mixed>> */
    private array $testNetHistory = [];
    /** @var array<int, array<string, mixed>> */
    private array $futureNetHistory = [];

    protected function tearDown(): void
    {
        if ($this->origPublicClient !== null) {
            StellarSDK::getPublicNetInstance()->setHttpClient($this->origPublicClient);
            $this->origPublicClient = null;
        }
        if ($this->origTestNetClient !== null) {
            StellarSDK::getTestNetInstance()->setHttpClient($this->origTestNetClient);
            $this->origTestNetClient = null;
        }
        if ($this->origFutureNetClient !== null) {
            StellarSDK::getFutureNetInstance()->setHttpClient($this->origFutureNetClient);
            $this->origFutureNetClient = null;
        }
        parent::tearDown();
    }

    private function getSampleSubmitTransactionJson(): string
    {
        return (string)json_encode([
            'hash' => 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2',
            'ledger' => 123456,
            'envelope_xdr' => 'AAAAAgAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAGQDHvLTAAABIwAAAAEAAAAAAAAAAAAAAABmh4pDAAAAAQAAABgwLDA3NSUgRGFpbHkgZm9yIEhvbGRlcnMAAAABAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAABaG1GYAAAAAAAAAAKpYmInAAAAQLmth39Fjo8TC05wn5ZOAw4lou2rkxAaK6k16lHYXlEcsYHZ/d+ga5bCgO9KV/sbKaZAUCC9KvFIplXkXffBxQ0WUsBeAAAAQC2w45T3S24shkJ7uyRl/P5xD86Xfi7qTYxmb8uh8PEcwlb5oqbnJcTlUV2uJs2+gzMlijNtAbrCm6wO+1YsJQ4=',
            'result_xdr' => 'AAAAAAAAAGQAAAAAAAAAAQAAAAAAAAABAAAAAAAAAAA=',
            'result_meta_xdr' => 'AAAAAwAAAAAAAAACAAAAAwMgANMAAAAAAAAAABpkeES/191B7z9YrUh5J2wySk7XDoZU7ulEgyoWUsBeAAAAAAHJUdQDHvLTAAABIgAAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAAAAAAAAAAAAAAAAAAMAAAAAAyAAyAAAAABmh4mjAAAAAAAAAAEDIADTAAAAAAAAAAAaZHhEv9fdQe8/WK1IeSdsMkpO1w6GVO7pRIMqFlLAXgAAAAAByVHUAx7y0wAAASMAAAAAAAAAAAAAAAAAAAAAAQAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAIAAAAAAAAAAAAAAAAAAAADAAAAAAMgANMAAAAAZoeJ4wAAAAAAAAABAAAABAAAAAMDIADTAAAAAQAAAABDkt3qvAkFIBzwQNUTIuVYO6lakWIP/qYVmqhwqWJiJwAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAKTCBRhynx//////////wAAAAEAAAABAAAA1xsdUZsAAAAAAAAAAAAAAAAAAAAAAAAAAQMgANMAAAABAAAAAEOS3eq8CQUgHPBA1RMi5Vg7qVqRYg/+phWaqHCpYmInAAAAAUhVTgAAAAAAYqvLVFIr7zrouOae2L2ZCcrBlj9GH/FhAvIptvt76+QAApMHukZ45H//////////AAAAAQAAAAEAAADXGx1RmwAAAAAAAAAAAAAAAAAAAAAAAAADAx/LTQAAAAEAAAAALKh2/uxMx/7OQG16N5OsdpWPl0ZGSeDIVpWQqsOV2wIAAAABSFVOAAAAAABiq8tUUivvOui45p7YvZkJysGWP0Yf8WEC8im2+3vr5AAAAdVOSfYYf/////////8AAAABAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAEDIADTAAAAAQAAAAAsqHb+7EzH/s5AbXo3k6x2lY+XRkZJ4MhWlZCqw5XbAgAAAAFIVU4AAAAAAGKry1RSK+866Ljmnti9mQnKwZY/Rh/xYQLyKbb7e+vkAAAB1ahlR7B//////////wAAAAEAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
        ]);
    }

    private function installHorizonMocks(): void
    {
        $publicSdk = StellarSDK::getPublicNetInstance();
        $testNetSdk = StellarSDK::getTestNetInstance();
        $futureNetSdk = StellarSDK::getFutureNetInstance();

        $this->origPublicClient ??= $publicSdk->getHttpClient();
        $this->origTestNetClient ??= $testNetSdk->getHttpClient();
        $this->origFutureNetClient ??= $futureNetSdk->getHttpClient();

        $this->publicHistory = [];
        $this->testNetHistory = [];
        $this->futureNetHistory = [];

        $body = $this->getSampleSubmitTransactionJson();

        $publicStack = HandlerStack::create(new MockHandler([new Response(200, [], $body)]));
        $publicStack->push(Middleware::history($this->publicHistory));
        $publicSdk->setHttpClient(new Client([
            'base_uri' => StellarSDK::$PUBLIC_NET_HORIZON_URL,
            'handler' => $publicStack,
        ]));

        $testNetStack = HandlerStack::create(new MockHandler([new Response(200, [], $body)]));
        $testNetStack->push(Middleware::history($this->testNetHistory));
        $testNetSdk->setHttpClient(new Client([
            'base_uri' => StellarSDK::$TEST_NET_HORIZON_URL,
            'handler' => $testNetStack,
        ]));

        $futureNetStack = HandlerStack::create(new MockHandler([new Response(200, [], $body)]));
        $futureNetStack->push(Middleware::history($this->futureNetHistory));
        $futureNetSdk->setHttpClient(new Client([
            'base_uri' => StellarSDK::$FUTURE_NET_HORIZON_URL,
            'handler' => $futureNetStack,
        ]));
    }

    private function computeExpectedSignedV1EnvelopeBase64(
        string $envelopeBase64,
        KeyPair $signer,
        string $networkPassphrase,
    ): string {
        $fixture = base64_decode($envelopeBase64, true);
        $this->assertIsString($fixture);
        // TEST_XDR is a V1 envelope ending with signature count 1 and one 72-byte decorated signature.
        $txEnd = strlen($fixture) - 4 - 72;
        $hash = hash(
            'sha256',
            hash('sha256', $networkPassphrase, true) . substr($fixture, 0, $txEnd),
            true
        );
        $expectedEnvelope = substr($fixture, 0, $txEnd)
            . pack('N', 2)
            . substr($fixture, $txEnd + 4)
            . substr($signer->getPublicKey(), -4)
            . pack('N', 64)
            . $signer->sign($hash);
        return base64_encode($expectedEnvelope);
    }

    private function assertDirectHorizonSubmission(
        ?Network $network,
        string $expectedPassphrase,
        string $expectedHost,
        string $expectedTarget,
    ): void {
        $this->installHorizonMocks();

        $uriScheme = new URIScheme();
        $signer = KeyPair::fromSeed(self::TEST_SECRET);
        // TEST_XDR carries a text memo so the SEP-29 memo check makes no account lookup request.
        $uri = $uriScheme->generateSignTransactionURI(self::TEST_XDR);

        $response = $uriScheme->signAndSubmitTransaction($uri, $signer, $network);

        $this->assertNull($response->getCallBackResponse());
        $this->assertNotNull($response->getSubmitTransactionResponse());
        $this->assertTrue($response->getSubmitTransactionResponse()->isSuccessful());

        $this->assertCount($expectedTarget === 'public' ? 1 : 0, $this->publicHistory);
        $this->assertCount($expectedTarget === 'testnet' ? 1 : 0, $this->testNetHistory);
        $this->assertCount($expectedTarget === 'futurenet' ? 1 : 0, $this->futureNetHistory);

        $history = match ($expectedTarget) {
            'public' => $this->publicHistory,
            'testnet' => $this->testNetHistory,
            'futurenet' => $this->futureNetHistory,
        };
        $request = $history[0]['request'];
        $this->assertSame('POST', $request->getMethod());

        $requestUri = $request->getUri();
        $this->assertSame('https', $requestUri->getScheme());
        $this->assertSame($expectedHost, $requestUri->getHost());
        $this->assertSame('/transactions', $requestUri->getPath());

        parse_str($requestUri->getQuery(), $query);
        $this->assertIsString($query['tx'] ?? null);

        $expectedBase64 = $this->computeExpectedSignedV1EnvelopeBase64(self::TEST_XDR, $signer, $expectedPassphrase);
        $this->assertSame($expectedBase64, $query['tx']);
    }

    // ==================== URIScheme Constructor Tests ====================

    public function testURISchemeConstructor(): void
    {
        $uriScheme = new URIScheme();
        $this->assertInstanceOf(URIScheme::class, $uriScheme);
    }

    // ==================== generateSignTransactionURI Tests ====================

    public function testGenerateSignTransactionURIBasic(): void
    {
        $uriScheme = new URIScheme();

        $uri = $uriScheme->generateSignTransactionURI(self::TEST_XDR);

        $this->assertStringStartsWith('web+stellar:tx?', $uri);
        $this->assertStringContainsString('xdr=', $uri);
    }

    public function testGenerateSignTransactionURIWithAllParameters(): void
    {
        $uriScheme = new URIScheme();

        $uri = $uriScheme->generateSignTransactionURI(
            self::TEST_XDR,
            replace: 'sourceAccount:X',
            callback: 'url:https://example.com/callback',
            publicKey: self::TEST_ACCOUNT,
            chain: 'web+stellar:tx?xdr=other',
            message: 'Please sign',
            networkPassphrase: 'Test SDF Network ; September 2015',
            originDomain: 'example.com',
            signature: 'YWJjZGVm'
        );

        $this->assertStringStartsWith('web+stellar:tx?', $uri);
        $this->assertStringContainsString('xdr=', $uri);
        $this->assertStringContainsString('replace=', $uri);
        $this->assertStringContainsString('callback=', $uri);
        $this->assertStringContainsString('pubkey=', $uri);
        $this->assertStringContainsString('chain=', $uri);
        $this->assertStringContainsString('msg=', $uri);
        $this->assertStringContainsString('network_passphrase=', $uri);
        $this->assertStringContainsString('origin_domain=', $uri);
        $this->assertStringContainsString('signature=', $uri);
    }

    public function testGenerateSignTransactionURIWithCallback(): void
    {
        $uriScheme = new URIScheme();

        $uri = $uriScheme->generateSignTransactionURI(
            self::TEST_XDR,
            callback: 'url:https://example.com/submit'
        );

        $this->assertStringContainsString('callback=', $uri);
        $this->assertStringContainsString('example.com', urldecode($uri));
    }

    public function testGenerateSignTransactionURIWithMessage(): void
    {
        $uriScheme = new URIScheme();

        $uri = $uriScheme->generateSignTransactionURI(
            self::TEST_XDR,
            message: 'Please review and sign this transaction'
        );

        $this->assertStringContainsString('msg=', $uri);
    }

    // ==================== generatePayOperationURI Tests ====================

    public function testGeneratePayOperationURIBasic(): void
    {
        $uriScheme = new URIScheme();

        $uri = $uriScheme->generatePayOperationURI(self::TEST_DESTINATION);

        $this->assertStringStartsWith('web+stellar:pay?', $uri);
        $this->assertStringContainsString('destination=', $uri);
    }

    public function testGeneratePayOperationURIWithAmount(): void
    {
        $uriScheme = new URIScheme();

        $uri = $uriScheme->generatePayOperationURI(
            self::TEST_DESTINATION,
            amount: '100.50'
        );

        $this->assertStringContainsString('amount=', $uri);
        $this->assertStringContainsString('100.50', $uri);
    }

    public function testGeneratePayOperationURIWithAsset(): void
    {
        $uriScheme = new URIScheme();

        $uri = $uriScheme->generatePayOperationURI(
            self::TEST_DESTINATION,
            amount: '100',
            assetCode: 'USDC',
            assetIssuer: self::TEST_ACCOUNT
        );

        $this->assertStringContainsString('asset_code=', $uri);
        $this->assertStringContainsString('USDC', $uri);
        $this->assertStringContainsString('asset_issuer=', $uri);
    }

    public function testGeneratePayOperationURIWithMemo(): void
    {
        $uriScheme = new URIScheme();

        $uri = $uriScheme->generatePayOperationURI(
            self::TEST_DESTINATION,
            memo: '12345',
            memoType: 'MEMO_ID'
        );

        $this->assertStringContainsString('memo=', $uri);
        $this->assertStringContainsString('memo_type=', $uri);
    }

    public function testGeneratePayOperationURIWithAllParameters(): void
    {
        $uriScheme = new URIScheme();

        $uri = $uriScheme->generatePayOperationURI(
            self::TEST_DESTINATION,
            amount: '50',
            assetCode: 'USD',
            assetIssuer: self::TEST_ACCOUNT,
            memo: 'Payment for services',
            memoType: 'MEMO_TEXT',
            callback: 'url:https://example.com/payment',
            message: 'Pay for subscription',
            networkPassphrase: 'Test SDF Network ; September 2015',
            originDomain: 'example.com',
            signature: 'YWJjZGVm'
        );

        $this->assertStringStartsWith('web+stellar:pay?', $uri);
        $this->assertStringContainsString('destination=', $uri);
        $this->assertStringContainsString('amount=', $uri);
        $this->assertStringContainsString('asset_code=', $uri);
        $this->assertStringContainsString('asset_issuer=', $uri);
        $this->assertStringContainsString('memo=', $uri);
        $this->assertStringContainsString('memo_type=', $uri);
        $this->assertStringContainsString('callback=', $uri);
        $this->assertStringContainsString('msg=', $uri);
        $this->assertStringContainsString('network_passphrase=', $uri);
        $this->assertStringContainsString('origin_domain=', $uri);
        $this->assertStringContainsString('signature=', $uri);
    }

    // ==================== getParameterValue Tests ====================

    public function testGetParameterValue(): void
    {
        $uriScheme = new URIScheme();
        $uri = 'web+stellar:tx?xdr=AAAA&msg=Hello&pubkey=' . self::TEST_ACCOUNT;

        $xdr = $uriScheme->getParameterValue('xdr', $uri);
        $msg = $uriScheme->getParameterValue('msg', $uri);
        $pubkey = $uriScheme->getParameterValue('pubkey', $uri);
        $missing = $uriScheme->getParameterValue('nonexistent', $uri);

        $this->assertEquals('AAAA', $xdr);
        $this->assertEquals('Hello', $msg);
        $this->assertEquals(self::TEST_ACCOUNT, $pubkey);
        $this->assertNull($missing);
    }

    public function testGetParameterValueFromPayUri(): void
    {
        $uriScheme = new URIScheme();
        $uri = 'web+stellar:pay?destination=' . self::TEST_DESTINATION . '&amount=100&asset_code=XLM';

        $destination = $uriScheme->getParameterValue('destination', $uri);
        $amount = $uriScheme->getParameterValue('amount', $uri);
        $assetCode = $uriScheme->getParameterValue('asset_code', $uri);

        $this->assertEquals(self::TEST_DESTINATION, $destination);
        $this->assertEquals('100', $amount);
        $this->assertEquals('XLM', $assetCode);
    }

    // ==================== signURI Tests ====================

    public function testSignURI(): void
    {
        $uriScheme = new URIScheme();
        $keyPair = KeyPair::fromSeed(self::TEST_SECRET);

        $uri = $uriScheme->generateSignTransactionURI(
            self::TEST_XDR,
            originDomain: 'example.com'
        );

        $signedUri = $uriScheme->signURI($uri, $keyPair);

        $this->assertStringContainsString('signature=', $signedUri);
        $this->assertStringContainsString('origin_domain=', $signedUri);
    }

    public function testSignURIWithPayOperation(): void
    {
        $uriScheme = new URIScheme();
        $keyPair = KeyPair::fromSeed(self::TEST_SECRET);

        $uri = $uriScheme->generatePayOperationURI(
            self::TEST_DESTINATION,
            amount: '100',
            originDomain: 'example.com'
        );

        $signedUri = $uriScheme->signURI($uri, $keyPair);

        $this->assertStringContainsString('signature=', $signedUri);
    }

    /**
     * Verifies the signing implementation against the test vector from the SEP-7 specification.
     * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0007.md#request-signing
     */
    public function testSignURIMatchesSpecTestVector(): void
    {
        $uri = 'web+stellar:pay?destination=GCALNQQBXAPZ2WIRSDDBMSTAKCUH5SG6U76YBFLQLIXJTF7FE5AX7AOO'
            . '&amount=120.1234567&memo=skdjfasf&memo_type=MEMO_TEXT'
            . '&msg=pay%20me%20with%20lumens&origin_domain=someDomain.com';
        $signer = KeyPair::fromSeed('SBPOVRVKTTV7W3IOX2FJPSMPCJ5L2WU2YKTP3HCLYPXNI5MDIGREVNYC');

        $uriScheme = new URIScheme();
        $signedUri = $uriScheme->signURI($uri, $signer);

        $expectedSignature = 'tbsLtlK/fouvRWk2UWFP47yHYeI1g1NEC/fEQvuXG6V8P+beLxplYbOVtTk1g94Wp97cHZ3pVJy/tZNYobl3Cw==';
        $parts = parse_url($signedUri);
        parse_str($parts['query'] ?? '', $params);

        $this->assertEquals($expectedSignature, $params['signature']);
    }

    // ==================== signAndSubmitTransaction Tests ====================

    public function testSignAndSubmitTransactionToCallback(): void
    {
        $uriScheme = new URIScheme();
        $keyPair = KeyPair::fromSeed(self::TEST_SECRET);

        // Mock HTTP client to return 200 for callback
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], '')
        ]);
        $handlerStack = HandlerStack::create($mock);
        $uriScheme->setMockHandlerStack($handlerStack);

        // Generate URI with callback
        $uri = $uriScheme->generateSignTransactionURI(
            self::TEST_XDR,
            callback: 'url:https://example.com/callback',
            originDomain: 'example.com'
        );
        $signedUri = $uriScheme->signURI($uri, $keyPair);

        $response = $uriScheme->signAndSubmitTransaction($signedUri, $keyPair, Network::testnet());

        $this->assertNotNull($response->getCallBackResponse());
        $this->assertEquals(200, $response->getCallBackResponse()->getStatusCode());
        $this->assertNull($response->getSubmitTransactionResponse());
    }

    public function testSignAndSubmitTransactionKeepsSacCreateContractV2Transaction(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '')]));
        $stack->push(Middleware::history($history));
        $uriScheme = new URIScheme();
        $uriScheme->setMockHandlerStack($stack);
        $signer = KeyPair::random();
        $uri = $uriScheme->generateSignTransactionURI(self::SAC_V2_ENVELOPE, callback: 'url:https://wallet.invalid/sep7/callback');

        $uriScheme->signAndSubmitTransaction($uri, $signer, Network::testnet());

        $this->assertCount(1, $history);
        // The form body carries the base64 envelope URL-encoded once, so parse_str yields the base64 string directly.
        parse_str((string)$history[0]['request']->getBody(), $form);
        $this->assertIsString($form['xdr'] ?? null);
        $submitted = base64_decode($form['xdr'], true);
        $this->assertIsString($submitted);
        $fixture = base64_decode(self::SAC_V2_ENVELOPE, true);
        // The fixture ends with its signature list: the count 1 and one 72-byte decorated signature.
        // The bytes before it are ENVELOPE_TYPE_TX and the transaction, the tail of the signed payload.
        $txEnd = strlen($fixture) - 4 - 72;
        $this->assertSame(bin2hex(substr($fixture, 0, $txEnd)), bin2hex(substr($submitted, 0, $txEnd)));
        $this->assertSame(bin2hex(pack('N', 2) . substr($fixture, $txEnd + 4)), bin2hex(substr($submitted, $txEnd, 4 + 72)));
        $this->assertSame($txEnd + 4 + 2 * 72, strlen($submitted));
        $hash = hash('sha256', hash('sha256', Network::testnet()->getNetworkPassphrase(), true) . substr($submitted, 0, $txEnd), true);
        $this->assertSame(self::SAC_V2_NETWORK_HASH, bin2hex($hash));
        $added = substr($submitted, $txEnd + 4 + 72);
        $this->assertSame(bin2hex(substr($signer->getPublicKey(), -4) . pack('N', 64)), bin2hex(substr($added, 0, 8)));
        $this->assertTrue($signer->verifySignature(substr($added, 8), $hash));
    }

    public function testSignAndSubmitTransactionEncodesCallbackXdrOnce(): void
    {
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '')]));
        $stack->push(Middleware::history($history));
        $uriScheme = new URIScheme();
        $uriScheme->setMockHandlerStack($stack);
        $signer = KeyPair::fromSeed(self::TEST_SECRET);
        $uri = $uriScheme->generateSignTransactionURI(
            self::TEST_XDR,
            callback: 'url:https://wallet.invalid/sep7/callback'
        );

        $uriScheme->signAndSubmitTransaction($uri, $signer, Network::testnet());

        $this->assertCount(1, $history);
        $request = $history[0]['request'];
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));

        $body = (string)$request->getBody();
        $this->assertStringStartsWith('xdr=', $body);
        $this->assertStringNotContainsString('%25', $body);
        // TEST_XDR holds '+', '/' and '=', so the body carries their form encoding.
        $this->assertStringContainsString('%2B', $body);
        $this->assertStringContainsString('%2F', $body);
        $this->assertStringContainsString('%3D', $body);

        parse_str($body, $form);
        $this->assertIsString($form['xdr'] ?? null);

        $fixture = base64_decode(self::TEST_XDR, true);
        $this->assertIsString($fixture);
        // TEST_XDR is a V1 envelope ending with signature count 1 and one 72-byte decorated signature.
        $txEnd = strlen($fixture) - 4 - 72;
        $hash = hash(
            'sha256',
            hash('sha256', Network::testnet()->getNetworkPassphrase(), true) . substr($fixture, 0, $txEnd),
            true
        );
        $expectedEnvelope = substr($fixture, 0, $txEnd)
            . pack('N', 2)
            . substr($fixture, $txEnd + 4)
            . substr($signer->getPublicKey(), -4)
            . pack('N', 64)
            . $signer->sign($hash);
        $expectedBase64 = base64_encode($expectedEnvelope);

        $this->assertSame($expectedBase64, $form['xdr']);
        $this->assertSame('xdr=' . urlencode($expectedBase64), $body);
    }

    public function testSignAndSubmitTransactionSendsCallbackThroughInjectedHttpClient(): void
    {
        // The host never resolves (RFC 6761), so a request that bypasses the injected client
        // fails without reaching a server.
        $callbackUrl = 'https://wallet.invalid/sep7/callback';
        $history = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '')]));
        $stack->push(Middleware::history($history));
        $uriScheme = new URIScheme(new Client(['handler' => $stack, 'timeout' => 12.5]));
        $uri = $uriScheme->generateSignTransactionURI(self::TEST_XDR, callback: 'url:' . $callbackUrl);

        $response = $uriScheme->signAndSubmitTransaction($uri, KeyPair::fromSeed(self::TEST_SECRET), Network::testnet());

        $this->assertSame(200, $response->getCallBackResponse()?->getStatusCode());
        $this->assertCount(1, $history);
        $this->assertSame('POST', $history[0]['request']->getMethod());
        $this->assertSame($callbackUrl, (string)$history[0]['request']->getUri());
        $this->assertStringStartsWith('xdr=', (string)$history[0]['request']->getBody());
        $this->assertSame(12.5, $history[0]['options']['timeout']);
    }

    public function testSignAndSubmitTransactionRejectsHttpCallback(): void
    {
        $uriScheme = new URIScheme();
        $keyPair = KeyPair::fromSeed(self::TEST_SECRET);

        $uri = $uriScheme->generateSignTransactionURI(
            self::TEST_XDR,
            callback: 'url:http://evil.example.com/steal',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service URL must use HTTPS');
        $uriScheme->signAndSubmitTransaction($uri, $keyPair, Network::testnet());
    }

    public function testSignAndSubmitTransactionSubmitsToPublicHorizon(): void
    {
        $this->assertDirectHorizonSubmission(
            Network::public(),
            'Public Global Stellar Network ; September 2015',
            'horizon.stellar.org',
            'public',
        );
        $this->assertDirectHorizonSubmission(
            null,
            'Public Global Stellar Network ; September 2015',
            'horizon.stellar.org',
            'public',
        );
    }

    public function testSignAndSubmitTransactionSubmitsToTestnetHorizon(): void
    {
        $this->assertDirectHorizonSubmission(
            Network::testnet(),
            'Test SDF Network ; September 2015',
            'horizon-testnet.stellar.org',
            'testnet',
        );
    }

    public function testSignAndSubmitTransactionSubmitsToFuturenetHorizon(): void
    {
        $this->assertDirectHorizonSubmission(
            Network::futurenet(),
            'Test SDF Future Network ; October 2022',
            'horizon-futurenet.stellar.org',
            'futurenet',
        );
    }

    public function testSignAndSubmitTransactionRejectsCustomNetworkWithoutCallback(): void
    {
        $this->installHorizonMocks();

        $uriScheme = new URIScheme();
        $signer = KeyPair::fromSeed(self::TEST_SECRET);
        $uri = $uriScheme->generateSignTransactionURI(self::TEST_XDR);
        $customPassphrase = 'Custom Private Network ; 2026';

        try {
            $uriScheme->signAndSubmitTransaction($uri, $signer, new Network($customPassphrase));
            $this->fail('Expected InvalidArgumentException for custom network without callback');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString($customPassphrase, $e->getMessage());
            $this->assertStringContainsString('No Horizon URL', $e->getMessage());
        }

        $this->assertCount(0, $this->publicHistory);
        $this->assertCount(0, $this->testNetHistory);
        $this->assertCount(0, $this->futureNetHistory);
    }

    public function testSignAndSubmitTransactionPostsCustomNetworkToUrlCallback(): void
    {
        $this->installHorizonMocks();

        $callbackHistory = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '')]));
        $stack->push(Middleware::history($callbackHistory));

        $uriScheme = new URIScheme();
        $uriScheme->setMockHandlerStack($stack);

        $signer = KeyPair::fromSeed(self::TEST_SECRET);
        $customPassphrase = 'Custom Private Network ; 2026';
        $callbackUrl = 'https://wallet.invalid/sep7/custom-callback';
        $uri = $uriScheme->generateSignTransactionURI(self::TEST_XDR, callback: 'url:' . $callbackUrl);

        $response = $uriScheme->signAndSubmitTransaction($uri, $signer, new Network($customPassphrase));

        $this->assertNull($response->getSubmitTransactionResponse());
        $this->assertNotNull($response->getCallBackResponse());
        $this->assertSame(200, $response->getCallBackResponse()->getStatusCode());

        $this->assertCount(1, $callbackHistory);
        $this->assertCount(0, $this->publicHistory);
        $this->assertCount(0, $this->testNetHistory);
        $this->assertCount(0, $this->futureNetHistory);

        $request = $callbackHistory[0]['request'];
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame($callbackUrl, (string)$request->getUri());
        $this->assertSame('application/x-www-form-urlencoded', $request->getHeaderLine('Content-Type'));

        $body = (string)$request->getBody();
        parse_str($body, $form);
        $this->assertIsString($form['xdr'] ?? null);

        $expectedBase64 = $this->computeExpectedSignedV1EnvelopeBase64(self::TEST_XDR, $signer, $customPassphrase);
        $this->assertSame($expectedBase64, $form['xdr']);
        $this->assertSame('xdr=' . urlencode($expectedBase64), $body);
    }

    public function testSignAndSubmitTransactionRejectsUnsupportedCallbacks(): void
    {
        $signer = KeyPair::fromSeed(self::TEST_SECRET);
        $encodedXdr = urlencode(self::TEST_XDR);
        $cases = [
            ['web+stellar:tx?xdr=' . $encodedXdr . '&callback=foo:bar', 'foo:bar'],
            ['web+stellar:tx?xdr=' . $encodedXdr . '&callback=foo%3Abar', 'foo:bar'],
            ['web+stellar:tx?xdr=' . $encodedXdr . '&callback=', 'empty callback'],
        ];

        foreach ($cases as [$uri, $expectedFragment]) {
            $this->installHorizonMocks();

            $callbackHistory = [];
            $stack = HandlerStack::create(new MockHandler([new Response(200, [], '')]));
            $stack->push(Middleware::history($callbackHistory));

            $uriScheme = new URIScheme();
            $uriScheme->setMockHandlerStack($stack);

            try {
                $uriScheme->signAndSubmitTransaction($uri, $signer, Network::testnet());
                $this->fail('Expected InvalidArgumentException for unsupported callback URI: ' . $uri);
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Unsupported', $e->getMessage());
                $this->assertStringContainsString($expectedFragment, $e->getMessage());
            }

            $this->assertCount(0, $callbackHistory);
            $this->assertCount(0, $this->publicHistory);
            $this->assertCount(0, $this->testNetHistory);
            $this->assertCount(0, $this->futureNetHistory);
        }
    }

    public function testSignAndSubmitTransactionRejectsUnparseableHttpsCallback(): void
    {
        $this->installHorizonMocks();

        $callbackHistory = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '')]));
        $stack->push(Middleware::history($callbackHistory));

        $uriScheme = new URIScheme();
        $uriScheme->setMockHandlerStack($stack);

        $signer = KeyPair::fromSeed(self::TEST_SECRET);
        $uri = 'web+stellar:tx?xdr=' . urlencode(self::TEST_XDR) . '&callback=url:https:///x';

        try {
            $uriScheme->signAndSubmitTransaction($uri, $signer, Network::testnet());
            $this->fail('Expected InvalidArgumentException for unparseable HTTPS callback URL');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Service URL cannot be parsed.', $e->getMessage());
            $this->assertStringNotContainsString('https:///x', $e->getMessage());
        }

        $this->assertCount(0, $callbackHistory);
        $this->assertCount(0, $this->publicHistory);
        $this->assertCount(0, $this->testNetHistory);
        $this->assertCount(0, $this->futureNetHistory);
    }

    public function testSignAndSubmitTransactionRejectsHttpsCallbackWithoutHost(): void
    {
        $this->installHorizonMocks();

        $callbackHistory = [];
        $stack = HandlerStack::create(new MockHandler([new Response(200, [], '')]));
        $stack->push(Middleware::history($callbackHistory));

        $uriScheme = new URIScheme();
        $uriScheme->setMockHandlerStack($stack);

        $signer = KeyPair::fromSeed(self::TEST_SECRET);
        $uri = 'web+stellar:tx?xdr=' . urlencode(self::TEST_XDR) . '&callback=url:https:';

        try {
            $uriScheme->signAndSubmitTransaction($uri, $signer, Network::testnet());
            $this->fail('Expected InvalidArgumentException for HTTPS callback URL without host');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('Service URL has no host.', $e->getMessage());
            $this->assertStringNotContainsString('https:', $e->getMessage());
        }

        $this->assertCount(0, $callbackHistory);
        $this->assertCount(0, $this->publicHistory);
        $this->assertCount(0, $this->testNetHistory);
        $this->assertCount(0, $this->futureNetHistory);
    }

    public function testSignAndSubmitTransactionSubmitsFeeBumpEnvelopeToPublicHorizon(): void
    {
        $this->installHorizonMocks();

        $innerV1Envelope = base64_decode(self::TEST_XDR, true);
        $this->assertIsString($innerV1Envelope);
        $signer = KeyPair::fromSeed(self::TEST_SECRET);

        // XDR TransactionEnvelope (ENVELOPE_TYPE_TX_FEE_BUMP = 5):
        // type(5) | feeSource(KEY_TYPE_ED25519=0 + 32-byte key) | fee(int64=400) | innerTx(V1 envelope) | ext(0) | signatures.len(0).
        // The inner transaction from TEST_XDR carries a text memo so SEP-29 makes no account lookup.
        $feeBumpPrefix = pack('N', 5)
            . pack('N', 0)
            . $signer->getPublicKey()
            . pack('J', 400)
            . $innerV1Envelope
            . pack('N', 0);
        $unsignedFeeBumpBase64 = base64_encode($feeBumpPrefix . pack('N', 0));

        $uriScheme = new URIScheme();
        $uri = $uriScheme->generateSignTransactionURI($unsignedFeeBumpBase64);

        $response = $uriScheme->signAndSubmitTransaction($uri, $signer, Network::public());

        $this->assertNull($response->getCallBackResponse());
        $this->assertNotNull($response->getSubmitTransactionResponse());
        $this->assertTrue($response->getSubmitTransactionResponse()->isSuccessful());

        $this->assertCount(1, $this->publicHistory);
        $this->assertCount(0, $this->testNetHistory);
        $this->assertCount(0, $this->futureNetHistory);

        $request = $this->publicHistory[0]['request'];
        $this->assertSame('POST', $request->getMethod());

        $requestUri = $request->getUri();
        $this->assertSame('https', $requestUri->getScheme());
        $this->assertSame('horizon.stellar.org', $requestUri->getHost());
        $this->assertSame('/transactions', $requestUri->getPath());

        parse_str($requestUri->getQuery(), $query);
        $this->assertIsString($query['tx'] ?? null);

        $publicPassphrase = 'Public Global Stellar Network ; September 2015';
        $feeBumpHash = hash(
            'sha256',
            hash('sha256', $publicPassphrase, true) . $feeBumpPrefix,
            true
        );
        $expectedSignedFeeBump = $feeBumpPrefix
            . pack('N', 1)
            . substr($signer->getPublicKey(), -4)
            . pack('N', 64)
            . $signer->sign($feeBumpHash);

        $this->assertSame(base64_encode($expectedSignedFeeBump), $query['tx']);
    }

    public function testGetParameterValueAndSignAndSubmitHandleMissingQueryAndUnparseableUri(): void
    {
        $uriScheme = new URIScheme();
        $signer = KeyPair::fromSeed(self::TEST_SECRET);
        $uris = [
            'web+stellar:tx',
            'http:///example.com',
            'web+stellar:tx?xdr[]=a',
        ];

        $warnings = [];
        set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = [$severity, $message];
            return true;
        });

        try {
            foreach ($uris as $uri) {
                $this->assertNull($uriScheme->getParameterValue('xdr', $uri));

                try {
                    $uriScheme->signAndSubmitTransaction($uri, $signer, Network::testnet());
                    $this->fail('Expected InvalidArgumentException for URI: ' . $uri);
                } catch (InvalidArgumentException $e) {
                    $this->assertStringContainsString('invalid url: ' . $uri, $e->getMessage());
                }
            }
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $warnings);
    }

    // ==================== checkUIRSchemeIsValid Tests ====================

    public function testCheckURISchemeIsValidMissingOriginDomain(): void
    {
        $uriScheme = new URIScheme();
        $uri = 'web+stellar:tx?xdr=' . self::TEST_XDR;

        try {
            $uriScheme->checkUIRSchemeIsValid($uri);
            $this->fail('Expected URISchemeError');
        } catch (URISchemeError $e) {
            $this->assertEquals(URISchemeError::missingOriginDomain, $e->getCode());
        }
    }

    public function testCheckURISchemeIsValidInvalidOriginDomain(): void
    {
        $uriScheme = new URIScheme();
        $uri = 'web+stellar:tx?xdr=' . self::TEST_XDR . '&origin_domain=invalid domain with spaces';

        try {
            $uriScheme->checkUIRSchemeIsValid($uri);
            $this->fail('Expected URISchemeError');
        } catch (URISchemeError $e) {
            $this->assertEquals(URISchemeError::invalidOriginDomain, $e->getCode());
        }
    }

    public function testCheckURISchemeIsValidMissingSignature(): void
    {
        $uriScheme = new URIScheme();
        $uri = 'web+stellar:tx?xdr=' . self::TEST_XDR . '&origin_domain=example.com';

        try {
            $uriScheme->checkUIRSchemeIsValid($uri);
            $this->fail('Expected URISchemeError');
        } catch (URISchemeError $e) {
            $this->assertEquals(URISchemeError::missingSignature, $e->getCode());
        }
    }

    public function testCheckURISchemeIsValidTomlNotFound(): void
    {
        $uriScheme = new URIScheme();

        // Mock HTTP client to return 404
        $mock = new MockHandler([
            new Response(404, [], 'Not Found')
        ]);
        $handlerStack = HandlerStack::create($mock);
        $uriScheme->setMockHandlerStack($handlerStack);

        $uri = 'web+stellar:tx?xdr=' . self::TEST_XDR . '&origin_domain=example.com&signature=YWJjZGVm';

        try {
            $uriScheme->checkUIRSchemeIsValid($uri);
            $this->fail('Expected URISchemeError');
        } catch (URISchemeError $e) {
            $this->assertEquals(URISchemeError::tomlNotFoundOrInvalid, $e->getCode());
        }
    }

    public function testCheckURISchemeIsValidTomlSignatureMissing(): void
    {
        $uriScheme = new URIScheme();

        // Mock HTTP client to return valid TOML without URI_REQUEST_SIGNING_KEY
        $tomlContent = "[DOCUMENTATION]\nORG_NAME = \"Example Org\"\n";
        $mock = new MockHandler([
            new Response(200, [], $tomlContent)
        ]);
        $handlerStack = HandlerStack::create($mock);
        $uriScheme->setMockHandlerStack($handlerStack);

        $uri = 'web+stellar:tx?xdr=' . self::TEST_XDR . '&origin_domain=example.com&signature=YWJjZGVm';

        try {
            $uriScheme->checkUIRSchemeIsValid($uri);
            $this->fail('Expected URISchemeError');
        } catch (URISchemeError $e) {
            $this->assertEquals(URISchemeError::tomlSignatureMissing, $e->getCode());
        }
    }

    public function testCheckURISchemeIsValidInvalidSignature(): void
    {
        $uriScheme = new URIScheme();

        // Mock HTTP client to return valid TOML with signing key
        $tomlContent = "URI_REQUEST_SIGNING_KEY = \"" . self::TEST_ACCOUNT . "\"\n";
        $mock = new MockHandler([
            new Response(200, [], $tomlContent)
        ]);
        $handlerStack = HandlerStack::create($mock);
        $uriScheme->setMockHandlerStack($handlerStack);

        $uri = 'web+stellar:tx?xdr=' . self::TEST_XDR . '&origin_domain=example.com&signature=InvalidSignature';

        try {
            $uriScheme->checkUIRSchemeIsValid($uri);
            $this->fail('Expected URISchemeError');
        } catch (URISchemeError $e) {
            $this->assertEquals(URISchemeError::invalidSignature, $e->getCode());
        }
    }

    public function testCheckURISchemeIsValidSuccess(): void
    {
        $uriScheme = new URIScheme();
        $keyPair = KeyPair::fromSeed(self::TEST_SECRET);

        // First, generate a properly signed URI
        $uri = $uriScheme->generateSignTransactionURI(
            self::TEST_XDR,
            originDomain: 'example.com'
        );
        $signedUri = $uriScheme->signURI($uri, $keyPair);

        // Mock HTTP client to return valid TOML with matching signing key
        $tomlContent = "URI_REQUEST_SIGNING_KEY = \"" . self::TEST_SIGNER_ACCOUNT . "\"\n";
        $mock = new MockHandler([
            new Response(200, [], $tomlContent)
        ]);
        $handlerStack = HandlerStack::create($mock);
        $uriScheme->setMockHandlerStack($handlerStack);

        $result = $uriScheme->checkUIRSchemeIsValid($signedUri);

        $this->assertTrue($result);
    }

    public function testCheckURISchemeIsValidWithSpecialCharacterSignature(): void
    {
        // The SEP-7 spec test vector produces a signature containing base64
        // '+', '/' and '==' characters, which must survive URL-encoding during
        // verification. The payload is reconstructed by stripping at the
        // "&signature=" marker, independent of the signature value's encoding.
        $uriScheme = new URIScheme();
        $signer = KeyPair::fromSeed('SBPOVRVKTTV7W3IOX2FJPSMPCJ5L2WU2YKTP3HCLYPXNI5MDIGREVNYC');
        $uri = 'web+stellar:pay?destination=GCALNQQBXAPZ2WIRSDDBMSTAKCUH5SG6U76YBFLQLIXJTF7FE5AX7AOO'
            . '&amount=120.1234567&memo=skdjfasf&memo_type=MEMO_TEXT'
            . '&msg=pay%20me%20with%20lumens&origin_domain=someDomain.com';
        $signedUri = $uriScheme->signURI($uri, $signer);
        // Sanity: the signature value is URL-encoded (contains %2F for '/').
        $this->assertStringContainsString('&signature=', $signedUri);
        $this->assertStringContainsString('%2F', $signedUri);

        $tomlContent = "URI_REQUEST_SIGNING_KEY = \"" . $signer->getAccountId() . "\"\n";
        $mock = new MockHandler([
            new Response(200, [], $tomlContent)
        ]);
        $uriScheme->setMockHandlerStack(HandlerStack::create($mock));

        $this->assertTrue($uriScheme->checkUIRSchemeIsValid($signedUri));
    }

    public function testCheckURISchemeRejectsTamperedUri(): void
    {
        $uriScheme = new URIScheme();
        $signer = KeyPair::fromSeed(self::TEST_SECRET);
        $uri = $uriScheme->generateSignTransactionURI(self::TEST_XDR, originDomain: 'example.com');
        $signedUri = $uriScheme->signURI($uri, $signer);

        // Tamper with the payload after signing: the signature must no longer verify.
        $tamperedUri = str_replace('origin_domain=example.com', 'origin_domain=evil.com', $signedUri);

        $tomlContent = "URI_REQUEST_SIGNING_KEY = \"" . self::TEST_SIGNER_ACCOUNT . "\"\n";
        $mock = new MockHandler([
            new Response(200, [], $tomlContent)
        ]);
        $uriScheme->setMockHandlerStack(HandlerStack::create($mock));

        $exception = false;
        try {
            $uriScheme->checkUIRSchemeIsValid($tamperedUri);
        } catch (URISchemeError $e) {
            $exception = ($e->getCode() === URISchemeError::invalidSignature);
        }
        $this->assertTrue($exception);
    }

    public function testCheckURISchemeRejectsInvalidBase64Signature(): void
    {
        $uriScheme = new URIScheme();
        // The signature value is not valid base64.
        $uri = $uriScheme->generateSignTransactionURI(
            self::TEST_XDR,
            originDomain: 'example.com',
            signature: '@@@invalid@@@'
        );

        $tomlContent = "URI_REQUEST_SIGNING_KEY = \"" . self::TEST_SIGNER_ACCOUNT . "\"\n";
        $mock = new MockHandler([
            new Response(200, [], $tomlContent)
        ]);
        $uriScheme->setMockHandlerStack(HandlerStack::create($mock));

        $exception = false;
        try {
            $uriScheme->checkUIRSchemeIsValid($uri);
        } catch (URISchemeError $e) {
            $exception = ($e->getCode() === URISchemeError::invalidSignature);
        }
        $this->assertTrue($exception);
    }

    public function testCheckURISchemeIsValidForSignedPayUri(): void
    {
        $uriScheme = new URIScheme();
        $signer = KeyPair::fromSeed(self::TEST_SECRET);
        $uri = $uriScheme->generatePayOperationURI(
            self::TEST_DESTINATION,
            amount: '10',
            originDomain: 'example.com'
        );
        $signedUri = $uriScheme->signURI($uri, $signer);

        $tomlContent = "URI_REQUEST_SIGNING_KEY = \"" . self::TEST_SIGNER_ACCOUNT . "\"\n";
        $mock = new MockHandler([
            new Response(200, [], $tomlContent)
        ]);
        $uriScheme->setMockHandlerStack(HandlerStack::create($mock));

        $this->assertTrue($uriScheme->checkUIRSchemeIsValid($signedUri));
    }

    // ==================== setMockHandlerStack Tests ====================

    public function testSetMockHandlerStack(): void
    {
        $uriScheme = new URIScheme();

        $mock = new MockHandler([
            new Response(200, [], 'Test response')
        ]);
        $handlerStack = HandlerStack::create($mock);

        // Should not throw
        $uriScheme->setMockHandlerStack($handlerStack);

        $this->assertInstanceOf(URIScheme::class, $uriScheme);
    }

    // ==================== URISchemeError Tests ====================

    public function testURISchemeErrorToString(): void
    {
        $invalidSignature = new URISchemeError(code: URISchemeError::invalidSignature);
        $this->assertEquals('URISchemeError: invalid Signature', $invalidSignature->toString());

        $invalidOriginDomain = new URISchemeError(code: URISchemeError::invalidOriginDomain);
        $this->assertEquals('URISchemeError: invalid Origin Domain', $invalidOriginDomain->toString());

        $missingOriginDomain = new URISchemeError(code: URISchemeError::missingOriginDomain);
        $this->assertEquals('URISchemeError: missing Origin Domain', $missingOriginDomain->toString());

        $missingSignature = new URISchemeError(code: URISchemeError::missingSignature);
        $this->assertEquals('URISchemeError: missing Signature', $missingSignature->toString());

        $tomlNotFound = new URISchemeError(code: URISchemeError::tomlNotFoundOrInvalid);
        $this->assertEquals('URISchemeError: toml not found or invalid', $tomlNotFound->toString());

        $tomlSignatureMissing = new URISchemeError(code: URISchemeError::tomlSignatureMissing);
        $this->assertEquals('URISchemeError: Toml Signature Missing', $tomlSignatureMissing->toString());
    }

    public function testURISchemeErrorToStringUnknown(): void
    {
        $unknownError = new URISchemeError(code: 999);
        $this->assertEquals('URISchemeError: unknown error', $unknownError->toString());
    }

    public function testURISchemeErrorConstants(): void
    {
        $this->assertEquals(0, URISchemeError::invalidSignature);
        $this->assertEquals(1, URISchemeError::invalidOriginDomain);
        $this->assertEquals(2, URISchemeError::missingOriginDomain);
        $this->assertEquals(3, URISchemeError::missingSignature);
        $this->assertEquals(4, URISchemeError::tomlNotFoundOrInvalid);
        $this->assertEquals(5, URISchemeError::tomlSignatureMissing);
    }

    // ==================== SubmitUriSchemeTransactionResponse Tests ====================

    public function testSubmitUriSchemeTransactionResponseWithSubmitResponse(): void
    {
        // Create mock SubmitTransactionResponse
        $mockSubmitResponse = $this->createMock(SubmitTransactionResponse::class);

        $response = new SubmitUriSchemeTransactionResponse($mockSubmitResponse, null);

        $this->assertSame($mockSubmitResponse, $response->getSubmitTransactionResponse());
        $this->assertNull($response->getCallBackResponse());
    }

    public function testSubmitUriSchemeTransactionResponseWithCallbackResponse(): void
    {
        // Create mock ResponseInterface
        $mockCallbackResponse = $this->createMock(ResponseInterface::class);

        $response = new SubmitUriSchemeTransactionResponse(null, $mockCallbackResponse);

        $this->assertNull($response->getSubmitTransactionResponse());
        $this->assertSame($mockCallbackResponse, $response->getCallBackResponse());
    }

    public function testSubmitUriSchemeTransactionResponseBothNull(): void
    {
        $response = new SubmitUriSchemeTransactionResponse(null, null);

        $this->assertNull($response->getSubmitTransactionResponse());
        $this->assertNull($response->getCallBackResponse());
    }

    // ==================== URI Scheme Constants Tests ====================

    public function testURISchemeConstants(): void
    {
        $this->assertEquals('web+stellar:', URIScheme::uriSchemeName);
        $this->assertEquals('tx?', URIScheme::signOperation);
        $this->assertEquals('pay?', URIScheme::payOperation);
        $this->assertEquals('xdr', URIScheme::xdrParameterName);
        $this->assertEquals('replace', URIScheme::replaceParameterName);
        $this->assertEquals('callback', URIScheme::callbackParameterName);
        $this->assertEquals('pubkey', URIScheme::publicKeyParameterName);
        $this->assertEquals('chain', URIScheme::chainParameterName);
        $this->assertEquals('msg', URIScheme::messageParameterName);
        $this->assertEquals('network_passphrase', URIScheme::networkPassphraseParameterName);
        $this->assertEquals('origin_domain', URIScheme::originDomainParameterName);
        $this->assertEquals('signature', URIScheme::signatureParameterName);
        $this->assertEquals('destination', URIScheme::destinationParameterName);
        $this->assertEquals('amount', URIScheme::amountParameterName);
        $this->assertEquals('asset_code', URIScheme::assetCodeParameterName);
        $this->assertEquals('asset_issuer', URIScheme::assetIssuerParameterName);
        $this->assertEquals('memo', URIScheme::memoParameterName);
        $this->assertEquals('memo_type', URIScheme::memoTypeParameterName);
        $this->assertEquals('stellar.sep.7 - URI Scheme', URIScheme::uriSchemePrefix);
    }
}
