<?php declare(strict_types=1);

// Copyright 2022 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\TransferServerService;

/**
 * Represents a single deposit or withdrawal transaction processed by an anchor.
 *
 * Contains comprehensive transaction details including status, amounts, fees, timestamps,
 * account information, and any additional data needed to track or complete the transaction.
 *
 * Used in transaction endpoint responses to provide detailed information about specific
 * operations. Includes support for refunds, quotes (SEP-38), and status updates.
 *
 * Transaction kinds: deposit, deposit-exchange, withdrawal, withdrawal-exchange.
 *
 * @package Soneso\StellarSDK\SEP\TransferServerService
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0006.md SEP-06 Specification
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md SEP-38 Quotes
 * @see AnchorTransactionResponse
 * @see AnchorTransactionsResponse
 * @see FeeDetails
 * @see TransactionRefunds
 */
class AnchorTransaction
{
    /**
     * @var string $id Unique, anchor-generated id for the deposit/withdrawal.
     */
    public string $id;

    /**
     * @var string $kind deposit, deposit-exchange, withdrawal or withdrawal-exchange.
     */
    public string $kind;

    /**
     * @var string Processing status of deposit/withdrawal.
     */
    public string $status;

    /**
     * @var int|null $statusEta (optional) Estimated number of seconds until a status change is expected.
     */
    public ?int $statusEta = null;

    /**
     * @var string|null $moreInfoUrl (optional) A URL the user can visit if they want more information
     * about their account / status.
     */
    public ?string $moreInfoUrl = null;

    /**
     * @var string|null $amountIn (optional) Amount received by anchor at start of transaction as a
     * string with up to 7 decimals. Excludes any fees charged before the anchor received the funds. Should be equals
     * to quote.sell_asset if a quote_id was used.
     */
    public ?string $amountIn = null;

    /**
     * @var string|null $amountInAsset (optional) The asset received or to be received by the Anchor. Must be present
     * if the deposit/withdraw was made using quotes. The value must be in SEP-38 Asset Identification Format.
     */
    public ?string $amountInAsset = null;

    /**
     * @var string|null $amountOut (optional) Amount sent by anchor to user at end of transaction as a string with up
     * to 7 decimals. Excludes amount converted to XLM to fund account and any external fees. Should be equals to
     * quote.buy_asset if a quote_id was used.
     */
    public ?string $amountOut = null;

    /**
     * @var string|null $amountOutAsset (optional) The asset delivered or to be delivered to the user. Must be present
     * if the deposit/withdraw was made using quotes. The value must be in SEP-38 Asset Identification Format.
     */
    public ?string $amountOutAsset = null;

    /**
     * @var string|null $amountFee (deprecated, optional) Amount of fee charged by anchor. Should be equals to
     * quote.fee.total if a quote_id was used.
     */
    public ?string $amountFee = null;

    /**
     * @var string|null $amountFeeAsset (deprecated, optional) The asset in which fees are calculated in. Must be
     * present if the deposit/withdraw was made using quotes. The value must be in SEP-38 Asset Identification Format.
     * Should be equals to quote.fee.asset if a quote_id was used.
     */
    public ?string $amountFeeAsset = null;

    /**
     * @var FeeDetails|null $feeDetails Description of fee charged by the anchor.
     * If quote_id is present, it should match the referenced quote's fee object.
     */
    public ?FeeDetails $feeDetails = null;

    /**
     * @var string|null $quoteId (optional) The ID of the quote used to create this transaction.
     * Should be present if a quote_id was included in the POST /transactions request. Clients should be aware though
     * that the quote_id may not be present in older implementations.
     */
    public ?string $quoteId = null;

    /**
     * @var string|null $from (optional) Sent from address (perhaps BTC, IBAN, or bank account in
     * the case of a deposit, Stellar address in the case of a withdrawal).
     */
    public ?string $from = null;

    /**
     * @var string|null $to (optional) Sent to address (perhaps BTC, IBAN, or bank account in
     * the case of a withdrawal, Stellar address in the case of a deposit).
     */
    public ?string $to = null;

    /**
     * @var string|null $externalExtra (optional) Extra information for the external account involved.
     * It could be a bank routing number, BIC, or store number for example.
     */
    public ?string $externalExtra = null;

