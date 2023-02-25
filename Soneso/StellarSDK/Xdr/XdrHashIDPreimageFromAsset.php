<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrHashIDPreimageFromAsset
{
    public string $networkID; //hash
    public XdrAsset $asset;

    /**
     * @param string $networkID
     * @param XdrAsset $asset
     */
    public function __construct(string $networkID, XdrAsset $asset)
    {
        $this->networkID = $networkID;
        $this->asset = $asset;
    }


    public function encode() : string {
        $bytes = XdrEncoder::opaqueFixed($this->liquidityPoolID, 32);
        $bytes .= $this->asset->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrHashIDPreimageFromAsset {
        $networkID = $xdr->readOpaqueFixed(32);
        $asset = XdrAsset::decode($xdr);
        return new XdrHashIDPreimageFromAsset($networkID, $asset);
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
     * @return XdrAsset
     */
    public function getAsset(): XdrAsset
    {
        return $this->asset;
    }

    /**
     * @param XdrAsset $asset
     */
    public function setAsset(XdrAsset $asset): void
    {
        $this->asset = $asset;
    }

}