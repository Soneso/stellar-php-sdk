<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrSorobanAuthorizedContractFunction
{

    public XdrSCAddress $contractAddress;
    public string $functionName;
    public array $args; // [XdrSCVal]

    /**
     * @param XdrSCAddress $contractAddress
     * @param string $functionName
     * @param array $args
     */
    public function __construct(XdrSCAddress $contractAddress, string $functionName, array $args)
    {
        $this->contractAddress = $contractAddress;
        $this->functionName = $functionName;
        $this->args = $args;
    }

    public function encode(): string {
        $bytes = $this->contractAddress->encode();
        $bytes .= XdrEncoder::string($this->functionName);
        $bytes .= XdrEncoder::integer32(count($this->args));
        foreach($this->args as $val) {
            if ($val instanceof XdrSCVal) {
                $bytes .= $val->encode();
            }
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrSorobanAuthorizedContractFunction {
        $contractAddress = XdrSCAddress::decode($xdr);
        $functionName = $xdr->readString();
        $valCount = $xdr->readInteger32();
        $args = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($args, XdrSCVal::decode($xdr));
        }

        return new XdrSorobanAuthorizedContractFunction($contractAddress, $functionName, $args);
    }

    /**
     * @return XdrSCAddress
     */
    public function getContractAddress(): XdrSCAddress
    {
        return $this->contractAddress;
    }

    /**
     * @param XdrSCAddress $contractAddress
     */
    public function setContractAddress(XdrSCAddress $contractAddress): void
    {
        $this->contractAddress = $contractAddress;
    }

    /**
     * @return string
     */
    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    /**
     * @param string $functionName
     */
    public function setFunctionName(string $functionName): void
    {
        $this->functionName = $functionName;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @param array $args
     */
    public function setArgs(array $args): void
    {
        $this->args = $args;
    }

}