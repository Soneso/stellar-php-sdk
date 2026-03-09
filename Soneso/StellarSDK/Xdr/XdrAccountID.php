<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Soneso\StellarSDK\Crypto\StrKey;

class XdrAccountID extends XdrAccountIDBase
{
    private string $accountId; // G...

    /**
     * Constructor.
     * @param string $accountId Base32 encoded public key/account id starting with G
     */
    public function __construct(string $accountId)
    {
        $pk = new XdrPublicKey(new XdrPublicKeyType(XdrPublicKeyType::PUBLIC_KEY_TYPE_ED25519));
        parent::__construct($pk);
        $this->accountId = $accountId;
    }

    /**
     * @return string Base32 encoded public key/account id starting with G
     */
    public function getAccountId(): string
    {
        return $this->accountId;
    }

    /**
     * Creates a new XdrAccountID from the passed stellar account id.
     * @param string $accountId Base32 encoded public key/account id starting with G
     * @return XdrAccountID
     */
    public static function fromAccountId(string $accountId) : XdrAccountID {
        return new XdrAccountID($accountId);
    }

    public function encode(): string
    {
        $this->accountID->ed25519 = StrKey::decodeAccountId($this->accountId);
        return parent::encode();
    }

    public static function decode(XdrBuffer $xdr): static {
        $pk = XdrPublicKey::decode($xdr);
        return new static(StrKey::encodeAccountId($pk->ed25519));
    }
}
