<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

class XdrSorobanCredentials extends XdrSorobanCredentialsBase
{
    public static function forSourceAccount(): XdrSorobanCredentials {
        return new XdrSorobanCredentials(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT());
    }

    public static function forAddressCredentials(XdrSorobanAddressCredentials $addressCredentials): XdrSorobanCredentials {
        $result = new XdrSorobanCredentials(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS());
        $result->address = $addressCredentials;
        return $result;
    }
}
