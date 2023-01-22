<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecTypeOption
{

    public XdrSCSpecTypeDef $valueType;

    /**
     * @param XdrSCSpecTypeDef $valueType
     */
    public function __construct(XdrSCSpecTypeDef $valueType)
    {
        $this->valueType = $valueType;
    }


    public function encode(): string {
        $bytes = $this->valueType->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecTypeOption {
        return new XdrSCSpecTypeOption(XdrSCSpecTypeDef::decode($xdr));
    }

    /**
     * @return XdrSCSpecTypeDef
     */
    public function getValueType(): XdrSCSpecTypeDef
    {
        return $this->valueType;
    }

    /**
     * @param XdrSCSpecTypeDef $valueType
     */
    public function setValueType(XdrSCSpecTypeDef $valueType): void
    {
        $this->valueType = $valueType;
    }

}