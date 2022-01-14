<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK;

use Soneso\StellarSDK\Xdr\XdrAccountID;
use Soneso\StellarSDK\Xdr\XdrClaimant;
use Soneso\StellarSDK\Xdr\XdrClaimantType;
use Soneso\StellarSDK\Xdr\XdrClaimantV0;
use Soneso\StellarSDK\Xdr\XdrClaimPredicate;
use Soneso\StellarSDK\Xdr\XdrClaimPredicateType;

class Claimant
{
    private string $destination;
    private XdrClaimPredicate $predicate;

    public function __construct(string $destination, XdrClaimPredicate $predicate) {
        $this->destination = $destination;
        $this->predicate = $predicate;
    }

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @return XdrClaimPredicate
     */
    public function getPredicate(): XdrClaimPredicate
    {
        return $this->predicate;
    }

    public static function predicateUnconditional() : XdrClaimPredicate {
        $type = new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL);
        return new XdrClaimPredicate($type);
    }

    public static function predicateAnd(XdrClaimPredicate $left, XdrClaimPredicate $right) : XdrClaimPredicate {
        $type = new XdrClaimPredicateType(XdrClaimPredicateType::AND);
        $result = new XdrClaimPredicate($type);
        $arr = array();
        array_push($arr, $left);
        array_push($arr, $right);
        $result->setAndPredicates($arr);
        return $result;
    }

    public static function predicateOr(XdrClaimPredicate $left, XdrClaimPredicate $right) : XdrClaimPredicate {
        $type = new XdrClaimPredicateType(XdrClaimPredicateType::OR);
        $result = new XdrClaimPredicate($type);
        $arr = array();
        array_push($arr, $left);
        array_push($arr, $right);
        $result->setOrPredicates($arr);
        return $result;
    }

    public static function predicateNot(XdrClaimPredicate $predicate) : XdrClaimPredicate {
        $type = new XdrClaimPredicateType(XdrClaimPredicateType::NOT);
        $result = new XdrClaimPredicate($type);
        $result->setNotPredicate($predicate);
        return $result;
    }

    public static function predicateBeforeAbsoluteTime(int $unixEpoch) : XdrClaimPredicate {
        $type = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME);
        $result = new XdrClaimPredicate($type);
        $result->setAbsBefore($unixEpoch);
        return $result;
    }

    public static function predicateBeforeRelativeTime(int $unixEpoch) : XdrClaimPredicate {
        $type = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_RELATIVE_TIME);
        $result = new XdrClaimPredicate($type);
        $result->setRelBefore($unixEpoch);
        return $result;
    }

    public static function fromXdr(XdrClaimant $xdr) : Claimant {
        $destination = $xdr->getV0()->getDestination()->getAccountId();
        $predicate = $xdr->getV0()->getPredicate();
        return new Claimant($destination, $predicate);
    }

    public function toXdr() : XdrClaimant {
        $type = new XdrClaimantType(XdrClaimantType::V0);
        $result = new XdrClaimant($type);
        $v0 = new XdrClaimantV0(XdrAccountID::fromAccountId($this->destination), $this->predicate);
        $result->setV0($v0);
        return $result;
    }
}