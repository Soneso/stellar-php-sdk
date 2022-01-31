<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Util\Hash;
use Soneso\StellarSDK\Xdr\XdrBuffer;
use Soneso\StellarSDK\Xdr\XdrDecoratedSignature;
use Soneso\StellarSDK\Xdr\XdrEnvelopeType;
use Soneso\StellarSDK\Xdr\XdrTransactionEnvelope;

abstract class AbstractTransaction
{
    const MIN_BASE_FEE = 100;
    private array $signatures; //[XdrDecoratedSignature]

    public function __construct() {
        $this->signatures = array();
    }

    public function sign(KeyPair $signer, Network $network) : void {
        if ($signer->getPrivateKey() == null) {
            throw new \InvalidArgumentException("signer needs private key to be able to sign");
        }
        $txHash = $this->hash($network);
        array_push($this->signatures, $signer->signDecorated($txHash));
    }

    public function hash(Network $network) : string {
        return Hash::generate($this->signatureBase($network));
    }

    public abstract function signatureBase(Network $network) : string;

    public function addSignature(XdrDecoratedSignature $signature) : void {
        array_push($this->signatures, $signature);
    }
    /**
     * @return array
     */
    public function getSignatures(): array
    {
        return $this->signatures;
    }

    /**
     * @param array $signatures
     */
    public function setSignatures(array $signatures): void
    {
        $this->signatures = $signatures;
    }

    public abstract function toEnvelopeXdr() : XdrTransactionEnvelope;

    public function toEnvelopeXdrBase64() :string {
        $xdrEnvelope = $this->toEnvelopeXdr();
        $bytes = $xdrEnvelope->encode();
        return base64_encode($bytes);
    }

    public static function fromEnvelopeXdr(XdrTransactionEnvelope $envelope) : AbstractTransaction {

        return match ($envelope->getType()->getValue()) {
            XdrEnvelopeType::ENVELOPE_TYPE_TX_V0 => Transaction::fromV0EnvelopeXdr($envelope->getV0()),
            XdrEnvelopeType::ENVELOPE_TYPE_TX => Transaction::fromV1EnvelopeXdr($envelope->getV1()),
            XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP => FeeBumpTransaction::fromFeeBumpTransactionEnvelope($envelope->getFeeBump()),
            default => throw new \InvalidArgumentException("unknown envelope type: " . $envelope->getType()->getValue())
        };
    }

    public static function fromEnvelopeBase64XdrString(string $envelope) : AbstractTransaction {
        $xdr = base64_decode($envelope);
        $xdrBuffer = new XdrBuffer($xdr);
        $xdrEnvelope = XdrTransactionEnvelope::decode($xdrBuffer);
        return static::fromEnvelopeXdr($xdrEnvelope);
    }
}