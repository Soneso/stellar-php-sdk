<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;

/**
 * Request parameters for initiating a SEP-24 interactive deposit
 *
 * This class represents the request payload for starting an interactive deposit
 * flow as defined by SEP-24 (Hosted Deposit and Withdrawal). It contains all the
 * parameters needed to initiate a deposit from an external payment system to the
 * Stellar network.
 *
 * A deposit transfers assets from an off-chain source (e.g., bank account, crypto
 * exchange) to the user's Stellar account. The user provides the external funds to
 * the anchor, and the anchor credits the equivalent Stellar asset to the user's
 * account. This request initiates the interactive flow where the user provides
 * deposit details through the anchor's web interface.
 *
 * Required fields include the JWT authentication token and asset code. Optional
 * fields allow pre-filling the interactive form with known information like amount,
 * source asset, destination account, memo, and KYC details to streamline the user
 * experience.
 *
 * @package Soneso\StellarSDK\SEP\Interactive
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md SEP-24 Specification
 * @see InteractiveService For executing deposit requests
 * @see SEP24Transaction For the transaction response
 * @see StandardKYCFields For KYC data structure
 */
class SEP24DepositRequest
{
    /**
     * @var string $jwt jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public string $jwt;

    /**
     * @var string $assetCode The code of the stellar asset the user wants to receive for their deposit with the anchor.
     * The value passed must match one of the codes listed in the /info response's deposit object.
     * 'native' is a special asset_code that represents the native XLM token.
     */
    public string $assetCode;

    /**
     * @var string|null $assetIssuer (optional) The issuer of the stellar asset the user wants to receive for their deposit with the anchor.
     * If assetIssuer is not provided, the anchor will use the asset issued by themselves as described in their TOML file.
     * If 'native' is specified as the assetCode, assetIssuer must be not be set.
     */
    public ?string $assetIssuer = null;

    /**
     * @var string|null $sourceAsset (optional) - string in Asset Identification Format - The asset user wants to send.
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format.
     * Note, that this is the asset user initially holds (off-chain or fiat asset).
     * If this is not provided, it will be collected in the interactive flow.
     * When quoteId is specified, this parameter must match the quote's sell_asset asset code or be omitted.
     */
    public ?string $sourceAsset = null;

    /**
     * @var float|null $amount (optional) Amount of asset requested to deposit.
     * If this is not provided it will be collected in the interactive flow.
     * When qouteId is specified, this parameter must match the quote's quote.sell_amount or be omitted.
     */
    public ?float $amount = null;

    /**
     * @var string|null $quoteId (optional) The id returned from a SEP-38 POST /quote response.
     */
    public ?string $quoteId = null;

    /**
     * @var string|null $account (optional) The Stellar or muxed account the client wants to use as the destination of the payment sent by the anchor.
     * Defaults to the account authenticated via SEP-10 if not specified.
     */
    public ?string $account = null;

    /**
     * @var string|null $memo (optional) Value of memo to attach to transaction, for hash this should be base64-encoded.
     * Because a memo can be specified in the SEP-10 JWT for Shared Accounts, this field can be different than the value included in the SEP-10 JWT.
     * For example, a client application could use the value passed for this parameter as a reference number used to match payments made to account.
     */
    public ?string $memo = null;

    /**
     * @var string|null $memoType (optional) type of memo that anchor should attach to the Stellar payment transaction,
     * one of 'text', 'id' or 'hash'
     */
    public ?string $memoType = null;

    /**
     * @var string|null $walletName (deprecated, optional) In communications / pages about the deposit,
     * anchor should display the wallet name to the user to explain where funds are going.
     */
    public ?string $walletName = null;


    /**
     * @var string|null $walletUrl (deprecated, optional) Anchor should link to this when notifying the user that the transaction has completed.
     */
    public ?string $walletUrl = null;

    /**
     * @var string|null $lang (optional) Defaults to en if not specified or if the specified language is not supported.
     * Language code specified using RFC 4646 which means it can also accept locale in the format en-US.
     * error fields in the response, as well as the interactive flow UI and any other user-facing strings
     * returned for this transaction should be in this language.
     */
    public ?string $lang = null;

    /**
     * @var string|null $claimableBalanceSupported (optional) True if the client supports receiving deposit transactions as a claimable balance, false otherwise.
     */
    public ?string $claimableBalanceSupported = null;

    /**
     * @var string|null $customerId (optional) id of an off-chain account (managed by the anchor) associated with this user's Stellar account (identified by the JWT's sub field).
     * If the anchor supports [SEP-12], the customer_id field should match the [SEP-12] customer's id.
     * customer_id should be passed only when the off-chain id is known to the client, but the relationship between this id and
     * the user's Stellar account is not known to the Anchor.
     */
    public ?string $customerId = null;

    /**
     * @var StandardKYCFields|null $kycFields Additionally, any SEP-9 parameters may be passed as well to make the onboarding experience simpler.
     */
    public ?StandardKYCFields $kycFields = null;

