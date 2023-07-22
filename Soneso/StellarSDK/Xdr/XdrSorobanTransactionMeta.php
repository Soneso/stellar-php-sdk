<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrSorobanTransactionMeta
{
    public XdrExtensionPoint $ext;
    public array $events; // [XdrContractEvent]
    public XdrSCVal $returnValue;
    public array $diagnosticEvents; // [XdrDiagnosticEvent]

    /**
     * @param XdrExtensionPoint $ext
     * @param array $events
     * @param XdrSCVal $returnValue
     * @param array $diagnosticEvents
     */
    public function __construct(XdrExtensionPoint $ext, array $events, XdrSCVal $returnValue, array $diagnosticEvents)
    {
        $this->ext = $ext;
        $this->events = $events;
        $this->returnValue = $returnValue;
        $this->diagnosticEvents = $diagnosticEvents;
    }


    public function encode(): string {
        $bytes = $this->ext->encode();
        $bytes .= XdrEncoder::integer32(count($this->events));
        foreach($this->events as $val) {
            $bytes .= $val->encode();
        }
        $bytes .= $this->returnValue->encode();
        $bytes .= XdrEncoder::integer32(count($this->diagnosticEvents));
        foreach($this->events as $val) {
            $bytes .= $val->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSorobanTransactionMeta {
        $ext = XdrExtensionPoint::decode($xdr);
        $valCount = $xdr->readInteger32();
        $eventsArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($eventsArr, XdrContractEvent::decode($xdr));
        }
        $returnValue = XdrSCVal::decode($xdr);
        $dEventsArr = array();
        $valCount = $xdr->readInteger32();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($dEventsArr, XdrDiagnosticEvent::decode($xdr));
        }
        return new XdrSorobanTransactionMeta($ext, $eventsArr, $returnValue, $dEventsArr);
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
     * @return XdrSCVal
     */
    public function getReturnValue(): XdrSCVal
    {
        return $this->returnValue;
    }

    /**
     * @param XdrSCVal $returnValue
     */
    public function setReturnValue(XdrSCVal $returnValue): void
    {
        $this->returnValue = $returnValue;
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