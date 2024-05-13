<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrSorobanTransactionMetaExt
{
    public int $discriminant;
    public ?XdrSorobanTransactionMetaExtV1 $v1 = null;

    /**
     * @param int $discriminant
     * @param XdrSorobanTransactionMetaExtV1|null $v1
     */
    public function __construct(int $discriminant, ?XdrSorobanTransactionMetaExtV1 $v1)
    {
        $this->discriminant = $discriminant;
        $this->v1 = $v1;
    }

    public function encode() : string {
        $bytes = XdrEncoder::integer32($this->discriminant);
        switch ($this->discriminant) {
            case 0:
                break;
            case 1:
                $bytes .= $this->v1->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSorobanTransactionMetaExt {
        $v = $xdr->readInteger32();
        $v1 = null;
        switch ($v) {
            case 0:
                break;
            case 1:
                $v1 = XdrSorobanTransactionMetaExtV1::decode($xdr);
                break;

        }
        return new XdrSorobanTransactionMetaExt($v,$v1);
    }

    public function getDiscriminant(): int
    {
        return $this->discriminant;
    }

    public function setDiscriminant(int $discriminant): void
    {
        $this->discriminant = $discriminant;
    }

    public function getV1(): ?XdrSorobanTransactionMetaExtV1
    {
        return $this->v1;
    }

    public function setV1(?XdrSorobanTransactionMetaExtV1 $v1): void
    {
        $this->v1 = $v1;
    }
}