<?php declare(strict_types=1);

// Copyright 2023 The Stellar PHP SDK Authors. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace Soneso\StellarSDK\SEP\Interactive;

use Soneso\StellarSDK\Responses\Response;

/**
 * Represents a SEP-24 hosted deposit/withdrawal transaction
 *
 * This class encapsulates the complete state of an interactive deposit or withdrawal
 * transaction as defined by SEP-24 (Hosted Deposit and Withdrawal). It contains all
 * transaction details including status, amounts, timestamps, and transfer information
 * for both deposit and withdrawal operations.
 *
 * SEP-24 enables anchors to provide an interactive user experience for deposit and
 * withdrawal flows, typically through a web interface. This class represents the
 * transaction data returned by the anchor during transaction queries and status updates.
 *
 * The transaction can represent either a deposit (external asset to Stellar) or a
 * withdrawal (Stellar asset to external system), with transaction-specific fields
 * available depending on the kind of operation.
 *
 * @package Soneso\StellarSDK\SEP\Interactive
 * @see https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md SEP-24 Specification
 * @see InteractiveService For initiating SEP-24 transactions
 * @see SEP24DepositRequest For deposit request parameters
 * @see SEP24WithdrawRequest For withdrawal request parameters
 */
class SEP24Transaction extends Response
{
    /**
     * @var string $id Unique, anchor-generated id for the deposit/withdrawal.
     */
    public string $id;

    /**
     * @var string $kind Possible values: 'deposit' or 'withdrawal'.
     */
    public string $kind;

    /**
     * @var string $status Processing status of deposit/withdrawal.
     * For possible values see: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#transaction-history
     */
    public string $status;

    /**
     * @var int|null $statusEta (optional) Estimated number of seconds until a status change is expected.
     */
    public ?int $statusEta = null;

    /**
     * @var bool|null $kycVerified (optional) True if the anchor has verified the user's KYC information for this transaction.
     */
    public ?bool $kycVerified = null;

    /**
     * @var string|null $moreInfoUrl A URL that is opened by wallets after the interactive flow is complete.
     * It can include banking information for users to start deposits, the status of the transaction,
     * or any other information the user might need to know about the transaction.
     */
    public ?string $moreInfoUrl = null;

    /**
     * @var string|null $amountIn Amount received by anchor at start of transaction as a string with up to 7 decimals.
     * Excludes any fees charged before the anchor received the funds.
     */
    public ?string $amountIn = null;

    /**
     * @var string|null $amountInAsset (optional)  The asset received or to be received by the Anchor.
     * Should be present if the deposit/withdraw was made using non-equivalent assets.
     * The value must be in SEP-38 Asset Identification Format.
     * https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format
     * See also the Asset Exchanges section for more information.
     * https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#asset-exchanges
     */
    public ?string $amountInAsset = null;

    /**
     * @var string|null $amountOut Amount sent by anchor to user at end of transaction as a string with up to 7 decimals.
     * Excludes amount converted to XLM to fund account and any external fees.
     */
    public ?string $amountOut = null;

    /**
     * @var string|null $amountOutAsset (optional) The asset delivered or to be delivered to the user.
     * Should be present if the deposit/withdraw was made using non-equivalent assets.
     * The value must be in SEP-38 Asset Identification Format.
     * https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format
     * See also the Asset Exchanges section for more information.
     * https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#asset-exchanges
     */
    public ?string $amountOutAsset = null;

    /**
     * @var string|null $amountFee Amount of fee charged by anchor.
     */
    public ?string $amountFee = null;

    /**
     * @var string|null $amountFeeAsset (optional) The asset in which fees are calculated in.
     * Should be present if the deposit/withdraw was made using non-equivalent assets.
     * The value must be in SEP-38 Asset Identification Format.
     * https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format
     * See also the Asset Exchanges section for more information.
     * https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#asset-exchanges
     */
    public ?string $amountFeeAsset = null;

