<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Soneso\StellarSDK\Crypto\CryptoKeyType;
use Soneso\StellarSDK\Crypto\StrKey;

class XdrMuxedAccountMed25519 extends XdrMuxedAccountMed25519Base
{
    public function encodeInverted(): string {
        $bytes = XdrEncoder::unsignedInteger256($this->getEd25519());
        $bytes .= XdrEncoder::unsignedInteger64($this->getId());
        return $bytes;
    }

    public static function decodeInverted(XdrBuffer $xdr) : XdrMuxedAccountMed25519 {
        $ed25519 = $xdr->readUnsignedInteger256();
        $id = $xdr->readUnsignedInteger64();
        return new XdrMuxedAccountMed25519($id, $ed25519);
    }

    /**
     * @return string str key representation of the account id (M...)
     */
    public function getAccountId(): string
    {
        return StrKey::encodeMuxedAccountId($this->encodeInverted());
    }
}
