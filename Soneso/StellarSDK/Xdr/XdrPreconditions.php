<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrPreconditions
{

    private XdrPreconditionType $type;
    private ?XdrTimeBounds $timeBounds = null;
    private ?XdrPreconditionsV2 $v2 = null;

    /**
     * @param XdrPreconditionType $type
     */
    public function __construct(XdrPreconditionType $type)
    {
        $this->type = $type;
    }

    /**
     * @return XdrPreconditionType
     */
    public function getType(): XdrPreconditionType
    {
        return $this->type;
    }

    /**
     * @param XdrPreconditionType $type
     */
    public function setType(XdrPreconditionType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return XdrTimeBounds|null
     */
    public function getTimeBounds(): ?XdrTimeBounds
    {
        return $this->timeBounds;
    }

    /**
     * @param XdrTimeBounds|null $timeBounds
     */
    public function setTimeBounds(?XdrTimeBounds $timeBounds): void
    {
        $this->timeBounds = $timeBounds;
    }

    /**
     * @return XdrPreconditionsV2|null
     */
    public function getV2(): ?XdrPreconditionsV2
    {
        return $this->v2;
    }

    /**
     * @param XdrPreconditionsV2|null $v2
     */
    public function setV2(?XdrPreconditionsV2 $v2): void
    {
        $this->v2 = $v2;
    }

    public function encode(): string {
        $bytes = $this->type->encode();

        if ($this->getType()->getValue() == XdrPreconditionType::TIME) {
            $bytes .= $this->timeBounds->encode();
        }
        else if ($this->getType()->getValue() == XdrPreconditionType::V2) {
            $bytes .= $this->v2->encode();
        }

        return $bytes;
    }

    public static function decode(XdrBuffer $xdr):  XdrPreconditions {
        $result = new XdrPreconditions(XdrPreconditionType::decode($xdr));
        if ($result->getType()->getValue() == XdrPreconditionType::TIME) {
            $result->setTimeBounds(XdrTimeBounds::decode($xdr));
        } else if ($result->getType()->getValue() == XdrPreconditionType::V2) {
            $result->setV2(XdrPreconditionsV2::decode($xdr));
        }
        return $result;
    }
}