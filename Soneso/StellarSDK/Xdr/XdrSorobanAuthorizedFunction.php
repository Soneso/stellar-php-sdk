<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrSorobanAuthorizedFunction extends XdrSorobanAuthorizedFunctionBase
{
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

    public static function forCreateContractArgsV2(XdrCreateContractArgsV2 $args): XdrSorobanAuthorizedFunction {
        $result = new XdrSorobanAuthorizedFunction(XdrSorobanAuthorizedFunctionType::SOROBAN_AUTHORIZED_FUNCTION_TYPE_CREATE_CONTRACT_V2_HOST_FN());
        $result->createContractV2HostFn = $args;
        return $result;
    }
}
