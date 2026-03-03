<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractIDPreimage extends XdrContractIDPreimageBase
{

    public static function forAddress(XdrSCAddress $address, String $saltHex): XdrContractIDPreimage {
        $result = new XdrContractIDPreimage(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ADDRESS());
        $result->address = $address;
        $result->salt = hex2bin($saltHex);
        return $result;
    }

    public static function forAsset(XdrAsset $asset): XdrContractIDPreimage {
        $result = new XdrContractIDPreimage(XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ASSET());
        $result->asset = $asset;
        return $result;
    }

}
