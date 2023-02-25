<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrHashIDPreimageSourceAccountContractID
{
    public string $networkID; // hash
    public XdrAccountID $sourceAccount;
    public string $salt; // uint256

    /**
     * @param string $networkID
     * @param XdrAccountID $sourceAccount
     * @param string $salt
     */
    public function __construct(string $networkID, XdrAccountID $sourceAccount, string $salt)
    {
        $this->networkID = $networkID;
        $this->sourceAccount = $sourceAccount;
        $this->salt = $salt;
    }


    public function encode() : string {
        $bytes = XdrEncoder::opaqueFixed($this->networkID, 32);
        $bytes .= $this->sourceAccount->encode();
        $bytes .= XdrEncoder::unsignedInteger256($this->salt);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrHashIDPreimageSourceAccountContractID {
        $networkID = $xdr->readOpaqueFixed(32);
        $sourceAccount = XdrAccountID::decode($xdr);
        $salt = $xdr->readUnsignedInteger256();
        return new XdrHashIDPreimageSourceAccountContractID($networkID, $sourceAccount, $salt);
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
     * @return XdrAccountID
     */
    public function getSourceAccount(): XdrAccountID
    {
        return $this->sourceAccount;
    }

    /**
     * @param XdrAccountID $sourceAccount
     */
    public function setSourceAccount(XdrAccountID $sourceAccount): void
    {
        $this->sourceAccount = $sourceAccount;
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