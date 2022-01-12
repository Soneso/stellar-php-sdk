<?php  declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests;

use Exception;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\SEP\Federation\Federation;

class SEP002Test extends TestCase
{
    /**
     * @throws Exception
     */
    public function testResolveStellarAddress(): void
    {
        $response = Federation::resolveStellarAddress("bob*soneso.com");
        $this->assertNotNull($response);
        $this->assertEquals("bob*soneso.com", $response->getStellarAddress());
        $this->assertEquals("GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI", $response->getAccountId());
        $this->assertEquals("text", $response->getMemoType());
        $this->assertEquals("hello memo text", $response->getMemo());
    }

    public function testResolveStellarAccountId(): void
    {
        $response = Federation::resolveStellarAccountId("GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI", "https://stellarid.io/federation");
        $this->assertNotNull($response);
        $this->assertEquals("bob*soneso.com", $response->getStellarAddress());
        $this->assertEquals("GBVPKXWMAB3FIUJB6T7LF66DABKKA2ZHRHDOQZ25GBAEFZVHTBPJNOJI", $response->getAccountId());
        $this->assertEquals("text", $response->getMemoType());
        $this->assertEquals("hello memo text", $response->getMemo());

    }
}