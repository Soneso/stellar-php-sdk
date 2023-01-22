<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTrustLineEntry
{
    public XdrAccountID $accountID;
    public XdrTrustlineAsset $asset;
    public int $balance; // int64
    public int $limit; // int64
    public int $flags; // uint32
    public XdrTrustLineEntryExt $ext;

    /**
     * @param XdrAccountID $accountID
     * @param XdrTrustlineAsset $asset
     * @param int $balance
     * @param int $limit
     * @param int $flags
     * @param XdrTrustLineEntryExt $ext
     */
    public function __construct(XdrAccountID $accountID, XdrTrustlineAsset $asset, int $balance, int $limit, int $flags, XdrTrustLineEntryExt $ext)
    {
        $this->accountID = $accountID;
        $this->asset = $asset;
        $this->balance = $balance;
        $this->limit = $limit;
        $this->flags = $flags;
        $this->ext = $ext;
    }


    public function encode(): string {
        $bytes = $this->accountID->encode();
        $bytes .= $this->asset->encode();
        $bytes .= XdrEncoder::integer64($this->balance);
        $bytes .= XdrEncoder::integer64($this->limit);
        $bytes .= XdrEncoder::unsignedInteger32($this->flags);
        $bytes .= $this->ext->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrTrustLineEntry {
        $accountID = XdrAccountID::decode($xdr);
        $asset = XdrTrustlineAsset::decode($xdr);
        $balance = $xdr->readInteger64();
        $limit = $xdr->readInteger64();
        $flags = $xdr->readUnsignedInteger32();
        $ext = XdrTrustLineEntryExt::decode($xdr);

        return new XdrTrustLineEntry($accountID, $asset, $balance, $limit, $flags, $ext);
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
     * @return XdrTrustlineAsset
     */
    public function getAsset(): XdrTrustlineAsset
    {
        return $this->asset;
    }

    /**
     * @param XdrTrustlineAsset $asset
     */
    public function setAsset(XdrTrustlineAsset $asset): void
    {
        $this->asset = $asset;
    }

    /**
     * @return int
     */
    public function getBalance(): int
    {
        return $this->balance;
    }

    /**
     * @param int $balance
     */
    public function setBalance(int $balance): void
    {
        $this->balance = $balance;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
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
     * @return XdrTrustLineEntryExt
     */
    public function getExt(): XdrTrustLineEntryExt
    {
        return $this->ext;
    }

    /**
     * @param XdrTrustLineEntryExt $ext
     */
    public function setExt(XdrTrustLineEntryExt $ext): void
    {
        $this->ext = $ext;
    }
}