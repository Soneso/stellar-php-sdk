<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrSigner
{
    private XdrSignerKey $key;
    private int $weight; //uint32

    public function __construct(XdrSignerKey $key, int $weight) {
        $this->key = $key;
        $this->weight = $weight;
    }

    /**
     * @return XdrSignerKey
     */
    public function getKey(): XdrSignerKey
    {
        return $this->key;
    }

    /**
     * @return int
     */
    public function getWeight(): int
    {
        return $this->weight;
    }

    public function encode(): string {
        $bytes = $this->key->encode();
        $bytes .= XdrEncoder::unsignedInteger32($this->weight);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSigner {
        $key = XdrSignerKey::decode($xdr);
        $weight = $xdr->readUnsignedInteger32();
        return new XdrSigner($key, $weight);
    }

}