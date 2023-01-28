<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrLedgerEntryData
{

    public XdrLedgerEntryType $type;
    public ?XdrAccountEntry $account = null;
    public ?XdrTrustLineEntry $trustline = null;
    public ?XdrOfferEntry $offer = null;
    public ?XdrDataEntry $data = null;
    public ?XdrClaimableBalanceEntry $claimableBalance = null;
    public ?XdrLiquidityPoolEntry $liquidityPool = null;
    public ?XdrContractDataEntry $contractData = null;
    public ?XdrContractCodeEntry $contractCode = null;
    public ?XdrConfigSettingEntry $configSetting = null;

    /**
     * @param XdrLedgerEntryType $type
     */
    public function __construct(XdrLedgerEntryType $type)
    {
        $this->type = $type;
    }

    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrLedgerEntryType::ACCOUNT:
                $bytes .= $this->account->encode();
                break;
            case XdrLedgerEntryType::TRUSTLINE:
                $bytes .= $this->trustline->encode();
                break;
            case XdrLedgerEntryType::OFFER:
                $bytes .= $this->offer->encode();
                break;
            case XdrLedgerEntryType::DATA:
                $bytes .= $this->data->encode();
                break;
            case XdrLedgerEntryType::CLAIMABLE_BALANCE:
                $bytes .= $this->claimableBalance->encode();
                break;
            case XdrLedgerEntryType::LIQUIDITY_POOL:
                $bytes .= $this->liquidityPool->encode();
                break;
            case XdrLedgerEntryType::CONTRACT_DATA:
                $bytes .= $this->contractData->encode();
                break;
            case XdrLedgerEntryType::CONTRACT_CODE:
                $bytes .= $this->contractCode->encode();
                break;
            case XdrLedgerEntryType::CONFIG_SETTING:
                $bytes .= $this->configSetting->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrLedgerEntryData {
        $result = new XdrLedgerEntryData(XdrLedgerEntryType::decode($xdr));
        switch ($result->type->value) {
            case XdrLedgerEntryType::ACCOUNT:
                $result->account = XdrAccountEntry::decode($xdr);
                break;
            case XdrLedgerEntryType::TRUSTLINE:
                $result->trustline = XdrTrustLineEntry::decode($xdr);
                break;
            case XdrLedgerEntryType::OFFER:
                $result->offer = XdrOfferEntry::decode($xdr);
                break;
            case XdrLedgerEntryType::DATA:
                $result->data = XdrDataEntry::decode($xdr);
                break;
            case XdrLedgerEntryType::CLAIMABLE_BALANCE:
                $result->claimableBalance = XdrClaimableBalanceEntry::decode($xdr);
                break;
            case XdrLedgerEntryType::LIQUIDITY_POOL:
                $result->liquidityPool = XdrLiquidityPoolEntry::decode($xdr);
                break;
            case XdrLedgerEntryType::CONTRACT_DATA:
                $result->contractData = XdrContractDataEntry::decode($xdr);
                break;
            case XdrLedgerEntryType::CONTRACT_CODE:
                $result->contractCode = XdrContractCodeEntry::decode($xdr);
                break;
            case XdrLedgerEntryType::CONFIG_SETTING:
                $result->configSetting = XdrConfigSettingEntry::decode($xdr);
                break;
        }
        return $result;
    }

    public static function fromBase64Xdr(String $base64Xdr) : XdrLedgerEntryData {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrLedgerEntryData::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
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
     * @return XdrAccountEntry|null
     */
    public function getAccount(): ?XdrAccountEntry
    {
        return $this->account;
    }

    /**
     * @param XdrAccountEntry|null $account
     */
    public function setAccount(?XdrAccountEntry $account): void
    {
        $this->account = $account;
    }

    /**
     * @return XdrTrustLineEntry|null
     */
    public function getTrustline(): ?XdrTrustLineEntry
    {
        return $this->trustline;
    }

    /**
     * @param XdrTrustLineEntry|null $trustline
     */
    public function setTrustline(?XdrTrustLineEntry $trustline): void
    {
        $this->trustline = $trustline;
    }

    /**
     * @return XdrOfferEntry|null
     */
    public function getOffer(): ?XdrOfferEntry
    {
        return $this->offer;
    }

    /**
     * @param XdrOfferEntry|null $offer
     */
    public function setOffer(?XdrOfferEntry $offer): void
    {
        $this->offer = $offer;
    }

    /**
     * @return XdrDataEntry|null
     */
    public function getData(): ?XdrDataEntry
    {
        return $this->data;
    }

    /**
     * @param XdrDataEntry|null $data
     */
    public function setData(?XdrDataEntry $data): void
    {
        $this->data = $data;
    }

    /**
     * @return XdrClaimableBalanceEntry|null
     */
    public function getClaimableBalance(): ?XdrClaimableBalanceEntry
    {
        return $this->claimableBalance;
    }

    /**
     * @param XdrClaimableBalanceEntry|null $claimableBalance
     */
    public function setClaimableBalance(?XdrClaimableBalanceEntry $claimableBalance): void
    {
        $this->claimableBalance = $claimableBalance;
    }

    /**
     * @return XdrLiquidityPoolEntry|null
     */
    public function getLiquidityPool(): ?XdrLiquidityPoolEntry
    {
        return $this->liquidityPool;
    }

    /**
     * @param XdrLiquidityPoolEntry|null $liquidityPool
     */
    public function setLiquidityPool(?XdrLiquidityPoolEntry $liquidityPool): void
    {
        $this->liquidityPool = $liquidityPool;
    }

    /**
     * @return XdrContractDataEntry|null
     */
    public function getContractData(): ?XdrContractDataEntry
    {
        return $this->contractData;
    }

    /**
     * @param XdrContractDataEntry|null $contractData
     */
    public function setContractData(?XdrContractDataEntry $contractData): void
    {
        $this->contractData = $contractData;
    }

    /**
     * @return XdrContractCodeEntry|null
     */
    public function getContractCode(): ?XdrContractCodeEntry
    {
        return $this->contractCode;
    }

    /**
     * @param XdrContractCodeEntry|null $contractCode
     */
    public function setContractCode(?XdrContractCodeEntry $contractCode): void
    {
        $this->contractCode = $contractCode;
    }

    /**
     * @return XdrConfigSettingEntry|null
     */
    public function getConfigSetting(): ?XdrConfigSettingEntry
    {
        return $this->configSetting;
    }

    /**
     * @param XdrConfigSettingEntry|null $configSetting
     */
    public function setConfigSetting(?XdrConfigSettingEntry $configSetting): void
    {
        $this->configSetting = $configSetting;
    }
}