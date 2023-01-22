<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecTypeVec
{

    public XdrSCSpecTypeDef $elementType;

    /**
     * @param XdrSCSpecTypeDef $elementType
     */
    public function __construct(XdrSCSpecTypeDef $elementType)
    {
        $this->elementType = $elementType;
    }


    public function encode(): string {
        $bytes = $this->elementType->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecTypeVec {
        return new XdrSCSpecTypeVec(XdrSCSpecTypeDef::decode($xdr));
    }

    /**
     * @return XdrSCSpecTypeDef
     */
    public function getElementType(): XdrSCSpecTypeDef
    {
        return $this->elementType;
    }

    /**
     * @param XdrSCSpecTypeDef $elementType
     */
    public function setElementType(XdrSCSpecTypeDef $elementType): void
    {
        $this->elementType = $elementType;
    }

}