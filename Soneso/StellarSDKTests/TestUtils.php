<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Soneso\StellarSDK\Transaction;

class TestUtils
{

    public static function resultDeAndEncodingTest(TestCase $testCase, Transaction $transaction, SubmitTransactionResponse $response) {
        // check decoding & encoding
        //print($response->getMetaXdrBase64());
        $meta = $response->getMetaXdr();
        $testCase->assertEquals($response->getMetaXdrBase64(), $meta->toBase64Xdr());
        $envelopeBase64 = $response->getEnvelopeXdrBase64();
        $testCase->assertEquals($envelopeBase64, $transaction->toEnvelopeXdrBase64());
        $result = $response->getResultXdr();
        $testCase->assertEquals($response->getResultXdrBase64(), $result->toBase64Xdr());
    }
}