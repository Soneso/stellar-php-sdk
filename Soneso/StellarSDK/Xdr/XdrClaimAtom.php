<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrClaimAtom
{
    private XdrClaimAtomType $type;
    private ?XdrClaimOfferAtomV0 $v0 = null;
    private ?XdrClaimOfferAtom $orderBook = null;
    private ?XdrClaimLiquidityAtom $liquidityPool = null;

    /**
     * @return XdrClaimAtomType
     */
    public function getType(): XdrClaimAtomType
    {
        return $this->type;
    }

    /**
     * @param XdrClaimAtomType $type
     */
    public function setType(XdrClaimAtomType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrClaimOfferAtomV0|null
     */
    public function getV0(): ?XdrClaimOfferAtomV0
    {
        return $this->v0;
    }

    /**
     * @param XdrClaimOfferAtomV0|null $v0
     */
    public function setV0(?XdrClaimOfferAtomV0 $v0): void
    {
        $this->v0 = $v0;
    }

    /**
     * @return XdrClaimOfferAtom|null
     */
    public function getOrderBook(): ?XdrClaimOfferAtom
    {
        return $this->orderBook;
    }

    /**
     * @param XdrClaimAtom|null $orderBook
     */
    public function setOrderBook(?XdrClaimAtom $orderBook): void
    {
        $this->orderBook = $orderBook;
    }

    /**
     * @return XdrClaimLiquidityAtom|null
     */
    public function getLiquidityPool(): ?XdrClaimLiquidityAtom
    {
        return $this->liquidityPool;
    }

    /**
     * @param XdrClaimLiquidityAtom|null $liquidityPool
     */
    public function setLiquidityPool(?XdrClaimLiquidityAtom $liquidityPool): void
    {
        $this->liquidityPool = $liquidityPool;
    }

    public function encode() : string {
        $bytes = $this->type->encode();
        $bytes .= match ($this->type->getValue()) {
            XdrClaimAtomType::V0 => $this->v0->encode(),
            XdrClaimAtomType::ORDER_BOOK => $this->orderBook->encode(),
            XdrClaimAtomType::LIQUIDITY_POOL => $this->liquidityPool->encode(),
        };
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrClaimAtom {
        $type = XdrClaimAtomType::decode($xdr);
        $decoded = new XdrClaimAtom();
        $decoded->type = $type;
        switch ($type->getValue()) {
            case XdrClaimAtomType::V0:
                $decoded->v0 = XdrClaimOfferAtomV0::decode($xdr);
                break;
            case XdrClaimAtomType::ORDER_BOOK:
                $decoded->orderBook = XdrClaimOfferAtom::decode($xdr);
                break;
            case XdrClaimAtomType::LIQUIDITY_POOL:
                $decoded->liquidityPool = XdrClaimLiquidityAtom::decode($xdr);
                break;
        }
        return $decoded;
    }
}