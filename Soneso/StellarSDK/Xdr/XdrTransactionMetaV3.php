<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTransactionMetaV3
{

    public array $txChangesBefore; // [XdrLedgerEntryChange]
    public array $operations; // [XdrOperationMeta]
    public array $txChangesAfter; // [XdrLedgerEntryChange]
    public array $events; // [XdrOperationEvents]
    public XdrTransactionResult $txResult;
    public array $hashes; // [bytes string]
    public array $diagnosticEvents; // [XdrOperationDiagnosticEvents]

    /**
     * @param array $txChangesBefore
     * @param array $operations
     * @param array $txChangesAfter
     * @param array $events
     * @param XdrTransactionResult $txResult
     * @param array $hashes
     * @param array $diagnosticEvents
     */
    public function __construct(array $txChangesBefore, array $operations, array $txChangesAfter, array $events, XdrTransactionResult $txResult, array $hashes, array $diagnosticEvents)
    {
        $this->txChangesBefore = $txChangesBefore;
        $this->operations = $operations;
        $this->txChangesAfter = $txChangesAfter;
        $this->events = $events;
        $this->txResult = $txResult;
        $this->hashes = $hashes;
        $this->diagnosticEvents = $diagnosticEvents;
    }


    public function encode(): string {
        $bytes = XdrEncoder::integer32(count($this->txChangesBefore));
        foreach($this->txChangesBefore as $val) {
            if ($val instanceof XdrLedgerEntryChange) {
                $bytes .= $val->encode();
            }
        }
        $bytes .= XdrEncoder::integer32(count($this->operations));
        foreach($this->operations as $val) {
            if ($val instanceof XdrOperationMeta) {
                $bytes .= $val->encode();
            }
        }
        $bytes .= XdrEncoder::integer32(count($this->txChangesAfter));
        foreach($this->txChangesAfter as $val) {
            if ($val instanceof XdrLedgerEntryChange) {
                $bytes .= $val->encode();
            }
        }
        $bytes .= XdrEncoder::integer32(count($this->events));
        foreach($this->events as $val) {
            if ($val instanceof XdrOperationEvents) {
                $bytes .= $val->encode();
            }
        }

        $bytes .= $this->txResult->encode();

        foreach($this->hashes as $val) {
            $bytes .= XdrEncoder::opaqueFixed($val, 32);
        }

        $bytes .= XdrEncoder::integer32(count($this->diagnosticEvents));
        foreach($this->diagnosticEvents as $val) {
            if ($val instanceof XdrOperationDiagnosticEvents) {
                $bytes .= $val->encode();
            }
        }

        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrTransactionMetaV3 {
        $valCount = $xdr->readInteger32();
        $txChangesBefore = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($txChangesBefore, XdrLedgerEntryChange::decode($xdr));
        }
        $valCount = $xdr->readInteger32();
        $operations = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($operations, XdrOperationMeta::decode($xdr));
        }
        $valCount = $xdr->readInteger32();
        $txChangesAfter = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($txChangesAfter, XdrLedgerEntryChange::decode($xdr));
        }
        $valCount = $xdr->readInteger32();
        $events = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($events, XdrOperationEvents::decode($xdr));
        }
        $txResult = XdrTransactionResult::decode($xdr);

        $hashesSize = 3;
        $hashes = array();
        for ($i = 0; $i < $hashesSize; $i++) {
            $hash = $xdr->readOpaqueFixed(32);
            array_push($hashes, $hash);
        }

        $valCount = $xdr->readInteger32();
        $diagnosticEvents = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($diagnosticEvents, XdrOperationDiagnosticEvents::decode($xdr));
        }

        return new XdrTransactionMetaV3($txChangesBefore, $operations, $txChangesAfter, $events, $txResult, $hashes, $diagnosticEvents);
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
     * @return XdrTransactionResult
     */
    public function getTxResult(): XdrTransactionResult
    {
        return $this->txResult;
    }

    /**
     * @param XdrTransactionResult $txResult
     */
    public function setTxResult(XdrTransactionResult $txResult): void
    {
        $this->txResult = $txResult;
    }

    /**
     * @return array
     */
    public function getHashes(): array
    {
        return $this->hashes;
    }

    /**
     * @param array $hashes
     */
    public function setHashes(array $hashes): void
    {
        $this->hashes = $hashes;
    }
}