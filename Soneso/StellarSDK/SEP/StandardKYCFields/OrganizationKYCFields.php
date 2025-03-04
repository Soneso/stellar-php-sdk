<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\StandardKYCFields;

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

    /// Full organization name as on the incorporation papers
    public ?string $name = null;

    /// Organization VAT number
    public ?string $VATNumber = null;

    /// Organization registration number
    public ?string $registrationNumber = null;

    /// Date the organization was registered
    public ?string $registrationDate = null;

    /// Organization registered address
    public ?string $registeredAddress = null;

    /// Organization shareholder number
    public ?int $numberOfShareholders = null;

    /// Can be an organization or a person and should be queried recursively up to the ultimate beneficial
    ///  owners (with KYC information for natural persons such as above)
    public ?string $shareholderName = null;

    /// Image of incorporation documents (bytes)
    public ?string $photoIncorporationDoc = null;

    /// Image of a utility bill, bank statement with the organization's name and address (bytes)
    public ?string $photoProofAddress = null;

    /// Country code for current address
    public ?string $addressCountryCode = null;

    /// Name of state/province/region/prefecture
    public ?string $stateOrProvince = null;

    /// name of city/town
    public ?string $city = null;

    /// Postal or other code identifying organization's locale
    public ?string $postalCode = null;

    /// Organization registered managing director
    public ?string $directorName = null;

    /// Organization website
    public ?string $website = null;

    /// Organization contact email
    public ?string $email = null;

    ///	Organization contact phone
    public ?string $phone = null;

    /// Financial Account Fields
    public ?FinancialAccountKYCFields $financialAccountKYCFields = null;

    /// Card Fields
    public ?CardKYCFields $cardKYCFields = null;

    /**
     * @return array<array-key, mixed>
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
     * @return array<array-key, string> the fields containing the files to be uploaded.
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