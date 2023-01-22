<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecTypeResult
{

    public XdrSCSpecTypeDef $okType;
    public XdrSCSpecTypeDef $errorType;

    /**
     * @param XdrSCSpecTypeDef $okType
     * @param XdrSCSpecTypeDef $errorType
     */
    public function __construct(XdrSCSpecTypeDef $okType, XdrSCSpecTypeDef $errorType)
    {
        $this->okType = $okType;
        $this->errorType = $errorType;
    }


    public function encode(): string {
        $bytes = $this->okType->encode();
        $bytes .= $this->errorType->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecTypeResult {
        return new XdrSCSpecTypeResult(XdrSCSpecTypeDef::decode($xdr), XdrSCSpecTypeDef::decode($xdr));
    }

    /**
     * @return XdrSCSpecTypeDef
     */
    public function getOkType(): XdrSCSpecTypeDef
    {
        return $this->okType;
    }

    /**
     * @param XdrSCSpecTypeDef $okType
     */
    public function setOkType(XdrSCSpecTypeDef $okType): void
    {
        $this->okType = $okType;
    }

    /**
     * @return XdrSCSpecTypeDef
     */
    public function getErrorType(): XdrSCSpecTypeDef
    {
        return $this->errorType;
    }

    /**
     * @param XdrSCSpecTypeDef $errorType
     */
    public function setErrorType(XdrSCSpecTypeDef $errorType): void
    {
        $this->errorType = $errorType;
    }

}