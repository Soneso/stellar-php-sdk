<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;

class TransactionBuilder
{
    private TransactionBuilderAccount $sourceAccount;
    private ?Memo $memo = null;
    private ?TimeBounds $timeBounds = null;
    private array $operations; //[AbstractOperation]
    private ?int $maxOperationFee = null;

    public function __construct(TransactionBuilderAccount $sourceAccount)
    {
        $this->sourceAccount = $sourceAccount;
        $this->operations = array();
    }

    public function addOperation(AbstractOperation $operation) : TransactionBuilder {
        array_push($this->operations, $operation);
        return $this;
    }

    public function setMemo(Memo $memo) : TransactionBuilder {
        $this->memo = $memo;
        return $this;
    }

    public function setTimeBounds(TimeBounds $timeBounds) : TransactionBuilder {
        $this->timeBounds = $timeBounds;
        return $this;
    }

    public function setMaxOperationFee(int $maxOperationFee) {
        if ($maxOperationFee < AbstractTransaction::MIN_BASE_FEE) {
            throw new InvalidArgumentException(
                "maxOperationFee cannot be smaller than the BASE_FEE (" . AbstractTransaction::MIN_BASE_FEE . ") : " . $maxOperationFee);
        }
        $this->maxOperationFee = $maxOperationFee;
    }

    public function build() : Transaction {
        if ($this->maxOperationFee == null) {
           $this->maxOperationFee =  AbstractTransaction::MIN_BASE_FEE;
        }

        $fee = count($this->operations) * $this->maxOperationFee;
        $source = $this->sourceAccount->getMuxedAccount();
        $seqNr = $this->sourceAccount->getIncrementedSequenceNumber();
        return new Transaction($source, $seqNr, $this->operations, $this->memo, $this->timeBounds, $fee);
    }
}