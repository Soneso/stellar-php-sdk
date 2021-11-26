<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrManageDataOperation
{
    private string $key;
    private XdrDataValue $value;

    public function __construct(string $key, XdrDataValue $value) {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @return XdrDataValue
     */
    public function getValue(): XdrDataValue
    {
        return $this->value;
    }

    public function encode(): string {
        $bytes = XdrEncoder::string($this->key, 64);
        $bytes .= $this->value->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrManageDataOperation {
        $key = $xdr->readString(64);
        $value = XdrDataValue::decode($xdr);
        return new XdrManageDataOperation($key, $value);
    }
}