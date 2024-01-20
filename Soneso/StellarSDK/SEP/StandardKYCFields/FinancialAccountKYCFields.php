<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\StandardKYCFields;


class FinancialAccountKYCFields
{
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

    public function fields() : array {
        $fields = array();
        if ($this->bankAccountType) {
            $fields += [ "bank_account_type" => $this->bankAccountType ];
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
        if ($this->clabeNumber) {
            $fields += [ "clabe_number" => $this->clabeNumber ];
        }
        if ($this->cbuNumber) {
            $fields += [ "cbu_number" => $this->cbuNumber ];
        }
        if ($this->cbuAlias) {
            $fields += [ "cbu_alias" => $this->cbuAlias ];
        }
        if ($this->cryptoAddress) {
            $fields += [ "crypto_address" => $this->cryptoAddress ];
        }
        if ($this->cryptoMemo) {
            $fields += [ "crypto_memo" => $this->cryptoMemo ];
        }
        return $fields;
    }
}