<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;

/**
 * Request parameters for initiating a SEP-24 interactive withdrawal
 *
 * This class represents the request payload for starting an interactive withdrawal
 * flow as defined by SEP-24 (Hosted Deposit and Withdrawal). It contains all the
 * parameters needed to initiate a withdrawal from the Stellar network to an external
 * payment system (e.g., bank account, crypto exchange).
 *
 * A withdrawal transfers assets from the user's Stellar account to an off-chain
 * destination. The anchor processes the withdrawal and sends funds to the specified
 * external account. This request initiates the interactive flow where the user
 * provides withdrawal details through the anchor's web interface.
 *
 * Required fields include the JWT authentication token and asset code. Optional
 * fields allow pre-filling the interactive form with known information like amount,
 * destination asset, and KYC details to streamline the user experience.
 *
 * @package Soneso\StellarSDK\SEP\Interactive
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md SEP-24 Specification
 * @see InteractiveService For executing withdrawal requests
 * @see SEP24Transaction For the transaction response
 * @see StandardKYCFields For KYC data structure
 */
class SEP24WithdrawRequest
{
    /**
     * @var string $jwt jwt token previously received from the anchor via the SEP-10 authentication flow
     */
    public string $jwt;

    /**
     * @var string $assetCode Code of the asset the user wants to withdraw. The value passed must match one of the codes listed in the /info response's withdraw object.
     * 'native' is a special asset_code that represents the native XLM token.
     */
    public string $assetCode;


    /**
     * @var string|null $assetIssuer (optional) The issuer of the stellar asset the user wants to withdraw with the anchor.
     * If asset_issuer is not provided, the anchor should use the asset issued by themselves as described in their TOML file.
     * If 'native' is specified as the asset_code, asset_issuer must be not be set.
     */
    public ?string $assetIssuer = null;

    /**
     * @var string|null $destinationAsset (optional) string in Asset Identification Format - The asset user wants to receive.
     * See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format.
     * It's an off-chain or fiat asset.
     * If this is not provided, it will be collected in the interactive flow.
     * When quote_id is specified, this parameter must match the quote's buy_asset asset code or be omitted.
     */
    public ?string $destinationAsset = null;

    /**
     * @var float|null $amount (optional) Amount of asset requested to withdraw. If this is not provided it will be collected in the interactive flow.
     */
    public ?float $amount = null;

    /**
     * @var string|null $quoteId (optional) The id returned from a SEP-38 POST /quote response.
     */
    public ?string $quoteId = null;

    /**
     * @var string|null $account The Stellar or muxed account the client will use as the source of the withdrawal payment to the anchor.
     * Defaults to the account authenticated via SEP-10 if not specified.
     */
    public ?string $account = null;

    /**
     * @var string|null $memo (deprecated, optional) This field was originally intended to differentiate users of the same Stellar account.
     * However, the anchor should use the sub value included in the decoded SEP-10 JWT instead.
     * Anchors should still support this parameter to maintain support for outdated clients.
     * See the Shared Account Authentication section for more information.
     * https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#shared-omnibus-or-pooled-accounts
     */
    public ?string $memo = null;

    /**
     * @var string|null $memoType (deprecated, optional) Type of memo. One of text, id or hash.
     * Deprecated because memos used to identify users of the same Stellar account should always be of type of id.
     */
    public ?string $memoType = null;

    /**
     * @var string|null $walletName (deprecated,optional) In communications / pages about the withdrawal,
     * anchor should display the wallet name to the user to explain where funds are coming from.
     * However, anchors should use client_domain (for non-custodial) and sub value
     * of JWT (for custodial) to determine wallet information.
     */
    public ?string $walletName = null;

    /**
     * @var string|null $walletUrl (deprecated,optional) Anchor can show this to the user when referencing
     * the wallet involved in the withdrawal (ex. in the anchor's transaction history).
     * However, anchors should use client_domain (for non-custodial) and sub value
     * of JWT (for custodial) to determine wallet information.
     */
    public ?string $walletUrl = null;

    /**
     * @var string|null $lang (optional) Defaults to en if not specified or if the specified language is not supported.
     * Language code specified using RFC 4646 which means it can also accept locale in the format en-US.
     * error fields in the response, as well as the interactive flow UI and any other user-facing
     * strings returned for this transaction should be in this language.
     */
    public ?string $lang = null;

    /**
     * @var string|null $refundMemo (optional) The memo the anchor must use when sending refund payments back to the user.
     * If not specified, the anchor should use the same memo used by the user to send the original payment.
     * If specified, refund_memo_type must also be specified.
     */
    public ?string $refundMemo = null;

    /**
     * @var string|null $refundMemoType (optional) The type of the refund_memo. Can be id, text, or hash.
     * See the memos documentation for more information.
     * If specified, refund_memo must also be specified.
     * https://developers.stellar.org/docs/encyclopedia/memos
     */
    public ?string $refundMemoType = null;

