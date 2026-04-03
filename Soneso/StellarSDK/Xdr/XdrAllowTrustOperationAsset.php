<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;

class XdrAllowTrustOperationAsset extends XdrAllowTrustOperationAssetBase
{
    /**
     * Serialize as a compact asset code string (e.g. "USD", "EURT").
     *
     * Overrides the generated base method, which would expand the type
     * discriminant and raw hex bytes. SEP-0011 uses a plain trimmed code.
     *
     * @param string                $prefix Key prefix for the TxRep map.
     * @param array<string, string> $lines  Output map (modified in place).
     */
    public function toTxRep(string $prefix, array &$lines): void
    {
        $lines[$prefix] = TxRepHelper::formatAllowTrustAsset($this);
    }

    /**
     * Deserialize from a compact asset code string.
     *
     * @param array<string, string> $map    Parsed TxRep map.
     * @param string                $prefix Key prefix.
     * @return static
     * @throws InvalidArgumentException If the value is missing or invalid.
     */
    public static function fromTxRep(array $map, string $prefix): static
    {
        $code = TxRepHelper::getValue($map, $prefix);
        if ($code === null) {
            throw new InvalidArgumentException('Missing TxRep value for: ' . $prefix);
        }

        $codeLen = strlen($code);
        if ($codeLen === 0 || $codeLen > 12) {
            throw new InvalidArgumentException('Asset code length must be 1–12 characters: ' . $code);
        }

        if ($codeLen <= 4) {
            $result = new static(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
            $result->setAssetCode4($code);
            return $result;
        }

        $result = new static(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $result->setAssetCode12($code);
        return $result;
    }

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
