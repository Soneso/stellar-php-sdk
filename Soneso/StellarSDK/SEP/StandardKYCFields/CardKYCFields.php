<?php declare(strict_types=1);

// Copyright 2025 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\StandardKYCFields;

/**
 * KYC fields for payment cards (credit/debit card information).
 *
 * This class provides standardized fields for collecting payment card information
 * required for KYC and payment processing according to SEP-09 specification. It includes
 * card details, billing address, and tokenization support for external payment systems.
 *
 * PRIVACY AND SECURITY WARNING:
 * This class handles highly sensitive payment card data subject to PCI-DSS requirements.
 * Implementers MUST ensure:
 * - Transmission only over HTTPS/TLS connections
 * - PCI-DSS Level 1 compliance for card data handling
 * - Never store unencrypted card numbers, CVCs, or full magnetic stripe data
 * - Prefer tokenization over direct card number storage
 * - Implement proper access controls and audit logging
 * - Secure data retention and deletion policies
 * - Compliance with applicable data protection regulations (GDPR, CCPA, etc.)
 * - Customer consent management for data collection and processing
 *
 * @package Soneso\StellarSDK\SEP\StandardKYCFields
 * @see https://github.com/stellar/stellar-protocol/blob/v1.17.0/ecosystem/sep-0009.md SEP-09 v1.17.0 Specification
 */
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

    /**
     * @var string|null Card number (PCI-DSS: use tokenization where possible)
     */
    public ?string $number = null;

    /**
     * @var string|null Expiration month and year in YY-MM format (e.g. 29-11 for November 2029)
     */
    public ?string $expirationDate = null;

    /**
     * @var string|null CVC number (security code on the back of the card)
     */
    public ?string $cvc = null;

    /**
     * @var string|null Name of the card holder
     */
    public ?string $holderName = null;

    /**
     * @var string|null Brand of the card/network (e.g. Visa, Mastercard, AmEx)
     */
    public ?string $network = null;

    /**
     * @var string|null Billing address postal code
     */
    public ?string $postalCode = null;

    /**
     * @var string|null Billing address country code (ISO 3166-1 alpha-2, e.g. US)
     */
    public ?string $countryCode = null;

    /**
     * @var string|null Name of state/province/region/prefecture (ISO 3166-2 format)
     */
    public ?string $stateOrProvince = null;

    /**
     * @var string|null Name of city/town
     */
    public ?string $city = null;

    /**
     * @var string|null Entire billing address (country, state, postal code, street address, etc.) as a multi-line string
     */
    public ?string $address = null;

    /**
     * @var string|null Token representation of the card in an external payment system (e.g. Stripe)
     */
    public ?string $token = null;

    /**
     * Returns all non-null card fields as an associative array.
     *
     * This method collects all populated payment card fields, returning them as
     * key-value pairs with 'card.' prefix suitable for submission to SEP-09 compliant
     * services. Implementers should prefer using tokenized card data where possible
     * to minimize PCI-DSS compliance scope.
     *
     * @return array<array-key, mixed> Associative array of field keys to values
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