    /**
     * @var string|null $customerId (optional) id of an off-chain account (managed by the anchor) associated
     * with this user's Stellar account (identified by the JWT's sub field).
     * If the anchor supports [SEP-12], the customer_id field should match the [SEP-12] customer's id.
     * customer_id should be passed only when the off-chain id is known to the client,
     * but the relationship between this id and the user's Stellar account is not known to the Anchor.
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
     * @return string Code of the asset the user wants to withdraw. The value passed must match one of the codes listed in the /info response's withdraw object.
     *  'native' is a special asset_code that represents the native XLM token.
     */
    public function getAssetCode(): string
    {
        return $this->assetCode;
    }

    /**
     * @param string $assetCode Code of the asset the user wants to withdraw. The value passed must match one of the codes listed in the /info response's withdraw object.
     *  'native' is a special asset_code that represents the native XLM token.
     */
    public function setAssetCode(string $assetCode): void
    {
        $this->assetCode = $assetCode;
    }

    /**
     * @return string|null (optional) The issuer of the stellar asset the user wants to withdraw with the anchor.
     * If asset_issuer is not provided, the anchor should use the asset issued by themselves as described in their TOML file.
     * If 'native' is specified as the asset_code, asset_issuer must be not be set.
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
    }

    /**
     * @param string|null $assetIssuer (optional) The issuer of the stellar asset the user wants to withdraw with the anchor.
     *  If asset_issuer is not provided, the anchor should use the asset issued by themselves as described in their TOML file.
     *  If 'native' is specified as the asset_code, asset_issuer must be not be set.
     */
    public function setAssetIssuer(?string $assetIssuer): void
    {
        $this->assetIssuer = $assetIssuer;
    }

    /**
     * @return string|null (optional) string in Asset Identification Format - The asset user wants to receive.
     *  See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format.
     *  It's an off-chain or fiat asset.
     *  If this is not provided, it will be collected in the interactive flow.
     *  When quote_id is specified, this parameter must match the quote's buy_asset asset code or be omitted.
     */
    public function getDestinationAsset(): ?string
    {
        return $this->destinationAsset;
    }

    /**
     * @param string|null $destinationAsset (optional) string in Asset Identification Format - The asset user wants to receive.
     *  See: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format.
     *  It's an off-chain or fiat asset.
     *  If this is not provided, it will be collected in the interactive flow.
     *  When quote_id is specified, this parameter must match the quote's buy_asset asset code or be omitted.
     */
    public function setDestinationAsset(?string $destinationAsset): void
    {
        $this->destinationAsset = $destinationAsset;
    }

    /**
     * @return float|null (optional) Amount of asset requested to withdraw. If this is not provided it will be collected in the interactive flow.
     */
    public function getAmount(): ?float
    {
        return $this->amount;
    }

    /**
     * @param float|null $amount (optional) Amount of asset requested to withdraw. If this is not provided it will be collected in the interactive flow.
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
     * @return string|null The Stellar or muxed account the client will use as the source of the withdrawal payment to the anchor.
     *  Defaults to the account authenticated via SEP-10 if not specified.
     */
    public function getAccount(): ?string
    {
        return $this->account;
    }

    /**
     * @param string|null $account The Stellar or muxed account the client will use as the source of the withdrawal payment to the anchor.
     *  Defaults to the account authenticated via SEP-10 if not specified.
     */
    public function setAccount(?string $account): void
    {
        $this->account = $account;
    }

    /**
     * @return string|null (deprecated, optional) This field was originally intended to differentiate users of the same Stellar account.
     *  However, the anchor should use the sub value included in the decoded SEP-10 JWT instead.
     *  Anchors should still support this parameter to maintain support for outdated clients.
     *  See the Shared Account Authentication section for more information.
     *  https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#shared-omnibus-or-pooled-accounts
     */
    public function getMemo(): ?string
    {
        return $this->memo;
    }

    /**
     * @param string|null $memo (deprecated, optional) This field was originally intended to differentiate users of the same Stellar account.
     *  However, the anchor should use the sub value included in the decoded SEP-10 JWT instead.
     *  Anchors should still support this parameter to maintain support for outdated clients.
     *  See the Shared Account Authentication section for more information.
     *  https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#shared-omnibus-or-pooled-accounts
     */
    public function setMemo(?string $memo): void
    {
        $this->memo = $memo;
    }

    /**
     * @return string|null (deprecated, optional) Type of memo. One of text, id or hash.
     *  Deprecated because memos used to identify users of the same Stellar account should always be of type of id.
     */
    public function getMemoType(): ?string
    {
        return $this->memoType;
    }