    /**
     * @var array<array-key, mixed>|null $customFields Custom SEP-9 fields that you can use for transmission.
     */
    public ?array $customFields = null;

    /**
     * @var array<array-key, string>|null $customFiles Custom SEP-9 files that you can use for transmission (["fieldname" => "binary string value", ...])
     */
    public ?array $customFiles = null;

    /**
     * @return string jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public function getJwt(): string
    {
        return $this->jwt;
    }

    /**
     * @param string $jwt jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public function setJwt(string $jwt): void
    {
        $this->jwt = $jwt;
    }

    /**
     * @return string The code of the stellar asset the user wants to receive for their deposit with the anchor.
     *  The value passed must match one of the codes listed in the /info response's deposit object.
     *  'native' is a special asset_code that represents the native XLM token.
     */
    public function getAssetCode(): string
    {
        return $this->assetCode;
    }

    /**
     * @param string $assetCode The code of the stellar asset the user wants to receive for their deposit with the anchor.
     *  The value passed must match one of the codes listed in the /info response's deposit object.
     *  'native' is a special asset_code that represents the native XLM token.
     */
    public function setAssetCode(string $assetCode): void
    {
        $this->assetCode = $assetCode;
    }

    /**
     * @return string|null The issuer of the stellar asset the user wants to receive for their deposit with the anchor.
     *  If assetIssuer is not provided, the anchor will use the asset issued by themselves as described in their TOML file.
     *  If 'native' is specified as the assetCode, assetIssuer must be not be set.
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
    }

    /**
     * @param string|null $assetIssuer The issuer of the stellar asset the user wants to receive for their deposit with the anchor.
     *  If assetIssuer is not provided, the anchor will use the asset issued by themselves as described in their TOML file.
     *  If 'native' is specified as the assetCode, assetIssuer must be not be set.
     */
    public function setAssetIssuer(?string $assetIssuer): void
    {
        $this->assetIssuer = $assetIssuer;
    }

    /**
     * @return string|null string in Asset Identification Format - The asset user wants to send.
     *  See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format.
     *  Note, that this is the asset user initially holds (off-chain or fiat asset).
     *  If this is not provided, it will be collected in the interactive flow.
     *  When quoteId is specified, this parameter must match the quote's sell_asset asset code or be omitted.
     */
    public function getSourceAsset(): ?string
    {
        return $this->sourceAsset;
    }

    /**
     * @param string|null $sourceAsset string in Asset Identification Format - The asset user wants to send.
     *  See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format.
     *  Note, that this is the asset user initially holds (off-chain or fiat asset).
     *  If this is not provided, it will be collected in the interactive flow.
     *  When quoteId is specified, this parameter must match the quote's sell_asset asset code or be omitted.
     */
    public function setSourceAsset(?string $sourceAsset): void
    {
        $this->sourceAsset = $sourceAsset;
    }

