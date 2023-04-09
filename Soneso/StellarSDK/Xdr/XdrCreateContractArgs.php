<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrCreateContractArgs
{

    public XdrContractID $contractID;
    public XdrSCContractExecutable $source;

    /**
     * @param XdrContractID $contractID
     * @param XdrSCContractExecutable $source
     */
    public function __construct(XdrContractID $contractID, XdrSCContractExecutable $source)
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
        return new XdrCreateContractArgs(XdrContractID::decode($xdr), XdrSCContractExecutable::decode($xdr));
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
     * @return XdrSCContractExecutable
     */
    public function getSource(): XdrSCContractExecutable
    {
        return $this->source;
    }

    /**
     * @param XdrSCContractExecutable $source
     */
    public function setSource(XdrSCContractExecutable $source): void
    {
        $this->source = $source;
    }
}