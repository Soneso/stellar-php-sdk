<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

class SEP24Transaction extends Response
{
    /// Unique, anchor-generated id for the deposit/withdrawal.
    public string $id;

    /// deposit or withdrawal.
    public string $kind;

    /// Processing status of deposit/withdrawal.
    public string $status;

    /// (optional) Estimated number of seconds until a status change is expected.
    public ?int $statusEta = null;

    /// (optional) True if the anchor has verified the user's KYC information for this transaction.
    public ?bool $kycVerified = null;

    /// A URL that is opened by wallets after the interactive flow is complete. It can include banking information for users to start deposits, the status of the transaction, or any other information the user might need to know about the transaction.
    public string $moreInfoUrl;

    /// Amount received by anchor at start of transaction as a string with up to 7 decimals. Excludes any fees charged before the anchor received the funds.
    public string $amountIn;

    /// (optional)  The asset received or to be received by the Anchor. Must be present if the deposit/withdraw was made using non-equivalent assets.
    /// The value must be in SEP-38 Asset Identification Format.
    /// https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format
    /// See also the Asset Exchanges section for more information.
    /// https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#asset-exchanges
    public ?string $amountInAsset = null;

    /// Amount sent by anchor to user at end of transaction as a string with up to 7 decimals.
    /// Excludes amount converted to XLM to fund account and any external fees.
    public string $amountOut;

    /// (optional) The asset delivered or to be delivered to the user. Must be present if the deposit/withdraw was made using non-equivalent assets.
    /// The value must be in SEP-38 Asset Identification Format.
    /// https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format
    /// See also the Asset Exchanges section for more information.
    /// https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#asset-exchanges
    public ?string $amountOutAsset = null;

    /// Amount of fee charged by anchor.
    public string $amountFee;

    /// (optional) The asset in which fees are calculated in. Must be present if the deposit/withdraw was made using non-equivalent assets.
    /// The value must be in SEP-38 Asset Identification Format.
    /// https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format
    /// See also the Asset Exchanges section for more information.
    /// https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#asset-exchanges
    public ?string $amountFeeAsset = null;

    /// (optional) The ID of the quote used when creating this transaction. Should be present if a quote_id
    /// was included in the POST /transactions/deposit/interactive or POST /transactions/withdraw/interactive request.
    /// Clients should be aware that the quote_id may not be present in older implementations.
    public ?string $quoteId = null;

    /// Start date and time of transaction. UTC ISO 8601 string
    public string $startedAt;

    /// (optional) The date and time of transaction reaching completed or refunded status. UTC ISO 8601 string
    public ?string $completedAt = null;

    /// (optional) The date and time of transaction reaching the current status. UTC ISO 8601 string
    public ?string $updatedAt = null;

    /// (optional) transaction_id on Stellar network of the transfer that either completed the deposit or started the withdrawal.
    public ?string $stellarTransactionId = null;

    /// (optional) ID of transaction on external network that either started the deposit or completed the withdrawal.
    public ?string $externalTransactionId = null;

    /// (optional) Human readable explanation of transaction status, if needed.
    public ?string $message = null;

    /// (deprecated, optional) This field is deprecated in favor of the refunds object and the refunded status.
    /// True if the transaction was refunded in full. False if the transaction was partially refunded or not refunded.
    /// For more details about any refunds, see the refunds object.
    public ?bool $refunded = null;

    /// (optional) An object describing any on or off-chain refund associated with this transaction.
    public ?Refund $refunds = null;

    /// In case of deposit: Sent from address, perhaps BTC, IBAN, or bank account.
    /// In case of withdraw: Stellar address the assets were withdrawn from.
    public ?string $from = null;

    /// In case of deposit: Stellar address the deposited assets were sent to.
    /// In case of withdraw: Sent to address (perhaps BTC, IBAN, or bank account in the case of a withdrawal, Stellar address in the case of a deposit).
    public ?string $to = null;

    //Fields for deposit transactions

    /// (optional) This is the memo (if any) used to transfer the asset to the to Stellar address.
    public ?string $depositMemo = null;

    /// (optional) Type for the deposit_memo.
    public ?string $depositMemoType = null;

    /// (optional) ID of the Claimable Balance used to send the asset initially requested.
    public ?string $claimableBalanceId = null;


    //Fields for withdraw transactions

    /// If this is a withdrawal, this is the anchor's Stellar account that the user transferred (or will transfer) their asset to.
    public ?string $withdrawAnchorAccount = null;

    /// Memo used when the user transferred to withdraw_anchor_account.
    /// Assigned null if the withdraw is not ready to receive payment, for example if KYC is not completed.
    public ?string $withdrawMemo = null;

    /// Memo type for withdraw_memo.
    public ?string $withdrawMemoType = null;

