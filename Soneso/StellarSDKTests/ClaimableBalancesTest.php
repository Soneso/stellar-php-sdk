<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;

class ClaimableBalancesTest extends TestCase
{
    public function testExistingClaimableBalance(): void
    {
        $claimableBalanceId = "000000006c08443899e3e5d3a4c0c93881dc70c4a35c93a4d35bf8bbfd4dd57770b58365";
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->requestClaimableBalance($claimableBalanceId);
        $this->assertEquals($claimableBalanceId, $response->getBalanceId());
    }

    public function testQueryClaimableBalances(): void
    {
        $claimableBalanceId = "000000006c08443899e3e5d3a4c0c93881dc70c4a35c93a4d35bf8bbfd4dd57770b58365";
        $sdk = StellarSDK::getTestNetInstance();
        $requestBuilder = $sdk->claimableBalances()->forSponsor("GDCMAMQQEF762HKWOILEVRTMC36UXC32LXV3XK4QI4POYQ5SUWZAVFSR")->order("desc")->limit(1);
        $response = $requestBuilder->execute();
        foreach ($response->getClaimableBalances() as $claimableBalance) {
            $this->assertEquals($claimableBalanceId, $claimableBalance->getBalanceId());
        }
    }
}