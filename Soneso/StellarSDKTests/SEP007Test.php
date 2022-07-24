<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\URIScheme\URIScheme;
use Soneso\StellarSDK\SEP\URIScheme\URISchemeError;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;

class SEP007Test extends TestCase
{
    private string $accountId =
      "GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV";
    private string $originDomainParam = "&origin_domain=place.domain.com";
    private string $secretSeed = "SBA2XQ5SRUW5H3FUQARMC6QYEPUYNSVCMM4PGESGVB2UIFHLM73TPXXF";
    private string $callbackParam = "&callback=url:https://examplepost.com";

    private function requestToml() : string {
        return '# Sample stellar.toml
    
        FEDERATION_SERVER="https://api.domain.com/federation"
        AUTH_SERVER="https://api.domain.com/auth"
        TRANSFER_SERVER="https://api.domain.com"
        URI_REQUEST_SIGNING_KEY="GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV"';
  }

    private function requestTomlSignatureMissing() : string {
        return '# Sample stellar.toml
    
        FEDERATION_SERVER="https://api.domain.com/federation"
        AUTH_SERVER="https://api.domain.com/auth"
        TRANSFER_SERVER="https://api.domain.com"';
    }

    private function requestTomlSignatureMissmatch() : string {
        return '# Sample stellar.toml

        FEDERATION_SERVER="https://api.domain.com/federation"
        AUTH_SERVER="https://api.domain.com/auth"
        TRANSFER_SERVER="https://api.domain.com"
        URI_REQUEST_SIGNING_KEY="GCCHBLJOZUFBVAUZP55N7ZU6ZB5VGEDHSXT23QC6UIVDQNGI6QDQTOOR"';
    }

    protected function setUp() : void {
        $sdk = StellarSDK::getTestNetInstance();
        try {
            $sdk->requestAccount($this->accountId);
        } catch (HorizonRequestException $e) {
            $this->assertTrue($e->getStatusCode() == 404);
            FriendBot::fundTestAccount($this->accountId);
        }
    }

    public function testGenerateSignTransactionUrl(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $sourceAccount = $sdk->requestAccount($this->accountId);
        $newHomeDomain = "www.soneso.com";

        $setOptionsOperation = (new SetOptionsOperationBuilder())
            ->setSourceAccount($this->accountId)
            ->setHomeDomain($newHomeDomain)
            ->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($setOptionsOperation)
            ->build();
        $uriScheme = new URIScheme();
        $url = $uriScheme->generateSignTransactionURI($transaction->toEnvelopeXdrBase64());
        self::assertTrue(str_starts_with($url, 'web+stellar:tx?xdr=AAAAAgAAAADNQvJCahsRijRFXMHgyGXdar95Wya9O'));
    }

    public function testGeneratePayOperationUrl(): void
    {
        $uriScheme = new URIScheme();
        $url = $uriScheme->generatePayOperationURI($this->accountId, amount:"123.21",
            assetCode: "ANA", assetIssuer: "GC4HC3AXQDNAMURMHVGMLFGLQELEQBCE4GI7IOKEAWAKBXY7SXXWBTLV");
        self::assertEquals("web+stellar:pay?destination=GDGUF4SCNINRDCRUIVOMDYGIMXOWVP3ZLMTL2OGQIWMFDDSECZSFQMQV&amount=123.21&asset_code=ANA&asset_issuer=GC4HC3AXQDNAMURMHVGMLFGLQELEQBCE4GI7IOKEAWAKBXY7SXXWBTLV",
            $url);
    }

