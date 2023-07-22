<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Soroban;

use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;
use Soneso\StellarSDK\Xdr\XdrSCVal;

/**
 * Represents a single address in the Stellar network.
 * An address can represent an account or a contract.
 */
class Address
{
    public const TYPE_ACCOUNT = 0;
    public const TYPE_CONTRACT = 1;

    public int $type;
    public ?string $accountId = null;
    public ?string $contractId = null; // hex

    /**
     * @param int $type
     * @param string|null $accountId
     * @param string|null $contractId
     */
    public function __construct(int $type, ?string $accountId = null, ?string $contractId = null)
    {
        $this->type = $type;
        $this->accountId = $accountId;
        $this->contractId = $contractId;
    }

    public static function fromAccountId(string $accountId) {
        return new Address(Address::TYPE_ACCOUNT, accountId: $accountId);
    }

    public static function fromContractId(string $contractId) {
        return new Address(Address::TYPE_CONTRACT, contractId: $contractId);
    }

    public static function fromXdr(XdrSCAddress $xdrAddress) {
        switch ($xdrAddress->type->value) {
            case XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT:
                return new Address(Address::TYPE_ACCOUNT, accountId: $xdrAddress->accountId->getAccountId());
            case XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT:
                return new Address(Address::TYPE_CONTRACT, contractId: $xdrAddress->contractId);
        }
    }

    public function toXdr(): XdrSCAddress {
        if ($this->type == Address::TYPE_ACCOUNT) {
            if ($this->accountId != null) {
                $xdr = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT());
                $xdr->accountId = XdrAccountID::fromAccountId($this->accountId);
                return $xdr;
            } else {
                throw new \RuntimeException("accountId is null");
            }
        }
        else if ($this->type == Address::TYPE_CONTRACT) {
            if ($this->contractId != null) {
                $xdr = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT());
                $xdr->contractId = $this->contractId;
                return $xdr;
            } else {
                throw new \RuntimeException("contractId is null");
            }
        } else {
            throw new \RuntimeException("unknown address type " . $this->type);
        }
    }

    public function toXdrSCVal() : XdrSCVal {
        return XdrSCVal::forAddress($this->toXdr());
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getAccountId(): ?string
    {
        return $this->accountId;
    }

    /**
     * @param string|null $accountId
     */
    public function setAccountId(?string $accountId): void
    {
        $this->accountId = $accountId;
    }

    /**
     * @return string|null
     */
    public function getContractId(): ?string
    {
        return $this->contractId;
    }

    /**
     * @param string|null $contractId
     */
    public function setContractId(?string $contractId): void
    {
        $this->contractId = $contractId;
    }
}