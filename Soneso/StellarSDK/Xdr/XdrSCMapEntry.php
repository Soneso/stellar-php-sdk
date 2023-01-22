<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCMapEntry
{

    public XdrSCVal $key;
    public XdrSCVal $val;

    /**
     * @param XdrSCVal $key
     * @param XdrSCVal $val
     */
    public function __construct(XdrSCVal $key, XdrSCVal $val)
    {
        $this->key = $key;
        $this->val = $val;
    }

    public function encode(): string {
        $bytes = $this->key->encode();
        $bytes .= $this->val->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCMapEntry {
        return new XdrSCMapEntry(XdrSCVal::decode($xdr), XdrSCVal::decode($xdr));
    }

    /**
     * @return XdrSCVal
     */
    public function getKey(): XdrSCVal
    {
        return $this->key;
    }

    /**
     * @param XdrSCVal $key
     */
    public function setKey(XdrSCVal $key): void
    {
        $this->key = $key;
    }

    /**
     * @return XdrSCVal
     */
    public function getVal(): XdrSCVal
    {
        return $this->val;
    }

    /**
     * @param XdrSCVal $val
     */
    public function setVal(XdrSCVal $val): void
    {
        $this->val = $val;
    }

}