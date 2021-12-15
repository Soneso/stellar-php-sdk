<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrLiquidityPoolParameters
{
    private XdrLiquidityPoolType $type;
    private ?XdrLiquidityPoolConstantProductParameters $constantProduct = null;

    public function __construct(XdrLiquidityPoolType $type, ?XdrLiquidityPoolConstantProductParameters $constantProduct = null) {
        $this->type = $type;
        $this->constantProduct = $constantProduct;
    }

    /**
     * @return XdrLiquidityPoolType
     */
    public function getType(): XdrLiquidityPoolType
    {
        return $this->type;
    }

    /**
     * @return XdrLiquidityPoolConstantProductParameters|null
     */
    public function getConstantProduct(): ?XdrLiquidityPoolConstantProductParameters
    {
        return $this->constantProduct;
    }

    /**
     * @param XdrLiquidityPoolConstantProductParameters|null $constantProduct
     */
    public function setConstantProduct(?XdrLiquidityPoolConstantProductParameters $constantProduct): void
    {
        $this->constantProduct = $constantProduct;
    }

    public function encode() : string {
        $bytes = $this->type->encode();
        if ($this->type->getValue() == XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT
            && $this->constantProduct != null) {
            $bytes .= $this->constantProduct->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrLiquidityPoolParameters {
        $type = XdrLiquidityPoolType::decode($xdr);
        $result = new XdrLiquidityPoolParameters($type);
        switch ($type->getValue()) {
            case XdrLiquidityPoolType::LIQUIDITY_POOL_CONSTANT_PRODUCT:
                $result->setConstantProduct(XdrLiquidityPoolConstantProductParameters::decode($xdr));
                break;
        }
        return $result;
    }
}