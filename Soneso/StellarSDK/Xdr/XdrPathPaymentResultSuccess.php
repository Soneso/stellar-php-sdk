<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrPathPaymentResultSuccess
{
    private array $offers; //[XdrClaimAtom]
    private XdrSimplePaymentResult $last;

    /**
     * @return array
     */
    public function getOffers(): array
    {
        return $this->offers;
    }

    /**
     * @return XdrSimplePaymentResult
     */
    public function getLast(): XdrSimplePaymentResult
    {
        return $this->last;
    }

    public function encode(): string {
        $bytes = XdrEncoder::integer32(count($this->offers));
        foreach($this->offers as $val) {
            if ($val instanceof XdrClaimAtom) {
                $bytes .= $val->encode();
            }
        }
        $bytes .= $this->last->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrPathPaymentResultSuccess {

        $result = new XdrPathPaymentResultSuccess();
        $count = $xdr->readInteger32();
        $offers = array();
        for ($i = 0; $i < $count; $i++) {
            array_push($offers, XdrClaimAtom::decode($xdr));
        }
        $result->offers = $offers;
        $result->last = XdrSimplePaymentResult::decode($xdr);
        return $result;
    }
}