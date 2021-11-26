<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerKey
{
    private XdrLedgerEntryType $type;
    private ?XdrLedgerKeyAccount $account = null;
    private ?XdrLedgerKeyTrustLine $trustLine = null;
    private ?XdrLedgerKeyOffer $offer = null;
    private ?XdrLedgerKeyData $data = null;
    private ?XdrClaimableBalanceID $balanceID = null;
    private ?string $liquidityPoolID = null;

    public function __construct(XdrLedgerEntryType $type) {
        $this->type = $type;
    }

    /**
     * @return XdrLedgerEntryType
     */
    public function getType(): XdrLedgerEntryType
    {
        return $this->type;
    }

    /**
     * @param XdrLedgerEntryType $type
     */
    public function setType(XdrLedgerEntryType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrLedgerKeyAccount|null
     */
    public function getAccount(): ?XdrLedgerKeyAccount
    {
        return $this->account;
    }

    /**
     * @param XdrLedgerKeyAccount|null $account
     */
    public function setAccount(?XdrLedgerKeyAccount $account): void
    {
        $this->account = $account;
    }

    /**
     * @return XdrLedgerKeyTrustLine|null
     */
    public function getTrustLine(): ?XdrLedgerKeyTrustLine
    {
        return $this->trustLine;
    }

    /**
     * @param XdrLedgerKeyTrustLine|null $trustLine
     */
    public function setTrustLine(?XdrLedgerKeyTrustLine $trustLine): void
    {
        $this->trustLine = $trustLine;
    }

    /**
     * @return XdrLedgerKeyOffer|null
     */
    public function getOffer(): ?XdrLedgerKeyOffer
    {
        return $this->offer;
    }

    /**
     * @param XdrLedgerKeyOffer|null $offer
     */
    public function setOffer(?XdrLedgerKeyOffer $offer): void
    {
        $this->offer = $offer;
    }

    /**
     * @return XdrLedgerKeyData|null
     */
    public function getData(): ?XdrLedgerKeyData
    {
        return $this->data;
    }

    /**
     * @param XdrLedgerKeyData|null $data
     */
    public function setData(?XdrLedgerKeyData $data): void
    {
        $this->data = $data;
    }

    /**
     * @return XdrClaimableBalanceID|null
     */
    public function getBalanceID(): ?XdrClaimableBalanceID
    {
        return $this->balanceID;
    }

    /**
     * @param XdrClaimableBalanceID|null $balanceID
     */
    public function setBalanceID(?XdrClaimableBalanceID $balanceID): void
    {
        $this->balanceID = $balanceID;
    }

    /**
     * @return string|null
     */
    public function getLiquidityPoolID(): ?string
    {
        return $this->liquidityPoolID;
    }

    /**
     * @param string|null $liquidityPoolID
     */
    public function setLiquidityPoolID(?string $liquidityPoolID): void
    {
        $this->liquidityPoolID = $liquidityPoolID;
    }

    public function encode(): string {
        $bytes = $this->type->encode();
        $bytes .= match ($this->type->getValue()) {
            XdrLedgerEntryType::ACCOUNT => $this->account->encode(),
            XdrLedgerEntryType::TRUSTLINE => $this->trustLine->encode(),
            XdrLedgerEntryType::OFFER => $this->offer->encode(),
            XdrLedgerEntryType::DATA => $this->data->encode(),
            XdrLedgerEntryType::CLAIMABLE_BALANCE => $this->balanceID->encode(),
            XdrLedgerEntryType::LIQUIDITY_POOL => XdrEncoder::string($this->liquidityPoolID, 64),
        };
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrLedgerKey {
        $value = $xdr->readInteger32();
        $type = new XdrLedgerEntryType($value);
        $result = new XdrLedgerKey($type);
        switch ($type->getValue()) {
            case XdrLedgerEntryType::ACCOUNT:
                $result->account = XdrLedgerKeyAccount::decode($xdr);
                break;
            case XdrLedgerEntryType::TRUSTLINE:
                $result->trustLine = XdrLedgerKeyTrustLine::decode($xdr);
                break;
            case XdrLedgerEntryType::OFFER:
                $result->offer = XdrLedgerKeyOffer::decode($xdr);
                break;
            case XdrLedgerEntryType::DATA:
                $result->data = XdrLedgerKeyData::decode($xdr);
                break;
            case XdrLedgerEntryType::CLAIMABLE_BALANCE:
                $result->balanceID = XdrClaimableBalanceID::decode($xdr);
                break;
            case XdrLedgerEntryType::LIQUIDITY_POOL:
                $result->liquidityPoolID = $xdr->readString(64);
                break;
        }
        return $result;
    }
}