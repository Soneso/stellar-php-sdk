<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAsset;
use Soneso\StellarSDK\Xdr\XdrAssetType;

class AssetTypeNative extends Asset {

    public function getType(): string {
        return  Asset::TYPE_NATIVE;
    }

    public function toXdr() : XdrAsset {
        return new XdrAsset(new XdrAssetType(XdrAssetType::ASSET_TYPE_NATIVE));
    }
}