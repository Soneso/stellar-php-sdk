<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

class PaymentOperationBuilder
{
    private ?MuxedAccount $sourceAccount = null;
    private MuxedAccount $destination;
    private Asset $asset;
    private string $amount;

    public function __construct(string $destinationAccountId, Asset $asset, string $amount) {
        $this->destination = new MuxedAccount($destinationAccountId);
        $this->asset = $asset;
        $this->amount = $amount;
    }

    public static function forMuxedDestinationAccount(MuxedAccount $destination, Asset $asset, string $amount) : PaymentOperationBuilder {
        return  new PaymentOperationBuilder($destination->getAccountId(), $asset, $amount);
    }

    public function setSourceAccount(string $accountId) {
        $this->sourceAccount = new MuxedAccount($accountId);
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) {
        $this->sourceAccount = $sourceAccount;
    }

    public function build(): PaymentOperation {
        $result = new PaymentOperation($this->destination, $this->asset, $this->amount);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}