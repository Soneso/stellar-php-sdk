<?php declare(strict_types=1);

// Copyright 2026 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDKTests\Unit\SEP\StandardKYCFields;

use DateTime;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\SEP\StandardKYCFields\CardKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\FinancialAccountKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\OrganizationKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;

class StandardKYCFieldsTest extends TestCase
{
    public function testNaturalPersonKYCFieldsBasicFields(): void
    {
        $naturalPerson = new NaturalPersonKYCFields();
        $naturalPerson->firstName = 'John';
        $naturalPerson->lastName = 'Doe';
        $naturalPerson->additionalName = 'Michael';
        $naturalPerson->emailAddress = 'john.doe@example.com';
        $naturalPerson->mobileNumber = '+14155551234';
        $naturalPerson->mobileNumberFormat = 'E.164';

        $fields = $naturalPerson->fields();

        $this->assertCount(6, $fields);
        $this->assertEquals('John', $fields[NaturalPersonKYCFields::FIRST_NAME_KEY]);
        $this->assertEquals('Doe', $fields[NaturalPersonKYCFields::LAST_NAME_KEY]);
        $this->assertEquals('Michael', $fields[NaturalPersonKYCFields::ADDITIONAL_NAME_KEY]);
        $this->assertEquals('john.doe@example.com', $fields[NaturalPersonKYCFields::EMAIL_ADDRESS_KEY]);
        $this->assertEquals('+14155551234', $fields[NaturalPersonKYCFields::MOBILE_NUMBER_KEY]);
        $this->assertEquals('E.164', $fields[NaturalPersonKYCFields::MOBILE_NUMBER_FORMAT_KEY]);
    }

    public function testNaturalPersonKYCFieldsAddressFields(): void
    {
        $naturalPerson = new NaturalPersonKYCFields();
        $naturalPerson->addressCountryCode = 'USA';
        $naturalPerson->stateOrProvince = 'California';
        $naturalPerson->city = 'San Francisco';
        $naturalPerson->postalCode = '94102';
        $naturalPerson->address = "123 Main St\nSan Francisco, CA 94102\nUSA";

        $fields = $naturalPerson->fields();

        $this->assertCount(5, $fields);
        $this->assertEquals('USA', $fields[NaturalPersonKYCFields::ADDRESS_COUNTRY_CODE_KEY]);
        $this->assertEquals('California', $fields[NaturalPersonKYCFields::STATE_OR_PROVINCE_KEY]);
        $this->assertEquals('San Francisco', $fields[NaturalPersonKYCFields::CITY_KEY]);
        $this->assertEquals('94102', $fields[NaturalPersonKYCFields::POSTAL_CODE_KEY]);
        $this->assertEquals("123 Main St\nSan Francisco, CA 94102\nUSA", $fields[NaturalPersonKYCFields::ADDRESS_KEY]);
    }

    public function testNaturalPersonKYCFieldsBirthAndIdentityFields(): void
    {
        $naturalPerson = new NaturalPersonKYCFields();
        $naturalPerson->birthDate = '1990-05-15';
        $naturalPerson->birthPlace = 'New York City';
        $naturalPerson->birthCountryCode = 'USA';
        $naturalPerson->idType = 'passport';
        $naturalPerson->idCountryCode = 'USA';
        $naturalPerson->idNumber = 'P12345678';
        $naturalPerson->idIssueDate = new DateTime('2020-01-01');
        $naturalPerson->idExpirationDate = new DateTime('2030-01-01');

        $fields = $naturalPerson->fields();

        $this->assertCount(8, $fields);
        $this->assertEquals('1990-05-15', $fields[NaturalPersonKYCFields::BIRTH_DATE_KEY]);
        $this->assertEquals('New York City', $fields[NaturalPersonKYCFields::BIRTH_PLACE_KEY]);
        $this->assertEquals('USA', $fields[NaturalPersonKYCFields::BIRTH_COUNTRY_CODE_KEY]);
        $this->assertEquals('passport', $fields[NaturalPersonKYCFields::ID_TYPE_KEY]);
        $this->assertEquals('USA', $fields[NaturalPersonKYCFields::ID_COUNTRY_CODE_KEY]);
        $this->assertEquals('P12345678', $fields[NaturalPersonKYCFields::ID_NUMBER_KEY]);
        $this->assertStringContainsString('2020-01-01', $fields[NaturalPersonKYCFields::ID_ISSUE_DATE_KEY]);
        $this->assertStringContainsString('2030-01-01', $fields[NaturalPersonKYCFields::ID_EXPIRATION_DATE_KEY]);
    }

