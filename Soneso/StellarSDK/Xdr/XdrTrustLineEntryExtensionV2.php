<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTrustLineEntryExtensionV2
{
    public int $discriminant;
    public int $liquidityPoolUseCount;

    /**
     * @param int $discriminant
     * @param int $liquidityPoolUseCount
     */
    public function __construct(int $discriminant, int $liquidityPoolUseCount)
    {
        $this->discriminant = $discriminant;
        $this->liquidityPoolUseCount = $liquidityPoolUseCount;
    }


    public function encode() : string {
        $bytes = XdrEncoder::integer32($this->liquidityPoolUseCount);
        $bytes .= XdrEncoder::integer32($this->discriminant);
        switch ($this->discriminant) {
            case 0:
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrTrustLineEntryExtensionV2 {
        $liquidityPoolUseCount = $xdr->readInteger32();
        $v = $xdr->readInteger32();
        switch ($v) {
            case 0:
                break;
        }
        return new XdrTrustLineEntryExtensionV2($v, $liquidityPoolUseCount);
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
    public function getLiquidityPoolUseCount(): int
    {
        return $this->liquidityPoolUseCount;
    }

    /**
     * @param int $liquidityPoolUseCount
     */
    public function setLiquidityPoolUseCount(int $liquidityPoolUseCount): void
    {
        $this->liquidityPoolUseCount = $liquidityPoolUseCount;
    }

}