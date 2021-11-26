<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\StellarSDK;

class TransactionsTest extends TestCase
{
    public function testExistingTransaction(): void
    {
        $transactionId = "7d57e01e174edd06bd33bbc76f39fb5bbffa1d550e743011754015a404560086";
        $sdk = StellarSDK::getTestNetInstance();
        $response = $sdk->requestTransaction($transactionId);
        $this->assertEquals($transactionId, $response->getId());

    }

    public function testQueryTransactions(): void
    {
        $sdk = StellarSDK::getTestNetInstance();
        $requestBuilder = $sdk->transactions()->order("desc")->limit(1);
        $response = $requestBuilder->execute();
        foreach ($response->getTransactions() as $transaction) {
            $this->assertEquals(1, $transaction->getOperationCount());
        }
    }
}