<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\CreateAccountOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\ManageBuyOfferOperationBuilder;
use Soneso\StellarSDK\ManageSellOfferOperationBuilder;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDKTests\TestUtils;

class TradesTest extends TestCase
{
    public function testManageBuyOffer() {
        $sdk = StellarSDK::getTestNetInstance();
        $issuerKeypair = KeyPair::random();
        $buyerKeypair = KeyPair::random();

        $issuerId = $issuerKeypair->getAccountId();
        $buyerId = $buyerKeypair->getAccountId();

        FriendBot::fundTestAccount($buyerId);

        $buyerAccount = $sdk->requestAccount($buyerId);
        $createAccountOp = (new CreateAccountOperationBuilder($issuerId, "10"))->build();
        $transaction = (new TransactionBuilder($buyerAccount))->addOperation($createAccountOp)->build();
        $transaction->sign($buyerKeypair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $assetCode = "ASTRO";
        $astroDollar = Asset::createNonNativeAsset($assetCode, $issuerId);
        $changeTrustOperation = (new ChangeTrustOperationBuilder($astroDollar, "10000"))->build();
        $transaction = (new TransactionBuilder($buyerAccount))->addOperation($changeTrustOperation)->build();
        $transaction->sign($buyerKeypair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $amountBuying = "100";
        $price = "0.5";

        $manageBuyOffer = (new ManageBuyOfferOperationBuilder(Asset::native(), $astroDollar, $amountBuying, $price))->build();
        $transaction = (new TransactionBuilder($buyerAccount))->addOperation($manageBuyOffer)->build();
        $transaction->sign($buyerKeypair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $offers = $sdk->offers()->forAccount($buyerId)->execute()->getOffers();
        $this->assertTrue($offers->count() == 1);
        $offerId = "";
        foreach ($offers as $offer) {
            $this->assertTrue($offer->getBuying()->getType() == Asset::TYPE_CREDIT_ALPHANUM_12);
            $offerAmount = floatval($offer->getAmount());
            $offerPrice = floatval($offer->getPrice());
            $this->assertTrue(floatval($amountBuying) == $offerAmount * $offerPrice);
            $this->assertTrue($offer->getSeller() == $buyerId);
            $offerId = $offer->getOfferId();
            break;
        }
        $offerId2 = "";
        $offers = $sdk->offers()->forBuyingAsset($astroDollar)->execute()->getOffers();
        foreach ($offers as $offer) {
            $offerId2 = $offer->getOfferId();
            break;
        }
        $this->assertNotEquals("", $offerId);
        $this->assertEquals($offerId, $offerId2);

        $orderBook = $sdk->orderBook()->forBuyingAsset(Asset::native())->forSellingAsset($astroDollar)->limit(1)->execute();
        $offerAmount = $orderBook->getBids()->toArray()[0]->getAmount();
        $offerPrice= $orderBook->getBids()->toArray()[0]->getPrice();
        $this->assertTrue($offerAmount * $offerPrice == 25);

        // update offer
        $amountBuying = "150";
        $price = "0.3";
        $manageBuyOffer = (new ManageBuyOfferOperationBuilder(Asset::native(), $astroDollar, $amountBuying, $price))
            ->setOfferId(intval($offerId))->build();
        $transaction = (new TransactionBuilder($buyerAccount))->addOperation($manageBuyOffer)->build();
        $transaction->sign($buyerKeypair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $offers = $sdk->offers()->forAccount($buyerId)->execute()->getOffers();
        $this->assertTrue($offers->count() == 1);
        $this->assertTrue($response->isSuccessful());
        $offer = $offers->toArray()[0];
        $this->assertTrue($offer->getBuying()->getType() == Asset::TYPE_CREDIT_ALPHANUM_12);
        $this->assertTrue($offer->getSelling()->getType() == Asset::TYPE_NATIVE);
        $offerAmount = floatval($offer->getAmount());
        $offerPrice = floatval($offer->getPrice());

        $this->assertTrue($amountBuying == strval(round($offerAmount * $offerPrice, 0, PHP_ROUND_HALF_EVEN)));
        $this->assertTrue($offer->getSeller() == $buyerId);

        $orderBook = $sdk->orderBook()->forBuyingAsset($astroDollar)->forSellingAsset(Asset::native())->limit(1)->execute();
        $offerAmount = $orderBook->getAsks()->toArray()[0]->getAmount();
        $offerPrice = $orderBook->getAsks()->toArray()[0]->getPrice();
        $this->assertTrue($amountBuying == strval(round($offerAmount * $offerPrice, 0, PHP_ROUND_HALF_EVEN)));

        // delete offer
        $amountBuying = "0";
        $manageBuyOffer = (new ManageBuyOfferOperationBuilder(Asset::native(), $astroDollar, $amountBuying, $price))
            ->setOfferId(intval($offerId))->build();
        $transaction = (new TransactionBuilder($buyerAccount))->addOperation($manageBuyOffer)->build();
        $transaction->sign($buyerKeypair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $offers = $sdk->offers()->forAccount($buyerId)->execute()->getOffers();
        $this->assertTrue($offers->count() == 0);
        $orderBook = $sdk->orderBook()->forBuyingAsset($astroDollar)->forSellingAsset(Asset::native())->limit(1)->execute();
        $this->assertTrue($orderBook->getBids()->count() == 0);
        $this->assertTrue($orderBook->getAsks()->count() == 0);
    }

    public function testManageSellOffer() {
        $sdk = StellarSDK::getTestNetInstance();
        $issuerKeypair = KeyPair::random();
        $sellerKeypair = KeyPair::random();

        $issuerId = $issuerKeypair->getAccountId();
        $sellerId = $sellerKeypair->getAccountId();

        FriendBot::fundTestAccount($sellerId);

        $sellerAccount = $sdk->requestAccount($sellerId);
        $createAccountOp = (new CreateAccountOperationBuilder($issuerId, "10"))->build();
        $transaction = (new TransactionBuilder($sellerAccount))->addOperation($createAccountOp)->build();
        $transaction->sign($sellerKeypair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $assetCode = "MOON";
        $moonDollar = Asset::createNonNativeAsset($assetCode, $issuerId);
        $changeTrustOperation = (new ChangeTrustOperationBuilder($moonDollar, "10000"))->build();
        $transaction = (new TransactionBuilder($sellerAccount))->addOperation($changeTrustOperation)->build();
        $transaction->sign($sellerKeypair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $issuerAccount = $sdk->requestAccount($issuerId);
        $paymentOperation = (new PaymentOperationBuilder($sellerId, $moonDollar,"10000"))->build();
        $transaction = (new TransactionBuilder($issuerAccount))->addOperation($paymentOperation)->build();
        $transaction->sign($issuerKeypair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $amountSelling = "100";
        $price = "0.5";

        $manageSellOffer = (new ManageSellOfferOperationBuilder($moonDollar, Asset::native(), $amountSelling, $price))->build();
        $transaction = (new TransactionBuilder($sellerAccount))->addOperation($manageSellOffer)->build();
        $transaction->sign($sellerKeypair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $offers = $sdk->offers()->forAccount($sellerId)->execute()->getOffers();
        $this->assertTrue($offers->count() == 1);
        $offerId = "";
        foreach ($offers as $offer) {
            $this->assertTrue($offer->getBuying()->getType() == Asset::TYPE_NATIVE);
            $offerAmount = floatval($offer->getAmount());
            $offerPrice = floatval($offer->getPrice());
            $this->assertTrue($offerAmount == $amountSelling);
            $this->assertTrue($offerPrice == $price);
            $this->assertTrue($offer->getSeller() == $sellerId);
            $offerId = $offer->getOfferId();
            break;
        }
        $offers = $sdk->offers()->forSellingAsset($moonDollar)->execute()->getOffers();
        $offerId2 = "";
        foreach ($offers as $offer) {
            $offerId2 = $offer->getOfferId();
            break;
        }
        $this->assertNotEquals("", $offerId);
        $this->assertEquals($offerId, $offerId2);

        $orderBook = $sdk->orderBook()->forBuyingAsset(Asset::native())->forSellingAsset($moonDollar)->limit(1)->execute();
        $offerAmount = $orderBook->getAsks()->toArray()[0]->getAmount();
        $offerPrice= $orderBook->getAsks()->toArray()[0]->getPrice();
        $this->assertTrue($offerAmount == $amountSelling);
        $this->assertTrue($offerPrice == $price);


        // update offer
        $amountSelling = "150";
        $price = "0.3";
        $manageSellOffer = (new ManageSellOfferOperationBuilder($moonDollar, Asset::native(), $amountSelling, $price))
            ->setOfferId(intval($offerId))->build();
        $transaction = (new TransactionBuilder($sellerAccount))->addOperation($manageSellOffer)->build();
        $transaction->sign($sellerKeypair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $offers = $sdk->offers()->forAccount($sellerId)->execute()->getOffers();
        $this->assertTrue($offers->count() == 1);
        $offer = $offers->toArray()[0];
        $this->assertTrue($offer->getSelling()->getType() == Asset::TYPE_CREDIT_ALPHANUM_4);
        $this->assertTrue($offer->getBuying()->getType() == Asset::TYPE_NATIVE);
        $offerAmount = floatval($offer->getAmount());
        $offerPrice = floatval($offer->getPrice());
        $this->assertTrue($offerAmount == $amountSelling);
        $this->assertTrue($offerPrice == $price);
        $this->assertTrue($offer->getSeller() == $sellerId);

        // delete offer
        $amountSelling = "0";
        $manageBuyOffer = (new ManageSellOfferOperationBuilder($moonDollar, Asset::native(), $amountSelling, $price))
            ->setOfferId(intval($offerId))->build();
        $transaction = (new TransactionBuilder($sellerAccount))->addOperation($manageBuyOffer)->build();
        $transaction->sign($sellerKeypair, Network::testnet());
        $response = $sdk->submitTransaction($transaction);
        $this->assertTrue($response->isSuccessful());
        TestUtils::resultDeAndEncodingTest($this, $transaction, $response);

        $offers = $sdk->offers()->forAccount($sellerId)->execute()->getOffers();
        $this->assertTrue($offers->count() == 0);
    }

    public function testIssue2() {
        $sdk = StellarSDK::getPublicNetInstance();
        $sellingAsset = Asset::createNonNativeAsset('yXLM', 'GARDNV3Q7YGT4AKSDF25LT32YSCCW4EV22Y2TV3I2PU2MMXJTEDL5T55');
        $orderBook = $sdk->orderBook()->forBuyingAsset(Asset::native())->forSellingAsset($sellingAsset)->limit(1)->execute();
        $offerAmount = $orderBook->getBids()->toArray()[0]->getAmount();
        $offerPrice = $orderBook->getBids()->toArray()[0]->getPrice();
        $this->assertTrue($offerAmount > 0);
        $this->assertTrue($offerPrice > 0);
    }
}