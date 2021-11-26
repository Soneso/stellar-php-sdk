<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;

class FeeBumpTransaction extends AbstractTransaction
{
    private int $fee;
    private MuxedAccount $feeAccount;
    private Transaction $innerTx;

    public function __construct(MuxedAccount $feeAccount, int $fee, Transaction $innerTx) {
        $this->fee = $fee;
        $this->feeAccount = $feeAccount;
        parent::__construct();
    }

    /**
     * @return int
     */
    public function getFee(): int
    {
        return $this->fee;
    }

    /**
     * @return MuxedAccount
     */
    public function getFeeAccount(): MuxedAccount
    {
        return $this->feeAccount;
    }

    /**
     * @return Transaction
     */
    public function getInnerTx(): Transaction
    {
        return $this->innerTx;
    }


    public function signatureBase(Network $network): string
    {
        // TODO: Implement signatureBase() method.
    }

    public function toEnvelopeXdr(): XdrTransactionEnvelope
    {
        // TODO: Implement toEnvelopeXdr() method.
    }

    public static function fromFeeBumpTransactionEnvelope(XdrFeeBumpTransactionEnvelope $envelope) : FeeBumpTransaction {
        $inner = Transaction::fromV1EnvelopeXdr($envelope->getTx()->getInnerTx()->getV1());
        $feeSourceAccount = MuxedAccount::fromXdr($envelope->getTx()->getFeeSource());
        $fee = $envelope->getTx()->getFee();
        return new FeeBumpTransaction($feeSourceAccount, $fee, $inner);
    }
}