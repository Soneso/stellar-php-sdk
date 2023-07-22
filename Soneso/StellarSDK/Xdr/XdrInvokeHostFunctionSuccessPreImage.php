<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrInvokeHostFunctionSuccessPreImage
{
    public XdrSCVal $returnValue;
    public array $events; // [XdrContractEvent]

    /**
     * @param XdrSCVal $returnValue
     * @param array $events
     */
    public function __construct(XdrSCVal $returnValue, array $events)
    {
        $this->returnValue = $returnValue;
        $this->events = $events;
    }


    public function encode(): string {
        $bytes = $this->returnValue->encode();
        $bytes .= XdrEncoder::integer32(count($this->events));
        foreach($this->events as $val) {
            $bytes .= $val->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrInvokeHostFunctionSuccessPreImage {
        $returnValue = XdrSCVal::decode($xdr);
        $valCount = $xdr->readInteger32();
        $entriesArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($entriesArr, XdrContractEvent::decode($xdr));
        }
        return new XdrInvokeHostFunctionSuccessPreImage($returnValue, $entriesArr);
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