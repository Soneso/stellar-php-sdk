<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

class BeginSponsoringFutureReservesOperationBuilder
{
    private string $sponsoredId;
    private ?MuxedAccount $sourceAccount = null;

    public function __construct(string $sponsoredId) {
        $this->sponsoredId = $sponsoredId;
    }

    public function setSourceAccount(string $accountId) : BeginSponsoringFutureReservesOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : BeginSponsoringFutureReservesOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): BeginSponsoringFutureReservesOperation {
        $result = new BeginSponsoringFutureReservesOperation($this->sponsoredId);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}