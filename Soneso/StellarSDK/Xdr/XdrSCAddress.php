<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Exception;
use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\StrKey;

class XdrSCAddress extends XdrSCAddressBase
{
    /**
     * Length of an "L..." liquidity pool strkey.
     */
    private const LIQUIDITY_POOL_STRKEY_LENGTH = 56;

    /**
     * Length of a 32-byte liquidity pool id in hexadecimal characters.
     */
    private const LIQUIDITY_POOL_HEX_LENGTH = 64;

    /**
     * Length of a "C..." contract strkey.
     */
    private const CONTRACT_STRKEY_LENGTH = 56;

    /**
     * Length of a 32-byte contract id in hexadecimal characters.
     */
    private const CONTRACT_HEX_LENGTH = 64;

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

    public function encode(): string {
        switch ($this->type->value) {
            case XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT:
                $bytes = $this->type->encode();
                $bytes .= XdrEncoder::opaqueFixed(
                    pack("H*", $this->getCanonicalContractIdHex()),
                    32
                );
                return $bytes;
            case XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL:
                $bytes = $this->type->encode();
                $bytes .= XdrEncoder::opaqueFixed(
                    pack("H*", $this->getCanonicalLiquidityPoolIdHex()),
                    32
                );
                return $bytes;
            default:
                // ACCOUNT, MUXED_ACCOUNT, CLAIMABLE_BALANCE are handled by the base.
                return parent::encode();
        }
    }

    public static function decode(XdrBuffer $xdr): static {
        $result = new static(XdrSCAddressType::decode($xdr));
        switch ($result->type->getValue()) {
            case XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT:
                $result->accountId = XdrAccountID::decode($xdr);
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT:
                $result->muxedAccount = XdrMuxedAccountMed25519::decode($xdr);
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT:
                $result->contractId = bin2hex($xdr->readOpaqueFixed(32));
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE:
                $result->claimableBalanceId = XdrClaimableBalanceID::decode($xdr);
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL:
                $result->liquidityPoolId = bin2hex($xdr->readOpaqueFixed(32));
                break;
        }
        return $result;
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

    /**
     * Returns the 32-byte contract id as 64 lower case hexadecimal characters,
     * whichever of the accepted spellings the field holds.
     *
     * A contract id is accepted as its strkey form "C..." or as the bare hash in
     * hexadecimal. Encoding and toStrKey() both resolve the field through here, so
     * neither takes what the other refuses.
     *
     * @return string the contract id as 64 lower case hexadecimal characters
     * @throws InvalidArgumentException when the field is unset, holds a string that is
     * neither a "C..." strkey nor hexadecimal, or has a length matching neither shape
     */
    private function getCanonicalContractIdHex(): string {
        $value = $this->contractId;
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('Contract id is not set');
        }
        if (strlen($value) === self::CONTRACT_STRKEY_LENGTH && $value[0] === 'C') {
            // The base32 and hexadecimal alphabets share A-F and 2-7, so a string of
            // this length and shape can read as either. The strkey reading is taken
            // first because it is the only one that can succeed: 56 characters is not
            // the length of the hexadecimal spelling. A value that fails it and is
            // hexadecimal falls to the hex rules below, which name the rule it breaks.
            try {
                return StrKey::decodeContractIdHex($value);
            } catch (InvalidArgumentException $e) {
                if (!ctype_xdigit($value)) {
                    throw $e;
                }
            }
        }
        if (!ctype_xdigit($value)) {
            throw new InvalidArgumentException(
                'Contract id must be a "C..." strkey or a hexadecimal string,'
                . ' "' . XdrJsonHelper::safePreview($value) . '" given'
            );
        }
        if (strlen($value) !== self::CONTRACT_HEX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Contract id must be %d characters as a "C..." strkey, or the contract'
                    . ' hash as %d hexadecimal characters; %d characters given',
                self::CONTRACT_STRKEY_LENGTH,
                self::CONTRACT_HEX_LENGTH,
                strlen($value)
            ));
        }
        // Hexadecimal is case insensitive, so the canonical spelling is lower case and
        // every reader of the contract id reports the same string for either spelling.
        return strtolower($value);
    }

    /**
     * Returns the 32-byte liquidity pool id as 64 lower case hexadecimal characters,
     * whichever of the accepted spellings the field holds.
     *
     * A pool id is accepted as its strkey form "L..." or as the bare hash in
     * hexadecimal. PoolID is a plain 32-byte hash on the wire with no discriminant
     * ahead of it, so there is no prefixed spelling to accept. Encoding and toStrKey()
     * both resolve the field through here, so neither takes what the other refuses.
     *
     * @return string the pool id as 64 lower case hexadecimal characters
     * @throws InvalidArgumentException when the field is unset, holds a string that is
     * neither an "L..." strkey nor hexadecimal, or has a length matching neither shape
     */
    private function getCanonicalLiquidityPoolIdHex(): string {
        $value = $this->liquidityPoolId;
        if ($value === null || $value === '') {
            throw new InvalidArgumentException('Liquidity pool id is not set');
        }
        if (strlen($value) === self::LIQUIDITY_POOL_STRKEY_LENGTH && $value[0] === 'L') {
            // Requiring the version letter here leaves a hexadecimal id of the same
            // length to the hex branches below, which name the rule it breaks.
            return StrKey::decodeLiquidityPoolIdHex($value);
        }
        if (!ctype_xdigit($value)) {
            throw new InvalidArgumentException(
                'Liquidity pool id must be an "L..." strkey or a hexadecimal string,'
                . ' "' . XdrJsonHelper::safePreview($value) . '" given'
            );
        }
        if (strlen($value) !== self::LIQUIDITY_POOL_HEX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'Liquidity pool id must be %d characters as an "L..." strkey, or the pool'
                    . ' hash as %d hexadecimal characters; %d characters given',
                self::LIQUIDITY_POOL_STRKEY_LENGTH,
                self::LIQUIDITY_POOL_HEX_LENGTH,
                strlen($value)
            ));
        }
        // Hexadecimal is case insensitive, so the canonical spelling is lower case and
        // every reader of the pool id reports the same string for either spelling.
        return strtolower($value);
    }
}
