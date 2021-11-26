<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrManageOfferResultCode
{
    private int $value;

    /// Success.
    const SUCCESS = 0;

    /// Generated offer would be invalid.
    const MALFORMED = -1;

    /// No trust line for what we're selling.
    const SELL_NO_TRUST = -2;

    /// No trust line for what we're buying.
    const BUY_NO_TRUST = -3;

    /// Not authorized to sell.
    const SELL_NOT_AUTHORIZED = -4;

    /// Not authorized to buy.
    const BUY_NOT_AUTHORIZED = -5;

    /// Can't receive more of what it's buying.
    const LINE_FULL = -6;

    /// Doesn't hold what it's trying to sell.
    const UNDERFUNDED = -7;

    /// Would cross an offer from the same user.
    const CROSS_SELF = -8;

    /// No issuer for what we're selling.
    const SELL_NO_ISSUER  = -9;

    /// No issuer for what we're buying.
    const BUY_NO_ISSUER  = -10;

    /// OfferID does not match an existing offer.
    const NOT_FOUND = -11;

    /// Not enough funds to create a new Offer.
    const LOW_RESERVE = -12;

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

    public static function decode(XdrBuffer $xdr) : XdrManageOfferResultCode {
        $value = $xdr->readInteger32();
        return new XdrManageOfferResultCode($value);
    }
}