<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrClaimant
{
    private XdrClaimantType $type;
    private ?XdrClaimantV0 $v0 = null;

    public function __construct(XdrClaimantType $type) {
        $this->type = $type;
    }

    /**
     * @return XdrClaimantType
     */
    public function getType(): XdrClaimantType
    {
        return $this->type;
    }

    /**
     * @return XdrClaimantV0|null
     */
    public function getV0(): ?XdrClaimantV0
    {
        return $this->v0;
    }

    /**
     * @param XdrClaimantV0|null $v0
     */
    public function setV0(?XdrClaimantV0 $v0): void
    {
        $this->v0 = $v0;
    }

    public function encode() : string {
        $bytes = $this->type->encode();
        $bytes .= match ($this->type->getValue()) {
            XdrClaimantType::V0 => $this->v0->encode()
        };
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrClaimant {
        $type = XdrClaimantType::decode($xdr);
        $decoded = new XdrClaimant($type);
        switch ($type->getValue()) {
            case XdrClaimantType::V0:
                $decoded->v0 = XdrClaimantV0::decode($xdr);
                break;
        }
        return $decoded;
    }
}