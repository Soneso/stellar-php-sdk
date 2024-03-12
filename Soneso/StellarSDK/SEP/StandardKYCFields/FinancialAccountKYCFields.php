<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\StandardKYCFields;


class FinancialAccountKYCFields
{
    // field keys
    public const BANK_ACCOUNT_TYPE_KEY = 'bank_account_type';
    public const BANK_ACCOUNT_NUMBER_KEY = 'bank_account_number';
    public const BANK_NUMBER_KEY = 'bank_number';
    public const BANK_PHONE_NUMBER_KEY = 'bank_phone_number';
    public const BANK_BRANCH_NUMBER_KEY = 'bank_branch_number';
    public const CLABE_NUMBER_KEY = 'clabe_number';
    public const CBU_NUMBER_KEY = 'cbu_number';
    public const CBU_ALIAS_KEY = 'cbu_alias';
    public const CRYPTO_ADDRESS_KEY = 'crypto_address';
    public const CRYPTO_MEMO_KEY = 'crypto_memo';

    /// checking or savings
    public ?string $bankAccountType = null;

    /// Number identifying bank account
    public ?string $bankAccountNumber = null;

    /// Number identifying bank in national banking system (routing number in US)
    public ?string $bankNumber = null;

    /// Phone number with country code for bank
    public ?string $bankPhoneNumber = null;

    /// Number identifying bank branch
    public ?string $bankBranchNumber = null;

    /// Bank account number for Mexico
    public ?string $clabeNumber = null;

    /// Clave Bancaria Uniforme (CBU) or Clave Virtual Uniforme (CVU).
    public ?string $cbuNumber = null;

    /// The alias for a Clave Bancaria Uniforme (CBU) or Clave Virtual Uniforme (CVU).
    public ?string $cbuAlias= null;

    /// Address for a cryptocurrency account
    public ?string $cryptoAddress = null;

    /// A destination tag/memo used to identify a transaction
    public ?string $cryptoMemo = null;

    /**
     * @param string|null $keyPrefix optional prefix for the fields keys. e.g. 'organization.'
     * @return array<array-key, mixed>
     */
    public function fields(?string $keyPrefix = '') : array {
        /**
         * @var array<array-key, mixed> $fields
         */
        $fields = array();
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
        if ($this->clabeNumber) {
            $fields += [ $keyPrefix . self::CLABE_NUMBER_KEY => $this->clabeNumber ];
        }
        if ($this->cbuNumber) {
            $fields += [ $keyPrefix . self::CBU_NUMBER_KEY => $this->cbuNumber ];
        }
        if ($this->cbuAlias) {
            $fields += [ $keyPrefix . self::CBU_ALIAS_KEY => $this->cbuAlias ];
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