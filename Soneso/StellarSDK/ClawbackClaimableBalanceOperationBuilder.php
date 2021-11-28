<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

class ClawbackClaimableBalanceOperationBuilder
{
    private string $balanceId;
    private ?MuxedAccount $sourceAccount = null;

    public function __construct(string $balanceId) {
        $this->balanceId = $balanceId;
    }

    public function setSourceAccount(string $accountId) : ClawbackClaimableBalanceOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : ClawbackClaimableBalanceOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): ClawbackClaimableBalanceOperation {
        $result = new ClawbackClaimableBalanceOperation($this->balanceId);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}