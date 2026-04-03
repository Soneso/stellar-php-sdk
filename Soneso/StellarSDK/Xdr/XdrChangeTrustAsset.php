<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\StrKey;

class XdrChangeTrustAsset extends XdrChangeTrustAssetBase
{
    /**
     * Serialize the change-trust asset.
     *
     * Native and credit assets are written as a single compact string
     * (e.g. "native" or "USD:G..."). Pool-share assets are expanded to their
     * sub-fields by delegating to the generated base implementation.
     *
     * @param string                $prefix Key prefix for the TxRep map.
     * @param array<string, string> $lines  Output map (modified in place).
     */
    public function toTxRep(string $prefix, array &$lines): void
    {
        if ($this->type->getValue() === XdrAssetType::ASSET_TYPE_POOL_SHARE) {
            parent::toTxRep($prefix, $lines);
            return;
        }
        $lines[$prefix] = TxRepHelper::formatChangeTrustAsset($this);
    }

    /**
     * Deserialize a change-trust asset.
     *
     * A compact asset string ("native", "XLM", or "CODE:ISSUER") is parsed
     * directly. Pool-share assets are expected to be represented by their
     * expanded sub-field keys and are deserialized via the generated base.
     *
     * Detection: if the prefix key itself is present in the map, the value is
     * a compact asset string; otherwise pool-share sub-fields are expected.
     *
     * @param array<string, string> $map    Parsed TxRep map.
     * @param string                $prefix Key prefix.
     * @return static
     * @throws InvalidArgumentException If the value is missing or invalid.
     */
    public static function fromTxRep(array $map, string $prefix): static
    {
        $raw = TxRepHelper::getValue($map, $prefix);

        if ($raw === null) {
            // No compact value present — pool-share asset with expanded sub-fields.
            return parent::fromTxRep($map, $prefix);
        }

        if ($raw === 'native' || $raw === 'XLM') {
            return new static(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        }

        $parts = explode(':', $raw, 2);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException('Invalid change trust asset: ' . $raw);
        }

        $code = trim($parts[0]);
        $issuer = trim($parts[1]);

        if (!StrKey::isValidAccountId($issuer)) {
            throw new InvalidArgumentException('Invalid change trust asset issuer: ' . $issuer);
        }

        $issuerId = XdrAccountID::fromAccountId($issuer);
        $codeLen = strlen($code);

        if ($codeLen === 0 || $codeLen > 12) {
            throw new InvalidArgumentException('Asset code length must be 1–12 characters: ' . $code);
        }

        if ($codeLen <= 4) {
            $result = new static(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4));
            $result->setAlphaNum4(new XdrAssetAlphaNum4($code, $issuerId));
            return $result;
        }

        $result = new static(new XdrAssetType(XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12));
        $result->setAlphaNum12(new XdrAssetAlphaNum12($code, $issuerId));
        return $result;
    }

    public static function fromXdrAsset(XdrAsset $xdrAsset) : XdrChangeTrustAsset {
        $result = new XdrChangeTrustAsset($xdrAsset->getType());
        switch ($xdrAsset->getType()->getValue()) {
            case XdrAssetType::ASSET_TYPE_NATIVE:
                break;
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM4:
                $result->setAlphaNum4($xdrAsset->getAlphaNum4());
                break;
            case XdrAssetType::ASSET_TYPE_CREDIT_ALPHANUM12:
                $result->setAlphaNum12($xdrAsset->getAlphaNum12());
                break;
            case XdrAssetType::ASSET_TYPE_POOL_SHARE:
                throw new \InvalidArgumentException('XdrAsset cannot represent ASSET_TYPE_POOL_SHARE. Use XdrChangeTrustAsset directly.');
        }
        return $result;
    }
}
