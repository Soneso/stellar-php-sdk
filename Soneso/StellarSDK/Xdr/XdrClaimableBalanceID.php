<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

use Soneso\StellarSDK\Crypto\StrKey;

class XdrClaimableBalanceID extends XdrClaimableBalanceIDBase
{
    public function __construct(XdrClaimableBalanceIDType $type, string $hash) {
        parent::__construct($type);
        $this->hash = $hash;
    }

    public function encode(): string {
        $bytes = $this->type->encode();
        switch ($this->type->getValue()) {
            case XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0:
                $idHex = $this->hash;
                if (str_starts_with($idHex, "B")) {
                    $idHex = StrKey::decodeClaimableBalanceIdHex($idHex);
                }
                $balanceIdBytes = pack("H*", $idHex);
                if (strlen($balanceIdBytes) > 32) {
                    $balanceIdBytes = substr($balanceIdBytes, -32);
                }
                $bytes .= XdrEncoder::opaqueFixed($balanceIdBytes, 32);
                break;
            default:
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr): static {
        $type = XdrClaimableBalanceIDType::decode($xdr);
        $hash = '';
        switch ($type->getValue()) {
            case XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0:
                $hash = bin2hex($xdr->readOpaqueFixed(32));
                break;
            default:
                break;
        }
        return new static($type, $hash);
    }

    /**
     * Returns the balance id as hex string with leading zeros, so it can be used in horizon requests.
     * e.g. '000000003be9c4382b2e4acc74600f6eb1b68e51de5e5cc22ee2adcf68bd7fdfa1f40cf9'
     * instead of '3be9c4382b2e4acc74600f6eb1b68e51de5e5cc22ee2adcf68bd7fdfa1f40cf9'
     * @return string balance id as hex string with leading zeros, so it can be used in horizon requests.
     */
    public function getPaddedBalanceIdHex() {
        return str_pad($this->getHash(), 72, '0', STR_PAD_LEFT);
    }

    public static function forClaimableBalanceId(string $claimableBalanceId) : XdrClaimableBalanceID {
        return new XdrClaimableBalanceID(
            XdrClaimableBalanceIDType::CLAIMABLE_BALANCE_ID_TYPE_V0(),
            $claimableBalanceId,
        );
    }
}
