<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrOperationMetaV2
{

    public XdrExtensionPoint $ext;

    /**
     * @var array<XdrLedgerEntryChange> $changes
     */
    public array $changes;

    /**
     * @var array<XdrContractEvent> $events
     */
    public array $events;

    /**
     * @param XdrExtensionPoint $ext
     * @param array<XdrLedgerEntryChange> $changes
     * @param array<XdrContractEvent> $events
     */
    public function __construct(XdrExtensionPoint $ext, array $changes, array $events)
    {
        $this->ext = $ext;
        $this->changes = $changes;
        $this->events = $events;
    }

    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= XdrEncoder::integer32(count($this->changes));
        foreach($this->changes as $val) {
            if ($val instanceof XdrLedgerEntryChange) {
                $bytes .= $val->encode();
            }
        }
        $bytes .= XdrEncoder::integer32(count($this->events));
        foreach($this->events as $val) {
            if ($val instanceof XdrContractEvent) {
                $bytes .= $val->encode();
            }
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrOperationMetaV2 {
        $ext = XdrExtensionPoint::decode($xdr);
        $valCount = $xdr->readInteger32();
        /**
         * @var array<XdrLedgerEntryChange> $changes
         */
        $changes = array();
        for ($i = 0; $i < $valCount; $i++) {
            $changes[] = XdrLedgerEntryChange::decode($xdr);
        }
        $valCount = $xdr->readInteger32();
        /**
         * @var array<XdrContractEvent> $events
         */
        $events = array();
        for ($i = 0; $i < $valCount; $i++) {
            $events[] = XdrContractEvent::decode($xdr);
        }

        return new XdrOperationMetaV2($ext,$changes,$events);
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
    public function getChanges(): array
    {
        return $this->changes;
    }

    /**
     * @param array $changes
     */
    public function setChanges(array $changes): void
    {
        $this->changes = $changes;
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
}