<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

class AccountMergeOperationBuilder
{
    private ?MuxedAccount $sourceAccount = null;
    private MuxedAccount $destination;

    public function __construct(string $destinationAccountId) {
        $this->destination = new MuxedAccount($destinationAccountId);
    }

    public static function forMuxedDestinationAccount(MuxedAccount $destination) : AccountMergeOperationBuilder {
        return new AccountMergeOperationBuilder($destination->getAccountId());
    }

    public function setSourceAccount(string $accountId) {
        $this->sourceAccount = new MuxedAccount($accountId);
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) {
        $this->sourceAccount = $sourceAccount;
    }

    public function build(): AccountMergeOperation {
        $result = new AccountMergeOperation($this->destination);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}