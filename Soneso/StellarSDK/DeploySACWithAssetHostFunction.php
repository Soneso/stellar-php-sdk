<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Xdr\XdrContractIDType;
use Soneso\StellarSDK\Xdr\XdrHostFunction;
use Soneso\StellarSDK\Xdr\XdrHostFunctionArgs;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;
use Soneso\StellarSDK\Xdr\XdrSCContractExecutableType;

class DeploySACWithAssetHostFunction extends HostFunction
{
    public Asset $asset;

    /**
     * @param Asset $asset
     * @param array|null $auth
     */
    public function __construct(Asset $asset, ?array $auth = array())
    {
        $this->asset = $asset;
        parent::__construct($auth);
    }

    public function toXdr() : XdrHostFunction {
        $args = XdrHostFunctionArgs::forDeploySACWithAsset($this->asset->toXdr());
        return new XdrHostFunction($args, self::convertToXdrAuth($this->auth));
    }

    /**
     * @throws Exception
     */
    public static function fromXdr(XdrHostFunction $xdr) : DeploySACWithAssetHostFunction {
        $args = $xdr->args;
        $type = $args->type;
        if ($type->value != XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT || $args->createContract == null
            || $args->createContract->contractID->type->value != XdrContractIDType::CONTRACT_ID_FROM_ASSET
            || $args->createContract->executable->type->value != XdrSCContractExecutableType::SCCONTRACT_EXECUTABLE_TOKEN) {
            throw new Exception("Invalid argument");
        }

        $asset = Asset::fromXdr($args->createContract->contractID->asset);

        return new DeploySACWithAssetHostFunction($asset, self::convertFromXdrAuth($xdr->auth));
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