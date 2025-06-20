<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

/**
 * Represents a transaction-level event in metadata.
 * Currently, this is limited to the fee events (when fee is charged or refunded).
 */
class XdrTransactionEvent
{
    /**
     * @var XdrTransactionEventStage $stage Stage at which an event has occurred.
     */
    public XdrTransactionEventStage $stage;

    /**
     * @var XdrContractEvent $event The contract event that has occurred.
     */
    public XdrContractEvent $event;

    /**
     * @param XdrTransactionEventStage $stage
     * @param XdrContractEvent $event
     */
    public function __construct(XdrTransactionEventStage $stage, XdrContractEvent $event)
    {
        $this->stage = $stage;
        $this->event = $event;
    }


    public function encode(): string {
        $bytes = $this->stage->encode();
        $bytes .= $this->event->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrTransactionEvent {
        $stage = XdrTransactionEventStage::decode($xdr);
        $event = XdrContractEvent::decode($xdr);
        return new XdrTransactionEvent($stage, $event);
    }

    /**
     * @return XdrTransactionEventStage
     */
    public function getStage(): XdrTransactionEventStage
    {
        return $this->stage;
    }

    /**
     * @param XdrTransactionEventStage $stage
     */
    public function setStage(XdrTransactionEventStage $stage): void
    {
        $this->stage = $stage;
    }

    /**
     * @return XdrContractEvent
     */
    public function getEvent(): XdrContractEvent
    {
        return $this->event;
    }

    /**
     * @param XdrContractEvent $event
     */
    public function setEvent(XdrContractEvent $event): void
    {
        $this->event = $event;
    }
}