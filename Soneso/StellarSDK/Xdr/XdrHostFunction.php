<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrHostFunction
{
    public XdrHostFunctionArgs $args;
    public array $auth; // [XdrContractAuth]

    /**
     * @param XdrHostFunctionArgs $args
     * @param array $auth
     */
    public function __construct(XdrHostFunctionArgs $args, array $auth)
    {
        $this->args = $args;
        $this->auth = $auth;
    }

    public function encode() : string {
        $bytes = $this->args->encode();
        $bytes .= XdrEncoder::integer32(count($this->auth));
        foreach($this->auth as $val) {
            $bytes .= $val->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrHostFunction {
        $args = XdrHostFunctionArgs::decode($xdr);
        $valCount = $xdr->readInteger32();
        $authArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($authArr, XdrContractAuth::decode($xdr));
        }

        return new XdrHostFunction($args, $authArr);
    }

    /**
     * @return XdrHostFunctionArgs
     */
    public function getArgs(): XdrHostFunctionArgs
    {
        return $this->args;
    }

    /**
     * @param XdrHostFunctionArgs $args
     */
    public function setArgs(XdrHostFunctionArgs $args): void
    {
        $this->args = $args;
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