    /**
     * @var string|null $externalExtraText (optional) Text version of external_extra.
     * This is the name of the bank or store
     */
    public ?string $externalExtraText = null;

    /**
     * @var string|null $depositMemo (optional) If this is a deposit, this is the memo (if any)
     * used to transfer the asset to the to Stellar address
     */
    public ?string $depositMemo = null;

    /**
     * @var string|null $depositMemoType (optional) Type for the depositMemo.
     */
    public ?string $depositMemoType = null;

    /**
     * @var string|null $withdrawAnchorAccount (optional) If this is a withdrawal, this is the anchor's Stellar account
     * that the user transferred (or will transfer) their issued asset to.
     */
    public ?string $withdrawAnchorAccount = null;

    /**
     * @var string|null $withdrawMemo (optional) Memo used when the user transferred to withdrawAnchorAccount.
     */
    public ?string $withdrawMemo = null;

    /**
     * @var string|null $withdrawMemoType (optional) Memo type for withdrawMemo.
     */
    public ?string $withdrawMemoType = null;

    /**
     * @var string|null $startedAt (optional) Start date and time of transaction - UTC ISO 8601 string.
     */
    public ?string $startedAt = null;

    /**
     * @var string|null $updatedAt (optional) The date and time of transaction reaching the current status.
     */
    public ?string $updatedAt = null;

    /**
     * @var string|null $completedAt (optional) Completion date and time of transaction - UTC ISO 8601 string.
     */
    public ?string $completedAt = null;

    /**
     * @var string|null $userActionRequiredBy (optional) The date and time by when the user action is required.
     *  In certain statuses, such as pending_user_transfer_start or incomplete, anchor waits for the user action and
     *  user_action_required_by field should be used to show the time anchors gives for the user to make an action
     *  before transaction will automatically be moved into a different status (such as expired or to be refunded).
     *  user_action_required_by should only be specified for statuses where user action is required, and omitted for
     *  all other. Anchor should specify the action waited on using message or more_info_url.
     */
    public ?string $userActionRequiredBy = null;

    /**
     * @var string|null $stellarTransactionId (optional) transaction_id on Stellar network of the transfer that either
     * completed the deposit or started the withdrawal.
     */
    public ?string $stellarTransactionId = null;

    /**
     * @var string|null $externalTransactionId (optional) ID of transaction on external network that either started
     * the deposit or completed the withdrawal.
     */
    public ?string $externalTransactionId = null;

    /**
     * @var string|null $message (optional) Human readable explanation of transaction status, if needed.
     */
    public ?string $message = null;

    /**
     * @var bool|null $refunded (deprecated, optional) This field is deprecated in favor of the refunds
     * object. True if the transaction was refunded in full. False if the transaction was partially refunded or not
     * refunded. For more details about any refunds, see the refunds object.
     */
    public ?bool $refunded = null;

    /**
     * @var TransactionRefunds|null $refunds (optional) An object describing any on or off-chain refund associated
     * with this transaction.
     */
    public ?TransactionRefunds $refunds = null;


    /**
     * @var string|null $requiredInfoMessage (optional) A human-readable message indicating any errors that require
     * updated information from the user.
     */
    public ?string $requiredInfoMessage = null;

    /**
     * @var array<string, AnchorField>|null $requiredInfoUpdates (optional) A set of fields that require update from
     * the user described in the same format as /info. This field is only relevant when status is
     * pending_transaction_info_update.
     */
    public ?array $requiredInfoUpdates = null;

    /**
     * @var array<string, DepositInstruction>|null $instructions (optional) JSON object containing the SEP-9 financial
     * account fields that describe how to complete the off-chain deposit in the same format as
     * the /deposit response. This field should be present if the instructions were provided in the /deposit response
     * or if it could not have been previously provided synchronously. This field should only be present
     * once the status becomes pending_user_transfer_start, not while the transaction has any statuses that precede it
     * such as incomplete, pending_anchor, or pending_customer_info_update.
     */
    public ?array $instructions = null;

