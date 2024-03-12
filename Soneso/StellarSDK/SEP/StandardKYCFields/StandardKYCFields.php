<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\StandardKYCFields;

/// Defines a list of standard KYC and AML fields for use in Stellar ecosystem protocols.
/// Issuers, banks, and other entities on Stellar should use these fields when sending
/// or requesting KYC / AML information with other parties on Stellar.
/// See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0009.md
class StandardKYCFields
{
    public ?NaturalPersonKYCFields $naturalPersonKYCFields = null;
    public ?OrganizationKYCFields $organizationKYCFields = null;
}