<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrAssetAlphaNum12
{
    private string $assetCode;
    private XdrAccountID $issuer;

    public function __construct(string $assetCode, XdrAccountID $issuer) {
        $this->assetCode = $assetCode;
        $this->issuer = $issuer;
    }

    /**
     * @return string
     */
    public function getAssetCode(): string
    {
        return $this->assetCode;
    }

    /**
     * @return XdrAccountID
     */
    public function getIssuer(): XdrAccountID
    {
        return $this->issuer;
    }

    public function encode() : string {
        $bytes = XdrEncoder::opaqueFixed($this->assetCode, 12, true);
        $bytes .= $this->issuer->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrAssetAlphaNum12 {
        $assetCode = $xdr->readOpaqueFixedString(12);
        $issuer = XdrAccountID::decode($xdr);
        return new XdrAssetAlphaNum12($assetCode, $issuer);
    }
}