<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrDataValueMandatory
{
    public string $value;

    public function __construct(string $value) {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    public function encode() : string {
        return XdrEncoder::opaqueVariable($this->value);
    }

    public static function decode(XdrBuffer $xdr) :  XdrDataValueMandatory {
        return new XdrDataValueMandatory($xdr->readOpaqueVariable());
    }
}