<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrPreconditionsV2
{
    private ?XdrTimeBounds $timeBounds = null;
    private ?XdrLedgerBounds $ledgerBounds = null;
    private ?XdrSequenceNumber $minSeqNum = null;
    private int $minSeqAge = 0;
    private int $minSeqLedgerGap = 0;
    private array $extraSigners = [];

    /**
     * @return XdrTimeBounds|null
     */
    public function getTimeBounds(): ?XdrTimeBounds
    {
        return $this->timeBounds;
    }

    /**
     * @param XdrTimeBounds|null $timeBounds
     */
    public function setTimeBounds(?XdrTimeBounds $timeBounds): void
    {
        $this->timeBounds = $timeBounds;
    }

    /**
     * @return XdrLedgerBounds|null
     */
    public function getLedgerBounds(): ?XdrLedgerBounds
    {
        return $this->ledgerBounds;
    }

    /**
     * @param XdrLedgerBounds|null $ledgerBounds
     */
    public function setLedgerBounds(?XdrLedgerBounds $ledgerBounds): void
    {
        $this->ledgerBounds = $ledgerBounds;
    }

    /**
     * @return XdrSequenceNumber|null
     */
    public function getMinSeqNum(): ?XdrSequenceNumber
    {
        return $this->minSeqNum;
    }

    /**
     * @param XdrSequenceNumber|null $minSeqNum
     */
    public function setMinSeqNum(?XdrSequenceNumber $minSeqNum): void
    {
        $this->minSeqNum = $minSeqNum;
    }

    /**
     * @return int
     */
    public function getMinSeqAge(): int
    {
        return $this->minSeqAge;
    }

    /**
     * @param int $minSeqAge
     */
    public function setMinSeqAge(int $minSeqAge): void
    {
        $this->minSeqAge = $minSeqAge;
    }

    /**
     * @return int
     */
    public function getMinSeqLedgerGap(): int
    {
        return $this->minSeqLedgerGap;
    }

    /**
     * @param int $minSeqLedgerGap
     */
    public function setMinSeqLedgerGap(int $minSeqLedgerGap): void
    {
        $this->minSeqLedgerGap = $minSeqLedgerGap;
    }

    /**
     * @return array
     */
    public function getExtraSigners(): array
    {
        return $this->extraSigners;
    }

    /**
     * @param array $extraSigners
     */
    public function setExtraSigners(array $extraSigners): void
    {
        $this->extraSigners = $extraSigners;
    }

    public function encode(): string {
        $bytes = "";

        if ($this->timeBounds !== null) {
            $bytes .= XdrEncoder::integer32(1);
            $bytes .= $this->timeBounds->encode();
        }
        else {
            $bytes .= XdrEncoder::integer32(0);
        }

        if ($this->ledgerBounds !== null) {
            $bytes .= XdrEncoder::integer32(1);
            $bytes .= $this->ledgerBounds->encode();
        }
        else {
            $bytes .= XdrEncoder::integer32(0);
        }

        if ($this->minSeqNum !== null) {
            $bytes .= XdrEncoder::integer32(1);
            $bytes .= $this->minSeqNum->encode();
        }
        else {
            $bytes .= XdrEncoder::integer32(0);
        }

        $bytes .= XdrEncoder::unsignedInteger64($this->minSeqAge);
        $bytes .= XdrEncoder::unsignedInteger32($this->minSeqLedgerGap);
        $bytes .= XdrEncoder::integer32(count($this->extraSigners));
        foreach($this->extraSigners as $extraSigner) {
            if ($extraSigner instanceof XdrSignerKey) {
                $bytes .= $extraSigner->encode();
            }
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrPreconditionsV2 {
        $result = new XdrPreconditionsV2();
        if ($xdr->readInteger32() == 1) {
            $result->setTimeBounds(XdrTimeBounds::decode($xdr));
        }
        if ($xdr->readInteger32() == 1) {
            $result->setLedgerBounds(XdrLedgerBounds::decode($xdr));
        }
        if ($xdr->readInteger32() == 1) {
            $result->setMinSeqNum(XdrSequenceNumber::decode($xdr));
        }
        $result->setMinSeqAge($xdr->readUnsignedInteger64());
        $result->setMinSeqLedgerGap($xdr->readUnsignedInteger32());

        $extraSignersCount = $xdr->readInteger32();
        $extraSigners = array();
        for ($i = 0; $i < $extraSignersCount; $i++) {
            array_push($extraSigners, XdrSignerKey::decode($xdr));
        }
        $result->setExtraSigners($extraSigners);
        return $result;
    }
}