    public function testNaturalPersonKYCFieldsTaxAndEmploymentFields(): void
    {
        $naturalPerson = new NaturalPersonKYCFields();
        $naturalPerson->taxId = '123-45-6789';
        $naturalPerson->taxIdName = 'SSN';
        $naturalPerson->occupation = 2120;
        $naturalPerson->employerName = 'Acme Corp';
        $naturalPerson->employerAddress = '456 Corporate Blvd, Business City, CA 94101';

        $fields = $naturalPerson->fields();

        $this->assertCount(5, $fields);
        $this->assertEquals('123-45-6789', $fields[NaturalPersonKYCFields::TAX_ID_KEY]);
        $this->assertEquals('SSN', $fields[NaturalPersonKYCFields::TAX_ID_NAME_KEY]);
        $this->assertEquals('2120', $fields[NaturalPersonKYCFields::OCCUPATION_KEY]);
        $this->assertEquals('Acme Corp', $fields[NaturalPersonKYCFields::EMPLOYER_NAME_KEY]);
        $this->assertEquals('456 Corporate Blvd, Business City, CA 94101', $fields[NaturalPersonKYCFields::EMPLOYER_ADDRESS_KEY]);
    }

    public function testNaturalPersonKYCFieldsAdditionalFields(): void
    {
        $naturalPerson = new NaturalPersonKYCFields();
        $naturalPerson->languageCode = 'en';
        $naturalPerson->ipAddress = '192.168.1.1';
        $naturalPerson->sex = 'male';
        $naturalPerson->referralId = 'REF123456';

        $fields = $naturalPerson->fields();

        $this->assertCount(4, $fields);
        $this->assertEquals('en', $fields[NaturalPersonKYCFields::LANGUAGE_CODE_KEY]);
        $this->assertEquals('192.168.1.1', $fields[NaturalPersonKYCFields::IP_ADDRESS_KEY]);
        $this->assertEquals('male', $fields[NaturalPersonKYCFields::SEX_KEY]);
        $this->assertEquals('REF123456', $fields[NaturalPersonKYCFields::REFERRAL_ID_KEY]);
    }

    public function testNaturalPersonKYCFieldsWithFinancialAccount(): void
    {
        $naturalPerson = new NaturalPersonKYCFields();
        $naturalPerson->firstName = 'Jane';
        $naturalPerson->lastName = 'Smith';

        $financialAccount = new FinancialAccountKYCFields();
        $financialAccount->bankName = 'Bank of America';
        $financialAccount->bankAccountNumber = '1234567890';
        $financialAccount->bankAccountType = 'checking';

        $naturalPerson->financialAccountKYCFields = $financialAccount;

        $fields = $naturalPerson->fields();

        $this->assertCount(5, $fields);
        $this->assertEquals('Jane', $fields[NaturalPersonKYCFields::FIRST_NAME_KEY]);
        $this->assertEquals('Smith', $fields[NaturalPersonKYCFields::LAST_NAME_KEY]);
        $this->assertEquals('Bank of America', $fields[FinancialAccountKYCFields::BANK_NAME_KEY]);
        $this->assertEquals('1234567890', $fields[FinancialAccountKYCFields::BANK_ACCOUNT_NUMBER_KEY]);
        $this->assertEquals('checking', $fields[FinancialAccountKYCFields::BANK_ACCOUNT_TYPE_KEY]);
    }

    public function testNaturalPersonKYCFieldsWithCard(): void
    {
        $naturalPerson = new NaturalPersonKYCFields();
        $naturalPerson->firstName = 'Alice';
        $naturalPerson->lastName = 'Johnson';

        $card = new CardKYCFields();
        $card->number = '4111111111111111';
        $card->expirationDate = '25-12';
        $card->holderName = 'Alice Johnson';

        $naturalPerson->cardKYCFields = $card;

        $fields = $naturalPerson->fields();

        $this->assertCount(5, $fields);
        $this->assertEquals('Alice', $fields[NaturalPersonKYCFields::FIRST_NAME_KEY]);
        $this->assertEquals('Johnson', $fields[NaturalPersonKYCFields::LAST_NAME_KEY]);
        $this->assertEquals('4111111111111111', $fields[CardKYCFields::NUMBER_KEY]);
        $this->assertEquals('25-12', $fields[CardKYCFields::EXPIRATION_DATE_KEY]);
        $this->assertEquals('Alice Johnson', $fields[CardKYCFields::HOLDER_NAME_KEY]);
    }

