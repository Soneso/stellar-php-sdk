<?php

namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\StellarSDK;

class TradeAggregationsTest extends TestCase
{
    public function testTradesAsset(): void
    {
        $baseAsset = Asset::createNonNativeAsset("SONESO","GAOF7ARG3ZAVUA63GCLXG5JQTMBAH3ZFYHGLGJLDXGDSXQRHD72LLGOB");
        $counterAsset = Asset::createNonNativeAsset("COOL", "GAZKB7OEYRUVL6TSBXI74D2IZS4JRCPBXJZ37MDDYAEYBOMHXUYIX5YL");
        $sdk = StellarSDK::getTestNetInstance();
        $requestBuilder = $sdk->tradeAggregations()->forBaseAsset($baseAsset)->forCounterAsset($counterAsset)->forResolution("60000")->order("desc");
        $response = $requestBuilder->execute();
        foreach ($response->getTradeAggregations() as $tradeAggregation) {
            $this->assertGreaterThan(0, strlen($tradeAggregation->getTimestamp()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getTradeCount()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getBaseVolume()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getCounterVolume()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getAveragePrice()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getHighPrice()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getHighPriceR()->getN()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getHighPriceR()->getD()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getLowPrice()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getLowPriceR()->getN()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getLowPriceR()->getD()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getOpenPrice()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getOpenPriceR()->getN()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getOpenPriceR()->getD()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getClosePrice()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getClosePriceR()->getN()));
            $this->assertGreaterThan(0, strlen($tradeAggregation->getClosePriceR()->getD()));
        }
    }

}