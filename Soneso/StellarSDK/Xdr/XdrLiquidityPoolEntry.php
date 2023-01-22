<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLiquidityPoolEntry
{

    public string $liquidityPoolID;
    public XdrLiquidityPoolBody $body;

    /**
     * @param string $liquidityPoolID
     * @param XdrLiquidityPoolBody $body
     */
    public function __construct(string $liquidityPoolID, XdrLiquidityPoolBody $body)
    {
        $this->liquidityPoolID = $liquidityPoolID;
        $this->body = $body;
    }


    public function encode(): string {
        $bytes = XdrEncoder::opaqueFixed($this->liquidityPoolID, 32);
        $bytes .= $this->body->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrLiquidityPoolEntry {
        $liquidityPoolID = $xdr->readOpaqueFixed(32);
        return new XdrLiquidityPoolEntry($liquidityPoolID, XdrLiquidityPoolBody::decode($xdr));
    }

    /**
     * @return string
     */
    public function getLiquidityPoolID(): string
    {
        return $this->liquidityPoolID;
    }

    /**
     * @param string $liquidityPoolID
     */
    public function setLiquidityPoolID(string $liquidityPoolID): void
    {
        $this->liquidityPoolID = $liquidityPoolID;
    }

    /**
     * @return XdrLiquidityPoolBody
     */
    public function getBody(): XdrLiquidityPoolBody
    {
        return $this->body;
    }

    /**
     * @param XdrLiquidityPoolBody $body
     */
    public function setBody(XdrLiquidityPoolBody $body): void
    {
        $this->body = $body;
    }
}