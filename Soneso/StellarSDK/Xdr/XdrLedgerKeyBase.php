<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\StrKey;

class XdrLedgerKeyBase
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

    public static function decode(XdrBuffer $xdr) : static {
        $value = $xdr->readInteger32();
        $type = new XdrLedgerEntryType($value);
        $result = new static($type);
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

    public static function fromBase64Xdr(String $base64Xdr) : static {
        $xdr = base64_decode($base64Xdr, true);
        if ($xdr === false) {
            throw new InvalidArgumentException('Invalid base64-encoded XDR');
        }
        $xdrBuffer = new XdrBuffer($xdr);
        return static::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }
}
