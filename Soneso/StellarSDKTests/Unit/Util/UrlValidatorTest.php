<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\Util;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Util\UrlValidator;

class UrlValidatorTest extends TestCase
{
    public function testAcceptsHttpsUrl(): void
    {
        UrlValidator::validateHttpsRequired('https://api.stellar.org/auth');
        $this->assertTrue(true); // no exception thrown
    }

    public function testAcceptsHttpsUrlWithPort(): void
    {
        UrlValidator::validateHttpsRequired('https://api.stellar.org:443/auth');
        $this->assertTrue(true);
    }

    public function testAcceptsUppercaseHttpsScheme(): void
    {
        UrlValidator::validateHttpsRequired('HTTPS://api.stellar.org/auth');
        $this->assertTrue(true);
    }

    public function testAcceptsMixedCaseHttpsScheme(): void
    {
        UrlValidator::validateHttpsRequired('Https://api.stellar.org/auth');
        $this->assertTrue(true);
    }

    public function testAcceptsHttpLocalhost(): void
    {
        UrlValidator::validateHttpsRequired('http://localhost');
        $this->assertTrue(true);
    }

    public function testAcceptsHttpLocalhostWithPort(): void
    {
        UrlValidator::validateHttpsRequired('http://localhost:8000');
        $this->assertTrue(true);
    }

    public function testAcceptsHttpLocalhostWithPath(): void
    {
        UrlValidator::validateHttpsRequired('http://localhost:8080/api/v1');
        $this->assertTrue(true);
    }

    public function testAcceptsHttp127001(): void
    {
        UrlValidator::validateHttpsRequired('http://127.0.0.1');
        $this->assertTrue(true);
    }

    public function testAcceptsHttp127001WithPort(): void
    {
        UrlValidator::validateHttpsRequired('http://127.0.0.1:3000');
        $this->assertTrue(true);
    }

    public function testAcceptsHttpIpv6Localhost(): void
    {
        UrlValidator::validateHttpsRequired('http://[::1]:8000');
        $this->assertTrue(true);
    }

    public function testRejectsHttpRemoteUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Service URL must use HTTPS');
        UrlValidator::validateHttpsRequired('http://api.stellar.org/auth');
    }

    public function testRejectsHttpRemoteUrlWithPort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validateHttpsRequired('http://api.stellar.org:8080/auth');
    }

    public function testRejectsUrlWithoutScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validateHttpsRequired('api.stellar.org/auth');
    }

    public function testRejectsEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validateHttpsRequired('');
    }

    public function testRejectsLocalhostSubdomain(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validateHttpsRequired('http://localhost.evil.com/auth');
    }

    public function testRejectsFtpScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validateHttpsRequired('ftp://files.stellar.org/data');
    }
}
