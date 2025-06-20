<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecEventV0
{

    public string $doc;
    public string $lib;
    public string $name;
    /**
     * @var array<string> $prefixTopics
     */
    public array $prefixTopics;

    /**
     * @var array<XdrSCSpecEventParamV0> $params
     */
    public array $params;

    public XdrSCSpecEventDataFormat $dataFormat;

    /**
     * @param string $doc
     * @param string $lib
     * @param string $name
     * @param array<string> $prefixTopics
     * @param array<XdrSCSpecEventParamV0> $params
     * @param XdrSCSpecEventDataFormat $dataFormat
     */
    public function __construct(string $doc, string $lib, string $name, array $prefixTopics, array $params, XdrSCSpecEventDataFormat $dataFormat)
    {
        $this->doc = $doc;
        $this->lib = $lib;
        $this->name = $name;
        $this->prefixTopics = $prefixTopics;
        $this->params = $params;
        $this->dataFormat = $dataFormat;
    }


    public function encode(): string {
        $bytes = XdrEncoder::string($this->doc);
        $bytes .= XdrEncoder::string($this->lib);
        $bytes .= XdrEncoder::string($this->name);
        $bytes .= XdrEncoder::integer32(count($this->prefixTopics));
        foreach($this->prefixTopics as $val) {
            $bytes .= XdrEncoder::string($val);
        }
        $bytes .= XdrEncoder::integer32(count($this->params));
        foreach($this->params as $val) {
            $bytes .= $val->encode();
        }
        $bytes .= $this->dataFormat->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecEventV0 {
        $doc = $xdr->readString();
        $lib = $xdr->readString();
        $name = $xdr->readString();

        /**
         * @var array<string> $prefixTopics
         */
        $prefixTopics = array();
        $valCount = $xdr->readInteger32();
        for ($i = 0; $i < $valCount; $i++) {
            $prefixTopics[] = $xdr->readString();
        }

        /**
         * @var array<XdrSCSpecEventParamV0> $params
         */
        $params = array();
        $valCount = $xdr->readInteger32();
        for ($i = 0; $i < $valCount; $i++) {
            $params[] = XdrSCSpecEventParamV0::decode($xdr);
        }

        $dataFormat = XdrSCSpecEventDataFormat::decode($xdr);

        return new XdrSCSpecEventV0($doc, $lib, $name, $prefixTopics, $params, $dataFormat);
    }

}