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

    // validatePathSegment tests

    public function testPathSegmentAcceptsValidId(): void
    {
        UrlValidator::validatePathSegment('abc123-def456', 'id');
        UrlValidator::validatePathSegment('550e8400-e29b-41d4-a716-446655440000', 'id');
        $this->assertTrue(true); // no exception thrown
    }

    public function testPathSegmentRejectsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid value for 'id'");
        UrlValidator::validatePathSegment('', 'id');
    }

    public function testPathSegmentRejectsPathTraversal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validatePathSegment('../../etc/passwd', 'id');
    }

    public function testPathSegmentRejectsForwardSlash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validatePathSegment('foo/bar', 'id');
    }

    public function testPathSegmentRejectsBackslash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validatePathSegment('foo\\bar', 'id');
    }

    public function testPathSegmentRejectsNullByte(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validatePathSegment("foo\0bar", 'id');
    }

    public function testPathSegmentRejectsQueryDelimiter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validatePathSegment('id?param=value', 'id');
    }

    public function testPathSegmentRejectsFragmentDelimiter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validatePathSegment('id#fragment', 'id');
    }

    public function testPathSegmentRejectsEncodedTraversal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validatePathSegment('%2e%2e%2fetc%2fpasswd', 'id');
    }

    public function testPathSegmentRejectsEncodedSlash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validatePathSegment('foo%2Fbar', 'id');
    }

    public function testPathSegmentRejectsEncodedNullByte(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validatePathSegment('foo%00bar', 'id');
    }

    public function testPathSegmentRejectsDoubleDot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validatePathSegment('..', 'id');
    }

    // validateDomain tests

    public function testDomainAcceptsValidDomain(): void
    {
        UrlValidator::validateDomain('example.com');
        UrlValidator::validateDomain('api.stellar.org');
        UrlValidator::validateDomain('localhost');
        $this->assertTrue(true);
    }

    public function testDomainAcceptsDomainWithPort(): void
    {
        UrlValidator::validateDomain('example.com:8443');
        $this->assertTrue(true);
    }

    public function testDomainAcceptsIpv6Literal(): void
    {
        UrlValidator::validateDomain('[::1]');
        $this->assertTrue(true);
    }

    public function testDomainRejectsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validateDomain('');
    }

    public function testDomainRejectsPathTraversal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validateDomain('../etc');
    }

    public function testDomainRejectsWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validateDomain('example .com');
    }

    public function testDomainRejectsQueryCharacter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validateDomain('example.com?foo');
    }

    public function testDomainRejectsNullByte(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validateDomain("example.com\0evil");
    }

    public function testDomainRejectsAtSign(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validateDomain('user@example.com');
    }

    public function testDomainRejectsDotOnly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validateDomain('...');
    }

    public function testDomainRejectsSingleDot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        UrlValidator::validateDomain('.');
    }
}
