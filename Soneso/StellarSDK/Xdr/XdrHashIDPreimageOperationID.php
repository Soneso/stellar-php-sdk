<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrHashIDPreimageOperationID
{
    public XdrAccountID $sourceAccount;
    public XdrSequenceNumber $seqNum;
    public int $opNum; //uint32

    /**
     * @param XdrAccountID $sourceAccount
     * @param XdrSequenceNumber $seqNum
     * @param int $opNum
     */
    public function __construct(XdrAccountID $sourceAccount, XdrSequenceNumber $seqNum, int $opNum)
    {
        $this->sourceAccount = $sourceAccount;
        $this->seqNum = $seqNum;
        $this->opNum = $opNum;
    }


    public function encode() : string {
        $bytes = $this->sourceAccount->encode();
        $bytes .= $this->seqNum->encode();
        $bytes .= XdrEncoder::unsignedInteger32($this->opNum);
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrHashIDPreimageOperationID {
        $sourceAccount = XdrAccountID::decode($xdr);
        $seqNum = XdrSequenceNumber::decode($xdr);
        $opNum = $xdr->readUnsignedInteger32();
        return new XdrHashIDPreimageOperationID($sourceAccount, $seqNum, $opNum);
    }

    /**
     * @return XdrAccountID
     */
    public function getSourceAccount(): XdrAccountID
    {
        return $this->sourceAccount;
    }

    /**
     * @param XdrAccountID $sourceAccount
     */
    public function setSourceAccount(XdrAccountID $sourceAccount): void
    {
        $this->sourceAccount = $sourceAccount;
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
    public function getOpNum(): int
    {
        return $this->opNum;
    }

    /**
     * @param int $opNum
     */
    public function setOpNum(int $opNum): void
    {
        $this->opNum = $opNum;
    }
}