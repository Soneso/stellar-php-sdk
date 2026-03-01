<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrSorobanTransactionMetaV2
{
    public XdrSorobanTransactionMetaExt $ext;
    public ?XdrSCVal $returnValue = null;

    /**
     * @param XdrSorobanTransactionMetaExt $ext
     * @param XdrSCVal|null $returnValue
     */
    public function __construct(XdrSorobanTransactionMetaExt $ext, ?XdrSCVal $returnValue)
    {
        $this->ext = $ext;
        $this->returnValue = $returnValue;
    }


    public function encode(): string {
        $bytes = $this->ext->encode();
        if ($this->returnValue !== null) {
            $bytes .= XdrEncoder::integer32(1);
            $bytes .= $this->returnValue->encode();
        } else {
            $bytes .= XdrEncoder::integer32(0);
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSorobanTransactionMetaV2 {
        $ext = XdrSorobanTransactionMetaExt::decode($xdr);
        $returnValue = null;
        if ($xdr->readInteger32() == 1) {
            $returnValue = XdrSCVal::decode($xdr);
        }
        return new XdrSorobanTransactionMetaV2($ext, $returnValue);
    }

    /**
     * @return XdrSorobanTransactionMetaExt
     */
    public function getExt(): XdrSorobanTransactionMetaExt
    {
        return $this->ext;
    }

    /**
     * @param XdrSorobanTransactionMetaExt $ext
     */
    public function setExt(XdrSorobanTransactionMetaExt $ext): void
    {
        $this->ext = $ext;
    }

    /**
     * @return XdrSCVal|null
     */
    public function getReturnValue(): ?XdrSCVal
    {
        return $this->returnValue;
    }

    /**
     * @param XdrSCVal|null $returnValue
     */
    public function setReturnValue(?XdrSCVal $returnValue): void
    {
        $this->returnValue = $returnValue;
    }

}