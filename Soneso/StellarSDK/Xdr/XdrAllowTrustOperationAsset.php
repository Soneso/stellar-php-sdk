<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;

class XdrAllowTrustOperationAsset
{
    private XdrAssetType $type;
    private ?string $assetCode4 = null;
    private ?string $assetCode12 = null;

    public function __construct(XdrAssetType $type, ?string $assetCode4 = null, ?string $assetCode12 = null) {
        $this->type = $type;
        $this->assetCode4 = $assetCode4;
        $this->assetCode12 = $assetCode12;
    }

    public static function fromAlphaNumAssetCode(string $assetCode) : XdrAllowTrustOperationAsset {
        $len = strlen($assetCode);
        if ($len <= 0 || $len > 12) {
            throw new InvalidArgumentException("invalid asset code ". $assetCode);
        }
        $type = $len > 4 ? XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12 : XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4;
        $assetCode4 = $len > 4 ? null : $assetCode;
        $assetCode12 = $len > 4 ? $assetCode : null;
        return new XdrAllowTrustOperationAsset(new XdrAssetType($type), $assetCode4, $assetCode12);
    }

    /**
     * @return XdrAssetType
     */
    public function getType(): XdrAssetType
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getAssetCode4(): ?string
    {
        return $this->assetCode4;
    }

    /**
     * @return string|null
     */
    public function getAssetCode12(): ?string
    {
        return $this->assetCode12;
    }

    public function encode() : string {
        $bytes = $this->type->encode();

        if ($this->type->getValue() == XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4) {
            $bytes .= XdrEncoder::opaqueFixed($this->assetCode4, 4, true);
        } else if ($this->type->getValue() == XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12) {
            $bytes .= XdrEncoder::opaqueFixed($this->assetCode12, 12, true);
        }

        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): XdrAllowTrustOperationAsset {
        $type = XdrAssetType::decode($xdr);

        if ($type->getValue() == XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4) {
            $assetCode = $xdr->readOpaqueFixedString(4);
            return new XdrAllowTrustOperationAsset($type, $assetCode, null);
        }

        $assetCode = $xdr->readOpaqueFixedString(12);
        return new XdrAllowTrustOperationAsset($type, null, $assetCode);
    }
}