    public function testMissingSignatureFromURIScheme() : void {
        $sdk = StellarSDK::getTestNetInstance();
        $sourceAccount = $sdk->requestAccount($this->accountId);
        $newHomeDomain = "www.soneso.com";

        $setOptionsOperation = (new SetOptionsOperationBuilder())
            ->setSourceAccount($this->accountId)
            ->setHomeDomain($newHomeDomain)
            ->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($setOptionsOperation)
            ->build();
        $uriScheme = new URIScheme();
        $url = $uriScheme->generateSignTransactionURI($transaction->toEnvelopeXdrBase64()) . $this->originDomainParam;
        try {
            $uriScheme->checkUIRSchemeIsValid($url);
            self::fail();
        } catch (URISchemeError $e) {
            self::assertEquals(URISchemeError::missingSignature, $e->getCode());
        }
    }

    public function testMissingDomainFromURIScheme() : void {
        $sdk = StellarSDK::getTestNetInstance();
        $sourceAccount = $sdk->requestAccount($this->accountId);
        $newHomeDomain = "www.soneso.com";

        $setOptionsOperation = (new SetOptionsOperationBuilder())
            ->setSourceAccount($this->accountId)
            ->setHomeDomain($newHomeDomain)
            ->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($setOptionsOperation)
            ->build();
        $uriScheme = new URIScheme();
        $url = $uriScheme->generateSignTransactionURI($transaction->toEnvelopeXdrBase64());

        try {
            $uriScheme->checkUIRSchemeIsValid($url);
            self::fail();
        } catch (URISchemeError $e) {
            self::assertEquals(URISchemeError::missingOriginDomain, $e->getCode());
        }
    }

    public function testGenerateSignedTxTestUrl() : void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $sourceAccount = $sdk->requestAccount($this->accountId);
        $newHomeDomain = "www.soneso.com";