    /**
     * @var string|null $quoteId (optional) The ID of the quote (sep-38) used when creating this transaction.
     * Should be present if a quote_id was included in the POST /transactions/deposit/interactive
     * or POST /transactions/withdraw/interactive request.
     * Clients should be aware that the quote_id may not be present in older implementations.
     */
    public ?string $quoteId = null;

    /**
     * @var string $startedAt Start date and time of transaction. UTC ISO 8601 string
     */
    public string $startedAt;

    /**
     * @var string|null $completedAt (optional) The date and time of transaction reaching completed or refunded status. UTC ISO 8601 string
     */
    public ?string $completedAt = null;

    /**
     * @var string|null $updatedAt (optional) The date and time of transaction reaching the current status. UTC ISO 8601 string
     */
    public ?string $updatedAt = null;

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
     * @var string|null $stellarTransactionId (optional) transaction_id on Stellar network of the transfer that either completed the deposit or started the withdrawal.
     */
    public ?string $stellarTransactionId = null;

    /**
     * @var string|null $externalTransactionId (optional) ID of transaction on external network that either started the deposit or completed the withdrawal.
     */
    public ?string $externalTransactionId = null;

    /**
     * @var string|null $message (optional) Human readable explanation of transaction status, if needed.
     */
    public ?string $message = null;

    /**
     * @var bool|null $refunded (deprecated, optional) This field is deprecated in favor of the refunds object and the refunded status.
     * True if the transaction was refunded in full. False if the transaction was partially refunded or not refunded.
     * For more details about any refunds, see the Refund object.
     */
    public ?bool $refunded = null;

    /**
     * @var Refund|null $refunds (optional) An object describing any on or off-chain refund associated with this transaction.
     */
    public ?Refund $refunds = null;

    /**
     * @var string|null $from In case of deposit: Sent from address, perhaps BTC, IBAN, or bank account.
     * In case of withdraw: Stellar address the assets were withdrawn from.
     */
    public ?string $from = null;

    /**
     * @var string|null $to In case of deposit: Stellar address the deposited assets were sent to.
     * In case of withdraw: Sent to address (perhaps BTC, IBAN, or bank account in the case of a withdrawal,
     * Stellar address in the case of a deposit).
     */
    public ?string $to = null;

    //Fields for deposit transactions

    /**
     * @var string|null $depositMemo (optional) Only for deposit transactions:
     * This is the memo (if any) used to transfer the asset to the to Stellar address.
     */
    public ?string $depositMemo = null;

    /**
     * @var string|null $depositMemoType (optional) Only for deposit transactions:
     * Type for the depositMemo.
     */
    public ?string $depositMemoType = null;

    /**
     * @var string|null $claimableBalanceId (optional) Only for deposit transactions:
     * ID of the Claimable Balance used to send the asset initially requested.
     */
    public ?string $claimableBalanceId = null;


    //Fields for withdraw transactions

    /**
     * @var string|null $withdrawAnchorAccount (optional) Only for withdraw transactions:
     * If this is a withdrawal, this is the anchor's Stellar account that the user transferred (or will transfer) their asset to.
     */
    public ?string $withdrawAnchorAccount = null;

    /**
     * @var string|null $withdrawMemo (optional) Only for withdraw transactions:
     * Memo used when the user transferred to withdrawAnchorAccount.
     * Assigned null if the withdrawal is not ready to receive payment, for example if KYC is not completed.
     */
    public ?string $withdrawMemo = null;

    /**
     * @var string|null $withdrawMemoType (optional) Only for withdraw transactions:
     * Memo type for withdrawMemo.
     */
    public ?string $withdrawMemoType = null;

    /**
     * Loads the needed data from a json array.
     * @param array<array-key, mixed> $json the data array to read from.
     * @return void
     */
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
        if (isset($json['user_action_required_by'])) $this->userActionRequiredBy = $json['user_action_required_by'];
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

