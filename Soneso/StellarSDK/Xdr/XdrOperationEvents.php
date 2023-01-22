<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrOperationEvents
{

    public array $events; // [XdrContractEvent]

    /**
     * @param array $events
     */
    public function __construct(array $events)
    {
        $this->events = $events;
    }


    public function encode(): string {
        $bytes = XdrEncoder::integer32(count($this->events));
        foreach($this->events as $val) {
            if ($val instanceof XdrContractEvent) {
                $bytes .= $val->encode();
            }
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrOperationEvents {
        $valCount = $xdr->readInteger32();
        $events = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($events, XdrContractEvent::decode($xdr));
        }

        return new XdrOperationEvents($events);
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