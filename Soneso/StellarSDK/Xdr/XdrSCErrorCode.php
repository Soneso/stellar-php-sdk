<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCErrorCode
{
    public int $value;

    const SCEC_ARITH_DOMAIN = 0;
    const SCEC_INDEX_BOUNDS = 1;
    const SCEC_INVALID_INPUT = 2;
    const SCEC_MISSING_VALUE = 3;
    const SCEC_EXISTING_VALUE = 4;
    const SCEC_EXCEEDED_LIMIT = 5;
    const SCEC_INVALID_ACTION = 6;
    const SCEC_INTERNAL_ERROR = 7;
    const SCEC_UNEXPECTED_TYPE = 8;
    const SCEC_UNEXPECTED_SIZE = 9;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    public function encode(): string
    {
        return XdrEncoder::integer32($this->value);
    }

    public static function decode(XdrBuffer $xdr): XdrSCErrorCode
    {
        $value = $xdr->readInteger32();
        return new XdrSCErrorCode($value);
    }

    public static function SCEC_ARITH_DOMAIN() : XdrSCErrorCode {
        return new XdrSCErrorCode(XdrSCErrorCode::SCEC_ARITH_DOMAIN);
    }

    public static function SCEC_INDEX_BOUNDS() : XdrSCErrorCode {
        return new XdrSCErrorCode(XdrSCErrorCode::SCEC_INDEX_BOUNDS);
    }
    public static function SCEC_INVALID_INPUT() : XdrSCErrorCode {
        return new XdrSCErrorCode(XdrSCErrorCode::SCEC_INVALID_INPUT);
    }
    public static function SCEC_MISSING_VALUE() : XdrSCErrorCode {
        return new XdrSCErrorCode(XdrSCErrorCode::SCEC_MISSING_VALUE);
    }
    public static function SCEC_EXISTING_VALUE() : XdrSCErrorCode {
        return new XdrSCErrorCode(XdrSCErrorCode::SCEC_EXISTING_VALUE);
    }
    public static function SCEC_EXCEEDED_LIMIT() : XdrSCErrorCode {
        return new XdrSCErrorCode(XdrSCErrorCode::SCEC_EXCEEDED_LIMIT);
    }
    public static function SCEC_INVALID_ACTION() : XdrSCErrorCode {
        return new XdrSCErrorCode(XdrSCErrorCode::SCEC_INVALID_ACTION);
    }
    public static function SCEC_INTERNAL_ERROR() : XdrSCErrorCode {
        return new XdrSCErrorCode(XdrSCErrorCode::SCEC_INTERNAL_ERROR);
    }
    public static function SCEC_UNEXPECTED_TYPE() : XdrSCErrorCode {
        return new XdrSCErrorCode(XdrSCErrorCode::SCEC_UNEXPECTED_TYPE);
    }
    public static function SCEC_UNEXPECTED_SIZE() : XdrSCErrorCode {
        return new XdrSCErrorCode(XdrSCErrorCode::SCEC_UNEXPECTED_SIZE);
    }
}