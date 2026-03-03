<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrClaimableBalanceEntryExt
{
    public int $discriminant;
    public ?XdrClaimableBalanceEntryExtV1 $v1 = null;

    /**
     * @param int $discriminant
     * @param XdrClaimableBalanceEntryExtV1|null $v1
     */
    public function __construct(int $discriminant, ?XdrClaimableBalanceEntryExtV1 $v1)
    {
        $this->discriminant = $discriminant;
        $this->v1 = $v1;
    }


    public function encode() : string {
        $bytes = XdrEncoder::integer32($this->discriminant);
        if ($this->v1 !== null) {
            $bytes .= $this->v1->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrClaimableBalanceEntryExt {
        $v = $xdr->readInteger32();
        $flags = null;
        if ($v == 1) {
            $flags = XdrClaimableBalanceEntryExtV1::decode($xdr);
        }
        return new XdrClaimableBalanceEntryExt($v, $flags);
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
}