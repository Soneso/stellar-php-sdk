<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use Exception;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Claimant;
use Soneso\StellarSDK\Xdr\XdrClaimPredicateType;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;

class ClaimantTest extends TestCase
{
    private string $accountId = "GAKL4XMWLXQKYNYR6ZDVLZT5FXQK3PKC4GZW7OKJX4KQLJKRWBWDXNYK";

    public function setUp(): void
    {
        error_reporting(E_ALL);
    }

    public function testClaimantConstruction()
    {
        $predicate = Claimant::predicateUnconditional();
        $claimant = new Claimant($this->accountId, $predicate);

        assertEquals($this->accountId, $claimant->getDestination());
        assertNotNull($claimant->getPredicate());
    }

    public function testPredicateUnconditional()
    {
        $predicate = Claimant::predicateUnconditional();
        assertEquals(XdrClaimPredicateType::UNCONDITIONAL, $predicate->getType()->getValue());
    }

    public function testPredicateAnd()
    {
        $pred1 = Claimant::predicateUnconditional();
        $pred2 = Claimant::predicateBeforeAbsoluteTime(1700000000);

        $andPredicate = Claimant::predicateAnd($pred1, $pred2);
        assertEquals(XdrClaimPredicateType::AND, $andPredicate->getType()->getValue());
        assertNotNull($andPredicate->getAndPredicates());
        assertEquals(2, count($andPredicate->getAndPredicates()));
    }

    public function testPredicateOr()
    {
        $pred1 = Claimant::predicateBeforeAbsoluteTime(1700000000);
        $pred2 = Claimant::predicateBeforeAbsoluteTime(1800000000);

        $orPredicate = Claimant::predicateOr($pred1, $pred2);
        assertEquals(XdrClaimPredicateType::OR, $orPredicate->getType()->getValue());
        assertNotNull($orPredicate->getOrPredicates());
        assertEquals(2, count($orPredicate->getOrPredicates()));
    }

    public function testPredicateNot()
    {
        $pred = Claimant::predicateBeforeAbsoluteTime(1700000000);
        $notPredicate = Claimant::predicateNot($pred);

        assertEquals(XdrClaimPredicateType::NOT, $notPredicate->getType()->getValue());
        assertNotNull($notPredicate->getNotPredicate());
    }

    public function testPredicateBeforeAbsoluteTime()
    {
        $timestamp = 1700000000;
        $predicate = Claimant::predicateBeforeAbsoluteTime($timestamp);

        assertEquals(XdrClaimPredicateType::BEFORE_ABSOLUTE_TIME, $predicate->getType()->getValue());
        assertEquals($timestamp, $predicate->getAbsBefore());
    }

    public function testPredicateBeforeRelativeTime()
    {
        $seconds = 86400;
        $predicate = Claimant::predicateBeforeRelativeTime($seconds);

        assertEquals(XdrClaimPredicateType::BEFORE_RELATIVE_TIME, $predicate->getType()->getValue());
        assertEquals($seconds, $predicate->getRelBefore());
    }

    public function testClaimantToXdr()
    {
        $predicate = Claimant::predicateUnconditional();
        $claimant = new Claimant($this->accountId, $predicate);

        $xdr = $claimant->toXdr();
        assertNotNull($xdr);
        assertNotNull($xdr->getV0());
        assertEquals($this->accountId, $xdr->getV0()->getDestination()->getAccountId());
    }

    public function testClaimantFromXdr()
    {
        $predicate = Claimant::predicateUnconditional();
        $claimant = new Claimant($this->accountId, $predicate);

        $xdr = $claimant->toXdr();
        $parsed = Claimant::fromXdr($xdr);

        assertEquals($this->accountId, $parsed->getDestination());
        assertEquals(XdrClaimPredicateType::UNCONDITIONAL, $parsed->getPredicate()->getType()->getValue());
    }

    public function testClaimantWithComplexPredicate()
    {
        $pred1 = Claimant::predicateBeforeAbsoluteTime(1700000000);
        $pred2 = Claimant::predicateBeforeRelativeTime(86400);
        $orPredicate = Claimant::predicateOr($pred1, $pred2);
        $notPredicate = Claimant::predicateNot($orPredicate);

        $claimant = new Claimant($this->accountId, $notPredicate);

        $xdr = $claimant->toXdr();
        $parsed = Claimant::fromXdr($xdr);

        assertEquals($this->accountId, $parsed->getDestination());
        assertEquals(XdrClaimPredicateType::NOT, $parsed->getPredicate()->getType()->getValue());
    }

    public function testMultipleAndPredicates()
    {
        $pred1 = Claimant::predicateBeforeAbsoluteTime(1700000000);
        $pred2 = Claimant::predicateBeforeRelativeTime(86400);

        $andPredicate = Claimant::predicateAnd($pred1, $pred2);

        $claimant = new Claimant($this->accountId, $andPredicate);
        $xdr = $claimant->toXdr();
        $parsed = Claimant::fromXdr($xdr);

        assertEquals(XdrClaimPredicateType::AND, $parsed->getPredicate()->getType()->getValue());
        assertEquals(2, count($parsed->getPredicate()->getAndPredicates()));
    }

    public function testNestedPredicates()
    {
        $pred1 = Claimant::predicateBeforeAbsoluteTime(1700000000);
        $pred2 = Claimant::predicateBeforeRelativeTime(86400);
        $pred3 = Claimant::predicateUnconditional();

        $andPredicate = Claimant::predicateAnd($pred1, $pred2);
        $orPredicate = Claimant::predicateOr($andPredicate, $pred3);

        $claimant = new Claimant($this->accountId, $orPredicate);

        $xdr = $claimant->toXdr();
        $parsed = Claimant::fromXdr($xdr);

        assertEquals(XdrClaimPredicateType::OR, $parsed->getPredicate()->getType()->getValue());
    }

    public function testTimeBasedPredicateValues()
    {
        $absoluteTime = 1700000000;
        $predicate = Claimant::predicateBeforeAbsoluteTime($absoluteTime);
        $claimant = new Claimant($this->accountId, $predicate);

        $xdr = $claimant->toXdr();
        $parsed = Claimant::fromXdr($xdr);

        assertEquals($absoluteTime, $parsed->getPredicate()->getAbsBefore());
    }

    public function testRelativeTimePredicateValues()
    {
        $relativeTime = 86400;
        $predicate = Claimant::predicateBeforeRelativeTime($relativeTime);
        $claimant = new Claimant($this->accountId, $predicate);

        $xdr = $claimant->toXdr();
        $parsed = Claimant::fromXdr($xdr);

        assertEquals($relativeTime, $parsed->getPredicate()->getRelBefore());
    }
}
