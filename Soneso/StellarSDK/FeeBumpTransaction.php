<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Exception;
use Soneso\StellarSDK\Util\Hash;
use Soneso\StellarSDK\Xdr\XdrEncoder;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransaction;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrFeeBumpTransactionInnerTx;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;

class FeeBumpTransaction extends AbstractTransaction
{
    private int $fee;
    private MuxedAccount $feeAccount;
    private Transaction $innerTx;

    public function __construct(MuxedAccount $feeAccount, int $fee, Transaction $innerTx) {
        $this->fee = $fee;
        $this->feeAccount = $feeAccount;
        $this->innerTx = $innerTx;
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


    /**
     * @throws Exception if inner transaction is not signed.
     */
    public function signatureBase(Network $network): string
    {
        $bytes = Hash::generate($network->getNetworkPassphrase());
        $bytes .= XdrEncoder::unsignedInteger32(XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP);
        $bytes .= $this->toXdr()->encode();
        return $bytes;
    }

    /**
     * @throws Exception if inner transaction is not signed.
     */
    public function toXdr() : XdrFeeBumpTransaction {
        $xdrInnerTxV1 = $this->innerTx->toEnvelopeXdr()->getV1();
        $xdrInnerTx = new XdrFeeBumpTransactionInnerTx(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX), $xdrInnerTxV1);
        return new XdrFeeBumpTransaction($this->feeAccount->toXdr(), $this->fee, $xdrInnerTx);
    }

    /**
     * @throws Exception if transaction is not signed.
     */
    public function toEnvelopeXdr(): XdrTransactionEnvelope
    {
        $xdr = new XdrTransactionEnvelope(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP));
        $feeBumpEnvelope = new XdrFeeBumpTransactionEnvelope($this->toXdr(), $this->getSignatures());
        $xdr->setFeeBump($feeBumpEnvelope);
        return $xdr;
    }

    public static function fromFeeBumpTransactionEnvelope(XdrFeeBumpTransactionEnvelope $envelope) : FeeBumpTransaction {
        $inner = Transaction::fromV1EnvelopeXdr($envelope->getTx()->getInnerTx()->getV1());
        $feeSourceAccount = MuxedAccount::fromXdr($envelope->getTx()->getFeeSource());
        $fee = $envelope->getTx()->getFee();
        $transaction = new FeeBumpTransaction($feeSourceAccount, $fee, $inner);
        $signatures = $envelope->getSignatures();
        foreach($signatures as $signature) {
            $transaction->addSignature($signature);
        }
        return $transaction;
    }
}