<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;

class SEP24DepositRequest
{
    /// jwt token previously received from the anchor via the SEP-10 authentication flow
    public string $jwt;

    /// The code of the stellar asset the user wants to receive for their deposit with the anchor.
    /// The value passed must match one of the codes listed in the /info response's deposit object.
    /// 'native' is a special asset_code that represents the native XLM token.
    public string $assetCode;


    /// (optional) The issuer of the stellar asset the user wants to receive for their deposit with the anchor.
    /// If assetIssuer is not provided, the anchor will use the asset issued by themselves as described in their TOML file.
    /// If 'native' is specified as the assetCode, assetIssuer must be not be set.
    public ?string $assetIssuer = null;

    /// (optional) - string in Asset Identification Format - The asset user wants to send. Note, that this is the asset user initially holds (off-chain or fiat asset).
    /// If this is not provided, it will be collected in the interactive flow.
    /// When quote_id is specified, this parameter must match the quote's sell_asset asset code or be omitted.
    public ?string $sourceAsset = null;

    /// (optional) Amount of asset requested to deposit. If this is not provided it will be collected in the interactive flow.
    public ?string $amount = null;

    /// (optional) The id returned from a SEP-38 POST /quote response.
    public ?string $quoteId = null;

    /// (optional) The Stellar (G...) or muxed account (M...) the client will use as the source of the withdrawal payment to the anchor.
    /// Defaults to the account authenticated via SEP-10 if not specified.
    public ?string $account = null;

    /// (optional) Value of memo to attach to transaction, for hash this should be base64-encoded.
    /// Because a memo can be specified in the SEP-10 JWT for Shared Accounts, this field can be different than the value included in the SEP-10 JWT.
    /// For example, a client application could use the value passed for this parameter as a reference number used to match payments made to account.
    public ?string $memo = null;

    /// (optional) type of memo that anchor should attach to the Stellar payment transaction, one of text, id or hash
    public ?string $memoType = null;

    /// (optional) In communications / pages about the deposit, anchor should display the wallet name to the user to explain where funds are going.
    public ?string $walletName = null;

    /// (optional) Anchor should link to this when notifying the user that the transaction has completed.
    public ?string $walletUrl = null;

    /// (optional) Defaults to en if not specified or if the specified language is not supported.
    /// Language code specified using RFC 4646 which means it can also accept locale in the format en-US.
    /// error fields in the response, as well as the interactive flow UI and any other user-facing strings
    /// returned for this transaction should be in this language.
    public ?string $lang = null;

    /// (optional) True if the client supports receiving deposit transactions as a claimable balance, false otherwise.
    public ?string $claimableBalanceSupported = null;

    /// Additionally, any SEP-9 parameters may be passed as well to make the onboarding experience simpler.
    public ?StandardKYCFields $kycFields = null;

    /// Custom SEP-9 fields that you can use for transmission (["fieldname" => "string value", ...])
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
    public function getSourceAsset(): ?string
    {
        return $this->sourceAsset;
    }

    /**
     * @param string|null $sourceAsset
     */
    public function setSourceAsset(?string $sourceAsset): void
    {
        $this->sourceAsset = $sourceAsset;
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
    public function getClaimableBalanceSupported(): ?string
    {
        return $this->claimableBalanceSupported;
    }

    /**
     * @param string|null $claimableBalanceSupported
     */
    public function setClaimableBalanceSupported(?string $claimableBalanceSupported): void
    {
        $this->claimableBalanceSupported = $claimableBalanceSupported;
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