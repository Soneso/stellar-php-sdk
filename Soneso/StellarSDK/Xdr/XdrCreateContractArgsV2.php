<?php declare(strict_types=1);

// Copyright 2024 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrCreateContractArgsV2
{

    public XdrContractIDPreimage $contractIDPreimage;
    public XdrContractExecutable $executable;
    /**
     * @var array<XdrSCVal>
     */
    public array $constructorArgs;

    /**
     * @param XdrContractIDPreimage $contractIDPreimage
     * @param XdrContractExecutable $executable
     * @param array<XdrSCVal> $constructorArgs
     */
    public function __construct(XdrContractIDPreimage $contractIDPreimage, XdrContractExecutable $executable, array $constructorArgs)
    {
        $this->contractIDPreimage = $contractIDPreimage;
        $this->executable = $executable;
        $this->constructorArgs = $constructorArgs;
    }


    public function encode(): string {
        $bytes = $this->contractIDPreimage->encode();
        $bytes .= $this->executable->encode();
        $bytes .= XdrEncoder::integer32(count($this->constructorArgs));
        foreach($this->constructorArgs as $val) {
            $bytes .= $val->encode();
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrCreateContractArgsV2 {
        $preimage = XdrContractIDPreimage::decode($xdr);
        $exec = XdrContractExecutable::decode($xdr);
        $valCount = $xdr->readInteger32();
        $argsArr = array();
        for ($i = 0; $i < $valCount; $i++) {
            array_push($argsArr, XdrSCVal::decode($xdr));
        }

        return new XdrCreateContractArgsV2($preimage, $exec, $argsArr);
    }
}