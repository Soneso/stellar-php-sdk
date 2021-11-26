<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrAssetAlphaNum4
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
        $bytes = XdrEncoder::opaqueFixed($this->assetCode, 4, true);
        $bytes .= $this->issuer->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrAssetAlphaNum4 {
        $assetCode = $xdr->readOpaqueFixedString(4);
        $issuer = XdrAccountID::decode($xdr);
        return new XdrAssetAlphaNum4($assetCode, $issuer);
    }

}