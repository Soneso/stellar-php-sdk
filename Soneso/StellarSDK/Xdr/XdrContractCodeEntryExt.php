<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractCodeEntryExt
{
    public int $discriminant;
    public ?XdrContractCodeEntryExtV1 $v1 = null;

    /**
     * @param int $discriminant
     * @param XdrContractCodeEntryExtV1|null $v1
     */
    public function __construct(int $discriminant, ?XdrContractCodeEntryExtV1 $v1)
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

    public static function decode(XdrBuffer $xdr) : XdrContractCodeEntryExt {
        $v = $xdr->readInteger32();
        $v1 = null;
        switch ($v) {
            case 0:
                break;
            case 1:
                $v1 = XdrContractCodeEntryExtV1::decode($xdr);
                break;

        }
        return new XdrContractCodeEntryExt($v,$v1);
    }

    /**
     * @return int
     */
    public function getDiscriminant(): int
    {
        return $this->discriminant;
    }

    /**
     * @param int $discriminant
     */
    public function setDiscriminant(int $discriminant): void
    {
        $this->discriminant = $discriminant;
    }

    /**
     * @return XdrContractCodeEntryExtV1|null
     */
    public function getV1(): ?XdrContractCodeEntryExtV1
    {
        return $this->v1;
    }

    /**
     * @param XdrContractCodeEntryExtV1|null $v1
     */
    public function setV1(?XdrContractCodeEntryExtV1 $v1): void
    {
        $this->v1 = $v1;
    }

}