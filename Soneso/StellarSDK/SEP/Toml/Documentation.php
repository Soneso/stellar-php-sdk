<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Toml;

/// Organization Documentation. From the stellar.toml DOCUMENTATION table.
/// See <a href="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md" target="_blank">Stellar Toml</a>
class Documentation
{
    /// Legal name of the organization.
    public ?string $orgName = null;

    /// (may not apply) DBA of the organization.
    public ?string $orgDBA = null;

    /// The organization's official URL. The stellar.toml must be hosted on the same domain.
    public ?string $orgUrl = null;

    /// An Url to a PNG image of the organization's logo on a transparent background.
    public ?string $orgLogo = null;

    /// Short description of the organization.
    public ?string $orgDescription = null;

    /// Physical address for the organization.
    public ?string $orgPhysicalAddress = null;

    /// URL on the same domain as the orgUrl that contains an image or pdf official document attesting to the physical address. It must list the orgName or orgDBA as the party at the address. Only documents from an official third party are acceptable. E.g. a utility bill, mail from a financial institution, or business license.
    public ?string $orgPhysicalAddressAttestation = null;

    /// The organization's phone number in E.164 format, e.g. +14155552671.
    public ?string $orgPhoneNumber = null;

    /// URL on the same domain as the orgUrl that contains an image or pdf of a phone bill showing both the phone number and the organization's name.
    public ?string $orgPhoneNumberAttestation = null;

    /// A Keybase account name for the organization. Should contain proof of ownership of any public online accounts you list here, including the organization's domain.
    public ?string $orgKeybase = null;

    /// The organization's Twitter account.
    public ?string $orgTwitter = null;

    /// The organization's Github account
    public ?string $orgGithub = null;

    /// An email where clients can contact the organization. Must be hosted at the orgUrl domain.
    public ?string $orgOfficialEmail = null;

    /// An email that users can use to request support regarding the organizations Stellar assets or applications.
    public ?string $orgSupportEmail = null;

    /// Name of the authority or agency that licensed the organization, if applicable.
    public ?string $orgLicensingAuthority = null;

    /// Type of financial or other license the organization holds, if applicable
    public ?string $orgLicenseType = null;

    /// Official license number of the organization, if applicable.
    public ?string $orgLicenseNumber = null;
}