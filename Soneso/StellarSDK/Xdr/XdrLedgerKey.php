<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Soneso\StellarSDK\Crypto\StrKey;

class XdrLedgerKey
{
    public XdrLedgerEntryType $type;
    public ?XdrLedgerKeyAccount $account = null;
    public ?XdrLedgerKeyTrustLine $trustLine = null;
    public ?XdrLedgerKeyOffer $offer = null;
    public ?XdrLedgerKeyData $data = null;
    public ?XdrClaimableBalanceID $balanceID = null;
    public ?string $liquidityPoolID = null;
    public ?XdrLedgerKeyContractData $contractData = null;
    public ?XdrLedgerKeyContractCode $contractCode = null;
    public ?XdrConfigSettingID $configSetting = null;
    public ?XdrLedgerKeyTTL $ttl = null;

    public function __construct(XdrLedgerEntryType $type) {
        $this->type = $type;
    }

    public function encode(): string {
        $bytes = $this->type->encode();
        $bytes .= match ($this->type->getValue()) {
            XdrLedgerEntryType::ACCOUNT => $this->account->encode(),
            XdrLedgerEntryType::TRUSTLINE => $this->trustLine->encode(),
            XdrLedgerEntryType::OFFER => $this->offer->encode(),
            XdrLedgerEntryType::DATA => $this->data->encode(),
            XdrLedgerEntryType::CLAIMABLE_BALANCE => $this->balanceID->encode(),
            XdrLedgerEntryType::LIQUIDITY_POOL => XdrEncoder::opaqueFixed($this->getLiquidityPoolIdBin(), 32),
            XdrLedgerEntryType::CONTRACT_DATA => $this->contractData->encode(),
            XdrLedgerEntryType::CONTRACT_CODE => $this->contractCode->encode(),
            XdrLedgerEntryType::CONFIG_SETTING => $this->configSetting->encode(),
            XdrLedgerEntryType::TTL => $this->ttl->encode(),
        };
        return $bytes;
    }

    private function getLiquidityPoolIdBin() : ?string {
        if ($this->liquidityPoolID === null) {
            return null;
        }
        $idHex = $this->liquidityPoolID;
        if (str_starts_with($idHex, "L")) {
            $idHex = StrKey::decodeLiquidityPoolIdHex($idHex);
        }
        return hex2bin($idHex);
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
                $result->liquidityPoolID = bin2hex($xdr->readOpaqueFixed(32));
                break;
            case XdrLedgerEntryType::CONTRACT_DATA:
                $result->contractData = XdrLedgerKeyContractData::decode($xdr);
                break;
            case XdrLedgerEntryType::CONTRACT_CODE:
                $result->contractCode = XdrLedgerKeyContractCode::decode($xdr);
                break;
            case XdrLedgerEntryType::CONFIG_SETTING:
                $result->configSetting = XdrConfigSettingID::decode($xdr);
                break;
            case XdrLedgerEntryType::TTL:
                $result->ttl = XdrLedgerKeyTTL::decode($xdr);
                break;
        }
        return $result;
    }

    public static function forAccountId(string $accountId) : XdrLedgerKey {
        $result = new XdrLedgerKey(XdrLedgerEntryType::ACCOUNT());
        $result->account = XdrLedgerKeyAccount::forAccountId($accountId);
        return $result;
    }

    public static function forTrustLine(string $accountId, XdrAsset $asset) : XdrLedgerKey {
        $result = new XdrLedgerKey(XdrLedgerEntryType::TRUSTLINE());
        $result->trustLine = new XdrLedgerKeyTrustLine(
            XdrAccountID::fromAccountId($accountId),
            XdrTrustlineAsset::fromXdrAsset($asset),
        );
        return $result;
    }

    public static function forOffer(string $sellerId, int $offerId) : XdrLedgerKey {
        $result = new XdrLedgerKey(XdrLedgerEntryType::OFFER());
        $result->offer = new XdrLedgerKeyOffer(XdrAccountID::fromAccountId($sellerId), $offerId);
        return $result;
    }

    public static function forData(string $accountId, string $dataName) : XdrLedgerKey {
        $result = new XdrLedgerKey(XdrLedgerEntryType::DATA());
        $result->data = new XdrLedgerKeyData(XdrAccountID::fromAccountId($accountId), $dataName);
        return $result;
    }

    public static function forClaimableBalanceId(string $claimableBalanceId) : XdrLedgerKey {
        $result = new XdrLedgerKey(XdrLedgerEntryType::CLAIMABLE_BALANCE());
        $result->balanceID = XdrClaimableBalanceID::forClaimableBalanceId($claimableBalanceId);
        return $result;
    }

    public static function forLiquidityPoolId(string $liquidityPoolId) : XdrLedgerKey {
        $result = new XdrLedgerKey(XdrLedgerEntryType::LIQUIDITY_POOL());
        $result->liquidityPoolID = $liquidityPoolId;
        return $result;
    }

    public static function forContractData(XdrSCAddress $contract, XdrSCVal $key, XdrContractDataDurability $durability) : XdrLedgerKey {
        $result = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_DATA());
        $result->contractData = new XdrLedgerKeyContractData($contract, $key, $durability);
        return $result;
    }

    public static function forContractCode(string $code) : XdrLedgerKey {
        $result = new XdrLedgerKey(XdrLedgerEntryType::CONTRACT_CODE());
        $result->contractCode = new XdrLedgerKeyContractCode($code);
        return $result;
    }

    public static function forConfigSettingID(XdrConfigSettingID $id) : XdrLedgerKey {
        $result = new XdrLedgerKey(XdrLedgerEntryType::CONFIG_SETTING());
        $result->configSetting = $id;
        return $result;
    }

    public static function forTTL(string $keyHash) : XdrLedgerKey {
        $result = new XdrLedgerKey(XdrLedgerEntryType::EXPIRATION());
        $result->ttl = new XdrLedgerKeyTTL($keyHash);
        return $result;
    }

    public static function fromBase64Xdr(String $base64Xdr) : XdrLedgerKey {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrLedgerKey::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }

    /**
     * @return XdrLedgerKeyTTL|null
     */
    public function getTtl(): ?XdrLedgerKeyTTL
    {
        return $this->ttl;
    }

    /**
     * @param XdrLedgerKeyTTL|null $ttl
     */
    public function setTtl(?XdrLedgerKeyTTL $ttl): void
    {
        $this->ttl = $ttl;
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

    /**
     * @return XdrLedgerKeyContractData|null
     */
    public function getContractData(): ?XdrLedgerKeyContractData
    {
        return $this->contractData;
    }

    /**
     * @param XdrLedgerKeyContractData|null $contractData
     */
    public function setContractData(?XdrLedgerKeyContractData $contractData): void
    {
        $this->contractData = $contractData;
    }

    /**
     * @return XdrLedgerKeyContractCode|null
     */
    public function getContractCode(): ?XdrLedgerKeyContractCode
    {
        return $this->contractCode;
    }

    /**
     * @param XdrLedgerKeyContractCode|null $contractCode
     */
    public function setContractCode(?XdrLedgerKeyContractCode $contractCode): void
    {
        $this->contractCode = $contractCode;
    }

    /**
     * @return XdrConfigSettingID|null
     */
    public function getConfigSetting(): ?XdrConfigSettingID
    {
        return $this->configSetting;
    }

    /**
     * @param XdrConfigSettingID|null $configSetting
     */
    public function setConfigSetting(?XdrConfigSettingID $configSetting): void
    {
        $this->configSetting = $configSetting;
    }
}