<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

class ExtendFootprintTTLOperationBuilder
{
    private int $extendTo;
    private ?MuxedAccount $sourceAccount = null;

    /**
     * @param int $extendTo
     */
    public function __construct(int $extendTo)
    {
        $this->extendTo = $extendTo;
    }


    public function setSourceAccount(string $accountId) : ExtendFootprintTTLOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : ExtendFootprintTTLOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): ExtendFootprintTTLOperation {
        $result = new ExtendFootprintTTLOperation($this->extendTo);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}