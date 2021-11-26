<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

class ClaimClaimableBalanceOperationBuilder
{
    private ?MuxedAccount $sourceAccount = null;
    private string $balanceId;

    public function __construct(string $balanceId) {
        $this->balanceId = $balanceId;
    }

    public function setSourceAccount(string $accountId) {
        $this->sourceAccount = new MuxedAccount($accountId);
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) {
        $this->sourceAccount = $sourceAccount;
    }

    public function build(): ClaimClaimableBalanceOperation {
        $result = new ClaimClaimableBalanceOperation($this->balanceId);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}