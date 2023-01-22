<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.
namespace Soneso\StellarSDK\Xdr;

class XdrAsset
{
    protected XdrAssetType $type;
    private ?XdrAssetAlphaNum4 $alphaNum4 = null;
    private ?XdrAssetAlphaNum12 $alphaNum12 = null;

    public function __construct(XdrAssetType $type)  {
        $this->type = $type;
    }

    /**
     * @return XdrAssetType
     */
    public function getType(): XdrAssetType
    {
        return $this->type;
    }

    /**
     * @param XdrAssetType $type
     */
    public function setType(XdrAssetType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrAssetAlphaNum4|null
     */
    public function getAlphaNum4(): ?XdrAssetAlphaNum4
    {
        return $this->alphaNum4;
    }

    /**
     * @param XdrAssetAlphaNum4|null $alphaNum4
     */
    public function setAlphaNum4(?XdrAssetAlphaNum4 $alphaNum4): void
    {
        $this->alphaNum4 = $alphaNum4;
    }

    /**
     * @return XdrAssetAlphaNum12|null
     */
    public function getAlphaNum12(): ?XdrAssetAlphaNum12
    {
        return $this->alphaNum12;
    }

    /**
     * @param XdrAssetAlphaNum12|null $alphaNum12
     */
    public function setAlphaNum12(?XdrAssetAlphaNum12 $alphaNum12): void
    {
        $this->alphaNum12 = $alphaNum12;
    }

    public function encode() : string {
        $bytes = $this->type->encode();
        switch ($this->type->getValue()) {
            case XdrAssetType::ASSET_TYPE_NATIVE:
                break;
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4:
                if ($this->alphaNum4 != null) {
                    $bytes .= $this->alphaNum4->encode();
                }
                break;
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12:
                if ($this->alphaNum12 != null) {
                    $bytes .= $this->alphaNum12->encode();
                }
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrAsset {
        $type = $xdr->readInteger32();
        $result = new XdrAsset(new XdrAssetType($type));
        switch ($type) {
            case XdrAssetType::ASSET_TYPE_NATIVE:
                break;
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4:
                $alphanum4 = XdrAssetAlphaNum4::decode($xdr);
                $result->setAlphaNum4($alphanum4);
                break;
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12:
                $alphanum12 = XdrAssetAlphaNum12::decode($xdr);
                $result->setAlphaNum12($alphanum12);
                break;
        }
        return $result;
    }
}