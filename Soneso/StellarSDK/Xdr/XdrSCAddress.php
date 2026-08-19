<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Exception;
use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\StrKey;

/**
 * SCAddress union, with the factory methods that take an id in the spelling the
 * SDK's callers use and the strkey rendering that gives one back.
 *
 * The generated base holds the contract id and the liquidity pool id as
 * hexadecimal and accepts their strkey spelling as well; every reader resolves
 * those two fields through XdrSCAddressBase::getCanonicalContractIdHex() and
 * XdrSCAddressBase::getCanonicalLiquidityPoolIdHex(), so no reader takes what
 * another refuses.
 */
class XdrSCAddress extends XdrSCAddressBase
{
    /**
     * Accepts ed25519 "G..." and muxed ("M...") account ids.
     * @param string $accountId "G..." or "M..."
     * @return XdrSCAddress
     */
    public static function forAccountId(string $accountId) : XdrSCAddress {
        if (str_starts_with($accountId, "G")) {
            $res = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT());
            $res->accountId = XdrAccountID::fromAccountId($accountId);
            return $res;
        } else if (str_starts_with($accountId, "M")) {
            $res = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT());
            $bytes = StrKey::decodeMuxedAccountId($accountId);
            $xdrBuffer = new XdrBuffer($bytes);
            $res->muxedAccount = XdrMuxedAccountMed25519::decodeInverted($xdrBuffer);
            return $res;
        } else {
            throw new InvalidArgumentException("invalid account id: " . $accountId);
        }
    }

    /**
     * Accepts hex or strkey values ("C...")
     * @param string $contractId hex or "C..."
     * @return XdrSCAddress
     */
    public static function forContractId(string $contractId) : XdrSCAddress {
        $res = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT());
        $res->contractId = $contractId;
        return $res;
    }

    /**
     * Accepts strkey ("B...") and hex values. The hexadecimal form is the balance hash
     * on its own, or the hash behind its type discriminant, as the SDK's
     * getPaddedBalanceIdHex() and Horizon spell it.
     * @param string $claimableBalanceId "B..." or hex string
     * @return XdrSCAddress
     */
    public static function forClaimableBalanceId(string $claimableBalanceId) : XdrSCAddress {
        $res = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE());
        $res->claimableBalanceId = XdrClaimableBalanceID::forClaimableBalanceId($claimableBalanceId);
        return $res;
    }

    /**
     * Accepts strkey ("L...") and hex values.
     * @param string $liquidityPoolId "L..." or the pool hash as 64 hex characters
     * @return XdrSCAddress
     */
    public static function forLiquidityPoolId(string $liquidityPoolId) : XdrSCAddress {
        $res = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL());
        $res->liquidityPoolId = $liquidityPoolId;
        return $res;
    }

    /**
     * Returns the StrKey representation of the address.
     * @throws InvalidArgumentException when the address holds an id that has no strkey
     * representation
     * @throws Exception when the address type is unknown
     */
    public function toStrKey() : string {
        switch ($this->type->value) {
            case XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT:
                return $this->accountId->getAccountId();
            case XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT:
                return StrKey::encodeContractIdHex($this->getCanonicalContractIdHex());
            case XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT:
                return $this->muxedAccount->getAccountId();
            case XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE:
                return StrKey::encodeClaimableBalanceIdHex(
                    $this->claimableBalanceId->getCanonicalHashHex()
                );
            case XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL:
                return StrKey::encodeLiquidityPoolIdHex($this->getCanonicalLiquidityPoolIdHex());
        }
        throw new Exception("unknown address type: " . $this->type->value);
    }
}
