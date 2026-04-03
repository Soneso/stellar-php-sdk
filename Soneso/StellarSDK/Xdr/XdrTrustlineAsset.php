<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\StrKey;

class XdrTrustlineAsset extends XdrTrustlineAssetBase
{
    /**
     * Serialize as a compact asset string.
     *
     * Returns "native" for XLM, "CODE:ISSUER" for credit assets, and the
     * 64-character lowercase hex pool ID for pool-share assets. Overrides
     * the generated base, which would expand sub-fields.
     *
     * @param string                $prefix Key prefix for the TxRep map.
     * @param array<string, string> $lines  Output map (modified in place).
     */
    public function toTxRep(string $prefix, array &$lines): void
    {
        $lines[$prefix] = TxRepHelper::formatTrustlineAsset($this);
    }

    /**
     * Deserialize from a compact asset string.
     *
     * Handles "native"/"XLM", a 64-character hex pool ID, or "CODE:ISSUER".
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
            throw new InvalidArgumentException('Missing TxRep value for: ' . $prefix);
        }

        if ($raw === 'native' || $raw === 'XLM') {
            return new static(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
        }

        // A 64-character hex string with no colon is treated as a pool ID.
        if (strlen($raw) === 64 && strpos($raw, ':') === false) {
            $bytes = hex2bin($raw);
            if ($bytes === false) {
                throw new InvalidArgumentException(
                    'Invalid trustline asset: expected 64-char hex pool ID but got invalid hex: ' . $raw
                );
            }
            $result = new static(new XdrAssetType(XdrAssetType::ASSET_TYPE_POOL_SHARE));
            $result->setLiquidityPoolID($bytes);
            return $result;
        }

        $parts = explode(':', $raw, 2);
        if (count($parts) !== 2) {
            throw new InvalidArgumentException('Invalid trustline asset: ' . $raw);
        }

        $code = trim($parts[0]);
        $issuer = trim($parts[1]);

        if (!StrKey::isValidAccountId($issuer)) {
            throw new InvalidArgumentException('Invalid trustline asset issuer: ' . $issuer);
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

    /**
     * @return string
     */
    public function getPoolId(): string
    {
        return $this->liquidityPoolID;
    }

    /**
     * @param string $poolId
     */
    public function setPoolId(string $poolId): void
    {
        $this->liquidityPoolID = $poolId;
    }

    public static function fromXdrAsset(XdrAsset $xdrAsset) : XdrTrustlineAsset {
        $result = new XdrTrustlineAsset($xdrAsset->getType());
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
                throw new \InvalidArgumentException('XdrAsset cannot represent ASSET_TYPE_POOL_SHARE. Use XdrTrustlineAsset directly.');
        }
        return $result;
    }
}
