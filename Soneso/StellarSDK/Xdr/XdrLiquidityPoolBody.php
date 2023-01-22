<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLiquidityPoolBody
{

    public XdrLiquidityPoolType $type;
    public ?XdrConstantProduct $constantProduct = null;

    /**
     * @param XdrLiquidityPoolType $type
     */
    public function __construct(XdrLiquidityPoolType $type)
    {
        $this->type = $type;
    }


    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->getValue()) {
            case XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT:
                $bytes .= $this->constantProduct->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrLiquidityPoolBody {
        $result = new XdrLiquidityPoolBody(XdrLiquidityPoolType::decode($xdr));
        switch ($result->type->getValue()) {
            case XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT:
                $result->constantProduct = XdrConstantProduct::decode($xdr);
                break;
        }
        return $result;
    }

    /**
     * @return XdrLiquidityPoolType
     */
    public function getType(): XdrLiquidityPoolType
    {
        return $this->type;
    }

    /**
     * @param XdrLiquidityPoolType $type
     */
    public function setType(XdrLiquidityPoolType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrConstantProduct|null
     */
    public function getConstantProduct(): ?XdrConstantProduct
    {
        return $this->constantProduct;
    }

    /**
     * @param XdrConstantProduct|null $constantProduct
     */
    public function setConstantProduct(?XdrConstantProduct $constantProduct): void
    {
        $this->constantProduct = $constantProduct;
    }
}