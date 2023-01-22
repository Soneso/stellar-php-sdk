<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTransactionEnvelope
{
    private XdrEnvelopeType $type;
    private ?XdrTransactionV1Envelope $v1 = null;
    private ?XdrTransactionV0Envelope $v0 = null;
    private ?XdrFeeBumpTransactionEnvelope $feeBump = null;

    public function __construct(XdrEnvelopeType $type) {
        $this->type = $type;
    }

    /**
     * @return XdrEnvelopeType
     */
    public function getType(): XdrEnvelopeType
    {
        return $this->type;
    }


    /**
     * @return XdrTransactionV1Envelope|null
     */
    public function getV1(): ?XdrTransactionV1Envelope
    {
        return $this->v1;
    }

    /**
     * @param XdrTransactionV1Envelope|null $v1
     */
    public function setV1(?XdrTransactionV1Envelope $v1): void
    {
        $this->v1 = $v1;
    }

    /**
     * @return XdrTransactionV0Envelope|null
     */
    public function getV0(): ?XdrTransactionV0Envelope
    {
        return $this->v0;
    }

    /**
     * @param XdrTransactionV0Envelope|null $v0
     */
    public function setV0(?XdrTransactionV0Envelope $v0): void
    {
        $this->v0 = $v0;
    }

    /**
     * @return XdrFeeBumpTransactionEnvelope|null
     */
    public function getFeeBump(): ?XdrFeeBumpTransactionEnvelope
    {
        return $this->feeBump;
    }

    /**
     * @param XdrFeeBumpTransactionEnvelope|null $feeBump
     */
    public function setFeeBump(?XdrFeeBumpTransactionEnvelope $feeBump): void
    {
        $this->feeBump = $feeBump;
    }

    public function encode(): string {
        $bytes = $this->type->encode();

        $bytes .= match ($this->type->getValue()) {
            XdrEnvelopeType::ENVELOPE_TYPE_TX_V0 => $this->v0->encode(),
            XdrEnvelopeType::ENVELOPE_TYPE_TX => $this->v1->encode(),
            XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP => $this->feeBump->encode()
        };

        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrTransactionEnvelope {
        $type = new XdrEnvelopeType($xdr->readInteger32());
        $envelope = new XdrTransactionEnvelope($type);
        switch ($type->getValue()) {
            case XdrEnvelopeType::ENVELOPE_TYPE_TX_V0:
                $envelope->setV0(XdrTransactionV0Envelope::decode($xdr));
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_TX:
                $envelope->setV1(XdrTransactionV1Envelope::decode($xdr));
                break;
            case XdrEnvelopeType::ENVELOPE_TYPE_TX_FEE_BUMP:
                $envelope->setFeeBump(XdrFeeBumpTransactionEnvelope::decode($xdr));
                break;
        }
        return $envelope;
    }

    public static function fromEnvelopeBase64XdrString(string $envelope) : XdrTransactionEnvelope {
        $xdr = base64_decode($envelope);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrTransactionEnvelope::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }
}