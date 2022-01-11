<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Toml;

/// Point of Contact Documentation. From the stellar.toml [[PRINCIPALS]] list. It contains identifying information for the primary point of contact or principal of the organization.
/// See <a href="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md" target="_blank">Stellar Toml</a>
class PointOfContact
{
    /// Full legal name.
    public ?string $name = null;

    /// Business email address for the principal.
    public ?string $email = null;

    /// Personal Keybase account. Should include proof of ownership for other online accounts, as well as the organization's domain.
    public ?string $keybase = null;

    /// Personal Telegram account.
    public ?string $telegram = null;

    /// Personal Twitter account.
    public ?string $twitter = null;

    /// Personal Github account.
    public ?string $github = null;

    /// SHA-256 hash of a photo of the principal's government-issued photo ID.
    public ?string $idPhotoHash = null;

    /// SHA-256 hash of a verification photo of principal. Should be well-lit and contain: principal holding ID card and signed, dated, hand-written message stating I, $name, am a principal of $orgName, a Stellar token issuer with address $issuerAddress.
    public ?string $verificationPhotoHash = null;

}