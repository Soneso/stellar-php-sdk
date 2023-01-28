<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrClaimableBalanceEntryExtV1
{
    public int $discriminant;
    public int $flags;

    /**
     * @param int $discriminant
     * @param int $flags
     */
    public function __construct(int $discriminant, int $flags)
    {
        $this->discriminant = $discriminant;
        $this->flags = $flags;
    }


    public function encode() : string {
        $bytes = XdrEncoder::integer32($this->discriminant);
        $bytes .= XdrEncoder::unsignedInteger32($this->flags);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrClaimableBalanceEntryExtV1 {
        $v = $xdr->readInteger32();
        $flags = $xdr->readUnsignedInteger32();
        return new XdrClaimableBalanceEntryExtV1($v, $flags);
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
     * @return int
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * @param int $flags
     */
    public function setFlags(int $flags): void
    {
        $this->flags = $flags;
    }
}