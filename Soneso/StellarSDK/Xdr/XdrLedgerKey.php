<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Soneso\StellarSDK\Crypto\StrKey;

class XdrLedgerKey extends XdrLedgerKeyBase
{
    // Backward-compatible field: hex-encoded pool ID (base uses XdrLedgerKeyLiquidityPool with raw binary)
    public ?string $liquidityPoolID = null;

    public function encode(): string {
        // Sync hex liquidityPoolID → base's binary liquidityPool struct before encoding
        if ($this->liquidityPoolID !== null && $this->liquidityPool === null) {
            $idHex = $this->liquidityPoolID;
            if (str_starts_with($idHex, "L")) {
                $idHex = StrKey::decodeLiquidityPoolIdHex($idHex);
            }
            $this->liquidityPool = new XdrLedgerKeyLiquidityPool(hex2bin($idHex));
        }
        return parent::encode();
    }

    public static function decode(XdrBuffer $xdr): static {
        $result = parent::decode($xdr);
        // Sync base's binary liquidityPool → hex liquidityPoolID after decoding
        if ($result->liquidityPool !== null) {
            $result->liquidityPoolID = bin2hex($result->liquidityPool->liquidityPoolID);
        }
        return $result;
    }

    // Backward-compatible getters/setters for liquidityPoolID
    public function getLiquidityPoolID(): ?string { return $this->liquidityPoolID; }
    public function setLiquidityPoolID(?string $liquidityPoolID): void { $this->liquidityPoolID = $liquidityPoolID; }

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

    /**
     * Override toTxRep so the claimable-balance case uses the SEP-0011 key path
     * `...claimableBalance.balanceID.*` instead of the generated `...claimableBalance.*`.
     *
     * @param string               $prefix
     * @param array<string,string> $lines
     */
    public function toTxRep(string $prefix, array &$lines): void {
        $this->type->toTxRep($prefix . '.type', $lines);
        switch ($this->type->getValue()) {
            case XdrLedgerEntryType::ACCOUNT:
                $this->account->toTxRep($prefix . '.account', $lines);
                break;
            case XdrLedgerEntryType::TRUSTLINE:
                $this->trustLine->toTxRep($prefix . '.trustLine', $lines);
                break;
            case XdrLedgerEntryType::OFFER:
                $this->offer->toTxRep($prefix . '.offer', $lines);
                break;
            case XdrLedgerEntryType::DATA:
                $this->data->toTxRep($prefix . '.data', $lines);
                break;
            case XdrLedgerEntryType::CLAIMABLE_BALANCE:
                // SEP-0011 uses .claimableBalance.balanceID.* for the balance ID union
                $this->balanceID->toTxRep($prefix . '.claimableBalance.balanceID', $lines);
                break;
            case XdrLedgerEntryType::LIQUIDITY_POOL:
                $this->liquidityPool->toTxRep($prefix . '.liquidityPool', $lines);
                break;
            case XdrLedgerEntryType::CONTRACT_DATA:
                $this->contractData->toTxRep($prefix . '.contractData', $lines);
                break;
            case XdrLedgerEntryType::CONTRACT_CODE:
                $this->contractCode->toTxRep($prefix . '.contractCode', $lines);
                break;
            case XdrLedgerEntryType::CONFIG_SETTING:
                $this->configSetting->toTxRep($prefix . '.configSetting', $lines);
                break;
            case XdrLedgerEntryType::TTL:
                $this->ttl->toTxRep($prefix . '.ttl', $lines);
                break;
            default:
                break;
        }
    }

    /**
     * Override fromTxRep to match the SEP-0011 key path for claimable balance IDs
     * (`...claimableBalance.balanceID.*` instead of `...claimableBalance.*`).
     *
     * @param array<string,string> $map
     * @param string               $prefix
     * @return static
     */
    public static function fromTxRep(array $map, string $prefix): static {
        $disc = XdrLedgerEntryType::fromTxRep($map, $prefix . '.type');
        $result = new static($disc);
        switch ($result->type->getValue()) {
            case XdrLedgerEntryType::ACCOUNT:
                $result->account = XdrLedgerKeyAccount::fromTxRep($map, $prefix . '.account');
                break;
            case XdrLedgerEntryType::TRUSTLINE:
                $result->trustLine = XdrLedgerKeyTrustLine::fromTxRep($map, $prefix . '.trustLine');
                break;
            case XdrLedgerEntryType::OFFER:
                $result->offer = XdrLedgerKeyOffer::fromTxRep($map, $prefix . '.offer');
                break;
            case XdrLedgerEntryType::DATA:
                $result->data = XdrLedgerKeyData::fromTxRep($map, $prefix . '.data');
                break;
            case XdrLedgerEntryType::CLAIMABLE_BALANCE:
                // SEP-0011 uses .claimableBalance.balanceID.* for the balance ID union
                $result->balanceID = XdrClaimableBalanceID::fromTxRep($map, $prefix . '.claimableBalance.balanceID');
                break;
            case XdrLedgerEntryType::LIQUIDITY_POOL:
                $result->liquidityPool = XdrLedgerKeyLiquidityPool::fromTxRep($map, $prefix . '.liquidityPool');
                break;
            case XdrLedgerEntryType::CONTRACT_DATA:
                $result->contractData = XdrLedgerKeyContractData::fromTxRep($map, $prefix . '.contractData');
                break;
            case XdrLedgerEntryType::CONTRACT_CODE:
                $result->contractCode = XdrLedgerKeyContractCode::fromTxRep($map, $prefix . '.contractCode');
                break;
            case XdrLedgerEntryType::CONFIG_SETTING:
                $result->configSetting = XdrConfigSettingID::fromTxRep($map, $prefix . '.configSetting');
                break;
            case XdrLedgerEntryType::TTL:
                $result->ttl = XdrLedgerKeyTTL::fromTxRep($map, $prefix . '.ttl');
                break;
            default:
                break;
        }
        return $result;
    }
}