    public function testNaturalPersonKYCFieldsFiles(): void
    {
        $naturalPerson = new NaturalPersonKYCFields();
        $naturalPerson->photoIdFront = base64_encode('front_photo_data');
        $naturalPerson->photoIdBack = base64_encode('back_photo_data');
        $naturalPerson->notaryApprovalOfPhotoId = base64_encode('notary_data');
        $naturalPerson->photoProofResidence = base64_encode('residence_proof_data');
        $naturalPerson->proofOfIncome = base64_encode('income_proof_data');
        $naturalPerson->proofOfLiveness = base64_encode('liveness_data');

        $files = $naturalPerson->files();

        $this->assertCount(6, $files);
        $this->assertEquals(base64_encode('front_photo_data'), $files[NaturalPersonKYCFields::PHOTO_ID_FRONT_KEY]);
        $this->assertEquals(base64_encode('back_photo_data'), $files[NaturalPersonKYCFields::PHOTO_ID_BACK_KEY]);
        $this->assertEquals(base64_encode('notary_data'), $files[NaturalPersonKYCFields::NOTARY_APPROVAL_OF_PHOTO_ID_KEY]);
        $this->assertEquals(base64_encode('residence_proof_data'), $files[NaturalPersonKYCFields::PHOTO_PROOF_RESIDENCE_KEY]);
        $this->assertEquals(base64_encode('income_proof_data'), $files[NaturalPersonKYCFields::PROOF_OF_INCOME_KEY]);
        $this->assertEquals(base64_encode('liveness_data'), $files[NaturalPersonKYCFields::PROOF_OF_LIVENESS_KEY]);
    }

    public function testNaturalPersonKYCFieldsEmptyFiles(): void
    {
        $naturalPerson = new NaturalPersonKYCFields();

        $files = $naturalPerson->files();

        $this->assertCount(0, $files);
    }

    public function testOrganizationKYCFieldsBasicFields(): void
    {
        $organization = new OrganizationKYCFields();
        $organization->name = 'Acme Corporation';
        $organization->VATNumber = 'VAT123456789';
        $organization->registrationNumber = 'REG987654321';
        $organization->registrationDate = '2010-01-15';
        $organization->registeredAddress = '789 Business Ave, Corporate City, CA 94105';

        $fields = $organization->fields();

        $this->assertCount(5, $fields);
        $this->assertEquals('Acme Corporation', $fields[OrganizationKYCFields::NAME_KEY]);
        $this->assertEquals('VAT123456789', $fields[OrganizationKYCFields::VAT_NUMBER_KEY]);
        $this->assertEquals('REG987654321', $fields[OrganizationKYCFields::REGISTRATION_NUMBER_KEY]);
        $this->assertEquals('2010-01-15', $fields[OrganizationKYCFields::REGISTRATION_DATE_KEY]);
        $this->assertEquals('789 Business Ave, Corporate City, CA 94105', $fields[OrganizationKYCFields::REGISTRATION_ADDRESS_KEY]);
    }

    public function testOrganizationKYCFieldsShareholderFields(): void
    {
        $organization = new OrganizationKYCFields();
        $organization->numberOfShareholders = 5;
        $organization->shareholderName = 'John Doe Holdings LLC';

        $fields = $organization->fields();

        $this->assertCount(2, $fields);
        $this->assertEquals(5, $fields[OrganizationKYCFields::NUMBER_OF_SHAREHOLDERS_KEY]);
        $this->assertEquals('John Doe Holdings LLC', $fields[OrganizationKYCFields::SHAREHOLDER_NAME_KEY]);
    }

