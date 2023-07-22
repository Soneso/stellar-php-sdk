<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use InvalidArgumentException;
use Soneso\StellarSDK\Xdr\XdrCreateContractArgs;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunction;
use Soneso\StellarSDK\Xdr\XdrSorobanAuthorizedFunctionType;


class SorobanAuthorizedFunction
{
    public ?SorobanAuthorizedContractFunction $contractFn = null;
    public ?XdrCreateContractArgs $createContractHostFn = null;

    /**
     * @param SorobanAuthorizedContractFunction|null $contractFn
     * @param XdrCreateContractArgs|null $createContractHostFn
     */
    public function __construct(?SorobanAuthorizedContractFunction $contractFn = null, ?XdrCreateContractArgs $createContractHostFn = null)
    {
        if ($contractFn == null && $createContractHostFn == null) {
            throw new InvalidArgumentException("Invalid arguments");
        }
        if ($contractFn != null && $createContractHostFn != null) {
            throw new InvalidArgumentException("Invalid arguments");
        }

        $this->contractFn = $contractFn;
        $this->createContractHostFn = $createContractHostFn;
    }

    public static function forContractFunction(Address $contractAddress, string $functionName, array $args = array()) : SorobanAuthorizedFunction {
        $cfn = new SorobanAuthorizedContractFunction($contractAddress, $functionName,$args);
        return new SorobanAuthorizedFunction($cfn);
    }

    public static function forCreateContractFunction(XdrCreateContractArgs $createContractHostFn) : SorobanAuthorizedFunction {
        return new SorobanAuthorizedFunction(null, $createContractHostFn);
    }

    public static function fromXdr(XdrSorobanAuthorizedFunction $xdr) : SorobanAuthorizedFunction {
        if ($xdr->type->value == XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CONTRACT_FN && $xdr->contractFn != null) {
            return new SorobanAuthorizedFunction(SorobanAuthorizedContractFunction::fromXdr($xdr->contractFn));
        }
        return new SorobanAuthorizedFunction(null, $xdr->createContractHostFn);
    }

    public function toXdr(): XdrSorobanAuthorizedFunction {
        if ($this->contractFn != null) {
            $af = new XdrSorobanAuthorizedFunction(XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CONTRACT_FN());
            $af->contractFn = $this->contractFn->toXdr();
            return $af;
        }
        $af = new XdrSorobanAuthorizedFunction(XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN());
        $af->createContractHostFn = $this->createContractHostFn;
        return $af;
    }

    /**
     * @return SorobanAuthorizedContractFunction|null
     */
    public function getContractFn(): ?SorobanAuthorizedContractFunction
    {
        return $this->contractFn;
    }

    /**
     * @param SorobanAuthorizedContractFunction|null $contractFn
     */
    public function setContractFn(?SorobanAuthorizedContractFunction $contractFn): void
    {
        $this->contractFn = $contractFn;
    }

    /**
     * @return XdrCreateContractArgs|null
     */
    public function getCreateContractHostFn(): ?XdrCreateContractArgs
    {
        return $this->createContractHostFn;
    }

    /**
     * @param XdrCreateContractArgs|null $createContractHostFn
     */
    public function setCreateContractHostFn(?XdrCreateContractArgs $createContractHostFn): void
    {
        $this->createContractHostFn = $createContractHostFn;
    }
}