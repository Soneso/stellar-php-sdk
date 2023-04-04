<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCHostAuthErrorCode
{
    public int $value;

    const HOST_AUTH_UNKNOWN_ERROR = 0;
    const HOST_AUTH_NONCE_ERROR = 1;
    const HOST_AUTH_DUPLICATE_AUTHORIZATION = 2;
    const HOST_AUTH_NOT_AUTHORIZED = 3;


    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function UNKNOWN_ERROR() : XdrSCHostAuthErrorCode {
        return new XdrSCHostAuthErrorCode(XdrSCHostAuthErrorCode::HOST_AUTH_UNKNOWN_ERROR);
    }

    public static function NONCE_ERROR() : XdrSCHostAuthErrorCode {
        return new XdrSCHostAuthErrorCode(XdrSCHostAuthErrorCode::HOST_AUTH_NONCE_ERROR);
    }

    public static function DUPLICATE_AUTHORIZATION() : XdrSCHostAuthErrorCode {
        return new XdrSCHostAuthErrorCode(XdrSCHostAuthErrorCode::HOST_AUTH_DUPLICATE_AUTHORIZATION);
    }

    public static function NOT_AUTHORIZED() : XdrSCHostAuthErrorCode {
        return new XdrSCHostAuthErrorCode(XdrSCHostAuthErrorCode::HOST_AUTH_NOT_AUTHORIZED);
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

    public static function decode(XdrBuffer $xdr): XdrSCHostAuthErrorCode
    {
        $value = $xdr->readInteger32();
        return new XdrSCHostAuthErrorCode($value);
    }
}