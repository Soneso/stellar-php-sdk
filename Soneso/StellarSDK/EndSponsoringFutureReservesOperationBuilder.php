<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

class EndSponsoringFutureReservesOperationBuilder
{
    private ?MuxedAccount $sourceAccount = null;

    public function setSourceAccount(string $accountId) : EndSponsoringFutureReservesOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : EndSponsoringFutureReservesOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): EndSponsoringFutureReservesOperation {
        $result = new EndSponsoringFutureReservesOperation();
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}