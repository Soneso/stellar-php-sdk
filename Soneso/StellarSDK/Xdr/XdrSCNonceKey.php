<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCNonceKey
{

    public int $nonce;

    /**
     * @param int $nonce
     */
    public function __construct(int $nonce)
    {
        $this->nonce = $nonce;
    }

    public function encode(): string {
        return XdrEncoder::integer64($this->nonce);
    }

    public static function decode(XdrBuffer $xdr):  XdrSCNonceKey {
        return new XdrSCNonceKey($xdr->readInteger64());
    }

    /**
     * @return int
     */
    public function getNonce(): int
    {
        return $this->nonce;
    }

    /**
     * @param int $nonce
     */
    public function setNonce(int $nonce): void
    {
        $this->nonce = $nonce;
    }
}