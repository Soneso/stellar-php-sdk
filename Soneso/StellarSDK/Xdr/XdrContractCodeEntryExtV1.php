<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractCodeEntryExtV1
{
    public XdrExtensionPoint $ext;
    public XdrContractCodeCostInputs $costInputs;

    /**
     * @param XdrExtensionPoint $ext
     * @param XdrContractCodeCostInputs $costInputs
     */
    public function __construct(XdrExtensionPoint $ext, XdrContractCodeCostInputs $costInputs)
    {
        $this->ext = $ext;
        $this->costInputs = $costInputs;
    }


    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= $this->costInputs->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrContractCodeEntryExtV1 {
        $ext = XdrExtensionPoint::decode($xdr);
        $costInputs = XdrContractCodeCostInputs::decode($xdr);

        return new XdrContractCodeEntryExtV1(
            $ext,
            $costInputs,
        );
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

    /**
     * @return XdrContractCodeCostInputs
     */
    public function getCostInputs(): XdrContractCodeCostInputs
    {
        return $this->costInputs;
    }

    /**
     * @param XdrContractCodeCostInputs $costInputs
     */
    public function setCostInputs(XdrContractCodeCostInputs $costInputs): void
    {
        $this->costInputs = $costInputs;
    }
}