<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;

class XdrAllowTrustOperationAsset extends XdrAllowTrustOperationAssetBase
{
    public static function fromAlphaNumAssetCode(string $assetCode) : XdrAllowTrustOperationAsset {
        $len = strlen($assetCode);
        if ($len <= 0 || $len > 12) {
            throw new InvalidArgumentException("invalid asset code ". $assetCode);
        }
        $type = $len > 4 ? XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12 : XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4;
        $result = new XdrAllowTrustOperationAsset(new XdrAssetType($type));
        if ($len > 4) {
            $result->assetCode12 = $assetCode;
        } else {
            $result->assetCode4 = $assetCode;
        }
        return $result;
    }

    public function encode(): string {
        $bytes = $this->type->encode();
        switch ($this->type->getValue()) {
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4:
                $bytes .= XdrEncoder::opaqueFixed($this->assetCode4, 4, true);
                break;
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12:
                $bytes .= XdrEncoder::opaqueFixed($this->assetCode12, 12, true);
                break;
            default:
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): static {
        $result = new static(XdrAssetType::decode($xdr));
        switch ($result->type->getValue()) {
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4:
                $result->assetCode4 = $xdr->readOpaqueFixedString(4);
                break;
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12:
                $result->assetCode12 = $xdr->readOpaqueFixedString(12);
                break;
            default:
                break;
        }
        return $result;
    }
}
