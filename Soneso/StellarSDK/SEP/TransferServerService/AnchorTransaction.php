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
 * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md SEP-06 Specification
 * @see https://github.com/stellar/stellar-protocol/blob/v2.5.0/ecosystem/sep-0038.md SEP-38 v2.5.0 Quotes
 * @see AnchorTransactionResponse
 * @see AnchorTransactionsResponse
 * @see FeeDetails
 * @see TransactionRefunds
 */
class AnchorTransaction
{
    /**
     * @param string $id Unique, anchor-generated id for the deposit/withdrawal.
     * @param string $kind deposit, deposit-exchange, withdrawal or withdrawal-exchange.
     * @param string $status Processing status of deposit/withdrawal.
     * - incomplete: Missing required information (non-interactive)
     * - pending_user_transfer_start: Awaiting user to send funds to anchor
     * - pending_anchor: Anchor is processing the transaction
     * - pending_stellar: Transaction submitted to Stellar network
     * - pending_external: Waiting for external system (bank, crypto network)
     * - pending_trust: User must add trustline for the asset
     * - pending_user: Action required from user (deprecated, use specific status)
     * - completed: Transaction successfully completed
     * - refunded: Transaction refunded to user
     * - expired: Transaction expired without completion
     * - no_market: No market available for requested conversion (exchange operations)
     * - too_small: Transaction amount is below minimum
     * - too_large: Transaction amount exceeds maximum
     * - error: Unrecoverable error occurred.
     *
     * @see https://github.com/stellar/stellar-protocol/blob/v4.3.0/ecosystem/sep-0006.md#transaction-status
     * @param int|null $statusEta Estimated number of seconds until a status change is expected.
     * @param string|null $moreInfoUrl A URL the user can visit if they want more information
     * about their account / status.
     * @param string|null $amountIn Amount received by anchor at start of transaction as a
     * string with up to 7 decimals. Excludes any fees charged before the anchor received the funds.
     * Should be equals to quote.sell_asset if a quote_id was used.
     * @param string|null $amountInAsset The asset received or to be received by the Anchor. Must be present
     * if the deposit/withdraw was made using quotes. The value must be in SEP-38 Asset Identification Format.
     * @param string|null $amountOut Amount sent by anchor to user at end of transaction as a string with up
     * to 7 decimals. Excludes amount converted to XLM to fund account and any external fees.
     * Should be equals to quote.buy_asset if a quote_id was used.
     * @param string|null $amountOutAsset The asset delivered or to be delivered to the user. Must be present
     * if the deposit/withdraw was made using quotes. The value must be in SEP-38 Asset Identification Format.
     * @param string|null $amountFee (deprecated) Amount of fee charged by anchor. Should be equals to
     * quote.fee.total if a quote_id was used.
     * @param string|null $amountFeeAsset (deprecated) The asset in which fees are calculated in. Must be
     * present if the deposit/withdraw was made using quotes. The value must be in SEP-38 Asset Identification Format.
     * Should be equals to quote.fee.asset if a quote_id was used.
     * @param FeeDetails|null $feeDetails Description of fee charged by the anchor.
     * If quote_id is present, it should match the referenced quote's fee object.
     * @param string|null $quoteId The ID of the quote used to create this transaction. Should be present if
     * a quote_id was included in the POST /transactions request. Clients should be aware though that the quote_id may
     * not be present in older implementations.
     * @param string|null $from Sent from address (perhaps BTC, IBAN, or bank account in
     * the case of a deposit, Stellar address in the case of a withdrawal).
     * @param string|null $to Sent to address (perhaps BTC, IBAN, or bank account in
     * the case of a withdrawal, Stellar address in the case of a deposit).
     * @param string|null $externalExtra Extra information for the external account involved.
     * It could be a bank routing number, BIC, or store number for example.
     * @param string|null $externalExtraText Text version of external_extra. This is the name of the bank or store.
     * @param string|null $depositMemo If this is a deposit, this is the memo (if any)
     * used to transfer the asset to the Stellar address.
     * @param string|null $depositMemoType Type for the depositMemo.
     * @param string|null $withdrawAnchorAccount If this is a withdrawal, this is the anchor's Stellar account
     * that the user transferred (or will transfer) their issued asset to.
     * @param string|null $withdrawMemo Memo used when the user transferred to withdrawAnchorAccount.
     * @param string|null $withdrawMemoType Memo type for withdrawMemo.
     * @param string|null $startedAt Start date and time of transaction - UTC ISO 8601 string.
     * @param string|null $updatedAt The date and time of transaction reaching the current status.
     * @param string|null $completedAt Completion date and time of transaction - UTC ISO 8601 string.
     * @param string|null $userActionRequiredBy The date and time by when the user action is required.
     * In certain statuses, such as pending_user_transfer_start or incomplete, anchor waits for the user action.
     * user_action_required_by should only be specified for statuses where user action is required, and omitted for all other.
     * @param string|null $stellarTransactionId transaction_id on Stellar network of the transfer that either
     * completed the deposit or started the withdrawal.
     * @param string|null $externalTransactionId ID of transaction on external network that either started
     * the deposit or completed the withdrawal.
     * @param string|null $message Human readable explanation of transaction status, if needed.
     * @param bool|null $refunded (deprecated) This field is deprecated in favor of the refunds object.
     * True if the transaction was refunded in full. False if the transaction was partially refunded or not refunded.
     * @param TransactionRefunds|null $refunds An object describing any on or off-chain refund associated
     * with this transaction.
     * @param string|null $requiredInfoMessage A human-readable message indicating any errors that require
     * updated information from the user.
     * @param array<string, AnchorField>|null $requiredInfoUpdates A set of fields that require update from
     * the user described in the same format as /info. This field is only relevant when status is
     * pending_transaction_info_update.
     * @param array<string, DepositInstruction>|null $instructions JSON object containing the SEP-9 financial
     * account fields that describe how to complete the off-chain deposit in the same format as
     * the /deposit response. This field should be present if the instructions were provided in the /deposit response.
     * @param string|null $claimableBalanceId ID of the Claimable Balance used to send the asset initially
     * requested. Only relevant for deposit transactions.
     */
    public function __construct(
        public string $id,
        public string $kind,
        public string $status,
        public ?int $statusEta = null,
        public ?string $moreInfoUrl = null,
        public ?string $amountIn = null,
        public ?string $amountInAsset = null,
        public ?string $amountOut = null,
        public ?string $amountOutAsset = null,
        public ?string $amountFee = null,
        public ?string $amountFeeAsset = null,
        public ?FeeDetails $feeDetails = null,
        public ?string $quoteId = null,
        public ?string $from = null,
        public ?string $to = null,
        public ?string $externalExtra = null,
        public ?string $externalExtraText = null,
        public ?string $depositMemo = null,
        public ?string $depositMemoType = null,
        public ?string $withdrawAnchorAccount = null,
        public ?string $withdrawMemo = null,
        public ?string $withdrawMemoType = null,
        public ?string $startedAt = null,
        public ?string $updatedAt = null,
        public ?string $completedAt = null,
        public ?string $userActionRequiredBy = null,
        public ?string $stellarTransactionId = null,
        public ?string $externalTransactionId = null,
        public ?string $message = null,
        public ?bool $refunded = null,
        public ?TransactionRefunds $refunds = null,
        public ?string $requiredInfoMessage = null,
        public ?array $requiredInfoUpdates = null,
        public ?array $instructions = null,
        public ?string $claimableBalanceId = null,
    ) {
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