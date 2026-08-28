<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\TOID;

use InvalidArgumentException;
use OverflowException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\SEP\TOID\TOID;

class TOIDTest extends TestCase
{
    public function testEncodeDecodeRoundTrip(): void
    {
        $cases = [
            [0, 0, 0, 0],
            [0, 0, 1, 1],
            [0, 0, 4095, 4095],
            [0, 1, 0, 4096],
            [0, 1048575, 0, 4294963200],
            [1, 0, 0, 4294967296],
            [1, 0, 1, 4294967297],
            [1, 1, 0, 4294971392],
            [1, 1, 1, 4294971393],
            [1234567, 89, 2000, 5302424890087376],
            [2147483647, 0, 0, 9223372032559808512],
            [2147483647, 1048575, 4095, PHP_INT_MAX],
        ];

        foreach ($cases as [$ledgerSequence, $transactionOrder, $operationIndex, $id]) {
            $toid = new TOID($ledgerSequence, $transactionOrder, $operationIndex);
            $this->assertSame($id, $toid->toInt64());

            $decoded = TOID::fromInt64($id);
            $this->assertSame($ledgerSequence, $decoded->getLedgerSequence());
            $this->assertSame($transactionOrder, $decoded->getTransactionOrder());
            $this->assertSame($operationIndex, $decoded->getOperationIndex());
        }
    }

    public function testIncrementOperationIndexWithinTransaction(): void
    {
        $toid = new TOID(0, 0, 0);

        $toid->incrementOperationIndex();
        $this->assertSame(0, $toid->getLedgerSequence());
        $this->assertSame(0, $toid->getTransactionOrder());
        $this->assertSame(1, $toid->getOperationIndex());

        $toid->incrementOperationIndex();
        $this->assertSame(0, $toid->getLedgerSequence());
        $this->assertSame(0, $toid->getTransactionOrder());
        $this->assertSame(2, $toid->getOperationIndex());

        $toid->incrementOperationIndex();
        $this->assertSame(0, $toid->getLedgerSequence());
        $this->assertSame(0, $toid->getTransactionOrder());
        $this->assertSame(3, $toid->getOperationIndex());
    }

    public function testIncrementOperationIndexRollsOverToNextLedger(): void
    {
        $toid = new TOID(0, 0, 4095);

        $toid->incrementOperationIndex();
        $this->assertSame(1, $toid->getLedgerSequence());
        $this->assertSame(0, $toid->getTransactionOrder());
        $this->assertSame(0, $toid->getOperationIndex());

        $toid->incrementOperationIndex();
        $this->assertSame(1, $toid->getLedgerSequence());
        $this->assertSame(0, $toid->getTransactionOrder());
        $this->assertSame(1, $toid->getOperationIndex());
    }

    public function testIncrementOperationIndexPreservesTransactionOrderAcrossRollover(): void
    {
        $toid = new TOID(5, 7, 4095);

        $toid->incrementOperationIndex();
        $this->assertSame(6, $toid->getLedgerSequence());
        $this->assertSame(7, $toid->getTransactionOrder());
        $this->assertSame(0, $toid->getOperationIndex());
    }

    public function testIncrementOperationIndexRollsOverIntoMaxLedgerSequence(): void
    {
        $toid = new TOID(2147483646, 0, 4095);

        $toid->incrementOperationIndex();
        $this->assertSame(2147483647, $toid->getLedgerSequence());
        $this->assertSame(0, $toid->getTransactionOrder());
        $this->assertSame(0, $toid->getOperationIndex());
        $this->assertSame(9223372032559808512, $toid->toInt64());
    }

    public function testIncrementOperationIndexBelowCeilingAtMaxLedgerSequenceDoesNotThrow(): void
    {
        $toid = new TOID(2147483647, 7, 4094);

        $toid->incrementOperationIndex();
        $this->assertSame(2147483647, $toid->getLedgerSequence());
        $this->assertSame(7, $toid->getTransactionOrder());
        $this->assertSame(4095, $toid->getOperationIndex());
    }

    public function testIncrementOperationIndexThrowsAtCeiling(): void
    {
        $toid = new TOID(2147483647, 0, 4095);

        $this->expectException(OverflowException::class);
        $toid->incrementOperationIndex();
    }

    public function testAfterLedger(): void
    {
        $this->assertSame(4294967295, TOID::afterLedger(0)->toInt64());
        $this->assertSame(8589934591, TOID::afterLedger(1)->toInt64());
        $this->assertSame(433791696895, TOID::afterLedger(100)->toInt64());
    }

    public function testLedgerRangeInclusive(): void
    {
        $cases = [
            [1, 1, 0, 8589934592],
            [1, 2, 0, 12884901888],
            [2, 2, 8589934592, 12884901888],
            [2, 3, 8589934592, 17179869184],
        ];

        foreach ($cases as [$from, $to, $start, $end]) {
            $range = TOID::ledgerRangeInclusive($from, $to);
            $this->assertSame($start, $range->getStart());
            $this->assertSame($end, $range->getEnd());
        }
    }

    public function testConstructorRejectsNegativeLedgerSequence(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TOID(-1, 0, 0);
    }

    public function testConstructorRejectsLedgerSequenceAboveMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TOID(2147483648, 0, 0);
    }

    public function testConstructorRejectsNegativeTransactionOrder(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TOID(0, -1, 0);
    }

    public function testConstructorRejectsTransactionOrderAboveMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TOID(0, 1048576, 0);
    }

    public function testConstructorRejectsNegativeOperationIndex(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TOID(0, 0, -1);
    }

    public function testConstructorRejectsOperationIndexAboveMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TOID(0, 0, 4096);
    }

    public function testFromInt64RejectsNegativeOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TOID::fromInt64(-1);
    }

    public function testFromInt64RejectsPhpIntMin(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TOID::fromInt64(PHP_INT_MIN);
    }

    public function testLedgerRangeInclusiveRejectsFromGreaterThanTo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TOID::ledgerRangeInclusive(2, 1);
    }

    public function testLedgerRangeInclusiveRejectsFromBelowOne(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TOID::ledgerRangeInclusive(0, 1);
    }

    public function testLedgerRangeInclusiveRejectsNegativeFrom(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TOID::ledgerRangeInclusive(-1, 100);
    }

    public function testLedgerRangeInclusiveRejectsToAtLedgerSequenceMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid range end, it must be less than 2147483647.');
        TOID::ledgerRangeInclusive(1, 2147483647);
    }

    public function testLedgerRangeInclusiveRejectsToAboveLedgerSequenceMaximum(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TOID::ledgerRangeInclusive(1, 2147483648);
    }
}
