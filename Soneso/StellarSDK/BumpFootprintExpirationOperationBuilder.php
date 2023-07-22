<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

class BumpFootprintExpirationOperationBuilder
{
    private int $ledgersToExpire;
    private ?MuxedAccount $sourceAccount = null;

    /**
     * @param int $ledgersToExpire
     */
    public function __construct(int $ledgersToExpire)
    {
        $this->ledgersToExpire = $ledgersToExpire;
    }


    public function setSourceAccount(string $accountId) : BumpFootprintExpirationOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : BumpFootprintExpirationOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): BumpFootprintExpirationOperation {
        $result = new BumpFootprintExpirationOperation($this->ledgersToExpire);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}