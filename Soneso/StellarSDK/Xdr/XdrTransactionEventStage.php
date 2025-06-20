<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace Soneso\StellarSDK\Xdr;

// Transaction-level events happen at different stages of the ledger apply flow
// (as opposed to the operation events that all happen atomically after
// a transaction is applied).
// This enum represents the possible stages during which an event has been
// emitted.
class XdrTransactionEventStage
{
    public int $value;

    // The event has happened before any one of the transactions has its operations applied.
    const TRANSACTION_EVENT_STAGE_BEFORE_ALL_TXS = 0;

    // The event has happened immediately after operations of the transaction have been applied.
    const TRANSACTION_EVENT_STAGE_AFTER_TX = 1;

    // The event has happened after every transaction had its operations applied.
    const TRANSACTION_EVENT_STAGE_AFTER_ALL_TXS = 2;

    public function __construct(int $value) {
        $this->value = $value;
    }

    public function encode(): string {
        return XdrEncoder::integer32($this->value);
    }

    public static function decode(XdrBuffer $xdr) : XdrTransactionEventStage {
        $value = $xdr->readInteger32();
        return new XdrTransactionEventStage($value);
    }

    public static function TRANSACTION_EVENT_STAGE_BEFORE_ALL_TXS() :  XdrTransactionEventStage {
        return new XdrTransactionEventStage(XdrTransactionEventStage::TRANSACTION_EVENT_STAGE_BEFORE_ALL_TXS);
    }

    public static function TRANSACTION_EVENT_STAGE_AFTER_TX() :  XdrTransactionEventStage {
        return new XdrTransactionEventStage(XdrTransactionEventStage::TRANSACTION_EVENT_STAGE_AFTER_TX);
    }

    public static function TRANSACTION_EVENT_STAGE_AFTER_ALL_TXS() :  XdrTransactionEventStage {
        return new XdrTransactionEventStage(XdrTransactionEventStage::TRANSACTION_EVENT_STAGE_AFTER_ALL_TXS);
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @param int $value
     */
    public function setValue(int $value): void
    {
        $this->value = $value;
    }
}