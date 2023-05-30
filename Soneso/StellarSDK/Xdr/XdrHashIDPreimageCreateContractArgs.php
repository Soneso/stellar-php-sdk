<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrHashIDPreimageCreateContractArgs
{
    public string $networkID; // hash
    public XdrSCContractExecutable $executable;
    public string $salt; // uint256

    /**
     * @param string $networkID
     * @param XdrSCContractExecutable $source
     * @param string $salt
     */
    public function __construct(string $networkID, XdrSCContractExecutable $source, string $salt)
    {
        $this->networkID = $networkID;
        $this->executable = $source;
        $this->salt = $salt;
    }


    public function encode() : string {
        $bytes = XdrEncoder::opaqueFixed($this->networkID, 32);
        $bytes .= $this->executable->encode();
        $bytes .= XdrEncoder::unsignedInteger256($this->salt);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrHashIDPreimageCreateContractArgs {
        $networkID = $xdr->readOpaqueFixed(32);
        $source = XdrSCContractExecutable::decode($xdr);
        $salt = $xdr->readUnsignedInteger256();
        return new XdrHashIDPreimageCreateContractArgs($networkID, $source, $salt);
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
     * @return XdrSCContractExecutable
     */
    public function getExecutable(): XdrSCContractExecutable
    {
        return $this->executable;
    }

    /**
     * @param XdrSCContractExecutable $executable
     */
    public function setExecutable(XdrSCContractExecutable $executable): void
    {
        $this->executable = $executable;
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     */
    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }
}