    /**
     * @return float|null Amount of asset requested to deposit.
     *  If this is not provided it will be collected in the interactive flow.
     *  When qouteId is specified, this parameter must match the quote's quote.sell_amount or be omitted.
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * @param float|null $amount Amount of asset requested to deposit.
     * If this is not provided it will be collected in the interactive flow.
     * When qouteId is specified, this parameter must match the quote's quote.sell_amount or be omitted.
     */
    public function setAmount(?float $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string|null The id returned from a SEP-38 POST /quote response.
     */
    public function getQuoteId(): ?string
    {
        return $this->quoteId;
    }

    /**
     * @param string|null $quoteId The id returned from a SEP-38 POST /quote response.
     */
    public function setQuoteId(?string $quoteId): void
    {
        $this->quoteId = $quoteId;
    }

    /**
     * @return string|null The Stellar or muxed account the client wants to use as the destination of the payment sent by the anchor.
     *  Defaults to the account authenticated via SEP-10 if not specified.
     */
    public function getAccount(): ?string
    {
        return $this->account;
    }

    /**
     * @param string|null $account The Stellar or muxed account the client wants to use as the destination of the payment sent by the anchor.
     *  Defaults to the account authenticated via SEP-10 if not specified.
     */
    public function setAccount(?string $account): void
    {
        $this->account = $account;
    }

    /**
     * @return string|null Value of memo to attach to transaction, for hash this should be base64-encoded.
     *  Because a memo can be specified in the SEP-10 JWT for Shared Accounts, this field can be different than the value included in the SEP-10 JWT.
     *  For example, a client application could use the value passed for this parameter as a reference number used to match payments made to account.
     */
    public function getMemo(): ?string
    {
        return $this->memo;
    }

    /**
     * @param string|null $memo Value of memo to attach to transaction, for hash this should be base64-encoded.
     *  Because a memo can be specified in the SEP-10 JWT for Shared Accounts, this field can be different than the value included in the SEP-10 JWT.
     *  For example, a client application could use the value passed for this parameter as a reference number used to match payments made to account.
     */
    public function setMemo(?string $memo): void
    {
        $this->memo = $memo;
    }

    /**
     * @return string|null type of memo that anchor should attach to the Stellar payment transaction,
     *  one of 'text', 'id' or 'hash'
     */
    public function getMemoType(): ?string
    {
        return $this->memoType;
    }

    /**
     * @param string|null $memoType type of memo that anchor should attach to the Stellar payment transaction,
     *  one of 'text', 'id' or 'hash'
     */
    public function setMemoType(?string $memoType): void
    {
        $this->memoType = $memoType;
    }

    /**
     * @return string|null (deprecated, optional) In communications / pages about the deposit,
     *  anchor should display the wallet name to the user to explain where funds are going.
     */
    public function getWalletName(): ?string
    {
        return $this->walletName;
    }

    /**
     * @param string|null $walletName (deprecated, optional) In communications / pages about the deposit,
     *  anchor should display the wallet name to the user to explain where funds are going.
     */
    public function setWalletName(?string $walletName): void
    {
        $this->walletName = $walletName;
    }

    /**
     * @return string|null (deprecated, optional) Anchor should link to this when notifying the user that the transaction has completed.
     */
    public function getWalletUrl(): ?string
    {
        return $this->walletUrl;
    }

    /**
     * @param string|null $walletUrl (deprecated, optional) Anchor should link to this when notifying the user that the transaction has completed.
     */
    public function setWalletUrl(?string $walletUrl): void
    {
        $this->walletUrl = $walletUrl;
    }

    /**
     * @return string|null Defaults to en if not specified or if the specified language is not supported.
     *  Language code specified using RFC 4646 which means it can also accept locale in the format en-US.
     *  error fields in the response, as well as the interactive flow UI and any other user-facing strings
     *  returned for this transaction should be in this language.
     */
    public function getLang(): ?string
    {
        return $this->lang;
    }

    /**
     * @param string|null $lang Defaults to en if not specified or if the specified language is not supported.
     *  Language code specified using RFC 4646 which means it can also accept locale in the format en-US.
     *  error fields in the response, as well as the interactive flow UI and any other user-facing strings
     *  returned for this transaction should be in this language.
     */
    public function setLang(?string $lang): void
    {
        $this->lang = $lang;
    }

    /**
     * @return string|null True if the client supports receiving deposit transactions as a claimable balance, false otherwise.
     */
    public function getClaimableBalanceSupported(): ?string
    {
        return $this->claimableBalanceSupported;
    }

    /**
     * @param string|null $claimableBalanceSupported True if the client supports receiving deposit transactions as a claimable balance, false otherwise.
     */
    public function setClaimableBalanceSupported(?string $claimableBalanceSupported): void
    {
        $this->claimableBalanceSupported = $claimableBalanceSupported;
    }

    /**
     * @return string|null id of an off-chain account (managed by the anchor) associated with this user's Stellar account (identified by the JWT's sub field).
     *  If the anchor supports [SEP-12], the customer_id field should match the [SEP-12] customer's id.
     *  customer_id should be passed only when the off-chain id is known to the client, but the relationship between this id and
     *  the user's Stellar account is not known to the Anchor.
     */
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @param string|null $customerId id of an off-chain account (managed by the anchor) associated with this user's Stellar account (identified by the JWT's sub field).
     *  If the anchor supports [SEP-12], the customer_id field should match the [SEP-12] customer's id.
     *  customer_id should be passed only when the off-chain id is known to the client, but the relationship between this id and
     *  the user's Stellar account is not known to the Anchor.
     * @return void
     */
    public function setCustomerId(?string $customerId): void
    {
        $this->customerId = $customerId;
    }

    /**
     * @return StandardKYCFields|null Additionally, any SEP-9 parameters may be passed as well to make the onboarding experience simpler.
     */
    public function getKycFields(): ?StandardKYCFields
    {
        return $this->kycFields;
    }

    /**
     * @param StandardKYCFields|null $kycFields Additionally, any SEP-9 parameters may be passed as well to make the onboarding experience simpler.
     */
    public function setKycFields(?StandardKYCFields $kycFields): void
    {
        $this->kycFields = $kycFields;
    }

    /**
     * @return array<array-key, mixed>|null Custom SEP-9 fields that you can use for transmission.
     */
    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    /**
     * @param array<array-key, mixed>|null $customFields Custom SEP-9 fields that you can use for transmission.
     */
    public function setCustomFields(?array $customFields): void
    {
        $this->customFields = $customFields;
    }

    /**
     * @return array<array-key, string>|null Custom SEP-9 files that you can use for transmission (["fieldname" => "binary string value", ...])
     */
    public function getCustomFiles(): ?array
    {
        return $this->customFiles;
    }

    /**
     * @param array<array-key, string>|null $customFiles Custom SEP-9 files that you can use for transmission (["fieldname" => "binary string value", ...])
     */
    public function setCustomFiles(?array $customFiles): void
    {
        $this->customFiles = $customFiles;
    }

}