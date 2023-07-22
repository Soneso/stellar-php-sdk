<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrCreateContractArgs
{

    public XdrContractIDPreimage $contractIDPreimage;
    public XdrContractExecutable $executable;

    /**
     * @param XdrContractIDPreimage $contractIDPreimage
     * @param XdrContractExecutable $executable
     */
    public function __construct(XdrContractIDPreimage $contractIDPreimage,
                                XdrContractExecutable $executable)
    {
        $this->contractIDPreimage = $contractIDPreimage;
        $this->executable = $executable;
    }


    public function encode(): string {
        $bytes = $this->contractIDPreimage->encode();
        $bytes .= $this->executable->encode();
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrCreateContractArgs {
        return new XdrCreateContractArgs(XdrContractIDPreimage::decode($xdr),
            XdrContractExecutable::decode($xdr));
    }

    /**
     * @return XdrContractIDPreimage
     */
    public function getContractIDPreimage(): XdrContractIDPreimage
    {
        return $this->contractIDPreimage;
    }

    /**
     * @param XdrContractIDPreimage $contractIDPreimage
     */
    public function setContractIDPreimage(XdrContractIDPreimage $contractIDPreimage): void
    {
        $this->contractIDPreimage = $contractIDPreimage;
    }

    /**
     * @return XdrContractExecutable
     */
    public function getExecutable(): XdrContractExecutable
    {
        return $this->executable;
    }

    /**
     * @param XdrContractExecutable $executable
     */
    public function setExecutable(XdrContractExecutable $executable): void
    {
        $this->executable = $executable;
    }

}