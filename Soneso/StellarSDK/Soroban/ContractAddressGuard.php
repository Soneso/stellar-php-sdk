<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Xdr\XdrJsonHelper;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCVal;
use Soneso\StellarSDK\Xdr\XdrSCValType;

/**
 * Refuses an id that does not name a contract where Soroban takes a contract, and a
 * contract data key that holds a muxed address. The host accepts a muxed contract (W...)
 * only where it accepts a muxed account (M...), and neither in a storage key (CAP-0084).
 *
 * @internal used by the invoke host function, the assembled transaction and the RPC contract loaders
 */
final class ContractAddressGuard
{
    public const CONTRACT_TO_INVOKE = 'The contract to invoke';
    public const CONTRACT_TO_LOAD = 'The contract to load';
    public const CONTRACT_DATA_OWNER = 'The contract data owner';

    private const MUXED_CONTRACT = 'a muxed contract (W...)';

    /**
     * Refuses an id that is neither a contract strkey ("C...") nor the contract hash as 64
     * hexadecimal characters.
     *
     * @param string $id the id given
     * @param string $subject the role of the id, as the message names it
     * @throws InvalidArgumentException naming the kind of address given and the id
     */
    public static function requireContractId(string $id, string $subject): void
    {
        if (StrKey::isValidContractId($id) || (strlen($id) === 64 && ctype_xdigit($id))) {
            return;
        }
        $kind = self::kindOf($id);
        if ($kind === null) {
            throw self::notAnAddress($id, $subject);
        }
        throw new InvalidArgumentException($subject . ' must be a contract (C...) address, got ' . $kind
            . ' address: ' . $id . ($kind === self::MUXED_CONTRACT ? '; use its base contract (C...) address' : ''));
    }

    /**
     * Builds the error for an id that names no address of any kind. The id is arbitrary input,
     * so the message echoes it escaped and bounded; a strkey of a known kind is plain base32.
     * A secret seed ("S...") is named by its kind only and never echoed.
     *
     * @param string $id the id given
     * @param string $subject the role of the id, as the message names it
     * @return InvalidArgumentException the error naming the id, or the kind for a secret seed
     */
    public static function notAnAddress(string $id, string $subject): InvalidArgumentException
    {
        if (StrKey::isValidSeed($id)) {
            return new InvalidArgumentException($subject
                . ' must be a contract (C...) address, got a secret seed (S...) instead of an address');
        }
        return new InvalidArgumentException($subject . ' must be a contract (C...) address: '
            . XdrJsonHelper::safePreview($id));
    }

    /**
     * Refuses a contract data key that holds a muxed account or muxed contract address, as
     * the key itself or at any depth inside a vector element or a map key or value.
     *
     * @param XdrSCVal $key the contract data key
     * @throws InvalidArgumentException if the key holds such an address
     */
    public static function requireKeyWithoutMuxedAddress(XdrSCVal $key): void
    {
        if (self::holdsMuxedAddress($key)) {
            throw new InvalidArgumentException(
                'Contract data keys cannot contain muxed account (M...) or muxed contract (W...) addresses');
        }
    }

    private static function kindOf(string $id): ?string
    {
        return match (true) {
            StrKey::isValidAccountId($id) => 'an account (G...)',
            StrKey::isValidMuxedAccountId($id) => 'a muxed account (M...)',
            StrKey::isValidMuxedContractId($id) => self::MUXED_CONTRACT,
            StrKey::isValidClaimableBalanceId($id) => 'a claimable balance (B...)',
            StrKey::isValidLiquidityPoolId($id) => 'a liquidity pool (L...)',
            default => null,
        };
    }

    private static function holdsMuxedAddress(XdrSCVal $val): bool
    {
        switch ($val->type->value) {
            case XdrSCValType::SCV_ADDRESS:
                $type = $val->address?->type->value;
                return $type === XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT
                    || $type === XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_CONTRACT;
            case XdrSCValType::SCV_VEC:
                foreach ($val->vec ?? [] as $element) {
                    if (self::holdsMuxedAddress($element)) {
                        return true;
                    }
                }
                return false;
            case XdrSCValType::SCV_MAP:
                foreach ($val->map ?? [] as $entry) {
                    if (self::holdsMuxedAddress($entry->key) || self::holdsMuxedAddress($entry->val)) {
                        return true;
                    }
                }
                return false;
            default:
                return false;
        }
    }
}
