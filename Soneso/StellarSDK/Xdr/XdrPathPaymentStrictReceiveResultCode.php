<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrPathPaymentStrictReceiveResultCode
{
    private int $value;

    /// Success.
    const SUCCESS = 0;

    /// Bad input.
    const MALFORMED = -1;

    /// Not enough funds in source account.
    const UNDERFUNDED = -2;

    /// No trust line on source account.
    const SRC_NO_TRUST = -3;

    /// Source not authorized to transfer.
    const SRC_NOT_AUTHORIZED = -4;

    /// Destination account does not exist.
    const NO_DESTINATION  = -5;

    /// Dest missing a trust line for asset.
    const NO_TRUST = -6;

    /// Dest not authorized to hold asset.
    const NOT_AUTHORIZED = -7;

    /// Dest would go above their limit.
    const LINE_FULL = -8;

    /// Missing issuer on one asset.
    const NO_ISSUER  = -9;

    /// Not enough offers to satisfy path.
    const TOO_FEW_OFFERS  = -10;

    /// Would cross one of its own offers.
    const OFFER_CROSS_SELF = -11;

    /// Could not satisfy sendmax.
    const OVER_SENDMAX = -12;

    public function __construct(int $value) {
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    public function encode(): string {
        return XdrEncoder::integer32($this->value);
    }

    public static function decode(XdrBuffer $xdr) : XdrPathPaymentStrictReceiveResultCode {
        $value = $xdr->readInteger32();
        return new XdrPathPaymentStrictReceiveResultCode($value);
    }
}