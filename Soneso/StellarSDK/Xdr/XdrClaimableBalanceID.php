<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrClaimableBalanceID
{

    private XdrClaimableBalanceIDType $type;
    private string $hash;

    public function __construct(XdrClaimableBalanceIDType $type, string $hash) {
        $this->type = $type;
        $this->hash = $hash;
    }

    /**
     * @return XdrClaimableBalanceIDType
     */
    public function getType(): XdrClaimableBalanceIDType
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    public function encode() : string
    {
        $bytes = $this->type->encode();
        $bytes .= XdrEncoder::opaqueFixed($this->hash, 32);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):XdrClaimableBalanceID {
        $result = new XdrClaimableBalanceID();
        $result->type = XdrClaimableBalanceIDType::decode($xdr);
        $result->hash = $xdr->readOpaqueFixed(32);
        return $result;
    }
}