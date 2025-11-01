<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use InvalidArgumentException;
use Soneso\StellarSDK\Constants\StellarConstants;

/**
 * Builds a new Transaction object.
 */
class TransactionBuilder
{
    private TransactionBuilderAccount $sourceAccount;
    private ?Memo $memo = null;
    private ?TransactionPreconditions $preconditions = null;
    /**
     * @var array<AbstractOperation>
     */
    private array $operations;
    private ?int $maxOperationFee = null;
    private bool $incrementSeqNr = true;

    /**
     * Construct a new transaction builder.
     * @param TransactionBuilderAccount $sourceAccount The source account for this transaction. This account is the account
     * who will use a sequence number. When build() is called, the account object's sequence number
     * will be incremented.
     */
    public function __construct(TransactionBuilderAccount $sourceAccount)
    {
        $this->sourceAccount = $sourceAccount;
        $this->operations = array();
    }

    /**
     * Adds a new <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">operation</a> to this transaction.
     * @param AbstractOperation $operation The operation to add.
     * @return TransactionBuilder Builder object so you can chain methods.
     */
    public function addOperation(AbstractOperation $operation) : TransactionBuilder {
        array_push($this->operations, $operation);
        return $this;
    }

    /**
     * Adds N new <a href="https://developers.stellar.org/docs/start/list-of-operations/" target="_blank">operation</a> to this transaction.
     * @param array<AbstractOperation> $allOperations Array of itens.
     * @return TransactionBuilder Builder object so you can chain methods.
     */
    public function addOperations(array $allOperations) : TransactionBuilder {
        foreach($allOperations as $operations){
            array_push($this->operations, $operations);
        }
        return $this;
    }

    /**
     * Allows you to avoid source account and transaction sequence number incrementation, when building the transaction.
     * If not set to false, it automatically increments it when building the transaction.
     * @param bool $increment false if you would like to suppress sequence number incrementation.
     * @return TransactionBuilder Builder object so you can chain methods.
     */
    public function setIncrementSequenceNumber(bool $increment) : TransactionBuilder {
        $this->incrementSeqNr = $increment;
        return $this;
    }

    /**
     * Adds a <a href="https://developers.stellar.org/docs/glossary/transactions/#memo" target="_blank">memo</a> to this transaction.
     * @param Memo $memo Memo to add.
     * @return TransactionBuilder Builder object so you can chain methods.
     */
    public function addMemo(Memo $memo) : TransactionBuilder {
        $this->memo = $memo;
        return $this;
    }

    /**
     * Adds a <a href="https://developers.stellar.org/docs/glossary/transactions/" target="_blank">time-bounds</a> to this transaction.
     * @param TimeBounds $timeBounds TimeBounds to add.
     * @return TransactionBuilder Builder object so you can chain methods.
     */
    public function setTimeBounds(TimeBounds $timeBounds) : TransactionBuilder {
        if ($this->preconditions == null) {
            $this->preconditions = new TransactionPreconditions();
        }
        $this->preconditions->setTimeBounds($timeBounds);
        return $this;
    }

    /**
     * Adds a <a href="https://developers.stellar.org/docs/glossary/transactions/" target="_blank">transaction preconditions</a> to this transaction.
     * @param TransactionPreconditions $preconditions Preconditions to add.
     * @return TransactionBuilder Builder object so you can chain methods.
     */
    public function setPreconditions(TransactionPreconditions $preconditions): TransactionBuilder {
        $this->preconditions = $preconditions;
        return $this;
    }

    /**
     * Sets the maximal operation fee (base fee) for the transaction.
     * @param int $maxOperationFee maximal operation fee (base fee).
     * @return TransactionBuilder Builder object so you can chain methods.
     */
    public function setMaxOperationFee(int $maxOperationFee) : TransactionBuilder {
        if ($maxOperationFee < StellarConstants::MIN_BASE_FEE_STROOPS) {
            throw new InvalidArgumentException(
                "maxOperationFee cannot be smaller than the BASE_FEE (" . StellarConstants::MIN_BASE_FEE_STROOPS . ") : " . $maxOperationFee);
        }
        $this->maxOperationFee = $maxOperationFee;
        return $this;
    }

    /**
     * Builds a transaction. It will increment sequence number of the source account.
     */
    public function build() : Transaction {
        if ($this->maxOperationFee == null) {
            $this->maxOperationFee =  StellarConstants::MIN_BASE_FEE_STROOPS;
        }

        $fee = count($this->operations) * $this->maxOperationFee;
        $source = $this->sourceAccount->getMuxedAccount();
        $seqNr = $this->sourceAccount->getSequenceNumber();
        if ($this->incrementSeqNr) {
            $seqNr = $this->sourceAccount->getIncrementedSequenceNumber();
        }

        $transaction = new Transaction($source, $seqNr, $this->operations, $this->memo, $this->preconditions, $fee);

        if ($this->incrementSeqNr) {
            // Increment sequence number when there were no exceptions when creating a transaction
            $this->sourceAccount->incrementSequenceNumber();
        }
        return $transaction;
    }
}