<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use phpseclib3\Math\BigInteger;

/**
 * Builds BumpSequence operation.
 * @see BumpSequenceOperation
 */
class BumpSequenceOperationBuilder
{
    private BigInteger $bumpTo;
    private ?MuxedAccount $sourceAccount = null;

    /**
     * Creates a BumpSequenceOperationBuilder
     * @param BigInteger $bumpTo Desired value for the operation’s source account sequence number.
     */
    public function __construct(BigInteger $bumpTo) {
        $this->bumpTo = $bumpTo;
    }

    public function setSourceAccount(string $accountId) : BumpSequenceOperationBuilder {
        $this->sourceAccount = MuxedAccount::fromAccountId($accountId);
        return $this;
    }

    public function setMuxedSourceAccount(MuxedAccount $sourceAccount) : BumpSequenceOperationBuilder {
        $this->sourceAccount = $sourceAccount;
        return $this;
    }

    public function build(): BumpSequenceOperation {
        $result = new BumpSequenceOperation($this->bumpTo);
        if ($this->sourceAccount != null) {
            $result->setSourceAccount($this->sourceAccount);
        }
        return $result;
    }
}