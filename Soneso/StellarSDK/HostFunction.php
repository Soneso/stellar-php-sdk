<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Soroban\ContractAuth;
use Soneso\StellarSDK\Xdr\XdrContractAuth;
use Soneso\StellarSDK\Xdr\XdrHostFunction;

abstract class HostFunction
{
    public array $auth; // array containing XdrContractAuth objects.

    /**
     * @param array $auth
     */
    public function __construct(array $auth)
    {
        $this->auth = $auth;
    }

    abstract public function toXdr() : XdrHostFunction;
    abstract public static function fromXdr(XdrHostFunction $xdr) : HostFunction;

    protected static function convertFromXdrAuth(array $xdrAuth) : array {
        $result = array();
        foreach ($xdrAuth as $xdr) {
            if ($xdr instanceof XdrContractAuth) {
                array_push($result , ContractAuth::fromXdr($xdr));
            }
        }
        return $result;
    }

    protected static function convertToXdrAuth(?array $auth) : array {
        if ($auth == null) {
            return array();
        }

        $result = array();
        foreach ($auth as $val) {
            if ($val instanceof ContractAuth) {
                array_push($result , $val->toXdr());
            }
        }
        return $result;
    }
}