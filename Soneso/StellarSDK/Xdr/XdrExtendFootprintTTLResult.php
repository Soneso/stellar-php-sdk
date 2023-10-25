<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrExtendFootprintTTLResult
{

    public XdrExtendFootprintTTLResultCode $code;

    /**
     * @param XdrExtendFootprintTTLResultCode $code
     */
    public function __construct(XdrExtendFootprintTTLResultCode $code)
    {
        $this->code = $code;
    }


    public function encode(): string {
        $bytes = $this->code->encode();

        switch ($this->code->value) {
            case XdrExtendFootprintTTLResultCode::EXTEND_FOOTPRINT_TTL_SUCCESS:
            case XdrExtendFootprintTTLResultCode::EXTEND_FOOTPRINT_TTL_MALFORMED:
            case XdrExtendFootprintTTLResultCode::EXTEND_FOOTPRINT_TTL_RESOURCE_LIMIT_EXCEEDED:
            case XdrExtendFootprintTTLResultCode::EXTEND_FOOTPRINT_TTL_INSUFFICIENT_REFUNDABLE_FEE:
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrExtendFootprintTTLResult {
        $result = new XdrExtendFootprintTTLResult(XdrExtendFootprintTTLResultCode::decode($xdr));
        switch ($result->code->value) {
            case XdrExtendFootprintTTLResultCode::EXTEND_FOOTPRINT_TTL_SUCCESS:
            case XdrExtendFootprintTTLResultCode::EXTEND_FOOTPRINT_TTL_MALFORMED:
            case XdrExtendFootprintTTLResultCode::EXTEND_FOOTPRINT_TTL_RESOURCE_LIMIT_EXCEEDED:
            case XdrExtendFootprintTTLResultCode::EXTEND_FOOTPRINT_TTL_INSUFFICIENT_REFUNDABLE_FEE:
                break;
        }
        return $result;
    }

    /**
     * @return XdrExtendFootprintTTLResultCode
     */
    public function getCode(): XdrExtendFootprintTTLResultCode
    {
        return $this->code;
    }

    /**
     * @param XdrExtendFootprintTTLResultCode $code
     */
    public function setCode(XdrExtendFootprintTTLResultCode $code): void
    {
        $this->code = $code;
    }

}