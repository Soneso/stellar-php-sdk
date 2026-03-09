<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Exception;
use InvalidArgumentException;
use Soneso\StellarSDK\Crypto\StrKey;

class XdrSCAddress extends XdrSCAddressBase
{

    /**
     * Accepts ed25519 "G..." and muxed ("M...") account ids.
     * @param string $accountId "G..." or "M..."
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
        $res->claimableBalanceId = XdrClaimableBalanceID::forClaimableBalanceId($claimableBalanceId);
        return $res;
    }

    public static function forLiquidityPoolId(string $liquidityPoolId) : XdrSCAddress {
        $res = new XdrSCAddress(XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL());
        $res->liquidityPoolId = $liquidityPoolId;
        return $res;
    }

    public function encode(): string {
        switch ($this->type->value) {
            case XdrSCAddressType::SC_ADDRESS_TYPE_CONTRACT:
                $bytes = $this->type->encode();
                $contractIdHex = $this->contractId;
                if (substr($contractIdHex, 0, 1 ) === 'C') {
                    $contractIdHex = StrKey::decodeContractIdHex($contractIdHex);
                }
                $bytes .= XdrEncoder::opaqueFixed(hex2bin($contractIdHex),32);
                return $bytes;
            case XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL:
                $bytes = $this->type->encode();
                $idHex = $this->liquidityPoolId;
                if (str_starts_with($idHex, "L")) {
                    $idHex = StrKey::decodeLiquidityPoolIdHex($idHex);
                }
                $poolIdBytes = pack("H*", $idHex);
                if (strlen($poolIdBytes) > 32) {
                    $poolIdBytes = substr($poolIdBytes, -32);
                }
                $bytes .= XdrEncoder::opaqueFixed($poolIdBytes, 32);
                return $bytes;
            default:
                // ACCOUNT, MUXED_ACCOUNT, CLAIMABLE_BALANCE: base handles correctly
                return parent::encode();
        }
    }

    public static function decode(XdrBuffer $xdr): static {
        $result = new static(XdrSCAddressType::decode($xdr));
        switch ($result->type->getValue()) {
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
                $result->claimableBalanceId = XdrClaimableBalanceID::decode($xdr);
                break;
            case XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL:
                $result->liquidityPoolId = bin2hex($xdr->readOpaqueFixed(32));
                break;
        }
        return $result;
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
                $hash = $this->claimableBalanceId->getHash();
                if (str_starts_with($hash, "B")) {
                    return $hash;
                }
                return StrKey::encodeClaimableBalanceIdHex($hash);
            case XdrSCAddressType::SC_ADDRESS_TYPE_LIQUIDITY_POOL:
                if (str_starts_with($this->liquidityPoolId, "L")) {
                    return $this->liquidityPoolId;
                }
                return StrKey::encodeLiquidityPoolIdHex($this->liquidityPoolId);
        }
        throw new Exception("unknown address type: " . $this->type->value);
    }

}
