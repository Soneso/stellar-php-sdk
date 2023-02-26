<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrInvokeHostFunctionOperation
{

    public XdrHostFunction $function;
    public XdrLedgerFootprint $footprint;
    public array $auth; // [XdrContractAuth]

    /**
     * @param XdrHostFunction $function
     * @param XdrLedgerFootprint $footprint
     */
    public function __construct(XdrHostFunction $function, XdrLedgerFootprint $footprint, array $auth)
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
        $hf = XdrHostFunction::decode($xdr);
        $fp = XdrLedgerFootprint::decode($xdr);
        $valCount = $xdr->readInteger32();
        $auth = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($auth, XdrContractAuth::decode($xdr));
        }
        return new XdrInvokeHostFunctionOperation($hf, $fp, $auth);
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

    /**
     * @return array
     */
    public function getAuth(): array
    {
        return $this->auth;
    }

    /**
     * @param array $auth
     */
    public function setAuth(array $auth): void
    {
        $this->auth = $auth;
    }
}