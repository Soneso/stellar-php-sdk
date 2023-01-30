<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrRevokeSponsorshipResult
{
    private XdrRevokeSponsorshipResultCode $resultCode;

    public function __construct(XdrRevokeSponsorshipResultCode $resultCode) {
        $this->resultCode = $resultCode;
    }

    /**
     * @return XdrRevokeSponsorshipResultCode
     */
    public function getResultCode(): XdrRevokeSponsorshipResultCode
    {
        return $this->resultCode;
    }

    /**
     * @param XdrRevokeSponsorshipResultCode $resultCode
     */
    public function setResultCode(XdrRevokeSponsorshipResultCode $resultCode): void
    {
        $this->resultCode = $resultCode;
    }

    public function encode(): string
    {
        return $this->resultCode->encode();
    }

    public static function decode(XdrBuffer $xdr):XdrRevokeSponsorshipResult {
        $resultCode = XdrRevokeSponsorshipResultCode::decode($xdr);
        return new XdrRevokeSponsorshipResult($resultCode);
    }
}