<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\Responses\Operations\CreateAccountOperationResponse;
use Soneso\StellarSDK\Responses\Operations\CreatePassiveSellOfferResponse;
use Soneso\StellarSDK\Responses\Operations\ManageBuyOfferOperationResponse;
use Soneso\StellarSDK\Responses\Operations\ManageSellOfferOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PathPaymentStrictReceiveOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PathPaymentStrictSendOperationResponse;
use Soneso\StellarSDK\Responses\Operations\PaymentOperationResponse;
use Soneso\StellarSDK\StellarSDK;

class OperationsTest extends TestCase
{
    public function testExistingCreateAccountOperations(): void
    {
        $id = "2480180404699137";
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->requestOperation($id);
        $this->assertEquals($id, $response->getOperationId());
        $this->assertGreaterThan(0, strlen($response->getPagingToken()));
        $this->assertTrue($response->isTransactionSuccessful());
        $this->assertGreaterThan(0, strlen($response->getSourceAccount()));
        $this->assertEquals(0, $response->getOperationType());
        $this->assertGreaterThan(0, strlen($response->getHumanReadableOperationType()));
        $this->assertGreaterThan(0, strlen($response->getTransactionHash()));
        $this->assertGreaterThan(0, strlen($response->getCreatedAt()));
        $this->assertTrue($response instanceof CreateAccountOperationResponse);
        if ($response instanceof CreateAccountOperationResponse) {
            $this->assertGreaterThan(0, strlen($response->getStartingBalance()));
            $this->assertGreaterThan(0, strlen($response->getFunder()));
            $this->assertGreaterThan(0, strlen($response->getAccount()));
            $this->assertGreaterThan(0, strlen($response->getFunderMuxed()));
            $this->assertGreaterThan(0, strlen($response->getFunderMuxedId()));
        }
    }

    public function testExistingPaymentOperations(): void
    {
        $id = "2480906254163969";
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->requestOperation($id);
        $this->assertTrue($response instanceof PaymentOperationResponse);
        if ($response instanceof PaymentOperationResponse) {
            $this->assertGreaterThan(0, strlen($response->getAmount()));
            $this->assertEquals(Asset::TYPE_NATIVE, $response->getAsset()->getType());
            $this->assertGreaterThan(0, strlen($response->getFrom()));
            $this->assertGreaterThan(0, strlen($response->getFromMuxed()));
            $this->assertGreaterThan(0, strlen($response->getFromMuxedId()));
            $this->assertGreaterThan(0, strlen($response->getTo()));
        }

        $id = "2482448147439617";
        $response = $sdk->requestOperation($id);
        $this->assertTrue($response instanceof PathPaymentStrictReceiveOperationResponse);
        if ($response instanceof PathPaymentStrictReceiveOperationResponse) {
            $this->assertGreaterThan(0, strlen($response->getSourceMax()));
            $this->assertGreaterThan(0, strlen($response->getAmount()));
            $this->assertGreaterThan(0, strlen($response->getSourceAmount()));
            $this->assertEquals(Asset::TYPE_CREDIT_ALPHANUM_4, $response->getAsset()->getType());
            $this->assertEquals(Asset::TYPE_CREDIT_ALPHANUM_4, $response->getSourceAsset()->getType());
            $this->assertGreaterThan(0, strlen($response->getFrom()));
            $this->assertGreaterThan(0, strlen($response->getTo()));
            $this->assertGreaterThan(0, $response->getPath()->count());
            foreach ($response->getPath() as $pass) {
                $this->assertEquals(Asset::TYPE_CREDIT_ALPHANUM_4, $pass->getType());
            }

        }

        $id = "2482370838016001";
        $response = $sdk->requestOperation($id);
        $this->assertTrue($response instanceof PathPaymentStrictSendOperationResponse);
        if ($response instanceof PathPaymentStrictSendOperationResponse) {
            $this->assertGreaterThan(0, strlen($response->getDestinationMin()));
        }
    }
    public function testExistingManageOfferOperations(): void
    {
        $id = "2409330624180225";
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->requestOperation($id);
        $this->assertTrue($response instanceof ManageSellOfferOperationResponse);
        if ($response instanceof ManageSellOfferOperationResponse) {
            $this->assertGreaterThan(0, strlen($response->getOfferId()));
            $this->assertGreaterThan(0, strlen($response->getAmount()));
            $this->assertGreaterThan(0, strlen($response->getPrice()));
            $this->assertNotNull($response->getSellingAsset());
            $this->assertNotNull($response->getBuyingAsset());
            $this->assertGreaterThan(0, $response->getPriceR()->getN());
            $this->assertGreaterThan(0, $response->getPriceR()->getD());
        }

        $id = "2499520642437121";
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->requestOperation($id);
        $this->assertTrue($response instanceof ManageBuyOfferOperationResponse);
        if ($response instanceof ManageBuyOfferOperationResponse) {
            $this->assertGreaterThan(0, strlen($response->getOfferId()));
            $this->assertGreaterThan(0, strlen($response->getAmount()));
            $this->assertGreaterThan(0, strlen($response->getPrice()));
            $this->assertNotNull($response->getSellingAsset());
            $this->assertNotNull($response->getBuyingAsset());
            $this->assertGreaterThan(0, $response->getPriceR()->getN());
            $this->assertGreaterThan(0, $response->getPriceR()->getD());
        }
    }

    public function testExistingCreatePassiveSellOfferOperations(): void
    {
        $id = "2561398236258305";
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->requestOperation($id);
        $this->assertTrue($response instanceof CreatePassiveSellOfferResponse);
        if ($response instanceof CreatePassiveSellOfferResponse) {
            $this->assertGreaterThan(0, strlen($response->getAmount()));
            $this->assertGreaterThan(0, strlen($response->getPrice()));
            $this->assertNotNull($response->getSellingAsset());
            $this->assertNotNull($response->getBuyingAsset());
            $this->assertGreaterThan(0, $response->getPriceR()->getN());
            $this->assertGreaterThan(0, $response->getPriceR()->getD());
        }
    }

    public function testOperationsPage(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $requestBuilder = $sdk->operations()->forAccount("GBIGEUGEIBSNLHTYC323SSY2K2ZSDXARVOAYGHOALRUNUYVUQT2MQU3X");
        $response = $requestBuilder->execute();
        foreach ($response->getOperations() as $operation) {
            $this->assertGreaterThan(0, strlen($operation->getOperationId()));
        }
    }
}