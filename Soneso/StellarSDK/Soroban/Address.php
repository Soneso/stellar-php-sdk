<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Exception;
use RuntimeException;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Represents a single address in the Stellar network.
 * An address can represent an account, contract, muxed account, claimable balance, or liquidity pool.
 *
 * See: https://developers.stellar.org/docs/learn/smart-contract-internals/authorization#address
 */
class Address
{
    public const TYPE_ACCOUNT = 0;
    public const TYPE_CONTRACT = 1;
    public const TYPE_MUXED_ACCOUNT = 2;
    public const TYPE_CLAIMABLE_BALANCE = 3;
    public const TYPE_LIQUIDITY_POOL = 4;

    /**
     * @var int $type type of address. Can be TYPE_ACCOUNT (0), TYPE_CONTRACT (1),
     * TYPE_MUXED_ACCOUNT (2), TYPE_CLAIMABLE_BALANCE (3), TYPE_LIQUIDITY_POOL (4).
     *
     */
    public int $type;

    /**
     * @var string|null $accountId only present if type is TYPE_ACCOUNT (0). ("G...")
     */
    public ?string $accountId = null;

    /**
     * @var string|null $contractId hex representation of the contract id. Only present if type is TYPE_CONTRACT (1).
     * If the StrKey representation is needed ("C..."), it can be encoded with StrKey::encodeContractIdHex($contractId)
     */
    public ?string $contractId = null;

    /**
     * @var string|null $muxedAccountId ("M...") - only present if type is TYPE_MUXED_ACCOUNT (2).
     */
    public ?string $muxedAccountId = null;

    /**
     * @var string|null $claimableBalanceId only present if type is TYPE_CLAIMABLE_BALANCE (3).
     */
    public ?string $claimableBalanceId = null;

    /**
     * @var string|null $liquidityPoolId only present if type is TYPE_LIQUIDITY_POOL (4).
     */
    public ?string $liquidityPoolId = null;

    /**
     * @param int $type type of address. can be one of TYPE_ACCOUNT (0), TYPE_CONTRACT (1),
     * TYPE_MUXED_ACCOUNT (2), TYPE_CLAIMABLE_BALANCE (3), TYPE_LIQUIDITY_POOL (4).
     * @param string|null $accountId required if type is TYPE_ACCOUNT (0), otherwise null
     * @param string|null $contractId hex representation. Required if type is TYPE_CONTRACT (1).
     * @param string|null $muxedAccountId required if type is TYPE_MUXED_ACCOUNT (2), otherwise null
     * @param string|null $claimableBalanceId required if type is TYPE_CLAIMABLE_BALANCE (3), otherwise null
     * @param string|null $liquidityPoolId required if type is TYPE_LIQUIDITY_POOL (4), otherwise null
     *
     * If you have a StrKey representation of the contract id ("C..."),
     * you can decode it to hex with StrKey::decodeContractIdHex($contractId)
     */
    public function __construct(int $type,
                                ?string $accountId = null,
                                ?string $contractId = null,
                                ?string $muxedAccountId = null,
                                ?string $claimableBalanceId = null,
                                ?string $liquidityPoolId = null,
    )
    {
        $this->type = $type;
        $this->accountId = $accountId;
        $this->contractId = $contractId;
        $this->muxedAccountId = $muxedAccountId;
        $this->claimableBalanceId = $claimableBalanceId;
        $this->liquidityPoolId = $liquidityPoolId;
    }

    /**
     * Creates a new instance of Address from the given account id ("G...")
     * @param string $accountId the account id to create the Address object from ("G...")
     * @return Address the created Address object.
     */
    public static function fromAccountId(string $accountId) : Address {
        return new Address(Address::TYPE_ACCOUNT, accountId: $accountId);
    }

    /**
     * Creates a new instance of Address from the given contract id.
     * @param string $contractId hex representation. If you have a str key contract id,
     * you can decode it to hex with StrKey::decodeContractIdHex($contractId)
     * @return Address the created Address object.
     */
    public static function fromContractId(string $contractId) : Address {
        return new Address(Address::TYPE_CONTRACT, contractId: $contractId);
    }

