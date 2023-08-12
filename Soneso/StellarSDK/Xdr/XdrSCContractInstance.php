<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;


class XdrSCContractInstance
{

    public XdrContractExecutable $executable;
    public ?array $storage = null; // [XdrSCMapEntry]

    /**
     * @param XdrContractExecutable $executable
     * @param array|null $storage
     */
    public function __construct(XdrContractExecutable $executable, ?array $storage)
    {
        $this->executable = $executable;
        $this->storage = $storage;
    }


    public function encode(): string {
        $bytes = $this->executable->encode();
        if ($this->storage !== null) {
            $bytes .= XdrEncoder::integer32(1);
            $bytes .= XdrEncoder::integer32(count($this->storage));
            foreach($this->storage  as $val) {
                if ($val instanceof XdrSCMapEntry) {
                    $bytes .= $val->encode();
                }
            }
        }
        else {
            $bytes .= XdrEncoder::integer32(0);
        }

        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrSCContractInstance {
        $result = new XdrSCContractInstance(XdrContractExecutable::decode($xdr), null);
        if ($xdr->readInteger32() == 1) {
            $valCount = $xdr->readInteger32();
            $arr = array();
            for ($i = 0; $i < $valCount; $i++) {
                array_push($arr, XdrSCMapEntry::decode($xdr));
            }
            $result->storage = $arr;
        }
        return $result;
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

    /**
     * @return array|null
     */
    public function getStorage(): ?array
    {
        return $this->storage;
    }

    /**
     * @param array|null $storage
     */
    public function setStorage(?array $storage): void
    {
        $this->storage = $storage;
    }

}