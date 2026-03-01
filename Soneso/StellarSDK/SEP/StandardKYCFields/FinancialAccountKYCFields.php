<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\StandardKYCFields;

/**
 * KYC fields for financial accounts (bank accounts, crypto addresses, mobile money).
 *
 * This class provides standardized fields for collecting financial account information
 * required for KYC and AML compliance according to SEP-09 specification. It supports
 * various account types including traditional banking, cryptocurrency, and mobile money
 * payment systems.
 *
 * PRIVACY AND SECURITY WARNING:
 * This class handles highly sensitive financial account information.
 * Implementers MUST ensure:
 * - Transmission only over HTTPS/TLS connections
 * - Encryption at rest for all stored financial data
 * - Compliance with applicable data protection regulations (GDPR, CCPA, etc.)
 * - Implementation of proper access controls and audit logging
 * - Secure data retention and deletion policies
 * - PCI-DSS compliance where applicable for payment card data
 * - Customer consent management for data collection and processing
 *
 * @package Soneso\StellarSDK\SEP\StandardKYCFields
 * @see https://github.com/stellar/stellar-protocol/blob/v1.17.0/ecosystem/sep-0009.md SEP-09 v1.17.0 Specification
 */
class FinancialAccountKYCFields
{
    // field keys
    public const BANK_NAME_KEY = 'bank_name';
    public const BANK_ACCOUNT_TYPE_KEY = 'bank_account_type';
    public const BANK_ACCOUNT_NUMBER_KEY = 'bank_account_number';
    public const BANK_NUMBER_KEY = 'bank_number';
    public const BANK_PHONE_NUMBER_KEY = 'bank_phone_number';
    public const BANK_BRANCH_NUMBER_KEY = 'bank_branch_number';
    public const EXTERNAL_TRANSFER_MEMO_KEY = 'external_transfer_memo';
    public const CLABE_NUMBER_KEY = 'clabe_number';
    public const CBU_NUMBER_KEY = 'cbu_number';
    public const CBU_ALIAS_KEY = 'cbu_alias';
    public const MOBILE_MONEY_NUMBER_KEY = 'mobile_money_number';
    public const MOBILE_MONEY_PROVIDER_KEY = 'mobile_money_provider';
    public const CRYPTO_ADDRESS_KEY = 'crypto_address';
    public const CRYPTO_MEMO_KEY = 'crypto_memo';

    /**
     * @var string|null Name of the bank (may be necessary in regions without unified routing systems)
     */
    public ?string $bankName = null;

    /**
     * @var string|null Type of bank account (e.g. checking, savings)
     */
    public ?string $bankAccountType = null;

    /**
     * @var string|null Number identifying bank account
     */
    public ?string $bankAccountNumber = null;

    /**
     * @var string|null Number identifying bank in national banking system (e.g. routing number in US)
     */
    public ?string $bankNumber = null;

    /**
     * @var string|null Phone number with country code for bank (E.164 format)
     */
    public ?string $bankPhoneNumber = null;

    /**
     * @var string|null Number identifying bank branch
     */
    public ?string $bankBranchNumber = null;

    /**
     * @var string|null Destination tag/memo used to identify a transaction
     */
    public ?string $externalTransferMemo = null;

    /**
     * @var string|null CLABE number (Clave Bancaria Estandarizada - bank account number for Mexico)
     */
    public ?string $clabeNumber = null;

    /**
     * @var string|null CBU (Clave Bancaria Uniforme) or CVU (Clave Virtual Uniforme) number for Argentina
     */
    public ?string $cbuNumber = null;

    /**
     * @var string|null Alias for a CBU (Clave Bancaria Uniforme) or CVU (Clave Virtual Uniforme)
     */
    public ?string $cbuAlias = null;

    /**
     * @var string|null Mobile phone number in E.164 format associated with a mobile money account
     * (Note: this number may be distinct from the customer's personal mobile number)
     */
    public ?string $mobileMoneyNumber = null;

    /**
     * @var string|null Name of the mobile money service provider
     */
    public ?string $mobileMoneyProvider = null;

    /**
     * @var string|null Address for a cryptocurrency account
     */
    public ?string $cryptoAddress = null;

    /**
     * @var string|null Destination tag/memo used to identify a cryptocurrency transaction
     * @deprecated Use $externalTransferMemo instead
     */
    public ?string $cryptoMemo = null;

    /**
     * Returns all non-null financial account fields as an associative array.
     *
     * This method collects all populated financial account fields, returning them as
     * key-value pairs suitable for submission to SEP-09 compliant services. An optional
     * key prefix (e.g. 'organization.') can be provided for nested field contexts.
     *
     * @param string|null $keyPrefix Optional prefix for field keys (e.g. 'organization.')
     * @return array<array-key, mixed> Associative array of field keys to values
     */
    public function fields(?string $keyPrefix = '') : array {
        /**
         * @var array<array-key, mixed> $fields
         */
        $fields = array();
        if ($this->bankName !== null) {
            $fields += [ $keyPrefix . self::BANK_NAME_KEY => $this->bankName ];
        }
        if ($this->bankAccountType) {
            $fields += [ $keyPrefix . self::BANK_ACCOUNT_TYPE_KEY => $this->bankAccountType ];
        }
        if ($this->bankAccountNumber) {
            $fields += [ $keyPrefix . self::BANK_ACCOUNT_NUMBER_KEY => $this->bankAccountNumber ];
        }
        if ($this->bankNumber) {
            $fields += [ $keyPrefix . self::BANK_NUMBER_KEY => $this->bankNumber ];
        }
        if ($this->bankPhoneNumber) {
            $fields += [ $keyPrefix . self::BANK_PHONE_NUMBER_KEY => $this->bankPhoneNumber ];
        }
        if ($this->bankBranchNumber) {
            $fields += [ $keyPrefix . self::BANK_BRANCH_NUMBER_KEY => $this->bankBranchNumber ];
        }
        if ($this->externalTransferMemo) {
            $fields += [ $keyPrefix . self::EXTERNAL_TRANSFER_MEMO_KEY => $this->externalTransferMemo ];
        }
        if ($this->clabeNumber) {
            $fields += [ $keyPrefix . self::CLABE_NUMBER_KEY => $this->clabeNumber ];
        }
        if ($this->cbuNumber) {
            $fields += [ $keyPrefix . self::CBU_NUMBER_KEY => $this->cbuNumber ];
        }
        if ($this->cbuAlias) {
            $fields += [ $keyPrefix . self::CBU_ALIAS_KEY => $this->cbuAlias ];
        }
        if ($this->mobileMoneyNumber) {
            $fields += [ $keyPrefix . self::MOBILE_MONEY_NUMBER_KEY => $this->mobileMoneyNumber ];
        }
        if ($this->mobileMoneyProvider) {
            $fields += [ $keyPrefix . self::MOBILE_MONEY_PROVIDER_KEY => $this->mobileMoneyProvider ];
        }
        if ($this->cryptoAddress) {
            $fields += [ $keyPrefix . self::CRYPTO_ADDRESS_KEY => $this->cryptoAddress ];
        }
        if ($this->cryptoMemo) {
            $fields += [ $keyPrefix . self::CRYPTO_MEMO_KEY => $this->cryptoMemo ];
        }
        return $fields;
    }
}