<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrTransactionMeta
{
    public int $v;
    public ?array $operations;
    public ?XdrTransactionMetaV1 $v1 = null;
    public ?XdrTransactionMetaV2 $v2 = null;
    public ?XdrTransactionMetaV3 $v3 = null;
    /**
     * @param int $v
     */
    public function __construct(int $v)
    {
        $this->v = $v;
    }


    public function encode() : string {
        $bytes = XdrEncoder::integer32($this->v);

        switch ($this->v) {
            case 0:
                $bytes .= XdrEncoder::integer32(count($this->operations));
                foreach($this->operations as $val) {
                    if ($val instanceof XdrOperationMeta) {
                        $bytes .= $val->encode();
                    }
                }
                break;
            case 1:
                $bytes .= $this->v1->encode();
                break;
            case 2:
                $bytes .= $this->v2->encode();
                break;
            case 3:
                $bytes .= $this->v3->encode();
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrTransactionMeta {
        $v = $xdr->readInteger32();
        $result = new XdrTransactionMeta($v);
        switch ($v) {
            case 0:
                $valCount = $xdr->readInteger32();
                $arr = array();
                for ($i = 0; $i < $valCount; $i++) {
                    array_push($arr, XdrOperationMeta::decode($xdr));
                }
                $result->operations = $arr;
                break;
            case 1:
                $result->v1 = XdrTransactionMetaV1::decode($xdr);
                break;
            case 2:
                $result->v2 = XdrTransactionMetaV2::decode($xdr);
                break;
            case 3:
                $result->v3 = XdrTransactionMetaV3::decode($xdr);
                break;
        }
        return $result;
    }

    public static function fromBase64Xdr(String $base64Xdr) : XdrTransactionMeta {
        $xdr = base64_decode($base64Xdr);
        $xdrBuffer = new XdrBuffer($xdr);
        return XdrTransactionMeta::decode($xdrBuffer);
    }

    public function toBase64Xdr() : String {
        return base64_encode($this->encode());
    }

    /**
     * @return int
     */
    public function getV(): int
    {
        return $this->v;
    }

    /**
     * @param int $v
     */
    public function setV(int $v): void
    {
        $this->v = $v;
    }

    /**
     * @return array|null
     */
    public function getOperations(): ?array
    {
        return $this->operations;
    }

    /**
     * @param array|null $operations
     */
    public function setOperations(?array $operations): void
    {
        $this->operations = $operations;
    }

    /**
     * @return XdrTransactionMetaV1|null
     */
    public function getV1(): ?XdrTransactionMetaV1
    {
        return $this->v1;
    }

    /**
     * @param XdrTransactionMetaV1|null $v1
     */
    public function setV1(?XdrTransactionMetaV1 $v1): void
    {
        $this->v1 = $v1;
    }

    /**
     * @return XdrTransactionMetaV2|null
     */
    public function getV2(): ?XdrTransactionMetaV2
    {
        return $this->v2;
    }

    /**
     * @param XdrTransactionMetaV2|null $v2
     */
    public function setV2(?XdrTransactionMetaV2 $v2): void
    {
        $this->v2 = $v2;
    }

    /**
     * @return XdrTransactionMetaV3|null
     */
    public function getV3(): ?XdrTransactionMetaV3
    {
        return $this->v3;
    }

    /**
     * @param XdrTransactionMetaV3|null $v3
     */
    public function setV3(?XdrTransactionMetaV3 $v3): void
    {
        $this->v3 = $v3;
    }

}