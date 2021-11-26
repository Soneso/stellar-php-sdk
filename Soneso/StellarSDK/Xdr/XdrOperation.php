<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrOperation
{
    private ?XdrMuxedAccount $sourceAccount = null;
    private XdrOperationBody $body;

    public function __construct(XdrOperationBody $body, ?XdrMuxedAccount $sourceAccount = null) {
        $this->body = $body;
        $this->sourceAccount = $sourceAccount;
    }

    /**
     * @return XdrMuxedAccount|null
     */
    public function getSourceAccount(): ?XdrMuxedAccount
    {
        return $this->sourceAccount;
    }

    /**
     * @param XdrMuxedAccount|null $sourceAccount
     */
    public function setSourceAccount(?XdrMuxedAccount $sourceAccount): void
    {
        $this->sourceAccount = $sourceAccount;
    }

    /**
     * @return XdrOperationBody
     */
    public function getBody(): XdrOperationBody
    {
        return $this->body;
    }


    public function encode() : string {
        $bytes = $this->sourceAccount != null ? XdrEncoder::integer32(1) : XdrEncoder::integer32(0);
        if ($this->sourceAccount != null) {
            $bytes .= $this->sourceAccount->encode();
        }
        $bytes .= $this->body->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrOperation {
        $sourceAccount = null;
        if ($xdr->readInteger32() == 1) {
            $sourceAccount = XdrMuxedAccount::decode($xdr);
        }
        $body = XdrOperationBody::decode($xdr);

        return new XdrOperation($body, $sourceAccount);
    }
}