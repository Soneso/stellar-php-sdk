<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrAccountMergeResult
{

    private XdrAccountMergeResultCode $resultCode;

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

    public static function decode(XdrBuffer $xdr):XdrAccountMergeResult {
        $result = new XdrAccountMergeResult();
        $resultCode = XdrAccountMergeResultCode::decode($xdr);
        $result->resultCode = $resultCode;
        return $result;
    }
}