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

/**
 * Represents a fee bump transaction
 *
 * Fee bump transactions allow an account to retroactively increase the fee on a
 * previously submitted transaction. This is useful when a transaction is stuck in
 * the queue due to insufficient fees or when network congestion requires higher fees.
 *
 * The fee bump transaction wraps an existing transaction (the inner transaction) and
 * specifies a new, higher fee. The fee account pays the additional fee, which must be
 * at least the minimum network fee plus the original transaction's fee.
 *
 * Key Characteristics:
 * - Contains an inner transaction that must already be signed
 * - Specifies a fee account (can be different from inner transaction's source)
 * - New fee must be higher than the inner transaction's fee
 * - Fee account must sign the fee bump transaction
 *
 * Use Cases:
 * - Unstick transactions with low fees
 * - Priority transaction processing
 * - Third-party fee sponsorship
 *
 * Usage:
 * <code>
 * // Create a fee bump transaction
 * $feeBump = new FeeBumpTransaction(
 *     $feeAccount,
 *     200000, // New higher fee in stroops
 *     $innerTransaction
 * );
 *
 * // Sign and submit
 * $feeBump->sign($feeAccountKeyPair, $network);
 * $response = $sdk->submitTransaction($feeBump);
 * </code>
 *
 * @package Soneso\StellarSDK
 * @see AbstractTransaction Base transaction functionality
 * @see Transaction The inner transaction type
 * @see FeeBumpTransactionBuilder For building fee bump transactions
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 */
class FeeBumpTransaction extends AbstractTransaction
{
    private int $fee;
    private MuxedAccount $feeAccount;
    private Transaction $innerTx;

    /**
     * Constructs a new FeeBumpTransaction
     *
     * @param MuxedAccount $feeAccount The account paying the fee (can be muxed)
     * @param int $fee The new fee in stroops (must be higher than inner transaction fee)
     * @param Transaction $innerTx The inner transaction to bump
     */
    public function __construct(MuxedAccount $feeAccount, int $fee, Transaction $innerTx) {
        $this->fee = $fee;
        $this->feeAccount = $feeAccount;
        $this->innerTx = $innerTx;
        parent::__construct();
    }

    /**
     * Gets the fee in stroops
     *
     * @return int The fee amount in stroops
     */
    public function getFee(): int
    {
        return $this->fee;
    }

    /**
     * Gets the fee account
     *
     * @return MuxedAccount The account paying the fee
     */
    public function getFeeAccount(): MuxedAccount
    {
        return $this->feeAccount;
    }

    /**
     * Gets the inner transaction
     *
     * @return Transaction The wrapped inner transaction
     */
    public function getInnerTx(): Transaction
    {
        return $this->innerTx;
    }


    /**
     * Returns the signature base for this fee bump transaction
     *
     * @param Network $network The network for which to generate the signature base
     * @return string The signature base bytes
     * @throws Exception If inner transaction is not signed
     */
    public function signatureBase(Network $network): string
    {
        $bytes = Hash::generate($network->getNetworkPassphrase());
        $bytes .= XdrEncoder::unsignedInteger32(XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP);
        $bytes .= $this->toXdr()->encode();
        return $bytes;
    }

    /**
     * Converts the fee bump transaction to XDR format
     *
     * @return XdrFeeBumpTransaction The XDR representation
     */
    public function toXdr() : XdrFeeBumpTransaction {
        $xdrInnerTxV1 = $this->innerTx->toEnvelopeXdr()->getV1();
        $xdrInnerTx = new XdrFeeBumpTransactionInnerTx(new XdrEnvelopeType(XdrEnvelopeType::ENVELOPE_TYPE_TX), $xdrInnerTxV1);
        return new XdrFeeBumpTransaction($this->feeAccount->toXdr(), $this->fee, $xdrInnerTx);
    }


    /**
     * Converts the fee bump transaction to XDR envelope format
     *
     * @return XdrTransactionEnvelope The XDR transaction envelope
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