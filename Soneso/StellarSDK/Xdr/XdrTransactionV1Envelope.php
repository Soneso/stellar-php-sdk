<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrTransactionV1Envelope
{
    public XdrTransaction $tx;
    /**
     * @var array<XdrDecoratedSignature>
     */
    public array $signatures;


    /**
     * @param XdrTransaction $tx
     * @param array<XdrDecoratedSignature> $signatures
     */
    public function __construct(XdrTransaction $tx, array $signatures) {
        $this->tx = $tx;
        $this->signatures = $signatures;
    }

    /**
     * @return XdrTransaction
     */
    public function getTx(): XdrTransaction
    {
        return $this->tx;
    }

    /**
     * @param array<XdrDecoratedSignature> $signatures
     */
    public function setSignatures(array $signatures): void
    {
        $this->signatures = $signatures;
    }

    /**
     * @return array<XdrDecoratedSignature>
     */
    public function getSignatures(): array
    {
        return $this->signatures;
    }


    public function encode(): string {
        $bytes = $this->tx->encode();
        $bytes .= XdrEncoder::integer32(count($this->signatures));
        foreach ($this->signatures as $signature) {
            $bytes .= $signature->encode();
        }
        return $bytes;
    }
    public static function decode(XdrBuffer $xdr) : XdrTransactionV1Envelope {
        $tx = XdrTransaction::decode($xdr);
        $count = $xdr->readInteger32();
        $signatures = array();
        for ($i = 0; $i < $count; $i++) {
            array_push($signatures, XdrDecoratedSignature::decode($xdr));
        }
        return new XdrTransactionV1Envelope($tx, $signatures);
    }
}