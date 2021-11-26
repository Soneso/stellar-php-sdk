<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

/// Transaction envelope used before protocol 13.
class XdrTransactionV0Envelope
{
    private XdrTransactionV0 $tx;
    private array $signatures; //[XdrDecoratedSignature]


    public function __construct(XdrTransactionV0 $tx, array $signatures) {
        $this->tx = $tx;
        $this->signatures = $signatures;
    }

    /**
     * @return XdrTransactionV0
     */
    public function getTx(): XdrTransactionV0
    {
        return $this->tx;
    }

    /**
     * @return array
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
    public static function decode(XdrBuffer $xdr) : XdrTransactionV0Envelope {
        $tx = XdrTransactionV0::decode($xdr);
        $count = $xdr->readInteger32();
        $signatures = array();
        for ($i = 0; $i < $count; $i++) {
            array_push($signatures, XdrDecoratedSignature::decode($xdr));
        }
        return new XdrTransactionV0Envelope($tx, $signatures);
    }
}