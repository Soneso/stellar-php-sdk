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

/**
 * Represents a Soroban host function for deploying a Stellar Asset Contract (SAC)
 *
 * This host function deploys a Stellar Asset Contract for a given asset. SACs are
 * standardized smart contracts that provide token functionality for Stellar assets
 * on the Soroban smart contract platform.
 *
 * Stellar Asset Contracts enable:
 * - Interoperability between Stellar assets and Soroban contracts
 * - Standard token interface (similar to ERC-20)
 * - Native asset support in smart contract environments
 *
 * Each Stellar asset can have one canonical SAC, deployed using this host function.
 * The contract address is deterministically generated from the asset.
 *
 * Usage:
 * <code>
 * // Deploy SAC for a custom asset
 * $asset = Asset::createNonNativeAsset("USD", "GBBB...");
 * $hostFunction = new DeploySACWithAssetHostFunction($asset);
 *
 * // Deploy SAC for native XLM
 * $xlmAsset = new AssetTypeNative();
 * $hostFunction = new DeploySACWithAssetHostFunction($xlmAsset);
 *
 * // Use in an InvokeHostFunctionOperation
 * $operation = (new InvokeHostFunctionOperationBuilder($hostFunction))->build();
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see HostFunction Base class for all host functions
 * @see Asset For asset representation
 * @see https://developers.stellar.org/docs/smart-contracts/tokens/stellar-asset-contract
 * @since 1.0.0
 */
class DeploySACWithAssetHostFunction extends HostFunction
{
    /**
     * @var Asset $asset The Stellar asset to deploy as a SAC
     */
    public Asset $asset;

    /**
     * Constructs a new DeploySACWithAssetHostFunction
     *
     * @param Asset $asset The Stellar asset to deploy
     */
    public function __construct(Asset $asset)
    {
        $this->asset = $asset;
        parent::__construct();
    }

    /**
     * Converts the deploy SAC host function to XDR format
     *
     * @return XdrHostFunction The XDR host function
     */
    public function toXdr() : XdrHostFunction {
        return XdrHostFunction::forDeploySACWithAsset($this->asset->toXdr());
    }

    /**
     * Creates a DeploySACWithAssetHostFunction from XDR format
     *
     * @param XdrHostFunction $xdr The XDR host function
     * @return DeploySACWithAssetHostFunction The decoded host function
     * @throws Exception If the XDR format is invalid or missing required data
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
     * Gets the asset
     *
     * @return Asset The Stellar asset to deploy
     */
    public function getAsset(): Asset
    {
        return $this->asset;
    }

    /**
     * Sets the asset
     *
     * @param Asset $asset The Stellar asset to deploy
     * @return void
     */
    public function setAsset(Asset $asset): void
    {
        $this->asset = $asset;
    }

}