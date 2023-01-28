<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrClawbackResult
{
    private XdrClawbackResultCode $resultCode;

    public function __construct(XdrClawbackResultCode $resultCode) {
        $this->resultCode = $resultCode;
    }

    /**
     * @return XdrClawbackResultCode
     */
    public function getResultCode(): XdrClawbackResultCode
    {
        return $this->resultCode;
    }

    public function encode(): string
    {
        return $this->resultCode->encode();
    }

    public static function decode(XdrBuffer $xdr):XdrClawbackResult {
        $resultCode = XdrClawbackResultCode::decode($xdr);
        return new XdrClawbackResult($resultCode);
    }
}