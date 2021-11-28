<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

class CreatePassiveSellOfferOperationBuilder
{
    private Asset $selling;
    private Asset $buying;
    private string $amount;
    private Price $price;
    private ?MuxedAccount $sourceAccount = null;

    public function __construct(Asset $selling, Asset $buying, string $amount, Price $price) {
        $this->selling = $selling;
        $this->buying = $buying;
        $this->amount = $amount;
        $this->price = $price;
    }

    public function setSourceAccount(string $accountId) : CreatePassiveSellOfferOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : CreatePassiveSellOfferOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): CreatePassiveSellOfferOperation {
        $result = new CreatePassiveSellOfferOperation($this->selling, $this->buying, $this->amount, $this->price);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}