    public function testOrganizationKYCFieldsAddressFields(): void
    {
        $organization = new OrganizationKYCFields();
        $organization->addressCountryCode = 'USA';
        $organization->stateOrProvince = 'Delaware';
        $organization->city = 'Wilmington';
        $organization->postalCode = '19801';

        $fields = $organization->fields();

        $this->assertCount(4, $fields);
        $this->assertEquals('USA', $fields[OrganizationKYCFields::ADDRESS_COUNTRY_CODE_KEY]);
        $this->assertEquals('Delaware', $fields[OrganizationKYCFields::STATE_OR_PROVINCE_KEY]);
        $this->assertEquals('Wilmington', $fields[OrganizationKYCFields::CITY_KEY]);
        $this->assertEquals('19801', $fields[OrganizationKYCFields::POSTAL_CODE_KEY]);
    }

    public function testOrganizationKYCFieldsContactFields(): void
    {
        $organization = new OrganizationKYCFields();
        $organization->directorName = 'Robert Smith';
        $organization->website = 'https://www.acmecorp.com';
        $organization->email = 'info@acmecorp.com';
        $organization->phone = '+14155559876';

        $fields = $organization->fields();

        $this->assertCount(4, $fields);
        $this->assertEquals('Robert Smith', $fields[OrganizationKYCFields::DIRECTOR_NAME_KEY]);
        $this->assertEquals('https://www.acmecorp.com', $fields[OrganizationKYCFields::WEBSITE_KEY]);
        $this->assertEquals('info@acmecorp.com', $fields[OrganizationKYCFields::EMAIL_KEY]);
        $this->assertEquals('+14155559876', $fields[OrganizationKYCFields::PHONE_KEY]);
    }

    public function testOrganizationKYCFieldsWithFinancialAccount(): void
    {
        $organization = new OrganizationKYCFields();
        $organization->name = 'Tech Innovations Inc';

        $financialAccount = new FinancialAccountKYCFields();
        $financialAccount->bankName = 'Wells Fargo';
        $financialAccount->bankAccountNumber = '9876543210';

        $organization->financialAccountKYCFields = $financialAccount;

        $fields = $organization->fields();

        $this->assertCount(3, $fields);
        $this->assertEquals('Tech Innovations Inc', $fields[OrganizationKYCFields::NAME_KEY]);
        $this->assertEquals('Wells Fargo', $fields[OrganizationKYCFields::KEY_PREFIX . FinancialAccountKYCFields::BANK_NAME_KEY]);
        $this->assertEquals('9876543210', $fields[OrganizationKYCFields::KEY_PREFIX . FinancialAccountKYCFields::BANK_ACCOUNT_NUMBER_KEY]);
    }

    public function testOrganizationKYCFieldsWithCard(): void
    {
        $organization = new OrganizationKYCFields();
        $organization->name = 'Retail Solutions LLC';

        $card = new CardKYCFields();
        $card->number = '5555555555554444';
        $card->network = 'Mastercard';

        $organization->cardKYCFields = $card;

        $fields = $organization->fields();

        $this->assertCount(3, $fields);
        $this->assertEquals('Retail Solutions LLC', $fields[OrganizationKYCFields::NAME_KEY]);
        $this->assertEquals('5555555555554444', $fields[CardKYCFields::NUMBER_KEY]);
        $this->assertEquals('Mastercard', $fields[CardKYCFields::NETWORK_KEY]);
    }

    public function testOrganizationKYCFieldsFiles(): void
    {
        $organization = new OrganizationKYCFields();
        $organization->photoIncorporationDoc = base64_encode('incorporation_doc_data');
        $organization->photoProofAddress = base64_encode('proof_address_data');

        $files = $organization->files();

        $this->assertCount(2, $files);
        $this->assertEquals(base64_encode('incorporation_doc_data'), $files[OrganizationKYCFields::PHOTO_INCORPORATION_DOC_KEY]);
        $this->assertEquals(base64_encode('proof_address_data'), $files[OrganizationKYCFields::PHOTO_PROOF_ADDRESS_KEY]);
    }

    public function testOrganizationKYCFieldsEmptyFiles(): void
    {
        $organization = new OrganizationKYCFields();

        $files = $organization->files();

        $this->assertCount(0, $files);
    }

