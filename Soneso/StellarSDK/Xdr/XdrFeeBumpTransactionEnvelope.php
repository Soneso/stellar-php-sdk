<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrFeeBumpTransactionEnvelope
{
    public XdrFeeBumpTransaction $tx;
    /**
     * @var array<XdrDecoratedSignature> $signatures
     */
    public array $signatures;


    /**
     * Constructor.
     * @param XdrFeeBumpTransaction $tx
     * @param array<XdrDecoratedSignature> $signatures
     */
    public function __construct(XdrFeeBumpTransaction $tx, array $signatures) {
        $this->tx = $tx;
        $this->signatures = $signatures;
    }

    /**
     * @return XdrFeeBumpTransaction
     */
    public function getTx(): XdrFeeBumpTransaction
    {
        return $this->tx;
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
            if ($signature instanceof XdrDecoratedSignature) {
                $bytes .= $signature->encode();
            }
        }
        return $bytes;
    }
    public static function decode(XdrBuffer $xdr) : XdrFeeBumpTransactionEnvelope {
        $tx = XdrFeeBumpTransaction::decode($xdr);
        $count = $xdr->readInteger32();
        $signatures = array();
        for ($i = 0; $i < $count; $i++) {
            array_push($signatures, XdrDecoratedSignature::decode($xdr));
        }
        return new XdrFeeBumpTransactionEnvelope($tx, $signatures);
    }
}