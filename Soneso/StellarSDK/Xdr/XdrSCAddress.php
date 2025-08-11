<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Exception;
use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\StrKey;

class XdrSCAddress
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

    public static function decode(XdrBuffer $xdr):  XdrSCAddress {
        $result = new XdrSCAddress(XdrSCAddressType::decode($xdr));
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
     * Accepts ed25519 "G..." and muxed ("M...") account ids.
     * @param string $accountId "C..." or "M..."
     * @return XdrSCAddress
     */
    public static function forAccountId(string $accountId) : XdrSCAddress {
        if (str_starts_with($accountId, "G")) {
            $res = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT());
            $res->accountId = XdrAccountID::fromAccountId($accountId);
            return $res;
        } else if (str_starts_with($accountId, "M")) {
            $res = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT());
            $bytes = StrKey::decodeMuxedAccountId($accountId);
            $xdrBuffer = new XdrBuffer($bytes);
            $res->muxedAccount = XdrMuxedAccountMed25519::decodeInverted($xdrBuffer);
            return $res;
        } else {
            throw new InvalidArgumentException("invalid account id: " . $accountId);
        }
    }

    /**
     * Accepts hex or strkey values ("C...")
     * @param string $contractId hex or "C..."
     * @return XdrSCAddress
     */
    public static function forContractId(string $contractId) : XdrSCAddress {
        $res = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT());
        $res->contractId = $contractId;
        return $res;
    }

    /**
     * Accepts hex values
     * @param string $claimableBalanceId hex string
     * @return XdrSCAddress
     */
    public static function forClaimableBalanceId(string $claimableBalanceId) : XdrSCAddress {
        $res = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE());
        $res->claimableBalanceId = $claimableBalanceId;
        return $res;
    }

    public static function forLiquidityPoolId(string $liquidityPoolId) : XdrSCAddress {
        $res = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL());
        $res->liquidityPoolId = $liquidityPoolId;
        return $res;
    }

    /**
     * Returns the StrKey representation of the address.
     * @throws Exception
     */
    public function toStrKey() : string {
        switch ($this->type->value) {
            case XdrSCAddressType::SC_ADDRESS_TYPE_ACCOUNT:
                return $this->accountId->getAccountId();
            case XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT:
                if (str_starts_with($this->contractId, "C")) {
                    return $this->contractId;
                }
                return StrKey::encodeContractIdHex($this->contractId);
            case XdrSCAddressType::SC_ADDRESS_TYPE_MUXED_ACCOUNT:
                return $this->muxedAccount->getAccountId();
            case XdrSCAddressType::SC_ADDRESS_TYPE_CLAIMABLE_BALANCE:
                if (str_starts_with($this->claimableBalanceId, "B")) {
                    return $this->claimableBalanceId;
                }
                return StrKey::encodeClaimableBalanceIdHex($this->claimableBalanceId);
            case XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL:
                if (str_starts_with($this->liquidityPoolId, "L")) {
                    return $this->liquidityPoolId;
                }
                return StrKey::encodeLiquidityPoolIdHex($this->liquidityPoolId);
        }
        throw new Exception("unknown address type: " . $this->type->value);
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