<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

use Soneso\StellarSDK\Responses\Response;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoField;

class AnchorTransaction extends Response
{
    /// Unique, anchor-generated id for the deposit/withdrawal.
    private string $id;

    /// deposit or withdrawal.
    private string $kind;

    /// Processing status of deposit/withdrawal.
    private string $status;

    /// (optional) Estimated number of seconds until a status change is expected.
    private ?int $statusEta = null;

    /// (optional) A URL the user can visit if they want more information about their account / status.
    private ?string $moreInfoUrl = null;

    /// (optional) Amount received by anchor at start of transaction as a string with up to 7 decimals. Excludes any fees charged before the anchor received the funds.
    private ?string $amountIn = null;

    /// (optional) Amount sent by anchor to user at end of transaction as a string with up to 7 decimals. Excludes amount converted to XLM to fund account and any external fees.
    private ?string $amountOut = null;

    /// (optional) Amount of fee charged by anchor.
    private ?string $amountFee = null;

    /// (optional) Sent from address (perhaps BTC, IBAN, or bank account in the case of a deposit, Stellar address in the case of a withdrawal).
    private ?string $from = null;

    /// (optional) Sent to address (perhaps BTC, IBAN, or bank account in the case of a withdrawal, Stellar address in the case of a deposit).
    private ?string $to = null;

    /// (optional) Extra information for the external account involved. It could be a bank routing number, BIC, or store number for example.
    private ?string $externalExtra = null;

    /// (optional) Text version of external_extra. This is the name of the bank or store
    private ?string $externalExtraText = null;

    /// (optional) If this is a deposit, this is the memo (if any) used to transfer the asset to the to Stellar address
    private ?string $depositMemo = null;

    /// (optional) Type for the deposit_memo.
    private ?string $depositMemoType = null;

    /// (optional) If this is a withdrawal, this is the anchor's Stellar account that the user transferred (or will transfer) their issued asset to.
    private ?string $withdrawAnchorAccount = null;

    /// (optional) Memo used when the user transferred to withdraw_anchor_account.
    private ?string $withdrawMemo = null;

    /// (optional) Memo type for withdraw_memo.
    private ?string $withdrawMemoType = null;

    /// (optional) Start date and time of transaction - UTC ISO 8601 string.
    private ?string $startedAt = null;

    /// (optional) Completion date and time of transaction - UTC ISO 8601 string.
    private ?string $completedAt = null;

    /// (optional) transaction_id on Stellar network of the transfer that either completed the deposit or started the withdrawal.
    private ?string $stellarTransactionId = null;

    /// (optional) ID of transaction on external network that either started the deposit or completed the withdrawal.
    private ?string $externalTransactionId = null;

    /// (optional) Human readable explanation of transaction status, if needed.
    private ?string $message = null;

    /// (optional) Should be true if the transaction was refunded. Not including this field means the transaction was not refunded.
    private ?bool $refunded = null;

    /// (optional) A human-readable message indicating any errors that require updated information from the user.
    private ?string $requiredInfoMessage = null;

    /// (optional) A set of fields that require update from the user described in the same format as /info. This field is only relevant when status is pending_transaction_info_update.
    private ?array $requiredInfoUpdates = null; //[string => AnchorField]

    /// (optional) ID of the Claimable Balance used to send the asset initially requested. Only relevant for deposit transactions
    private ?string $claimableBalanceId = null;

    /**
     * Unique, anchor-generated id for the deposit/withdrawal.
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * deposit or withdrawal.
     * @return string
     */
    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * Processing status of deposit/withdrawal.
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * (optional) Estimated number of seconds until a status change is expected.
     * @return int|null
     */
    public function getStatusEta(): ?int
    {
        return $this->statusEta;
    }

    /**
     * (optional) A URL the user can visit if they want more information about their account / status.
     * @return string|null
     */
    public function getMoreInfoUrl(): ?string
    {
        return $this->moreInfoUrl;
    }

    /**
     * (optional) Amount received by anchor at start of transaction as a string with up to 7 decimals. Excludes any fees charged before the anchor received the funds.
     * @return string|null
     */
    public function getAmountIn(): ?string
    {
        return $this->amountIn;
    }

    /**
     * (optional) Amount sent by anchor to user at end of transaction as a string with up to 7 decimals. Excludes amount converted to XLM to fund account and any external fees.
     * @return string|null
     */
    public function getAmountOut(): ?string
    {
        return $this->amountOut;
    }

    /**
     * (optional) Amount of fee charged by anchor.
     * @return string|null
     */
    public function getAmountFee(): ?string
    {
        return $this->amountFee;
    }

    /**
     * (optional) Sent from address (perhaps BTC, IBAN, or bank account in the case of a deposit, Stellar address in the case of a withdrawal).
     * @return string|null
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * (optional) Sent to address (perhaps BTC, IBAN, or bank account in the case of a withdrawal, Stellar address in the case of a deposit).
     * @return string|null
     */
    public function getTo(): ?string
    {
        return $this->to;
    }

    /**
     * (optional) Extra information for the external account involved. It could be a bank routing number, BIC, or store number for example.
     * @return string|null
     */
    public function getExternalExtra(): ?string
    {
        return $this->externalExtra;
    }

    /**
     *  (optional) Text version of external_extra. This is the name of the bank or store
     * @return string|null
     */
    public function getExternalExtraText(): ?string
    {
        return $this->externalExtraText;
    }

    /**
     * (optional) If this is a deposit, this is the memo (if any) used to transfer the asset to the to Stellar address
     * @return string|null
     */
    public function getDepositMemo(): ?string
    {
        return $this->depositMemo;
    }

