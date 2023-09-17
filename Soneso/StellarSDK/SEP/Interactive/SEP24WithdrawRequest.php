<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;

class SEP24WithdrawRequest
{
    /// jwt token previously received from the anchor via the SEP-10 authentication flow
    public string $jwt;

    /// Code of the asset the user wants to withdraw. The value passed must match one of the codes listed in the /info response's withdraw object.
    /// 'native' is a special asset_code that represents the native XLM token.
    public string $assetCode;


    /// (optional) The issuer of the stellar asset the user wants to withdraw with the anchor.
    /// If asset_issuer is not provided, the anchor should use the asset issued by themselves as described in their TOML file.
    /// If 'native' is specified as the asset_code, asset_issuer must be not be set.
    public ?string $assetIssuer = null;

    /// (optional) string in Asset Identification Format - The asset user wants to receive. It's an off-chain or fiat asset.
    /// If this is not provided, it will be collected in the interactive flow.
    /// When quote_id is specified, this parameter must match the quote's buy_asset asset code or be omitted.
    public ?string $destinationAsset = null;

    /// (optional) Amount of asset requested to withdraw. If this is not provided it will be collected in the interactive flow.
    public ?string $amount = null;

    /// (optional) The id returned from a SEP-38 POST /quote response.
    public ?string $quoteId = null;

    /// (optional) The Stellar (G...) or muxed account (M...) the client wants to use as the destination of the payment sent by the anchor.
    /// Defaults to the account authenticated via SEP-10 if not specified.
    public ?string $account = null;

    /// (deprecated, optional) This field was originally intended to differentiate users of the same Stellar account.
    /// However, the anchor should use the sub value included in the decoded SEP-10 JWT instead.
    /// Anchors should still support this parameter to maintain support for outdated clients.
    /// See the Shared Account Authentication section for more information.
    /// https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#shared-omnibus-or-pooled-accounts
    public ?string $memo = null;

    /// (deprecated, optional) Type of memo. One of text, id or hash. Deprecated because memos used to identify users of the same Stellar account should always be of type of id.
    public ?string $memoType = null;

    /// (optional) In communications / pages about the withdrawal, anchor should display the wallet name to the user to explain where funds are coming from.
    public ?string $walletName = null;

    /// (optional) Anchor can show this to the user when referencing the wallet involved in the withdrawal (ex. in the anchor's transaction history).
    public ?string $walletUrl = null;

    /// (optional) Defaults to en if not specified or if the specified language is not supported.
    /// Language code specified using RFC 4646 which means it can also accept locale in the format en-US.
    /// error fields in the response, as well as the interactive flow UI and any other user-facing
    /// strings returned for this transaction should be in this language.
    public ?string $lang = null;

    /// (optional) The memo the anchor must use when sending refund payments back to the user.
    /// If not specified, the anchor should use the same memo used by the user to send the original payment.
    /// If specified, refund_memo_type must also be specified.
    public ?string $refundMemo = null;

    /// (optional) The type of the refund_memo. Can be id, text, or hash.
    /// See the memos documentation for more information.
    /// If specified, refund_memo must also be specified.
    /// https://developers.stellar.org/docs/encyclopedia/memos
    public ?string $refundMemoType = null;

    /// Additionally, any SEP-9 parameters may be passed as well to make the onboarding experience simpler.
    public ?StandardKYCFields $kycFields = null;

    /// Custom SEP-9 fields that you can use for transmission (fieldname,value)
    public ?array $customFields = null;

    /// Custom SEP-9 files that you can use for transmission (fieldname, value)
    public ?array $customFiles = null;

    /**
     * @return string
     */
    public function getJwt(): string
    {
        return $this->jwt;
    }

    /**
     * @param string $jwt
     */
    public function setJwt(string $jwt): void
    {
        $this->jwt = $jwt;
    }

    /**
     * @return string
     */
    public function getAssetCode(): string
    {
        return $this->assetCode;
    }

    /**
     * @param string $assetCode
     */
    public function setAssetCode(string $assetCode): void
    {
        $this->assetCode = $assetCode;
    }

