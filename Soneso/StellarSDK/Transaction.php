<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Exception;
use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Util\Hash;
use Soneso\StellarSDK\Xdr\XdrEncoder;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrSequenceNumber;
use Soneso\StellarSDK\Xdr\XdrSorobanTransactionData;
use Soneso\StellarSDK\Xdr\XdrTransaction;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;
use Soneso\StellarSDK\Xdr\XdrTransactionExt;
use Soneso\StellarSDK\Xdr\XdrTransactionV0Envelope;
use Soneso\StellarSDK\Xdr\XdrTransactionV1Envelope;

/**
 * Represents <a href="https://developers.stellar.org/docs/glossary/transactions/" target="_blank">Transaction</a> in Stellar network.
 */
class Transaction extends AbstractTransaction
{
    private int $fee = AbstractTransaction::MIN_BASE_FEE;
    private BigInteger $sequenceNumber;
    private MuxedAccount $sourceAccount;
    private array $operations; //[AbstractOperation]
    private Memo $memo;
    private ?TransactionPreconditions $preconditions;
    private ?XdrSorobanTransactionData $sorobanTransactionData = null;

    public function __construct(MuxedAccount $sourceAccount, BigInteger $sequenceNumber, array $operations,
                                ?Memo $memo = null, ?TransactionPreconditions $preconditions = null,
                                ?int $fee = null, ?XdrSorobanTransactionData $sorobanTransactionData = null) {

        if (count($operations) == 0) {
            throw new InvalidArgumentException("At least one operation required");
        }

        foreach ($operations as $operation) {
            if (!($operation instanceof AbstractOperation)) {
                throw new InvalidArgumentException("operation array contains unknown operation type");
            }
        }

        if ($fee == null) {
            $this->fee = AbstractTransaction::MIN_BASE_FEE * count($operations);
        } else {
            $this->fee = $fee;
        }

        $this->sourceAccount = $sourceAccount;
        $this->sequenceNumber = $sequenceNumber;
        $this->operations = $operations;
        $this->preconditions = $preconditions;
        $this->memo = $memo ?? Memo::none();
        $this->sorobanTransactionData = $sorobanTransactionData;
        parent::__construct();
    }

    /**
     * @return TransactionPreconditions|null
     */
    public function getPreconditions(): ?TransactionPreconditions
    {
        return $this->preconditions;
    }


    /**
     * @return BigInteger
     */
    public function getSequenceNumber(): BigInteger
    {
        return $this->sequenceNumber;
    }

    /**
     * Returns fee paid for transaction in stroops (1 stroop = 0.0000001 XLM).
     * @return int
     */
    public function getFee(): int
    {
        return $this->fee;
    }

    /**
     * @param int $fee
     */
    public function setFee(int $fee): void
    {
        $this->fee = $fee;
    }

    /**
     * @param int $resourceFee
     */
    public function addResourceFee(int $resourceFee): void
    {
        $this->fee += $resourceFee;
    }

    /**
     * @return MuxedAccount
     */
    public function getSourceAccount(): MuxedAccount
    {
        return $this->sourceAccount;
    }

    /**
     * Returns operations in this transaction.
     * @return array
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * @return Memo
     */
    public function getMemo(): Memo
    {
        return $this->memo;
    }

    /**
     * TimeBounds, or null (representing no time restrictions).
     * @return TimeBounds|null
     */
    public function getTimeBounds(): ?TimeBounds
    {
        if ($this->preconditions != null) {
            return $this->preconditions->getTimeBounds();
        }
        return null;
    }

    /**
     * @return XdrSorobanTransactionData|null
     */
    public function getSorobanTransactionData(): ?XdrSorobanTransactionData
    {
        return $this->sorobanTransactionData;
    }

    /**
     * @param array|null $auth
     */
    public function setSorobanAuth(?array $auth = array()) {
        $authToSet = $auth;
        if ($authToSet == null) {
            $authToSet = array();
        }
        foreach ($this->operations as $operation) {
            if ($operation instanceof InvokeHostFunctionOperation) {
                $operation->auth = $authToSet;
            }
        }
    }

