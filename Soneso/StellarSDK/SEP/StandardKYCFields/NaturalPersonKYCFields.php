<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\StandardKYCFields;

use DateTime;
use DateTimeInterface;

/**
 * KYC and AML fields for natural persons (individual customers).
 *
 * This class provides standardized fields for collecting Know Your Customer (KYC) and
 * Anti-Money Laundering (AML) information about individual persons in compliance with
 * SEP-09 specification. It includes personal identification, contact information,
 * address details, tax information, and supporting documentation.
 *
 * PRIVACY AND SECURITY WARNING:
 * This class handles highly sensitive Personally Identifiable Information (PII) and KYC data.
 * Implementers MUST ensure:
 * - Transmission only over HTTPS/TLS connections
 * - Encryption at rest for all stored KYC data
 * - Compliance with applicable data protection regulations (GDPR, CCPA, etc.)
 * - Implementation of proper access controls and audit logging
 * - Secure data retention and deletion policies
 * - Customer consent management for data collection and processing
 * - Binary fields (photos, documents) must be base64 encoded for transmission
 *
 * @package Soneso\StellarSDK\SEP\StandardKYCFields
 * @see https://github.com/stellar/stellar-protocol/blob/v1.17.0/ecosystem/sep-0009.md SEP-09 v1.17.0 Specification
 */
class NaturalPersonKYCFields
{
    // field keys
    public const LAST_NAME_KEY = 'last_name';
    public const FIRST_NAME_KEY = 'first_name';
    public const ADDITIONAL_NAME_KEY = 'additional_name';
    public const ADDRESS_COUNTRY_CODE_KEY = 'address_country_code';
    public const STATE_OR_PROVINCE_KEY = 'state_or_province';
    public const CITY_KEY = 'city';
    public const POSTAL_CODE_KEY = 'postal_code';
    public const ADDRESS_KEY = 'address';
    public const MOBILE_NUMBER_KEY = 'mobile_number';
    public const MOBILE_NUMBER_FORMAT_KEY = 'mobile_number_format';
    public const EMAIL_ADDRESS_KEY = 'email_address';
    public const BIRTH_DATE_KEY = 'birth_date';
    public const BIRTH_PLACE_KEY = 'birth_place';
    public const BIRTH_COUNTRY_CODE_KEY = 'birth_country_code';
    public const TAX_ID_KEY = 'tax_id';
    public const TAX_ID_NAME_KEY = 'tax_id_name';
    public const OCCUPATION_KEY = 'occupation';
    public const EMPLOYER_NAME_KEY = 'employer_name';
    public const EMPLOYER_ADDRESS_KEY = 'employer_address';
    public const LANGUAGE_CODE_KEY = 'language_code';
    public const ID_TYPE_KEY = 'id_type';
    public const ID_COUNTRY_CODE_KEY = 'id_country_code';
    public const ID_ISSUE_DATE_KEY = 'id_issue_date';
    public const ID_EXPIRATION_DATE_KEY = 'id_expiration_date';
    public const ID_NUMBER_KEY = 'id_number';
    public const IP_ADDRESS_KEY = 'ip_address';
    public const SEX_KEY = 'sex';
    public const REFERRAL_ID_KEY = 'referral_id';

    // files keys
    public const PHOTO_ID_FRONT_KEY = 'photo_id_front';
    public const PHOTO_ID_BACK_KEY = 'photo_id_back';
    public const NOTARY_APPROVAL_OF_PHOTO_ID_KEY = 'notary_approval_of_photo_id';
    public const PHOTO_PROOF_RESIDENCE_KEY = 'photo_proof_residence';
    public const PROOF_OF_INCOME_KEY = 'proof_of_income';
    public const PROOF_OF_LIVENESS_KEY = 'proof_of_liveness';

    /**
     * @var string|null Family or last name
     */
    public ?string $lastName = null;

    /**
     * @var string|null Given or first name
     */
    public ?string $firstName = null;

    /**
     * @var string|null Middle name or other additional name
     */
    public ?string $additionalName = null;

    /**
     * @var string|null Country code for current address (ISO 3166-1 alpha-3)
     */
    public ?string $addressCountryCode = null;

    /**
     * @var string|null Name of state/province/region/prefecture
     */
    public ?string $stateOrProvince = null;

    /**
     * @var string|null Name of city/town
     */
    public ?string $city = null;

    /**
     * @var string|null Postal or other code identifying user's locale
     */
    public ?string $postalCode = null;

    /**
     * @var string|null Entire address (country, state, postal code, street address, etc.) as a multi-line string
     */
    public ?string $address = null;

    /**
     * @var string|null Mobile phone number with country code in E.164 format
     */
    public ?string $mobileNumber = null;

