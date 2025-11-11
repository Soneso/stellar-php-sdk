<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\CryptoKeyType;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Crypto\StrKey;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrMuxedAccount;
use Soneso\StellarSDK\Xdr\XdrMuxedAccountMed25519;

/**
 * Represents a multiplexed Stellar account
 *
 * Multiplexed accounts (also known as muxed accounts) allow multiple virtual accounts
 * to share a single Stellar account. This is useful for exchanges, payment processors,
 * and other services that need to differentiate between multiple users or sub-accounts
 * while using the same underlying Stellar account.
 *
 * A muxed account consists of:
 * - The underlying Ed25519 account ID (G-address)
 * - An optional 64-bit ID that differentiates virtual accounts
 *
 * When the ID is present, the account is encoded as an M-address. When absent,
 * it's a regular G-address.
 *
 * Usage:
 * <code>
 * // Create a regular account (no ID)
 * $muxed = new MuxedAccount("GABC...");
 *
 * // Create a multiplexed account with ID
 * $muxed = new MuxedAccount("GABC...", 12345);
 *
 * // Get the account ID (M-address if ID present, G-address otherwise)
 * $accountId = $muxed->getAccountId();
 *
 * // Parse from account ID string
 * $muxed = MuxedAccount::fromAccountId("MABC...");
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see Account For account with sequence number management
 * @see https://developers.stellar.org/docs/encyclopedia/muxed-accounts
 * @since 1.0.0
 */
class MuxedAccount
{
    private string $ed25519AccountId;
    private ?string $accountId = null;
    private ?int $id;
    private ?XdrMuxedAccount $xdr = null;

    /**
     * Constructs a new MuxedAccount instance
     *
     * @param string $ed25519AccountId The Ed25519 account ID (G-address)
     * @param int|null $id Optional 64-bit ID for multiplexing (creates M-address when set)
     * @throws InvalidArgumentException If the account ID does not start with 'G'
     */
    public function __construct(string $ed25519AccountId, ?int $id = null) {
        $firstChar = substr( $ed25519AccountId, 0, 1);
        if ("G" != $firstChar) {
            throw new InvalidArgumentException("ed25519AccountId must start with G");
        }
        $this->ed25519AccountId = $ed25519AccountId;
        $this->id = $id;
    }

    /**
     * Gets the account ID as a string
     *
     * Returns an M-address if the account has a muxed ID, otherwise returns
     * the underlying G-address.
     *
     * @return string The account ID (M-address or G-address)
     */
    public function getAccountId(): string
    {
        if ($this->accountId == null) {
            $xdrMuxedAccount = $this->getXdr();
            if ($xdrMuxedAccount->getDiscriminant() == CryptoKeyType::KEY_TYPE_MUXED_ED25519) {
                $bytes = $xdrMuxedAccount->getMed25519()->encodeInverted();
                $this->accountId = StrKey::encodeMuxedAccountId($bytes);
            } else {
                $this->accountId = $this->ed25519AccountId;
            }
        }
        return $this->accountId;
    }

    /**
     * Gets the XDR representation of the muxed account
     *
     * @return XdrMuxedAccount The XDR muxed account
     */
    public function getXdr() : XdrMuxedAccount {
        if ($this->xdr == null) {
            $this->xdr = $this->toXdr();
        }
        return $this->toXdr();
    }

    /**
     * Gets the underlying Ed25519 account ID
     *
     * @return string The Ed25519 account ID (G-address)
     */
    public function getEd25519AccountId(): string
    {
        return $this->ed25519AccountId;
    }

    /**
     * Gets the muxed account ID
     *
     * @return int|null The 64-bit ID, or null if not a multiplexed account
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Creates a MuxedAccount from an account ID string
     *
     * Accepts both G-addresses (regular accounts) and M-addresses (multiplexed accounts).
     *
     * @param string $accountId The account ID (G-address or M-address)
     * @return MuxedAccount The created MuxedAccount instance
     * @throws InvalidArgumentException If the account ID format is invalid
     */
    public static function fromAccountId(string $accountId) : MuxedAccount {
        $firstChar = substr( $accountId, 0, 1);
        if ("G" == $firstChar) {
            return new MuxedAccount($accountId);
        } else if ("M" == $firstChar) {
            return static::fromMed25519AccountId($accountId);
        } else {
            throw new InvalidArgumentException("invalid accountId: " . $accountId);
        }
    }

    /**
     * Creates a MuxedAccount from a MED25519 account ID (M-address)
     *
     * @param string $med25519AccountId The M-address to decode
     * @return MuxedAccount The created MuxedAccount instance
     */
    public static function fromMed25519AccountId(string $med25519AccountId) : MuxedAccount {
        $bytes =  StrKey::decodeMuxedAccountId($med25519AccountId);
        $xdrBuffer = new XdrBuffer($bytes);
        $muxMed25519 = XdrMuxedAccountMed25519::decodeInverted($xdrBuffer);
        $muxedAccount = new XdrMuxedAccount(null, $muxMed25519);
        return static::fromXdr($muxedAccount);
    }

    /**
     * Creates a MuxedAccount from XDR format
     *
     * @param XdrMuxedAccount $muxedAccount The XDR muxed account
     * @return MuxedAccount The created MuxedAccount instance
     * @throws InvalidArgumentException If the XDR discriminant is invalid
     */
    public static function fromXdr(XdrMuxedAccount $muxedAccount) : MuxedAccount {
        if ($muxedAccount->getDiscriminant() == CryptoKeyType::KEY_TYPE_MUXED_ED25519) {
            $ed25519AccountId = StrKey::encodeAccountId($muxedAccount->getMed25519()->getEd25519());
            $id = $muxedAccount->getMed25519()->getId();
            return new MuxedAccount($ed25519AccountId, $id);
        } else if ($muxedAccount->getDiscriminant() == CryptoKeyType::KEY_TYPE_ED25519) {
            $ed25519AccountId = StrKey::encodeAccountId($muxedAccount->getEd25519());
            return new MuxedAccount($ed25519AccountId, null);
        } else {
            throw new InvalidArgumentException("invalid discriminant: ". $muxedAccount->getDiscriminant() . "in muxed account parameter");
        }
    }

    /**
     * Converts the muxed account to XDR format
     *
     * @return XdrMuxedAccount The XDR representation
     */
    public function toXdr() : XdrMuxedAccount {
        if (!$this->id) {
            return KeyPair::fromAccountId($this->ed25519AccountId)->getXdrMuxedAccount();
        } else {
            $bytes = StrKey::decodeAccountId($this->ed25519AccountId);
            $muxMed25519 = new XdrMuxedAccountMed25519($this->id, $bytes);
            return new XdrMuxedAccount(null, $muxMed25519);
        }
    }
}