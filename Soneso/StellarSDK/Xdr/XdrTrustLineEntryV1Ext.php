<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTrustLineEntryV1Ext
{
    public int $discriminant;
    public ?XdrTrustLineEntryExtensionV2 $v2 = null;

    public function __construct(int $discriminant) {
        $this->discriminant = $discriminant;
    }

    public function encode() : string {
        $bytes = XdrEncoder::integer32($this->discriminant);
        switch ($this->discriminant) {
            case 0:
                break;
            case 2:
                $bytes .= $this->v2->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrTrustLineEntryV1Ext {
        $v = $xdr->readInteger32();
        $result = new XdrTrustLineEntryV1Ext($v);
        switch ($v) {
            case 0:
                break;
            case 2:
                $result->v2 = XdrTrustLineEntryExtensionV2::decode($xdr);
                break;
        }
        return $result;
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