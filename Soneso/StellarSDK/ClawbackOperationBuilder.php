<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

class ClawbackOperationBuilder
{
    private Asset $asset;
    private MuxedAccount $from;
    private string $amount;
    private ?MuxedAccount $sourceAccount = null;

    public function __construct(Asset $asset, MuxedAccount $from, string $amount) {
        $this->asset = $asset;
        $this->from = $from;
        $this->amount = $amount;
    }

    public function setSourceAccount(string $accountId) : ClawbackOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : ClawbackOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): ClawbackOperation {
        $result = new ClawbackOperation($this->asset, $this->from, $this->amount);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}