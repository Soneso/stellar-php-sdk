<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrAccountEntryV1Ext
{
    public int $discriminant;
    public ?XdrAccountEntryV2 $v2 = null;

    /**
     * @param int $discriminant
     * @param XdrAccountEntryV2|null $v2
     */
    public function __construct(int $discriminant, ?XdrAccountEntryV2 $v2)
    {
        $this->discriminant = $discriminant;
        $this->v2 = $v2;
    }


    public function encode() : string {
        $bytes = XdrEncoder::integer32($this->discriminant);
        switch ($this->discriminant) {
            case 0:
                break;
            case 2:
                $bytes .= $this->v2->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrAccountEntryV1Ext {
        $v = $xdr->readInteger32();
        $v2 = null;
        switch ($v) {
            case 0:
                break;
            case 2:
                $v2 = XdrAccountEntryV2::decode($xdr);
                break;

        }
        return new XdrAccountEntryV1Ext($v,$v2);
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
     * @return XdrAccountEntryV2|null
     */
    public function getV2(): ?XdrAccountEntryV2
    {
        return $this->v2;
    }

    /**
     * @param XdrAccountEntryV2|null $v2
     */
    public function setV2(?XdrAccountEntryV2 $v2): void
    {
        $this->v2 = $v2;
    }
}