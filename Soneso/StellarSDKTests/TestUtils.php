<?php  declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\AbstractTransaction;
use Soneso\StellarSDK\Responses\Transaction\SubmitTransactionResponse;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\Xdr\XdrEncoder;
use Soneso\StellarSDK\Xdr\XdrLedgerEntryChange;
use function PHPUnit\Framework\assertEquals;

class TestUtils
{

    public static function resultDeAndEncodingTest(TestCase $testCase, AbstractTransaction $transaction, SubmitTransactionResponse $response) {
        // check decoding & encoding
        $meta = $response->getResultMetaXdr();
        $testCase->assertEquals($response->getResultMetaXdrBase64(), $meta->toBase64Xdr());
        $envelopeBase64 = $response->getEnvelopeXdrBase64();
        $testCase->assertEquals($envelopeBase64, $transaction->toEnvelopeXdrBase64());
        $result = $response->getResultXdr();
        $testCase->assertEquals($response->getResultXdrBase64(), $result->toBase64Xdr());
        $feeMeta = $response->getFeeMetaXdrBase64();
        $feeMetaXdr = $response->getFeeMetaXdr();
        if ($feeMetaXdr != null) {
            $bytes = XdrEncoder::integer32(count($feeMetaXdr));
            foreach($feeMetaXdr as $val) {
                if ($val instanceof XdrLedgerEntryChange) {
                    $bytes .= $val->encode();
                }
            }
            assertEquals($feeMeta, base64_encode($bytes));
        }
    }
}