<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

class XdrAccountMergeResultCode
{

    private int $value;

    /// Success.
    const SUCCESS = 0;

    /// Can't merge onto itself.
    const MALFORMED  = -1;

    /// Destination does not exist.
    const NO_ACCOUNT = -2;

    /// Source account has AUTH_IMMUTABLE set.
    const IMMUTABLE_SET = -3;

    /// Account has trust lines/offers.
    const HAS_SUB_ENTRIES = -4;

    /// Sequence number is over max allowed.
    const SEQNUM_TOO_FAR = -5;

    /// Can't add source balance to destination balance.
    const DEST_FULL = -6;

    /// /// Can't merge account that is a sponsor.
    const IS_SPONSOR  = -7;


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

    public static function decode(XdrBuffer $xdr) : XdrAccountMergeResultCode {
        $value = $xdr->readInteger32();
        return new XdrAccountMergeResultCode($value);
    }

}