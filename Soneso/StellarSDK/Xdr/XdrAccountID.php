<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Soneso\StellarSDK\Crypto\CryptoKeyType;
use Soneso\StellarSDK\Crypto\StrKey;

class XdrAccountID
{
    private string $accountId; // G...

    /**
     * Constructor.
     * @param string $accountId Base32 encoded public key/account id starting with G
     */
    public function __construct(string $accountId)
    {
        $this->accountId = $accountId;
    }

    /**
     * @return string
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
        $bytes = XdrEncoder::integer32(CryptoKeyType::KEY_TYPE_ED25519);
        $bytes .= XdrEncoder::opaqueFixed(StrKey::decodeAccountId($this->accountId));
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrAccountID {
        $type = $xdr->readInteger32();
        $accountIdBytes = $xdr->readOpaqueFixed(32);
        $accountId = StrKey::encodeAccountId($accountIdBytes);
        if ($type == CryptoKeyType::KEY_TYPE_ED25519) {
            return new XdrAccountID($accountId);
        } else {
            throw new \InvalidArgumentException("invalid type for accountid : ".$type);
        }
    }
}