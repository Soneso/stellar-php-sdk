<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrHashIDPreimageContractID
{
    public string $networkID; // hash
    public string $contractID; // hex
    public string $salt; // uint256

    /**
     * @param string $networkID
     * @param string $contractID
     * @param string $salt
     */
    public function __construct(string $networkID, string $contractID, string $salt)
    {
        $this->networkID = $networkID;
        $this->contractID = $contractID;
        $this->salt = $salt;
    }


    public function encode() : string {
        $bytes = XdrEncoder::opaqueFixed($this->networkID, 32);
        $bytes .= XdrEncoder::opaqueFixed(hex2bin($this->contractID), 32);
        $bytes .= XdrEncoder::unsignedInteger256($this->salt);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrHashIDPreimageContractID {
        $networkID = $xdr->readOpaqueFixed(32);
        $contractID = bin2hex($xdr->readOpaqueFixed(32));
        $salt = $xdr->readUnsignedInteger256();
        return new XdrHashIDPreimageContractID($networkID, $contractID, $salt);
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
     * @return string
     */
    public function getContractID(): string
    {
        return $this->contractID;
    }

    /**
     * @param string $contractID
     */
    public function setContractID(string $contractID): void
    {
        $this->contractID = $contractID;
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