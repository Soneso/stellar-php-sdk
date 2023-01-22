<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrAccountEntryV2Ext
{
    public int $discriminant;
    public ?XdrAccountEntryV3 $v3 = null;

    /**
     * @param int $discriminant
     * @param XdrAccountEntryV3|null $v3
     */
    public function __construct(int $discriminant, ?XdrAccountEntryV3 $v3)
    {
        $this->discriminant = $discriminant;
        $this->v3 = $v3;
    }


    public function encode() : string {
        $bytes = XdrEncoder::integer32($this->discriminant);
        switch ($this->discriminant) {
            case 0:
                break;
            case 3:
                $bytes .= $this->v3->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrAccountEntryV2Ext {
        $v = $xdr->readInteger32();
        $v3 = null;
        switch ($v) {
            case 0:
                break;
            case 3:
                $v3 = XdrAccountEntryV3::decode($xdr);
                break;

        }
        return new XdrAccountEntryV2Ext($v,$v3);
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
     * @return XdrAccountEntryV3|null
     */
    public function getV3(): ?XdrAccountEntryV3
    {
        return $this->v3;
    }

    /**
     * @param XdrAccountEntryV3|null $v3
     */
    public function setV3(?XdrAccountEntryV3 $v3): void
    {
        $this->v3 = $v3;
    }

}