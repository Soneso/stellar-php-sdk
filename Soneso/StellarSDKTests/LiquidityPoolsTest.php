<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\StellarSDK;

class LiquidityPoolsTest extends TestCase
{
    public function testExistingLiquidityPool(): void
    {
        $id = "02a147d2a9b4a72d718b43e78f45d230196c59443cf01aa355bfb645e449e45a";
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->requestLiquidityPool($id);
        $this->assertEquals($id, $response->getPoolId());

    }

    public function testQueryPools(): void
    {
        $id = "02a147d2a9b4a72d718b43e78f45d230196c59443cf01aa355bfb645e449e45a";
        $sdk = StellarSDK::getTestNetInstance();
        $requestBuilder = $sdk->liquidityPools()->forReserves("eINR:GANDIZO7TWMXNWQQRMF5EYV2L4M6V3NUFXQYRDIQXWIJ7OWWS4TONAXN","eSGD:GDJFEYZXMJQKFWU3HSTIAQCJEUVG4VD4FBA73CKKOLZQWEXGVCLSFRCT")->order("desc")->limit(1);
        $response = $requestBuilder->execute();
        foreach ($response->getLiquidityPools() as $value) {
            $this->assertEquals($id, $value->getPoolId());
            $this->assertGreaterThan(0, strlen($value->getPagingToken()));
            $this->assertGreaterThan(0, $value->getFee());
            $this->assertGreaterThan(0, strlen($value->getType()));
            $this->assertGreaterThan(0, strlen($value->getTotalShares()));
            foreach ($value->getReserves() as $reserve) {
                $this->assertGreaterThan(0, strlen($reserve->getAmount()));
                $this->assertEquals(Asset::TYPE_CREDIT_ALPHANUM_4, $reserve->getAsset()->getType());
            }
            $this->assertGreaterThan(0, strlen($value->getLastModifiedTime()));
            $this->assertGreaterThan(0, $value->getLastModifiedLedger());
        }
    }
}