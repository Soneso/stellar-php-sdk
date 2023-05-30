<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;
class XdrInvokeHostFunctionOperation
{
    public array $functions; //[XdrHostFunction]

    /**
     * @param array $functions
     */
    public function __construct(array $functions)
    {
        $this->functions = $functions;
    }


    public function encode(): string {
        $bytes = XdrEncoder::integer32(count($this->functions));
        foreach($this->functions as $val) {
            $bytes .= $val->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrInvokeHostFunctionOperation {
        $valCount = $xdr->readInteger32();
        $functionsArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($functionsArr, XdrHostFunction::decode($xdr));
        }

        return new XdrInvokeHostFunctionOperation($functionsArr);
    }
}
/*
class XdrInvokeHostFunctionOperation
{

    public XdrHostFunctionArgs $function;
    public XdrLedgerFootprint $footprint;
    public array $auth; // [XdrContractAuth]


    public function __construct(XdrHostFunctionArgs $function, XdrLedgerFootprint $footprint, array $auth)
    {
        $this->function = $function;
        $this->footprint = $footprint;
        $this->auth = $auth;
    }


    public function encode(): string {
        $bytes = $this->function->encode();
        $bytes .= $this->footprint->encode();
        $bytes .= XdrEncoder::integer32(count($this->auth));
        foreach($this->auth as $val) {
            if ($val instanceof XdrContractAuth) {
                $bytes .= $val->encode();
            }
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrInvokeHostFunctionOperation {
        $hf = XdrHostFunctionArgs::decode($xdr);
        $fp = XdrLedgerFootprint::decode($xdr);
        $valCount = $xdr->readInteger32();
        $auth = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($auth, XdrContractAuth::decode($xdr));
        }
        return new XdrInvokeHostFunctionOperation($hf, $fp, $auth);
    }

}
*/