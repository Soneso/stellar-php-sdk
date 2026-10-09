<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Soroban\Address;
use Soneso\StellarSDK\Xdr\XdrMuxedContract;
use Soneso\StellarSDK\Xdr\XdrSCAddress;
use Soneso\StellarSDK\Xdr\XdrSCAddressType;

/**
 * A contract, optionally paired with a 64-bit multiplexing id (CAP-0084).
 *
 * With an id the pair renders as a muxed contract address, a "W..." strkey whose
 * payload is the 32-byte contract id followed by the big-endian id (SEP-23). Without
 * an id it renders as the contract address ("C..."). A muxed contract address can
 * receive a Stellar Asset Contract transfer; it is never a Soroban auth address.
 *
 * Ids above PHP_INT_MAX are held as negative ints, the two's complement form the
 * SDK's uint64 XDR fields use.
 *
 * Usage:
 * <code>
 * $muxed = new MuxedContract("CA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAXE", 123456);
 * // WA3D5KRYM6CB7OWQ6TWYRR3Z4T7GNZLKERYNZGGA5SOAOPIFY6YQGAAAAAAAAAPCIA6IG
 * $address = $muxed->getAddress();
 *
 * $parsed = MuxedContract::fromAddress($address);
 * $parsed->getContractId(); // "CA3D5KRY..."
 * $parsed->getId(); // 123456
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see MuxedAccount For the account counterpart
 * @see https://github.com/stellar/stellar-protocol/blob/master/core/cap-0084.md CAP-0084
 */
class MuxedContract
{
    private string $contractId;
    private ?int $id;

    /**
     * Pairs a contract with a multiplexing id.
     *
     * @param string $contractId the contract as a "C..." strkey or as its 32-byte hash in hexadecimal
     * @param int|null $id the multiplexing id; null for the contract on its own. 0 is a valid id.
     * @throws InvalidArgumentException if $contractId is neither a valid "C..." strkey nor
     * 64 hexadecimal characters
     */
    public function __construct(string $contractId, ?int $id = null) {
        try {
            $contractIdHex = XdrSCAddress::forContractId($contractId)->getCanonicalContractIdHex();
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(
                'Invalid contract id "' . $contractId . '": ' . $e->getMessage(), 0, $e
            );
        }
        $this->contractId = StrKey::encodeContractIdHex($contractIdHex);
        $this->id = $id;
    }

    /**
     * Creates a MuxedContract from a contract address ("C...", no id) or a muxed contract
     * address ("W...").
     *
     * @param string $address the "C..." or "W..." strkey
     * @return MuxedContract the decoded contract and id
     * @throws InvalidArgumentException if $address is neither a valid "C..." nor a valid "W..." strkey
     */
    public static function fromAddress(string $address) : MuxedContract {
        if (StrKey::isValidContractId($address)) {
            return new MuxedContract($address);
        }
        if (StrKey::isValidMuxedContractId($address)) {
            return self::fromXdrSCAddress(XdrSCAddress::forMuxedContractId($address));
        }
        throw new InvalidArgumentException(
            'Expected a contract (C...) or muxed contract (W...) address, got: ' . $address
        );
    }

    /**
     * Creates a MuxedContract from the contract or the muxed contract arm of an SCAddress.
     *
     * @param XdrSCAddress $address the XDR address
     * @return MuxedContract the contract, with the id of the muxed contract arm
     * @throws InvalidArgumentException if the address holds another arm, naming the arm
     */
    public static function fromXdrSCAddress(XdrSCAddress $address) : MuxedContract {
        switch ($address->type->value) {
            case XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT:
                return new MuxedContract($address->getCanonicalContractIdHex());
            case XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_CONTRACT:
                $muxedContract = $address->muxedContract;
                return new MuxedContract(bin2hex($muxedContract->contractId), $muxedContract->id);
        }
        throw new InvalidArgumentException(
            'Expected the contract or muxed contract arm of an SCAddress, got ' . $address->type->enumName()
        );
    }

    /**
     * Returns the contract address.
     *
     * @return string the "C..." strkey of the contract
     */
    public function getContractId(): string
    {
        return $this->contractId;
    }

    /**
     * Returns the multiplexing id.
     *
     * @return int|null the id, or null for the contract on its own
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Returns the muxed contract address ("W...") when an id is set, else the contract
     * address ("C...").
     *
     * @return string the strkey of this address
     */
    public function getAddress(): string
    {
        return $this->toXdrSCAddress()->toStrKey();
    }

    /**
     * Converts this object to an SCAddress: the muxed contract arm when an id is set,
     * else the contract arm.
     *
     * @return XdrSCAddress the XDR address
     */
    public function toXdrSCAddress(): XdrSCAddress
    {
        if ($this->id === null) {
            return XdrSCAddress::forContractId($this->contractId);
        }
        $address = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_CONTRACT());
        $address->muxedContract = new XdrMuxedContract($this->id, StrKey::decodeContractId($this->contractId));
        return $address;
    }

    /**
     * Converts this object to a Soroban Address: of type Address::TYPE_MUXED_CONTRACT when
     * an id is set, else of type Address::TYPE_CONTRACT.
     *
     * @return Address the Soroban address
     */
    public function toAddress(): Address
    {
        return Address::fromXdr($this->toXdrSCAddress());
    }
}
