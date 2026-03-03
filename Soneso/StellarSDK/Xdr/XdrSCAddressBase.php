<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Soneso\StellarSDK\Crypto\StrKey;

class XdrSCAddressBase
{

    public XdrSCAddressType $type;
    public ?XdrAccountID $accountId = null;
    /**
     * @var string|null $contractId hex or strkey representation ('C...')
     */
    public ?string $contractId = null; // hex
    public ?XdrMuxedAccountMed25519 $muxedAccount = null;
    /**
     * @var string|null $claimableBalanceId hex
     */
    public ?string $claimableBalanceId = null;
    /**
     * @var string|null $liquidityPoolId hex
     */
    public ?string $liquidityPoolId = null;

    /**
     * @param XdrSCAddressType $type
     */
    public function __construct(XdrSCAddressType $type)
    {
        $this->type = $type;
    }

    public function encode(): string {
        $bytes = $this->type->encode();

        switch ($this->type->value) {
            case XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT:
                $bytes .= $this->accountId->encode();
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT:
                $contractIdHex = $this->contractId;
                if (substr($contractIdHex, 0, 1 ) === 'C') {
                    $contractIdHex = StrKey::decodeContractIdHex($contractIdHex);
                }
                $bytes .= XdrEncoder::opaqueFixed(hex2bin($contractIdHex),32);
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT:
                $bytes .= $this->muxedAccount->encode();
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE:
                $xdr = XdrClaimableBalanceID::forClaimableBalanceId($this->claimableBalanceId);
                $bytes .= $xdr->encode();
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL:
                $idHex = $this->liquidityPoolId;
                if (str_starts_with($idHex, "L")) {
                    $idHex = StrKey::decodeLiquidityPoolIdHex($idHex);
                }
                $poolIdBytes = pack("H*", $idHex);
                if (strlen($poolIdBytes) > 32) {
                    $poolIdBytes = substr($poolIdBytes, -32);
                }
                $bytes .= XdrEncoder::opaqueFixed($poolIdBytes, 32);
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): static {
        $result = new static(XdrSCAddressType::decode($xdr));
        switch ($result->type->value) {
            case XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT:
                $result->accountId = XdrAccountID::decode($xdr);
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT:
                $result->muxedAccount = XdrMuxedAccountMed25519::decode($xdr);
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT:
                $result->contractId = bin2hex($xdr->readOpaqueFixed(32));
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE:
                $xdrCID = XdrClaimableBalanceID::decode($xdr);
                $result->claimableBalanceId = $xdrCID->getHash();
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL:
                $result->liquidityPoolId = bin2hex($xdr->readOpaqueFixed(32));
                break;
        }
        return $result;
    }

    /**
     * @return XdrSCAddressType
     */
    public function getType(): XdrSCAddressType
    {
        return $this->type;
    }

    /**
     * @param XdrSCAddressType $type
     */
    public function setType(XdrSCAddressType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrAccountID|null
     */
    public function getAccountId(): ?XdrAccountID
    {
        return $this->accountId;
    }

    /**
     * @param XdrAccountID|null $accountId
     */
    public function setAccountId(?XdrAccountID $accountId): void
    {
        $this->accountId = $accountId;
    }

    /**
     * @return string|null
     */
    public function getContractId(): ?string
    {
        return $this->contractId;
    }

    /**
     * @param string|null $contractId
     */
    public function setContractId(?string $contractId): void
    {
        $this->contractId = $contractId;
    }

    /**
     * @return XdrMuxedAccountMed25519|null
     */
    public function getMuxedAccount(): ?XdrMuxedAccountMed25519
    {
        return $this->muxedAccount;
    }

    /**
     * @param XdrMuxedAccountMed25519|null $muxedAccount
     */
    public function setMuxedAccount(?XdrMuxedAccountMed25519 $muxedAccount): void
    {
        $this->muxedAccount = $muxedAccount;
    }

    /**
     * @return string|null
     */
    public function getClaimableBalanceId(): ?string
    {
        return $this->claimableBalanceId;
    }

    /**
     * @param string|null $claimableBalanceId
     */
    public function setClaimableBalanceId(?string $claimableBalanceId): void
    {
        $this->claimableBalanceId = $claimableBalanceId;
    }

    /**
     * @return string|null
     */
    public function getLiquidityPoolId(): ?string
    {
        return $this->liquidityPoolId;
    }

    /**
     * @param string|null $liquidityPoolId
     */
    public function setLiquidityPoolId(?string $liquidityPoolId): void
    {
        $this->liquidityPoolId = $liquidityPoolId;
    }

}