    /**
     * Constructs a new instance of SEP24Transaction by using the given data.
     * @param array<array-key, mixed> $json the data to construct the object from.
     * @return SEP24Transaction the object containing the parsed data.
     */
    public static function fromJson(array $json) : SEP24Transaction
    {
        $result = new SEP24Transaction();
        $result->loadFromJson($json);
        return $result;
    }

    /**
     * @return string Unique, anchor-generated id for the deposit/withdrawal.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id Unique, anchor-generated id for the deposit/withdrawal.
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string Possible values: 'deposit' or 'withdrawal'.
     */
    public function getKind(): string
    {
        return $this->kind;
    }

    /**
     * @param string $kind Possible values: 'deposit' or 'withdrawal'.
     */
    public function setKind(string $kind): void
    {
        $this->kind = $kind;
    }

    /**
     * @return string Processing status of deposit/withdrawal.
     * For possible values see: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#transaction-history
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status Processing status of deposit/withdrawal.
     *  For possible values see: https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#transaction-history
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * @return int|null (optional) Estimated number of seconds until a status change is expected.
     */
    public function getStatusEta(): ?int
    {
        return $this->statusEta;
    }

    /**
     * @param int|null $statusEta (optional) Estimated number of seconds until a status change is expected.
     */
    public function setStatusEta(?int $statusEta): void
    {
        $this->statusEta = $statusEta;
    }

    /**
     * @return bool|null (optional) True if the anchor has verified the user's KYC information for this transaction.
     */
    public function getKycVerified(): ?bool
    {
        return $this->kycVerified;
    }

    /**
     * @param bool|null $kycVerified (optional) True if the anchor has verified the user's KYC information for this transaction.
     */
    public function setKycVerified(?bool $kycVerified): void
    {
        $this->kycVerified = $kycVerified;
    }

    /**
     * @return string|null A URL that is opened by wallets after the interactive flow is complete.
     *  It can include banking information for users to start deposits, the status of the transaction,
     *  or any other information the user might need to know about the transaction.
     */
    public function getMoreInfoUrl(): ?string
    {
        return $this->moreInfoUrl;
    }

    /**
     * @param ?string $moreInfoUrl A URL that is opened by wallets after the interactive flow is complete.
     *   It can include banking information for users to start deposits, the status of the transaction,
     *   or any other information the user might need to know about the transaction.
     */
    public function setMoreInfoUrl(?string $moreInfoUrl): void
    {
        $this->moreInfoUrl = $moreInfoUrl;
    }

    /**
     * @return string|null Amount received by anchor at start of transaction as a string with up to 7 decimals.
     *  Excludes any fees charged before the anchor received the funds.
     */
    public function getAmountIn(): ?string
    {
        return $this->amountIn;
    }

    /**
     * @param ?string $amountIn Amount received by anchor at start of transaction as a string with up to 7 decimals.
     *   Excludes any fees charged before the anchor received the funds.
     */
    public function setAmountIn(?string $amountIn): void
    {
        $this->amountIn = $amountIn;
    }

    /**
     * @return string|null The asset received or to be received by the Anchor.
     *  Should be present if the deposit/withdraw was made using non-equivalent assets.
     *  The value must be in SEP-38 Asset Identification Format.
     *  https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format
     *  See also the Asset Exchanges section for more information.
     *  https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#asset-exchanges
     */
    public function getAmountInAsset(): ?string
    {
        return $this->amountInAsset;
    }

    /**
     * @param string|null $amountInAsset The asset received or to be received by the Anchor.
     *   Should be present if the deposit/withdraw was made using non-equivalent assets.
     *   The value must be in SEP-38 Asset Identification Format.
     *   https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format
     *   See also the Asset Exchanges section for more information.
     *   https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#asset-exchanges
     */
    public function setAmountInAsset(?string $amountInAsset): void
    {
        $this->amountInAsset = $amountInAsset;
    }

    /**
     * @return ?string Amount sent by anchor to user at end of transaction as a string with up to 7 decimals.
     *  Excludes amount converted to XLM to fund account and any external fees.
     */
    public function getAmountOut(): ?string
    {
        return $this->amountOut;
    }

