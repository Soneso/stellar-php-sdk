<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Xdr\XdrHostFunctionType;

class InvokeHostFunctionOperationBuilder
{
    // common
    private XdrHostFunctionType $hostFunctionType;
    private ?Footprint $footprint = null;
    private ?MuxedAccount $sourceAccount = null;

    // for invoking contracts
    private ?string $contractID = null;
    private ?string $functionName = null;
    private ?array $arguments = null; // [XdrSCVal]

    // for installing contracts
    private ?string  $contractCodeBytes = null;// Uint8List

    // for creating contracts
    private ?string $wasmId = null;
    private ?string $salt = null;
    private ?Asset  $asset = null;

    /**
     * @param XdrHostFunctionType $hostFunctionType
     */
    public function __construct(XdrHostFunctionType $hostFunctionType)
    {
        $this->hostFunctionType = $hostFunctionType;
    }

    public static function forInvokingContract(string $contractID, string $functionName, ?array $functionArguments = null, ?Footprint $footprint = null) : InvokeHostFunctionOperationBuilder {
        $type = new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT);
        $builder = new InvokeHostFunctionOperationBuilder($type);
        $builder->contractID = $contractID;
        $builder->functionName = $functionName;
        $builder->arguments = $functionArguments;
        $builder->footprint = $footprint;
        return $builder;
    }

    public static function forInstallingContractCode(string $contractCodeBytes, ?Footprint $footprint = null) : InvokeHostFunctionOperationBuilder {
        $type = new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_INSTALL_CONTRACT_CODE);
        $builder = new InvokeHostFunctionOperationBuilder($type);
        $builder->contractCodeBytes = $contractCodeBytes;
        $builder->footprint = $footprint;
        return $builder;
    }

    public static function forCreatingContract(string $wasmId, ?string $salt = null, ?Footprint $footprint = null) : InvokeHostFunctionOperationBuilder {
        $type = new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT);
        $builder = new InvokeHostFunctionOperationBuilder($type);
        $builder->wasmId = $wasmId;
        $builder->salt = $salt != null ? $salt : random_bytes(32);
        $builder->footprint = $footprint;
        return $builder;
    }

    public static function forDeploySACWithSourceAccount(?string $salt = null, ?Footprint $footprint = null) : InvokeHostFunctionOperationBuilder {
        $type = new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT);
        $builder = new InvokeHostFunctionOperationBuilder($type);
        $builder->salt = $salt != null ? $salt : random_bytes(32);
        $builder->footprint = $footprint;
        return $builder;
    }

    public static function forDeploySACWithAsset(Asset $asset, ?string $salt = null, ?Footprint $footprint = null) : InvokeHostFunctionOperationBuilder {
        $type = new XdrHostFunctionType(XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT);
        $builder = new InvokeHostFunctionOperationBuilder($type);
        $builder->asset = $asset;
        $builder->salt = $salt;
        $builder->footprint = $footprint;
        return $builder;
    }

    public function setSourceAccount(string $accountId) : InvokeHostFunctionOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : InvokeHostFunctionOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function setFootprint(Footprint $footprint) : InvokeHostFunctionOperationBuilder {
        $this->footprint = $footprint;
        return $this;
    }


    /**
     * @throws Exception if the host function type is unknown or not implemented
     */
    public function build(): InvokeHostFunctionOperation {

        switch ($this->hostFunctionType->value) {
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_INVOKE_CONTRACT:
                return new InvokeContractOp($this->contractID, $this->functionName, $this->arguments, $this->footprint, $this->sourceAccount);
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_INSTALL_CONTRACT_CODE:
                return new InstallContractCodeOp($this->contractCodeBytes, $this->footprint, $this->sourceAccount);
            case XdrHostFunctionType::HOST_FUNCTION_TYPE_CREATE_CONTRACT:
                if($this->wasmId != null) {
                    return new CreateContractOp($this->wasmId, $this->salt, $this->footprint, $this->sourceAccount);
                } else if($this->asset != null) {
                    return new DeploySACWithAssetOp($this->asset, $this->footprint, $this->sourceAccount);
                } else {
                    return new DeploySACWithSourceAccountOp($this->salt, $this->footprint, $this->sourceAccount);
                }
            default:
                throw new Exception('unknown host function type: ' . $this->hostFunctionType->value);
        }
    }
}