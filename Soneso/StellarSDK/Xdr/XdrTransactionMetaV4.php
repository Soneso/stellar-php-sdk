<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTransactionMetaV4
{

    public XdrExtensionPoint $ext;
    /**
     * @var array<XdrLedgerEntryChange> $txChangesBefore tx level changes before operations are applied if any
     */
    public array $txChangesBefore;

    /**
     * @var array<XdrOperationMetaV2> $operations meta for each operation
     */
    public array $operations;

    /**
     * @var array<XdrLedgerEntryChange> $txChangesAfter tx level changes after operations are applied if any
     */
    public array $txChangesAfter;


    /**
     * @var XdrSorobanTransactionMetaV2|null $sorobanMeta Soroban-specific meta (only for Soroban transactions).
     */
    public ?XdrSorobanTransactionMetaV2 $sorobanMeta = null;

    /**
     * @var array<XdrTransactionEvent> $events Used for transaction-level events (like fee payment)
     */
    public array $events;

    /**
     * @var array<XdrDiagnosticEvent> $diagnosticEvents Used for all diagnostic information
     */
    public array $diagnosticEvents;

    /**
     * @param XdrExtensionPoint $ext
     * @param array<XdrLedgerEntryChange> $txChangesBefore
     * @param array<XdrOperationMetaV2> $operations
     * @param array<XdrLedgerEntryChange> $txChangesAfter
     * @param XdrSorobanTransactionMetaV2|null $sorobanMeta
     * @param array<XdrTransactionEvent> $events
     * @param array<XdrDiagnosticEvent> $diagnosticEvents
     */
    public function __construct(XdrExtensionPoint $ext,
                                array $txChangesBefore,
                                array $operations,
                                array $txChangesAfter,
                                ?XdrSorobanTransactionMetaV2 $sorobanMeta,
                                array $events,
                                array $diagnosticEvents)
    {
        $this->ext = $ext;
        $this->txChangesBefore = $txChangesBefore;
        $this->operations = $operations;
        $this->txChangesAfter = $txChangesAfter;
        $this->sorobanMeta = $sorobanMeta;
        $this->events = $events;
        $this->diagnosticEvents = $diagnosticEvents;
    }


    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= XdrEncoder::integer32(count($this->txChangesBefore));
        foreach($this->txChangesBefore as $val) {
            if ($val instanceof XdrLedgerEntryChange) {
                $bytes .= $val->encode();
            }
        }
        $bytes .= XdrEncoder::integer32(count($this->operations));
        foreach($this->operations as $val) {
            if ($val instanceof XdrOperationMetaV2) {
                $bytes .= $val->encode();
            }
        }
        $bytes .= XdrEncoder::integer32(count($this->txChangesAfter));
        foreach($this->txChangesAfter as $val) {
            if ($val instanceof XdrLedgerEntryChange) {
                $bytes .= $val->encode();
            }
        }

        if ($this->sorobanMeta !== null) {
            $bytes .= XdrEncoder::integer32(1);
            $bytes .= $this->sorobanMeta->encode();
        } else {
            $bytes .= XdrEncoder::integer32(0);
        }

        $bytes .= XdrEncoder::integer32(count($this->events));
        foreach($this->events as $val) {
            if ($val instanceof XdrTransactionEvent) {
                $bytes .= $val->encode();
            }
        }

        $bytes .= XdrEncoder::integer32(count($this->diagnosticEvents));
        foreach($this->diagnosticEvents as $val) {
            if ($val instanceof XdrDiagnosticEvent) {
                $bytes .= $val->encode();
            }
        }

        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrTransactionMetaV4 {
        $ext = XdrExtensionPoint::decode($xdr);

        /**
         * @var array<XdrLedgerEntryChange> $txChangesBefore
         */
        $txChangesBefore = array();
        $valCount = $xdr->readInteger32();
        for ($i = 0; $i < $valCount; $i++) {
            $txChangesBefore[] = XdrLedgerEntryChange::decode($xdr);
        }

        /**
         * @var array<XdrOperationMetaV2> $operations
         */
        $operations = array();
        $valCount = $xdr->readInteger32();
        for ($i = 0; $i < $valCount; $i++) {
            $operations[] = XdrOperationMetaV2::decode($xdr);
        }

        /**
         * @var array<XdrLedgerEntryChange> $txChangesAfter
         */
        $txChangesAfter = array();
        $valCount = $xdr->readInteger32();
        for ($i = 0; $i < $valCount; $i++) {
            $txChangesAfter[] = XdrLedgerEntryChange::decode($xdr);
        }

        $sorobanMeta = null;
        if ($xdr->readInteger32() == 1) {
            $sorobanMeta = XdrSorobanTransactionMetaV2::decode($xdr);
        }

        /**
         * @var array<XdrTransactionEvent> $events
         */
        $events = array();
        $valCount = $xdr->readInteger32();
        for ($i = 0; $i < $valCount; $i++) {
            $events[] = XdrTransactionEvent::decode($xdr);
        }

        /**
         * @var array<XdrDiagnosticEvent> $diagnosticEvents
         */
        $diagnosticEvents = array();
        $valCount = $xdr->readInteger32();
        for ($i = 0; $i < $valCount; $i++) {
            $diagnosticEvents[] = XdrDiagnosticEvent::decode($xdr);
        }

        return new XdrTransactionMetaV4($ext,
            $txChangesBefore, $operations, $txChangesAfter, $sorobanMeta, $events, $diagnosticEvents);
    }

    /**
     * @return XdrExtensionPoint
     */
    public function getExt(): XdrExtensionPoint
    {
        return $this->ext;
    }

    /**
     * @param XdrExtensionPoint $ext
     */
    public function setExt(XdrExtensionPoint $ext): void
    {
        $this->ext = $ext;
    }

    /**
     * @return array
     */
    public function getTxChangesBefore(): array
    {
        return $this->txChangesBefore;
    }

    /**
     * @param array $txChangesBefore
     */
    public function setTxChangesBefore(array $txChangesBefore): void
    {
        $this->txChangesBefore = $txChangesBefore;
    }

    /**
     * @return array
     */
    public function getOperations(): array
    {
        return $this->operations;
    }

    /**
     * @param array $operations
     */
    public function setOperations(array $operations): void
    {
        $this->operations = $operations;
    }

    /**
     * @return array
     */
    public function getTxChangesAfter(): array
    {
        return $this->txChangesAfter;
    }

    /**
     * @param array $txChangesAfter
     */
    public function setTxChangesAfter(array $txChangesAfter): void
    {
        $this->txChangesAfter = $txChangesAfter;
    }

    /**
     * @return XdrSorobanTransactionMetaV2|null
     */
    public function getSorobanMeta(): ?XdrSorobanTransactionMetaV2
    {
        return $this->sorobanMeta;
    }

    /**
     * @param XdrSorobanTransactionMetaV2|null $sorobanMeta
     */
    public function setSorobanMeta(?XdrSorobanTransactionMetaV2 $sorobanMeta): void
    {
        $this->sorobanMeta = $sorobanMeta;
    }

    /**
     * @return array
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @param array $events
     */
    public function setEvents(array $events): void
    {
        $this->events = $events;
    }

    /**
     * @return array
     */
    public function getDiagnosticEvents(): array
    {
        return $this->diagnosticEvents;
    }

    /**
     * @param array $diagnosticEvents
     */
    public function setDiagnosticEvents(array $diagnosticEvents): void
    {
        $this->diagnosticEvents = $diagnosticEvents;
    }

}