    public function testCardKYCFieldsAllFields(): void
    {
        $card = new CardKYCFields();
        $card->number = '4111111111111111';
        $card->expirationDate = '26-06';
        $card->cvc = '123';
        $card->holderName = 'John Doe';
        $card->network = 'Visa';
        $card->postalCode = '94102';
        $card->countryCode = 'US';
        $card->stateOrProvince = 'CA';
        $card->city = 'San Francisco';
        $card->address = '123 Main St, San Francisco, CA 94102';
        $card->token = 'tok_1234567890abcdef';

        $fields = $card->fields();

        $this->assertCount(11, $fields);
        $this->assertEquals('4111111111111111', $fields[CardKYCFields::NUMBER_KEY]);
        $this->assertEquals('26-06', $fields[CardKYCFields::EXPIRATION_DATE_KEY]);
        $this->assertEquals('123', $fields[CardKYCFields::CVC_KEY]);
        $this->assertEquals('John Doe', $fields[CardKYCFields::HOLDER_NAME_KEY]);
        $this->assertEquals('Visa', $fields[CardKYCFields::NETWORK_KEY]);
        $this->assertEquals('94102', $fields[CardKYCFields::POSTAL_CODE_KEY]);
        $this->assertEquals('US', $fields[CardKYCFields::COUNTRY_CODE_KEY]);
        $this->assertEquals('CA', $fields[CardKYCFields::STATE_OR_PROVINCE_KEY]);
        $this->assertEquals('San Francisco', $fields[CardKYCFields::CITY_KEY]);
        $this->assertEquals('123 Main St, San Francisco, CA 94102', $fields[CardKYCFields::ADDRESS_KEY]);
        $this->assertEquals('tok_1234567890abcdef', $fields[CardKYCFields::TOKEN_KEY]);
    }

    public function testCardKYCFieldsTokenOnly(): void
    {
        $card = new CardKYCFields();
        $card->token = 'tok_stripe_test_token';

        $fields = $card->fields();

        $this->assertCount(1, $fields);
        $this->assertEquals('tok_stripe_test_token', $fields[CardKYCFields::TOKEN_KEY]);
    }

    public function testCardKYCFieldsEmpty(): void
    {
        $card = new CardKYCFields();

        $fields = $card->fields();

        $this->assertCount(0, $fields);
    }

    public function testFinancialAccountKYCFieldsBankFields(): void
    {
        $financialAccount = new FinancialAccountKYCFields();
        $financialAccount->bankName = 'Chase Bank';
        $financialAccount->bankAccountType = 'savings';
        $financialAccount->bankAccountNumber = '1111222233334444';
        $financialAccount->bankNumber = '021000021';
        $financialAccount->bankPhoneNumber = '+18005551234';
        $financialAccount->bankBranchNumber = 'BR001';

        $fields = $financialAccount->fields();

        $this->assertCount(6, $fields);
        $this->assertEquals('Chase Bank', $fields[FinancialAccountKYCFields::BANK_NAME_KEY]);
        $this->assertEquals('savings', $fields[FinancialAccountKYCFields::BANK_ACCOUNT_TYPE_KEY]);
        $this->assertEquals('1111222233334444', $fields[FinancialAccountKYCFields::BANK_ACCOUNT_NUMBER_KEY]);
        $this->assertEquals('021000021', $fields[FinancialAccountKYCFields::BANK_NUMBER_KEY]);
        $this->assertEquals('+18005551234', $fields[FinancialAccountKYCFields::BANK_PHONE_NUMBER_KEY]);
        $this->assertEquals('BR001', $fields[FinancialAccountKYCFields::BANK_BRANCH_NUMBER_KEY]);
    }

    public function testFinancialAccountKYCFieldsInternationalFields(): void
    {
        $financialAccount = new FinancialAccountKYCFields();
        $financialAccount->clabeNumber = '032180000118359719';
        $financialAccount->cbuNumber = '0170076540000001234567';
        $financialAccount->cbuAlias = 'my.alias.cbu';
        $financialAccount->externalTransferMemo = 'MEMO123456';

        $fields = $financialAccount->fields();

        $this->assertCount(4, $fields);
        $this->assertEquals('032180000118359719', $fields[FinancialAccountKYCFields::CLABE_NUMBER_KEY]);
        $this->assertEquals('0170076540000001234567', $fields[FinancialAccountKYCFields::CBU_NUMBER_KEY]);
        $this->assertEquals('my.alias.cbu', $fields[FinancialAccountKYCFields::CBU_ALIAS_KEY]);
        $this->assertEquals('MEMO123456', $fields[FinancialAccountKYCFields::EXTERNAL_TRANSFER_MEMO_KEY]);
    }

