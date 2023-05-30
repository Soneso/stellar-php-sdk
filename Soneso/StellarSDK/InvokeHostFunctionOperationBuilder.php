<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Exception;

class InvokeHostFunctionOperationBuilder
{
    // common
    public array $functions;
    public ?MuxedAccount $sourceAccount = null;

    /**
     * @param array|null $functions
     */
    public function __construct(?array $functions = array())
    {
        $this->functions = $functions;
    }

    public function addFunction(HostFunction $function) : InvokeHostFunctionOperationBuilder {
        array_push($this->functions, $function);
        return $this;
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
        return new InvokeHostFunctionOperation($this->functions, $this->sourceAccount);
    }
}