<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrSorobanTransactionMeta
{
    public XdrSorobanTransactionMetaExt $ext;
    /**
     * @var array<XdrContractEvent> $events
     */
    public array $events;
    public XdrSCVal $returnValue;

    /**
     * @var array<XdrDiagnosticEvent> $diagnosticEvents
     */
    public array $diagnosticEvents;

    /**
     * Constructor.
     *
     * @param XdrSorobanTransactionMetaExt $ext
     * @param array<XdrContractEvent> $events
     * @param XdrSCVal $returnValue
     * @param array<XdrDiagnosticEvent> $diagnosticEvents
     */
    public function __construct(
        XdrSorobanTransactionMetaExt $ext,
        array $events,
        XdrSCVal $returnValue,
        array $diagnosticEvents,
    )
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
        $ext = XdrSorobanTransactionMetaExt::decode($xdr);
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
     * @return XdrSorobanTransactionMetaExt
     */
    public function getExt(): XdrSorobanTransactionMetaExt
    {
        return $this->ext;
    }

    /**
     * @param XdrSorobanTransactionMetaExt $ext
     */
    public function setExt(XdrSorobanTransactionMetaExt $ext): void
    {
        $this->ext = $ext;
    }

    /**
     * @return array<XdrContractEvent>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @param array<XdrContractEvent> $events
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
     * @return array<XdrDiagnosticEvent>
     */
    public function getDiagnosticEvents(): array
    {
        return $this->diagnosticEvents;
    }

    /**
     * @param array<XdrDiagnosticEvent> $diagnosticEvents
     */
    public function setDiagnosticEvents(array $diagnosticEvents): void
    {
        $this->diagnosticEvents = $diagnosticEvents;
    }

}