<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrInflationResult
{
    private XdrInflationResultCode $code;
    private ?array $payouts = null; // [XdrInflationPayout]

    public function __construct(XdrInflationResultCode $code, ?array $payouts = null) {
        $this->code = $code;
        $this->payouts = $payouts;
    }

    /**
     * @return XdrInflationResultCode
     */
    public function getCode(): XdrInflationResultCode
    {
        return $this->code;
    }

    /**
     * @return array|null
     */
    public function getPayouts(): ?array
    {
        return $this->payouts;
    }


    public static function decode(XdrBuffer $xdr) : XdrInflationResult {
        $code = XdrInflationResultCode::decode($xdr);
        if (XdrInflationResultCode::SUCCESS == $code->getValue()) {
            $count = $xdr->readInteger32();
            $payouts = array();
            for ($i = 0; $i < $count; $i++) {
                array_push($payouts, XdrInflationPayout::decode($xdr));
            }
            return new XdrInflationResult($code, $payouts);
        }
        return new XdrInflationResult($code);
    }
}