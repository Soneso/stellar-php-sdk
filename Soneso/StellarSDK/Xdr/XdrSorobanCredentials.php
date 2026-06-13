<?php declare(strict_types=1);

namespace Soneso\StellarSDK\Xdr;

/**
 * Hand-written factory wrapper for XdrSorobanCredentialsBase.
 *
 * Provides named constructors for all four credential arms. The generated base class
 * (XdrSorobanCredentialsBase) handles encode/decode/JSON/TxRep; this subclass only adds
 * the factory methods.
 */
class XdrSorobanCredentials extends XdrSorobanCredentialsBase
{
    /**
     * Creates source-account credentials.
     *
     * @return XdrSorobanCredentials
     */
    public static function forSourceAccount(): XdrSorobanCredentials
    {
        return new XdrSorobanCredentials(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_SOURCE_ACCOUNT());
    }

    /**
     * Creates legacy ADDRESS credentials.
     *
     * @param XdrSorobanAddressCredentials $addressCredentials
     * @return XdrSorobanCredentials
     */
    public static function forAddressCredentials(XdrSorobanAddressCredentials $addressCredentials): XdrSorobanCredentials
    {
        $result = new XdrSorobanCredentials(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS());
        $result->address = $addressCredentials;
        return $result;
    }

    /**
     * Creates ADDRESS_V2 credentials (Protocol 27, CAP-71).
     *
     * Uses ENVELOPE_TYPE_SOROBAN_AUTHORIZATION_WITH_ADDRESS (address-bound preimage).
     * Invalid on networks below Protocol 27.
     *
     * @param XdrSorobanAddressCredentials $addressCredentials
     * @return XdrSorobanCredentials
     */
    public static function forAddressCredentialsV2(XdrSorobanAddressCredentials $addressCredentials): XdrSorobanCredentials
    {
        $result = new XdrSorobanCredentials(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_V2());
        $result->addressV2 = $addressCredentials;
        return $result;
    }

    /**
     * Creates ADDRESS_WITH_DELEGATES credentials (Protocol 27, CAP-71).
     *
     * Uses ENVELOPE_TYPE_SOROBAN_AUTHORIZATION_WITH_ADDRESS with a recursive delegate tree.
     * Invalid on networks below Protocol 27.
     *
     * @param XdrSorobanAddressCredentialsWithDelegates $addressWithDelegates
     * @return XdrSorobanCredentials
     */
    public static function forAddressWithDelegates(
        XdrSorobanAddressCredentialsWithDelegates $addressWithDelegates,
    ): XdrSorobanCredentials {
        $result = new XdrSorobanCredentials(XdrSorobanCredentialsType::SOROBAN_CREDENTIALS_ADDRESS_WITH_DELEGATES());
        $result->addressWithDelegates = $addressWithDelegates;
        return $result;
    }
}