    /**
     * @var string|null Expected format of the mobile_number field (e.g. E.164, hash). Defaults to E.164 if not specified.
     */
    public ?string $mobileNumberFormat = null;

    /**
     * @var string|null Email address (RFC 5322 format)
     */
    public ?string $emailAddress = null;

    /**
     * @var string|null Date of birth in ISO 8601 format (e.g. 1976-07-04)
     */
    public ?string $birthDate = null;

    /**
     * @var string|null Place of birth (city, state, country; as on passport)
     */
    public ?string $birthPlace = null;

    /**
     * @var string|null Country code of birth (ISO 3166-1 alpha-3)
     */
    public ?string $birthCountryCode = null;

    /**
     * @var string|null Tax identifier of user in their country (e.g. social security number in US)
     */
    public ?string $taxId = null;

    /**
     * @var string|null Name of the tax ID (e.g. SSN or ITIN in the US)
     */
    public ?string $taxIdName = null;

    /**
     * @var int|null Occupation ISCO08 code
     */
    public ?int $occupation = null;

    /**
     * @var string|null Name of employer
     */
    public ?string $employerName = null;

    /**
     * @var string|null Address of employer
     */
    public ?string $employerAddress = null;

    /**
     * @var string|null Primary language (ISO 639-1 code)
     */
    public ?string $languageCode = null;

    /**
     * @var string|null Type of photo ID (e.g. passport, drivers_license, id_card)
     */
    public ?string $idType = null;

    /**
     * @var string|null Country issuing passport or photo ID (ISO 3166-1 alpha-3)
     */
    public ?string $idCountryCode = null;

    /**
     * @var DateTime|null ID issue date (ISO 8601 format)
     */
    public ?DateTime $idIssueDate = null;

    /**
     * @var DateTime|null ID expiration date (ISO 8601 format)
     */
    public ?DateTime $idExpirationDate = null;

    /**
     * @var string|null Passport or ID number
     */
    public ?string $idNumber = null;

    /**
     * @var string|null Image of front of user's photo ID or passport (base64 encoded)
     */
    public ?string $photoIdFront = null;

    /**
     * @var string|null Image of back of user's photo ID or passport (base64 encoded)
     */
    public ?string $photoIdBack = null;

    /**
     * @var string|null Image of notary's approval of photo ID or passport (base64 encoded)
     */
    public ?string $notaryApprovalOfPhotoId = null;

    /**
     * @var string|null IP address of customer's computer
     */
    public ?string $ipAddress = null;

    /**
     * @var string|null Image of a utility bill, bank statement or similar with the user's name and address (base64 encoded)
     */
    public ?string $photoProofResidence = null;

    /**
     * @var string|null Sex (e.g. male, female, other)
     */
    public ?string $sex = null;

    /**
     * @var string|null Image of user's proof of income document (base64 encoded)
     */
    public ?string $proofOfIncome = null;

    /**
     * @var string|null Video or image file of user as a liveness proof (base64 encoded)
     */
    public ?string $proofOfLiveness = null;

    /**
     * @var string|null User's origin (such as an id in another application) or a referral code
     */
    public ?string $referralId = null;

    /**
     * @var FinancialAccountKYCFields|null Financial account fields (bank account, crypto address, etc.)
     */
    public ?FinancialAccountKYCFields $financialAccountKYCFields = null;

    /**
     * @var CardKYCFields|null Card fields (credit/debit card information)
     */
    public ?CardKYCFields $cardKYCFields = null;

