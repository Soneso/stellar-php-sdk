<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.
namespace Soneso\StellarSDK\Xdr;

class XdrManageOfferResult
{
    private XdrManageOfferResultCode $code;
    private ?XdrManageOfferSuccessResult $success = null;

    public function __construct(XdrManageOfferResultCode $code, XdrManageOfferSuccessResult $success = null) {
        $this->code = $code;
        $this->success = $success;
    }

    /**
     * @return XdrManageOfferResultCode
     */
    public function getCode(): XdrManageOfferResultCode
    {
        return $this->code;
    }

    /**
     * @return XdrManageOfferSuccessResult|null
     */
    public function getSuccess(): ?XdrManageOfferSuccessResult
    {
        return $this->success;
    }

    public function encode() : string {
        $bytes = $this->code->encode();
        if ($this->success != null && XdrManageOfferResultCode::SUCCESS == $this->code->getValue()) {
            $bytes .= $this->success->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrManageOfferResult {
        $code = XdrManageOfferResultCode::decode($xdr);
        $success = null;
        if (XdrManageOfferResultCode::SUCCESS == $code->getValue()) {
            $success = XdrManageOfferSuccessResult::decode($xdr);
        }
        return new XdrManageOfferResult($code, $success);
    }
}