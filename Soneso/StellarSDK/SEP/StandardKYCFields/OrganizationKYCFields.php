<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\StandardKYCFields;

class OrganizationKYCFields
{
    /// Full organization name as on the incorporation papers
    public ?string $name = null;

    /// Organization VAT number
    public ?string $VATNumber = null;

    /// Organization registration number
    public ?string $registrationNumber = null;

    /// Organization registered address
    public ?string $registeredAddress = null;

    /// Organization shareholder number
    public ?int $numberOfShareholders = null;

    /// Can be an organization or a person and should be queried recursively up to the ultimate beneficial owners (with KYC information for natural persons such as above)
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

    public function fields() : array
    {
        $fields = array();
        if ($this->name) {
            $fields += ["organization.name" => $this->name];
        }
        if ($this->VATNumber) {
            $fields += ["organization.VAT_number" => $this->VATNumber];
        }
        if ($this->registrationNumber) {
            $fields += ["organization.registration_number" => $this->registrationNumber];
        }
        if ($this->registeredAddress) {
            $fields += ["organization.registered_address" => $this->registeredAddress];
        }
        if ($this->numberOfShareholders) {
            $fields += ["organization.number_of_shareholders" => $this->numberOfShareholders];
        }
        if ($this->shareholderName) {
            $fields += ["organization.shareholder_name" => $this->shareholderName];
        }
        if ($this->addressCountryCode) {
            $fields += ["organization.address_country_code" => $this->addressCountryCode];
        }
        if ($this->stateOrProvince) {
            $fields += ["organization.state_or_province" => $this->stateOrProvince];
        }
        if ($this->city) {
            $fields += ["organization.city" => $this->city];
        }
        if ($this->postalCode) {
            $fields += ["organization.postal_code" => $this->postalCode];
        }
        if ($this->directorName) {
            $fields += ["organization.director_name" => $this->directorName];
        }
        if ($this->website) {
            $fields += ["organization.website" => $this->website];
        }
        if ($this->email) {
            $fields += ["organization.email" => $this->email];
        }
        if ($this->phone) {
            $fields += ["organization.phone" => $this->phone];
        }
        return $fields;
    }

    public function files() : array
    {
        $files = array();
        if ($this->photoIncorporationDoc) {
            $files += [ "photo_incorporation_doc" => $this->photoIncorporationDoc ];
        }
        if ($this->photoProofAddress) {
            $files += [ "photo_proof_address" => $this->photoProofAddress ];
        }
        return $files;
    }
}