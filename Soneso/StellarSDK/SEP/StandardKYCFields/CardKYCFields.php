<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\StandardKYCFields;


class CardKYCFields
{
    // field keys
    public const NUMBER_KEY = 'card.number';
    public const EXPIRATION_DATE_KEY = 'card.expiration_date';
    public const CVC_KEY = 'card.cvc';
    public const HOLDER_NAME_KEY = 'card.holder_name';
    public const NETWORK_KEY = 'card.network';
    public const POSTAL_CODE_KEY = 'card.postal_code';
    public const COUNTRY_CODE_KEY = 'card.country_code';
    public const STATE_OR_PROVINCE_KEY = 'card.state_or_province';
    public const CITY_KEY = 'card.city';
    public const ADDRESS_KEY = 'card.address';
    public const TOKEN_KEY = 'card.token';

    /// Card number
    public ?string $number = null;

    /// Expiration month and year in YY-MM format (e.g. 29-11, November 2029)
    public ?string $expirationDate = null;

    /// CVC number (Digits on the back of the card)
    public ?string $cvc = null;

    /// Name of the card holder
    public ?string $holderName = null;

    /// Brand of the card/network it operates within (e.g. Visa, Mastercard, AmEx, etc.)
    public ?string $network = null;

    /// Billing address postal code
    public ?string $postalCode = null;

    /// Billing address country code in ISO 3166-1 alpha-2 code (e.g. US)
    public ?string $countryCode = null;

    /// Name of state/province/region/prefecture is ISO 3166-2 format
    public ?string $stateOrProvince = null;

    /// Name of city/town
    public ?string $city = null;

    /// Entire address (country, state, postal code, street address, etc...) as a multi-line string
    public ?string $address = null;

    /// Token representation of the card in some external payment system (e.g. Stripe)
    public ?string $token = null;

    /**
     * @return array<array-key, mixed>
     */
    public function fields() : array {
        /**
         * @var array<array-key, mixed> $fields
         */
        $fields = array();
        if ($this->number) {
            $fields += [ self::NUMBER_KEY => $this->number ];
        }
        if ($this->expirationDate) {
            $fields += [ self::EXPIRATION_DATE_KEY => $this->expirationDate ];
        }
        if ($this->cvc) {
            $fields += [ self::CVC_KEY => $this->cvc ];
        }
        if ($this->holderName) {
            $fields += [ self::HOLDER_NAME_KEY => $this->holderName ];
        }
        if ($this->network) {
            $fields += [ self::NETWORK_KEY => $this->network ];
        }
        if ($this->postalCode) {
            $fields += [ self::POSTAL_CODE_KEY => $this->postalCode ];
        }
        if ($this->countryCode) {
            $fields += [ self::COUNTRY_CODE_KEY => $this->countryCode ];
        }
        if ($this->stateOrProvince) {
            $fields += [ self::STATE_OR_PROVINCE_KEY=> $this->stateOrProvince ];
        }
        if ($this->city) {
            $fields += [ self::CITY_KEY=> $this->city ];
        }
        if ($this->address) {
            $fields += [ self::ADDRESS_KEY => $this->address ];
        }
        if ($this->token) {
            $fields += [ self::TOKEN_KEY => $this->token ];
        }
        return $fields;
    }
}