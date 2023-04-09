<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrDiagnosticEvent
{

    public bool $inSuccessfulContractCall;
    public XdrContractEvent $event;

    /**
     * @param bool $inSuccessfulContractCall
     * @param XdrContractEvent $event
     */
    public function __construct(bool $inSuccessfulContractCall, XdrContractEvent $event)
    {
        $this->inSuccessfulContractCall = $inSuccessfulContractCall;
        $this->event = $event;
    }


    public function encode(): string {
        $bytes = XdrEncoder::boolean($this->inSuccessfulContractCall);
        $bytes .= $this->event->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrDiagnosticEvent {
        return new XdrDiagnosticEvent($xdr->readBoolean(), XdrContractEvent::decode($xdr));
    }

    public static function fromBase64Xdr(String $base64Xdr) : XdrDiagnosticEvent {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrDiagnosticEvent::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }

}