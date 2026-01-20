<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Integration;

use PHPUnit\Framework\TestCase;
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
}