    /**
     * (optional) Type for the deposit_memo.
     * @return string|null
     */
    public function getDepositMemoType(): ?string
    {
        return $this->depositMemoType;
    }

    /**
     * (optional) If this is a withdrawal, this is the anchor's Stellar account that the user transferred (or will transfer) their issued asset to.
     * @return string|null
     */
    public function getWithdrawAnchorAccount(): ?string
    {
        return $this->withdrawAnchorAccount;
    }

    /**
     * (optional) Memo used when the user transferred to withdraw_anchor_account.
     * @return string|null
     */
    public function getWithdrawMemo(): ?string
    {
        return $this->withdrawMemo;
    }

    /**
     * (optional) Memo type for withdraw_memo.
     * @return string|null
     */
    public function getWithdrawMemoType(): ?string
    {
        return $this->withdrawMemoType;
    }

    /**
     *  (optional) Start date and time of transaction - UTC ISO 8601 string.
     * @return string|null
     */
    public function getStartedAt(): ?string
    {
        return $this->startedAt;
    }

    /**
     * (optional) Completion date and time of transaction - UTC ISO 8601 string.
     * @return string|null
     */
    public function getCompletedAt(): ?string
    {
        return $this->completedAt;
    }

    /**
     * (optional) transaction_id on Stellar network of the transfer that either completed the deposit or started the withdrawal.
     * @return string|null
     */
    public function getStellarTransactionId(): ?string
    {
        return $this->stellarTransactionId;
    }

    /**
     * (optional) ID of transaction on external network that either started the deposit or completed the withdrawal.
     * @return string|null
     */
    public function getExternalTransactionId(): ?string
    {
        return $this->externalTransactionId;
    }

    /**
     *  (optional) Human readable explanation of transaction status, if needed.
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * (optional) Should be true if the transaction was refunded. Not including this field means the transaction was not refunded.
     * @return bool|null
     */
    public function getRefunded(): ?bool
    {
        return $this->refunded;
    }

    /**
     * (optional) A human-readable message indicating any errors that require updated information from the user.
     * @return string|null
     */
    public function getRequiredInfoMessage(): ?string
    {
        return $this->requiredInfoMessage;
    }

    /**
     * (optional) A set of fields that require update from the user described in the same format as /info. This field is only relevant when status is pending_transaction_info_update.
     * @return array|null [string => AnchorField] | null
     */
    public function getRequiredInfoUpdates(): ?array
    {
        return $this->requiredInfoUpdates;
    }

    /**
     * (optional) ID of the Claimable Balance used to send the asset initially requested. Only relevant for deposit transactions.
     * @return string|null
     */
    public function getClaimableBalanceId(): ?string
    {
        return $this->claimableBalanceId;
    }


    protected function loadFromJson(array $json) : void {
        if (isset($json['id'])) $this->id = $json['id'];
        if (isset($json['kind'])) $this->kind = $json['kind'];
        if (isset($json['status'])) $this->status = $json['status'];
        if (isset($json['status_eta'])) $this->statusEta = $json['status_eta'];
        if (isset($json['more_info_url'])) $this->moreInfoUrl = $json['more_info_url'];
        if (isset($json['amount_in'])) $this->amountIn = $json['amount_in'];
        if (isset($json['amount_out'])) $this->amountOut = $json['amount_out'];
        if (isset($json['amount_fee'])) $this->amountFee = $json['amount_fee'];
        if (isset($json['from'])) $this->from = $json['from'];
        if (isset($json['external_extra'])) $this->externalExtra = $json['external_extra'];
        if (isset($json['external_extra_text'])) $this->externalExtraText = $json['external_extra_text'];
        if (isset($json['deposit_memo'])) $this->depositMemo = $json['deposit_memo'];
        if (isset($json['deposit_memo_type'])) $this->depositMemoType = $json['deposit_memo_type'];
        if (isset($json['withdraw_anchor_account'])) $this->withdrawAnchorAccount = $json['withdraw_anchor_account'];
        if (isset($json['withdraw_memo'])) $this->withdrawMemo = $json['withdraw_memo'];
        if (isset($json['withdraw_memo_type'])) $this->withdrawMemoType = $json['withdraw_memo_type'];
        if (isset($json['started_at'])) $this->startedAt = $json['started_at'];
        if (isset($json['completed_at'])) $this->completedAt = $json['completed_at'];
        if (isset($json['stellar_transaction_id'])) $this->stellarTransactionId = $json['stellar_transaction_id'];
        if (isset($json['external_transaction_id'])) $this->externalTransactionId = $json['external_transaction_id'];
        if (isset($json['message'])) $this->message = $json['message'];
        if (isset($json['refunded'])) $this->refunded = $json['refunded'];
        if (isset($json['required_info_message'])) $this->requiredInfoMessage = $json['required_info_message'];
        if (isset($json['claimable_balance_id'])) $this->id = $json['claimable_balance_id'];
        if (isset($json['required_info_updates']) && isset($json['required_info_updates']['transaction'])) {
            $transaction = $json['required_info_updates']['transaction'];
            $this->requiredInfoUpdates = array();
            foreach(array_keys($transaction) as $key) {
                $value = AnchorField::fromJson($transaction[$key]);
                $this->requiredInfoUpdates += [$key => $value];
            }
        }
    }

    public static function fromJson(array $json) : AnchorTransaction
    {
        $result = new AnchorTransaction();
        $result->loadFromJson($json);
        return $result;
    }
}