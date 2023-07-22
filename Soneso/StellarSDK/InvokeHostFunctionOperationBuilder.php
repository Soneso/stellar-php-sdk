<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Exception;

class InvokeHostFunctionOperationBuilder
{
    // common
    public HostFunction $function;
    public array $auth; // [XdrSorobanAuthorizationEntry]
    public ?MuxedAccount $sourceAccount = null;

    /**
     * @param HostFunction $function
     * @param array $auth [XdrSorobanAuthorizationEntry]
     */
    public function __construct(HostFunction $function, array $auth = array())
    {
        $this->function = $function;
        $this->auth = $auth;
    }


    public function setSourceAccount(string $accountId) : InvokeHostFunctionOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : InvokeHostFunctionOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    /**
     * @throws Exception if the host function type is unknown or not implemented
     */
    public function build(): InvokeHostFunctionOperation {
        return new InvokeHostFunctionOperation($this->function, $this->auth, $this->sourceAccount);
    }
}