    /**
     * Creates a new instance of Address from the given liquidity pool id.
     * @param string $liquidityPoolId hex representation. If you have a str key liquidity pool id,
     * you can decode it to hex with StrKey::decodeLiquidityPoolIdHex($liquidityPoolId)
     * @return Address the created Address object.
     */
    public static function fromLiquidityPoolId(string $liquidityPoolId) : Address {
        return new Address(Address::TYPE_LIQUIDITY_POOL, liquidityPoolId: $liquidityPoolId);
    }

    /**
     * Creates a new instance of Address from the given claimable balance id.
     * @param string $claimableBalanceId hex representation. If you have a str key claimable balance id,
     * you can decode it to hex with StrKey::decodeClaimableBalanceIdHex($claimableBalanceId)
     * @return Address the created Address object.
     */
    public static function fromClaimableBalanceId(string $claimableBalanceId) : Address {
        return new Address(Address::TYPE_LIQUIDITY_POOL, claimableBalanceId: $claimableBalanceId);
    }

    /**
     * Creates a new instance of Address from the given muxed account id ("M...")
     * @param string $muxedAccountId the muxed account id to create the Address object from ("M...")
     * @return Address the created Address object.
     */
    public static function fromMuxedAccountId(string $muxedAccountId) : Address {
        return new Address(Address::TYPE_MUXED_ACCOUNT, muxedAccountId:$muxedAccountId);
    }

    /**
     * Creates an Address object from the given XdrSCAddress object.
     * @param XdrSCAddress $xdrAddress the xdr object to create the Address object from.
     * @return Address the created Address object.
     */
    public static function fromXdr(XdrSCAddress $xdrAddress) : Address
    {
        if ($xdrAddress->type->value === XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT) {
            return new Address(Address::TYPE_ACCOUNT, accountId: $xdrAddress->accountId->getAccountId());
        } else if ($xdrAddress->type->value === XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT) {
            return new Address(Address::TYPE_CONTRACT, contractId: $xdrAddress->contractId);
        } else if ($xdrAddress->type->value === XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT) {
            return new Address(Address::TYPE_MUXED_ACCOUNT, muxedAccountId: $xdrAddress->muxedAccount->getAccountId());
        } else if ($xdrAddress->type->value === XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE) {
            return new Address(Address::TYPE_CLAIMABLE_BALANCE, claimableBalanceId: $xdrAddress->getClaimableBalanceId());
        } else if ($xdrAddress->type->value === XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL) {
            return new Address(Address::TYPE_LIQUIDITY_POOL, liquidityPoolId: $xdrAddress->getLiquidityPoolId());
        }else {
            throw new RuntimeException("unknown XdrSCAddress type " . $xdrAddress->type->value);
        }
    }

    public static function fromXdrSCVal(XdrSCVal $val) : Address {
        if ($val->type->value === XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT && $val->address !== null) {
            return self::fromXdr($val->address);
        } else {
            throw new RuntimeException("Given XdrSCVal is not of type address.");
        }
    }

    /**
     * Tries to convert a given id to an Address. The given id can be a contract id,
     * an account id, a muxed account id, a claimable balance id, or a liquidity pool id.
     * If not, returns null.
     * @param string $id a contract id, an account id, a muxed account id, a claimable balance id, or a liquidity pool id.
     * @return Address|null The address if could be converted.
     */
    public static function fromAnyId(string $id) : ?Address {
        if (ctype_xdigit($id)) { // is hex string
            try {
                $strKeyContractId = StrKey::encodeContractIdHex($id);
                if (StrKey::isValidContractId($strKeyContractId)) {
                    return Address::fromContractId($id);
                }
            } catch (Exception $e) {}
            try {
                $strKeyLiquidityPoolId = StrKey::encodeLiquidityPoolIdHex($id);
                if (StrKey::isValidLiquidityPoolId($strKeyLiquidityPoolId)) {
                    return Address::fromLiquidityPoolId($id);
                }
            } catch (Exception $e) {}

            try {
                $strKeyClaimableBalanceId = StrKey::encodeClaimableBalanceIdHex($id);
                if (StrKey::isValidClaimableBalanceId($strKeyClaimableBalanceId)) {
                    return Address::fromClaimableBalanceId($id);
                }
            } catch (Exception $e) {}
        } else {
            if (StrKey::isValidAccountId($id)) {
                return Address::fromAccountId($id);
            }
            if (StrKey::isValidMuxedAccountId($id)) {
                return Address::fromMuxedAccountId($id);
            }
            if (StrKey::isValidContractId($id)) {
                return Address::fromContractId(StrKey::decodeContractIdHex($id));
            }
            if (StrKey::isValidClaimableBalanceId($id)) {
                return Address::fromClaimableBalanceId(StrKey::decodeClaimableBalanceIdHex($id));
            }
            if (StrKey::isValidLiquidityPoolId($id)) {
                return Address::fromLiquidityPoolId(StrKey::decodeLiquidityPoolIdHex($id));
            }
        }
        return null;
    }

