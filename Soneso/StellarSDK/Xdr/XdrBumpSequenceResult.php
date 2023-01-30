<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrBumpSequenceResult
{

    private XdrBumpSequenceResultCode $resultCode;

    /**
     * @return XdrBumpSequenceResultCode
     */
    public function getResultCode(): XdrBumpSequenceResultCode
    {
        return $this->resultCode;
    }

    /**
     * @param XdrBumpSequenceResultCode $resultCode
     */
    public function setResultCode(XdrBumpSequenceResultCode $resultCode): void
    {
        $this->resultCode = $resultCode;
    }

    public function encode(): string
    {
        return $this->resultCode->encode();
    }

    public static function decode(XdrBuffer $xdr):XdrBumpSequenceResult {
        $result = new XdrBumpSequenceResult();
        $resultCode = XdrBumpSequenceResultCode::decode($xdr);
        $result->resultCode = $resultCode;
        return $result;
    }
}