<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerKeyTTL
{
    public string $keyHash;

    /**
     * @param string $keyHash
     */
    public function __construct(string $keyHash)
    {
        $this->keyHash = $keyHash;
    }


    public function encode(): string {
        return XdrEncoder::opaqueFixed($this->keyHash, 32);
    }

    public static function decode(XdrBuffer $xdr) : XdrLedgerKeyTTL {
        $hash = $xdr->readOpaqueFixed(32);
        return new XdrLedgerKeyTTL($hash);
    }

    /**
     * @return string
     */
    public function getKeyHash(): string
    {
        return $this->keyHash;
    }

    /**
     * @param string $keyHash
     */
    public function setKeyHash(string $keyHash): void
    {
        $this->keyHash = $keyHash;
    }
}