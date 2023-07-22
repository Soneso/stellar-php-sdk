<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrInvokeHostFunctionOp
{
    public XdrHostFunction $hostFunction;
    public array $auth; // [XdrSorobanAuthorizationEntry]

    /**
     * @param XdrHostFunction $hostFunction
     * @param array $auth
     */
    public function __construct(XdrHostFunction $hostFunction, array $auth)
    {
        $this->hostFunction = $hostFunction;
        $this->auth = $auth;
    }

    public function encode(): string {
        $bytes = $this->hostFunction->encode();
        $bytes .= XdrEncoder::integer32(count($this->auth));
        foreach($this->auth as $val) {
            $bytes .= $val->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrInvokeHostFunctionOp {
        $hostFunction = XdrHostFunction::decode($xdr);
        $valCount = $xdr->readInteger32();
        $authArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($authArr, XdrSorobanAuthorizationEntry::decode($xdr));
        }

        return new XdrInvokeHostFunctionOp($hostFunction, $authArr);
    }

    /**
     * @return XdrHostFunction
     */
    public function getHostFunction(): XdrHostFunction
    {
        return $this->hostFunction;
    }

    /**
     * @param XdrHostFunction $hostFunction
     */
    public function setHostFunction(XdrHostFunction $hostFunction): void
    {
        $this->hostFunction = $hostFunction;
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
