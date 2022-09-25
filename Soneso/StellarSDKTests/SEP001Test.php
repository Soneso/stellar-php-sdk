<?php

namespace Soneso\StellarSDKTests;

use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\SEP\Toml\Currency;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Soneso\StellarSDK\SEP\Toml\Validator;

class SEP001Test extends TestCase
{
    public function testFromTomlString(): void {
        $toml = '# Sample stellar.toml
          VERSION="2.0.0"
          
          NETWORK_PASSPHRASE="Public Global Stellar Network ; September 2015"
          FEDERATION_SERVER="https://stellarid.io/federation/"
          AUTH_SERVER="https://api.domain.com/auth"
          TRANSFER_SERVER="https://api.domain.com"
          SIGNING_KEY="GBBHQ7H4V6RRORKYLHTCAWP6MOHNORRFJSDPXDFYDGJB2LPZUFPXUEW3"
          HORIZON_URL="https://horizon.domain.com"
          ACCOUNTS=[
              "GD5DJQDDBKGAYNEAXU562HYGOOSYAEOO6AS53PZXBOZGCP5M2OPGMZV3",
              "GAENZLGHJGJRCMX5VCHOLHQXU3EMCU5XWDNU4BGGJFNLI2EL354IVBK7",
              "GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U"
          ]
          DIRECT_PAYMENT_SERVER="https://test.direct-payment.com"
          ANCHOR_QUOTE_SERVER="https://test.anchor-quote.com"
          
          [DOCUMENTATION]
          ORG_NAME="Organization Name"
          ORG_DBA="Organization DBA"
          ORG_URL="https://www.domain.com"
          ORG_LOGO="https://www.domain.com/awesomelogo.png"
          ORG_DESCRIPTION="Description of issuer"
          ORG_PHYSICAL_ADDRESS="123 Sesame Street, New York, NY 12345, United States"
          ORG_PHYSICAL_ADDRESS_ATTESTATION="https://www.domain.com/address_attestation.jpg"
          ORG_PHONE_NUMBER="1 (123)-456-7890"
          ORG_PHONE_NUMBER_ATTESTATION="https://www.domain.com/phone_attestation.jpg"
          ORG_KEYBASE="accountname"
          ORG_TWITTER="orgtweet"
          ORG_GITHUB="orgcode"
          ORG_OFFICIAL_EMAIL="info@domain.com"
          ORG_SUPPORT_EMAIL="support@domain.com"
    
          [[PRINCIPALS]]
          name="Jane Jedidiah Johnson"
          email="jane@domain.com"
          keybase="crypto_jane"
          twitter="crypto_jane"
          github="crypto_jane"
          id_photo_hash="be688838ca8686e5c90689bf2ab585cef1137c999b48c70b92f67a5c34dc15697b5d11c982ed6d71be1e1e7f7b4e0733884aa97c3f7a339a8ed03577cf74be09"
          verification_photo_hash="016ba8c4cfde65af99cb5fa8b8a37e2eb73f481b3ae34991666df2e04feb6c038666ebd1ec2b6f623967756033c702dde5f423f7d47ab6ed1827ff53783731f7"
    
          [[CURRENCIES]]
          code="USD"
          issuer="GCZJM35NKGVK47BB4SPBDV25477PZYIYPVVG453LPYFNXLS3FGHDXOCM"
          display_decimals=2
    
          [[CURRENCIES]]
          code="BTC"
          issuer="GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U"
          display_decimals=7
          anchor_asset_type="crypto"
          anchor_asset="BTC"
          redemption_instructions="Use SEP6 with our federation server"
          collateral_addresses=["2C1mCx3ukix1KfegAY5zgQJV7sanAciZpv"]
          collateral_address_signatures=["304502206e21798a42fae0e854281abd38bacd1aeed3ee3738d9e1446618c4571d10"]
    
              # asset with meta info
          [[CURRENCIES]]
          code="GOAT"
          issuer="GD5T6IPRNCKFOHQWT264YPKOZAWUMMZOLZBJ6BNQMUGPWGRLBK3U7ZNP"
          display_decimals=2
          name="goat share"
          desc="1 GOAT token entitles you to a share of revenue from Elkins Goat Farm."
          conditions="There will only ever be 10,000 GOAT tokens in existence. We will distribute the revenue share annually on Jan. 15th"
          image="https://static.thenounproject.com/png/2292360-200.png"
          fixed_number=10000
    
          [[VALIDATORS]]
          ALIAS="domain-au"
          DISPLAY_NAME="Domain Australia"
          HOST="core-au.domain.com:11625"
          PUBLIC_KEY="GD5DJQDDBKGAYNEAXU562HYGOOSYAEOO6AS53PZXBOZGCP5M2OPGMZV3"
          HISTORY="http://history.domain.com/prd/core-live/core_live_001/"
    
          [[VALIDATORS]]
          ALIAS="domain-sg"
          DISPLAY_NAME="Domain Singapore"
          HOST="core-sg.domain.com:11625"
          PUBLIC_KEY="GAENZLGHJGJRCMX5VCHOLHQXU3EMCU5XWDNU4BGGJFNLI2EL354IVBK7"
          HISTORY="http://history.domain.com/prd/core-live/core_live_002/"
    
          [[VALIDATORS]]
          ALIAS="domain-us"
          DISPLAY_NAME="Domain United States"
          HOST="core-us.domain.com:11625"
          PUBLIC_KEY="GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U"
          HISTORY="http://history.domain.com/prd/core-live/core_live_003/"
    
          # optional extra information for humans
          # Useful place for anchors to detail various policies and required info
    
          ###################################
          # Required compliance fields:
          #      name=<recipient name>
          #      addr=<recipient address>
          # Federation Format:
          #        <phone number>*anchor.com
          #        Forwarding supported by sending to: forward*anchor.com
          #           forward_type=bank_account
          #           swift=<swift code of receiving bank>
          #           acct=<recipient account number at receiving bank>
          # Minimum Amount Forward: \$2 USD
          # Maximum Amount Forward: \$10000 USD';

        $stellarToml = new StellarToml($toml);
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
        $this->assertEquals("GBBHQ7H4V6RRORKYLHTCAWP6MOHNORRFJSDPXDFYDGJB2LPZUFPXUEW3", $generalInformation->signingKey);
        $this->assertEquals("https://horizon.domain.com", $generalInformation->horizonUrl);
        $this->assertTrue(in_array("GD5DJQDDBKGAYNEAXU562HYGOOSYAEOO6AS53PZXBOZGCP5M2OPGMZV3", $generalInformation->accounts));
        $this->assertTrue(in_array("GAENZLGHJGJRCMX5VCHOLHQXU3EMCU5XWDNU4BGGJFNLI2EL354IVBK7", $generalInformation->accounts));
        $this->assertTrue(in_array("GAOO3LWBC4XF6VWRP5ESJ6IBHAISVJMSBTALHOQM2EZG7Q477UWA6L7U", $generalInformation->accounts));
        $this->assertNull($generalInformation->uriRequestSigningKey);
        $this->assertEquals("https://test.direct-payment.com", $generalInformation->directPaymentServer);
        $this->assertEquals("https://test.anchor-quote.com", $generalInformation->anchorQuoteServer);

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
        $this->assertEquals("info@domain.com", $documentation->orgOfficialEmail);
        $this->assertEquals("support@domain.com", $documentation->orgSupportEmail);
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
        $this->assertCount(3, $currencies);
        $firstCurrency = $currencies[0];
        $this->assertTrue($firstCurrency instanceof Currency);
        if ($firstCurrency instanceof Currency) {
            $this->assertEquals("USD", $firstCurrency->code);
            $this->assertEquals("GCZJM35NKGVK47BB4SPBDV25477PZYIYPVVG453LPYFNXLS3FGHDXOCM", $firstCurrency->issuer);
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