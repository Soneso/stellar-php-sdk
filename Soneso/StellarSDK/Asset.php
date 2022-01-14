<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;
use Soneso\StellarSDK\Xdr\XdrChangeTrustAsset;
use Soneso\StellarSDK\Xdr\XdrTrustlineAsset;

abstract class Asset {

    public const TYPE_NATIVE = "native";
    public const TYPE_CREDIT_ALPHANUM_4 = "credit_alphanum4";
    public const TYPE_CREDIT_ALPHANUM_12 = "credit_alphanum12";
    public const TYPE_POOL_SHARE = "liquidty_pool_shares";

    /**
     * @return string
     */
    public abstract function getType(): string;

    public static function create(string $type, ?string $code = null, ?string $issuer = null) : Asset {
        if (Asset::TYPE_NATIVE == $type) {
            return new AssetTypeNative();
        } else if (Asset::TYPE_CREDIT_ALPHANUM_4 == $type || Asset::TYPE_CREDIT_ALPHANUM_12 == $type) {
            if ($code == null) {
                throw new \RuntimeException("asset code can not be null");
            }

            if ($issuer == null) {
                throw new \RuntimeException("asset issuer can not be null");
            }

            return Asset::createNonNativeAsset($code, $issuer);

        }
        throw new \RuntimeException("unsupported asset type: " . $type);
    }

    public static function createNonNativeAsset(string $code, string $issuer) : AssetTypeCreditAlphanum {
        $codeLen = strlen($code);
        if ($codeLen >= 1 && $codeLen <= 4) {
            return new AssetTypeCreditAlphanum4($code, $issuer);
        } else if ($codeLen > 4 && $codeLen <= 12) {
            return new AssetTypeCreditAlphanum12($code, $issuer);
        } else {
            throw new \RuntimeException("invalid asset code length: " . $codeLen);
        }
    }

    public static function canonicalForm(Asset $asset) : string {
        if ($asset instanceof AssetTypeNative) {
            return "native";
        } else if ($asset instanceof AssetTypeCreditAlphanum) {
            return $asset->getCode() . ":" . $asset->getIssuer();
        }
        throw new \RuntimeException("unsupported asset type: " . $asset->getType());
    }

    public static function createFromCanonicalForm(string $canonicalForm) : ?Asset {
        if ($canonicalForm == 'XLM' || $canonicalForm == "native") {
            return new AssetTypeNative();
        } else {
            $components = explode(":", $canonicalForm);
            if (count($components) == 2) {
                $code = $components[0];
                $issuer = $components[1];
                if (strlen($code) <= 4) {
                    return new AssetTypeCreditAlphanum4($code, $issuer);
                } else if (strlen($code) <= 12) {
                    return new AssetTypeCreditAlphanum12($code, $issuer);
                }
            }
        }
        return null;
    }

    public static function native() : AssetTypeNative {
        return new AssetTypeNative();
    }

    public static function fromJson(array $json) : Asset {
        if (Asset::TYPE_NATIVE == $json['asset_type']) {
            return new AssetTypeNative();
        } else {
            return Asset::createNonNativeAsset($json['asset_code'], $json['asset_issuer']);
        }
    }

    public abstract function toXdr() : XdrAsset;

    public function toXdrChangeTrustAsset(): XdrChangeTrustAsset
    {
        return XdrChangeTrustAsset::fromXdrAsset($this->toXdr());
    }

    public static function fromXdr(XdrAsset $xdrAsset) : Asset {
        switch ($xdrAsset->getType()->getValue()) {
            case XdrAssetType::ASSET_TYPE_NATIVE:
                return new AssetTypeNative();
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4:
                $assetCode4 = $xdrAsset->getAlphaNum4()->getAssetCode();
                $issuer = $xdrAsset->getAlphaNum4()->getIssuer()->getAccountId();
                return new AssetTypeCreditAlphanum4($assetCode4, $issuer);
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12:
                $assetCode12 = $xdrAsset->getAlphaNum12()->getAssetCode();
                $issuer = $xdrAsset->getAlphaNum12()->getIssuer()->getAccountId();
                return new AssetTypeCreditAlphanum12($assetCode12, $issuer);
            case XdrAssetType::ASSET_TYPE_POOL_SHARE:
                if ($xdrAsset instanceof XdrChangeTrustAsset) {
                    $a = $xdrAsset->getLiquidityPool()->getConstantProduct()->getAssetA();
                    $b = $xdrAsset->getLiquidityPool()->getConstantProduct()->getAssetB();
                    return new AssetTypePoolShare(Asset::fromXdr($a), Asset::fromXdr($b));
                } else {
                    throw new InvalidArgumentException("Unknown pool share asset type");
                }
            default:
                throw new InvalidArgumentException("Unknown asset type " . $xdrAsset->getType()->getValue());
        }
    }

    public function toXdrTrustlineAsset(): XdrTrustlineAsset
    {
        return XdrTrustlineAsset::fromXdrAsset($this->toXdr());
    }
}