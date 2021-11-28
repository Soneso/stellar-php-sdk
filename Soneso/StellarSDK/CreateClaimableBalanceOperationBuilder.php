<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

class CreateClaimableBalanceOperationBuilder
{
    private array $claimants; //[Claimant]
    private Asset $asset;
    private string $amount;
    private ?MuxedAccount $sourceAccount = null;

    public function __construct(array $claimants, Asset $asset, string $amount) {
        $this->claimants = $claimants;
        $this->asset = $asset;
        $this->amount = $amount;
    }

    public function setSourceAccount(string $accountId) : CreateClaimableBalanceOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : CreateClaimableBalanceOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): CreateClaimableBalanceOperation {
        $result = new CreateClaimableBalanceOperation($this->claimants, $this->asset, $this->amount);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}