    protected function loadFromJson(array $json) : void {
        if (isset($json['id'])) $this->id = $json['id'];
        if (isset($json['kind'])) $this->kind = $json['kind'];
        if (isset($json['status'])) $this->status = $json['status'];
        if (isset($json['status_eta'])) $this->statusEta = $json['status_eta'];
        if (isset($json['kyc_verified'])) $this->kycVerified = $json['kyc_verified'];
        if (isset($json['more_info_url'])) $this->moreInfoUrl = $json['more_info_url'];
        if (isset($json['amount_in'])) $this->amountIn = $json['amount_in'];
        if (isset($json['amount_in_asset'])) $this->amountInAsset = $json['amount_in_asset'];
        if (isset($json['amount_out'])) $this->amountOut = $json['amount_out'];
        if (isset($json['amount_out_asset'])) $this->amountOutAsset = $json['amount_out_asset'];
        if (isset($json['amount_fee'])) $this->amountFee = $json['amount_fee'];
        if (isset($json['amount_fee_asset'])) $this->amountFeeAsset = $json['amount_fee_asset'];
        if (isset($json['quote_id'])) $this->quoteId = $json['quote_id'];
        if (isset($json['started_at'])) $this->startedAt = $json['started_at'];
        if (isset($json['completed_at'])) $this->completedAt = $json['completed_at'];
        if (isset($json['updated_at'])) $this->updatedAt = $json['updated_at'];
        if (isset($json['stellar_transaction_id'])) $this->stellarTransactionId = $json['stellar_transaction_id'];
        if (isset($json['external_transaction_id'])) $this->externalTransactionId = $json['external_transaction_id'];
        if (isset($json['message'])) $this->message = $json['message'];
        if (isset($json['refunded'])) $this->refunded = $json['refunded'];
        if (isset($json['refunds'])) $this->refunds = Refund::fromJson($json['refunds']);
        if (isset($json['from'])) $this->from = $json['from'];
        if (isset($json['to'])) $this->to = $json['to'];
        if (isset($json['deposit_memo'])) $this->depositMemo = $json['deposit_memo'];
        if (isset($json['deposit_memo_type'])) $this->depositMemoType = $json['deposit_memo_type'];
        if (isset($json['claimable_balance_id'])) $this->claimableBalanceId = $json['claimable_balance_id'];
        if (isset($json['withdraw_anchor_account'])) $this->withdrawAnchorAccount = $json['withdraw_anchor_account'];
        if (isset($json['withdraw_memo'])) $this->withdrawMemo = $json['withdraw_memo'];
        if (isset($json['withdraw_memo_type'])) $this->withdrawMemoType = $json['withdraw_memo_type'];
    }