    /**
     * @param ?string $amountOut Amount sent by anchor to user at end of transaction as a string with up to 7 decimals.
     *   Excludes amount converted to XLM to fund account and any external fees.
     */
    public function setAmountOut(?string $amountOut): void
    {
        $this->amountOut = $amountOut;
    }

    /**
     * @return string|null The asset delivered or to be delivered to the user.
     *  Should be present if the deposit/withdraw was made using non-equivalent assets.
     *  The value must be in SEP-38 Asset Identification Format.
     *  https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format
     *  See also the Asset Exchanges section for more information.
     *  https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#asset-exchanges
     */
    public function getAmountOutAsset(): ?string
    {
        return $this->amountOutAsset;
    }

    /**
     * @param string|null $amountOutAsset The asset delivered or to be delivered to the user.
     *   Should be present if the deposit/withdraw was made using non-equivalent assets.
     *   The value must be in SEP-38 Asset Identification Format.
     *   https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format
     *   See also the Asset Exchanges section for more information.
     *   https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#asset-exchanges
     */
    public function setAmountOutAsset(?string $amountOutAsset): void
    {
        $this->amountOutAsset = $amountOutAsset;
    }

    /**
     * @return ?string Amount of fee charged by anchor.
     */
    public function getAmountFee(): ?string
    {
        return $this->amountFee;
    }

    /**
     * @param ?string $amountFee Amount of fee charged by anchor.
     */
    public function setAmountFee(?string $amountFee): void
    {
        $this->amountFee = $amountFee;
    }

    /**
     * @return string|null The asset in which fees are calculated in.
     *  Should be present if the deposit/withdraw was made using non-equivalent assets.
     *  The value must be in SEP-38 Asset Identification Format.
     *  https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format
     *  See also the Asset Exchanges section for more information.
     *  https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#asset-exchanges
     */
    public function getAmountFeeAsset(): ?string
    {
        return $this->amountFeeAsset;
    }

    /**
     * @param string|null $amountFeeAsset The asset in which fees are calculated in.
     *   Should be present if the deposit/withdraw was made using non-equivalent assets.
     *   The value must be in SEP-38 Asset Identification Format.
     *   https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0038.md#asset-identification-format
     *   See also the Asset Exchanges section for more information.
     *   https://github.com/stellar/stellar-protocol/blob/master/ecosystem/sep-0024.md#asset-exchanges
     */
    public function setAmountFeeAsset(?string $amountFeeAsset): void
    {
        $this->amountFeeAsset = $amountFeeAsset;
    }

    /**
     * @return string|null (optional) The ID of the quote (sep-38) used when creating this transaction.
     *  Should be present if a quote_id was included in the POST /transactions/deposit/interactive
     *  or POST /transactions/withdraw/interactive request.
     *  Clients should be aware that the quote_id may not be present in older implementations.
     */
    public function getQuoteId(): ?string
    {
        return $this->quoteId;
    }

    /**
     * @param string|null $quoteId (optional) The ID of the quote (sep-38) used when creating this transaction.
     *   Should be present if a quote_id was included in the POST /transactions/deposit/interactive
     *   or POST /transactions/withdraw/interactive request.
     *   Clients should be aware that the quote_id may not be present in older implementations.
     */
    public function setQuoteId(?string $quoteId): void
    {
        $this->quoteId = $quoteId;
    }

    /**
     * @return string Start date and time of transaction. UTC ISO 8601 string.
     */
    public function getStartedAt(): string
    {
        return $this->startedAt;
    }

