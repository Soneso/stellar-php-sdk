<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Xdr\XdrContractIDPreimageType;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrContractExecutableType;

class DeploySACWithAssetHostFunction extends HostFunction
{
    public Asset $asset;

    /**
     * @param Asset $asset
     */
    public function __construct(Asset $asset)
    {
        $this->asset = $asset;
        parent::__construct();
    }

    public function toXdr() : XdrHostFunction {
        return XdrHostFunction::forDeploySACWithAsset($this->asset->toXdr());
    }

    /**
     * @throws Exception
     */
    public static function fromXdr(XdrHostFunction $xdr) : DeploySACWithAssetHostFunction {
        $type = $xdr->type;
        if ($type->value !== XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT &&
            $type->value !== XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT_V2) {
            throw new Exception("Invalid argument");
        }

        $preimage = $xdr->createContract !== null ? $xdr->createContract->contractIDPreimage :
            ($xdr->createContractV2 !== null ? $xdr->createContractV2->contractIDPreimage : null);
        $executableTypeValue = $xdr->createContract !== null ? $xdr->createContract->executable->type->value :
            ($xdr->createContractV2 !== null ? $xdr->createContractV2->executable->type->value : null);
        $xdrAsset = $xdr->createContract !== null ? $xdr->createContract->contractIDPreimage->asset :
            ($xdr->createContractV2 !== null ? $xdr->createContractV2->contractIDPreimage->asset : null);

        if($preimage === null || $executableTypeValue === null || $xdrAsset === null) {
            throw new Exception("Invalid argument");
        }

        if ($preimage->type->value !== XdrContractIDPreimageType::CONTRACT_ID_PREIMAGE_FROM_ASSET ||
            $executableTypeValue !== XdrContractExecutableType::CONTRACT_EXECUTABLE_STELLAR_ASSET) {
            throw new Exception("Invalid argument");
        }

        return new DeploySACWithAssetHostFunction(Asset::fromXdr($xdrAsset));
    }

    /**
     * @return Asset
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * @param Asset $asset
     */
    public function setAsset(Asset $asset): void
    {
        $this->asset = $asset;
    }

}