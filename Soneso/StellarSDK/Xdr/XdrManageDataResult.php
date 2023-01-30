<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrManageDataResult
{
    private XdrManageDataResultCode $code;

    public function __construct(XdrManageDataResultCode $code) {
        $this->code = $code;
    }

    /**
     * @return XdrManageDataResultCode
     */
    public function getCode(): XdrManageDataResultCode
    {
        return $this->code;
    }

    public function encode(): string
    {
        return $this->code->encode();
    }

    public static function decode(XdrBuffer $xdr) : XdrManageDataResult
    {
        $code = XdrManageDataResultCode::decode($xdr);
        return new XdrManageDataResult($code);
    }
}