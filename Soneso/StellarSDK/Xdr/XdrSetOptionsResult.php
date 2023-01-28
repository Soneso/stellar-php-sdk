<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrSetOptionsResult
{
    private XdrSetOptionsResultCode $resultCode;

    /**
     * @return XdrSetOptionsResultCode
     */
    public function getResultCode(): XdrSetOptionsResultCode
    {
        return $this->resultCode;
    }

    /**
     * @param XdrSetOptionsResultCode $resultCode
     */
    public function setResultCode(XdrSetOptionsResultCode $resultCode): void
    {
        $this->resultCode = $resultCode;
    }

    public function encode(): string
    {
        return $this->resultCode->encode();
    }

    public static function decode(XdrBuffer $xdr):XdrSetOptionsResult {
        $result = new XdrSetOptionsResult();
        $resultCode = XdrSetOptionsResultCode::decode($xdr);
        $result->resultCode = $resultCode;
        return $result;
    }
}