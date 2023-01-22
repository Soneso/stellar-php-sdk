<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrContractEventBodyV0
{
    public array $topics; // [XdrSCVal]
    public XdrSCVal $data;

    /**
     * @param array $topics
     * @param XdrSCVal $data
     */
    public function __construct(array $topics, XdrSCVal $data)
    {
        $this->topics = $topics;
        $this->data = $data;
    }


    public function encode(): string {
        $bytes = XdrEncoder::integer32(count($this->topics));
        foreach($this->topics as $val) {
            if ($val instanceof XdrSCVal) {
                $bytes .= $val->encode();
            }
        }
        $bytes .= $this->data->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrContractEventBodyV0 {
        $valCount = $xdr->readInteger32();
        $topics = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($topics, XdrSCVal::decode($xdr));
        }

        $data = XdrSCVal::decode($xdr);

        return new XdrContractEventBodyV0($topics, $data);
    }

    /**
     * @return array
     */
    public function getTopics(): array
    {
        return $this->topics;
    }

    /**
     * @param array $topics
     */
    public function setTopics(array $topics): void
    {
        $this->topics = $topics;
    }

    /**
     * @return XdrSCVal
     */
    public function getData(): XdrSCVal
    {
        return $this->data;
    }

    /**
     * @param XdrSCVal $data
     */
    public function setData(XdrSCVal $data): void
    {
        $this->data = $data;
    }
}