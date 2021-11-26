<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrDataValue
{
    private ?string $value = null;

    public function __construct(?string $value = null) {
        $this->value = $value;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    public function encode() : string {
        $bytes = "";
        if ($this->value) {
            $bytes .= XdrEncoder::boolean(true);
            $bytes .= XdrEncoder::opaqueVariable($this->value);
        }
        else {
            $bytes .= XdrEncoder::boolean(false);
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) :  XdrDataValue {
        $value = null;
        if ($xdr->readBoolean()) {
            $value = $xdr->readOpaqueVariable(64);
        }
        return new XdrDataValue($value);
    }
}