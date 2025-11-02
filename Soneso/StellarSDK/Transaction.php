<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Exception;
use InvalidArgumentException;
use phpseclib3\Math\BigInteger;
use Soneso\StellarSDK\Constants\StellarConstants;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Soroban\SorobanAuthorizationEntry;
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
 * Represents a Stellar transaction that contains a set of operations to be executed atomically
 *
 * A transaction is the fundamental unit of change on the Stellar network. It consists of
 * one or more operations that are executed together or not at all. Each transaction requires
 * a source account, sequence number, and signatures from the required signers.
 *
 * Usage:
 * <code>
 * // Build a transaction using the TransactionBuilder
 * $transaction = (new TransactionBuilder($sourceAccount))
 *     ->addOperation($paymentOp)
 *     ->addMemo(Memo::text("Payment for invoice"))
 *     ->build();
 *
 * // Sign the transaction
 * $transaction->sign($sourceKeyPair, Network::testnet());
 *
 * // Submit to the network
 * $response = $sdk->submitTransaction($transaction);
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see https://developers.stellar.org/docs/learn/fundamentals/transactions Stellar Transactions
 * @see TransactionBuilder For building transactions
 * @since 1.0.0
 */
class Transaction extends AbstractTransaction
{
    private int $fee = StellarConstants::MIN_BASE_FEE_STROOPS;
    private BigInteger $sequenceNumber;
    private MuxedAccount $sourceAccount;
    /**
     * @var array<AbstractOperation> $operations
     */
    private array $operations;
    private Memo $memo;
    private ?TransactionPreconditions $preconditions;
    private ?XdrSorobanTransactionData $sorobanTransactionData = null;

    /**
     * Constructor.
     * @param MuxedAccount $sourceAccount Source account of the transaction.
     * @param BigInteger $sequenceNumber Sequence number of the source account.
     * @param array<AbstractOperation> $operations Operations to be added to the transaction.
     * @param Memo|null $memo Memo to be added to the transaction.
     * @param TransactionPreconditions|null $preconditions Transaction preconditions if any.
     * @param int|null $fee maximum fee to be paid to the Stellar Network for the transaction.
     * If not set it will be calculated by using the current minimum base fee of currently 100 stoops per operation.
     * @param XdrSorobanTransactionData|null $sorobanTransactionData Soroban transaction data if needed.
     * @throws InvalidArgumentException If operations array is empty or contains invalid types
     */
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
            $this->fee = StellarConstants::MIN_BASE_FEE_STROOPS * count($operations);
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
     * Returns the transaction preconditions if set
     *
     * @return TransactionPreconditions|null The preconditions or null if not set
     */
    public function getPreconditions(): ?TransactionPreconditions
    {
        return $this->preconditions;
    }

    /**
     * Returns the sequence number for this transaction
     *
     * The sequence number must be exactly one greater than the current sequence
     * number of the source account.
     *
     * @return BigInteger The transaction sequence number
     */
    public function getSequenceNumber(): BigInteger
    {
        return $this->sequenceNumber;
    }

    /**
     * Returns the maximum fee willing to be paid for this transaction in stroops
     *
     * One stroop equals 0.0000001 XLM. The fee is calculated per operation by default
     * using the current minimum base fee (100 stroops per operation).
     *
     * @return int The maximum fee in stroops
     */
    public function getFee(): int
    {
        return $this->fee;
    }

    /**
     * Sets the maximum fee for this transaction in stroops
     *
     * @param int $fee The maximum fee to pay in stroops
     */
    public function setFee(int $fee): void
    {
        $this->fee = $fee;
    }

    /**
     * Adds a resource fee to the existing transaction fee
     *
     * Used primarily for Soroban smart contract transactions to account for
     * computational and storage resources consumed during execution.
     *
     * @param int $resourceFee Additional resource fee in stroops
     */
    public function addResourceFee(int $resourceFee): void
    {
        $this->fee += $resourceFee;
    }

    /**
     * Returns the source account for this transaction
     *
     * @return MuxedAccount The transaction source account
     */
    public function getSourceAccount(): MuxedAccount
    {
        return $this->sourceAccount;
    }

