<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrBumpFootprintExpirationResult
{

    public XdrBumpFootprintExpirationResultCode $code;

    /**
     * @param XdrBumpFootprintExpirationResultCode $code
     */
    public function __construct(XdrBumpFootprintExpirationResultCode $code)
    {
        $this->code = $code;
    }


    public function encode(): string {
        $bytes = $this->code->encode();

        switch ($this->code->value) {
            case XdrBumpFootprintExpirationResultCode::BUMP_FOOTPRINT_EXPIRATION_SUCCESS:
            case XdrBumpFootprintExpirationResultCode::BUMP_FOOTPRINT_EXPIRATION_MALFORMED:
            case XdrBumpFootprintExpirationResultCode::BUMP_FOOTPRINT_EXPIRATION_RESOURCE_LIMIT_EXCEEDED:
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrBumpFootprintExpirationResult {
        $result = new XdrBumpFootprintExpirationResult(XdrBumpFootprintExpirationResultCode::decode($xdr));
        switch ($result->code->value) {
            case XdrBumpFootprintExpirationResultCode::BUMP_FOOTPRINT_EXPIRATION_SUCCESS:
            case XdrBumpFootprintExpirationResultCode::BUMP_FOOTPRINT_EXPIRATION_MALFORMED:
            case XdrBumpFootprintExpirationResultCode::BUMP_FOOTPRINT_EXPIRATION_RESOURCE_LIMIT_EXCEEDED:
                break;
        }
        return $result;
    }

    /**
     * @return XdrBumpFootprintExpirationResultCode
     */
    public function getCode(): XdrBumpFootprintExpirationResultCode
    {
        return $this->code;
    }

    /**
     * @param XdrBumpFootprintExpirationResultCode $code
     */
    public function setCode(XdrBumpFootprintExpirationResultCode $code): void
    {
        $this->code = $code;
    }

}