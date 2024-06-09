<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSorobanAuthorizedFunction
{

    public XdrSorobanAuthorizedFunctionType $type;
    public ?XdrInvokeContractArgs $contractFn;
    public ?XdrCreateContractArgs $createContractHostFn = null;

    /**
     * @param XdrSorobanAuthorizedFunctionType $type
     */
    public function __construct(XdrSorobanAuthorizedFunctionType $type)
    {
        $this->type = $type;
    }


    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CONTRACT_FN:
                $bytes .= $this->contractFn->encode();
                break;
            case XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN:
                $bytes .= $this->createContractHostFn->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSorobanAuthorizedFunction {
        $result = new XdrSorobanAuthorizedFunction(XdrSorobanAuthorizedFunctionType::decode($xdr));
        switch ($result->type->value) {
            case XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CONTRACT_FN:
                $result->contractFn = XdrInvokeContractArgs::decode($xdr);
                break;
            case XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN:
                $result->createContractHostFn = XdrCreateContractArgs::decode($xdr);
                break;
        }
        return $result;
    }

    public static function forInvokeContractArgs(XdrInvokeContractArgs $args): XdrSorobanAuthorizedFunction {
        $result = new XdrSorobanAuthorizedFunction(XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CONTRACT_FN());
        $result->contractFn = $args;
        return $result;
    }

    public static function forCreateContractArgs(XdrCreateContractArgs $args): XdrSorobanAuthorizedFunction {
        $result = new XdrSorobanAuthorizedFunction(XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_HOST_FN());
        $result->createContractHostFn = $args;
        return $result;
    }

    /**
     * @return XdrSorobanAuthorizedFunctionType
     */
    public function getType(): XdrSorobanAuthorizedFunctionType
    {
        return $this->type;
    }

    /**
     * @param XdrSorobanAuthorizedFunctionType $type
     */
    public function setType(XdrSorobanAuthorizedFunctionType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrInvokeContractArgs|null
     */
    public function getContractFn(): ?XdrInvokeContractArgs
    {
        return $this->contractFn;
    }

    /**
     * @param XdrInvokeContractArgs|null $contractFn
     */
    public function setContractFn(?XdrInvokeContractArgs $contractFn): void
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