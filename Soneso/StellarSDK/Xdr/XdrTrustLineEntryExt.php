<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrTrustLineEntryExt
{
    public int $discriminant;
    public ?XdrTrustLineEntryV1 $v1 = null;

    /**
     * @param int $discriminant
     * @param XdrTrustLineEntryV1|null $v1
     */
    public function __construct(int $discriminant, ?XdrTrustLineEntryV1 $v1)
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

    public static function decode(XdrBuffer $xdr) : XdrTrustLineEntryExt {
        $v = $xdr->readInteger32();
        $v1 = null;
        switch ($v) {
            case 0:
                break;
            case 1:
                $v1 = XdrTrustLineEntryV1::decode($xdr);
                break;

        }
        return new XdrTrustLineEntryExt($v,$v1);
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
     * @return XdrTrustLineEntryV1|null
     */
    public function getV1(): ?XdrTrustLineEntryV1
    {
        return $this->v1;
    }

    /**
     * @param XdrTrustLineEntryV1|null $v1
     */
    public function setV1(?XdrTrustLineEntryV1 $v1): void
    {
        $this->v1 = $v1;
    }

}