    public function testFinancialAccountKYCFieldsMobileMoneyFields(): void
    {
        $financialAccount = new FinancialAccountKYCFields();
        $financialAccount->mobileMoneyNumber = '+254712345678';
        $financialAccount->mobileMoneyProvider = 'M-Pesa';

        $fields = $financialAccount->fields();

        $this->assertCount(2, $fields);
        $this->assertEquals('+254712345678', $fields[FinancialAccountKYCFields::MOBILE_MONEY_NUMBER_KEY]);
        $this->assertEquals('M-Pesa', $fields[FinancialAccountKYCFields::MOBILE_MONEY_PROVIDER_KEY]);
    }

    public function testFinancialAccountKYCFieldsCryptoFields(): void
    {
        $financialAccount = new FinancialAccountKYCFields();
        $financialAccount->cryptoAddress = 'GDX7XNKZ3JXQFXV2QKXLQFXV2QKXLQFXV2QKXLQFXV2QKXLQFXV2QKX';
        $financialAccount->cryptoMemo = '1234567890';

        $fields = $financialAccount->fields();

        $this->assertCount(2, $fields);
        $this->assertEquals('GDX7XNKZ3JXQFXV2QKXLQFXV2QKXLQFXV2QKXLQFXV2QKXLQFXV2QKX', $fields[FinancialAccountKYCFields::CRYPTO_ADDRESS_KEY]);
        $this->assertEquals('1234567890', $fields[FinancialAccountKYCFields::CRYPTO_MEMO_KEY]);
    }

    public function testFinancialAccountKYCFieldsWithPrefix(): void
    {
        $financialAccount = new FinancialAccountKYCFields();
        $financialAccount->bankName = 'Test Bank';
        $financialAccount->bankAccountNumber = '9999888877776666';

        $fieldsWithPrefix = $financialAccount->fields('organization.');

        $this->assertCount(2, $fieldsWithPrefix);
        $this->assertEquals('Test Bank', $fieldsWithPrefix['organization.' . FinancialAccountKYCFields::BANK_NAME_KEY]);
        $this->assertEquals('9999888877776666', $fieldsWithPrefix['organization.' . FinancialAccountKYCFields::BANK_ACCOUNT_NUMBER_KEY]);
    }

    public function testFinancialAccountKYCFieldsEmpty(): void
    {
        $financialAccount = new FinancialAccountKYCFields();

        $fields = $financialAccount->fields();

        $this->assertCount(0, $fields);
    }

    public function testStandardKYCFieldsWithNaturalPerson(): void
    {
        $standardKYC = new StandardKYCFields();

        $naturalPerson = new NaturalPersonKYCFields();
        $naturalPerson->firstName = 'Alice';
        $naturalPerson->lastName = 'Williams';

        $standardKYC->naturalPersonKYCFields = $naturalPerson;

        $this->assertNotNull($standardKYC->naturalPersonKYCFields);
        $this->assertEquals('Alice', $standardKYC->naturalPersonKYCFields->firstName);
        $this->assertEquals('Williams', $standardKYC->naturalPersonKYCFields->lastName);
    }

    public function testStandardKYCFieldsWithOrganization(): void
    {
        $standardKYC = new StandardKYCFields();

        $organization = new OrganizationKYCFields();
        $organization->name = 'Global Enterprises Inc';
        $organization->VATNumber = 'VAT999888777';

        $standardKYC->organizationKYCFields = $organization;

        $this->assertNotNull($standardKYC->organizationKYCFields);
        $this->assertEquals('Global Enterprises Inc', $standardKYC->organizationKYCFields->name);
        $this->assertEquals('VAT999888777', $standardKYC->organizationKYCFields->VATNumber);
    }

    public function testStandardKYCFieldsWithBothPersonAndOrganization(): void
    {
        $standardKYC = new StandardKYCFields();

        $naturalPerson = new NaturalPersonKYCFields();
        $naturalPerson->firstName = 'Bob';
        $naturalPerson->lastName = 'Jones';

        $organization = new OrganizationKYCFields();
        $organization->name = 'Jones Consulting';

        $standardKYC->naturalPersonKYCFields = $naturalPerson;
        $standardKYC->organizationKYCFields = $organization;

        $this->assertNotNull($standardKYC->naturalPersonKYCFields);
        $this->assertNotNull($standardKYC->organizationKYCFields);
        $this->assertEquals('Bob', $standardKYC->naturalPersonKYCFields->firstName);
        $this->assertEquals('Jones Consulting', $standardKYC->organizationKYCFields->name);
    }

