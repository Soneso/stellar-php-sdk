<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerKeyContractCode
{
    public string $hash;

    /**
     * @param string $hash
     */
    public function __construct(string $hash)
    {
        $this->hash = $hash;
    }


    public function encode(): string {
        return XdrEncoder::opaqueFixed($this->hash, 32);
    }

    public static function decode(XdrBuffer $xdr) : XdrLedgerKeyContractCode {
        $hash = $xdr->readOpaqueFixed(32);
        return new XdrLedgerKeyContractCode($hash);
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @param string $hash
     */
    public function setHash(string $hash): void
    {
        $this->hash = $hash;
    }
}