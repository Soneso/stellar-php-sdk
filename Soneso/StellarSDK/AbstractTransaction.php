<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Constants\StellarConstants;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Util\Hash;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;

/**
 * Abstract base class for all Stellar transaction types
 *
 * This class provides common functionality for regular transactions and fee bump
 * transactions, including signature management and XDR serialization. All transactions
 * must be signed by the required signers before submission to the network.
 *
 * Transaction Types:
 * - Transaction: Regular transactions containing operations
 * - FeeBumpTransaction: Wrapper transactions that increase fees for existing transactions
 *
 * Security Notice: Always verify transaction contents before signing. Private keys
 * should be handled securely and never exposed in logs or transmitted over insecure channels.
 *
 * @package Soneso\StellarSDK
 * @see Transaction For regular transactions
 * @see FeeBumpTransaction For fee bump transactions
 * @see https://developers.stellar.org Stellar developer docs
 * @since 1.0.0
 */
abstract class AbstractTransaction
{
    /**
     * @var array<XdrDecoratedSignature> $signatures
     */
    private array $signatures;

    /**
     * Constructs a new AbstractTransaction with an empty signature list
     */
    public function __construct() {
        $this->signatures = array();
    }

    /**
     * Signs the transaction with the provided keypair
     *
     * @param KeyPair $signer The keypair used to sign the transaction (must have private key)
     * @param Network $network The network for which this transaction is intended
     * @return void
     * @throws \InvalidArgumentException If the signer does not have a private key
     */
    public function sign(KeyPair $signer, Network $network) : void {
        if ($signer->getPrivateKey() == null) {
            throw new \InvalidArgumentException("signer needs private key to be able to sign");
        }
        $txHash = $this->hash($network);
        array_push($this->signatures, $signer->signDecorated($txHash));
    }

    /**
     * Calculates the hash of the transaction for signing
     *
     * @param Network $network The network for which to calculate the hash
     * @return string The transaction hash
     */
    public function hash(Network $network) : string {
        return Hash::generate($this->signatureBase($network));
    }

    /**
     * Returns the signature base for this transaction
     *
     * @param Network $network The network for which to generate the signature base
     * @return string The signature base bytes
     */
    public abstract function signatureBase(Network $network) : string;

    /**
     * Adds a signature to the transaction
     *
     * @param XdrDecoratedSignature $signature The signature to add
     * @return void
     */
    public function addSignature(XdrDecoratedSignature $signature) : void {
        array_push($this->signatures, $signature);
    }

    /**
     * Gets all signatures attached to this transaction
     *
     * @return array<XdrDecoratedSignature> Array of decorated signatures
     */
    public function getSignatures(): array
    {
        return $this->signatures;
    }

    /**
     * Sets the signatures for this transaction
     *
     * @param array<XdrDecoratedSignature> $signatures Array of decorated signatures
     * @return void
     */
    public function setSignatures(array $signatures): void
    {
        $this->signatures = $signatures;
    }

    /**
     * Converts the transaction to XDR envelope format
     *
     * @return XdrTransactionEnvelope The XDR transaction envelope
     */
    public abstract function toEnvelopeXdr() : XdrTransactionEnvelope;

    /**
     * Converts the transaction to base64-encoded XDR envelope format
     *
     * @return string Base64-encoded XDR transaction envelope
     */
    public function toEnvelopeXdrBase64() :string {
        $xdrEnvelope = $this->toEnvelopeXdr();
        $bytes = $xdrEnvelope->encode();
        return base64_encode($bytes);
    }

    /**
     * Creates a transaction from an XDR transaction envelope
     *
     * @param XdrTransactionEnvelope $envelope The XDR transaction envelope
     * @return AbstractTransaction The decoded transaction (Transaction or FeeBumpTransaction)
     * @throws \InvalidArgumentException If the envelope type is unknown
     */
    public static function fromEnvelopeXdr(XdrTransactionEnvelope $envelope) : AbstractTransaction {

        return match ($envelope->getType()->getValue()) {
            XdrEnvelopeType::ENVELOPE_TYPE_TX_V0 => Transaction::fromV0EnvelopeXdr($envelope->getV0()),
            XdrEnvelopeType::ENVELOPE_TYPE_TX => Transaction::fromV1EnvelopeXdr($envelope->getV1()),
            XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP => FeeBumpTransaction::fromFeeBumpTransactionEnvelope($envelope->getFeeBump()),
            default => throw new \InvalidArgumentException("unknown envelope type: " . $envelope->getType()->getValue())
        };
    }

    /**
     * Creates a transaction from a base64-encoded XDR string
     *
     * @param string $envelope Base64-encoded XDR transaction envelope
     * @return AbstractTransaction The decoded transaction (Transaction or FeeBumpTransaction)
     * @throws \InvalidArgumentException If the envelope type is unknown or XDR is invalid
     */
    public static function fromEnvelopeBase64XdrString(string $envelope) : AbstractTransaction {
        $xdr = base64_decode($envelope);
        $xdrBuffer = new XdrBuffer($xdr);
        $xdrEnvelope = XdrTransactionEnvelope::decode($xdrBuffer);
        return static::fromEnvelopeXdr($xdrEnvelope);
    }
}