    /**
     * @var string|null $claimableBalanceId (optional) ID of the Claimable Balance used to send the asset initially
     * requested. Only relevant for deposit transactions.
     */
    public ?string $claimableBalanceId = null;

    /**
     * @param string $id Unique, anchor-generated id for the deposit/withdrawal.
     * @param string $kind deposit, deposit-exchange, withdrawal or withdrawal-exchange.
     * @param string $status Processing status of deposit/withdrawal.
     * @param int|null $statusEta (optional) Estimated number of seconds until a status change is expected.
     * @param string|null $moreInfoUrl (optional) A URL the user can visit if they want more information
     * about their account / status.
     * @param string|null $amountIn (optional) Amount received by anchor at start of transaction as a
     * string with up to 7 decimals. Excludes any fees charged before the anchor received the funds. Should be equals
     * to quote.sell_asset if a quote_id was used.
     * @param string|null $amountInAsset (optional) The asset received or to be received by the Anchor. Must be present
     * if the deposit/withdraw was made using quotes. The value must be in SEP-38 Asset Identification Format.
     * @param string|null $amountOut (optional) Amount sent by anchor to user at end of transaction as
     * a string with up to 7 decimals. Excludes amount converted to XLM to fund account and any external fees.
     * Should be equals to quote.buy_asset if a quote_id was used.
     * @param string|null $amountOutAsset (optional) The asset delivered or to be delivered to the user. Must be
     * present if the deposit/withdraw was made using quotes. The value must be in SEP-38 Asset Identification Format.
     * @param string|null $amountFee (deprecated, optional) Amount of fee charged by anchor. Should be equals to
     * quote.fee.total if a quote_id was used.
     * @param string|null $amountFeeAsset (deprecated, optional) The asset in which fees are calculated in.
     * Must be present if the deposit/withdraw was made using quotes. The value must be in SEP-38 Asset Identification
     * Format. Should be equals to quote.fee.asset if a quote_id was used.
     * @param FeeDetails|null $feeDetails Description of fee charged by the anchor. If quote_id is present, it should
     * match the referenced quote's fee object.
     * @param string|null $quoteId (optional) The ID of the quote used to create this transaction. Should be present if
     * a quote_id was included in the POST /transactions request. Clients should be aware though that the quote_id may
     * not be present in older implementations.
     * @param string|null $from (optional) Sent from address (perhaps BTC, IBAN, or bank account in
     * the case of a deposit, Stellar address in the case of a withdrawal).
     * @param string|null $to (optional) Sent to address (perhaps BTC, IBAN, or bank account in
     * the case of a withdrawal, Stellar address in the case of a deposit).
     * @param string|null $externalExtra (optional) Extra information for the external account involved.
     * It could be a bank routing number, BIC, or store number for example.
     * @param string|null $externalExtraText (optional) Text version of external_extra. This is the name of the bank
     * or store
     * @param string|null $depositMemo (optional) If this is a deposit, this is the memo (if any)
     * used to transfer the asset to the to Stellar address
     * @param string|null $depositMemoType (optional) Type for the depositMemo.
     * @param string|null $withdrawAnchorAccount (optional) If this is a withdrawal, this is the anchor's Stellar account
     * that the user transferred (or will transfer) their issued asset to.
     * @param string|null $withdrawMemo (optional) Memo used when the user transferred to withdrawAnchorAccount.
     * @param string|null $withdrawMemoType (optional) Memo type for withdrawMemo.
     * @param string|null $startedAt (optional) Start date and time of transaction - UTC ISO 8601 string.
     * @param string|null $updatedAt (optional) The date and time of transaction reaching the current status.
     * @param string|null $completedAt (optional) Completion date and time of transaction - UTC ISO 8601 string.
     * @param string|null $userActionRequiredBy (optional) The date and time by when the user action is required.
     * In certain statuses, such as pending_user_transfer_start or incomplete, anchor waits for the user action and
     * user_action_required_by field should be used to show the time anchors gives for the user to make an action
     * before transaction will automatically be moved into a different status (such as expired or to be refunded).
     * user_action_required_by should only be specified for statuses where user action is required, and omitted for
     * all other. Anchor should specify the action waited on using message or more_info_url.
     * @param string|null $stellarTransactionId (optional) transaction_id on Stellar network of the transfer that either
     * completed the deposit or started the withdrawal.
     * @param string|null $externalTransactionId (optional) ID of transaction on external network that either started
     * the deposit or completed the withdrawal.
     * @param string|null $message (optional) Human readable explanation of transaction status, if needed.
     * @param bool|null $refunded (deprecated, optional) This field is deprecated in favor of the refunds
     * object. True if the transaction was refunded in full. False if the transaction was partially refunded or not
     * refunded. For more details about any refunds, see the refunds object.
     * @param TransactionRefunds|null $refunds (optional) An object describing any on or off-chain refund associated
     * with this transaction.
     * @param string|null $requiredInfoMessage (optional) A human-readable message indicating any errors that require
     * updated information from the user.
     * @param array<string, AnchorField>|null $requiredInfoUpdates (optional) A set of fields that require update from
     * the user described in the same format as /info. This field is only relevant when status is
     * pending_transaction_info_update.
     * @param array<string, DepositInstruction>|null $instructions (optional) JSON object containing the SEP-9 financial
     * account fields that describe how to complete the off-chain deposit in the same format as
     * the /deposit response. This field should be present if the instructions were provided in the /deposit response
     * or if it could not have been previously provided synchronously. This field should only be present
     * once the status becomes pending_user_transfer_start, not while the transaction has any statuses that precede it
     * such as incomplete, pending_anchor, or pending_customer_info_update.
     * @param string|null $claimableBalanceId (optional) ID of the Claimable Balance used to send the asset initially
     * requested. Only relevant for deposit transactions.
     */
    public function __construct(
        string $id,
        string $kind,
        string $status,
        ?int $statusEta = null,
        ?string $moreInfoUrl = null,
        ?string $amountIn = null,
        ?string $amountInAsset = null,
        ?string $amountOut = null,
        ?string $amountOutAsset = null,
        ?string $amountFee = null,
        ?string $amountFeeAsset = null,
        ?FeeDetails $feeDetails = null,
        ?string $quoteId = null,
        ?string $from = null,
        ?string $to = null,
        ?string $externalExtra = null,
        ?string $externalExtraText = null,
        ?string $depositMemo = null,
        ?string $depositMemoType = null,
        ?string $withdrawAnchorAccount = null,
        ?string $withdrawMemo = null,
        ?string $withdrawMemoType = null,
        ?string $startedAt = null,
        ?string $updatedAt = null,
        ?string $completedAt = null,
        ?string $userActionRequiredBy = null,
        ?string $stellarTransactionId = null,
        ?string $externalTransactionId = null,
        ?string $message = null,
        ?bool $refunded = null,
        ?TransactionRefunds $refunds = null,
        ?string $requiredInfoMessage = null,
        ?array $requiredInfoUpdates = null,
        ?array $instructions = null,
        ?string $claimableBalanceId = null)
    {
        $this->id = $id;
        $this->kind = $kind;
        $this->status = $status;
        $this->statusEta = $statusEta;
        $this->moreInfoUrl = $moreInfoUrl;
        $this->amountIn = $amountIn;
        $this->amountInAsset = $amountInAsset;
        $this->amountOut = $amountOut;
        $this->amountOutAsset = $amountOutAsset;
        $this->amountFee = $amountFee;
        $this->amountFeeAsset = $amountFeeAsset;
        $this->feeDetails = $feeDetails;
        $this->quoteId = $quoteId;
        $this->from = $from;
        $this->to = $to;
        $this->externalExtra = $externalExtra;
        $this->externalExtraText = $externalExtraText;
        $this->depositMemo = $depositMemo;
        $this->depositMemoType = $depositMemoType;
        $this->withdrawAnchorAccount = $withdrawAnchorAccount;
        $this->withdrawMemo = $withdrawMemo;
        $this->withdrawMemoType = $withdrawMemoType;
        $this->startedAt = $startedAt;
        $this->updatedAt = $updatedAt;
        $this->completedAt = $completedAt;
        $this->userActionRequiredBy = $userActionRequiredBy;
        $this->stellarTransactionId = $stellarTransactionId;
        $this->externalTransactionId = $externalTransactionId;
        $this->message = $message;
        $this->refunded = $refunded;
        $this->refunds = $refunds;
        $this->requiredInfoMessage = $requiredInfoMessage;
        $this->requiredInfoUpdates = $requiredInfoUpdates;
        $this->instructions = $instructions;
        $this->claimableBalanceId = $claimableBalanceId;
    }

