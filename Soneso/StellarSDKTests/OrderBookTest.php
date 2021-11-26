<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeNative;
use Soneso\StellarSDK\StellarSDK;

class OrderBookTest extends TestCase
{

    public function testQueryOrderBook(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $sellingAsset = new AssetTypeNative();
        $buyingAsset = Asset::createNonNativeAsset("TK1", "GDSQO2WU67JTHA7G3GEV6URXIDEOQNCSL36PGFAWRIVMFX62EPJBYAM4");
        $requestBuilder = $sdk->orderBook()->forSellingAsset($sellingAsset)->forBuyingAsset($buyingAsset);
        $response = $requestBuilder->execute();
        $this->assertEquals(Asset::TYPE_NATIVE, $response->getBase()->getType());
        $this->assertEquals(Asset::TYPE_CREDIT_ALPHANUM_4, $response->getCounter()->getType());
        foreach($response->getAsks() as $ask) {
            $this->assertEquals("0.5500000", $ask->getPrice());
            $this->assertEquals(11, $ask->getPriceR()->getN());
            $this->assertEquals(20, $ask->getPriceR()->getD());
            $this->assertEquals("1.8181819", $ask->getAmount());
        }
    }
}