    /**
     * Returns the StrKey representation of the address.
     * @throws Exception
     */
    public function toStrKey() : string {
        return $this->toXdr()->toStrKey();
    }

    /**
     * Converts this object to its XDR representation.
     * @return XdrSCAddress
     */
    public function toXdr(): XdrSCAddress {
        if ($this->type == Address::TYPE_ACCOUNT) {
            if ($this->accountId != null) {
                return XdrSCAddress::forAccountId($this->accountId);
            } else {
                throw new RuntimeException("accountId is null");
            }
        }
        else if ($this->type == Address::TYPE_CONTRACT) {
            if ($this->contractId != null) {
                return XdrSCAddress::forContractId($this->contractId);
            } else {
                throw new RuntimeException("contractId is null");
            }
        } else if ($this->type == Address::TYPE_MUXED_ACCOUNT) {
            if ($this->muxedAccountId != null) {
                return XdrSCAddress::forAccountId($this->muxedAccountId);
            } else {
                throw new RuntimeException("muxedAccountId is null");
            }
        } else if ($this->type == Address::TYPE_CLAIMABLE_BALANCE) {
            if ($this->claimableBalanceId != null) {
                return XdrSCAddress::forClaimableBalanceId($this->claimableBalanceId);
            } else {
                throw new RuntimeException("claimableBalanceId is null");
            }
        } else if ($this->type == Address::TYPE_LIQUIDITY_POOL) {
            if ($this->liquidityPoolId != null) {
                return XdrSCAddress::forLiquidityPoolId($this->liquidityPoolId);
            } else {
                throw new RuntimeException("liquidityPoolId is null");
            }
        } else {
            throw new RuntimeException("unknown address type " . $this->type);
        }
    }

    /**
     * Converts this object to a XdrSCVal object.
     * @return XdrSCVal
     */
    public function toXdrSCVal() : XdrSCVal {
        return XdrSCVal::forAddress($this->toXdr());
    }

    /**
     * @return int type of address. Can be 0 (account) or 1 (contract).
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type type of address. Can be 0 (account) or 1 (contract).
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null account id, only present if type is 0 (account)
     */
    public function getAccountId(): ?string
    {
        return $this->accountId;
    }

    /**
     * @param string|null $accountId only needed if type is 0 (account)
     */
    public function setAccountId(?string $accountId): void
    {
        $this->accountId = $accountId;
    }

    /**
     * @return string|null contract id as hex, only present if type is 1 (contract)
     */
    public function getContractId(): ?string
    {
        return $this->contractId;
    }

    /**
     * @param string|null $contractId contract id as hex, only required if type is 1 (contract)
     */
    public function setContractId(?string $contractId): void
    {
        $this->contractId = $contractId;
    }

    /**
     * @return string|null
     */
    public function getMuxedAccountId(): ?string
    {
        return $this->muxedAccountId;
    }

    /**
     * @param string|null $muxedAccountId
     */
    public function setMuxedAccountId(?string $muxedAccountId): void
    {
        $this->muxedAccountId = $muxedAccountId;
    }

    /**
     * @return string|null
     */
    public function getClaimableBalanceId(): ?string
    {
        return $this->claimableBalanceId;
    }

    /**
     * @param string|null $claimableBalanceId
     */
    public function setClaimableBalanceId(?string $claimableBalanceId): void
    {
        $this->claimableBalanceId = $claimableBalanceId;
    }

    /**
     * @return string|null
     */
    public function getLiquidityPoolId(): ?string
    {
        return $this->liquidityPoolId;
    }

    /**
     * @param string|null $liquidityPoolId
     */
    public function setLiquidityPoolId(?string $liquidityPoolId): void
    {
        $this->liquidityPoolId = $liquidityPoolId;
    }
}