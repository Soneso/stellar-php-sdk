<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use InvalidArgumentException;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;

/**
 * Refuses muxed account (M...) and muxed contract (W...) addresses where Soroban auth
 * takes an account or contract address. The host accepts neither kind as an auth
 * address (CAP-0067, CAP-0084).
 *
 * @internal used by the Soroban authorization signing and credentials building paths
 */
final class SorobanAuthAddressGuard
{
    /**
     * Refuses a credential address that encodes to the muxed account or muxed contract arm.
     * The XDR discriminant decides, so an account-typed Address holding an "M..." id is
     * refused as well.
     *
     * @param Address $address the credential address
     * @throws InvalidArgumentException naming the address
     */
    public static function requireCredentialAddress(Address $address): void
    {
        $xdrAddress = $address->toXdr();
        if (self::isMuxed($xdrAddress)) {
            throw new InvalidArgumentException(
                self::message('auth credential addresses', $xdrAddress->toStrKey())
                    . '; use the underlying G... or C... address instead'
            );
        }
    }

    /**
     * Refuses a delegate tree in which a node at any depth holds a muxed account or muxed
     * contract address, judged by the XDR discriminant.
     *
     * @param array<SorobanDelegateSignature> $delegates the delegate nodes
     * @throws InvalidArgumentException naming the first such address, or when the tree is
     * deeper than the XDR decode limit
     */
    public static function requireDelegateAddresses(array $delegates): void
    {
        self::requireDelegateAddressesFrom($delegates, 0);
    }

    /**
     * @param array<SorobanDelegateSignature> $delegates
     */
    private static function requireDelegateAddressesFrom(array $delegates, int $depth): void
    {
        if ($depth > XdrBuffer::RECURSION_LIMIT) {
            throw new InvalidArgumentException(
                'Delegate tree traversal depth limit (' . XdrBuffer::RECURSION_LIMIT . ') exceeded'
            );
        }
        foreach ($delegates as $node) {
            if (self::isMuxed($node->address)) {
                throw new InvalidArgumentException(self::message('delegate addresses', $node->address->toStrKey()));
            }
            if ($node->nestedDelegates !== []) {
                self::requireDelegateAddressesFrom($node->nestedDelegates, $depth + 1);
            }
        }
    }

    /**
     * Refuses a strkey that names a muxed account or muxed contract. No account or
     * contract strkey starts with "M" or "W".
     *
     * @param string $strKey the strkey given
     * @param string $role the place the strkey was given for, in plural, as the message names it
     * @throws InvalidArgumentException naming the strkey
     */
    public static function requireStrKey(string $strKey, string $role): void
    {
        if (str_starts_with($strKey, 'M') || str_starts_with($strKey, 'W')) {
            throw new InvalidArgumentException(self::message($role, $strKey));
        }
    }

    private static function isMuxed(XdrSCAddress $address): bool
    {
        $type = $address->type->value;
        return $type === XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT
            || $type === XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_CONTRACT;
    }

    private static function message(string $role, string $strKey): string
    {
        return 'Muxed account (M...) and muxed contract (W...) addresses are not valid Soroban '
            . $role . ': ' . $strKey;
    }
}
