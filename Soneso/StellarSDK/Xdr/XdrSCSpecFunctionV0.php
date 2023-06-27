<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSCSpecFunctionV0
{

    public string $doc;
    public string $name;
    public array $inputs; // [XdrSCSpecFunctionInputV0]
    public array $outputs; // [XdrSCSpecTypeDef]

    /**
     * @param string $doc
     * @param string $name
     * @param array $inputs [XdrSCSpecFunctionInputV0]
     * @param array $outputs
     */
    public function __construct(string $doc, string $name, array $inputs, array $outputs)
    {
        $this->doc = $doc;
        $this->name = $name;
        $this->inputs = $inputs;
        $this->outputs = $outputs;
    }


    public function encode(): string {
        $bytes = XdrEncoder::string($this->doc);
        $bytes .= XdrEncoder::string($this->name);
        $bytes .= XdrEncoder::integer32(count($this->inputs));
        foreach($this->inputs as $val) {
            $bytes .= $val->encode();
        }
        $bytes .= XdrEncoder::integer32(count($this->outputs));
        foreach($this->outputs as $val) {
            $bytes .= $val->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCSpecFunctionV0 {
        $doc = $xdr->readString();
        $name = $xdr->readString();
        $valCount = $xdr->readInteger32();
        $inputsArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($inputsArr, XdrSCSpecFunctionInputV0::decode($xdr));
        }
        $valCount = $xdr->readInteger32();
        $outputsArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($outputsArr, XdrSCSpecTypeDef::decode($xdr));
        }

        return new XdrSCSpecFunctionV0($doc, $name, $inputsArr, $outputsArr);
    }

    /**
     * @return string
     */
    public function getDoc(): string
    {
        return $this->doc;
    }

    /**
     * @param string $doc
     */
    public function setDoc(string $doc): void
    {
        $this->doc = $doc;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return array [XdrSCSpecFunctionInputV0]
     */
    public function getInputs(): array
    {
        return $this->inputs;
    }

    /**
     * @param array $inputs [XdrSCSpecFunctionInputV0]
     */
    public function setInputs(array $inputs): void
    {
        $this->inputs = $inputs;
    }

    /**
     * @return array [XdrSCSpecTypeDef]
     */
    public function getOutputs(): array
    {
        return $this->outputs;
    }

    /**
     * @param array $outputs [XdrSCSpecTypeDef]
     */
    public function setOutputs(array $outputs): void
    {
        $this->outputs = $outputs;
    }
}