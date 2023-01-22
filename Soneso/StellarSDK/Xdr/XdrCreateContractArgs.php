<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrCreateContractArgs
{

    public XdrContractID $contractID;
    public XdrSCContractCode $source;

    /**
     * @param XdrContractID $contractID
     * @param XdrSCContractCode $source
     */
    public function __construct(XdrContractID $contractID, XdrSCContractCode $source)
    {
        $this->contractID = $contractID;
        $this->source = $source;
    }


    public function encode(): string {
        $bytes = $this->contractID->encode();
        $bytes .= $this->source->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrCreateContractArgs {
        return new XdrCreateContractArgs(XdrContractID::decode($xdr), XdrSCContractCode::decode($xdr));
    }

    /**
     * @return XdrContractID
     */
    public function getContractID(): XdrContractID
    {
        return $this->contractID;
    }

    /**
     * @param XdrContractID $contractID
     */
    public function setContractID(XdrContractID $contractID): void
    {
        $this->contractID = $contractID;
    }

    /**
     * @return XdrSCContractCode
     */
    public function getSource(): XdrSCContractCode
    {
        return $this->source;
    }

    /**
     * @param XdrSCContractCode $source
     */
    public function setSource(XdrSCContractCode $source): void
    {
        $this->source = $source;
    }
}