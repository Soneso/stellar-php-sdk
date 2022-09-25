<?php declare(strict_types=1);

// Copyright 2021 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Toml;

/// Currency Documentation. From the stellar.toml [[CURRENCIES]] list, one set of fields for each currency supported. Applicable fields should be completed and any that don't apply should be excluded.
/// See <a href="https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0001.md" target="_blank">Stellar Toml</a>
class Currency
{
    /// Token code.
    public ?string $code = null;

    /// A pattern with ? as a single character wildcard. Allows a [[CURRENCIES]] entry to apply to multiple assets that share the same info. An example is futures, where the only difference between issues is the date of the contract. E.g. CORN???????? to match codes such as CORN20180604.
    public ?string $codeTemplate = null;

    /// Token issuer Stellar public key.
    public ?string $issuer = null;

    /// Status of token. One of live, dead, test, or private. Allows issuer to mark whether token is dead/for testing/for private use or is live and should be listed in live exchanges.
    public ?string $status = null;

    /// Preference for number of decimals to show when a client displays currency balance.
    public ?int $displayDecimals = null;

    /// A short name for the token.
    public ?string $name = null;

    /// Description of token and what it represents.
    public ?string $desc = null;

    /// Conditions on token.
    public ?string $conditions = null;

    /// URL to a PNG image on a transparent background representing token.
    public ?string $image = null;

    /// Fixed number of tokens, if the number of tokens issued will never change.
    public ?int $fixedNumber = null;

    /// Max number of tokens, if there will never be more than maxNumber tokens.
    public ?int $maxNumber = null;

    /// The number of tokens is dilutable at the issuer's discretion.
    public ?bool $isUnlimited = null;

    /// true if token can be redeemed for underlying asset, otherwise false.
    public ?bool $isAssetAnchored = null;

    /// Type of asset anchored. Can be fiat, crypto, nft, stock, bond, commodity, realestate, or other.
    public ?string $anchorAssetType = null;

    /// If anchored token, code / symbol for asset that token is anchored to. E.g. USD, BTC, SBUX, Address of real-estate investment property.
    public ?string $anchorAsset = null;

    /// If anchored token, these are instructions to redeem the underlying asset from tokens.
    public ?string $redemptionInstructions = null;

    /// If this is an anchored crypto token, list of one or more public addresses that hold the assets for which you are issuing tokens.
    public ?array $collateralAddresses = null; // [string]

    /// Messages stating that funds in the collateralAddresses list are reserved to back the issued asset.
    public ?array $collateralAddressMessages = null; // [string]

    /// These prove you control the collateralAddresses. For each address you list, sign the entry in collateralAddressMessages with the address's private key and add the resulting string to this list as a base64-encoded raw signature.
    public ?array $collateralAddressSignatures = null; // [string]

    /// Indicates whether or not this is a sep0008 regulated asset. If missing, false is assumed.
    public ?bool $regulated = null;

    /// URL of a sep0008 compliant approval service that signs validated transactions.
    public ?string $approvalServer = null;

    /// A human readable string that explains the issuer's requirements for approving transactions.
    public ?string $approvalCriteria = null;

    /// Alternately, stellar.toml can link out to a separate TOML file for each currency by specifying toml="https://DOMAIN/.well-known/CURRENCY.toml" as the currency's only field.
    /// In this case only this field is filled. To load the currency data, you can use StellarToml.currencyFromUrl(String toml).
    public ?string $toml = null;

}