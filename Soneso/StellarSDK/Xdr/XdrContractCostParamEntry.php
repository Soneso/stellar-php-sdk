<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractCostParamEntry
{
    public XdrExtensionPoint $ext;
    public int $constTerm;
    public int $linearTerm;

    /**
     * @param XdrExtensionPoint $ext
     * @param int $constTerm
     * @param int $linearTerm
     */
    public function __construct(XdrExtensionPoint $ext, int $constTerm, int $linearTerm)
    {
        $this->ext = $ext;
        $this->constTerm = $constTerm;
        $this->linearTerm = $linearTerm;
    }


    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= XdrEncoder::integer64($this->constTerm);
        $bytes .= XdrEncoder::integer64($this->linearTerm);

        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrContractCostParamEntry {
        $ext = XdrExtensionPoint::decode($xdr);
        $constTerm = $xdr->readInteger64();
        $linearTerm = $xdr->readInteger64();

        return new XdrContractCostParamEntry($ext, $constTerm, $linearTerm);
    }

    /**
     * @return int
     */
    public function getConstTerm(): int
    {
        return $this->constTerm;
    }

    /**
     * @param int $constTerm
     */
    public function setConstTerm(int $constTerm): void
    {
        $this->constTerm = $constTerm;
    }

    /**
     * @return int
     */
    public function getLinearTerm(): int
    {
        return $this->linearTerm;
    }

    /**
     * @param int $linearTerm
     */
    public function setLinearTerm(int $linearTerm): void
    {
        $this->linearTerm = $linearTerm;
    }

    /**
     * @return XdrExtensionPoint
     */
    public function getExt(): XdrExtensionPoint
    {
        return $this->ext;
    }

    /**
     * @param XdrExtensionPoint $ext
     */
    public function setExt(XdrExtensionPoint $ext): void
    {
        $this->ext = $ext;
    }
}