<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrHashIDPreimageEd25519ContractID
{
    public string $networkID; // hash
    public string $ed25519; // uint256
    public string $salt; // uint256

    /**
     * @param string $networkID
     * @param string $ed25519
     * @param string $salt
     */
    public function __construct(string $networkID, string $ed25519, string $salt)
    {
        $this->networkID = $networkID;
        $this->ed25519 = $ed25519;
        $this->salt = $salt;
    }


    public function encode() : string {
        $bytes = XdrEncoder::opaqueFixed($this->networkID, 32);
        $bytes .= XdrEncoder::unsignedInteger256($this->ed25519);
        $bytes .= XdrEncoder::unsignedInteger256($this->salt);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrHashIDPreimageEd25519ContractID {
        $networkID = $xdr->readOpaqueFixed(32);
        $ed25519 = $xdr->readUnsignedInteger256();
        $salt = $xdr->readUnsignedInteger256();
        return new XdrHashIDPreimageEd25519ContractID($networkID, $ed25519, $salt);
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
    public function getEd25519(): string
    {
        return $this->ed25519;
    }

    /**
     * @param string $ed25519
     */
    public function setEd25519(string $ed25519): void
    {
        $this->ed25519 = $ed25519;
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