    /**
     * @param XdrSorobanTransactionData|null $sorobanTransactionData
     */
    public function setSorobanTransactionData(?XdrSorobanTransactionData $sorobanTransactionData): void
    {
        $this->sorobanTransactionData = $sorobanTransactionData;
    }

    public function signatureBase(Network $network): string
    {
        $bytes = Hash::generate($network->getNetworkPassphrase());
        $bytes .= XdrEncoder::unsignedInteger32(XdrEnvelopeType::ENVELOPE_TYPE_TX);
        $bytes .= $this->toXdr()->encode();
        return $bytes;
    }

    public function toXdr() : XdrTransaction {
        $xdrMuxedSourceAccount = $this->sourceAccount->toXdr();
        $xdrSequenceNr = new XdrSequenceNumber($this->sequenceNumber);
        $xdrOperations = array();
        foreach ($this->operations as $operation) {
            if ($operation instanceof AbstractOperation) {
                array_push($xdrOperations, $operation->toXdr());
            }
        }
        $xdrMemo = $this->memo->toXdr();
        $xdrCond = $this->preconditions?->toXdr();
        $xdrExt = null;
        if ($this->sorobanTransactionData != null) {
            $xdrExt = new XdrTransactionExt(1, $this->sorobanTransactionData);
        }
        return new XdrTransaction($xdrMuxedSourceAccount, $xdrSequenceNr, $xdrOperations, $this->fee, $xdrMemo, $xdrCond, $xdrExt);
    }

    public function toXdrBase64() : string {
        $xdr = $this->toXdr();
        return base64_encode($xdr->encode());
    }
    /**
     * @throws Exception if transaction is not signed.
     */
    public function toEnvelopeXdr(): XdrTransactionEnvelope
    {
        $xdrTransaction = $this->toXdr();
        $v1Envelope = new XdrTransactionV1Envelope($xdrTransaction, $this->getSignatures());
        $type = new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX);
        $xdrEnvelope = new XdrTransactionEnvelope($type);
        $xdrEnvelope->setV1($v1Envelope);
        return $xdrEnvelope;
    }

    public static function fromV1EnvelopeXdr(XdrTransactionV1Envelope $envelope) : Transaction {
        $tx = $envelope->getTx();
        $sourceAccount = MuxedAccount::fromXdr($tx->getSourceAccount());
        $fee = $tx->getFee();
        $seqNr = $tx->getSequenceNumber()->getValue();
        $memo = Memo::fromXdr($tx->getMemo());
        $operations = array();
        $cond = null;
        if ($tx->getPreconditions() != null) {
            $cond = TransactionPreconditions::fromXdr($tx->getPreconditions());
        }
        foreach($tx->getOperations() as $operation) {
            array_push($operations, AbstractOperation::fromXdr($operation));
        }

        $transaction = new Transaction($sourceAccount, $seqNr, $operations, $memo, $cond, $fee, $tx->ext->sorobanTransactionData);
        foreach($envelope->getSignatures() as $signature) {
            $transaction->addSignature($signature);
        }
        return $transaction;
    }

    public static function fromV0EnvelopeXdr(XdrTransactionV0Envelope $envelope) : Transaction {
        $tx = $envelope->getTx();
        $accId = KeyPair::fromPublicKey($tx->getSourceAccountEd25519())->getAccountId();
        $sourceAccount = MuxedAccount::fromAccountId($accId);
        $fee = $tx->getFee();
        $seqNr = $tx->getSequenceNumber()->getValue();
        $memo = Memo::fromXdr($tx->getMemo());
        $operations = array();
        $cond = null;
        if ($tx->getTimeBounds() != null) {
            $cond = new TransactionPreconditions();
            $cond->setTimeBounds(TimeBounds::fromXdr($tx->getTimeBounds()));
        }
        foreach($tx->getOperations() as $operation) {
            array_push($operations, AbstractOperation::fromXdr($operation));
        }
        $transaction = new Transaction($sourceAccount, $seqNr, $operations, $memo, $cond, $fee);
        foreach($envelope->getSignatures() as $signature) {
            $transaction->addSignature($signature);
        }
        return $transaction;
    }

    public static function builder(TransactionBuilderAccount $sourceAccount) : TransactionBuilder{
        return new TransactionBuilder($sourceAccount);
    }
}