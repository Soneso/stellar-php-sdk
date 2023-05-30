<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTransactionResultCode
{
    private int $value;

    /// Fee bump inner transaction succeeded.
    const FEE_BUMP_INNER_SUCCESS = 1;

    /// All operations succeeded.
    const SUCCESS = 0;

    /// One of the operations failed (none were applied).
    const FAILED = -1;

    /// Ledger closeTime before minTime.
    const TOO_EARLY = -2;

    /// Ledger closeTime after maxTime.
    const TOO_LATE = -3;

    /// No operation was specified.
    const MISSING_OPERATION = -4;

    /// Sequence number does not match source account.
    const BAD_SEQ = -5;

    /// Too few valid signatures / wrong network.
    const BAD_AUTH = -6;

    /// Fee would bring account below reserve.
    const INSUFFICIENT_BALANCE = -7;

    /// Source account not found.
    const NO_ACCOUNT = -8;

    /// Fee is too small.
    const INSUFFICIENT_FEE = -9;

    /// Unused signatures attached to transaction.
    const BAD_AUTH_EXTRA  = -10;

    /// An unknown error occurred.
    const INTERNAL_ERROR = -11;

    /// Transaction type not supported.
    const NOT_SUPPORTED = -12;

    /// Fee bump inner transaction failed.
    const FEE_BUMP_INNER_FAILED = -13;

    /// Sponsorship not ended.
    const BAD_SPONSORSHIP = -14;

    /// minSeqAge or minSeqLedgerGap conditions not met
    const BAD_MIN_SEQ_AGE_OR_GAP = -15;

    /// precondition is invalid
    const MALFORMED = -16;

    /// declared Soroban resource usage exceeds the network limit
    const SOROBAN_RESOURCE_LIMIT_EXCEEDED = -17;

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

    public static function decode(XdrBuffer $xdr) : XdrTransactionResultCode {
        $value = $xdr->readInteger32();
        return new XdrTransactionResultCode($value);
    }
}