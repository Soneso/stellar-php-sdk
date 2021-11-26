<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.
namespace Soneso\StellarSDK\Xdr;

class XdrRevokeSponsorshipOperation
{
    private XdrRevokeSponsorshipType $type;
    private ?XdrLedgerKey $ledgerKey = null;
    private ?XdrRevokeSponsorshipSigner $signer = null;

    /**
     * @return XdrRevokeSponsorshipType
     */
    public function getType(): XdrRevokeSponsorshipType
    {
        return $this->type;
    }

    /**
     * @param XdrRevokeSponsorshipType $type
     */
    public function setType(XdrRevokeSponsorshipType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrLedgerKey|null
     */
    public function getLedgerKey(): ?XdrLedgerKey
    {
        return $this->ledgerKey;
    }

    /**
     * @param XdrLedgerKey|null $ledgerKey
     */
    public function setLedgerKey(?XdrLedgerKey $ledgerKey): void
    {
        $this->ledgerKey = $ledgerKey;
    }

    /**
     * @return XdrRevokeSponsorshipSigner|null
     */
    public function getSigner(): ?XdrRevokeSponsorshipSigner
    {
        return $this->signer;
    }

    /**
     * @param XdrRevokeSponsorshipSigner|null $signer
     */
    public function setSigner(?XdrRevokeSponsorshipSigner $signer): void
    {
        $this->signer = $signer;
    }

    public function encode() : string {
        $bytes = $this->type->encode();
        if ($this->ledgerKey) {
            $bytes .= $this->ledgerKey->encode();
        } else if ($this->signer) {
            $bytes .= $this->signer->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrRevokeSponsorshipOperation {
        $op = new XdrRevokeSponsorshipOperation();
        $op->type = XdrRevokeSponsorshipType::decode($xdr);
        if ($op->type->getValue() == XdrRevokeSponsorshipType::LEDGER_ENTRY) {
            $op->ledgerKey = XdrLedgerKey::decode($xdr);
        } else if ($op->type->getValue() == XdrRevokeSponsorshipType::SIGNER) {
            $op->signer = XdrRevokeSponsorshipSigner::decode($xdr);
        }
        return $op;
    }
}