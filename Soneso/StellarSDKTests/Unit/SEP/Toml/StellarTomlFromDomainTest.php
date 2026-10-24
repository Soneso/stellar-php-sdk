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
use Soneso\StellarSDK\SEP\Toml\Documentation;
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

    /** @dataProvider emptyCurrencyBodies */
    public function testCurrencyFromUrlWithEmptyBodyThrowsException(string $body): void
    {
        $mock = new MockHandler([new Response(200, [], $body)]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        $url = 'https://example.com/.well-known/USD.toml';
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid currency toml {$url}: no fields");
        try {
            StellarToml::currencyFromUrl($url, $client);
        } finally {
            $this->assertSame(0, $mock->count());
        }
    }

    /** @return array<string, array{string}> */
    public static function emptyCurrencyBodies(): array
    {
        return [
            'empty' => [''],
            'newline only' => ["\n"],
            'comment only' => ['# only a comment'],
        ];
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
    /** @dataProvider mistypedFields */
    public function testMistypedFieldThrowsException(string $toml, string $field, string $type): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid stellar.toml field {$field}: found {$type}");
        new StellarToml($toml);
    }

    /** @return array<string, array{string, string, string}> */
    public static function mistypedFields(): array
    {
        $sections = [
            '' => 'VERSION NETWORK_PASSPHRASE FEDERATION_SERVER AUTH_SERVER TRANSFER_SERVER '
                . 'TRANSFER_SERVER_SEP0024 KYC_SERVER WEB_AUTH_ENDPOINT WEB_AUTH_FOR_CONTRACTS_ENDPOINT '
                . 'WEB_AUTH_CONTRACT_ID SIGNING_KEY HORIZON_URL ACCOUNTS URI_REQUEST_SIGNING_KEY '
                . 'DIRECT_PAYMENT_SERVER ANCHOR_QUOTE_SERVER',
            '[DOCUMENTATION]' => 'ORG_NAME ORG_DBA ORG_URL ORG_LOGO ORG_DESCRIPTION ORG_PHYSICAL_ADDRESS '
                . 'ORG_PHYSICAL_ADDRESS_ATTESTATION ORG_PHONE_NUMBER ORG_PHONE_NUMBER_ATTESTATION '
                . 'ORG_KEYBASE ORG_TWITTER ORG_GITHUB ORG_OFFICIAL_EMAIL ORG_SUPPORT_EMAIL '
                . 'ORG_LICENSING_AUTHORITY ORG_LICENSE_TYPE ORG_LICENSE_NUMBER',
            '[[PRINCIPALS]]' => 'name email keybase twitter telegram github id_photo_hash verification_photo_hash',
            '[[CURRENCIES]]' => 'toml code code_template issuer contract status display_decimals name desc '
                . 'conditions image fixed_number max_number is_unlimited is_asset_anchored anchor_asset_type '
                . 'anchor_asset attestation_of_reserve redemption_instructions collateral_addresses '
                . 'collateral_address_messages collateral_address_signatures regulated approval_server approval_criteria',
            '[[VALIDATORS]]' => 'ALIAS DISPLAY_NAME PUBLIC_KEY HOST HISTORY',
        ];
        $data = [];
        foreach ($sections as $section => $fields) {
            foreach (explode(' ', $fields) as $field) {
                $numericOrBool = in_array($field, [
                    'display_decimals', 'fixed_number', 'max_number', 'is_unlimited', 'is_asset_anchored', 'regulated',
                ], true);
                $value = $numericOrBool ? '"7"' : '2';
                $fieldName = $section === '[[PRINCIPALS]]' ? "PRINCIPALS.{$field}" : $field;
                $data[$section . $field] = [$section . "\n{$field} = {$value}", $fieldName, $numericOrBool ? 'string' : 'int'];
            }
        }
        foreach (['DOCUMENTATION', 'PRINCIPALS', 'CURRENCIES', 'VALIDATORS'] as $section) {
            $data[$section] = ["{$section} = 2", $section, 'int'];
        }
        foreach (['PRINCIPALS', 'CURRENCIES', 'VALIDATORS'] as $section) {
            $data[$section . ' item'] = ["{$section} = [2]", $section, 'int'];
        }
        $data['DOCUMENTATION array of tables'] = ['[[DOCUMENTATION]]' . "\n" . 'ORG_NAME = "Org"', 'DOCUMENTATION', 'list'];
        $data['DOCUMENTATION inline list'] = ['DOCUMENTATION = ["Org"]', 'DOCUMENTATION', 'list'];
        return $data;
    }

    /** @dataProvider emptyDocumentationTables */
    public function testEmptyDocumentationParsesToEmptyDocumentation(string $toml): void
    {
        $documentation = (new StellarToml($toml))->getDocumentation();
        $this->assertInstanceOf(Documentation::class, $documentation);
        $this->assertSame(get_object_vars(new Documentation()), get_object_vars($documentation));
    }

    /** @return array<string, array{string}> */
    public static function emptyDocumentationTables(): array
    {
        return [
            'empty array' => ['DOCUMENTATION = []'],
            'empty table' => ['[DOCUMENTATION]'],
        ];
    }

    public function testFromDomainPreservesTypeException(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], 'VERSION = 2'),
        ]))]);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid stellar.toml field VERSION: found int');
        StellarToml::fromDomain('example.com', $client);
    }

    public function testCurrencyFromUrlPreservesTypeException(): void
    {
        $client = new Client(['handler' => HandlerStack::create(new MockHandler([
            new Response(200, [], 'display_decimals = "7"'),
        ]))]);
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid stellar.toml field display_decimals: found string');
        StellarToml::currencyFromUrl('https://example.com/USD.toml', $client);
    }

}
