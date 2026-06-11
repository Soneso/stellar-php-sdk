<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\Toml;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Yosymfony\Toml\Exception\ParseException;

class StellarTomlFromDomainTest extends TestCase
{
    private string $tomlBody = 'VERSION="2.0.0"
NETWORK_PASSPHRASE="Public Global Stellar Network ; September 2015"
FEDERATION_SERVER="https://federation.example.com"
SIGNING_KEY="GBBHQ7H4V6RRORKYLHTCAWP6MOHNORRFJSDPXDFYDGJB2LPZUFPXUEW3"
DIRECT_PAYMENT_SERVER="https://payments.example.com"

[[CURRENCIES]]
code="USD"
issuer="GCZJM35NKGVK47BB4SPBDV25477PZYIYPVVG453LPYFNXLS3FGHDXOCM"
display_decimals=2';

    public function testFromDomainSuccess(): void
    {
        $mock = new MockHandler([
            new Response(200, [], $this->tomlBody)
        ]);

        $stack = new HandlerStack();
        $stack->setHandler($mock);
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) {
            $this->assertEquals("GET", $request->getMethod());
            $this->assertEquals(
                "https://example.com/.well-known/stellar.toml",
                (string)$request->getUri()
            );
            return $request;
        }));

        $stellarToml = StellarToml::fromDomain("example.com", new Client(['handler' => $stack]));

        $generalInformation = $stellarToml->getGeneralInformation();
        $this->assertNotNull($generalInformation);
        $this->assertEquals("2.0.0", $generalInformation->version);
        $this->assertEquals(
            "Public Global Stellar Network ; September 2015",
            $generalInformation->networkPassphrase
        );
        $this->assertEquals("https://federation.example.com", $generalInformation->federationServer);
        $this->assertEquals(
            "GBBHQ7H4V6RRORKYLHTCAWP6MOHNORRFJSDPXDFYDGJB2LPZUFPXUEW3",
            $generalInformation->signingKey
        );
        $this->assertEquals("https://payments.example.com", $generalInformation->directPaymentServer);

        $currencies = $stellarToml->getCurrencies()->toArray();
        $this->assertCount(1, $currencies);
        $this->assertEquals("USD", $currencies[0]->code);
        $this->assertEquals(
            "GCZJM35NKGVK47BB4SPBDV25477PZYIYPVVG453LPYFNXLS3FGHDXOCM",
            $currencies[0]->issuer
        );
        $this->assertEquals(2, $currencies[0]->displayDecimals);
    }

    public function testFromDomainNotFound(): void
    {
        $mock = new MockHandler([
            new Response(404, [], "not found")
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Stellar toml not found.");
        StellarToml::fromDomain("example.com", new Client(['handler' => HandlerStack::create($mock)]));
    }

    public function testFromDomainNon200StatusCode(): void
    {
        // With http_errors disabled the client returns the response so that
        // the explicit status code check in fromDomain throws.
        $mock = new MockHandler([
            new Response(500, [], "internal server error")
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock), 'http_errors' => false]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Stellar toml not found. Response status code 500");
        StellarToml::fromDomain("example.com", $client);
    }

    public function testFromDomainServerError(): void
    {
        $mock = new MockHandler([
            new Response(500, [], "internal server error")
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Stellar toml not found.");
        StellarToml::fromDomain("example.com", new Client(['handler' => HandlerStack::create($mock)]));
    }

    public function testFromDomainMalformedToml(): void
    {
        $mock = new MockHandler([
            new Response(200, [], "this is = not [ valid toml ===")
        ]);

        $this->expectException(ParseException::class);
        StellarToml::fromDomain("example.com", new Client(['handler' => HandlerStack::create($mock)]));
    }

    public function testFromDomainRejectsDomainWithPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid domain");
        StellarToml::fromDomain("example.com/.well-known/../evil");
    }

    public function testFromDomainRejectsDomainWithWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid domain");
        StellarToml::fromDomain("exa mple.com");
    }

    public function testCurrencyFromUrlRejectsHttpUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Service URL must use HTTPS");
        StellarToml::currencyFromUrl("http://example.com/.well-known/USD.toml");
    }

    public function testCurrencyFromUrlSuccess(): void
    {
        $currencyToml = 'code = "USD"' . "\n"
            . 'issuer = "GAKL4XMWLXQKYNYR6ZDVLZT5FXQK3PKC4GZW7OKJX4KQLJKRWBWDXNYK"' . "\n"
            . 'display_decimals = 2' . "\n"
            . 'name = "US Dollar"' . "\n";
        $mock = new MockHandler([
            new Response(200, [], $currencyToml),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $currency = StellarToml::currencyFromUrl("https://example.com/.well-known/USD.toml", $client);

        $this->assertEquals("USD", $currency->code);
        $this->assertEquals("GAKL4XMWLXQKYNYR6ZDVLZT5FXQK3PKC4GZW7OKJX4KQLJKRWBWDXNYK", $currency->issuer);
        $this->assertEquals(2, $currency->displayDecimals);
        $this->assertEquals("US Dollar", $currency->name);
        $this->assertSame(0, $mock->count());
    }

    public function testCurrencyFromUrlNotFound(): void
    {
        $mock = new MockHandler([
            new Response(404, [], 'not found'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $exception = false;
        try {
            StellarToml::currencyFromUrl("https://example.com/.well-known/USD.toml", $client);
        } catch (Exception $e) {
            $exception = str_contains($e->getMessage(), 'Currency toml not found');
        }
        $this->assertTrue($exception);
    }

    public function testCurrencyFromUrlMalformedToml(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'this is = not [ valid toml ==='),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $this->expectException(ParseException::class);
        StellarToml::currencyFromUrl("https://example.com/.well-known/USD.toml", $client);
    }
}
