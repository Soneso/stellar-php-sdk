<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;

/**
 * Represents the native XLM asset on the Stellar network
 *
 * XLM (Lumens) is the native cryptocurrency of Stellar and does not require
 * a trustline or issuer. It is used to pay transaction fees and maintain
 * minimum account balances.
 *
 * Usage:
 * <code>
 * $xlm = Asset::native();
 * // or
 * $xlm = new AssetTypeNative();
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 */
class AssetTypeNative extends Asset {

    /**
     * Returns the asset type identifier
     *
     * @return string Always returns "native"
     */
    public function getType(): string {
        return  Asset::TYPE_NATIVE;
    }

    /**
     * Converts this native asset to its XDR representation
     *
     * @return XdrAsset XDR format of the native asset
     */
    public function toXdr() : XdrAsset {
        return new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
    }
}