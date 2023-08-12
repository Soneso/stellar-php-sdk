<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCError
{
    public XdrSCErrorType $type;
    public XdrSCErrorCode $code;

    public function __construct(XdrSCErrorType $type, XdrSCErrorCode $code) {
        $this->type = $type;
        $this->code = $code;
    }

    public function encode(): string {
        $bytes = $this->type->encode();
        $bytes .= $this->code->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSCError {
        $type = XdrSCErrorType::decode($xdr);
        $code = XdrSCErrorCode::decode($xdr);
        return new XdrSCError($type, $code);
    }

    /**
     * @return XdrSCErrorType
     */
    public function getType(): XdrSCErrorType
    {
        return $this->type;
    }

    /**
     * @param XdrSCErrorType $type
     */
    public function setType(XdrSCErrorType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrSCErrorCode
     */
    public function getCode(): XdrSCErrorCode
    {
        return $this->code;
    }

    /**
     * @param XdrSCErrorCode $code
     */
    public function setCode(XdrSCErrorCode $code): void
    {
        $this->code = $code;
    }

}