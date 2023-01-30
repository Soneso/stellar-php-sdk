<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrAccountMergeResult
{

    public XdrAccountMergeResultCode $resultCode;
    public ?BigInteger $sourceAccountBalance = null;

    /**
     * @return XdrAccountMergeResultCode
     */
    public function getResultCode(): XdrAccountMergeResultCode
    {
        return $this->resultCode;
    }

    /**
     * @param XdrAccountMergeResultCode $resultCode
     */
    public function setResultCode(XdrAccountMergeResultCode $resultCode): void
    {
        $this->resultCode = $resultCode;
    }

    public function encode(): string
    {
        $bytes = $this->resultCode->encode();
        if ($this->sourceAccountBalance != null) {
            $bytes .= XdrEncoder::bigInteger64($this->sourceAccountBalance);
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):XdrAccountMergeResult {
        $result = new XdrAccountMergeResult();
        $resultCode = XdrAccountMergeResultCode::decode($xdr);
        $result->resultCode = $resultCode;
        if ($result->resultCode->getValue() == XdrAccountMergeResultCode::SUCCESS) {
            $result->sourceAccountBalance = $xdr->readBigInteger64();
        }
        return $result;
    }
}