<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Toml;

/// General information from the stellar.toml file.
/// See <a href="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md" target="_blank">Stellar Toml</a>
class GeneralInformation
{
    /// The version of SEP-1 your stellar.toml adheres to. This helps parsers know which fields to expect.
    public ?string $version = null;

    /// The passphrase for the specific Stellar network this infrastructure operates on.
    public ?string $networkPassphrase = null;

    /// The endpoint for clients to resolve stellar addresses for users on your domain via SEP-2 Federation Protocol.
    public ?string $federationServer = null;

    /// The endpoint used for SEP-3 Compliance Protocol.
    public ?string $authServer = null;

    /// The server used for SEP-6 Anchor/Client interoperability.
    public ?string $transferServer = null;

    /// The server used for SEP-24 Anchor/Client interoperability.
    public ?string $transferServerSep24 = null;

    /// The server used for SEP-12 Anchor/Client customer info transfer.
    public ?string $kYCServer = null;

    /// The endpoint used for SEP-10 Web Authentication.
    public ?string $webAuthEndpoint = null;

    /// The signing key is used for SEP-3 Compliance Protocol (deprecated) and SEP-10 Authentication Protocol.
    public ?string $signingKey = null;

    /// Location of public-facing Horizon instance (if one is offered).
    public ?string $horizonUrl = null;

    /// A list of Stellar accounts that are controlled by this domain
    public array $accounts = array();

    /// The signing key is used for SEP-7 delegated signing.
    public ?string $uriRequestSigningKey = null;

    /// The server used for receiving SEP-31 direct fiat-to-fiat payments. Requires SEP-12 and hence a KYC_SERVER TOML attribute.
    public ?string $directPaymentServer = null;

    /// The server used for receiving SEP-38 requests.
    public ?string $anchorQuoteServer = null;
}