    public static function fromJson(array $json) : SEP24Transaction
    {
        $result = new SEP24Transaction();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * @param string $kind
     */
    public function setKind(string $kind): void
    {
        $this->kind = $kind;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return int|null
     */
    public function getStatusEta(): ?int
    {
        return $this->statusEta;
    }

    /**
     * @param int|null $statusEta
     */
    public function setStatusEta(?int $statusEta): void
    {
        $this->statusEta = $statusEta;
    }

    /**
     * @return bool|null
     */
    public function getKycVerified(): ?bool
    {
        return $this->kycVerified;
    }

    /**
     * @param bool|null $kycVerified
     */
    public function setKycVerified(?bool $kycVerified): void
    {
        $this->kycVerified = $kycVerified;
    }

    /**
     * @return string
     */
    public function getMoreInfoUrl(): string
    {
        return $this->moreInfoUrl;
    }

    /**
     * @param string $moreInfoUrl
     */
    public function setMoreInfoUrl(string $moreInfoUrl): void
    {
        $this->moreInfoUrl = $moreInfoUrl;
    }

    /**
     * @return string
     */
    public function getAmountIn(): string
    {
        return $this->amountIn;
    }

    /**
     * @param string $amountIn
     */
    public function setAmountIn(string $amountIn): void
    {
        $this->amountIn = $amountIn;
    }

    /**
     * @return string|null
     */
    public function getAmountInAsset(): ?string
    {
        return $this->amountInAsset;
    }

    /**
     * @param string|null $amountInAsset
     */
    public function setAmountInAsset(?string $amountInAsset): void
    {
        $this->amountInAsset = $amountInAsset;
    }

    /**
     * @return string
     */
    public function getAmountOut(): string
    {
        return $this->amountOut;
    }

    /**
     * @param string $amountOut
     */
    public function setAmountOut(string $amountOut): void
    {
        $this->amountOut = $amountOut;
    }

    /**
     * @return string|null
     */
    public function getAmountOutAsset(): ?string
    {
        return $this->amountOutAsset;
    }

    /**
     * @param string|null $amountOutAsset
     */
    public function setAmountOutAsset(?string $amountOutAsset): void
    {
        $this->amountOutAsset = $amountOutAsset;
    }

    /**
     * @return string
     */
    public function getAmountFee(): string
    {
        return $this->amountFee;
    }

    /**
     * @param string $amountFee
     */
    public function setAmountFee(string $amountFee): void
    {
        $this->amountFee = $amountFee;
    }

    /**
     * @return string|null
     */
    public function getAmountFeeAsset(): ?string
    {
        return $this->amountFeeAsset;
    }

    /**
     * @param string|null $amountFeeAsset
     */
    public function setAmountFeeAsset(?string $amountFeeAsset): void
    {
        $this->amountFeeAsset = $amountFeeAsset;
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
     * @return string
     */
    public function getStartedAt(): string
    {
        return $this->startedAt;
    }

    /**
     * @param string $startedAt
     */
    public function setStartedAt(string $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    /**
     * @return string|null
     */
    public function getCompletedAt(): ?string
    {
        return $this->completedAt;
    }

    /**
     * @param string|null $completedAt
     */
    public function setCompletedAt(?string $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    /**
     * @return string|null
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * @param string|null $updatedAt
     */
    public function setUpdatedAt(?string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * @return string|null
     */
    public function getStellarTransactionId(): ?string
    {
        return $this->stellarTransactionId;
    }

    /**
     * @param string|null $stellarTransactionId
     */
    public function setStellarTransactionId(?string $stellarTransactionId): void
    {
        $this->stellarTransactionId = $stellarTransactionId;
    }

    /**
     * @return string|null
     */
    public function getExternalTransactionId(): ?string
    {
        return $this->externalTransactionId;
    }

    /**
     * @param string|null $externalTransactionId
     */
    public function setExternalTransactionId(?string $externalTransactionId): void
    {
        $this->externalTransactionId = $externalTransactionId;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string|null $message
     */
    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return bool|null
     */
    public function getRefunded(): ?bool
    {
        return $this->refunded;
    }

    /**
     * @param bool|null $refunded
     */
    public function setRefunded(?bool $refunded): void
    {
        $this->refunded = $refunded;
    }

    /**
     * @return Refund|null
     */
    public function getRefunds(): ?Refund
    {
        return $this->refunds;
    }

    /**
     * @param Refund|null $refunds
     */
    public function setRefunds(?Refund $refunds): void
    {
        $this->refunds = $refunds;
    }

    /**
     * @return string|null
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * @param string|null $from
     */
    public function setFrom(?string $from): void
    {
        $this->from = $from;
    }

    /**
     * @return string|null
     */
    public function getTo(): ?string
    {
        return $this->to;
    }

    /**
     * @param string|null $to
     */
    public function setTo(?string $to): void
    {
        $this->to = $to;
    }

    /**
     * @return string|null
     */
    public function getDepositMemo(): ?string
    {
        return $this->depositMemo;
    }

    /**
     * @param string|null $depositMemo
     */
    public function setDepositMemo(?string $depositMemo): void
    {
        $this->depositMemo = $depositMemo;
    }

    /**
     * @return string|null
     */
    public function getDepositMemoType(): ?string
    {
        return $this->depositMemoType;
    }

    /**
     * @param string|null $depositMemoType
     */
    public function setDepositMemoType(?string $depositMemoType): void
    {
        $this->depositMemoType = $depositMemoType;
    }

    /**
     * @return string|null
     */
    public function getClaimableBalanceId(): ?string
    {
        return $this->claimableBalanceId;
    }

    /**
     * @param string|null $claimableBalanceId
     */
    public function setClaimableBalanceId(?string $claimableBalanceId): void
    {
        $this->claimableBalanceId = $claimableBalanceId;
    }

    /**
     * @return string|null
     */
    public function getWithdrawAnchorAccount(): ?string
    {
        return $this->withdrawAnchorAccount;
    }

    /**
     * @param string|null $withdrawAnchorAccount
     */
    public function setWithdrawAnchorAccount(?string $withdrawAnchorAccount): void
    {
        $this->withdrawAnchorAccount = $withdrawAnchorAccount;
    }

    /**
     * @return string|null
     */
    public function getWithdrawMemo(): ?string
    {
        return $this->withdrawMemo;
    }

    /**
     * @param string|null $withdrawMemo
     */
    public function setWithdrawMemo(?string $withdrawMemo): void
    {
        $this->withdrawMemo = $withdrawMemo;
    }

    /**
     * @return string|null
     */
    public function getWithdrawMemoType(): ?string
    {
        return $this->withdrawMemoType;
    }

    /**
     * @param string|null $withdrawMemoType
     */
    public function setWithdrawMemoType(?string $withdrawMemoType): void
    {
        $this->withdrawMemoType = $withdrawMemoType;
    }
}