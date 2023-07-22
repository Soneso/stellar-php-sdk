<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrHostFunction;

abstract class HostFunction
{

    public function __construct()
    {
    }

    abstract public function toXdr() : XdrHostFunction;
    abstract public static function fromXdr(XdrHostFunction $xdr) : HostFunction;

}