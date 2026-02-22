<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\URIScheme;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Soneso\StellarSDK\SEP\URIScheme\SubmitUriSchemeTransactionResponse;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;
use Soneso\StellarSDK\SEP\URIScheme\URISchemeError;
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
    private const TEST_XDR = 'AAAAAgAAAAA2YISI8AAPy2Fl+4Z5jvNHqRz9SClHHBH4vb/DhYrV5QAAAGQAAHGcAAAABAAAAAEAAAAAAAAAAAAAAABmgRXDAAAAAQAAABVIZWxsbywgU3RlbGxhciBXb3JsZCEAAAAAAAABAAAAAAAAAAEAAAAAbmgm1V2dg5V1mq1elMcG1txjSYKZ9wEgoSBaeW8UiFoAAAABVVNEQwAAAABzYWLsUZvT8QupERb1qVZfnpDqwMO7Q2f1k8PsYhVvs1oAAAAABfXhAAAAAAAAAAABhYrV5QAAAEBt3Dq8jIDQF0DKIA0pFLKQ26gOhPeLSZzxNQdIBcSxHKxGSm3zRaIp5Jl5+YlXjfUOqhPbBbjmhp3NjMqjhZ0D';

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
