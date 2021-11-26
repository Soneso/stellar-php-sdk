<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\StellarSDK;

class AssetsTest extends TestCase
{
    public function testQueryAssetByCode(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->assets()->forAssetCode("SONESO")->order("desc")->execute();
        foreach ($response->getAssets() as $asset) {
            $this->assertEquals("SONESO", $asset->getAssetCode());
        }
    }
}