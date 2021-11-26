<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;

class EffectsTest extends TestCase
{

    public function testEffectsPage(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $requestBuilder = $sdk->effects()->forAccount("GAOF7ARG3ZAVUA63GCLXG5JQTMBAH3ZFYHGLGJLDXGDSXQRHD72LLGOB");
        $response = $requestBuilder->execute();
        foreach ($response->getEffects() as $effect) {
            $this->assertGreaterThan(0, strlen($effect->getEffectId()));
        }
    }
}