    /**
     * @param string $startedAt Start date and time of transaction. UTC ISO 8601 string.
     */
    public function setStartedAt(string $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    /**
     * @return string|null The date and time of transaction reaching completed or refunded status. UTC ISO 8601 string.
     */
    public function getCompletedAt(): ?string
    {
        return $this->completedAt;
    }

    /**
     * @param string|null $completedAt The date and time of transaction reaching completed or refunded status. UTC ISO 8601 string.
     */
    public function setCompletedAt(?string $completedAt): void
    {
        $this->completedAt = $completedAt;
    }

    /**
     * @return string|null The date and time of transaction reaching the current status. UTC ISO 8601 string.
     */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    /**
     * @param string|null $updatedAt The date and time of transaction reaching the current status. UTC ISO 8601 string.
     */
    public function setUpdatedAt(?string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Gets the deadline by when user action is required
     *
     * @return string|null The date and time by when user action is required. UTC ISO 8601 string. Only specified for statuses where user action is required.
     */
    public function getUserActionRequiredBy(): ?string
    {
        return $this->userActionRequiredBy;
    }

    /**
     * Sets the deadline by when user action is required
     *
     * @param string|null $userActionRequiredBy The date and time by when user action is required. UTC ISO 8601 string.
     */
    public function setUserActionRequiredBy(?string $userActionRequiredBy): void
    {
        $this->userActionRequiredBy = $userActionRequiredBy;
    }

    /**
     * @return string|null transaction_id on Stellar network of the transfer that either completed the deposit or started the withdrawal.
     */
    public function getStellarTransactionId(): ?string
    {
        return $this->stellarTransactionId;
    }

    /**
     * @param string|null $stellarTransactionId transaction_id on Stellar network of the transfer that either completed the deposit or started the withdrawal.
     */
    public function setStellarTransactionId(?string $stellarTransactionId): void
    {
        $this->stellarTransactionId = $stellarTransactionId;
    }

    /**
     * @return string|null ID of transaction on external network that either started the deposit or completed the withdrawal.
     */
    public function getExternalTransactionId(): ?string
    {
        return $this->externalTransactionId;
    }

    /**
     * @param string|null $externalTransactionId ID of transaction on external network that either started the deposit or completed the withdrawal.
     */
    public function setExternalTransactionId(?string $externalTransactionId): void
    {
        $this->externalTransactionId = $externalTransactionId;
    }

    /**
     * @return string|null Human-readable explanation of transaction status, if needed.
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string|null $message Human-readable explanation of transaction status, if needed.
     */
    public function setMessage(?string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return bool|null (deprecated, optional) This field is deprecated in favor of the refunds object and the refunded status.
     *  True if the transaction was refunded in full. False if the transaction was partially refunded or not refunded.
     *  For more details about any refunds, see the Refund object.
     */
    public function getRefunded(): ?bool
    {
        return $this->refunded;
    }

    /**
     * @param bool|null $refunded (deprecated, optional) This field is deprecated in favor of the refunds object and the refunded status.
     *   True if the transaction was refunded in full. False if the transaction was partially refunded or not refunded.
     *   For more details about any refunds, see the Refund object.
     */
    public function setRefunded(?bool $refunded): void
    {
        $this->refunded = $refunded;
    }

    /**
     * @return Refund|null (optional) An object describing any on or off-chain refund associated with this transaction.
     */
    public function getRefunds(): ?Refund
    {
        return $this->refunds;
    }

    /**
     * @param Refund|null $refunds (optional) An object describing any on or off-chain refund associated with this transaction.
     */
    public function setRefunds(?Refund $refunds): void
    {
        $this->refunds = $refunds;
    }

    /**
     * @return string|null In case of deposit: Sent from address, perhaps BTC, IBAN, or bank account.
     *  In case of withdraw: Stellar address the assets were withdrawn from.
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * @param string|null $from In case of deposit: Sent from address, perhaps BTC, IBAN, or bank account.
     *   In case of withdraw: Stellar address the assets were withdrawn from.
     */
    public function setFrom(?string $from): void
    {
        $this->from = $from;
    }

    /**
     * @return string|null In case of deposit: Stellar address the deposited assets were sent to.
     *  In case of withdraw: Sent to address (perhaps BTC, IBAN, or bank account in the case of a withdrawal,
     *  Stellar address in the case of a deposit).
     */
    public function getTo(): ?string
    {
        return $this->to;
    }

    /**
     * @param string|null $to In case of deposit: Stellar address the deposited assets were sent to.
     *   In case of withdraw: Sent to address (perhaps BTC, IBAN, or bank account in the case of a withdrawal,
     *   Stellar address in the case of a deposit).
     */
    public function setTo(?string $to): void
    {
        $this->to = $to;
    }

    /**
     * @return string|null (optional) Only for deposit transactions:
     *  This is the memo (if any) used to transfer the asset to the to Stellar address.
     */
    public function getDepositMemo(): ?string
    {
        return $this->depositMemo;
    }

    /**
     * @param string|null $depositMemo (optional) Only for deposit transactions:
     *   This is the memo (if any) used to transfer the asset to the to Stellar address.
     */
    public function setDepositMemo(?string $depositMemo): void
    {
        $this->depositMemo = $depositMemo;
    }

    /**
     * @return string|null (optional) Only for deposit transactions:
     *  Type for the depositMemo.
     */
    public function getDepositMemoType(): ?string
    {
        return $this->depositMemoType;
    }

    /**
     * @param string|null $depositMemoType (optional) Only for deposit transactions:
     *   Type for the depositMemo.
     */
    public function setDepositMemoType(?string $depositMemoType): void
    {
        $this->depositMemoType = $depositMemoType;
    }

    /**
     * @return string|null (optional) Only for deposit transactions:
     *  ID of the Claimable Balance used to send the asset initially requested.
     */
    public function getClaimableBalanceId(): ?string
    {
        return $this->claimableBalanceId;
    }

    /**
     * @param string|null $claimableBalanceId (optional) Only for deposit transactions:
     *   ID of the Claimable Balance used to send the asset initially requested.
     */
    public function setClaimableBalanceId(?string $claimableBalanceId): void
    {
        $this->claimableBalanceId = $claimableBalanceId;
    }

    /**
     * @return string|null (optional) Only for withdraw transactions:
     *  If this is a withdrawal, this is the anchor's Stellar account that the user transferred (or will transfer) their asset to.
     */
    public function getWithdrawAnchorAccount(): ?string
    {
        return $this->withdrawAnchorAccount;
    }

    /**
     * @param string|null $withdrawAnchorAccount (optional) Only for withdraw transactions:
     *   If this is a withdrawal, this is the anchor's Stellar account that the user transferred (or will transfer) their asset to.
     */
    public function setWithdrawAnchorAccount(?string $withdrawAnchorAccount): void
    {
        $this->withdrawAnchorAccount = $withdrawAnchorAccount;
    }

    /**
     * @return string|null (optional) Only for withdraw transactions:
     *  Memo used when the user transferred to withdrawAnchorAccount.
     *  Assigned null if the withdrawal is not ready to receive payment, for example if KYC is not completed.
     */
    public function getWithdrawMemo(): ?string
    {
        return $this->withdrawMemo;
    }

    /**
     * @param string|null $withdrawMemo (optional) Only for withdraw transactions:
     *   Memo used when the user transferred to withdrawAnchorAccount.
     *   Assigned null if the withdrawal is not ready to receive payment, for example if KYC is not completed.
     */
    public function setWithdrawMemo(?string $withdrawMemo): void
    {
        $this->withdrawMemo = $withdrawMemo;
    }

    /**
     * @return string|null (optional) Only for withdraw transactions:
     *  Memo type for withdrawMemo.
     */
    public function getWithdrawMemoType(): ?string
    {
        return $this->withdrawMemoType;
    }

    /**
     * @param string|null $withdrawMemoType (optional) Only for withdraw transactions:
     *   Memo type for withdrawMemo.
     */
    public function setWithdrawMemoType(?string $withdrawMemoType): void
    {
        $this->withdrawMemoType = $withdrawMemoType;
    }
}