<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrSetTrustLineFlagsOperation
{
    private XdrAccountID $accountID;
    private XdrAsset $asset;
    private int $clearFlags;
    private int $setFlags;

    /**
     * @param XdrAccountID $accountID
     * @param XdrAsset $asset
     * @param int $clearFlags
     * @param int $setFlags
     */
    public function __construct(XdrAccountID $accountID, XdrAsset $asset, int $clearFlags, int $setFlags)
    {
        $this->accountID = $accountID;
        $this->asset = $asset;
        $this->clearFlags = $clearFlags;
        $this->setFlags = $setFlags;
    }

    /**
     * @return XdrAccountID
     */
    public function getAccountID(): XdrAccountID
    {
        return $this->accountID;
    }

    /**
     * @return XdrAsset
     */
    public function getAsset(): XdrAsset
    {
        return $this->asset;
    }

    /**
     * @return int
     */
    public function getClearFlags(): int
    {
        return $this->clearFlags;
    }

    /**
     * @return int
     */
    public function getSetFlags(): int
    {
        return $this->setFlags;
    }

    public function encode() : string {
        $bytes = $this->accountID->encode();
        $bytes .= $this->asset->encode();
        $bytes .= XdrEncoder::unsignedInteger32($this->clearFlags);
        $bytes .= XdrEncoder::unsignedInteger32($this->setFlags);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) :  XdrSetTrustLineFlagsOperation {
        $accountID = XdrAccountID::decode($xdr);
        $asset = XdrAsset::decode($xdr);
        $clearFlags = $xdr->readUnsignedInteger32();
        $setFlags = $xdr->readUnsignedInteger32();
        return new XdrSetTrustLineFlagsOperation($accountID, $asset, $clearFlags, $setFlags);
    }
}