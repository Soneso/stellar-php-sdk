<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrFeeBumpTransactionInnerTx
{
    private XdrEnvelopeType $type;
    private XdrTransactionV1Envelope $v1;

    public function __construct(XdrEnvelopeType $type, XdrTransactionV1Envelope $v1)
    {
        $this->type = $type;
        $this->v1 = $v1;
    }

    /**
     * @return XdrEnvelopeType
     */
    public function getType(): XdrEnvelopeType
    {
        return $this->type;
    }

    /**
     * @return XdrTransactionV1Envelope
     */
    public function getV1(): XdrTransactionV1Envelope
    {
        return $this->v1;
    }

    public function encode() : string {
        $bytes = $this->type->encode();
        if ($this->type->getValue() == XdrEnvelopeType::ENVELOPE_TYPE_TX) {
            $bytes .= $this->v1->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrFeeBumpTransactionInnerTx {
        $type = XdrEnvelopeType::decode($xdr);
        $v1 = null;
        if ($type->getValue() == XdrEnvelopeType::ENVELOPE_TYPE_TX) {
            $v1 = XdrTransactionV1Envelope::decode($xdr);
        }
        return new XdrFeeBumpTransactionInnerTx($type, $v1);
    }
}