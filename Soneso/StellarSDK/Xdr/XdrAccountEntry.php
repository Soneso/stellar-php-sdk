<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use phpseclib3\Math\BigInteger;

class XdrAccountEntry
{
    public XdrAccountID $accountID;
    public BigInteger $balance;
    public XdrSequenceNumber $seqNum;
    public int $numSubEntries; // uint32
    public ?XdrAccountID $inflationDest = null;
    public int $flags; // uint32
    public string $homeDomain; // string 32
    public string $thresholds;
    public array $signers;  // [XdrSigner]
    public XdrAccountEntryExt $ext;

    /**
     * @param XdrAccountID $accountID
     * @param BigInteger $balance
     * @param XdrSequenceNumber $seqNum
     * @param int $numSubEntries
     * @param XdrAccountID|null $inflationDest
     * @param int $flags
     * @param string $homeDomain
     * @param string $thresholds
     * @param array $signers
     * @param XdrAccountEntryExt $ext
     */
    public function __construct(XdrAccountID $accountID, BigInteger $balance, XdrSequenceNumber $seqNum, int $numSubEntries, ?XdrAccountID $inflationDest, int $flags, string $homeDomain, string $thresholds, array $signers, XdrAccountEntryExt $ext)
    {
        $this->accountID = $accountID;
        $this->balance = $balance;
        $this->seqNum = $seqNum;
        $this->numSubEntries = $numSubEntries;
        $this->inflationDest = $inflationDest;
        $this->flags = $flags;
        $this->homeDomain = $homeDomain;
        $this->thresholds = $thresholds;
        $this->signers = $signers;
        $this->ext = $ext;
    }


    public function encode(): string {
        $bytes = $this->accountID->encode();
        $bytes .= XdrEncoder::bigInteger64($this->balance);
        $bytes .= $this->seqNum->encode();
        $bytes .= XdrEncoder::unsignedInteger32($this->numSubEntries);
        if ($this->inflationDest != null) {
            $bytes .= XdrEncoder::integer32(1);
            $bytes .= $this->inflationDest->encode();
        } else {
            $bytes .= XdrEncoder::integer32(0);
        }
        $bytes .= XdrEncoder::unsignedInteger32($this->flags);
        $bytes .= XdrEncoder::string($this->homeDomain, 32);
        $bytes .= XdrEncoder::opaqueFixed($this->thresholds,4,true);
        $bytes .= XdrEncoder::integer32(count($this->signers));
        foreach($this->signers as $val) {
            $bytes .= $val->encode();
        }
        $bytes .= $this->ext->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrAccountEntry {
        $accountID = XdrAccountID::decode($xdr);
        $balance = $xdr->readBigInteger64();
        $seqNum = XdrSequenceNumber::decode($xdr);
        $numSubEntries = $xdr->readUnsignedInteger32();
        $inflationDest = null;
        if ($xdr->readInteger32() == 1) {
            $inflationDest = XdrAccountID::decode($xdr);
        }
        $flags = $xdr->readUnsignedInteger32();
        $homeDomain = $xdr->readString(32);
        $thresholds = $xdr->readOpaqueFixed(4);
        $valCount = $xdr->readInteger32();
        $signersArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($signersArr, XdrSigner::decode($xdr));
        }
        $ext = XdrAccountEntryExt::decode($xdr);

        return new XdrAccountEntry($accountID, $balance, $seqNum, $numSubEntries,
            $inflationDest, $flags, $homeDomain, $thresholds, $signersArr, $ext);
    }

    /**
     * @return XdrAccountID
     */
    public function getAccountID(): XdrAccountID
    {
        return $this->accountID;
    }

    /**
     * @param XdrAccountID $accountID
     */
    public function setAccountID(XdrAccountID $accountID): void
    {
        $this->accountID = $accountID;
    }

    /**
     * @return int|BigInteger
     */
    public function getBalance(): BigInteger|int
    {
        return $this->balance;
    }

    /**
     * @param int|BigInteger $balance
     */
    public function setBalance(BigInteger|int $balance): void
    {
        $this->balance = $balance;
    }


    /**
     * @return XdrSequenceNumber
     */
    public function getSeqNum(): XdrSequenceNumber
    {
        return $this->seqNum;
    }

    /**
     * @param XdrSequenceNumber $seqNum
     */
    public function setSeqNum(XdrSequenceNumber $seqNum): void
    {
        $this->seqNum = $seqNum;
    }

    /**
     * @return int
     */
    public function getNumSubEntries(): int
    {
        return $this->numSubEntries;
    }

    /**
     * @param int $numSubEntries
     */
    public function setNumSubEntries(int $numSubEntries): void
    {
        $this->numSubEntries = $numSubEntries;
    }

    /**
     * @return XdrAccountID|null
     */
    public function getInflationDest(): ?XdrAccountID
    {
        return $this->inflationDest;
    }

    /**
     * @param XdrAccountID|null $inflationDest
     */
    public function setInflationDest(?XdrAccountID $inflationDest): void
    {
        $this->inflationDest = $inflationDest;
    }

    /**
     * @return int
     */
    public function getFlags(): int
    {
        return $this->flags;
    }

    /**
     * @param int $flags
     */
    public function setFlags(int $flags): void
    {
        $this->flags = $flags;
    }

    /**
     * @return string
     */
    public function getHomeDomain(): string
    {
        return $this->homeDomain;
    }

    /**
     * @param string $homeDomain
     */
    public function setHomeDomain(string $homeDomain): void
    {
        $this->homeDomain = $homeDomain;
    }

    /**
     * @return string
     */
    public function getThresholds(): string
    {
        return $this->thresholds;
    }

    /**
     * @param string $thresholds
     */
    public function setThresholds(string $thresholds): void
    {
        $this->thresholds = $thresholds;
    }

    /**
     * @return array
     */
    public function getSigners(): array
    {
        return $this->signers;
    }

    /**
     * @param array $signers
     */
    public function setSigners(array $signers): void
    {
        $this->signers = $signers;
    }

    /**
     * @return XdrAccountEntryExt
     */
    public function getExt(): XdrAccountEntryExt
    {
        return $this->ext;
    }

    /**
     * @param XdrAccountEntryExt $ext
     */
    public function setExt(XdrAccountEntryExt $ext): void
    {
        $this->ext = $ext;
    }

}