    /**
     * Returns all operations included in this transaction
     *
     * Operations are executed in the order they appear in this array.
     *
     * @return array<AbstractOperation> Array of operations to be executed
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * Returns the memo attached to this transaction
     *
     * @return Memo The transaction memo (defaults to Memo::none() if not set)
     */
    public function getMemo(): Memo
    {
        return $this->memo;
    }

    /**
     * Returns the time bounds for this transaction if set
     *
     * Time bounds restrict when a transaction can be successfully executed.
     * Returns null if no time restrictions are set.
     *
     * @return TimeBounds|null The time bounds or null if not restricted
     */
    public function getTimeBounds(): ?TimeBounds
    {
        if ($this->preconditions != null) {
            return $this->preconditions->getTimeBounds();
        }
        return null;
    }

    /**
     * Returns the Soroban transaction data if this is a smart contract transaction
     *
     * @return XdrSorobanTransactionData|null Soroban-specific data or null for standard transactions
     */
    public function getSorobanTransactionData(): ?XdrSorobanTransactionData
    {
        return $this->sorobanTransactionData;
    }

    /**
     * Sets authorization entries for Soroban smart contract invocations
     *
     * This method updates all InvokeHostFunctionOperation instances in the transaction
     * with the provided authorization data.
     *
     * @param array<SorobanAuthorizationEntry>|null $auth Array of authorization entries or null/empty array to clear
     */
    public function setSorobanAuth(?array $auth = array()) : void {
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
     * Sets Soroban transaction data for smart contract transactions
     *
     * @param XdrSorobanTransactionData|null $sorobanTransactionData Soroban-specific transaction data
     */
    public function setSorobanTransactionData(?XdrSorobanTransactionData $sorobanTransactionData): void
    {
        $this->sorobanTransactionData = $sorobanTransactionData;
    }

    /**
     * Generates the signature base for this transaction
     *
     * The signature base is what gets signed by keypairs. It includes the network
     * passphrase hash, envelope type, and transaction XDR.
     *
     * @param Network $network The network this transaction is intended for
     * @return string Raw bytes to be signed
     */
    public function signatureBase(Network $network): string
    {
        $bytes = Hash::generate($network->getNetworkPassphrase());
        $bytes .= XdrEncoder::unsignedInteger32(XdrEnvelopeType::ENVELOPE_TYPE_TX);
        $bytes .= $this->toXdr()->encode();
        return $bytes;
    }

    /**
     * Converts this transaction to its XDR representation
     *
     * @return XdrTransaction XDR format of this transaction
     */
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

    /**
     * Converts this transaction to base64-encoded XDR
     *
     * @return string Base64-encoded XDR representation
     */
    public function toXdrBase64() : string {
        $xdr = $this->toXdr();
        return base64_encode($xdr->encode());
    }

    /**
     * Converts this transaction to a complete XDR transaction envelope
     *
     * The envelope includes the transaction data and all signatures.
     *
     * @return XdrTransactionEnvelope Complete transaction envelope ready for submission
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

    /**
     * Creates a Transaction object from a V1 transaction envelope XDR
     *
     * @param XdrTransactionV1Envelope $envelope The XDR envelope to parse
     * @return Transaction The reconstructed transaction with signatures
     */
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

    /**
     * Creates a Transaction object from a V0 transaction envelope XDR
     *
     * V0 transactions are the older format. This method converts them to the current
     * Transaction format for compatibility.
     *
     * @param XdrTransactionV0Envelope $envelope The V0 XDR envelope to parse
     * @return Transaction The reconstructed transaction with signatures
     */
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

    /**
     * Creates a new TransactionBuilder for constructing transactions
     *
     * This is the recommended way to build transactions with a fluent interface.
     *
     * @param TransactionBuilderAccount $sourceAccount The source account for the transaction
     * @return TransactionBuilder Builder for constructing the transaction
     * @see TransactionBuilder
     */
    public static function builder(TransactionBuilderAccount $sourceAccount) : TransactionBuilder{
        return new TransactionBuilder($sourceAccount);
    }
}