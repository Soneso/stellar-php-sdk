<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrInvokeHostFunctionOperation
{

    public XdrHostFunction $function;
    public XdrLedgerFootprint $footprint;

    /**
     * @param XdrHostFunction $function
     * @param XdrLedgerFootprint $footprint
     */
    public function __construct(XdrHostFunction $function, XdrLedgerFootprint $footprint)
    {
        $this->function = $function;
        $this->footprint = $footprint;
    }


    public function encode(): string {
        $bytes = $this->function->encode();
        $bytes .= $this->footprint->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrInvokeHostFunctionOperation {
        return new XdrInvokeHostFunctionOperation(XdrHostFunction::decode($xdr), XdrLedgerFootprint::decode($xdr));
    }

    /**
     * @return XdrHostFunction
     */
    public function getFunction(): XdrHostFunction
    {
        return $this->function;
    }

    /**
     * @param XdrHostFunction $function
     */
    public function setFunction(XdrHostFunction $function): void
    {
        $this->function = $function;
    }

    /**
     * @return XdrLedgerFootprint
     */
    public function getFootprint(): XdrLedgerFootprint
    {
        return $this->footprint;
    }

    /**
     * @param XdrLedgerFootprint $footprint
     */
    public function setFootprint(XdrLedgerFootprint $footprint): void
    {
        $this->footprint = $footprint;
    }
}