        $setOptionsOperation = (new SetOptionsOperationBuilder())
            ->setSourceAccount($this->accountId)
            ->setHomeDomain($newHomeDomain)
            ->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($setOptionsOperation)
            ->build();
        $uriScheme = new URIScheme();
        $url = $uriScheme->generateSignTransactionURI($transaction->toEnvelopeXdrBase64()) . $this->originDomainParam;
        $signerKeyPair = KeyPair::fromSeed($this->secretSeed);
        $url = $uriScheme->signURI($url,$signerKeyPair);
        $signature = $uriScheme->getParameterValue(URIScheme::signatureParameterName, $url);
        self::assertNotNull($signature);
    }

    public function testValidateTestUrl() : void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $sourceAccount = $sdk->requestAccount($this->accountId);
        $newHomeDomain = "www.soneso.com";

        $setOptionsOperation = (new SetOptionsOperationBuilder())
            ->setSourceAccount($this->accountId)
            ->setHomeDomain($newHomeDomain)
            ->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($setOptionsOperation)
            ->build();
        $uriScheme = new URIScheme();
        $url = $uriScheme->generateSignTransactionURI($transaction->toEnvelopeXdrBase64()) . $this->originDomainParam;
        $signerKeyPair = KeyPair::fromSeed($this->secretSeed);
        $url = $uriScheme->signURI($url,$signerKeyPair);
        $signature = $uriScheme->getParameterValue(URIScheme::signatureParameterName, $url);
        self::assertNotNull($signature);

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestToml())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $request;
        }));

        $uriScheme->setMockHandlerStack($stack);
        $isValid = $uriScheme->checkUIRSchemeIsValid($url);
        self::assertTrue($isValid);
    }

    public function testSignAndSubmitTransaction() : void {
        $sdk = StellarSDK::getTestNetInstance();
        $sourceAccount = $sdk->requestAccount($this->accountId);
        $newHomeDomain = "www.soneso.com";

        $setOptionsOperation = (new SetOptionsOperationBuilder())
            ->setSourceAccount($this->accountId)
            ->setHomeDomain($newHomeDomain)
            ->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($setOptionsOperation)
            ->build();
        $uriScheme = new URIScheme();
        $url = $uriScheme->generateSignTransactionURI($transaction->toEnvelopeXdrBase64()) . $this->originDomainParam;
        $signerKeyPair = KeyPair::fromSeed($this->secretSeed);
        $url = $uriScheme->signURI($url,$signerKeyPair);
        $response = $uriScheme->signAndSubmitTransaction($url,$signerKeyPair,Network::testnet());
        self::assertNull($response->getCallBackResponse());
        self::assertNotNull($response->getSubmitTransactionResponse());
        self::assertTrue($response->getSubmitTransactionResponse()->isSuccessful());
    }

    public function testSignAndSubmitTransactionToCallback() : void {
        $sdk = StellarSDK::getTestNetInstance();
        $sourceAccount = $sdk->requestAccount($this->accountId);
        $newHomeDomain = "www.soneso.com";

        $setOptionsOperation = (new SetOptionsOperationBuilder())
            ->setSourceAccount($this->accountId)
            ->setHomeDomain($newHomeDomain)
            ->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($setOptionsOperation)
            ->build();
        $uriScheme = new URIScheme();
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], "")
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $request;
        }));

        $uriScheme->setMockHandlerStack($stack);
        $url = $uriScheme->generateSignTransactionURI($transaction->toEnvelopeXdrBase64()) . $this->originDomainParam . $this->callbackParam;
        $signerKeyPair = KeyPair::fromSeed($this->secretSeed);
        $url = $uriScheme->signURI($url,$signerKeyPair);
        $response = $uriScheme->signAndSubmitTransaction($url,$signerKeyPair,Network::testnet());
        self::assertNotNull($response->getCallBackResponse());
        self::assertEquals(200, $response->getCallBackResponse()->getStatusCode());
    }

    public function testTomlSignatureMissing() : void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $sourceAccount = $sdk->requestAccount($this->accountId);
        $newHomeDomain = "www.soneso.com";

        $setOptionsOperation = (new SetOptionsOperationBuilder())
            ->setSourceAccount($this->accountId)
            ->setHomeDomain($newHomeDomain)
            ->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($setOptionsOperation)
            ->build();
        $uriScheme = new URIScheme();
        $url = $uriScheme->generateSignTransactionURI($transaction->toEnvelopeXdrBase64()) . $this->originDomainParam;
        $signerKeyPair = KeyPair::fromSeed($this->secretSeed);
        $url = $uriScheme->signURI($url,$signerKeyPair);
        $signature = $uriScheme->getParameterValue(URIScheme::signatureParameterName, $url);
        self::assertNotNull($signature);

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestTomlSignatureMissing())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $request;
        }));

        $uriScheme->setMockHandlerStack($stack);
        try {
            $isValid = $uriScheme->checkUIRSchemeIsValid($url);
            self::fail();
        } catch (URISchemeError $e) {
            self::assertEquals(URISchemeError::tomlSignatureMissing, $e->getCode());
        }
    }

    public function testTomlSignatureMissmatch() : void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $sourceAccount = $sdk->requestAccount($this->accountId);
        $newHomeDomain = "www.soneso.com";

        $setOptionsOperation = (new SetOptionsOperationBuilder())
            ->setSourceAccount($this->accountId)
            ->setHomeDomain($newHomeDomain)
            ->build();

        $transaction = (new TransactionBuilder($sourceAccount))
            ->addOperation($setOptionsOperation)
            ->build();
        $uriScheme = new URIScheme();
        $url = $uriScheme->generateSignTransactionURI($transaction->toEnvelopeXdrBase64()) . $this->originDomainParam;
        $signerKeyPair = KeyPair::fromSeed($this->secretSeed);
        $url = $uriScheme->signURI($url,$signerKeyPair);
        $signature = $uriScheme->getParameterValue(URIScheme::signatureParameterName, $url);
        self::assertNotNull($signature);

        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], $this->requestTomlSignatureMissmatch())
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            return $request;
        }));

        $uriScheme->setMockHandlerStack($stack);
        try {
            $isValid = $uriScheme->checkUIRSchemeIsValid($url);
            self::fail();
        } catch (URISchemeError $e) {
            self::assertEquals(URISchemeError::invalidSignature, $e->getCode());
        }
    }
}