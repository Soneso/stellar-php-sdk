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

class MuxedAccount
{
    private string $ed25519AccountId;
    private ?string $accountId = null;
    private ?int $id;
    private ?XdrMuxedAccount $xdr = null;

    public function __construct(string $ed25519AccountId, ?int $id = null) {
        $firstChar = substr( $ed25519AccountId, 0, 1);
        if ("G" != $firstChar) {
            throw new InvalidArgumentException("ed25519AccountId must start with G");
        }
        $this->ed25519AccountId = $ed25519AccountId;
        $this->id = $id;
    }

    /**
     * @return string
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
     * @return XdrMuxedAccount
     */
    public function getXdr() : XdrMuxedAccount {
        if ($this->xdr == null) {
            $this->xdr = $this->toXdr();
        }
        return $this->toXdr();
    }

    /**
     * @return string
     */
    public function getEd25519AccountId(): string
    {
        return $this->ed25519AccountId;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

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

    public static function fromMed25519AccountId(string $med25519AccountId) : MuxedAccount {
        $bytes =  StrKey::decodeMuxedAccountId($med25519AccountId);
        $xdrBuffer = new XdrBuffer($bytes);
        $muxMed25519 = XdrMuxedAccountMed25519::decodeInverted($xdrBuffer);
        $muxedAccount = new XdrMuxedAccount(null, $muxMed25519);
        return static::fromXdr($muxedAccount);
    }

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