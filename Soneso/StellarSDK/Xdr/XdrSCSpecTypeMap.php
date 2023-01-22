<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecTypeMap
{

    public XdrSCSpecTypeDef $keyType;
    public XdrSCSpecTypeDef $valueType;

    /**
     * @param XdrSCSpecTypeDef $keyType
     * @param XdrSCSpecTypeDef $valueType
     */
    public function __construct(XdrSCSpecTypeDef $keyType, XdrSCSpecTypeDef $valueType)
    {
        $this->keyType = $keyType;
        $this->valueType = $valueType;
    }


    public function encode(): string {
        $bytes = $this->keyType->encode();
        $bytes .= $this->valueType->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecTypeMap {
        return new XdrSCSpecTypeMap(XdrSCSpecTypeDef::decode($xdr), XdrSCSpecTypeDef::decode($xdr));
    }

    /**
     * @return XdrSCSpecTypeDef
     */
    public function getKeyType(): XdrSCSpecTypeDef
    {
        return $this->keyType;
    }

    /**
     * @param XdrSCSpecTypeDef $keyType
     */
    public function setKeyType(XdrSCSpecTypeDef $keyType): void
    {
        $this->keyType = $keyType;
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