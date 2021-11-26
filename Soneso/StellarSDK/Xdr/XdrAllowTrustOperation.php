<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrAllowTrustOperation
{
    private XdrAccountID $trustor;
    private XdrAllowTrustOperationAsset $asset;
    private int $authorized;

    public function __construct(XdrAccountID $trustor, XdrAllowTrustOperationAsset $asset, int $authorized) {
        $this->trustor = $trustor;
        $this->asset = $asset;
        $this->authorized = $authorized;
    }

    /**
     * @return XdrAccountID
     */
    public function getTrustor(): XdrAccountID
    {
        return $this->trustor;
    }

    /**
     * @return XdrAllowTrustOperationAsset
     */
    public function getAsset(): XdrAllowTrustOperationAsset
    {
        return $this->asset;
    }

    /**
     * @return int
     */
    public function getAuthorized(): int
    {
        return $this->authorized;
    }

    public function encode() : string {
        $bytes = $this->trustor->encode();
        $bytes .= $this->asset->encode();
        $bytes .= XdrEncoder::integer32($this->authorized);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): XdrAllowTrustOperation {
        $trustor = XdrAccountID::decode($xdr);
        $asset = XdrAllowTrustOperationAsset::decode($xdr);
        $authorized = $xdr->readInteger32();
        return new XdrAllowTrustOperation($trustor, $asset, $authorized);
    }
}