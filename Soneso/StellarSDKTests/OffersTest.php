<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;

class OffersTest extends TestCase
{
    public function testExistingOffer(): void
    {
        $id = "367169";
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->requestOffer($id);
        $this->assertEquals($id, $response->getOfferId());

    }

    public function testQueryOffers(): void
    {
        $id = "367169";
        $sdk = StellarSDK::getTestNetInstance();
        $requestBuilder = $sdk->offers()->forSeller("GCTO2UY65VCHJYCH7WBDYA7Z52PD54SL4KWB7THUUVCINTFLTYHJ2NLP")->order("desc")->limit(1);
        $response = $requestBuilder->execute();
        foreach ($response->getOffers() as $offer) {
            $this->assertEquals($id, $offer->getOfferId());
        }
    }
}