<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\Xdr;

class XdrClaimPredicate
{
    private XdrClaimPredicateType $type;
    private ?array $andPredicates = null; // [XdrClaimPredicate]
    private ?array $orPredicates = null; // [XdrClaimPredicate]
    private ?XdrClaimPredicate $notPredicate = null;
    private ?int $absBefore = null;
    private ?int $relBefore = null;

    public function __construct(XdrClaimPredicateType $type) {
        $this->type = $type;
    }

    /**
     * @return XdrClaimPredicateType
     */
    public function getType(): XdrClaimPredicateType
    {
        return $this->type;
    }

    /**
     * @return array|null
     */
    public function getAndPredicates(): ?array
    {
        return $this->andPredicates;
    }

    /**
     * @param array|null $andPredicates
     */
    public function setAndPredicates(?array $andPredicates): void
    {
        $this->andPredicates = $andPredicates;
    }

    /**
     * @return array|null
     */
    public function getOrPredicates(): ?array
    {
        return $this->orPredicates;
    }

    /**
     * @param array|null $orPredicates
     */
    public function setOrPredicates(?array $orPredicates): void
    {
        $this->orPredicates = $orPredicates;
    }

    /**
     * @return XdrClaimPredicate|null
     */
    public function getNotPredicate(): ?XdrClaimPredicate
    {
        return $this->notPredicate;
    }

    /**
     * @param XdrClaimPredicate|null $notPredicate
     */
    public function setNotPredicate(?XdrClaimPredicate $notPredicate): void
    {
        $this->notPredicate = $notPredicate;
    }


    /**
     * @return int|null
     */
    public function getAbsBefore(): ?int
    {
        return $this->absBefore;
    }

    /**
     * @param int|null $absBefore
     */
    public function setAbsBefore(?int $absBefore): void
    {
        $this->absBefore = $absBefore;
    }

    /**
     * @return int|null
     */
    public function getRelBefore(): ?int
    {
        return $this->relBefore;
    }

    /**
     * @param int|null $relBefore
     */
    public function setRelBefore(?int $relBefore): void
    {
        $this->relBefore = $relBefore;
    }

    public function encode() : string {
        $bytes = $this->type->encode();
        switch ($this->type->getValue()) {
            case XdrClaimPredicateType::UNCONDITIONAL:
                break;
            case XdrClaimPredicateType::AND:
                $size = count($this->andPredicates);
                $bytes .= XdrEncoder::integer32($size);
                foreach ($this->andPredicates as $predicate) {
                    if ($predicate instanceof  XdrClaimPredicate) {
                        $bytes .= $predicate->encode();
                    }
                }
                break;
            case XdrClaimPredicateType::OR:
                $size = count($this->orPredicates);
                $bytes .= XdrEncoder::integer32($size);
                foreach ($this->orPredicates as $predicate) {
                    if ($predicate instanceof  XdrClaimPredicate) {
                        $bytes .= $predicate->encode();
                    }
                }
                break;
            case XdrClaimPredicateType::NOT:
                $bytes .= XdrEncoder::integer32(1);
                $bytes .= $this->notPredicate->encode();
                break;
            case XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME:
                $bytes .= XdrEncoder::integer64($this->absBefore);
                break;
            case XdrClaimPredicateType::BEFORE_RELATIVE_TIME:
                $bytes .= XdrEncoder::integer64($this->relBefore);
                break;
        }
        return $bytes;
    }

    public static function decode(XdrBuffer $xdr) : XdrClaimPredicate {
        $typeInt = $xdr->readInteger32();
        $type = new XdrClaimPredicateType($typeInt);
        $result = new XdrClaimPredicate($type);
        switch ($typeInt) {
            case XdrClaimPredicateType::UNCONDITIONAL:
                break;
            case XdrClaimPredicateType::AND:
                $size = $xdr->readInteger32();
                $andPredicates = array();
                for ($i = 0; $i < $size; $i++) {
                    array_push($andPredicates, XdrClaimPredicate::decode($xdr));
                }
                $result->setAndPredicates($andPredicates);
                break;
            case XdrClaimPredicateType::OR:
                $size = $xdr->readInteger32();
                $orPredicates = array();
                for ($i = 0; $i < $size; $i++) {
                    array_push($orPredicates, XdrClaimPredicate::decode($xdr));
                }
                $result->setOrPredicates($orPredicates);
                break;
            case XdrClaimPredicateType::NOT:
                $size = $xdr->readInteger32();
                $notPredicates = array();
                for ($i = 0; $i < $size; $i++) {
                    array_push($notPredicates, XdrClaimPredicate::decode($xdr));
                }
                $result->setNotPredicate($notPredicates[0]);
                break;
            case XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME:
                $unixTime = $xdr->readInteger64();
                $result->setAbsBefore($unixTime);
                break;
            case XdrClaimPredicateType::BEFORE_RELATIVE_TIME:
                $unixTime = $xdr->readInteger64();
                $result->setRelBefore($unixTime);
                break;
        }
        return $result;
    }
}