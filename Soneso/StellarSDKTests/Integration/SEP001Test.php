<?php

namespace Soneso\StellarSDKTests\Integration;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\SEP\Toml\Currency;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Soneso\StellarSDK\SEP\Toml\Validator;

class SEP001Test extends TestCase
{
    public function testTomlDomain(): void
    {
        $stellarToml = StellarToml::fromDomain("soneso.com");
        $this->assertNotNull($stellarToml);
        $generalInformation = $stellarToml->getGeneralInformation();
        $this->assertNotNull($generalInformation);
        $this->assertEquals("2.0.0", $generalInformation->version);
        $this->assertEquals("Public Global Stellar Network ; September 2015", $generalInformation->networkPassphrase);
        $this->assertEquals("https://stellarid.io/federation/", $generalInformation->federationServer);
        $this->assertEquals("https://api.domain.com/auth", $generalInformation->authServer);
        $this->assertEquals("https://api.domain.com", $generalInformation->transferServer);
        $this->assertNull($generalInformation->transferServerSep24);
        $this->assertNull($generalInformation->kYCServer);
        $this->assertNull($generalInformation->webAuthEndpoint);
        $this->assertNull($generalInformation->webAuthForContractsEndpoint);
        $this->assertNull($generalInformation->webAuthContractId);
        $this->assertEquals("GBBHQ7H4V6RRORKYLHTCAWP6MOHNORRFJSDPXDFYDGJB2LPZUFPXUEW3", $generalInformation->signingKey);
        $this->assertEquals("https://horizon.domain.com", $generalInformation->horizonUrl);
        $this->assertTrue(in_array("GD5DJQDDBKGAYNEAXU562HYGOOSYAEOO6AS53PZXBOZGCP5M2OPGMZV3", $generalInformation->accounts));
        $this->assertTrue(in_array("GAENZLGHJGJRCMX5VCHOLHQXU3EMCU5XWDNU4BGGJFNLI2EL354IVBK7", $generalInformation->accounts));
        $this->assertTrue(in_array("GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U", $generalInformation->accounts));
        $this->assertNull($generalInformation->uriRequestSigningKey);

        $documentation = $stellarToml->getDocumentation();
        $this->assertNotNull($documentation);
        $this->assertEquals("Organization Name", $documentation->orgName);
        $this->assertEquals("Organization DBA", $documentation->orgDBA);
        $this->assertEquals("https://www.domain.com", $documentation->orgUrl);
        $this->assertEquals("https://www.domain.com/awesomelogo.png", $documentation->orgLogo);
        $this->assertEquals("Description of issuer", $documentation->orgDescription);
        $this->assertEquals("123 Sesame Street, New York, NY 12345, United States", $documentation->orgPhysicalAddress);
        $this->assertEquals("https://www.domain.com/address_attestation.jpg", $documentation->orgPhysicalAddressAttestation);
        $this->assertEquals("1 (123)-456-7890", $documentation->orgPhoneNumber);
        $this->assertEquals("https://www.domain.com/phone_attestation.jpg", $documentation->orgPhoneNumberAttestation);
        $this->assertEquals("accountname", $documentation->orgKeybase);
        $this->assertEquals("orgtweet", $documentation->orgTwitter);
        $this->assertEquals("orgcode", $documentation->orgGithub);
        $this->assertEquals("support@domain.com", $documentation->orgOfficialEmail);
        $this->assertNull($documentation->orgLicensingAuthority);
        $this->assertNull($documentation->orgLicenseType);
        $this->assertNull($documentation->orgLicenseNumber);

        $principals = $stellarToml->getPrincipals();
        $this->assertNotNull($principals);
        foreach ($principals as $pointOfContact) {
            $this->assertEquals("Jane Jedidiah Johnson", $pointOfContact->name);
            $this->assertEquals("jane@domain.com", $pointOfContact->email);
            $this->assertEquals("crypto_jane", $pointOfContact->keybase);
            $this->assertNull($pointOfContact->telegram);
            $this->assertEquals("crypto_jane", $pointOfContact->twitter);
            $this->assertEquals("crypto_jane", $pointOfContact->github);
            $this->assertEquals("be688838ca8686e5c90689bf2ab585cef1137c999b48c70b92f67a5c34dc15697b5d11c982ed6d71be1e1e7f7b4e0733884aa97c3f7a339a8ed03577cf74be09", $pointOfContact->idPhotoHash);
            $this->assertEquals("016ba8c4cfde65af99cb5fa8b8a37e2eb73f481b3ae34991666df2e04feb6c038666ebd1ec2b6f623967756033c702dde5f423f7d47ab6ed1827ff53783731f7", $pointOfContact->verificationPhotoHash);
            break;
        }

        $currencies = $stellarToml->getCurrencies()->toArray();
        $this->assertCount(4, $currencies);
        $firstCurrency = $currencies[0];
        $this->assertTrue($firstCurrency instanceof Currency);
        if ($firstCurrency instanceof Currency) {
            $this->assertEquals("USD", $firstCurrency->code);
            $this->assertEquals("GCZJM35NKGVK47BB4SPBDV25477PZYIYPVVG453LPYFNXLS3FGHDXOCM", $firstCurrency->issuer);
            $this->assertNull($firstCurrency->contract);
            $this->assertEquals(2, $firstCurrency->displayDecimals);
        }
        $secondCurrency = $currencies[1];
        $this->assertTrue($secondCurrency instanceof Currency);
        if ($secondCurrency instanceof Currency) {
            $this->assertEquals("crypto", $secondCurrency->anchorAssetType);
            $this->assertEquals("BTC", $secondCurrency->anchorAsset);
            $this->assertEquals("Use SEP6 with our federation server", $secondCurrency->redemptionInstructions);
            $this->assertTrue(in_array("2C1mCx3ukix1KfegAY5zgQJV7sanAciZpv", $secondCurrency->collateralAddresses));
            $this->assertTrue(in_array("304502206e21798a42fae0e854281abd38bacd1aeed3ee3738d9e1446618c4571d10", $secondCurrency->collateralAddressSignatures));
        }
        $thirdCurrency = $currencies[2];
        $this->assertTrue($thirdCurrency instanceof Currency);
        if ($thirdCurrency instanceof Currency) {
            $this->assertEquals("GOAT", $thirdCurrency->code);
            $this->assertEquals("GD5T6IPRNCKFOHQWT264YPKOZAWUMMZOLZBJ6BNQMUGPWGRLBK3U7ZNP", $thirdCurrency->issuer);
            $this->assertEquals(2, $thirdCurrency->displayDecimals);
            $this->assertEquals("goat share", $thirdCurrency->name);
            $this->assertEquals("1 GOAT token entitles you to a share of revenue from Elkins Goat Farm.", $thirdCurrency->desc);
            $this->assertEquals("There will only ever be 10,000 GOAT tokens in existence. We will distribute the revenue share annually on Jan. 15th", $thirdCurrency->conditions);
            $this->assertEquals("https://static.thenounproject.com/png/2292360-200.png", $thirdCurrency->image);
            $this->assertEquals(10000, $thirdCurrency->fixedNumber);
        }

        $tomlCurrency = $currencies[3];
        $this->assertTrue($tomlCurrency instanceof Currency);
        if ($tomlCurrency instanceof Currency) {
            $this->assertEquals("https://soneso.com/.well-known/TESTC.toml", $tomlCurrency->toml);
            $currency = StellarToml::currencyFromUrl($tomlCurrency->toml);
            $this->assertNotNull($currency);
            $this->assertEquals("TESTC", $currency->code);
            $this->assertEquals("GCPWPTAX6QVJQIQARN2WESISHVLN65D4HAGQECHLCAV22UST3W2Q6QTA", $currency->issuer);
            $this->assertEquals(2, $currency->displayDecimals);
            $this->assertEquals("test currency", $currency->name);
            $this->assertEquals("TESTC description", $currency->desc);
            $this->assertEquals("TESTC conditions", $currency->conditions);
            $this->assertEquals("https://soneso.com/123.png", $currency->image);
            $this->assertEquals(10000, $currency->fixedNumber);
        }

        $validators = $stellarToml->getValidators()->toArray();
        $this->assertNotNull($validators);
        $this->assertCount(3, $validators);
        $firstValidator = $validators[0];
        $this->assertTrue($firstValidator instanceof Validator);
        if ($firstValidator instanceof Validator) {
            $this->assertEquals("domain-au", $firstValidator->alias);
            $this->assertEquals("Domain Australia", $firstValidator->displayName);
            $this->assertEquals("core-au.domain.com:11625", $firstValidator->host);
            $this->assertEquals("GD5DJQDDBKGAYNEAXU562HYGOOSYAEOO6AS53PZXBOZGCP5M2OPGMZV3", $firstValidator->publicKey);
            $this->assertEquals("http://history.domain.com/prd/core-live/core_live_001/", $firstValidator->history);
        }
        $secondValidator = $validators[1];
        $this->assertTrue($secondValidator instanceof Validator);
        if ($secondValidator instanceof Validator) {
            $this->assertEquals("domain-sg", $secondValidator->alias);
            $this->assertEquals("Domain Singapore", $secondValidator->displayName);
            $this->assertEquals("core-sg.domain.com:11625", $secondValidator->host);
            $this->assertEquals("GAENZLGHJGJRCMX5VCHOLHQXU3EMCU5XWDNU4BGGJFNLI2EL354IVBK7", $secondValidator->publicKey);
            $this->assertEquals("http://history.domain.com/prd/core-live/core_live_002/", $secondValidator->history);
        }
        $thirdValidator = $validators[2];
        $this->assertTrue($thirdValidator instanceof Validator);
        if ($thirdValidator instanceof Validator) {
            $this->assertEquals("domain-us", $thirdValidator->alias);
            $this->assertEquals("Domain United States", $thirdValidator->displayName);
            $this->assertEquals("core-us.domain.com:11625", $thirdValidator->host);
            $this->assertEquals("GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U", $thirdValidator->publicKey);
            $this->assertEquals("http://history.domain.com/prd/core-live/core_live_003/", $thirdValidator->history);
        }
    }

}