    /**
     * @return string|null
     */
    public function getAssetIssuer(): ?string
    {
        return $this->assetIssuer;
    }

    /**
     * @param string|null $assetIssuer
     */
    public function setAssetIssuer(?string $assetIssuer): void
    {
        $this->assetIssuer = $assetIssuer;
    }

    /**
     * @return string|null
     */
    public function getDestinationAsset(): ?string
    {
        return $this->destinationAsset;
    }

    /**
     * @param string|null $destinationAsset
     */
    public function setDestinationAsset(?string $destinationAsset): void
    {
        $this->destinationAsset = $destinationAsset;
    }

    /**
     * @return string|null
     */
    public function getAmount(): ?string
    {
        return $this->amount;
    }

    /**
     * @param string|null $amount
     */
    public function setAmount(?string $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string|null
     */
    public function getQuoteId(): ?string
    {
        return $this->quoteId;
    }

    /**
     * @param string|null $quoteId
     */
    public function setQuoteId(?string $quoteId): void
    {
        $this->quoteId = $quoteId;
    }

    /**
     * @return string|null
     */
    public function getAccount(): ?string
    {
        return $this->account;
    }

    /**
     * @param string|null $account
     */
    public function setAccount(?string $account): void
    {
        $this->account = $account;
    }

    /**
     * @return string|null
     */
    public function getMemo(): ?string
    {
        return $this->memo;
    }

    /**
     * @param string|null $memo
     */
    public function setMemo(?string $memo): void
    {
        $this->memo = $memo;
    }

    /**
     * @return string|null
     */
    public function getMemoType(): ?string
    {
        return $this->memoType;
    }

    /**
     * @param string|null $memoType
     */
    public function setMemoType(?string $memoType): void
    {
        $this->memoType = $memoType;
    }

    /**
     * @return string|null
     */
    public function getWalletName(): ?string
    {
        return $this->walletName;
    }

    /**
     * @param string|null $walletName
     */
    public function setWalletName(?string $walletName): void
    {
        $this->walletName = $walletName;
    }

    /**
     * @return string|null
     */
    public function getWalletUrl(): ?string
    {
        return $this->walletUrl;
    }

    /**
     * @param string|null $walletUrl
     */
    public function setWalletUrl(?string $walletUrl): void
    {
        $this->walletUrl = $walletUrl;
    }

    /**
     * @return string|null
     */
    public function getLang(): ?string
    {
        return $this->lang;
    }

    /**
     * @param string|null $lang
     */
    public function setLang(?string $lang): void
    {
        $this->lang = $lang;
    }

    /**
     * @return string|null
     */
    public function getRefundMemo(): ?string
    {
        return $this->refundMemo;
    }

    /**
     * @param string|null $refundMemo
     */
    public function setRefundMemo(?string $refundMemo): void
    {
        $this->refundMemo = $refundMemo;
    }

    /**
     * @return string|null
     */
    public function getRefundMemoType(): ?string
    {
        return $this->refundMemoType;
    }

    /**
     * @param string|null $refundMemoType
     */
    public function setRefundMemoType(?string $refundMemoType): void
    {
        $this->refundMemoType = $refundMemoType;
    }

    /**
     * @return StandardKYCFields|null
     */
    public function getKycFields(): ?StandardKYCFields
    {
        return $this->kycFields;
    }

    /**
     * @param StandardKYCFields|null $kycFields
     */
    public function setKycFields(?StandardKYCFields $kycFields): void
    {
        $this->kycFields = $kycFields;
    }

    /**
     * @return array|null
     */
    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    /**
     * @param array|null $customFields
     */
    public function setCustomFields(?array $customFields): void
    {
        $this->customFields = $customFields;
    }

    /**
     * @return array|null
     */
    public function getCustomFiles(): ?array
    {
        return $this->customFiles;
    }

    /**
     * @param array|null $customFiles
     */
    public function setCustomFiles(?array $customFiles): void
    {
        $this->customFiles = $customFiles;
    }

}