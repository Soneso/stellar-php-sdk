<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrHashIDPreimageRevokeID
{
    public XdrAccountID $sourceAccount;
    public XdrSequenceNumber $seqNum;
    public int $opNum; //uint32
    public string $liquidityPoolID; //hash
    public XdrAsset $asset;

    /**
     * @param XdrAccountID $sourceAccount
     * @param XdrSequenceNumber $seqNum
     * @param int $opNum
     * @param string $liquidityPoolID
     * @param XdrAsset $asset
     */
    public function __construct(XdrAccountID $sourceAccount, XdrSequenceNumber $seqNum, int $opNum, string $liquidityPoolID, XdrAsset $asset)
    {
        $this->sourceAccount = $sourceAccount;
        $this->seqNum = $seqNum;
        $this->opNum = $opNum;
        $this->liquidityPoolID = $liquidityPoolID;
        $this->asset = $asset;
    }


    public function encode() : string {
        $bytes = $this->sourceAccount->encode();
        $bytes .= $this->seqNum->encode();
        $bytes .= XdrEncoder::unsignedInteger32($this->opNum);
        $bytes .= XdrEncoder::opaqueFixed($this->liquidityPoolID, 32);
        $bytes .= $this->asset->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrHashIDPreimageRevokeID {
        $sourceAccount = XdrAccountID::decode($xdr);
        $seqNum = XdrSequenceNumber::decode($xdr);
        $opNum = $xdr->readUnsignedInteger32();
        $liquidityPoolID = $xdr->readOpaqueFixed(32);
        $asset = XdrAsset::decode($xdr);
        return new XdrHashIDPreimageRevokeID($sourceAccount, $seqNum, $opNum, $liquidityPoolID, $asset);
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

    /**
     * @return string
     */
    public function getLiquidityPoolID(): string
    {
        return $this->liquidityPoolID;
    }

    /**
     * @param string $liquidityPoolID
     */
    public function setLiquidityPoolID(string $liquidityPoolID): void
    {
        $this->liquidityPoolID = $liquidityPoolID;
    }

    /**
     * @return XdrAsset
     */
    public function getAsset(): XdrAsset
    {
        return $this->asset;
    }

    /**
     * @param XdrAsset $asset
     */
    public function setAsset(XdrAsset $asset): void
    {
        $this->asset = $asset;
    }
}