    /**
     * Returns all non-null KYC fields as an associative array.
     *
     * This method collects all populated natural person KYC fields including
     * nested financial account and card fields, returning them as key-value pairs
     * suitable for submission to SEP-09 compliant services.
     *
     * @return array<array-key, mixed> Associative array of field keys to values
     */
    public function fields() : array {
        /**
         * @var array<array-key, mixed> $fields
         */
        $fields = array();
        if ($this->lastName) {
            $fields += [ self::LAST_NAME_KEY => $this->lastName ];
        }
        if ($this->firstName) {
            $fields += [ self::FIRST_NAME_KEY => $this->firstName ];
        }
        if ($this->additionalName) {
            $fields += [ self::ADDITIONAL_NAME_KEY => $this->additionalName ];
        }
        if ($this->addressCountryCode) {
            $fields += [ self::ADDRESS_COUNTRY_CODE_KEY => $this->addressCountryCode ];
        }
        if ($this->stateOrProvince) {
            $fields += [ self::STATE_OR_PROVINCE_KEY => $this->stateOrProvince ];
        }
        if ($this->city) {
            $fields += [ self::CITY_KEY => $this->city ];
        }
        if ($this->postalCode) {
            $fields += [ self::POSTAL_CODE_KEY => $this->postalCode ];
        }
        if ($this->address) {
            $fields += [ self::ADDRESS_KEY => $this->address ];
        }
        if ($this->mobileNumber) {
            $fields += [ self::MOBILE_NUMBER_KEY => $this->mobileNumber ];
        }
        if ($this->mobileNumberFormat) {
            $fields += [ self::MOBILE_NUMBER_FORMAT_KEY => $this->mobileNumberFormat ];
        }
        if ($this->emailAddress) {
            $fields += [ self::EMAIL_ADDRESS_KEY => $this->emailAddress ];
        }
        if ($this->birthDate) {
            $fields += [ self::BIRTH_DATE_KEY => $this->birthDate ];
        }
        if ($this->birthPlace) {
            $fields += [ self::BIRTH_PLACE_KEY => $this->birthPlace ];
        }
        if ($this->birthCountryCode) {
            $fields += [ self::BIRTH_COUNTRY_CODE_KEY => $this->birthCountryCode ];
        }
        if ($this->taxId) {
            $fields += [ self::TAX_ID_KEY => $this->taxId ];
        }
        if ($this->taxIdName) {
            $fields += [ self::TAX_ID_NAME_KEY => $this->taxIdName ];
        }
        if ($this->occupation) {
            $fields += [ self::OCCUPATION_KEY => strval($this->occupation) ];
        }
        if ($this->employerName) {
            $fields += [ self::EMPLOYER_NAME_KEY => $this->employerName ];
        }
        if ($this->employerAddress) {
            $fields += [ self::EMPLOYER_ADDRESS_KEY => $this->employerAddress ];
        }
        if ($this->languageCode) {
            $fields += [ self::LANGUAGE_CODE_KEY => $this->languageCode ];
        }
        if ($this->idType) {
            $fields += [ self::ID_TYPE_KEY => $this->idType ];
        }
        if ($this->idCountryCode) {
            $fields += [ self::ID_COUNTRY_CODE_KEY => $this->idCountryCode ];
        }
        if ($this->idIssueDate) {
            $fields += [ self::ID_ISSUE_DATE_KEY => $this->idIssueDate->format(DateTimeInterface::ATOM) ];
        }
        if ($this->idExpirationDate) {
            $fields += [ self::ID_EXPIRATION_DATE_KEY => $this->idExpirationDate->format(DateTimeInterface::ATOM) ];
        }
        if ($this->idNumber) {
            $fields += [ self::ID_NUMBER_KEY => $this->idNumber ];
        }
        if ($this->ipAddress) {
            $fields += [ self::IP_ADDRESS_KEY => $this->ipAddress ];
        }
        if ($this->sex) {
            $fields += [ self::SEX_KEY => $this->sex];
        }
        if ($this->referralId) {
            $fields += [ self::REFERRAL_ID_KEY => $this->referralId ];
        }

        if ($this->financialAccountKYCFields !== null) {
            $financialFields = $this->financialAccountKYCFields->fields();
            $fields = array_merge($fields, $financialFields);
        }

        if ($this->cardKYCFields !== null) {
            $cardFields = $this->cardKYCFields->fields();
            $fields = array_merge($fields, $cardFields);
        }

        return $fields;
    }

    /**
     * Returns all non-null binary file fields as an associative array.
     *
     * This method collects all populated binary file fields (photos, documents)
     * that are base64 encoded, returning them as key-value pairs for file upload
     * to SEP-09 compliant services.
     *
     * @return array<array-key, string> Associative array of file field keys to base64 encoded values
     */
    public function files() : array
    {
        $files = array();
        if ($this->photoIdFront) {
            $files += [ self::PHOTO_ID_FRONT_KEY => $this->photoIdFront ];
        }
        if ($this->photoIdBack) {
            $files += [ self::PHOTO_ID_BACK_KEY => $this->photoIdBack ];
        }
        if ($this->notaryApprovalOfPhotoId) {
            $files += [ self::NOTARY_APPROVAL_OF_PHOTO_ID_KEY => $this->notaryApprovalOfPhotoId ];
        }
        if ($this->photoProofResidence) {
            $files += [ self::PHOTO_PROOF_RESIDENCE_KEY => $this->photoProofResidence ];
        }
        if ($this->proofOfIncome) {
            $files += [ self::PROOF_OF_INCOME_KEY => $this->proofOfIncome ];
        }
        if ($this->proofOfLiveness) {
            $files += [ self::PROOF_OF_LIVENESS_KEY => $this->proofOfLiveness ];
        }
        return $files;
    }
}
