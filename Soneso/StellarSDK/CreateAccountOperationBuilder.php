<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

class CreateAccountOperationBuilder
{
    private string $destination;
    private string $startingBalance;
    private ?MuxedAccount $sourceAccount = null;

    public function __construct(string $destination, string $startingBalance) {
        $this->destination = $destination;
        $this->startingBalance = $startingBalance;
    }

    public function setSourceAccount(string $accountId) {
        $this->sourceAccount = new MuxedAccount($accountId);
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) {
        $this->sourceAccount = $sourceAccount;
    }

    public function build(): CreateAccountOperation {
        $result = new CreateAccountOperation($this->destination, $this->startingBalance);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}