<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\StellarSDK;

class TradesTest extends TestCase
{
    public function testRequestAllTrades(): void
    {
        $offerId = "4613737015635091458";
        $sdk = StellarSDK::getTestNetInstance();
        $requestBuilder = $sdk->trades()->forOffer($offerId)->order("desc");
        $response = $requestBuilder->execute();
        foreach ($response->getTrades() as $trade) {
            $this->assertEquals($offerId, $trade->getBaseOfferId());
        }
    }

    public function testTradesForLiquidityPool(): void
    {
        $liquidityPoolId = "2c0bfa623845dd101cbf074a1ca1ae4b2458cc8d0104ad65939ebe2cd9054355";
        $sdk = StellarSDK::getTestNetInstance();
        $requestBuilder = $sdk->trades()->forLiquidityPool($liquidityPoolId)->order("desc");
        $response = $requestBuilder->execute();
        foreach ($response->getTrades() as $trade) {
            $this->assertEquals($liquidityPoolId, $trade->getCounterLiquidityPoolId());
        }
    }

    public function testTradesAsset(): void
    {

        $liquidityPoolId = "2c0bfa623845dd101cbf074a1ca1ae4b2458cc8d0104ad65939ebe2cd9054355";
        $baseAsset = Asset::createNonNativeAsset("SONESO","GAOF7ARG3ZAVUA63GCLXG5JQTMBAH3ZFYHGLGJLDXGDSXQRHD72LLGOB");
        $counterAsset = Asset::createNonNativeAsset("COOL", "GAZKB7OEYRUVL6TSBXI74D2IZS4JRCPBXJZ37MDDYAEYBOMHXUYIX5YL");
        $sdk = StellarSDK::getTestNetInstance();
        $requestBuilder = $sdk->trades()->forBaseAsset($baseAsset)->forCounterAsset($counterAsset)->order("desc");
        $response = $requestBuilder->execute();
        foreach ($response->getTrades() as $trade) {
            $this->assertEquals($liquidityPoolId, $trade->getCounterLiquidityPoolId());
        }
    }

}