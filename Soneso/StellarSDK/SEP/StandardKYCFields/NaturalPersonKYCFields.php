<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\StandardKYCFields;

use DateTime;
use DateTimeInterface;

class NaturalPersonKYCFields
{
    /// Family or last name
    public ?string $lastName = null;

    /// Given or first name
    public ?string $firstName = null;

    /// Middle name or other additional name
    public ?string $additionalName = null;

    /// country code for current address
    public ?string $addressCountryCode = null;

    /// name of state/province/region/prefecture
    public ?string $stateOrProvince = null;

    /// name of city/town
    public ?string $city = null;

    /// Postal or other code identifying user's locale
    public ?string $postalCode = null;

    /// Entire address (country, state, postal code, street address, etc...) as a multi-line string
    public ?string $address = null;

    /// Mobile phone number with country code, in E.164 format
    public ?string $mobileNumber = null;

    /// Email address
    public ?string $emailAddress = null;

    /// Date of birth, e.g. 1976-07-04
    public ?string $birthDate = null;

    /// Place of birth (city, state, country; as on passport)
    public ?string $birthPlace = null;

    /// ISO Code of country of birth ISO 3166-1 alpha-3
    public ?string $birthCountryCode = null;

    /// Number identifying bank account
    public ?string $bankAccountNumber = null;

    /// Number identifying bank in national banking system (routing number in US)
    public ?string $bankNumber = null;

    /// Phone number with country code for bank
    public ?string $bankPhoneNumber = null;

    /// Number identifying bank branch
    public ?string $bankBranchNumber = null;

    /// Tax identifier of user in their country (social security number in US)
    public ?string $taxId = null;

    /// Name of the tax ID (SSN or ITIN in the US)
    public ?string $taxIdName = null;

    /// Occupation ISCO code.
    public ?int $occupation = null;

    /// Name of employer.
    public ?string $employerName = null;

    /// Address of employer
    public ?string $employerAddress = null;

    /// primary language ISO 639-1
    public ?string $languageCode = null;

    /// passport, drivers_license, id_card, etc...
    public ?string $idType = null;

    /// country issuing passport or photo ID as ISO 3166-1 alpha-3 cod
    public ?string $idCountryCode = null;

    /// ID issue date
    public ?DateTime $idIssueDate = null;

    /// ID expiration date
    public ?DateTime $idExpirationDate = null;

    /// Passport or ID number
    public ?string $idNumber = null;

    /// Image of front of user's photo ID or passport (bytes)
    public ?string $photoIdFront = null;

    /// Image of back of user's photo ID or passport (bytes)
    public ?string $photoIdBack = null;

    /// Image of notary's approval of photo ID or passport (bytes)
    public ?string $notaryApprovalOfPhotoId = null;

    /// IP address of customer's computer
    public ?string $ipAddress = null;

    /// Image of a utility bill, bank statement or similar with the user's name and address (bytes)
    public ?string $photoProofResidence = null;

    /// male, female, or other
    public ?string $sex = null;

    public function fields() : array {
        $fields = array();
        if ($this->lastName) {
            $fields += [ "last_name" => $this->lastName ];
        }
        if ($this->firstName) {
            $fields += [ "first_name" => $this->firstName ];
        }
        if ($this->additionalName) {
            $fields += [ "additional_name" => $this->additionalName ];
        }
        if ($this->addressCountryCode) {
            $fields += [ "address_country_code" => $this->addressCountryCode ];
        }
        if ($this->stateOrProvince) {
            $fields += [ "state_or_province" => $this->stateOrProvince ];
        }
        if ($this->city) {
            $fields += [ "city" => $this->city ];
        }
        if ($this->postalCode) {
            $fields += [ "postal_code" => $this->postalCode ];
        }
        if ($this->address) {
            $fields += [ "address" => $this->address ];
        }
        if ($this->mobileNumber) {
            $fields += [ "mobile_number" => $this->mobileNumber ];
        }
        if ($this->emailAddress) {
            $fields += [ "email_address" => $this->emailAddress ];
        }
        if ($this->birthDate) {
            $fields += [ "birth_date" => $this->birthDate ];
        }
        if ($this->birthPlace) {
            $fields += [ "birth_place" => $this->birthPlace ];
        }
        if ($this->birthCountryCode) {
            $fields += [ "birth_country_code" => $this->birthCountryCode ];
        }
        if ($this->bankAccountNumber) {
            $fields += [ "bank_account_number" => $this->bankAccountNumber ];
        }
        if ($this->bankNumber) {
            $fields += [ "bank_number" => $this->bankNumber ];
        }
        if ($this->bankPhoneNumber) {
            $fields += [ "bank_phone_number" => $this->bankPhoneNumber ];
        }
        if ($this->bankBranchNumber) {
            $fields += [ "bank_branch_number" => $this->bankBranchNumber ];
        }
        if ($this->taxId) {
            $fields += [ "tax_id" => $this->taxId ];
        }
        if ($this->taxIdName) {
            $fields += [ "tax_id_name" => $this->taxIdName ];
        }
        if ($this->occupation) {
            $fields += [ "occupation" => strval($this->occupation) ];
        }
        if ($this->employerName) {
            $fields += [ "employer_name" => $this->employerName ];
        }
        if ($this->employerAddress) {
            $fields += [ "employer_address" => $this->employerAddress ];
        }
        if ($this->languageCode) {
            $fields += [ "language_code" => $this->languageCode ];
        }
        if ($this->idType) {
            $fields += [ "id_type" => $this->idType ];
        }
        if ($this->idCountryCode) {
            $fields += [ "id_country_code" => $this->idCountryCode ];
        }
        if ($this->idIssueDate) {
            $fields += [ "id_issue_date" => $this->idIssueDate->format(DateTimeInterface::ATOM) ];
        }
        if ($this->idExpirationDate) {
            $fields += [ "id_expiration_date" => $this->idExpirationDate->format(DateTimeInterface::ATOM) ];
        }
        if ($this->idNumber) {
            $fields += [ "id_number" => $this->idNumber ];
        }
        if ($this->ipAddress) {
            $fields += [ "ip_address" => $this->ipAddress ];
        }
        if ($this->sex) {
            $fields += [ "sex" => $this->sex ];
        }
        return $fields;
    }

    public function files() : array
    {
        $files = array();
        if ($this->photoIdFront) {
            $files += [ "photo_id_front" => $this->photoIdFront ];
        }
        if ($this->photoIdBack) {
            $files += [ "photo_id_back" => $this->photoIdBack ];
        }
        if ($this->notaryApprovalOfPhotoId) {
            $files += [ "notary_approval_of_photo_id" => $this->notaryApprovalOfPhotoId ];
        }
        if ($this->photoProofResidence) {
            $files += [ "photo_proof_residence" => $this->photoProofResidence ];
        }
        return $files;
    }
}
