<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\StandardKYCFields;

/**
 * KYC and AML fields for organizations (companies, legal entities).
 *
 * This class provides standardized fields for collecting Know Your Customer (KYC) and
 * Anti-Money Laundering (AML) information about organizations in compliance with
 * SEP-09 specification. It includes corporate identification, registration details,
 * contact information, and supporting documentation.
 *
 * Note: All organization field keys use dot notation with 'organization.' prefix
 * (e.g. 'organization.name', 'organization.address_country_code').
 *
 * PRIVACY AND SECURITY WARNING:
 * This class handles highly sensitive corporate and Personally Identifiable Information (PII).
 * Implementers MUST ensure:
 * - Transmission only over HTTPS/TLS connections
 * - Encryption at rest for all stored KYC data
 * - Compliance with applicable data protection regulations (GDPR, CCPA, etc.)
 * - Implementation of proper access controls and audit logging
 * - Secure data retention and deletion policies
 * - Corporate authorization management for data collection and processing
 * - Binary fields (photos, documents) must be base64 encoded for transmission
 *
 * @package Soneso\StellarSDK\SEP\StandardKYCFields
 * @see https://github.com/stellar/stellar-protocol/blob/v1.17.0/ecosystem/sep-0009.md SEP-09 v1.17.0 Specification
 */
class OrganizationKYCFields
{

    // field keys
    public const KEY_PREFIX = 'organization.';
    public const NAME_KEY = self::KEY_PREFIX . 'name';
    public const VAT_NUMBER_KEY = self::KEY_PREFIX . 'VAT_number';
    public const REGISTRATION_NUMBER_KEY = self::KEY_PREFIX . 'registration_number';
    public const REGISTRATION_DATE_KEY = self::KEY_PREFIX . 'registration_date';
    public const REGISTRATION_ADDRESS_KEY = self::KEY_PREFIX . 'registered_address';
    public const NUMBER_OF_SHAREHOLDERS_KEY = self::KEY_PREFIX . 'number_of_shareholders';
    public const SHAREHOLDER_NAME_KEY = self::KEY_PREFIX . 'shareholder_name';
    public const ADDRESS_COUNTRY_CODE_KEY = self::KEY_PREFIX . 'address_country_code';
    public const STATE_OR_PROVINCE_KEY = self::KEY_PREFIX . 'state_or_province';
    public const CITY_KEY = self::KEY_PREFIX . 'city';
    public const POSTAL_CODE_KEY = self::KEY_PREFIX . 'postal_code';
    public const DIRECTOR_NAME_KEY = self::KEY_PREFIX . 'director_name';
    public const WEBSITE_KEY = self::KEY_PREFIX . 'website';
    public const EMAIL_KEY = self::KEY_PREFIX . 'email';
    public const PHONE_KEY = self::KEY_PREFIX . 'phone';

    // files keys
    public const PHOTO_INCORPORATION_DOC_KEY =self::KEY_PREFIX . 'photo_incorporation_doc';
    public const PHOTO_PROOF_ADDRESS_KEY = self::KEY_PREFIX . 'photo_proof_address';

    /**
     * @var string|null Full organization name as on the incorporation papers
     */
    public ?string $name = null;

    /**
     * @var string|null Organization VAT number
     */
    public ?string $VATNumber = null;

    /**
     * @var string|null Organization registration number
     */
    public ?string $registrationNumber = null;

    /**
     * @var string|null Date the organization was registered (ISO 8601 format)
     */
    public ?string $registrationDate = null;

    /**
     * @var string|null Organization registered address
     */
    public ?string $registeredAddress = null;

    /**
     * @var int|null Number of shareholders in the organization
     */
    public ?int $numberOfShareholders = null;

    /**
     * @var string|null Shareholder name (can be an organization or a person, should be queried recursively
     * up to the ultimate beneficial owners with KYC information for natural persons)
     */
    public ?string $shareholderName = null;

    /**
     * @var string|null Image of incorporation documents (base64 encoded)
     */
    public ?string $photoIncorporationDoc = null;

    /**
     * @var string|null Image of a utility bill, bank statement with the organization's name and address (base64 encoded)
     */
    public ?string $photoProofAddress = null;

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
     * @var string|null Postal or other code identifying organization's locale
     */
    public ?string $postalCode = null;

    /**
     * @var string|null Organization registered managing director
     */
    public ?string $directorName = null;

    /**
     * @var string|null Organization website URL
     */
    public ?string $website = null;

    /**
     * @var string|null Organization contact email (RFC 5322 format)
     */
    public ?string $email = null;

    /**
     * @var string|null Organization contact phone number (E.164 format)
     */
    public ?string $phone = null;

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
     * This method collects all populated organization KYC fields including
     * nested financial account and card fields, returning them as key-value pairs
     * with 'organization.' prefix suitable for submission to SEP-09 compliant services.
     *
     * @return array<array-key, mixed> Associative array of field keys to values
     */
    public function fields() : array
    {
        /**
         * @var array<array-key, mixed> $fields
         */
        $fields = array();
        if ($this->name) {
            $fields += [self::NAME_KEY => $this->name];
        }
        if ($this->VATNumber) {
            $fields += [self::VAT_NUMBER_KEY => $this->VATNumber];
        }
        if ($this->registrationNumber) {
            $fields += [self::REGISTRATION_NUMBER_KEY => $this->registrationNumber];
        }
        if ($this->registrationDate) {
            $fields += [self::REGISTRATION_DATE_KEY => $this->registrationDate];
        }
        if ($this->registeredAddress) {
            $fields += [self::REGISTRATION_ADDRESS_KEY => $this->registeredAddress];
        }
        if ($this->numberOfShareholders) {
            $fields += [self::NUMBER_OF_SHAREHOLDERS_KEY => $this->numberOfShareholders];
        }
        if ($this->shareholderName) {
            $fields += [self::SHAREHOLDER_NAME_KEY => $this->shareholderName];
        }
        if ($this->addressCountryCode) {
            $fields += [self::ADDRESS_COUNTRY_CODE_KEY => $this->addressCountryCode];
        }
        if ($this->stateOrProvince) {
            $fields += [self::STATE_OR_PROVINCE_KEY => $this->stateOrProvince];
        }
        if ($this->city) {
            $fields += [self::CITY_KEY => $this->city];
        }
        if ($this->postalCode) {
            $fields += [self::POSTAL_CODE_KEY => $this->postalCode];
        }
        if ($this->directorName) {
            $fields += [self::DIRECTOR_NAME_KEY => $this->directorName];
        }
        if ($this->website) {
            $fields += [self::WEBSITE_KEY => $this->website];
        }
        if ($this->email) {
            $fields += [self::EMAIL_KEY => $this->email];
        }
        if ($this->phone) {
            $fields += [self::PHONE_KEY => $this->phone];
        }
        if ($this->financialAccountKYCFields !== null) {
            $financialFields = $this->financialAccountKYCFields->fields(self::KEY_PREFIX);
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
     * that are base64 encoded, returning them as key-value pairs with 'organization.'
     * prefix for file upload to SEP-09 compliant services.
     *
     * @return array<array-key, string> Associative array of file field keys to base64 encoded values
     */
    public function files() : array
    {
        /**
         * @var array<array-key, string> $files
         */
        $files = array();

        if ($this->photoIncorporationDoc) {
            $files += [ self::PHOTO_INCORPORATION_DOC_KEY => $this->photoIncorporationDoc ];
        }

        if ($this->photoProofAddress) {
            $files += [ self::PHOTO_PROOF_ADDRESS_KEY => $this->photoProofAddress ];
        }

        return $files;
    }
}