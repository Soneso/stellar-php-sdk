<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use RuntimeException;
use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Represents a single address in the Stellar network.
 * An address can represent an account or a contract.
 *
 * See: https://developers.stellar.org/docs/learn/smart-contract-internals/authorization#address
 */
class Address
{
    public const TYPE_ACCOUNT = 0;
    public const TYPE_CONTRACT = 1;

    /**
     * @var int $type type of address. Can be 0 (account) or 1 (contract).
     */
    public int $type;

    /**
     * @var string|null $accountId only present if type is 0 (account)
     */
    public ?string $accountId = null;

    /**
     * @var string|null $contractId hex representation of the contract id. Only present if type is 1 (contract).
     * If the StrKey representation is needed ("C..."), it can be encoded with StrKey::encodeContractIdHex($contractId)
     */
    public ?string $contractId = null;

    /**
     * @param int $type type of address. can be 0 (account) or 1 (contract).
     * @param string|null $accountId required if type is 0 (account), otherwise null
     * @param string|null $contractId hex representation. required if type is 1 (contract).
     * If you have a StrKey representation of the contract id ("C..."),
     * you can decode it to hex with StrKey::decodeContractIdHex($contractId)
     */
    public function __construct(int $type, ?string $accountId = null, ?string $contractId = null)
    {
        $this->type = $type;
        $this->accountId = $accountId;
        $this->contractId = $contractId;
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
        } else {
            throw new RuntimeException("unknown XdrSCAddress type " . $xdrAddress->type->value);
        }
    }

    /**
     * Converts this object to its XDR representation.
     * @return XdrSCAddress
     */
    public function toXdr(): XdrSCAddress {
        if ($this->type == Address::TYPE_ACCOUNT) {
            if ($this->accountId != null) {
                $xdr = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT());
                $xdr->accountId = XdrAccountID::fromAccountId($this->accountId);
                return $xdr;
            } else {
                throw new RuntimeException("accountId is null");
            }
        }
        else if ($this->type == Address::TYPE_CONTRACT) {
            if ($this->contractId != null) {
                $xdr = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT());
                $xdr->contractId = $this->contractId;
                return $xdr;
            } else {
                throw new RuntimeException("contractId is null");
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
}