    public function testNaturalPersonKYCFieldsCompleteScenario(): void
    {
        $naturalPerson = new NaturalPersonKYCFields();

        $naturalPerson->firstName = 'Sarah';
        $naturalPerson->lastName = 'Martinez';
        $naturalPerson->additionalName = 'Elizabeth';
        $naturalPerson->emailAddress = 'sarah.martinez@example.com';
        $naturalPerson->mobileNumber = '+14155557890';
        $naturalPerson->addressCountryCode = 'USA';
        $naturalPerson->stateOrProvince = 'Texas';
        $naturalPerson->city = 'Austin';
        $naturalPerson->postalCode = '78701';
        $naturalPerson->birthDate = '1985-08-20';
        $naturalPerson->birthCountryCode = 'USA';
        $naturalPerson->taxId = '987-65-4321';
        $naturalPerson->taxIdName = 'SSN';

        $financialAccount = new FinancialAccountKYCFields();
        $financialAccount->bankName = 'Texas Trust Bank';
        $financialAccount->bankAccountNumber = '5555666677778888';
        $financialAccount->bankAccountType = 'checking';
        $financialAccount->bankNumber = '111000025';

        $naturalPerson->financialAccountKYCFields = $financialAccount;

        $naturalPerson->photoIdFront = base64_encode('id_front_image');
        $naturalPerson->photoIdBack = base64_encode('id_back_image');

        $fields = $naturalPerson->fields();
        $files = $naturalPerson->files();

        $this->assertGreaterThanOrEqual(17, count($fields));
        $this->assertEquals('Sarah', $fields[NaturalPersonKYCFields::FIRST_NAME_KEY]);
        $this->assertEquals('Martinez', $fields[NaturalPersonKYCFields::LAST_NAME_KEY]);
        $this->assertEquals('Texas Trust Bank', $fields[FinancialAccountKYCFields::BANK_NAME_KEY]);

        $this->assertCount(2, $files);
        $this->assertEquals(base64_encode('id_front_image'), $files[NaturalPersonKYCFields::PHOTO_ID_FRONT_KEY]);
        $this->assertEquals(base64_encode('id_back_image'), $files[NaturalPersonKYCFields::PHOTO_ID_BACK_KEY]);
    }

    public function testOrganizationKYCFieldsCompleteScenario(): void
    {
        $organization = new OrganizationKYCFields();

        $organization->name = 'TechCorp International Ltd';
        $organization->VATNumber = 'VAT123456789';
        $organization->registrationNumber = 'REG2010123456';
        $organization->registrationDate = '2010-05-15';
        $organization->addressCountryCode = 'GBR';
        $organization->city = 'London';
        $organization->postalCode = 'EC1A 1BB';
        $organization->directorName = 'James Anderson';
        $organization->website = 'https://www.techcorp.com';
        $organization->email = 'contact@techcorp.com';
        $organization->phone = '+442071234567';
        $organization->numberOfShareholders = 3;

        $financialAccount = new FinancialAccountKYCFields();
        $financialAccount->bankName = 'Barclays Bank';
        $financialAccount->bankAccountNumber = 'GB29NWBK60161331926819';

        $organization->financialAccountKYCFields = $financialAccount;

        $organization->photoIncorporationDoc = base64_encode('incorporation_certificate');

        $fields = $organization->fields();
        $files = $organization->files();

        $this->assertGreaterThanOrEqual(14, count($fields));
        $this->assertEquals('TechCorp International Ltd', $fields[OrganizationKYCFields::NAME_KEY]);
        $this->assertEquals('VAT123456789', $fields[OrganizationKYCFields::VAT_NUMBER_KEY]);
        $this->assertEquals('Barclays Bank', $fields[OrganizationKYCFields::KEY_PREFIX . FinancialAccountKYCFields::BANK_NAME_KEY]);

        $this->assertCount(1, $files);
        $this->assertEquals(base64_encode('incorporation_certificate'), $files[OrganizationKYCFields::PHOTO_INCORPORATION_DOC_KEY]);
    }
}