    /**
     * Constructs a new instance of AnchorTransaction by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return AnchorTransaction the object containing the parsed data.
     */
    public static function fromJson(array $json) : AnchorTransaction
    {
        $result = new AnchorTransaction($json['id'], $json['kind'], $json['status']);

        if (isset($json['status_eta'])) $result->statusEta = $json['status_eta'];
        if (isset($json['more_info_url'])) $result->moreInfoUrl = $json['more_info_url'];
        if (isset($json['amount_in'])) $result->amountIn = $json['amount_in'];
        if (isset($json['amount_in_asset'])) $result->amountInAsset = $json['amount_in_asset'];
        if (isset($json['amount_out'])) $result->amountOut = $json['amount_out'];
        if (isset($json['amount_out_asset'])) $result->amountOutAsset = $json['amount_out_asset'];
        if (isset($json['amount_fee'])) $result->amountFee = $json['amount_fee'];
        if (isset($json['amount_fee_asset'])) $result->amountFeeAsset = $json['amount_fee_asset'];
        if (isset($json['fee_details'])) $result->feeDetails = FeeDetails::fromJson($json['fee_details']);
        if (isset($json['quote_id'])) $result->quoteId = $json['quote_id'];
        if (isset($json['from'])) $result->from = $json['from'];
        if (isset($json['to'])) $result->to = $json['to'];
        if (isset($json['external_extra'])) $result->externalExtra = $json['external_extra'];
        if (isset($json['external_extra_text'])) $result->externalExtraText = $json['external_extra_text'];
        if (isset($json['deposit_memo'])) $result->depositMemo = $json['deposit_memo'];
        if (isset($json['deposit_memo_type'])) $result->depositMemoType = $json['deposit_memo_type'];
        if (isset($json['withdraw_anchor_account'])) $result->withdrawAnchorAccount = $json['withdraw_anchor_account'];
        if (isset($json['withdraw_memo'])) $result->withdrawMemo = $json['withdraw_memo'];
        if (isset($json['withdraw_memo_type'])) $result->withdrawMemoType = $json['withdraw_memo_type'];
        if (isset($json['started_at'])) $result->startedAt = $json['started_at'];
        if (isset($json['updated_at'])) $result->updatedAt = $json['updated_at'];
        if (isset($json['completed_at'])) $result->completedAt = $json['completed_at'];
        if (isset($json['user_action_required_by'])) $result->userActionRequiredBy = $json['user_action_required_by'];
        if (isset($json['stellar_transaction_id'])) $result->stellarTransactionId = $json['stellar_transaction_id'];
        if (isset($json['external_transaction_id'])) $result->externalTransactionId = $json['external_transaction_id'];
        if (isset($json['message'])) $result->message = $json['message'];
        if (isset($json['refunded'])) $result->refunded = $json['refunded'];
        if (isset($json['refunds'])) $result->refunds = TransactionRefunds::fromJson($json['refunds']);
        if (isset($json['required_info_message'])) $result->requiredInfoMessage = $json['required_info_message'];
        if (isset($json['instructions'])) {
            $result->instructions = array();
            $jsonFields = $json['instructions'];
            foreach(array_keys($jsonFields) as $key) {
                $value = DepositInstruction::fromJson($jsonFields[$key]);
                $result->instructions += [$key => $value];
            }
        }

        if (isset($json['required_info_updates']) && isset($json['required_info_updates']['transaction'])) {
            $transaction = $json['required_info_updates']['transaction'];
            $result->requiredInfoUpdates = array();
            foreach(array_keys($transaction) as $key) {
                $value = AnchorField::fromJson($transaction[$key]);
                $result->requiredInfoUpdates += [$key => $value];
            }
        }
        if (isset($json['claimable_balance_id'])) $result->claimableBalanceId = $json['claimable_balance_id'];

        return $result;
    }
}