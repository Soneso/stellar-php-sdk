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

/**
 * Represents a claimant for claimable balances
 *
 * A claimant is an account that can claim a claimable balance, subject to
 * optional predicates that control when the balance can be claimed. Predicates
 * can be based on time (absolute or relative) or combined using logical operators
 * (AND, OR, NOT).
 *
 * @package Soneso\StellarSDK
 * @see https://developers.stellar.org/docs/encyclopedia/claimable-balances Documentation on claimable balances
 */
class Claimant
{
    private string $destination;
    private XdrClaimPredicate $predicate;

    /**
     * Claimant constructor
     *
     * @param string $destination The account ID that can claim the balance
     * @param XdrClaimPredicate $predicate The conditions under which the balance can be claimed
     */
    public function __construct(string $destination, XdrClaimPredicate $predicate) {
        $this->destination = $destination;
        $this->predicate = $predicate;
    }

    /**
     * Gets the destination account ID
     *
     * @return string The account ID that can claim the balance
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * Gets the claim predicate
     *
     * @return XdrClaimPredicate The conditions for claiming the balance
     */
    public function getPredicate(): XdrClaimPredicate
    {
        return $this->predicate;
    }

    /**
     * Creates an unconditional claim predicate
     *
     * The balance can be claimed at any time without restrictions.
     *
     * @return XdrClaimPredicate An unconditional predicate
     */
    public static function predicateUnconditional() : XdrClaimPredicate {
        $type = new XdrClaimPredicateType(XdrClaimPredicateType::UNCONDITIONAL);
        return new XdrClaimPredicate($type);
    }

    /**
     * Creates an AND predicate combining two predicates
     *
     * Both predicates must be satisfied for the balance to be claimable.
     *
     * @param XdrClaimPredicate $left The first predicate
     * @param XdrClaimPredicate $right The second predicate
     * @return XdrClaimPredicate An AND predicate
     */
    public static function predicateAnd(XdrClaimPredicate $left, XdrClaimPredicate $right) : XdrClaimPredicate {
        $type = new XdrClaimPredicateType(XdrClaimPredicateType::AND);
        $result = new XdrClaimPredicate($type);
        $arr = array();
        array_push($arr, $left);
        array_push($arr, $right);
        $result->setAndPredicates($arr);
        return $result;
    }

    /**
     * Creates an OR predicate combining two predicates
     *
     * Either predicate can be satisfied for the balance to be claimable.
     *
     * @param XdrClaimPredicate $left The first predicate
     * @param XdrClaimPredicate $right The second predicate
     * @return XdrClaimPredicate An OR predicate
     */
    public static function predicateOr(XdrClaimPredicate $left, XdrClaimPredicate $right) : XdrClaimPredicate {
        $type = new XdrClaimPredicateType(XdrClaimPredicateType::OR);
        $result = new XdrClaimPredicate($type);
        $arr = array();
        array_push($arr, $left);
        array_push($arr, $right);
        $result->setOrPredicates($arr);
        return $result;
    }

    /**
     * Creates a NOT predicate inverting another predicate
     *
     * The wrapped predicate must NOT be satisfied for the balance to be claimable.
     *
     * @param XdrClaimPredicate $predicate The predicate to negate
     * @return XdrClaimPredicate A NOT predicate
     */
    public static function predicateNot(XdrClaimPredicate $predicate) : XdrClaimPredicate {
        $type = new XdrClaimPredicateType(XdrClaimPredicateType::NOT);
        $result = new XdrClaimPredicate($type);
        $result->setNotPredicate($predicate);
        return $result;
    }

    /**
     * Creates a predicate based on absolute time
     *
     * The balance can be claimed before the specified Unix timestamp.
     *
     * @param int $unixEpoch The Unix timestamp (seconds since epoch)
     * @return XdrClaimPredicate A time-based predicate
     */
    public static function predicateBeforeAbsoluteTime(int $unixEpoch) : XdrClaimPredicate {
        $type = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME);
        $result = new XdrClaimPredicate($type);
        $result->setAbsBefore($unixEpoch);
        return $result;
    }

    /**
     * Creates a predicate based on relative time
     *
     * The balance can be claimed before the specified duration has elapsed
     * since the claimable balance was created.
     *
     * @param int $unixEpoch The number of seconds after the claimable balance creation (NOT a Unix timestamp)
     * @return XdrClaimPredicate A relative time-based predicate
     */
    public static function predicateBeforeRelativeTime(int $unixEpoch) : XdrClaimPredicate {
        $type = new XdrClaimPredicateType(XdrClaimPredicateType::BEFORE_RELATIVE_TIME);
        $result = new XdrClaimPredicate($type);
        $result->setRelBefore($unixEpoch);
        return $result;
    }

    /**
     * Creates a Claimant from XDR format
     *
     * @param XdrClaimant $xdr The XDR encoded claimant
     * @return Claimant The decoded claimant object
     */
    public static function fromXdr(XdrClaimant $xdr) : Claimant {
        $destination = $xdr->getV0()->getDestination()->getAccountId();
        $predicate = $xdr->getV0()->getPredicate();
        return new Claimant($destination, $predicate);
    }

    /**
     * Converts this claimant to XDR format
     *
     * @return XdrClaimant The XDR representation of this claimant
     */
    public function toXdr() : XdrClaimant {
        $type = new XdrClaimantType(XdrClaimantType::V0);
        $result = new XdrClaimant($type);
        $v0 = new XdrClaimantV0(XdrAccountID::fromAccountId($this->destination), $this->predicate);
        $result->setV0($v0);
        return $result;
    }
}