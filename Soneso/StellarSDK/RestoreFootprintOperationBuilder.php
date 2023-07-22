<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

class RestoreFootprintOperationBuilder
{
    private ?MuxedAccount $sourceAccount = null;

    public function __construct()
    {
    }


    public function setSourceAccount(string $accountId) : RestoreFootprintOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : RestoreFootprintOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): RestoreFootprintOperation {
        $result = new RestoreFootprintOperation();
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}