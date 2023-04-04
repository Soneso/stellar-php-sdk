<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCHostContextErrorCode
{
    public int $value;

    const HOST_CONTEXT_UNKNOWN_ERROR = 0;
    const HOST_CONTEXT_NO_CONTRACT_RUNNING = 1;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public static function UNKNOWN_ERROR() : XdrSCHostContextErrorCode {
        return new XdrSCHostContextErrorCode(XdrSCHostContextErrorCode::HOST_CONTEXT_UNKNOWN_ERROR);
    }

    public static function NO_CONTRACT_RUNNING() : XdrSCHostContextErrorCode {
        return new XdrSCHostContextErrorCode(XdrSCHostContextErrorCode::HOST_CONTEXT_NO_CONTRACT_RUNNING);
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

    public static function decode(XdrBuffer $xdr): XdrSCHostContextErrorCode
    {
        $value = $xdr->readInteger32();
        return new XdrSCHostContextErrorCode($value);
    }
}