    /**
     * @param string|null $memoType (deprecated, optional) Type of memo. One of text, id or hash.
     *  Deprecated because memos used to identify users of the same Stellar account should always be of type of id.
     */
    public function setMemoType(?string $memoType): void
    {
        $this->memoType = $memoType;
    }

    /**
     * @return string|null (deprecated,optional) In communications / pages about the withdrawal,
     *  anchor should display the wallet name to the user to explain where funds are coming from.
     *  However, anchors should use client_domain (for non-custodial) and sub value
     *  of JWT (for custodial) to determine wallet information.
     */
    public function getWalletName(): ?string
    {
        return $this->walletName;
    }

    /**
     * @param string|null $walletName (deprecated,optional) In communications / pages about the withdrawal,
     *  anchor should display the wallet name to the user to explain where funds are coming from.
     *  However, anchors should use client_domain (for non-custodial) and sub value
     *  of JWT (for custodial) to determine wallet information.
     */
    public function setWalletName(?string $walletName): void
    {
        $this->walletName = $walletName;
    }

    /**
     * @return string|null (deprecated,optional) Anchor can show this to the user when referencing
     *  the wallet involved in the withdrawal (ex. in the anchor's transaction history).
     *  However, anchors should use client_domain (for non-custodial) and sub value
     *  of JWT (for custodial) to determine wallet information.
     */
    public function getWalletUrl(): ?string
    {
        return $this->walletUrl;
    }

    /**
     * @param string|null $walletUrl (deprecated,optional) Anchor can show this to the user when referencing
     *  the wallet involved in the withdrawal (ex. in the anchor's transaction history).
     *  However, anchors should use client_domain (for non-custodial) and sub value
     *  of JWT (for custodial) to determine wallet information.
     */
    public function setWalletUrl(?string $walletUrl): void
    {
        $this->walletUrl = $walletUrl;
    }

    /**
     * @return string|null (optional) Defaults to en if not specified or if the specified language is not supported.
     *  Language code specified using RFC 4646 which means it can also accept locale in the format en-US.
     *  error fields in the response, as well as the interactive flow UI and any other user-facing
     *  strings returned for this transaction should be in this language.
     */
    public function getLang(): ?string
    {
        return $this->lang;
    }

    /**
     * @param string|null $lang (optional) Defaults to en if not specified or if the specified language is not supported.
     *  Language code specified using RFC 4646 which means it can also accept locale in the format en-US.
     *  error fields in the response, as well as the interactive flow UI and any other user-facing
     *  strings returned for this transaction should be in this language.
     */
    public function setLang(?string $lang): void
    {
        $this->lang = $lang;
    }

    /**
     * @return string|null (optional) The memo the anchor must use when sending refund payments back to the user.
     *  If not specified, the anchor should use the same memo used by the user to send the original payment.
     *  If specified, refund_memo_type must also be specified.
     */
    public function getRefundMemo(): ?string
    {
        return $this->refundMemo;
    }

    /**
     * @param string|null $refundMemo (optional) The memo the anchor must use when sending refund payments back to the user.
     *  If not specified, the anchor should use the same memo used by the user to send the original payment.
     *  If specified, refund_memo_type must also be specified.
     */
    public function setRefundMemo(?string $refundMemo): void
    {
        $this->refundMemo = $refundMemo;
    }

    /**
     * @return string|null (optional) The type of the refund_memo. Can be id, text, or hash.
     *  See the memos documentation for more information.
     *  If specified, refund_memo must also be specified.
     *  https://developers.stellar.org/docs/encyclopedia/memos
     */
    public function getRefundMemoType(): ?string
    {
        return $this->refundMemoType;
    }

    /**
     * @param string|null $refundMemoType (optional) The type of the refund_memo. Can be id, text, or hash.
     *   See the memos documentation for more information.
     *   If specified, refund_memo must also be specified.
     *   https://developers.stellar.org/docs/encyclopedia/memos
     */
    public function setRefundMemoType(?string $refundMemoType): void
    {
        $this->refundMemoType = $refundMemoType;
    }

    /**
     * @return string|null (optional) id of an off-chain account (managed by the anchor) associated
     *  with this user's Stellar account (identified by the JWT's sub field).
     *  If the anchor supports [SEP-12], the customer_id field should match the [SEP-12] customer's id.
     *  customer_id should be passed only when the off-chain id is known to the client,
     *  but the relationship between this id and the user's Stellar account is not known to the Anchor.
     */
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    /**
     * @param string|null $customerId (optional) id of an off-chain account (managed by the anchor) associated
     *  with this user's Stellar account (identified by the JWT's sub field).
     *  If the anchor supports [SEP-12], the customer_id field should match the [SEP-12] customer's id.
     *  customer_id should be passed only when the off-chain id is known to the client,
     *  but the relationship between this id and the user's Stellar account is not known to the Anchor.
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