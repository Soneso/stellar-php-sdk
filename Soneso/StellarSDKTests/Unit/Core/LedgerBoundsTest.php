<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\LedgerBounds;
use Soneso\StellarSDK\Xdr\XdrLedgerBounds;

/**
 * Unit tests for LedgerBounds
 *
 * Tests constructor, getters/setters, and XDR encoding/decoding.
 */
class LedgerBoundsTest extends TestCase
{
    public function testConstructor(): void
    {
        $bounds = new LedgerBounds(100, 200);

        $this->assertEquals(100, $bounds->getMinLedger());
        $this->assertEquals(200, $bounds->getMaxLedger());
    }

    public function testGetMinLedger(): void
    {
        $bounds = new LedgerBounds(1000, 2000);
        $this->assertEquals(1000, $bounds->getMinLedger());
    }

    public function testSetMinLedger(): void
    {
        $bounds = new LedgerBounds(100, 200);
        $this->assertEquals(100, $bounds->getMinLedger());

        $bounds->setMinLedger(150);
        $this->assertEquals(150, $bounds->getMinLedger());
    }

    public function testGetMaxLedger(): void
    {
        $bounds = new LedgerBounds(1000, 5000);
        $this->assertEquals(5000, $bounds->getMaxLedger());
    }

    public function testSetMaxLedger(): void
    {
        $bounds = new LedgerBounds(100, 200);
        $this->assertEquals(200, $bounds->getMaxLedger());

        $bounds->setMaxLedger(300);
        $this->assertEquals(300, $bounds->getMaxLedger());
    }

    public function testToXdr(): void
    {
        $bounds = new LedgerBounds(100, 200);
        $xdr = $bounds->toXdr();

        $this->assertInstanceOf(XdrLedgerBounds::class, $xdr);
        $this->assertEquals(100, $xdr->getMinLedger());
        $this->assertEquals(200, $xdr->getMaxLedger());
    }

    public function testFromXdr(): void
    {
        $xdr = new XdrLedgerBounds(500, 1000);
        $bounds = LedgerBounds::fromXdr($xdr);

        $this->assertEquals(500, $bounds->getMinLedger());
        $this->assertEquals(1000, $bounds->getMaxLedger());
    }

    public function testToXdrFromXdrRoundTrip(): void
    {
        $original = new LedgerBounds(12345, 67890);
        $xdr = $original->toXdr();
        $decoded = LedgerBounds::fromXdr($xdr);

        $this->assertEquals($original->getMinLedger(), $decoded->getMinLedger());
        $this->assertEquals($original->getMaxLedger(), $decoded->getMaxLedger());
    }

    public function testZeroValues(): void
    {
        $bounds = new LedgerBounds(0, 0);

        $this->assertEquals(0, $bounds->getMinLedger());
        $this->assertEquals(0, $bounds->getMaxLedger());
    }

    public function testLargeValues(): void
    {
        $minLedger = 1000000000;
        $maxLedger = 2000000000;
        $bounds = new LedgerBounds($minLedger, $maxLedger);

        $this->assertEquals($minLedger, $bounds->getMinLedger());
        $this->assertEquals($maxLedger, $bounds->getMaxLedger());

        $xdr = $bounds->toXdr();
        $decoded = LedgerBounds::fromXdr($xdr);

        $this->assertEquals($minLedger, $decoded->getMinLedger());
        $this->assertEquals($maxLedger, $decoded->getMaxLedger());
    }

    public function testSameMinMax(): void
    {
        $bounds = new LedgerBounds(100, 100);

        $this->assertEquals(100, $bounds->getMinLedger());
        $this->assertEquals(100, $bounds->getMaxLedger());
    }
}
