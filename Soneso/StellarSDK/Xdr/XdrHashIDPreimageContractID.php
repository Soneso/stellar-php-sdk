<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrHashIDPreimageContractID
{
    public string $networkID; // hash
    public XdrContractIDPreimage $contractIDPreimage;

    /**
     * @param string $networkID
     * @param XdrContractIDPreimage $contractIDPreimage
     */
    public function __construct(string $networkID, XdrContractIDPreimage $contractIDPreimage)
    {
        $this->networkID = $networkID;
        $this->contractIDPreimage = $contractIDPreimage;
    }


    public function encode() : string {
        $bytes = XdrEncoder::opaqueFixed($this->networkID, 32);
        $bytes .= $this->contractIDPreimage->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrHashIDPreimageContractID {
        $networkID = $xdr->readOpaqueFixed(32);
        $contractIDPreimage = XdrContractIDPreimage::decode($xdr);
        return new XdrHashIDPreimageContractID($networkID, $contractIDPreimage);
    }

    /**
     * @return string
     */
    public function getNetworkID(): string
    {
        return $this->networkID;
    }

    /**
     * @param string $networkID
     */
    public function setNetworkID(string $networkID): void
    {
        $this->networkID = $networkID;
    }

    /**
     * @return XdrContractIDPreimage
     */
    public function getContractIDPreimage(): XdrContractIDPreimage
    {
        return $this->contractIDPreimage;
    }

    /**
     * @param XdrContractIDPreimage $contractIDPreimage
     */
    public function setContractIDPreimage(XdrContractIDPreimage $contractIDPreimage): void
    {
        $this->contractIDPreimage = $contractIDPreimage;
    }

}